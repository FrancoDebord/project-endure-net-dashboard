<?php

namespace App\Http\Controllers;

use App\Services\RedCapService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GpsExportController extends Controller
{
    private const CLUSTER_LABELS = [
        '1' => 'Djigbe / Grappe 1',  '2' => 'Djigbe / Grappe 2',
        '3' => 'Gbonou / Grappe 1',  '4' => 'Gbonou / Grappe 2',  '5' => 'Gbonou / Grappe 3',
        '6' => 'Miniffi / Grappe 1', '7' => 'Miniffi / Grappe 2', '8' => 'Miniffi / Grappe 3',
    ];
    private const CLUSTER_FILENAMES = [
        '1' => 'Djigbe_Grappe-1',  '2' => 'Djigbe_Grappe-2',
        '3' => 'Gbonou_Grappe-1',  '4' => 'Gbonou_Grappe-2',  '5' => 'Gbonou_Grappe-3',
        '6' => 'Miniffi_Grappe-1', '7' => 'Miniffi_Grappe-2', '8' => 'Miniffi_Grappe-3',
    ];
    private const COHORT_LABELS = ['1' => 'Cohorte A', '2' => 'Cohorte B'];
    private const VILLAGE_LABELS = [
        'DJ' => 'Djigbe', '1' => 'Djigbe',
        'GB' => 'Gbonou', '2' => 'Gbonou',
        'MI' => 'Miniffi', '3' => 'Miniffi',
    ];

    public function __construct(private RedCapService $redcap) {}

    public function index(): View
    {
        return view('gps-export');
    }

    public function export(Request $request): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $selectedClusters = array_values(array_filter((array) $request->input('clusters', [])));
        $selectedCohorts  = array_values(array_filter((array) $request->input('cohorts', [])));
        $consentOnly      = $request->boolean('consent_only', true);

        $idRaw     = $this->redcap->getIdentificationData();
        $qbRaw     = $this->redcap->getBaselineQuestionnaireData();
        $visiteRaw = $this->redcap->getVisiteData();

        // Lookup tables keyed by household_id
        $qbByHh = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $visiteByHh = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        // Build unified household list
        $households = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->unique('household_id')
            ->map(function ($r) use ($qbByHh, $visiteByHh) {
                $qb     = $qbByHh->get($r['household_id'], []);
                $visite = $visiteByHh->get($r['household_id'], []);

                // GPS: identification form first, fallback to questionnaire_base
                $lat = ($r['a_geo_latitude_id']   ?? '') ?: ($visite['a_geo_latitude_id']  ?? '') ?: ($qb['b_geo_latitude']  ?? '');
                $lng = ($r['a_geo_longitude_id']  ?? '') ?: ($visite['a_geo_longitude_id'] ?? '') ?: ($qb['b_geo_longitude'] ?? '');

                $cluster = ($r['cluster_djigbe']  ?? '')
                        ?: ($r['cluster_gbonou']  ?? '')
                        ?: ($r['cluster_miniffi'] ?? '');
                $consent = ($r['consent_accepted'] ?? '') ?: ($qb['consent_accepted'] ?? '');

                return [
                    'household_id' => $r['household_id'],
                    'lat'          => trim($lat),
                    'lng'          => trim($lng),
                    'village'      => $r['village'] ?? '',
                    'cluster'      => $cluster,
                    'cohort'       => $r['study_cohort'] ?? '',
                    'fullname'     => trim($qb['b_hhh_fullname'] ?? ''),
                    'address'      => trim($qb['b_hh_address']  ?? ''),
                    'consent'      => $consent,
                ];
            })
            ->filter(fn($r) => $r['lat'] !== '' && $r['lng'] !== '');

        // Apply filters
        if (!empty($selectedClusters)) {
            $households = $households->filter(fn($r) => in_array($r['cluster'], $selectedClusters));
        }
        if (!empty($selectedCohorts)) {
            $households = $households->filter(fn($r) => in_array($r['cohort'], $selectedCohorts));
        }
        if ($consentOnly) {
            $households = $households->filter(fn($r) => $r['consent'] === '1');
        }

        if ($households->isEmpty()) {
            return redirect()->route('gps.export')
                ->with('error', 'Aucun ménage avec coordonnées GPS trouvé pour la sélection choisie.');
        }

        $byCluster = $households->groupBy('cluster')->sortKeys();

        // Build a descriptive label for the filename
        if ($byCluster->count() === 1) {
            $clusterKey  = $byCluster->keys()->first();
            $label       = self::CLUSTER_LABELS[$clusterKey] ?? "Grappe $clusterKey";
            $filename    = 'ENDURE-Net_' . (self::CLUSTER_FILENAMES[$clusterKey] ?? "Grappe-$clusterKey") . '_' . now()->format('Y-m-d') . '.gpx';
        } else {
            $label    = 'ENDURE-Net — ' . $byCluster->count() . ' grappes';
            $filename = 'ENDURE-Net_GPS_' . now()->format('Y-m-d') . '.gpx';
        }

        // Always one GPX file — no ZIP dependency required.
        // The cluster label is included in each waypoint's description so OsmAnd
        // can distinguish points by grappe without needing separate files.
        $gpx = $this->buildGpx($households, $label);

        return response($gpx, 200, [
            'Content-Type'        => 'application/gpx+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildGpx(\Illuminate\Support\Collection $points, string $label): string
    {
        $labelXml = htmlspecialchars($label, ENT_XML1, 'UTF-8');
        $count    = $points->count();
        $date     = now()->format('Y-m-d\TH:i:s\Z');

        $lines   = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $lines[] = '<gpx version="1.1" creator="ENDURE-Net Dashboard"';
        $lines[] = '     xmlns="http://www.topografix.com/GPX/1/1"';
        $lines[] = '     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $lines[] = '     xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">';
        $lines[] = '  <metadata>';
        $lines[] = "    <name>{$labelXml}</name>";
        $lines[] = "    <desc>Export ENDURE-Net &#x2014; {$count} m&#xE9;nage(s)</desc>";
        $lines[] = "    <time>{$date}</time>";
        $lines[] = '  </metadata>';

        foreach ($points->sortBy('household_id') as $p) {
            $lat    = number_format((float) $p['lat'], 7, '.', '');
            $lng    = number_format((float) $p['lng'], 7, '.', '');
            $name   = htmlspecialchars($p['household_id'], ENT_XML1, 'UTF-8');

            $cohortLabel = self::COHORT_LABELS[$p['cohort']] ?? '';
            $villageLabel = self::VILLAGE_LABELS[$p['village']] ?? $p['village'];
            $parts = array_filter([
                $p['fullname'] !== '' ? 'Chef: ' . $p['fullname'] : '',
                $cohortLabel,
                $villageLabel !== '' ? 'Village: ' . $villageLabel : '',
                $p['address']  !== '' ? 'Adresse: ' . $p['address'] : '',
            ]);
            $desc = htmlspecialchars(implode(' | ', $parts), ENT_XML1, 'UTF-8');

            $lines[] = "  <wpt lat=\"{$lat}\" lon=\"{$lng}\">";
            $lines[] = "    <name>{$name}</name>";
            if ($desc !== '') {
                $lines[] = "    <desc>{$desc}</desc>";
            }
            $lines[] = '    <type>M&#xE9;nage &#xE9;tude</type>';
            $lines[] = '  </wpt>';
        }

        $lines[] = '</gpx>';
        return implode("\n", $lines) . "\n";
    }
}
