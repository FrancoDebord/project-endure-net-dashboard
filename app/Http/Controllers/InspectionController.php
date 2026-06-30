<?php

namespace App\Http\Controllers;

use App\Services\KoboToolboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InspectionController extends Controller
{
    public function __construct(private KoboToolboxService $kobo) {}

    // Colonnes connues, dans l'ordre d'affichage, avec labels français
    private const COLUMN_LABELS = [
        'date_inspection'   => 'Date inspection',
        'code_moustiquaire' => 'Code moustiquaire',
        'type_moustiquaire' => 'Type',
        'fabricant'         => 'Fabricant',
        'modele'            => 'Modèle',
        'lot_number'        => 'N° lot',
        'numero_balle'      => 'N° balle',
        'sampling_position' => 'Position',
        'lieu_entrepot'     => 'Lieu entrepôt',
        'remarque'          => 'Remarque',
        'presence'          => 'Présence',
    ];

    private const BRAND_LABELS = [
        'PMD' => 'PermaNet Dual',
        'IG2' => 'Interceptor G2',
    ];

    private const META_KEYS = [
        '_id', '_uuid', '_submission_time', '_submitted_by',
        '_xform_id_string', '_attachments', '_status',
        '_geolocation', '_version', '_tags', '_notes',
        '_validation_status', 'formhub/uuid',
        'meta/instanceID', 'meta/deprecatedID', '_index',
        'initiales', 'moustiquaires/itn_brand',
    ];

    public function index(): View
    {
        $submissions = $this->kobo->getSubmissions();

        // ── KPI globaux ──────────────────────────────────────────────────────
        $total = count($submissions);
        $now   = now();

        $last30days = collect($submissions)->filter(fn($s) =>
            isset($s['_submission_time']) &&
            \Carbon\Carbon::parse($s['_submission_time'])->gte($now->copy()->subDays(30))
        )->count();

        $last7days = collect($submissions)->filter(fn($s) =>
            isset($s['_submission_time']) &&
            \Carbon\Carbon::parse($s['_submission_time'])->gte($now->copy()->subDays(7))
        )->count();

        // ── Listes de filtres ────────────────────────────────────────────────
        $enumerators = collect($submissions)
            ->pluck('initiales')->filter()->unique()->sort()->values()->toArray();

        $brands = collect($submissions)
            ->pluck('moustiquaires/itn_brand')->filter()->unique()->sort()->values()->toArray();

        $lieux = collect($submissions)
            ->pluck('lieu_entrepot')->filter()->unique()->sort()->values()->toArray();

        $lots = collect($submissions)
            ->pluck('moustiquaires/lot_number')->filter()->unique()->sort()->values()->toArray();

        // mapping marque → lots pour rafraîchir le filtre lot côté client
        $brandLots = collect($submissions)
            ->groupBy('moustiquaires/itn_brand')
            ->map(fn($group) => $group
                ->pluck('moustiquaires/lot_number')
                ->filter()->unique()->sort()->values()->toArray()
            )
            ->toArray();

        // ── Comptages par marque ─────────────────────────────────────────────
        $brandCounts = collect($submissions)
            ->groupBy('moustiquaires/itn_brand')
            ->map->count()
            ->sortKeys()
            ->toArray();

        // ── Colonnes : priorité aux colonnes connues, puis colonnes extra ────
        $displayColumns = $this->resolveColumns($submissions);

        // ── Lignes pour la vue ───────────────────────────────────────────────
        $rows = collect($submissions)->map(function ($s) {
            $data = collect($s)->reject(fn($_, $k) =>
                in_array($k, self::META_KEYS) || str_starts_with($k, '_')
            )->toArray();

            // Date ISO pour le filtre (priorité à date_inspection du formulaire)
            $dateIso = match(true) {
                !empty($s['date_inspection']) => substr($s['date_inspection'], 0, 10),
                !empty($s['_submission_time']) => substr($s['_submission_time'], 0, 10),
                default                        => '',
            };

            return [
                'id'            => $s['_id'] ?? null,
                'initiales'     => $s['initiales'] ?? '—',
                'itn_brand'     => $s['moustiquaires/itn_brand'] ?? null,
                'lot_number'    => $s['moustiquaires/lot_number'] ?? null,
                'lieu_entrepot' => $s['lieu_entrepot'] ?? null,
                'date_for_filter' => $dateIso,
                'geolocation'   => $s['_geolocation'] ?? null,
                'data'          => $data,
            ];
        })->sortByDesc('id')->values()->toArray();

        $visualInspectionData = $this->buildVisualInspectionTable($submissions);

        return view('inspections', compact(
            'rows',
            'displayColumns',
            'total',
            'last30days',
            'last7days',
            'enumerators',
            'brands',
            'brandCounts',
            'lieux',
            'visualInspectionData',
        ) + ['brandLabels' => self::BRAND_LABELS, 'lots' => $lots, 'brandLots' => $brandLots]);
    }

    public function export(): StreamedResponse
    {
        abort_unless(request()->user()?->email === 'francis.houeha@airid-africa.com', 403);

        $submissions    = $this->kobo->getSubmissions();
        $displayColumns = $this->resolveColumns($submissions);
        $headers        = array_merge(['_id', 'initiales', 'moustiquaires/itn_brand'], $displayColumns, ['latitude', 'longitude']);
        $filename       = 'inspections_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($submissions, $displayColumns, $headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');

            foreach ($submissions as $s) {
                $geo = $s['_geolocation'] ?? [null, null];
                $row = [$s['_id'] ?? '', $s['initiales'] ?? '', $s['moustiquaires/itn_brand'] ?? ''];
                foreach ($displayColumns as $col) {
                    $val   = $s[$col] ?? '';
                    $row[] = is_array($val) ? implode('|', $val) : $val;
                }
                $row[] = $geo[0] ?? '';
                $row[] = $geo[1] ?? '';
                fputcsv($out, $row, ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportChartsExcel(): StreamedResponse
    {
        $submissions = $this->kobo->getSubmissions();
        $viz         = $this->buildVisualInspectionTable($submissions);

        // ── Aggregate per brand ─────────────────────────────────────────────
        $sums = [];
        foreach (['PMD', 'IG2'] as $brand) {
            $s = [
                'total'    => 0,
                'fabric'   => ['avec_trous' => 0, 'sans_trous' => 0, 'taille1' => 0, 'taille2' => 0, 'taille3' => 0, 'taille4' => 0],
                'colour'   => ['decolore' => 0, 'bon' => 0],
                'symmetry' => [],
                'tache'    => ['avec' => 0, 'sans' => 0],
                'overall'  => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            ];
            foreach ($viz[$brand] ?? [] as $r) {
                $s['total'] += $r['total'];
                foreach ($r['fabric'] as $k => $v)   { $s['fabric'][$k] += $v; }
                $s['colour']['decolore'] += $r['colour']['decolore'];
                $s['colour']['bon']      += $r['colour']['bon'];
                foreach ($r['symmetry'] as $val => $cnt) {
                    $s['symmetry'][$val] = ($s['symmetry'][$val] ?? 0) + $cnt;
                }
                $s['tache']['avec'] += $r['tache']['avec'];
                $s['tache']['sans'] += $r['tache']['sans'];
                for ($i = 1; $i <= 5; $i++) { $s['overall'][$i] += ($r['overall'][$i] ?? 0); }
            }
            $sums[$brand] = $s;
        }

        $pct = fn($n, $total): float => $total > 0 ? round($n / $total * 100, 1) : 0.0;
        $pT  = $sums['PMD']['total'];
        $iT  = $sums['IG2']['total'];

        // ── PhpSpreadsheet setup ────────────────────────────────────────────
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $SOLID   = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
        $CENTER  = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $VCENTER = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;
        $THIN    = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;

        $mkHdr = fn(string $rgb) => [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $rgb]],
            'alignment' => ['horizontal' => $CENTER, 'vertical' => $VCENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $buildSheet = function (string $title, array $rows, int $pTotal, int $iTotal) use ($spreadsheet, $mkHdr, $SOLID, $CENTER, $THIN): void {
            $sh = $spreadsheet->createSheet();
            $sh->setTitle(mb_substr($title, 0, 31));

            $sh->mergeCells('B1:C1');
            $sh->mergeCells('D1:E1');
            $sh->setCellValue('A1', 'Indicateur');
            $sh->setCellValue('B1', 'PMD — PermaNet Dual');
            $sh->setCellValue('D1', 'IG2 — Interceptor G2');
            $sh->getStyle('A1')->applyFromArray($mkHdr('1E293B'));
            $sh->getStyle('B1:C1')->applyFromArray($mkHdr('6D28D9'));
            $sh->getStyle('D1:E1')->applyFromArray($mkHdr('0369A1'));
            $sh->getRowDimension(1)->setRowHeight(22);

            foreach (['A2' => '', 'B2' => 'n', 'C2' => '%', 'D2' => 'n', 'E2' => '%'] as $cell => $label) {
                $sh->setCellValue($cell, $label);
            }
            $sh->getStyle('A2:E2')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => '475569']],
                'alignment' => ['horizontal' => $CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => $THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);

            $r = 3;
            foreach ($rows as $idx => [$label, $pN, $pP, $iN, $iP]) {
                $sh->setCellValue("A{$r}", $label);
                $sh->setCellValue("B{$r}", $pN);
                $sh->setCellValue("C{$r}", $pP);
                $sh->setCellValue("D{$r}", $iN);
                $sh->setCellValue("E{$r}", $iP);
                $sh->getStyle("C{$r}")->getNumberFormat()->setFormatCode('0.0"%"');
                $sh->getStyle("E{$r}")->getNumberFormat()->setFormatCode('0.0"%"');
                if ($idx % 2 === 1) {
                    $sh->getStyle("A{$r}:E{$r}")->applyFromArray([
                        'fill' => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    ]);
                }
                $r++;
            }

            $sh->setCellValue("A{$r}", 'TOTAL');
            $sh->setCellValue("B{$r}", $pTotal);
            $sh->setCellValue("C{$r}", '100 %');
            $sh->setCellValue("D{$r}", $iTotal);
            $sh->setCellValue("E{$r}", '100 %');
            $sh->getStyle("A{$r}:E{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $sh->getStyle("A3:E{$r}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => $THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            $sh->getColumnDimension('A')->setWidth(33);
            foreach (['B', 'C', 'D', 'E'] as $col) { $sh->getColumnDimension($col)->setWidth(16); }
            $sh->freezePane('A3');
        };

        // ── 1. Intégrité du tissu ───────────────────────────────────────────
        $buildSheet('Intégrité du tissu', [
            ['Intactes (sans trous)', $sums['PMD']['fabric']['sans_trous'], $pct($sums['PMD']['fabric']['sans_trous'], $pT), $sums['IG2']['fabric']['sans_trous'], $pct($sums['IG2']['fabric']['sans_trous'], $iT)],
            ['Avec trous',            $sums['PMD']['fabric']['avec_trous'], $pct($sums['PMD']['fabric']['avec_trous'], $pT), $sums['IG2']['fabric']['avec_trous'], $pct($sums['IG2']['fabric']['avec_trous'], $iT)],
        ], $pT, $iT);

        // ── 2. Trous par taille ─────────────────────────────────────────────
        $pHoles = $sums['PMD']['fabric']['taille1'] + $sums['PMD']['fabric']['taille2'] + $sums['PMD']['fabric']['taille3'] + $sums['PMD']['fabric']['taille4'];
        $iHoles = $sums['IG2']['fabric']['taille1'] + $sums['IG2']['fabric']['taille2'] + $sums['IG2']['fabric']['taille3'] + $sums['IG2']['fabric']['taille4'];
        $buildSheet('Trous par taille', [
            ['Petits (< 0.5 cm²)',     $sums['PMD']['fabric']['taille1'], $pct($sums['PMD']['fabric']['taille1'], $pHoles), $sums['IG2']['fabric']['taille1'], $pct($sums['IG2']['fabric']['taille1'], $iHoles)],
            ['Moyens (0.5 – 2 cm²)',   $sums['PMD']['fabric']['taille2'], $pct($sums['PMD']['fabric']['taille2'], $pHoles), $sums['IG2']['fabric']['taille2'], $pct($sums['IG2']['fabric']['taille2'], $iHoles)],
            ['Grands (2 – 10 cm²)',    $sums['PMD']['fabric']['taille3'], $pct($sums['PMD']['fabric']['taille3'], $pHoles), $sums['IG2']['fabric']['taille3'], $pct($sums['IG2']['fabric']['taille3'], $iHoles)],
            ['Très grands (> 10 cm²)', $sums['PMD']['fabric']['taille4'], $pct($sums['PMD']['fabric']['taille4'], $pHoles), $sums['IG2']['fabric']['taille4'], $pct($sums['IG2']['fabric']['taille4'], $iHoles)],
        ], $pHoles, $iHoles);

        // ── 3. Couleur ──────────────────────────────────────────────────────
        $buildSheet('Couleur', [
            ['Bonne couleur', $sums['PMD']['colour']['bon'],      $pct($sums['PMD']['colour']['bon'],      $pT), $sums['IG2']['colour']['bon'],      $pct($sums['IG2']['colour']['bon'],      $iT)],
            ['Décolorée',     $sums['PMD']['colour']['decolore'], $pct($sums['PMD']['colour']['decolore'], $pT), $sums['IG2']['colour']['decolore'], $pct($sums['IG2']['colour']['decolore'], $iT)],
        ], $pT, $iT);

        // ── 4. Symétrie ─────────────────────────────────────────────────────
        $allSymVals = array_unique(array_merge(array_keys($sums['PMD']['symmetry']), array_keys($sums['IG2']['symmetry'])));
        sort($allSymVals);
        $pSymTotal = (int) array_sum($sums['PMD']['symmetry']);
        $iSymTotal = (int) array_sum($sums['IG2']['symmetry']);
        $symRows = array_map(fn($val) => [
            (string) $val,
            $sums['PMD']['symmetry'][$val] ?? 0,
            $pct($sums['PMD']['symmetry'][$val] ?? 0, $pSymTotal),
            $sums['IG2']['symmetry'][$val] ?? 0,
            $pct($sums['IG2']['symmetry'][$val] ?? 0, $iSymTotal),
        ], $allSymVals);
        $buildSheet('Symétrie', $symRows, $pSymTotal, $iSymTotal);

        // ── 5. Présence de tache ────────────────────────────────────────────
        $buildSheet('Présence de tache', [
            ['Sans tache', $sums['PMD']['tache']['sans'], $pct($sums['PMD']['tache']['sans'], $pT), $sums['IG2']['tache']['sans'], $pct($sums['IG2']['tache']['sans'], $iT)],
            ['Avec tache', $sums['PMD']['tache']['avec'], $pct($sums['PMD']['tache']['avec'], $pT), $sums['IG2']['tache']['avec'], $pct($sums['IG2']['tache']['avec'], $iT)],
        ], $pT, $iT);

        // ── 6. État général ─────────────────────────────────────────────────
        $gradeLabels = [1 => 'Grade 1 — Excellent', 2 => 'Grade 2 — Bon', 3 => 'Grade 3 — Moyen', 4 => 'Grade 4 — Mauvais', 5 => 'Grade 5 — Très mauvais'];
        $buildSheet('État général', array_map(fn($i) => [
            $gradeLabels[$i],
            $sums['PMD']['overall'][$i] ?? 0,
            $pct($sums['PMD']['overall'][$i] ?? 0, $pT),
            $sums['IG2']['overall'][$i] ?? 0,
            $pct($sums['IG2']['overall'][$i] ?? 0, $iT),
        ], [1, 2, 3, 4, 5]), $pT, $iT);

        // ── 7. Détail par lot-balle ─────────────────────────────────────────
        $coordFromIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::class . '::stringFromColumnIndex';
        $dsh          = $spreadsheet->createSheet();
        $dsh->setTitle('Détail par lot-balle');
        $dHeaders = ['Bras', 'Lot', 'Balle', 'Position', 'Total', 'Intactes', 'Avec trous', 'T1 (<0.5cm²)', 'T2 (0.5-2cm²)', 'T3 (2-10cm²)', 'T4 (>10cm²)', 'Bonne couleur', 'Décolorée', 'Symétrie', 'Sans tache', 'Avec tache', 'G1', 'G2', 'G3', 'G4', 'G5'];
        $lastDCol     = $coordFromIdx(count($dHeaders));
        foreach ($dHeaders as $ci => $h) {
            $dsh->setCellValue($coordFromIdx($ci + 1) . '1', $h);
        }
        $dsh->getStyle('A1:' . $lastDCol . '1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => $CENTER, 'vertical' => $VCENTER],
        ]);
        $dsh->getRowDimension(1)->setRowHeight(20);

        $dr = 2;
        foreach (['PMD', 'IG2'] as $brand) {
            foreach ($viz[$brand] ?? [] as $row) {
                $rowData = [
                    $brand, $row['lot'], $row['bale'], $row['pos'], $row['total'],
                    $row['fabric']['sans_trous'], $row['fabric']['avec_trous'],
                    $row['fabric']['taille1'], $row['fabric']['taille2'],
                    $row['fabric']['taille3'], $row['fabric']['taille4'],
                    $row['colour']['bon'], $row['colour']['decolore'],
                    implode(', ', array_map(fn($k, $v) => "{$k}:{$v}", array_keys($row['symmetry']), $row['symmetry'])),
                    $row['tache']['sans'], $row['tache']['avec'],
                    $row['overall'][1] ?? 0, $row['overall'][2] ?? 0, $row['overall'][3] ?? 0, $row['overall'][4] ?? 0, $row['overall'][5] ?? 0,
                ];
                foreach ($rowData as $ci => $val) {
                    $dsh->setCellValue($coordFromIdx($ci + 1) . $dr, $val);
                }
                if ($dr % 2 === 0) {
                    $dsh->getStyle('A' . $dr . ':' . $lastDCol . $dr)->applyFromArray([
                        'fill' => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    ]);
                }
                $dr++;
            }
        }
        foreach (range(1, count($dHeaders)) as $ci) {
            $dsh->getColumnDimension($coordFromIdx($ci))->setAutoSize(true);
        }
        $dsh->freezePane('A2');
        $dsh->setAutoFilter('A1:' . $lastDCol . '1');

        // ── Stream ──────────────────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'analyse-comparative-pmd-ig2-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function clearCache(): RedirectResponse
    {
        $this->kobo->clearCache();
        return redirect()->route('inspections')->with('success', 'Cache KoboToolbox vidé.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Read a field from a submission, trying the moustiquaires/ prefix first.
     */
    private function getField(array $s, string $key): ?string
    {
        // 1. Try the most common group prefix first
        if (isset($s["moustiquaires/{$key}"]) && $s["moustiquaires/{$key}"] !== null && $s["moustiquaires/{$key}"] !== '') {
            return (string)$s["moustiquaires/{$key}"];
        }
        // 2. Try bare key
        if (isset($s[$key]) && $s[$key] !== null && $s[$key] !== '') {
            return (string)$s[$key];
        }
        // 3. Fallback: scan all keys for any group prefix ending with /{key}
        //    (handles group_xyz/etat_moustiquaire and similar patterns)
        foreach ($s as $k => $v) {
            if (str_ends_with($k, "/{$key}") && $v !== null && $v !== '') {
                return (string)$v;
            }
        }
        return null;
    }

    /**
     * Aggregate submissions into the visual inspection table structure.
     * Each row = one unique (brand × lot × bale × sampling_position) combination.
     */
    private function buildVisualInspectionTable(array $submissions): array
    {
        $table = ['PMD' => [], 'IG2' => []];

        $groups = collect($submissions)
            ->filter(fn($s) => in_array($this->getField($s, 'itn_brand'), ['PMD', 'IG2'], true))
            ->groupBy(fn($s) => implode('|||', [
                $this->getField($s, 'itn_brand')         ?? '',
                $this->getField($s, 'lot_number')        ?? '',
                $this->getField($s, 'numero_balle')      ?? '',
                $this->getField($s, 'sampling_position') ?? '',
            ]));

        foreach ($groups as $key => $group) {
            [$brand, $lot, $bale, $pos] = explode('|||', $key, 4);
            if (!isset($table[$brand])) continue;

            $total = $group->count();

            // ── Fabric integrity (presence_trous + tailles) ──────────────────
            $avecTrous = $group->filter(fn($s) => $this->getField($s, 'presence_trous') === '1')->count();
            $fabric = [
                'avec_trous' => $avecTrous,
                'sans_trous' => $total - $avecTrous,
                'taille1'    => $group->sum(fn($s) => (int)($this->getField($s, 'nb_taille1') ?? 0)),
                'taille2'    => $group->sum(fn($s) => (int)($this->getField($s, 'nb_taille2') ?? 0)),
                'taille3'    => $group->sum(fn($s) => (int)($this->getField($s, 'nb_taille3') ?? 0)),
                'taille4'    => $group->sum(fn($s) => (int)($this->getField($s, 'nb_taille4') ?? 0)),
            ];

            // ── Colour (decoloration) ────────────────────────────────────────
            $decolore = $group->filter(fn($s) => $this->getField($s, 'decoloration') === '1')->count();

            // ── Symmetry: count each distinct value ──────────────────────────
            $symmetry = $group
                ->groupBy(fn($s) => $this->getField($s, 'symmetric') ?? '—')
                ->map->count()
                ->sortKeys()
                ->toArray();

            // ── Tache (presence_tache) ───────────────────────────────────────
            $avecTache = $group->filter(fn($s) => $this->getField($s, 'presence_tache') === '1')->count();

            // ── Overall damage (etat_moustiquaire 1–5) ───────────────────────
            $overall = [];
            for ($i = 1; $i <= 5; $i++) {
                $code = (string)$i;
                $overall[$i] = $group->filter(fn($s) => $this->getField($s, 'etat_moustiquaire') === $code)->count();
            }

            $table[$brand][] = [
                'lot'      => $lot,
                'bale'     => $bale,
                'pos'      => $pos,
                'total'    => $total,
                'fabric'   => $fabric,
                'colour'   => ['decolore' => $decolore, 'bon' => $total - $decolore],
                'symmetry' => $symmetry,
                'tache'    => ['avec' => $avecTache, 'sans' => $total - $avecTache],
                'overall'  => $overall,
            ];
        }

        // Sort each brand group by lot → bale → position
        foreach (['PMD', 'IG2'] as $brand) {
            usort($table[$brand], fn($a, $b) =>
                [$a['lot'], $a['bale'], $a['pos']] <=> [$b['lot'], $b['bale'], $b['pos']]
            );
        }

        return $table;
    }

    private function resolveColumns(array $submissions): array
    {
        $extraColumns = collect($submissions)
            ->flatMap(fn($s) => array_keys($s))
            ->unique()
            ->reject(fn($k) =>
                in_array($k, self::META_KEYS) ||
                str_starts_with($k, '_') ||
                array_key_exists($k, self::COLUMN_LABELS)
            )
            ->values()
            ->toArray();

        $allKeys      = collect($submissions)->flatMap(fn($s) => array_keys($s))->unique();
        $knownPresent = array_filter(array_keys(self::COLUMN_LABELS), fn($k) => $allKeys->contains($k));

        return array_merge($knownPresent, $extraColumns);
    }
}
