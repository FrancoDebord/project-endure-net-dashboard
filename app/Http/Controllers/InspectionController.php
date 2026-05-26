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

    public function clearCache(): RedirectResponse
    {
        $this->kobo->clearCache();
        return redirect()->route('inspections')->with('success', 'Cache KoboToolbox vidé.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

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
