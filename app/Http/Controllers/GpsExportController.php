<?php

namespace App\Http\Controllers;

use App\Services\RedCapService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ZipArchive;

class GpsExportController extends Controller
{
    private const CLUSTER_LABELS = [
        '1'  => 'Djigbe / Grappe 1',  '2'  => 'Djigbe / Grappe 2',
        '3'  => 'Gbonou / Grappe 1',  '4'  => 'Gbonou / Grappe 2',  '5'  => 'Gbonou / Grappe 3',
        '6'  => 'Miniffi / Grappe 1', '7'  => 'Miniffi / Grappe 2', '8'  => 'Miniffi / Grappe 3',
        '9'  => 'Miniffi / Grappe 4',
        '10' => 'Dogbo / Grappe 1',
        '11' => 'Agao / Grappe 1',
        '12' => 'Akoba / Grappe 1',
    ];
    private const CLUSTER_FILENAMES = [
        '1'  => 'Djigbe_Grappe-1',   '2'  => 'Djigbe_Grappe-2',
        '3'  => 'Gbonou_Grappe-1',   '4'  => 'Gbonou_Grappe-2',   '5'  => 'Gbonou_Grappe-3',
        '6'  => 'Miniffi_Grappe-1',  '7'  => 'Miniffi_Grappe-2',  '8'  => 'Miniffi_Grappe-3',
        '9'  => 'Miniffi_Grappe-4',
        '10' => 'Dogbo_Grappe-1',
        '11' => 'Agao_Grappe-1',
        '12' => 'Akoba_Grappe-1',
    ];
    private const COHORT_LABELS = ['1' => 'Cohorte A', '2' => 'Cohorte B'];
    private const VILLAGE_LABELS = [
        'DJ' => 'Djigbe',  '1' => 'Djigbe',
        'GB' => 'Gbonou',  '2' => 'Gbonou',
        'MI' => 'Miniffi', '3' => 'Miniffi',
        'DO' => 'Dogbo',
        'AG' => 'Agao',
        'AK' => 'Akoba',
    ];

    public function __construct(private RedCapService $redcap) {}

    public function index(): View
    {
        // Villages et grappes sont connus statiquement — aucun appel REDCap
        $villages = array_keys(array_filter(self::VILLAGE_LABELS, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY));
        $clusters = array_keys(self::CLUSTER_LABELS);

        // Tablets et DAGs depuis le cache uniquement (pas d'appel bloquant)
        $tablets = [];
        $dags    = [];
        $cached  = \Illuminate\Support\Facades\Cache::get('redcap.identification', []);
        if (!empty($cached)) {
            $rows    = collect($cached)
                ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
                ->unique('household_id');
            $tablets = $rows->map(fn($r) => count($p = explode('-', $r['household_id'])) === 5 ? $p[3] : null)
                ->filter()->unique()->sort()->values()->all();
            $dags    = $rows->pluck('redcap_data_access_group')
                ->filter(fn($v) => $v !== '' && $v !== null)
                ->unique()->sort()->values()->all();
        }

        return view('gps-export', compact('tablets', 'dags', 'villages', 'clusters'));
    }

    public function export(Request $request): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $groupBy         = in_array($request->input('group_by'), ['cluster', 'village', 'tablet', 'dag'])
                           ? $request->input('group_by') : 'cluster';
        $selectedItems   = array_values(array_filter((array) $request->input('selection', [])));
        $selectedCohorts = array_values(array_filter((array) $request->input('cohorts', [])));
        $consentOnly     = $request->boolean('consent_only', true);

        $idRaw     = $this->redcap->getIdentificationData();
        $qbRaw     = $this->redcap->getBaselineQuestionnaireData();
        $visiteRaw = $this->redcap->getVisiteData();

        $qbByHh = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $visiteByHh = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        $households = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->unique('household_id')
            ->map(function ($r) use ($qbByHh, $visiteByHh) {
                $qb     = $qbByHh->get($r['household_id'], []);
                $visite = $visiteByHh->get($r['household_id'], []);

                $lat = ($r['a_geo_latitude_id']   ?? '') ?: ($visite['a_geo_latitude_id']  ?? '') ?: ($qb['b_geo_latitude']  ?? '');
                $lng = ($r['a_geo_longitude_id']  ?? '') ?: ($visite['a_geo_longitude_id'] ?? '') ?: ($qb['b_geo_longitude'] ?? '');

                $cluster = ($r['cluster_id']      ?? '')
                        ?: ($r['cluster_djigbe']  ?? '')
                        ?: ($r['cluster_gbonou']  ?? '')
                        ?: ($r['cluster_miniffi'] ?? '');
                $consent = ($r['consent_accepted'] ?? '') ?: ($qb['consent_accepted'] ?? '');

                $parts  = explode('-', $r['household_id'] ?? '');
                $tablet = count($parts) === 5 ? $parts[3] : '';
                $dag    = $r['redcap_data_access_group'] ?? '';

                return [
                    'household_id' => $r['household_id'],
                    'lat'          => trim($lat),
                    'lng'          => trim($lng),
                    'village'      => $r['village'] ?? '',
                    'cluster'      => $cluster,
                    'tablet'       => $tablet,
                    'dag'          => $dag,
                    'cohort'       => $r['study_cohort'] ?? '',
                    'fullname'     => trim($qb['b_hhh_fullname'] ?? ''),
                    'address'      => trim($qb['b_hh_address']  ?? ''),
                    'consent'      => $consent,
                ];
            })
            ->filter(fn($r) => $r['lat'] !== '' && $r['lng'] !== '');

        if (!empty($selectedItems)) {
            $households = $households->filter(fn($r) => in_array($r[$groupBy], $selectedItems));
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

        $grouped = $households->groupBy($groupBy)->sortKeys();

        if ($grouped->count() === 1) {
            $key      = $grouped->keys()->first();
            $label    = $this->getGroupLabel($groupBy, $key);
            $gpx      = $this->buildGpx($grouped->first(), $label);
            $filename = $this->getGroupFilename($groupBy, $key) . '_' . now()->format('Y-m-d') . '.gpx';

            return response($gpx, 200, [
                'Content-Type'        => 'application/gpx+xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'endure_gps_');
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($grouped as $key => $points) {
            $label    = $this->getGroupLabel($groupBy, $key);
            $gpx      = $this->buildGpx($points, $label);
            $filename = $this->getGroupFilename($groupBy, $key) . '.gpx';
            $zip->addFromString($filename, $gpx);
        }
        $zip->close();

        $content     = file_get_contents($tmpFile);
        unlink($tmpFile);
        $suffix      = match($groupBy) { 'village' => 'Villages', 'tablet' => 'Tablettes', 'dag' => 'DAG', default => 'GPS' };
        $zipFilename = 'ENDURE-Net_' . $suffix . '_' . now()->format('Y-m-d') . '.zip';

        return response($content, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $zipFilename . '"',
        ]);
    }

    private function getGroupLabel(string $groupBy, string $key): string
    {
        return match($groupBy) {
            'cluster' => self::CLUSTER_LABELS[$key] ?? "Grappe $key",
            'village' => self::VILLAGE_LABELS[$key] ?? $key,
            'tablet'  => "Tablette $key",
            default   => $key,
        };
    }

    private function getGroupFilename(string $groupBy, string $key): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '-', $key);
        return match($groupBy) {
            'cluster' => 'ENDURE-Net_' . (self::CLUSTER_FILENAMES[$key] ?? "Grappe-$key"),
            'village' => 'ENDURE-Net_' . (self::VILLAGE_LABELS[$key] ?? $safe),
            'tablet'  => "ENDURE-Net_Tablette-$safe",
            default   => "ENDURE-Net_$safe",
        };
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

            $cohortLabel  = self::COHORT_LABELS[$p['cohort']] ?? '';
            $villageLabel = self::VILLAGE_LABELS[$p['village']] ?? $p['village'];
            $parts = array_filter([
                $p['fullname'] !== '' ? 'Chef: ' . $p['fullname'] : '',
                $cohortLabel,
                $villageLabel !== '' ? 'Village: ' . $villageLabel : '',
                $p['address']  !== '' ? 'Adresse: ' . $p['address'] : '',
                $p['tablet']   !== '' ? 'Tablette: ' . $p['tablet'] : '',
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
