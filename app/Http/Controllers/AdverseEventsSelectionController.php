<?php

namespace App\Http\Controllers;

use App\Models\AeSelectedHousehold;
use App\Models\AeSelectionRun;
use App\Services\RedCapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdverseEventsSelectionController extends Controller
{
    private const ARM_LABELS = ['PD' => 'PERMANET DUAL', 'G2' => 'INTERCEPTOR G2'];
    private const ARM_COLORS = ['PD' => '#5B21B6',       'G2' => '#0369A1'];

    private const CLUSTER_DISPLAY = [
        '1'  => 'Djigbe 1',  '2'  => 'Djigbe 2',
        '3'  => 'Gbonou 1',  '4'  => 'Gbonou 2',  '5'  => 'Gbonou 3',
        '6'  => 'Miniffi 1', '7'  => 'Miniffi 2', '8'  => 'Miniffi 3', '9' => 'Miniffi 4',
        '10' => 'Dogbo',     '11' => 'Agao',       '12' => 'Akoba',
    ];

    private const VILLAGE_LABELS = [
        'AG' => 'Agao',
        'AK' => 'Akoba',
        'DJ' => 'Djigbe',
        'GB' => 'Gbonou',
        'MI' => 'Miniffi',
        'DO' => 'Dogbo',
    ];

    public function __construct(private RedCapService $redcap) {}

    // ── GET /adverse-events/selection ──────────────────────────────────────────

    public function index(Request $request): View
    {
        $aeVisitedIds = $this->getAeVisitedIds();

        $query = AeSelectedHousehold::query();

        if ($request->filled('arm'))     $query->where('arm', $request->arm);
        if ($request->filled('village')) $query->where('village', $request->village);
        if ($request->filled('cluster')) $query->where('cluster_id', $request->cluster);
        if ($request->filled('dag'))     $query->where('dag', $request->dag);
        if ($request->filled('cohort'))  $query->where('study_cohort', $request->cohort);
        if ($request->filled('status')) {
            match($request->status) {
                'visited' => $query->whereIn('household_id', $aeVisitedIds->keys()),
                'pending' => $query->whereNotIn('household_id', $aeVisitedIds->keys()),
                default   => null,
            };
        }
        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('household_id', 'like', $q)
                    ->orWhere('head_name', 'like', $q)
                    ->orWhere('contact_name', 'like', $q)
                    ->orWhere('numero_menage', 'like', $q);
            });
        }

        $households = $query
            ->orderBy('arm')
            ->orderBy('village')
            ->orderBy('cluster_id')
            ->orderBy('household_id')
            ->paginate(50)
            ->withQueryString();

        $stats = $this->buildStats($aeVisitedIds);

        $filterOptions = [
            'villages' => AeSelectedHousehold::distinct()->orderBy('village')->pluck('village')->filter()->values(),
            'clusters' => AeSelectedHousehold::distinct()->orderBy('cluster_id')->pluck('cluster_id')->filter()->values(),
            'dags'     => AeSelectedHousehold::distinct()->orderBy('dag')->pluck('dag')->filter()->values(),
            'cohorts'  => AeSelectedHousehold::distinct()->orderBy('study_cohort')->pluck('study_cohort')->filter()->values(),
        ];

        $runs = AeSelectionRun::with('creator')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $villageLabels = self::VILLAGE_LABELS;
        $clusterLabels = self::CLUSTER_DISPLAY;
        return view('ae-selection', compact('households', 'stats', 'filterOptions', 'runs',
                                           'villageLabels', 'clusterLabels', 'aeVisitedIds'));
    }

    // ── POST /adverse-events/selection/select ──────────────────────────────────

    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'arm'   => 'nullable|in:PD,G2',
            'count' => 'required|integer|min:1|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $arms  = $validated['arm'] ? [$validated['arm']] : ['PD', 'G2'];
        $count = (int) $validated['count'];
        $pool  = $this->buildEligiblePool();

        DB::beginTransaction();
        try {
            foreach ($arms as $arm) {
                AeSelectedHousehold::where('arm', $arm)->delete();

                $armPool  = $pool->filter(fn($h) => $h['arm'] === $arm)->values();
                $selected = $this->stratifiedSample($armPool, $count);

                $run = AeSelectionRun::create([
                    'arm'            => $arm,
                    'target_count'   => $count,
                    'selected_count' => $selected->count(),
                    'notes'          => $validated['notes'] ?? null,
                    'created_by'     => auth()->id(),
                ]);

                $now = now()->toDateTimeString();
                foreach ($selected->chunk(200) as $chunk) {
                    AeSelectedHousehold::insert(
                        $chunk->map(fn($h) => array_merge($h, [
                            'run_id'     => $run->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]))->toArray()
                    );
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la sélection : ' . $e->getMessage());
        }

        $label = $validated['arm'] ? self::ARM_LABELS[$validated['arm']] : 'les deux bras';
        return redirect()->route('ae.selection.index')
            ->with('success', "Sélection aléatoire effectuée pour {$label} ({$count} ménages cibles par bras).");
    }

    // ── POST /adverse-events/selection/add-more ────────────────────────────────

    public function addMore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'arm'   => 'required|in:PD,G2',
            'count' => 'required|integer|min:1|max:200',
            'notes' => 'nullable|string|max:500',
        ]);

        $arm  = $validated['arm'];
        $count = (int) $validated['count'];

        $existingIds = AeSelectedHousehold::where('arm', $arm)
            ->pluck('household_id')
            ->flip();

        $addable = $this->stratifiedSample(
            $this->buildEligiblePool()
                ->filter(fn($h) => $h['arm'] === $arm && !$existingIds->has($h['household_id']))
                ->values(),
            $count
        );

        if ($addable->isEmpty()) {
            return back()->with('error', 'Aucun ménage supplémentaire disponible pour ce bras.');
        }

        $run = AeSelectionRun::create([
            'arm'            => $arm,
            'target_count'   => $count,
            'selected_count' => $addable->count(),
            'notes'          => 'Ajout complémentaire. ' . ($validated['notes'] ?? ''),
            'created_by'     => auth()->id(),
        ]);

        $now = now()->toDateTimeString();
        foreach ($addable->chunk(200) as $chunk) {
            AeSelectedHousehold::insert(
                $chunk->map(fn($h) => array_merge($h, [
                    'run_id'     => $run->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]))->toArray()
            );
        }

        return redirect()->route('ae.selection.index')
            ->with('success', "{$addable->count()} ménage(s) supplémentaire(s) ajouté(s) pour " . self::ARM_LABELS[$arm] . ".");
    }

    // ── POST /adverse-events/selection/reset ───────────────────────────────────

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate(['arm' => 'nullable|in:PD,G2']);

        if (!empty($validated['arm'])) {
            AeSelectedHousehold::where('arm', $validated['arm'])->delete();
            $label = self::ARM_LABELS[$validated['arm']];
        } else {
            DB::table('ae_selected_households')->truncate();
            $label = 'tous les bras';
        }

        return redirect()->route('ae.selection.index')
            ->with('success', "Sélection réinitialisée pour {$label}. Vous pouvez relancer une nouvelle sélection.");
    }

    // ── POST /adverse-events/selection/{id}/toggle ─────────────────────────────

    public function toggleCompleted(Request $request, int $id): JsonResponse
    {
        $hh        = AeSelectedHousehold::findOrFail($id);
        $completed = !$hh->form_completed;

        $hh->update([
            'form_completed'    => $completed,
            'form_completed_at' => $completed ? now() : null,
            'form_completed_by' => $completed ? auth()->id() : null,
        ]);

        $arm   = $hh->arm;
        $total = AeSelectedHousehold::where('arm', $arm)->count();
        $done  = AeSelectedHousehold::where('arm', $arm)->where('form_completed', true)->count();

        return response()->json([
            'success'        => true,
            'form_completed' => $completed,
            'completed_at'   => $hh->form_completed_at?->format('d/m/Y H:i'),
            'arm_total'      => $total,
            'arm_done'       => $done,
            'arm_rate'       => $total > 0 ? round($done / $total * 100, 1) : 0,
        ]);
    }

    // ── GET /adverse-events/selection/excel ────────────────────────────────────

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = AeSelectedHousehold::query();

        if ($request->filled('arm'))     $query->where('arm', $request->arm);
        if ($request->filled('village')) $query->where('village', $request->village);
        if ($request->filled('cluster')) $query->where('cluster_id', $request->cluster);
        if ($request->filled('dag'))     $query->where('dag', $request->dag);
        if ($request->filled('cohort'))  $query->where('study_cohort', $request->cohort);
        if ($request->filled('status')) {
            $query->where('form_completed', $request->status === 'completed');
        }

        $rows = $query
            ->orderBy('arm')->orderBy('village')->orderBy('cluster_id')->orderBy('household_id')
            ->get();

        $filename = 'ae-selection-' . now()->format('Y-m-d') . '.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('AE Sélection');

        // ── En-têtes ──
        $headers = [
            'A' => 'Code ménage',
            'B' => 'Bras',
            'C' => 'Cohorte',
            'D' => 'Village',
            'E' => 'Grappe',
            'F' => 'DAG',
            'G' => 'Chef de ménage',
            'H' => 'Tél. chef',
            'I' => 'Contact',
            'J' => 'Tél. contact',
            'K' => 'Meilleur moment',
            'L' => 'Adresse',
            'M' => 'Nb moustiquaires',
            'N' => 'Codes moustiquaires',
            'O' => 'Statut',
            'P' => 'Date complétion',
        ];

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C41230']],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
            $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getRowDimension(1)->setRowHeight(20);

        // ── Données ──
        $altStyle = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]];

        foreach ($rows as $i => $hh) {
            $row = $i + 2;

            $netCodes = is_array($hh->net_codes) ? implode(', ', $hh->net_codes) : '';

            $sheet->setCellValue('A' . $row, $hh->household_id);
            $sheet->setCellValue('B' . $row, $hh->arm);
            $sheet->setCellValue('C' . $row, $hh->study_cohort == '1' ? 'Cohorte A' : ($hh->study_cohort == '2' ? 'Cohorte B' : $hh->study_cohort));
            $sheet->setCellValue('D' . $row, self::VILLAGE_LABELS[$hh->village] ?? $hh->village);
            $sheet->setCellValue('E' . $row, self::CLUSTER_DISPLAY[$hh->cluster_id] ?? ($hh->cluster_id ? 'Grappe '.$hh->cluster_id : ''));
            $sheet->setCellValue('F' . $row, $hh->dag);
            $sheet->setCellValue('G' . $row, $hh->head_name);
            $sheet->setCellValue('H' . $row, $hh->head_phone);
            $sheet->setCellValue('I' . $row, $hh->contact_name);
            $sheet->setCellValue('J' . $row, $hh->contact_phone);
            $sheet->setCellValue('K' . $row, $hh->moment_visite);
            $sheet->setCellValue('L' . $row, $hh->adresse);
            $sheet->setCellValue('M' . $row, $hh->nb_moustiquaires_etude);
            $sheet->setCellValue('N' . $row, $netCodes);
            $sheet->setCellValue('O' . $row, $hh->form_completed ? 'Complété' : 'En attente');
            $sheet->setCellValue('P' . $row, $hh->form_completed_at?->format('d/m/Y H:i'));

            if ($i % 2 === 1) {
                $sheet->getStyle('A'.$row.':P'.$row)->applyFromArray($altStyle);
            }
        }

        // Figer la première ligne
        $sheet->freezePane('A2');

        // Filtre auto sur les en-têtes
        $sheet->setAutoFilter('A1:P1');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── GET /adverse-events/selection/pdf ──────────────────────────────────────

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(600);
        $aeVisitedIds = $this->getAeVisitedIds();
        $query = AeSelectedHousehold::query();

        if ($request->filled('arm'))     $query->where('arm', $request->arm);
        if ($request->filled('village')) $query->where('village', $request->village);
        if ($request->filled('cluster')) $query->where('cluster_id', $request->cluster);
        if ($request->filled('dag'))     $query->where('dag', $request->dag);
        if ($request->filled('cohort'))  $query->where('study_cohort', $request->cohort);
        if ($request->filled('status')) {
            match($request->status) {
                'visited' => $query->whereIn('household_id', $aeVisitedIds->keys()),
                'pending' => $query->whereNotIn('household_id', $aeVisitedIds->keys()),
                default   => null,
            };
        }

        $households = $query
            ->orderBy('arm')
            ->orderBy('study_cohort')
            ->orderBy('village')
            ->orderBy('cluster_id')
            ->orderBy('household_id')
            ->get();

        $stats         = $this->buildStats($aeVisitedIds);
        $filters       = $request->only(['arm', 'village', 'cluster', 'dag', 'cohort', 'status']);
        $villageLabels = self::VILLAGE_LABELS;
        $clusterLabels = self::CLUSTER_DISPLAY;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'ae-selection-pdf',
            compact('households', 'stats', 'filters', 'villageLabels', 'clusterLabels', 'aeVisitedIds')
        );
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'isPhpEnabled'         => false,
            'defaultMediaType'     => 'print',
            'dpi'                  => 96,
            'defaultFont'          => 'dejavu sans',
            'enable_php'           => false,
        ]);

        $armSlug     = $request->filled('arm')     ? '-' . strtolower($request->arm) : '';
        $clusterSlug = $request->filled('cluster')
            ? '-' . str_replace(' ', '-', self::CLUSTER_DISPLAY[$request->cluster] ?? 'grappe'.$request->cluster)
            : '';
        return $pdf->download('ae-menages-selection' . $armSlug . $clusterSlug . '-' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Helpers privés ─────────────────────────────────────────────────────────

    // Tirage stratifié proportionnel par (cohorte × grappe) — méthode des plus grands restes.
    // Garantit une représentation équilibrée des cohortes A/B et des villages/grappes.
    private function stratifiedSample(Collection $pool, int $count): Collection
    {
        $total = $pool->count();
        if ($total === 0 || $count === 0) return collect();
        if ($count >= $total) return $pool->shuffle()->values();

        // Strates = cohorte + grappe (les deux axes d'équilibre demandés)
        $strata = $pool->groupBy(
            fn($h) => ($h['study_cohort'] ?? 'X') . '|' . ($h['cluster_id'] ?? 'X')
        );

        // Allocation proportionnelle avec arrondi "floor"
        $floors = $strata->map(fn($s) => (int) floor($count * $s->count() / $total));
        $deficit = $count - $floors->sum();

        // Distribuer le reste aux strates ayant le plus grand résidu décimal
        if ($deficit > 0) {
            $residuals = $strata->map(fn($s, $k) =>
                ($count * $s->count() / $total) - $floors[$k]
            )->sortDesc()->keys()->take($deficit);

            $floors = $floors->mapWithKeys(
                fn($n, $k) => [$k => $n + ($residuals->contains($k) ? 1 : 0)]
            );
        }

        // Tirage aléatoire dans chaque strate
        $selected = collect();
        foreach ($strata as $key => $stratum) {
            $take = min($floors[$key] ?? 0, $stratum->count());
            if ($take > 0) {
                $selected = $selected->concat($stratum->shuffle()->take($take));
            }
        }

        // Combler si un arrondi a laissé un manque résiduel
        if ($selected->count() < $count) {
            $done = $selected->pluck('household_id')->flip();
            $selected = $selected->concat(
                $pool->filter(fn($h) => !$done->has($h['household_id']))
                     ->shuffle()
                     ->take($count - $selected->count())
            );
        }

        return $selected->shuffle()->values();
    }

    private function buildEligiblePool(): Collection
    {
        set_time_limit(120);

        // Parallélise tous les appels REDCap via Http::pool() (cache 30 min)
        $this->redcap->warmAll();

        $idData     = collect($this->redcap->getIdentificationData());
        $form1bData = collect($this->redcap->getForm1bData());
        $form4aData = collect($this->redcap->getForm4aData());
        $netData    = collect($this->redcap->getStudyNetData());

        // nb_moustiquaires_etude par ménage (lignes non-répétantes)
        $form4aMap = $form4aData
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('nb_moustiquaires_etude', 'household_id');

        // Codes moustiquaires par ménage (net_identifier_man, Form 4a répétitif)
        $netCodesMap = $netData
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') !== '' &&
                !empty($r['net_identifier_man'])
            )
            ->groupBy('household_id')
            ->map(fn($rows) =>
                $rows->pluck('net_identifier_man')->filter()->unique()->sort()->values()->toArray()
            );

        // Chef de ménage et contact (Form 1b)
        $form1bMap = $form1bData
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        return $idData
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['consent_accepted'] ?? '') === '1'
            )
            ->unique('household_id')
            ->map(function ($r) use ($form4aMap, $netCodesMap, $form1bMap) {
                $id    = $r['household_id'];
                $arm   = $this->inferArm($r);
                if ($arm === '') return null;

                $form1b = $form1bMap->get($id, []);

                return [
                    'household_id'           => $id,
                    'arm'                    => $arm,
                    'village'                => $r['village']                ?? null,
                    'cluster_id'             => $r['cluster_id']             ?? null,
                    'dag'                    => $r['redcap_data_access_group'] ?? null,
                    'study_cohort'           => $r['study_cohort']           ?? null,
                    'numero_menage'          => $r['numero_menage']          ?? null,
                    'head_name'              => $form1b['b_hhh_fullname']           ?? null,
                    'head_phone'             => $form1b['b_hhh_phone']               ?? null,
                    'contact_name'           => $form1b['b_hh_point_contact']        ?? null,
                    'contact_phone'          => $form1b['b_hh_point_contact_phone']  ?? null,
                    'moment_visite'          => $form1b['b_hh_moment_visite']        ?? null,
                    'adresse'                => $form1b['b_hh_address']              ?? null,
                    'nb_moustiquaires_etude' => ($v = (int)($form4aMap->get($id, 0) ?: 0)) ? $v : null,
                    'net_codes'              => json_encode($netCodesMap->get($id, [])),
                    'form_completed'         => 0,
                    'form_completed_at'      => null,
                    'form_completed_by'      => null,
                ];
            })
            ->filter()
            ->values();
    }

    private function inferArm(array $row): string
    {
        // Champ bras REDCap : 1 = PD, 2 = G2
        $bras = $row['bras'] ?? '';
        if ($bras === '1') return 'PD';
        if ($bras === '2') return 'G2';

        // Fallback : déduire depuis household_id (VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE)
        $parts = explode('-', $row['household_id'] ?? '');
        if (count($parts) >= 3) {
            $code = strtoupper($parts[2]);
            if ($code === 'PD') return 'PD';
            if ($code === 'G2') return 'G2';
        }
        return '';
    }

    // Retourne un flip-collection des household_ids ayant un enregistrement AE non-répétant dans REDCap.
    private function getAeVisitedIds(): Collection
    {
        $aeData = collect($this->redcap->getAdverseEventsData());
        return $aeData
            ->filter(fn($r) =>
                ($r['household_id'] ?? '') !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === ''
            )
            ->pluck('household_id')
            ->flip();
    }

    private function buildStats(Collection $aeVisitedIds = null): array
    {
        $aeVisitedIds ??= collect();
        $total     = AeSelectedHousehold::count();
        $completed = AeSelectedHousehold::where('form_completed', true)->count();

        $allIds   = AeSelectedHousehold::pluck('household_id')->flip();
        $visited  = $aeVisitedIds->intersectByKeys($allIds)->count();

        $byArm = [];
        foreach (['PD', 'G2'] as $arm) {
            $t       = AeSelectedHousehold::where('arm', $arm)->count();
            $d       = AeSelectedHousehold::where('arm', $arm)->where('form_completed', true)->count();
            $armIds  = AeSelectedHousehold::where('arm', $arm)->pluck('household_id')->flip();
            $armVisited = $aeVisitedIds->intersectByKeys($armIds)->count();
            $byArm[$arm] = [
                'label'          => self::ARM_LABELS[$arm],
                'color'          => self::ARM_COLORS[$arm],
                'total'          => $t,
                'completed'      => $d,
                'remaining'      => $t - $d,
                'rate'           => $t > 0 ? round($d / $t * 100, 1) : 0,
                'redcap_visited' => $armVisited,
                'redcap_pending' => $t - $armVisited,
                'redcap_rate'    => $t > 0 ? round($armVisited / $t * 100, 1) : 0,
            ];
        }

        return [
            'total'          => $total,
            'completed'      => $completed,
            'remaining'      => $total - $completed,
            'rate'           => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'redcap_visited' => $visited,
            'redcap_pending' => $total - $visited,
            'redcap_rate'    => $total > 0 ? round($visited / $total * 100, 1) : 0,
            'by_arm'         => $byArm,
        ];
    }
}
