<?php

namespace App\Http\Controllers;

use App\Services\CohortAFollowUpService;
use App\Services\RedCapService;
use App\Support\HouseholdHelpers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use HouseholdHelpers;

    /** Recherche O(1) d'un ménage dans $visits (index construit une fois par collection) */
    private array $visitIndex = [];
    private function visitOf(\Illuminate\Support\Collection $visits, string $id): ?array
    {
        $k = spl_object_id($visits);
        $this->visitIndex[$k] ??= $visits->keyBy('household_id')->all();
        return $this->visitIndex[$k][$id] ?? null;
    }

    private const TABLET_COLORS  = [
        '#C41230','#374151','#D97706','#16A34A',
        '#7C3AED','#0891B2','#DB2777','#059669',
        '#EA580C','#4338CA','#0D9488','#9333EA',
    ];


    /** Données nécessaires à tous les onglets (buildVisits) — clés de RedCapService::warm() */
    private const CORE_DATA = ['project_info', 'visite', 'identification', 'questionnaire_base'];

    /** Données propres à chaque onglet (Cohorte A : préchargée par CohortAFollowUpService) */
    private const TAB_DATA = [
        'baseline'     => ['members', 'old_nets', 'study_nets'],
        'rapport'      => ['members', 'old_nets', 'study_nets'],
        'distribution' => ['members', 'old_nets', 'study_nets'],
        'ua'           => ['ua_baseline', 'ua_24'],
        'ae'           => ['adverse_events'],
    ];

    private const EVENTS = [
        'baseline' => ['key' => 'baseline', 'label' => 'Baseline & Distribution', 'icon' => '📋', 'event' => 'baseline_et_distri_arm_1'],
        'ua'       => ['key' => 'ua',       'label' => 'User Acceptability',      'icon' => '✅', 'event' => 'user_acceptability_arm_1'],
        'ae'       => ['key' => 'ae',       'label' => 'Adverse Events',          'icon' => '⚠️', 'event' => 'adverse_events_arm_1'],
        'suivi_a'  => ['key' => 'suivi_a',  'label' => 'Suivi Cohorte A',         'icon' => '📊', 'event' => null],
        'suivi'    => ['key' => 'suivi',    'label' => 'Suivi Cohorte B',         'icon' => '🔄', 'event' => null],
    ];

    public function __construct(private RedCapService $redcap) {}

    public function index(): View
    {
        $cacheWarm = Cache::has('redcap.identification') && Cache::has('redcap.study_nets');

        // Cache froid → squelette (JS lance warmAll puis recharge)
        if (!$cacheWarm) {
            return view('dashboard-skeleton', [
                'projectInfo' => Cache::get('redcap.project_info', ['project_title' => 'ENDURE-Net Dashboard']),
                'events'      => self::EVENTS,
            ]);
        }

        // Cache chaud → shell immédiat, chaque onglet se charge en background via JS
        return view('dashboard-shell', [
            'projectInfo' => $this->redcap->getProjectInfo(),
            'events'      => self::EVENTS,
        ]);
    }

    // POST /dashboard/warm — préchauffage du cache REDCap en arrière-plan
    public function warmCache(): \Illuminate\Http\JsonResponse
    {
        set_time_limit(180);
        try {
            // Uniquement le socle commun : chaque onglet précharge ensuite ses propres données
            $this->redcap->warm(array_merge(self::CORE_DATA, ['study_nets']));
            return response()->json(['done' => true]);
        } catch (\Exception $e) {
            return response()->json(['done' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function section(Request $request, string $tab): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $this->redcap->warm(array_merge(self::CORE_DATA, self::TAB_DATA[$tab] ?? []));

        $visiteRaw   = $this->redcap->getVisiteData();
        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $visits      = $this->buildVisits($visiteRaw, $idRaw, $qbRaw);

        $reportTablets = collect($visiteRaw)
            ->filter(fn($r) => ($r['a_tablette_id'] ?? '') !== '')
            ->pluck('a_tablette_id')->unique()->sort()->values();

        $data = [
            'events'          => self::EVENTS,
            'projectInfo'     => $this->redcap->getProjectInfo(),
            'reportTablets'   => $reportTablets,
            'renderSection'   => $tab,
            // Provide safe defaults for variables used by other tabs
            'baseline'        => [],
            'ua'              => [],
            'ua24'            => [],
            'ae'              => [],
            'followUpA'       => [],
            'followUpB'       => [],
            'performanceChart'=> ['labels' => [], 'datasets' => []],
        ];

        switch ($tab) {
            case 'baseline':
            case 'rapport':
            case 'distribution':
                $memberRaw   = $this->redcap->getMemberData();
                $oldNetRaw   = $this->redcap->getOldNetData();
                $studyNetRaw = $this->redcap->getStudyNetData();
                $data['baseline']         = $this->buildBaselineData($visits, $qbRaw, $memberRaw, $oldNetRaw, $studyNetRaw, $idRaw);
                $data['performanceChart'] = $this->buildPerformanceChartData($visiteRaw, $idRaw, $qbRaw);
                break;

            case 'ua':
                $uaRaw   = $this->redcap->getUserAcceptabilityData('user_acceptability_arm_1');
                $ua24Raw = $this->redcap->getUserAcceptabilityData('suivi_24_mois_arm_1');
                $idMap   = collect($idRaw)->filter(fn($r) => $r['household_id'] !== '')->keyBy('household_id');
                $data['ua']   = $this->buildUAData($uaRaw, $visits, $idMap);
                $data['ua24'] = $this->buildUAData($ua24Raw, $visits, $idMap);
                break;

            case 'ae':
                $aeRaw      = $this->redcap->getAdverseEventsData();
                $data['ae'] = $this->buildAEData($aeRaw, $visits);
                break;

            case 'followup':
            case 'suivi_a':
            case 'suivi':
                if ($tab !== 'suivi') {
                    // Cohorte A : Visite, Questionnaire Base, Membres, Avant Suivi, Form 7, 8, 9
                    $fuA = app(CohortAFollowUpService::class)->loadAll();
                    // statusById (1 600+ ménages) n'est utile qu'à la page de suivi individuel
                    $data['followUpA'] = array_map(fn($tp) => array_diff_key($tp, ['statusById' => 1]), $fuA['timepoints']);
                }
                if ($tab !== 'suivi_a') {
                    // Cohorte B : Form 10 uniquement (une instance par moustiquaire prélevée)
                    $fuB = app(\App\Services\CohortBFollowUpService::class)->loadAll();
                    $data['followUpB'] = array_map(fn($tp) => array_diff_key($tp, ['statusById' => 1]), $fuB['timepoints']);
                }
                break;
        }

        $html = view('dashboard', $data)->render();

        // Compression gzip (sections de plusieurs Mo, très répétitives) si le serveur ne l'a pas déjà fait
        $headers = ['Content-Type' => 'text/html; charset=UTF-8', 'Vary' => 'Accept-Encoding'];
        if (str_contains((string) $request->header('Accept-Encoding'), 'gzip')
            && !ini_get('zlib.output_compression') && function_exists('gzencode')) {
            $html = gzencode($html, 6);
            $headers['Content-Encoding'] = 'gzip';
        }
        return response()->make($html, 200, $headers);
    }

    public function exportRapportPdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $tablets  = array_values(array_filter((array)$request->input('tablets', [])));

        $visiteRaw   = $this->redcap->getVisiteData();
        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $studyNetRaw = $this->redcap->getStudyNetData();

        $idMap = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        // consent_accepted may live in either identification or questionnaire_base
        $qbConsentMap = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('consent_accepted', 'household_id');

        $consented = fn(string $id): bool =>
            (($idMap->get($id, [])['consent_accepted'] ?? '') ?: ($qbConsentMap->get($id, ''))) === '1';

        $allNetsByHh = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'section_5_moustiquaires_imprgnes_dinsecticide_appa' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        // ── Section 1: Daily activity (filtered by period/tablet/initials) ──
        $visits = collect($visiteRaw)->filter(fn($r) => $r['household_id'] !== '');
        if ($dateFrom) $visits = $visits->filter(fn($r) => ($r['a_date_visite_id'] ?? '') >= $dateFrom);
        if ($dateTo)   $visits = $visits->filter(fn($r) => ($r['a_date_visite_id'] ?? '') <= $dateTo);
        if (!empty($tablets)) $visits = $visits->filter(fn($r) => in_array($r['a_tablette_id'] ?? '', $tablets));

        $daily = [];
        foreach ($visits->groupBy('a_date_visite_id')->sortKeys() as $date => $byDate) {
            $dateTablets = [];
            foreach ($byDate->groupBy('a_tablette_id')->sortKeys() as $tablet => $byTablet) {
                $hhIds    = $byTablet->pluck('household_id')->unique();
                $enrolled = $hhIds->filter(fn($id) => $consented($id));
                $nets     = $enrolled->sum(fn($id) => $allNetsByHh->get($id, 0));
                $dateTablets[] = [
                    'tablet'   => $tablet ?: '—',
                    'visited'  => $hhIds->count(),
                    'enrolled' => $enrolled->count(),
                    'nets'     => $nets,
                ];
            }
            $daily[] = [
                'date'     => $date,
                'tablets'  => $dateTablets,
                'visited'  => array_sum(array_column($dateTablets, 'visited')),
                'enrolled' => array_sum(array_column($dateTablets, 'enrolled')),
                'nets'     => array_sum(array_column($dateTablets, 'nets')),
            ];
        }

        // Unique HH IDs across the whole period (avoids double-counting households
        // that have visit records on multiple days)
        $allPeriodHhIds      = $visits->pluck('household_id')->unique();
        $allPeriodEnrolledIds = $allPeriodHhIds->filter(fn($id) => $consented($id));
        $periodVisited  = $allPeriodHhIds->count();
        $periodEnrolled = $allPeriodEnrolledIds->count();
        $periodNets     = $allPeriodEnrolledIds->sum(fn($id) => $allNetsByHh->get($id, 0));

        // ── Section 2: Cumulative summary (all data, no date/tablet filter) ──
        $allEnrolled = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->filter(fn($r) => $consented($r['household_id']))
            ->map(function ($r) use ($allNetsByHh) {
                $bras    = self::resolveBrasKey($r);
                $cluster = self::extractCluster($r);
                $parsed  = self::parseHouseholdId($r['household_id'] ?? '');
                $village = ($r['village'] ?? '') ?: ($parsed['village'] ?? '');
                return array_merge($r, [
                    'bras'    => $bras,
                    'cluster' => $cluster,
                    'village' => $village,
                    'nets'    => $allNetsByHh->get($r['household_id'], 0),
                ]);
            });

        $byBrasCohort = $allEnrolled->groupBy('bras')->map(fn($g, $bras) => [
            'bras'    => self::BRAS_LABELS[$bras] ?? "Bras $bras",
            'total'   => $g->count(),
            'nets'    => $g->sum('nets'),
            'cohorts' => $g->groupBy('study_cohort')->map(fn($sg, $c) => [
                'cohort' => self::COHORT_LABELS[$c] ?? "Cohorte $c",
                'total'  => $sg->count(),
                'nets'   => $sg->sum('nets'),
            ])->sortKeys()->values(),
        ])->sortKeys()->values();

        $byVillage = $allEnrolled->groupBy('village')->map(fn($g, $v) => [
            'village' => self::VILLAGE_LABELS[$v] ?? ($v ?: 'Inconnu'),
            'total'   => $g->count(),
            'nets'    => $g->sum('nets'),
        ])->values();

        $byCluster = $allEnrolled->groupBy('cluster')->map(fn($g, $c) => [
            'cluster' => self::CLUSTER_LABELS[$c] ?? ($c ? "Grappe $c" : 'Inconnu'),
            'total'   => $g->count(),
            'nets'    => $g->sum('nets'),
        ])->sortKeys()->values();

        $totalEnrolled = $allEnrolled->count();
        $totalNets     = $allEnrolled->sum('nets');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rapport-pdf', compact(
            'daily', 'periodVisited', 'periodEnrolled', 'periodNets',
            'byBrasCohort', 'byVillage', 'byCluster',
            'totalEnrolled', 'totalNets',
            'dateFrom', 'dateTo', 'tablets'
        ));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rapport-activites-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * GET /baseline/old-nets/csv
     * Liste des ménages possédant des moustiquaires avant la distribution de l'étude,
     * au format CSV prêt à importer dans KoboToolbox (média pulldata / select_from_file).
     */
    public function exportOldNetsHouseholdsCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $idRaw     = $this->redcap->getIdentificationData();
        $qbRaw     = $this->redcap->getBaselineQuestionnaireData();
        $oldNetRaw = $this->redcap->getOldNetData();

        $qbByHh = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        // Nombre de filets réellement enregistrés dans le Form 3B (une instance par filet)
        $oldNetsByHh = collect($oldNetRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'form_3b_anciennes_moustiquaires_du_mnage' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        $rows = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->map(function ($r) use ($qbByHh, $oldNetsByHh) {
                $id      = $r['household_id'];
                $qb      = $qbByHh->get($id, []);
                $consent = ($r['consent_accepted'] ?? '') ?: ($qb['consent_accepted'] ?? '');

                // Form 3B en priorité ; à défaut, le nombre déclaré dans le questionnaire baseline
                $registered = (int) $oldNetsByHh->get($id, 0);
                $declared   = ($qb['moustiquaire'] ?? '') === '1' ? (int) ($qb['nb_moustiquaires'] ?? 0) : 0;
                $nbOld      = $registered > 0 ? $registered : $declared;

                $parsed     = self::parseHouseholdId($id);
                $villageKey = ($r['village'] ?? '') ?: ($parsed['village'] ?? '');
                $cluster    = self::extractCluster($r);
                $clusterLbl = self::CLUSTER_LABELS[$cluster] ?? '';
                $grappe     = $clusterLbl !== ''
                    ? trim(explode('/', $clusterLbl)[1] ?? $clusterLbl)
                    : ($parsed['grappe'] !== '' ? 'Grappe ' . (int) $parsed['grappe'] : '');

                return [
                    'consented'                  => $consent === '1',
                    'village'                    => self::VILLAGE_LABELS[$villageKey] ?? $villageKey,
                    'grappe'                     => $grappe,
                    'household_id'               => $id,
                    'bras'                       => self::BRAS_LABELS[self::resolveBrasKey($r)] ?? '',
                    'cohorte'                    => self::COHORT_LABELS[$r['study_cohort'] ?? ''] ?? '',
                    'nb_anciennes_moustiquaires' => $nbOld,
                ];
            })
            ->filter(fn($r) => $r['consented'] && $r['nb_anciennes_moustiquaires'] > 0)
            ->sortBy(fn($r) => [$r['village'], $r['grappe'], $r['household_id']])
            ->values();

        $columns  = ['village', 'grappe', 'household_id', 'bras', 'cohorte', 'nb_anciennes_moustiquaires'];
        $filename = 'menages_anciennes_moustiquaires_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            // Pas de BOM : KoboToolbox/Enketo lirait le BOM dans le nom de la 1re colonne
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $r) {
                fputcsv($out, array_map(fn($c) => $r[$c], $columns));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function clearCache(): RedirectResponse
    {
        $this->redcap->clearCache();
        return redirect()->back()->with('success', 'Cache vidé avec succès.');
    }

    public function exportPerformancePdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $tablets  = array_values(array_filter((array)$request->input('tablets', [])));

        $visiteRaw = $this->redcap->getVisiteData();
        $idRaw     = $this->redcap->getIdentificationData();
        $qbRaw     = $this->redcap->getBaselineQuestionnaireData();

        $idMap = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        $qbConsentMap = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('consent_accepted', 'household_id');

        $consented = fn(string $id): bool =>
            (($idMap->get($id, [])['consent_accepted'] ?? '') ?: ($qbConsentMap->get($id, ''))) === '1';

        $visits = collect($visiteRaw)->filter(fn($r) => $r['household_id'] !== '');
        if ($dateFrom) $visits = $visits->filter(fn($r) => ($r['a_date_visite_id'] ?? '') >= $dateFrom);
        if ($dateTo)   $visits = $visits->filter(fn($r) => ($r['a_date_visite_id'] ?? '') <= $dateTo);
        if (!empty($tablets)) $visits = $visits->filter(fn($r) => in_array($r['a_tablette_id'] ?? '', $tablets));

        // All unique dates in the period (sorted)
        $allDates = $visits->pluck('a_date_visite_id')->filter()->unique()->sort()->values()->toArray();

        // Per-tablet performance
        $tabletData = [];
        $colorPalette = ['#C41230','#374151','#D97706','#16A34A','#7C3AED','#0891B2','#DB2777','#059669','#EA580C','#4338CA','#0D9488','#9333EA'];
        $ti = 0;
        foreach ($visits->groupBy('a_tablette_id')->sortKeys() as $tablet => $byTablet) {
            $daily = [];
            foreach ($allDates as $date) {
                $hhIds    = $byTablet->where('a_date_visite_id', $date)->pluck('household_id')->unique();
                $enrolled = $hhIds->filter(fn($id) => $consented($id));
                $daily[$date] = $enrolled->count();
            }
            $activeDays    = count(array_filter($daily, fn($v) => $v > 0));
            $totalEnrolled = array_sum($daily);
            $avgPerDay     = $activeDays > 0 ? round($totalEnrolled / $activeDays, 1) : 0;

            $tabletData[] = [
                'tablet'     => $tablet,
                'daily'      => $daily,
                'activeDays' => $activeDays,
                'total'      => $totalEnrolled,
                'avg'        => $avgPerDay,
                'color'      => $colorPalette[$ti % count($colorPalette)],
            ];
            $ti++;
        }

        $grandTotal    = array_sum(array_column($tabletData, 'total'));
        $grandAvg      = count($tabletData) > 0 && count($allDates) > 0
            ? round($grandTotal / count($allDates) / count($tabletData), 1)
            : 0;

        $chartImage = $request->input('chart_image', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('performance-pdf', compact(
            'tabletData', 'allDates', 'grandTotal', 'grandAvg',
            'dateFrom', 'dateTo', 'tablets', 'chartImage'
        ));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('performance-binomes-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportDistributionPdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $tablets  = array_values(array_filter((array)$request->input('tablets', [])));

        $visiteRaw   = $this->redcap->getVisiteData();
        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $studyNetRaw = $this->redcap->getStudyNetData();

        $qbByHh = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $consentedHhIds = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->map(function ($r) use ($qbByHh) {
                $consent = ($r['consent_accepted'] ?? '') ?: ($qbByHh->get($r['household_id'], [])['consent_accepted'] ?? '');
                return array_merge($r, ['consent_accepted' => $consent]);
            })
            ->where('consent_accepted', '1')
            ->pluck('household_id')->flip();

        $visitDataByHh = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        // Visit stats per tablet (with date + tablet filters)
        $visitsForStats = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['a_tablette_id'] ?? '') !== '')
            ->unique('household_id');
        if ($dateFrom)        $visitsForStats = $visitsForStats->filter(fn($r) => ($r['a_date_visite_id'] ?? '') >= $dateFrom);
        if ($dateTo)          $visitsForStats = $visitsForStats->filter(fn($r) => ($r['a_date_visite_id'] ?? '') <= $dateTo);
        if (!empty($tablets)) $visitsForStats = $visitsForStats->filter(fn($r) => in_array($r['a_tablette_id'] ?? '', $tablets));
        $visitStatsByTablet = $visitsForStats->groupBy('a_tablette_id')->map(fn($g) => [
            'visited'   => $g->count(),
            'consented' => $g->filter(fn($r) => $consentedHhIds->has($r['household_id']))->count(),
        ]);

        $idMapForBras = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        $nets = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
                && $r['redcap_repeat_instance'] !== ''
                && $consentedHhIds->has($r['household_id'])
            )
            ->map(function ($r) use ($visitDataByHh, $idMapForBras) {
                $visit  = $visitDataByHh->get($r['household_id'], []);
                $idData = $idMapForBras->get($r['household_id'], []);
                return [
                    'household_id' => $r['household_id'],
                    'net_code'     => $r['net_identifier_man'] ?: '—',
                    'tablette'     => $visit['a_tablette_id'] ?? '',
                    'date_visit'   => $visit['a_date_visite_id'] ?? '',
                    'bras'         => self::BRAS_LABELS[self::resolveBrasKey($idData)] ?? '—',
                    'cohort'       => self::COHORT_LABELS[$idData['study_cohort'] ?? ''] ?? '—',
                ];
            });

        if ($dateFrom)        $nets = $nets->filter(fn($r) => $r['date_visit'] >= $dateFrom);
        if ($dateTo)          $nets = $nets->filter(fn($r) => $r['date_visit'] <= $dateTo);
        if (!empty($tablets)) $nets = $nets->filter(fn($r) => in_array($r['tablette'], $tablets));

        $netsByTablet = $nets
            ->filter(fn($r) => $r['tablette'] !== '')
            ->groupBy('tablette')->sortKeys()
            ->map(fn($tNets, $tablet) => [
                'tablet'     => $tablet,
                'visited'    => $visitStatsByTablet->get($tablet, ['visited' => 0, 'consented' => 0])['visited'],
                'consented'  => $visitStatsByTablet->get($tablet, ['visited' => 0, 'consented' => 0])['consented'],
                'households' => $tNets->groupBy('household_id')->sortKeys()
                    ->map(fn($hhNets, $hhId) => [
                        'household_id' => $hhId,
                        'date_visit'   => $hhNets->first()['date_visit'],
                        'nets'         => $hhNets->pluck('net_code')->values(),
                        'total'        => $hhNets->count(),
                        'bras'         => $hhNets->first()['bras'],
                        'cohort'       => $hhNets->first()['cohort'],
                    ])->values(),
                'total' => $tNets->count(),
            ])->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('distribution-pdf', compact(
            'netsByTablet', 'dateFrom', 'dateTo', 'tablets'
        ));
        $pdf->setPaper('A4', 'portrait');

        $prefix = !empty($tablets)
            ? 'Tablette-' . implode('-', $tablets) . '_'
            : 'Toutes-tablettes_';

        return $pdf->download($prefix . 'distribution-moustiquaires_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportDistributionResumePdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $tablets  = array_values(array_filter((array)$request->input('tablets', [])));

        $visiteRaw   = $this->redcap->getVisiteData();
        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $studyNetRaw = $this->redcap->getStudyNetData();

        $qbByHh = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $consentedHhIds = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->map(function ($r) use ($qbByHh) {
                $consent = ($r['consent_accepted'] ?? '') ?: ($qbByHh->get($r['household_id'], [])['consent_accepted'] ?? '');
                return array_merge($r, ['consent_accepted' => $consent]);
            })
            ->where('consent_accepted', '1')
            ->pluck('household_id')->flip();

        $visitDataByHh = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        // Visit stats per tablet (filtered)
        $visitsForStats = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['a_tablette_id'] ?? '') !== '')
            ->unique('household_id');
        if ($dateFrom)        $visitsForStats = $visitsForStats->filter(fn($r) => ($r['a_date_visite_id'] ?? '') >= $dateFrom);
        if ($dateTo)          $visitsForStats = $visitsForStats->filter(fn($r) => ($r['a_date_visite_id'] ?? '') <= $dateTo);
        if (!empty($tablets)) $visitsForStats = $visitsForStats->filter(fn($r) => in_array($r['a_tablette_id'] ?? '', $tablets));
        $visitStatsByTablet = $visitsForStats->groupBy('a_tablette_id')->map(fn($g) => [
            'visited'   => $g->count(),
            'consented' => $g->filter(fn($r) => $consentedHhIds->has($r['household_id']))->count(),
        ]);

        $idMapForBras = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        // Net records
        $nets = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
                && $r['redcap_repeat_instance'] !== ''
                && $consentedHhIds->has($r['household_id'])
            )
            ->map(function ($r) use ($visitDataByHh, $idMapForBras) {
                $visit  = $visitDataByHh->get($r['household_id'], []);
                $idData = $idMapForBras->get($r['household_id'], []);
                return [
                    'household_id' => $r['household_id'],
                    'tablette'     => $visit['a_tablette_id'] ?? '',
                    'date_visit'   => $visit['a_date_visite_id'] ?? '',
                    'marked'       => ($r['net_identifier_man'] ?? '') !== '',
                    'bras'         => self::BRAS_LABELS[self::resolveBrasKey($idData)] ?? '—',
                    'cohort'       => self::COHORT_LABELS[$idData['study_cohort'] ?? ''] ?? '—',
                ];
            });

        if ($dateFrom)        $nets = $nets->filter(fn($r) => $r['date_visit'] >= $dateFrom);
        if ($dateTo)          $nets = $nets->filter(fn($r) => $r['date_visit'] <= $dateTo);
        if (!empty($tablets)) $nets = $nets->filter(fn($r) => in_array($r['tablette'], $tablets));

        // Group per tablet → per date
        $summary = $nets
            ->filter(fn($r) => $r['tablette'] !== '')
            ->groupBy('tablette')->sortKeys()
            ->map(fn($tNets, $tablet) => [
                'tablet'           => $tablet,
                'visited'          => $visitStatsByTablet->get($tablet, ['visited' => 0, 'consented' => 0])['visited'],
                'consented'        => $visitStatsByTablet->get($tablet, ['visited' => 0, 'consented' => 0])['consented'],
                'byDate'           => $tNets->groupBy('date_visit')->sortKeys()
                    ->map(fn($dNets, $date) => [
                        'date'        => $date,
                        'hh_count'    => $dNets->pluck('household_id')->unique()->count(),
                        'distributed' => $dNets->count(),
                        'marked'      => $dNets->where('marked', true)->count(),
                    ])->values(),
                'total_distributed' => $tNets->count(),
                'total_marked'      => $tNets->where('marked', true)->count(),
                'total_hh'          => $tNets->pluck('household_id')->unique()->count(),
            ])->values();

        $grandDistributed = $summary->sum('total_distributed');
        $grandMarked      = $summary->sum('total_marked');
        $grandHh          = $nets->pluck('household_id')->unique()->count();

        $byBrasCohortNets = $nets
            ->filter(fn($r) => $r['bras'] !== '—')
            ->groupBy('bras')->sortKeys()
            ->map(fn($brasGroup, $brasLabel) => [
                'bras'    => $brasLabel,
                'nets'    => $brasGroup->count(),
                'marked'  => $brasGroup->where('marked', true)->count(),
                'cohorts' => $brasGroup->groupBy('cohort')->sortKeys()
                    ->map(fn($g, $cohort) => [
                        'cohort' => $cohort,
                        'nets'   => $g->count(),
                        'marked' => $g->where('marked', true)->count(),
                    ])->values(),
            ])->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('distribution-resume-pdf', compact(
            'summary', 'grandDistributed', 'grandMarked', 'grandHh',
            'byBrasCohortNets', 'dateFrom', 'dateTo', 'tablets'
        ));
        $pdf->setPaper('A4', 'portrait');

        $prefix = !empty($tablets)
            ? 'Tablette-' . implode('-', $tablets) . '_'
            : 'Toutes-tablettes_';

        return $pdf->download($prefix . 'resume-distribution_' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Flow diagram page (SVG / PNG / JPG) ──────────────────────────────────
    public function downloadFlowDiagram(): \Illuminate\View\View
    {
        $this->redcap->warmAll();

        $idData = collect($this->redcap->getIdentificationData())
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['consent_accepted'] ?? '') === '1')
            ->unique('household_id')
            ->values();

        $idMap  = $idData->keyBy('household_id');
        $form4a = collect($this->redcap->getForm4aData())
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '');

        $pdNets = $form4a
            ->filter(fn($r) => ($idMap->get($r['household_id'], [])['bras'] ?? '') === '1')
            ->sum(fn($r) => (int) ($r['nb_moustiquaires_etude'] ?? 0));
        $g2Nets = $form4a
            ->filter(fn($r) => ($idMap->get($r['household_id'], [])['bras'] ?? '') === '2')
            ->sum(fn($r) => (int) ($r['nb_moustiquaires_etude'] ?? 0));

        $aePD = \App\Models\AeSelectedHousehold::where('arm', 'PD')->count();
        $aeG2 = \App\Models\AeSelectedHousehold::where('arm', 'G2')->count();

        $fmt = fn(int $n): string => number_format($n, 0, ',', ' ');

        $svg = $this->buildFlowDiagramSvg([
            'totalHH'  => $fmt($idData->count()),
            'pdHH'     => $fmt($idData->where('bras', '1')->count()),
            'g2HH'     => $fmt($idData->where('bras', '2')->count()),
            'totalNets'=> $fmt($pdNets + $g2Nets),
            'pdNets'   => $fmt($pdNets),
            'g2Nets'   => $fmt($g2Nets),
            'cohA'     => $fmt($idData->where('study_cohort', '1')->count()),
            'cohA_PD'  => $fmt($idData->where('study_cohort', '1')->where('bras', '1')->count()),
            'cohA_G2'  => $fmt($idData->where('study_cohort', '1')->where('bras', '2')->count()),
            'cohB'     => $fmt($idData->where('study_cohort', '2')->count()),
            'cohB_PD'  => $fmt($idData->where('study_cohort', '2')->where('bras', '1')->count()),
            'cohB_G2'  => $fmt($idData->where('study_cohort', '2')->where('bras', '2')->count()),
            'aePD'     => $fmt($aePD),
            'aeG2'     => $fmt($aeG2),
            'date'     => now()->format('d/m/Y'),
        ]);

        return view('flow-diagram', compact('svg'));
    }

    private function buildFlowDiagramSvg(array $d): string
    {
        extract($d);
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg id="flow-svg" xmlns="http://www.w3.org/2000/svg" width="1200" height="680" viewBox="0 0 1200 680">
  <rect width="1200" height="680" fill="white"/>
  <!-- L1: ITN Distribution -->
  <rect x="20" y="15" width="1160" height="85" rx="6" fill="#DAEEF3" stroke="#17375E" stroke-width="2.5"/>
  <text x="600" y="42" text-anchor="middle" font-family="Arial,sans-serif" font-size="19" font-weight="bold" fill="#17375E">ITN Distribution</text>
  <text x="600" y="62" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" fill="#17375E">PermaNet&#174; Dual (PD) and Interceptor&#174; G2</text>
  <text x="600" y="82" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" fill="#17375E">PD: {$pdHH} HH ({$pdNets} ITNs)&#160;&#160;&#183;&#160;&#160;G2: {$g2HH} HH ({$g2Nets} ITNs)&#160;&#160;&#183;&#160;&#160;Total: {$totalHH} HH &#8212; {$totalNets} ITNs distributed</text>
  <!-- connector L1→L2 -->
  <line x1="600" y1="100" x2="600" y2="135" stroke="#8EAABD" stroke-width="1.8" stroke-dasharray="5,3"/>
  <!-- L2: Adverse Events selection -->
  <rect x="350" y="135" width="500" height="72" rx="6" fill="#EBF1DE" stroke="#4F7A28" stroke-width="2"/>
  <text x="600" y="161" text-anchor="middle" font-family="Arial,sans-serif" font-size="16" font-weight="bold" fill="#375623">Adverse Events Selection</text>
  <text x="600" y="182" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" fill="#375623">PD: {$aePD} HH&#160;&#160;&#183;&#160;&#160;G2: {$aeG2} HH</text>
  <text x="600" y="199" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-style="italic" fill="#375623">Follow-up at 1 week &#8211; 1 month</text>
  <!-- connectors L2→L3 fork (vertical exits between Fabric integrity and Lab bioassays) -->
  <line x1="497" y1="207" x2="497" y2="232" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="250" y1="232" x2="843" y2="232" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="250" y1="232" x2="250" y2="255" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="843" y1="232" x2="843" y2="255" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <!-- L3: Cohorts -->
  <rect x="75" y="255" width="350" height="58" rx="5" fill="#FFFF99" stroke="#BF8F00" stroke-width="2"/>
  <text x="250" y="279" text-anchor="middle" font-family="Arial,sans-serif" font-size="16" font-weight="bold" fill="#7F5200">Cohort A &#8212; {$cohA} HH</text>
  <text x="250" y="300" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" fill="#8A5900">PD: {$cohA_PD}&#160;&#160;&#183;&#160;&#160;G2: {$cohA_G2}</text>
  <rect x="492" y="255" width="702" height="58" rx="5" fill="#FFFF99" stroke="#BF8F00" stroke-width="2"/>
  <text x="843" y="279" text-anchor="middle" font-family="Arial,sans-serif" font-size="16" font-weight="bold" fill="#7F5200">Cohort B &#8212; {$cohB} HH</text>
  <text x="843" y="300" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" fill="#8A5900">PD: {$cohB_PD}&#160;&#160;&#183;&#160;&#160;G2: {$cohB_G2}</text>
  <!-- connectors L3→L4 Cohort A -->
  <line x1="250" y1="313" x2="250" y2="340" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="110" y1="340" x2="395" y2="340" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="110" y1="340" x2="110" y2="358" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="395" y1="340" x2="395" y2="358" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <!-- connectors L3→L4 Cohort B (3 branches: Bioessais, Semi-terrain, Chimie/Physique) -->
  <line x1="843" y1="313" x2="843" y2="340" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="600" y1="340" x2="1090" y2="340" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="600" y1="340" x2="600" y2="358" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="805" y1="340" x2="805" y2="358" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="1090" y1="340" x2="1090" y2="358" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <!-- L4: Activity boxes -->
  <rect x="15" y="358" width="191" height="95" rx="5" fill="#EBF1DE" stroke="#4F7A28" stroke-width="1.8"/>
  <text x="110" y="381" text-anchor="middle" font-family="Arial,sans-serif" font-size="15" font-weight="bold" fill="#375623">Attrition</text>
  <text x="110" y="399" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#375623">{$cohA_PD} HH (PD)</text>
  <text x="110" y="416" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#375623">{$cohA_G2} HH (G2)</text>
  <text x="110" y="437" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" font-style="italic" fill="#375623">6, 12, 24, 36 months</text>
  <rect x="298" y="358" width="194" height="95" rx="5" fill="#EBF1DE" stroke="#4F7A28" stroke-width="1.8"/>
  <text x="395" y="381" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" font-weight="bold" fill="#375623">Fabric integrity</text>
  <text x="395" y="399" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#375623">N &#8805; 750 ITNs / arm</text>
  <text x="395" y="418" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" font-style="italic" fill="#375623">6, 12, 24, 36 months</text>
  <rect x="502" y="358" width="196" height="95" rx="5" fill="#EBF1DE" stroke="#4F7A28" stroke-width="1.8"/>
  <text x="600" y="381" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" font-weight="bold" fill="#375623">Laboratory bioassays</text>
  <text x="600" y="399" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#375623">30 ITNs / arm</text>
  <text x="600" y="418" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" font-style="italic" fill="#375623">0, 12, 24, 36 months</text>
  <rect x="706" y="358" width="198" height="95" rx="5" fill="#EBF1DE" stroke="#4F7A28" stroke-width="1.8"/>
  <text x="805" y="381" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" font-weight="bold" fill="#375623">Semi-field RR hut trial</text>
  <text x="805" y="399" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#375623">33 ITNs / arm withdrawn</text>
  <text x="805" y="418" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" font-style="italic" fill="#375623">0, 12, 24, 36 months</text>
  <rect x="993" y="358" width="192" height="95" rx="5" fill="#EBF1DE" stroke="#4F7A28" stroke-width="1.8"/>
  <text x="1090" y="378" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="bold" fill="#375623">Chemical analysis</text>
  <text x="1090" y="394" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" font-style="italic" fill="#375623">0, 6, 12, 18, 24, 36 months</text>
  <text x="1090" y="414" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="bold" fill="#375623">Physical charact.</text>
  <text x="1090" y="430" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" font-style="italic" fill="#375623">0, 12, 24, 36 months</text>
  <!-- connectors L4→L5 -->
  <line x1="110"  y1="453" x2="110"  y2="472" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="395"  y1="453" x2="395"  y2="472" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="600"  y1="453" x2="600"  y2="472" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="805"  y1="453" x2="805"  y2="472" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <line x1="1090" y1="453" x2="1090" y2="472" stroke="#8EAABD" stroke-width="1.5" stroke-dasharray="5,3"/>
  <!-- L5: Outcome boxes -->
  <rect x="15" y="472" width="191" height="165" rx="5" fill="#FFCCDD" stroke="#C0444F" stroke-width="1.5"/>
  <text x="110" y="494" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="bold" fill="#5E1933">Physical presence / absence</text>
  <text x="110" y="513" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">&#8226; Reasons for absence</text>
  <text x="110" y="530" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">&#8226; Functional survival</text>
  <text x="110" y="547" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">&#8226; Median survival</text>
  <rect x="298" y="472" width="194" height="165" rx="5" fill="#FFCCDD" stroke="#C0444F" stroke-width="1.5"/>
  <text x="395" y="494" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="bold" fill="#5E1933">Proportional Hole Index (pHI)</text>
  <text x="395" y="513" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">&#8226; Good (pHI 0&#8211;64)</text>
  <text x="395" y="530" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">&#8226; Acceptable (65&#8211;642)</text>
  <text x="395" y="547" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">&#8226; Torn (pHI &#8805; 643)</text>
  <rect x="502" y="472" width="196" height="165" rx="5" fill="#FFCCDD" stroke="#C0444F" stroke-width="1.5"/>
  <text x="600" y="494" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="bold" fill="#5E1933">Cone bioassays (S) + Tunnel (R)</text>
  <text x="600" y="513" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">KD, mortality, blood-feeding</text>
  <text x="600" y="530" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">inhibition &#8212; 0, 12, 24,</text>
  <text x="600" y="547" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">36 months</text>
  <rect x="706" y="472" width="198" height="165" rx="5" fill="#FFCCDD" stroke="#C0444F" stroke-width="1.5"/>
  <text x="805" y="494" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="bold" fill="#5E1933">Mortality + Blood-feeding</text>
  <text x="805" y="513" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">inhibition &#8212; An. gambiae</text>
  <text x="805" y="530" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" font-style="italic" fill="#5E1933">pyrethroid resistant, Cove</text>
  <rect x="993" y="472" width="192" height="165" rx="5" fill="#FFCCDD" stroke="#C0444F" stroke-width="1.5"/>
  <text x="1090" y="494" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">AI content, surface AI,</text>
  <text x="1090" y="511" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">WRI, bursting strength,</text>
  <text x="1090" y="528" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#5E1933">resistance to hole formation</text>
  <!-- Footer -->
  <text x="600" y="666" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" fill="#9AAAB8">REDCap &#183; ENDURE-Net Project #46 &#183; AIRID-Africa &#183; Generated on {$date}</text>
</svg>
SVG;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function buildPerformanceChartData(array $visiteRaw, array $idRaw, array $qbRaw): array
    {
        $idMap = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        $qbConsentMap = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('consent_accepted', 'household_id');

        $consented = fn(string $id): bool =>
            (($idMap->get($id, [])['consent_accepted'] ?? '') ?: ($qbConsentMap->get($id, ''))) === '1';

        $visits = collect($visiteRaw)->filter(fn($r) => $r['household_id'] !== '');

        $allDates = $visits->pluck('a_date_visite_id')->filter()->unique()->sort()->values()->toArray();

        $datasets = [];
        $ti = 0;
        foreach ($visits->groupBy('a_tablette_id')->sortKeys() as $tablet => $byTablet) {
            if ($tablet === '') continue;
            $daily = [];
            foreach ($allDates as $date) {
                $hhIds = $byTablet->where('a_date_visite_id', $date)->pluck('household_id')->unique();
                $daily[] = $hhIds->filter(fn($id) => $consented($id))->count();
            }
            $color = self::TABLET_COLORS[$ti % count(self::TABLET_COLORS)];
            $datasets[] = [
                'label'           => 'Tablette ' . $tablet,
                'data'            => $daily,
                'borderColor'     => $color,
                'backgroundColor' => $color . '22',
                'pointRadius'     => count($allDates) > 60 ? 1 : 3,
                'pointHoverRadius'=> 5,
                'tension'         => 0.3,
                'borderWidth'     => 2,
                'fill'            => false,
            ];
            $ti++;
        }

        $labels = array_map(
            fn($d) => \Carbon\Carbon::parse($d)->format('d/m'),
            $allDates
        );

        return compact('labels', 'datasets');
    }

    private function buildVisits(array $visiteRaw, array $idRaw, array $qbRaw): Collection
    {
        $idMap = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        $qbMap = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        return collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && $r['visit_status'] !== '')
            ->unique('household_id')
            ->map(function ($v) use ($idMap, $qbMap) {
                $id     = $v['household_id'];
                $idData = $idMap->get($id, []);
                $qb     = $qbMap->get($id, []);
                $consent = ($idData['consent_accepted'] ?? '') ?: ($qb['consent_accepted'] ?? '');
                return array_merge($v, $idData, [
                    'a_household_eligible' => $qb['a_household_eligible'] ?? '',
                    'consent_accepted'     => $consent,
                    'bras'                 => self::resolveBrasKey($idData),
                ]);
            })
            ->values();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // BUILDERS
    // ══════════════════════════════════════════════════════════════════════════

    private function buildBaselineData(Collection $visits, array $qbRaw, array $memberRaw, array $oldNetRaw, array $studyNetRaw, array $idRaw): array
    {
        // Build an all-households collection from the identification form (has bras, village, cluster_id)
        // joined with qbRaw for consent — bypasses any visit_status join failures.
        // Keep only non-repeating rows; without a forms= filter, REDCap returns all
        // baseline rows including repeating instrument rows where qb fields are blank.
        $qbByHh = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $allHouseholds = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->map(function ($r) use ($qbByHh) {
                $qb = $qbByHh->get($r['household_id'], []);
                // Consent may live in the identification form or in questionnaire_base;
                // use whichever source gives a non-empty value.
                $consent = ($r['consent_accepted'] ?? '') ?: ($qb['consent_accepted'] ?? '');
                $parsed  = self::parseHouseholdId($r['household_id']);
                return array_merge($r, [
                    'consent_accepted' => $consent,
                    'bras'             => self::resolveBrasKey($r),
                    'village'          => ($r['village'] ?? '') ?: ($parsed['village'] ?? ''),
                ]);
            });

        $totalVisited   = $allHouseholds->count();
        $totalConsented = $allHouseholds->where('consent_accepted', '1')->count();

        $byVillage = $this->groupStats($allHouseholds, 'village', self::VILLAGE_LABELS);
        $byBras    = $this->groupStats($allHouseholds, 'bras',    self::BRAS_LABELS);

        $byCluster = $allHouseholds->map(function ($r) {
            return array_merge($r, ['_cluster' => self::extractCluster($r)]);
        })->groupBy('_cluster')->map(fn($g, $k) => [
            'label'     => self::CLUSTER_LABELS[$k] ?? "Grappe $k",
            'total'     => $g->count(),
            'consented' => $g->where('consent_accepted', '1')->count(),
        ])->sortKeys()->values();

        $cohortByBras = $allHouseholds->groupBy('bras')->map(fn($g, $bras) => [
            'label'   => self::BRAS_LABELS[$bras] ?? "Bras $bras",
            // Counts restricted to households with a known cohort so the header total
            // equals the sum of cohort rows and "consentis" numbers are consistent.
            'total'     => $g->filter(fn($hh) => isset(self::COHORT_LABELS[$hh['study_cohort'] ?? '']))->count(),
            'consented' => $g->filter(fn($hh) => isset(self::COHORT_LABELS[$hh['study_cohort'] ?? '']))->where('consent_accepted', '1')->count(),
            'cohorts'   => $g->groupBy('study_cohort')
                ->filter(fn($sg, $c) => isset(self::COHORT_LABELS[$c]))
                ->map(fn($sg, $c) => [
                    'label'     => self::COHORT_LABELS[$c],
                    'total'     => $sg->count(),
                    'consented' => $sg->where('consent_accepted', '1')->count(),
                ])->values(),
        ])->values();

        $qb = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $allHhById = $allHouseholds->keyBy('household_id');
        $gpsData = collect($qbRaw)
            ->filter(fn($r) => $r['b_geo_latitude'] !== '' && $r['b_geo_longitude'] !== '')
            ->map(function ($r) use ($allHhById) {
                $hh = $allHhById->get($r['household_id'], []);
                return [
                    'id'     => $r['household_id'],
                    'lat'    => (float)$r['b_geo_latitude'],
                    'lng'    => (float)$r['b_geo_longitude'],
                    'bras'   => self::BRAS_LABELS[$hh['bras'] ?? ''] ?? '',
                    'cohort' => self::COHORT_LABELS[$hh['study_cohort'] ?? ''] ?? '',
                ];
            })
            ->values();

        $allDates = collect($qbRaw)
            ->filter(fn($r) => $r['b_date_visit'] !== '')
            ->pluck('b_date_visit')->unique()->sort()->values();

        $tabletDatasets = collect($qbRaw)
            ->filter(fn($r) => $r['b_tablette'] !== '')
            ->groupBy('b_tablette')->sortKeys()->values()
            ->map(fn($g, $i) => [
                'label' => 'Tablette ' . $g->first()['b_tablette'],
                'data'  => $allDates->map(fn($d) => $g->where('b_date_visit', $d)->count())->values(),
                'color' => self::TABLET_COLORS[$i % count(self::TABLET_COLORS)],
            ]);

        $tabletActivity = collect($qbRaw)
            ->filter(fn($r) => $r['b_tablette'] !== '')
            ->groupBy('b_tablette')
            ->map(fn($g, $t) => [
                'tablette' => "Tablette $t",
                'total'    => $g->count(),
                'par_date' => $g->groupBy('b_date_visit')->map->count()->sortKeys(),
            ])
            ->sortKeys()->values();

        $membersByHh = collect($memberRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'section_1_membres_du_mnage' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        $oldNetsByHh = collect($oldNetRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'form_3b_anciennes_moustiquaires_du_mnage' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        // Use allHouseholds (dual-source consent, no visit_status requirement) so nets
        // belonging to consented households without a recorded visit_status are included.
        $consentedHhIds = $allHouseholds->where('consent_accepted', '1')->pluck('household_id')->flip();

        $studyNetsFiltered = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
                && $r['redcap_repeat_instance'] !== ''
                && $consentedHhIds->has($r['household_id'])
            );

        $studyNetsByHh = $studyNetsFiltered->groupBy('household_id')->map(fn($g) => [
            'count' => $g->count(),
            'codes' => $g->pluck('net_identifier_man')->filter()->values()->implode(', '),
        ]);

        // Keyed lookups from the identification form (has bras, village, cluster_id, study_cohort)
        $allHouseholdsById = $allHouseholds->keyBy('household_id');
        // Visit-specific fields (date, tablette) keyed by household_id
        $visitDataByHh = $visits->keyBy('household_id');

        $studyNetsDetail = $studyNetsFiltered->map(function ($r) use ($allHouseholdsById, $visitDataByHh) {
            $hh    = $allHouseholdsById->get($r['household_id'], []);
            $visit = $visitDataByHh->get($r['household_id'], []);
            return [
                'household_id' => $r['household_id'],
                'instance'     => $r['redcap_repeat_instance'],
                'net_code'     => $r['net_identifier_man'] ?: '—',
                'bras'         => self::BRAS_LABELS[$hh['bras'] ?? ''] ?? '—',
                'cohort'       => self::COHORT_LABELS[$hh['study_cohort'] ?? ''] ?? '—',
                'village'      => self::VILLAGE_LABELS[$hh['village'] ?? ''] ?? '—',
                'cluster'      => self::CLUSTER_LABELS[self::extractCluster($hh)] ?? '—',
                'dag'          => $hh['redcap_data_access_group'] ?? ($r['redcap_data_access_group'] ?? ''),
                'tablette'     => $visit['a_tablette_id'] ?? '',
                'date_visit'   => $visit['a_date_visite_id'] ?? '',
            ];
        })->values();

        $byDag = $studyNetsDetail
            ->groupBy('dag')
            ->map(fn($g, $d) => ['dag' => $d ?: 'Non défini', 'count' => $g->count()])
            ->sortKeys()->values();

        $visitsByTablet = $visits
            ->filter(fn($r) => ($r['a_tablette_id'] ?? '') !== '')
            ->groupBy('a_tablette_id');

        $netsByTablet = $studyNetsDetail
            ->filter(fn($r) => $r['tablette'] !== '')
            ->groupBy('tablette')->sortKeys()
            ->map(function ($tNets, $tablet) use ($visitsByTablet, $membersByHh) {
                $households = $tNets->groupBy('household_id')->sortKeys()
                    ->map(function ($hhNets, $hhId) use ($membersByHh) {
                        $total  = $hhNets->count();
                        $m      = $membersByHh->get($hhId, 0);
                        $netMin = $m > 0 ? (int) ceil($m / 3) : null;
                        $netMax = $m > 0 ? (int) ceil($m / 2) : null;
                        $status = $netMin !== null
                            ? ($total < $netMin ? 'low' : ($total > $netMax ? 'high' : 'ok'))
                            : null;
                        return [
                            'household_id' => $hhId,
                            'date_visit'   => $hhNets->first()['date_visit'],
                            'nets'         => $hhNets->pluck('net_code')->values(),
                            'total'        => $total,
                            'bras'         => $hhNets->first()['bras'],
                            'cohort'       => $hhNets->first()['cohort'],
                            'members'      => $m,
                            'net_min'      => $netMin,
                            'net_max'      => $netMax,
                            'net_status'   => $status,
                        ];
                    })->values();
                return [
                    'tablet'    => $tablet,
                    'visited'   => $visitsByTablet->get($tablet, collect())->count(),
                    'consented' => $visitsByTablet->get($tablet, collect())->where('consent_accepted', '1')->count(),
                    'households'=> $households,
                    'total'     => $tNets->count(),
                    'net_low'   => $households->filter(fn($h) => $h['net_status'] === 'low')->count(),
                    'net_high'  => $households->filter(fn($h) => $h['net_status'] === 'high')->count(),
                    'pd_nets'   => $tNets->where('bras', 'PERMANET DUAL')->count(),
                    'ig2_nets'  => $tNets->where('bras', 'INTERCEPTOR G2')->count(),
                    'ig2_marked'=> $tNets->filter(fn($r) => $r['bras'] === 'INTERCEPTOR G2' && $r['net_code'] !== '—')->count(),
                ];
            })->values();

        $householdDetails = $allHouseholds->map(function ($hh) use ($qb, $membersByHh, $oldNetsByHh, $studyNetsByHh, $visitDataByHh) {
            $id         = $hh['household_id'];
            $parsed     = self::parseHouseholdId($id);
            $qbHh       = $qb->get($id, []);
            $nets       = $studyNetsByHh->get($id, ['count' => 0, 'codes' => '']);
            $cluster    = self::extractCluster($hh);
            $visitData  = $visitDataByHh->get($id, []);
            $villageKey = ($hh['village'] ?? '') ?: ($parsed['village'] ?? '');

            return [
                'id'           => $id,
                'id_valid'     => $parsed['valid'],
                'village'      => self::VILLAGE_LABELS[$villageKey] ?? '—',
                'bras'         => self::BRAS_LABELS[$hh['bras'] ?? ''] ?? '—',
                'cohort'       => self::COHORT_LABELS[$hh['study_cohort'] ?? ''] ?? '—',
                'cluster'      => self::CLUSTER_LABELS[$cluster] ?? ($cluster ?: '—'),
                'dag'          => $hh['redcap_data_access_group'] ?? '',
                'consented'    => ($hh['consent_accepted'] ?? '') === '1',
                'members'      => $membersByHh->get($id, 0),
                'sleep_spaces' => ($qbHh['sleep_total_spaces'] ?? '') ?: '—',
                'old_nets'     => $oldNetsByHh->get($id, 0),
                'study_nets'   => $nets['count'],
                'net_codes'    => $nets['codes'],
                'date_visit'   => $visitData['a_date_visite_id'] ?? '',
                'tablette'     => $visitData['a_tablette_id'] ?? '',
            ];
        });

        $byBrasCohortNets = $studyNetsDetail
            ->filter(fn($r) => $r['bras'] !== '—')
            ->groupBy('bras')->sortKeys()
            ->map(fn($brasGroup, $brasLabel) => [
                'bras'    => $brasLabel,
                'nets'    => $brasGroup->count(),
                'marked'  => $brasGroup->filter(fn($r) => $r['net_code'] !== '—')->count(),
                'cohorts' => $brasGroup->groupBy('cohort')->sortKeys()
                    ->map(fn($g, $cohort) => [
                        'cohort' => $cohort,
                        'nets'   => $g->count(),
                        'marked' => $g->filter(fn($r) => $r['net_code'] !== '—')->count(),
                    ])->values(),
            ])->values();

        // Cohorts ordered for pivot columns
        $allCohorts = $studyNetsDetail
            ->pluck('cohort')
            ->filter(fn($c) => $c !== '—' && $c !== '')
            ->unique()->sort()->values();

        // Pivot: bras → grappes (village/grappe rows) × cohort columns
        $byBrasCohortDetail = $studyNetsDetail
            ->filter(fn($r) => $r['bras'] !== '—')
            ->groupBy('bras')->sortKeys()
            ->map(fn($brasGroup, $brasLabel) => [
                'bras'      => $brasLabel,
                'total'     => $brasGroup->count(),
                'marked'    => $brasGroup->filter(fn($r) => $r['net_code'] !== '—')->count(),
                'by_cohort' => $brasGroup->groupBy('cohort')->map->count()->toArray(),
                'grappes'   => $brasGroup->groupBy('cluster')->sortKeys()
                    ->map(fn($grappeGroup, $grappeName) => [
                        'label'     => $grappeName,
                        'total'     => $grappeGroup->count(),
                        'marked'    => $grappeGroup->filter(fn($r) => $r['net_code'] !== '—')->count(),
                        'by_cohort' => $grappeGroup->groupBy('cohort')->map->count()->toArray(),
                    ])->values(),
            ])->values();

        return compact(
            'totalVisited', 'totalConsented',
            'byVillage', 'byBras', 'byCluster', 'cohortByBras',
            'gpsData', 'allDates', 'tabletDatasets', 'tabletActivity',
            'householdDetails', 'membersByHh',
            'studyNetsDetail', 'studyNetsByHh', 'byDag', 'netsByTablet',
            'byBrasCohortNets', 'byBrasCohortDetail', 'allCohorts'
        ) + ['netsByBras' => $studyNetsDetail->groupBy('bras')->map->count()];
    }

    private function buildUAData(array $raw, Collection $visits, ?Collection $idMap = null): array
    {
        $records = collect($raw)->filter(fn($r) => $r['household_id'] !== '');
        $total   = $records->unique('household_id')->count();
        $uniq    = $records->unique('household_id');

        $like        = $records->where('net_like', '1')->count();
        $satisfied   = $records->whereIn('net_overall_satisfaction', ['1', '2'])->count();
        $continueUse = $records->where('net_continue_use', '1')->count();
        $recommend   = $records->where('net_recommend', '1')->count();
        $easyUse     = $records->where('net_easy_use', '1')->count();

        $satisfactionDist = $records->groupBy('net_overall_satisfaction')
            ->map->count()
            ->mapWithKeys(fn($c, $k) => [match((string)$k) {
                '1' => 'Très satisfait',
                '2' => 'Satisfait',
                '3' => 'Insatisfait',
                '4' => 'Très insatisfait',
                '', '0' => 'Non renseigné',
                default  => "Valeur $k"
            } => $c]);

        // ── Rows (fix cohort/village/cluster from household ID when visits data missing) ──
        $rows = $uniq->map(function ($r) use ($visits, $idMap) {
            $hhId       = $r['household_id'];
            $hh         = $this->visitOf($visits, $hhId) ?? [];
            $idData     = $idMap?->get($hhId, []) ?? [];
            $parsed     = self::parseHouseholdId($hhId);
            $brasKey    = ($hh['bras'] ?? '') ?: self::resolveBrasKey(array_merge($r, (array)$hh, ['household_id' => $hhId]));
            $villageKey = ($hh['village'] ?? '') ?: ($idData['village'] ?? '') ?: ($parsed['village'] ?? '');
            $clusterKey = self::extractCluster($hh ?: ($idData ?: ['household_id' => $hhId]));
            $cohortKey  = $hh['study_cohort'] ?? ($idData['study_cohort'] ?? '');
            return [
                'id'           => $hhId,
                'bras'         => self::BRAS_LABELS[$brasKey] ?? '—',
                'cohort'       => self::COHORT_LABELS[$cohortKey] ?? '—',
                'village'      => self::VILLAGE_LABELS[$villageKey] ?? '—',
                'cluster'      => self::CLUSTER_LABELS[$clusterKey] ?? '—',
                'net_like'     => $r['net_like'] === '1' ? 'Oui' : ($r['net_like'] === '0' ? 'Non' : '—'),
                'satisfaction' => match($r['net_overall_satisfaction']) {
                    '1' => 'Très satisfait', '2' => 'Satisfait',
                    '3' => 'Insatisfait',    '4' => 'Très insatisfait', default => '—'
                },
                'continue_use' => $r['net_continue_use'] === '1' ? 'Oui' : ($r['net_continue_use'] === '0' ? 'Non' : '—'),
                'recommend'    => $r['net_recommend'] === '1' ? 'Oui' : ($r['net_recommend'] === '0' ? 'Non' : '—'),
                'easy_use'     => $r['net_easy_use'] === '1' ? 'Oui' : ($r['net_easy_use'] === '0' ? 'Non' : '—'),
            ];
        })->values();

        // ── Respondent role distribution ──
        $respRoleDefs = [
            '1' => 'Chef de ménage',
            '2' => 'Conjoint(e)',
            '3' => 'Autre membre du ménage âgé de 18 ans ou plus',
            '4' => 'Aucun des précédents',
        ];
        $respRoleDist = $uniq
            ->filter(fn($r) => ($r['uac_resp_role'] ?? '') !== '')
            ->groupBy('uac_resp_role')
            ->map->count()
            ->mapWithKeys(fn($c, $k) => [($respRoleDefs[(string)$k] ?? "Rôle $k") => $c])
            ->sortDesc()
            ->all();

        // ── Importance / adequacy stats ──
        $importanceLevels = ['1' => 'Très important', '2' => 'Plutôt important', '3' => 'Peu important', '4' => 'Pas important'];
        $binaryPct = fn(Collection $col, string $f) => [
            'oui' => $col->where($f, '1')->count(),
            'non' => $col->where($f, '0')->count(),
            'total' => $col->filter(fn($r) => in_array($r[$f] ?? '', ['0','1']))->count(),
        ];
        $importanceDist = fn(string $f) => $uniq
            ->filter(fn($r) => ($r[$f] ?? '') !== '')
            ->groupBy($f)
            ->map->count()
            ->mapWithKeys(fn($c, $k) => [($importanceLevels[(string)$k] ?? "Niveau $k") => $c])
            ->all();

        $importanceStats = [
            ['label' => 'Design / motif',    'field' => 'net_design_importance',  'type' => 'likert', 'dist' => $importanceDist('net_design_importance')],
            ['label' => 'Couleur',            'field' => 'net_color_importance',   'type' => 'likert', 'dist' => $importanceDist('net_color_importance')],
            ['label' => 'Taille',             'field' => 'net_taille_importance',  'type' => 'likert', 'dist' => $importanceDist('net_taille_importance')],
            ['label' => 'Taille adéquate',    'field' => 'net_size_adequate',      'type' => 'binary', 'dist' => $binaryPct($uniq, 'net_size_adequate')],
            ['label' => 'Facile à installer', 'field' => 'net_easy_install',       'type' => 'binary', 'dist' => $binaryPct($uniq, 'net_easy_install')],
            ['label' => 'Facile à utiliser',  'field' => 'net_easy_use',           'type' => 'binary', 'dist' => $binaryPct($uniq, 'net_easy_use')],
        ];

        // ── Open-ended themes ──
        $openEndedQuestions = [
            'use_issues'   => ['label' => 'Problèmes rencontrés', 'themes' => [
                'Inconfort thermique'    => ['chaud', 'chaleur', 'étouff', 'transpir'],
                'Taille inadaptée'       => ['taille', 'court', 'étroit', 'petit', 'large', 'dimens'],
                'Installation difficile' => ['instal', 'accroch', 'suspendr', 'monter', 'fixer', 'tient', 'attach', 'tomber'],
                'Odeur gênante'          => ['odeur', 'sent', 'mauvais'],
                'Réaction cutanée'       => ['démang', 'brûl', 'allergi', 'irritat', 'peau', 'picot'],
                'Déchirures / fragilité' => ['déchir', 'trouer', 'abîm', 'fragile', 'percé', 'trou'],
                'Efficacité réduite'     => ['moustique', 'pénètre', 'pass', 'protège pas'],
                'Aucun problème'         => ['aucun', 'rien', 'sans problème', 'pas eu', 'pas de problème'],
            ]],
            'best_features' => ['label' => 'Meilleures caractéristiques', 'themes' => [
                'Protection efficace'     => ['protèg', 'efficace', 'moustique', 'insecte', 'punaise', 'tue', 'chasse'],
                'Qualité / Solidité'      => ['solid', 'résist', 'durable', 'qualité', 'robuste'],
                'Confort'                 => ['doux', 'confort', 'frais', 'agréable', 'léger', 'souple'],
                'Design / Esthétique'     => ['couleur', 'belle', 'jolie', 'joli', 'design', 'beau'],
                'Taille suffisante'       => ['taille', 'grand', 'spacieux', 'couvre', 'large'],
                "Facilité d'utilisation"  => ['facile', 'simple', 'pratique'],
            ]],
            'improvement'   => ['label' => "Suggestions d'amélioration", 'themes' => [
                'Taille / dimensions'      => ['taille', 'plus grand', 'plus large', 'agrandir', 'long'],
                'Couleur / design'         => ['couleur', 'autre couleur', 'blanc', 'bleu', 'design'],
                'Résistance / Qualité'     => ['solid', 'résistant', 'matériau', 'qualité', 'déchir'],
                'Confort'                  => ['confort', 'frais', 'doux', 'légèr', 'respir', 'aérer'],
                "Installation"             => ['installation', 'accrocher', 'suspendre', 'plus facile'],
                "Efficacité"               => ['efficace', 'moustique', 'insecticid', 'traitement'],
                'Rien / Satisfait'         => ['aucune', 'rien', 'satisfait', 'content', 'bien', 'pas de suggestion'],
            ]],
            'comparison'    => ['label' => 'Comparaison avec autres filets', 'themes' => [
                'Supérieur aux autres'   => ['meilleur', 'supérieur', 'mieux', 'plus efficace', 'meilleur que'],
                'Inférieur aux autres'   => ['moins bien', 'inférieur', 'moins efficace', 'moins bon', 'préfère autre', 'ancien meilleur'],
                'Similaire / Équivalent' => ['pareil', 'même chose', 'similaire', 'équivalent', 'identique', 'comparable'],
                'Pas de comparaison'     => ['jamais', 'aucun autre', 'pas comparé', 'première fois', 'pas utilisé autre'],
            ]],
            'brand_pref'    => ['label' => 'Préférence de marque', 'themes' => [
                'PERMANET DUAL'        => ['permanet', 'dual', 'vestergaard'],
                'INTERCEPTOR G2'       => ['interceptor', 'g2', 'basf'],
                'Pas de préférence'    => ['pas de', 'aucune', 'indifférent', 'peu importe', 'même', 'n\'importe'],
            ]],
        ];

        $openEndedThemes = [];
        foreach ($openEndedQuestions as $key => $def) {
            $fieldMap = [
                'use_issues'    => 'net_use_issues',
                'best_features' => 'net_best_features',
                'improvement'   => 'net_improvement_suggest',
                'comparison'    => 'net_comparison_other',
                'brand_pref'    => 'net_brand_preference',
            ];
            $openEndedThemes[$key] = array_merge(
                ['label' => $def['label']],
                $this->thematize($uniq, $fieldMap[$key], $def['themes'])
            );
        }

        // ── Per-arm stats ──
        $visitMap = $visits->filter(fn($v) => ($v['household_id'] ?? '') !== '')->keyBy('household_id');
        $recordsWithArm = $records->map(function ($r) use ($visitMap) {
            $hh  = $visitMap->get($r['household_id'], []);
            $key = ($hh['bras'] ?? '') ?: self::resolveBrasKey(array_merge($r, (array) $hh));
            return array_merge($r, ['_arm' => $key]);
        });

        // Extract fieldMap here so the closure can use it
        $openEndedFieldMap = [
            'use_issues'    => 'net_use_issues',
            'best_features' => 'net_best_features',
            'improvement'   => 'net_improvement_suggest',
            'comparison'    => 'net_comparison_other',
            'brand_pref'    => 'net_brand_preference',
        ];

        $likeReasonDefs = [
            'net_like_reason___1'  => 'Le matériau est doux',
            'net_like_reason___2'  => "J'aime la taille",
            'net_like_reason___3'  => "J'aime la couleur",
            'net_like_reason___4'  => 'Efficace contre les moustiques',
            'net_like_reason___5'  => 'Efficace contre les punaises',
            'net_like_reason___6'  => 'Solide / résistante',
            'net_like_reason___7'  => 'Dure longtemps',
            'net_like_reason___8'  => 'Facile à laver',
            'net_like_reason___99' => 'Autres raisons',
        ];
        $dislikeReasonDefs = [
            'net_dislike_reason___1'  => 'Trop dure',
            'net_dislike_reason___2'  => 'Trop petite',
            'net_dislike_reason___3'  => 'Brûlures / démangeaisons',
            'net_dislike_reason___4'  => "Mauvaise odeur",
            'net_dislike_reason___5'  => "N'aime pas la couleur",
            'net_dislike_reason___6'  => 'Mal aux mains au lavage',
            'net_dislike_reason___7'  => 'Donne trop chaud',
            'net_dislike_reason___8'  => 'Se déchire facilement',
            'net_dislike_reason___9'  => 'Pas efficace vs moustiques',
            'net_dislike_reason___10' => 'Pas efficace vs insectes',
            'net_dislike_reason___11' => 'Trop courte',
            'net_dislike_reason___12' => 'Qualité faible',
            'net_dislike_reason___13' => "Pas facile à utiliser",
            'net_dislike_reason___99' => 'Autres raisons',
        ];

        $armStats = function (string $arm) use (
            $recordsWithArm, $likeReasonDefs, $dislikeReasonDefs,
            $respRoleDefs, $importanceLevels, $openEndedQuestions, $openEndedFieldMap
        ): array {
            $recs    = $recordsWithArm->where('_arm', $arm);
            $n       = $recs->unique('household_id')->count();

            $likeRecs    = $recs->where('net_like', '1');
            $dislikeRecs = $recs->where('net_like', '0');
            $noContRecs  = $recs->where('net_continue_use', '0');

            $likeReasons = [];
            foreach ($likeReasonDefs as $field => $label) {
                $c = $likeRecs->where($field, '1')->count();
                if ($c > 0) $likeReasons[$label] = $c;
            }
            arsort($likeReasons);

            $dislikeReasons = [];
            foreach ($dislikeReasonDefs as $field => $label) {
                $c = $dislikeRecs->where($field, '1')->count();
                if ($c > 0) $dislikeReasons[$label] = $c;
            }
            arsort($dislikeReasons);

            $noContReasons = $noContRecs
                ->pluck('net_continue_no_reason')
                ->filter(fn($v) => trim((string)$v) !== '')
                ->map(fn($v) => mb_strtoupper(trim($v)))
                ->countBy()
                ->sortDesc()
                ->all();

            $armUniq = $recs->unique('household_id');

            // Per-arm respondent role distribution
            $armRespRoleDist = $armUniq
                ->filter(fn($r) => ($r['uac_resp_role'] ?? '') !== '')
                ->groupBy('uac_resp_role')
                ->map->count()
                ->mapWithKeys(fn($c, $k) => [($respRoleDefs[(string)$k] ?? "Rôle $k") => $c])
                ->sortDesc()
                ->all();

            // Per-arm importance / adequacy stats
            $armBinaryPct = fn(string $f) => [
                'oui'   => $armUniq->where($f, '1')->count(),
                'non'   => $armUniq->where($f, '0')->count(),
                'total' => $armUniq->filter(fn($r) => in_array($r[$f] ?? '', ['0', '1']))->count(),
            ];
            $armImpDist = fn(string $f) => $armUniq
                ->filter(fn($r) => ($r[$f] ?? '') !== '')
                ->groupBy($f)
                ->map->count()
                ->mapWithKeys(fn($c, $k) => [($importanceLevels[(string)$k] ?? "Niveau $k") => $c])
                ->all();
            $armImportanceStats = [
                ['label' => 'Design / motif',    'field' => 'net_design_importance', 'type' => 'likert', 'dist' => $armImpDist('net_design_importance')],
                ['label' => 'Couleur',           'field' => 'net_color_importance',  'type' => 'likert', 'dist' => $armImpDist('net_color_importance')],
                ['label' => 'Taille',            'field' => 'net_taille_importance', 'type' => 'likert', 'dist' => $armImpDist('net_taille_importance')],
                ['label' => 'Taille adéquate',   'field' => 'net_size_adequate',     'type' => 'binary', 'dist' => $armBinaryPct('net_size_adequate')],
                ['label' => 'Facile à installer','field' => 'net_easy_install',      'type' => 'binary', 'dist' => $armBinaryPct('net_easy_install')],
                ['label' => 'Facile à utiliser', 'field' => 'net_easy_use',          'type' => 'binary', 'dist' => $armBinaryPct('net_easy_use')],
            ];

            // Per-arm open-ended themes
            $armOpenEndedThemes = [];
            foreach ($openEndedQuestions as $key => $def) {
                $armOpenEndedThemes[$key] = array_merge(
                    ['label' => $def['label']],
                    $this->thematize($armUniq, $openEndedFieldMap[$key], $def['themes'])
                );
            }

            return [
                'total'            => $n,
                'absent'           => $armUniq->where('ac_hh_present', '0')->count(),
                'present'          => $armUniq->where('ac_hh_present', '1')->count(),
                'like'             => $likeRecs->count(),
                'satisfied'        => $recs->whereIn('net_overall_satisfaction', ['1', '2'])->count(),
                'continueUse'      => $recs->where('net_continue_use', '1')->count(),
                'recommend'        => $recs->where('net_recommend', '1')->count(),
                'easyUse'          => $recs->where('net_easy_use', '1')->count(),
                'likeReasons'      => $likeReasons,
                'dislikeReasons'   => $dislikeReasons,
                'noContReasons'    => $noContReasons,
                'likeCount'        => $likeRecs->unique('household_id')->count(),
                'dislikeCount'     => $dislikeRecs->unique('household_id')->count(),
                'noContCount'      => $noContRecs->unique('household_id')->count(),
                'contCount'        => $recs->where('net_continue_use', '1')->unique('household_id')->count(),
                'respRoleDist'     => $armRespRoleDist,
                'importanceStats'  => $armImportanceStats,
                'openEndedThemes'  => $armOpenEndedThemes,
            ];
        };
        $byArm = ['PD' => $armStats('1'), 'G2' => $armStats('2')];

        // ── Daily tablet activity ──
        // Filter BEFORE unique so that unique() keeps the row that actually has tablet+date data,
        // not an empty parent row that REDCap sometimes exports first.
        $uaJoined = $records
            ->filter(fn($r) => ($r['ac_tablet'] ?? '') !== '' && ($r['ac_date_interview'] ?? '') !== '')
            ->unique('household_id')
            ->map(fn($r) => ['tablet' => trim($r['ac_tablet']), 'date' => trim($r['ac_date_interview'])]);
        $allUaDates = $uaJoined->pluck('date')->unique()->sort()->values();
        $uaTabletActivity = $uaJoined->groupBy('tablet')->map(function ($rows, $tablet) {
            $perDate = $rows->groupBy('date')->map(fn($g) => $g->count());
            return ['tablet' => $tablet, 'par_date' => $perDate->toArray(), 'total' => $rows->count()];
        })->sortKeys()->values();
        $uaTabletDatasets = $uaTabletActivity->map(function ($tab, $i) use ($allUaDates) {
            return [
                'label' => 'Tablette ' . $tab['tablet'],
                'data'  => $allUaDates->map(fn($d) => (int) ($tab['par_date'][$d] ?? 0))->values()->toArray(),
                'color' => self::TABLET_COLORS[$i % count(self::TABLET_COLORS)],
            ];
        })->values();

        return compact('total', 'like', 'satisfied', 'continueUse', 'recommend', 'easyUse',
                       'satisfactionDist', 'rows', 'byArm', 'uaTabletActivity', 'allUaDates', 'uaTabletDatasets',
                       'respRoleDist', 'importanceStats', 'openEndedThemes');
    }

    private function thematize(Collection $records, string $field, array $themes): array
    {
        $responses = $records
            ->pluck($field)
            ->filter(fn($v) => trim((string)$v) !== '')
            ->values();

        $total = $responses->count();
        if ($total === 0) return ['total' => 0, 'themes' => [], 'other' => ['count' => 0, 'pct' => 0, 'examples' => []]];

        $themeResults = [];
        foreach ($themes as $name => $keywords) {
            $matched = $responses->filter(function ($text) use ($keywords) {
                $lower = mb_strtolower((string)$text);
                foreach ($keywords as $kw) {
                    if (str_contains($lower, mb_strtolower($kw))) return true;
                }
                return false;
            });
            $themeResults[$name] = [
                'count'    => $matched->count(),
                'pct'      => round($matched->count() / $total * 100, 1),
                'examples' => $matched->take(3)->values()->all(),
            ];
        }
        uasort($themeResults, fn($a, $b) => $b['count'] - $a['count']);

        $otherResponses = $responses->filter(function ($text) use ($themes) {
            $lower = mb_strtolower((string)$text);
            foreach ($themes as $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($lower, mb_strtolower($kw))) return false;
                }
            }
            return true;
        });

        return [
            'total'  => $total,
            'themes' => $themeResults,
            'other'  => [
                'count'    => $otherResponses->count(),
                'pct'      => round($otherResponses->count() / $total * 100, 1),
                'examples' => $otherResponses->take(3)->values()->all(),
            ],
        ];
    }

    private function buildAEData(array $raw, Collection $visits): array
    {
        $allRecords = collect($raw)->filter(fn($r) => $r['household_id'] !== '');

        // nb_moustiquaires_etude par ménage (form 4b → cache redcap.form4a)
        $form4aMap = collect($this->redcap->getForm4aData())
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        // form_5a = enregistrements non-répétants (root record, redcap_repeat_instrument vide)
        $form5a = $allRecords->where('redcap_repeat_instrument', '');

        // form_5b (par moustiquaire — répétant) : symptômes, utilisation
        $form5b = $allRecords->where('redcap_repeat_instrument', 'form_5_adverse_avents');

        // total = ménages form_5a (enregistrements non-répétants)
        $form5aRoot  = $allRecords->where('redcap_repeat_instrument', '');
        $total       = $form5aRoot->unique('household_id')->count();
        $anySymptom  = $form5b->where('ae_any_symptom', '1')->unique('household_id')->count();
        $continueUse = $form5b->where('itn_continue_use', '1')->unique('household_id')->count();
        $bedbugs     = $form5aRoot->where('bedbugs_current_house', '1')->unique('household_id')->count();
        $bbOnNet     = $form5aRoot->where('bb_seen_on_net_ever', '1')->unique('household_id')->count();

        $symptomFields = [
            'ae_itch_adult'       => 'Démangeaisons',
            'ae_faceburn_adult'   => 'Brûlures visage',
            'ae_sneeze_adult'     => 'Éternuements',
            'ae_rhinorrhea_adult' => 'Rhinorrhée',
            'ae_headache_adult'   => 'Maux de tête',
            'ae_nausea_adult'     => 'Nausées',
            'ae_eyeirrit_adult'   => 'Irritation yeux',
            'ae_tearing_adult'    => 'Larmoiements',
            'ae_badsmell_adult'   => 'Mauvaise odeur',
        ];
        $symptomCounts = collect($symptomFields)->map(fn($label, $field) => [
            'label' => $label,
            'count' => $form5b->where($field, '1')->count(),
        ])->values();

        $form5aByHh = $form5a->keyBy('household_id');

        $rows = $form5b->unique('household_id')->map(function ($r) use ($visits, $form5aByHh) {
            $hh         = $this->visitOf($visits, $r['household_id']) ?? [];
            $a5         = $form5aByHh->get($r['household_id'], []);
            $brasKey    = ($hh['bras'] ?? '') ?: self::resolveBrasKey(array_merge($r, $hh));
            $villageKey = ($hh['village'] ?? '') ?: (self::parseHouseholdId($r['household_id'])['village'] ?? '');
            return [
                'id'           => $r['household_id'],
                'bras'         => self::BRAS_LABELS[$brasKey] ?? '—',
                'cohort'       => self::COHORT_LABELS[$hh['study_cohort'] ?? ''] ?? '—',
                'village'      => self::VILLAGE_LABELS[$villageKey] ?? '—',
                'cluster'      => self::CLUSTER_LABELS[self::extractCluster($hh)] ?? '—',
                'date'         => $a5['ad_evt_date'] ?? '—',
                'any_symptom'  => $r['ae_any_symptom'] === '1',
                'continue_use' => $r['itn_continue_use'] === '1',
                'bedbugs'      => ($a5['bedbugs_current_house'] ?? '') === '1',
            ];
        })->values();

        // ── Per-arm stats ──
        // Source autoritaire : household_ids de AeSelectedHousehold par bras.
        // Pas d'inférence — on filtre directement par appartenance à la sélection.
        $pdSelectedIds = \App\Models\AeSelectedHousehold::where('arm', 'PD')->pluck('household_id')->flip();
        $g2SelectedIds = \App\Models\AeSelectedHousehold::where('arm', 'G2')->pluck('household_id')->flip();
        $selectedIdsByArm = ['1' => $pdSelectedIds, '2' => $g2SelectedIds];

        $respondentDefs = [
            '1' => 'Chef de ménage', '2' => 'Conjoint(e)',
            '3' => 'Autre membre du ménage âgé de 18 ans ou plus', '4' => 'Aucun des précédents',
        ];
        $bbActionDefs = [
            '1' => 'Aucune action', '2' => 'Lavé le filet',
            '3' => 'Retiré le filet', '4' => 'Traitement anti-punaises', '5' => 'Autre',
        ];
        $nonUseReasonDefs = [
            '1' => 'Trop chaud',          '2' => 'Démangeaisons / Irritations',
            '3' => 'Mauvaise odeur',      '4' => 'Pas de moustiques',
            '5' => 'Difficile à installer','6' => 'Défaut du filet', '99' => 'Autre raison',
        ];

        $aeArmStats = function (string $arm) use ($form5a, $form5b, $selectedIdsByArm, $symptomFields, $respondentDefs, $bbActionDefs, $nonUseReasonDefs): array {
            $sel    = $selectedIdsByArm[$arm];
            $a5     = $form5a->filter(fn($r) => $sel->has($r['household_id']));
            $b5     = $form5b->filter(fn($r) => $sel->has($r['household_id']));
            $a5Uniq = $a5->unique('household_id');
            $b5Tot  = $b5->count();
            $n      = $a5Uniq->count();
            $pctHh  = fn($v) => $n > 0 ? round($v / $n * 100, 1) : 0;
            $pctNet = fn($v) => $b5Tot > 0 ? round($v / $b5Tot * 100, 1) : 0;

            // Respondent
            $respondentDist = $a5Uniq
                ->filter(fn($r) => ($r['ad_evt_respondent'] ?? '') !== '')
                ->groupBy('ad_evt_respondent')->map->count()
                ->mapWithKeys(fn($c, $k) => [($respondentDefs[(string)$k] ?? "Rôle $k") => $c])
                ->sortDesc()->all();

            // Net presence
            $itnPresent = $a5Uniq->sum(fn($r) => (int)($r['ae_itn_present_n'] ?? 0));
            $itnTotal   = $a5Uniq->sum(fn($r) => (int)($r['ae_itn_n'] ?? 0));

            // Bedbug locations (binary checkboxes)
            $bbLocDefs = [
                'Première utilisation' => 'bedbug_seen_first_use',
                'Ancien filet'         => 'bb_net_old',
                'Cadre du lit'         => 'bb_bed_frame',
                'Literie'              => 'bb_bed_linen',
                'Matelas'              => 'bb_mattress',
                'Vêtements'            => 'bb_clothing',
                'Murs / sol'           => 'bb_walls_floor',
                'Bagages'              => 'bb_luggage',
                'Autre'                => 'bb_other_specify',
            ];
            $bbLocations = [];
            foreach ($bbLocDefs as $label => $field) {
                $c = $a5Uniq->where($field, '1')->count();
                if ($c > 0) $bbLocations[$label] = ['count' => $c, 'pct' => $pctHh($c)];
            }
            uasort($bbLocations, fn($a, $b) => $b['count'] - $a['count']);

            // BB net influence
            $bbNetEff    = $a5Uniq->where('bb_net_eff_att', '1')->count();
            $bbSeenOnNet = $a5Uniq->where('bb_seen_on_net_ever', '1')->count();
            $bbStopNet   = $a5Uniq->where('bb_stop_net_use_ever', '1')->count();
            $bbActionDist = $a5Uniq
                ->filter(fn($r) => ($r['bb_net_action'] ?? '') !== '')
                ->groupBy('bb_net_action')->map->count()
                ->mapWithKeys(fn($c, $k) => [($bbActionDefs[(string)$k] ?? "Action $k") => $c])
                ->sortDesc()->all();

            // Net-level symptoms
            $ageClassDefs = ['1' => 'Adulte', '2' => 'Enfant (< 18 ans)', '3' => 'Femme enceinte'];
            $symDetails = [];
            foreach ($symptomFields as $field => $label) {
                $sympRecs = $b5->where($field, '1');
                $count    = $sympRecs->count();
                $ageBase  = str_replace('_adult', '_adult_age', $field);
                $ageCounts = [];
                foreach ($ageClassDefs as $n => $ageLabel) {
                    $c = $sympRecs->where($ageBase . '___' . $n, '1')->count();
                    if ($c > 0) $ageCounts[$ageLabel] = $c;
                }
                $symDetails[$field] = [
                    'label'        => $label,
                    'count'        => $count,
                    'pct'          => $pctNet($count),
                    'ageCounts'    => $ageCounts,
                    'householdIds' => $sympRecs->pluck('household_id')->unique()->sort()->values()->toArray(),
                ];
            }

            // Other symptoms (free text)
            $otherSymptoms = $b5->pluck('ae_other_symptom')
                ->filter(fn($v) => trim((string)$v) !== '')
                ->map(fn($v) => mb_strtoupper(trim($v)))
                ->countBy()->sortDesc()->all();

            // Continue use + non-use reason
            $continueUseHh = $b5->where('itn_continue_use', '1')->unique('household_id')->count();
            $nonUseReasonDist = $b5->where('itn_continue_use', '0')
                ->filter(fn($r) => ($r['itn_nonuse_reason'] ?? '') !== '')
                ->groupBy('itn_nonuse_reason')->map->count()
                ->mapWithKeys(fn($c, $k) => [($nonUseReasonDefs[(string)$k] ?? "Raison $k") => $c])
                ->sortDesc()->all();

            $reportedPhysician = $b5->where('ae_reported_physician', '1')->unique('household_id')->count();

            // Average users per net
            $avgNetUsers = $b5Tot > 0
                ? round($b5->avg(fn($r) => is_numeric($r['ae_net_users_n'] ?? '') ? (float)$r['ae_net_users_n'] : 0), 1)
                : 0;

            return [
                'total'             => $n,
                'hhWithNets'        => $b5->unique('household_id')->count(),
                'anySymptom'        => $b5->where('ae_any_symptom', '1')->unique('household_id')->count(),
                'continueUse'       => $continueUseHh,
                'bedbugs'           => $a5Uniq->where('bedbugs_current_house', '1')->unique('household_id')->count(),
                'respondentDist'    => $respondentDist,
                'itnPresent'        => $itnPresent,
                'itnTotal'          => $itnTotal,
                'bbCommunity'       => $a5Uniq->where('bedbugs_community_problem', '1')->count(),
                'bbLocations'       => $bbLocations,
                'bbNetEff'          => $bbNetEff,
                'bbSeenOnNet'       => $bbSeenOnNet,
                'bbStopNet'         => $bbStopNet,
                'bbActionDist'      => $bbActionDist,
                'netCount'          => $b5Tot,
                'avgNetUsers'       => $avgNetUsers,
                'symDetails'        => $symDetails,
                'otherSymptoms'     => $otherSymptoms,
                'nonUseReasonDist'  => $nonUseReasonDist,
                'reportedPhysician' => $reportedPhysician,
            ];
        };
        $byArm = ['PD' => $aeArmStats('1'), 'G2' => $aeArmStats('2')];

        // ── Per-arm symptom breakdown + net-level symptom rate + absent stats ──
        $symptomsByArm = [];
        $absentByArm   = [];
        foreach (['PD' => '1', 'G2' => '2'] as $armKey => $armCode) {
            $sel   = $selectedIdsByArm[$armCode];
            $a5arm = $form5a->filter(fn($r) => $sel->has($r['household_id']));
            $b5arm = $form5b->filter(fn($r) => $sel->has($r['household_id']));

            $armSymptoms = collect($symptomFields)->map(fn($label, $field) => [
                'label' => $label,
                'count' => $b5arm->where($field, '1')->count(),
            ])->values();

            $b5armTotal  = $b5arm->count();
            $b5armSymp   = $b5arm->where('ae_any_symptom', '1')->count();
            $netSympRate = $b5armTotal > 0 ? round($b5armSymp / $b5armTotal * 100, 1) : 0;

            $symptomsByArm[$armKey] = [
                'symptoms'    => $armSymptoms,
                'netTotal'    => $b5armTotal,
                'netWithSymp' => $b5armSymp,
                'netSympRate' => $netSympRate,
            ];

            $selectedTotal  = $sel->count();
            $presentHhIds   = $a5arm->where('ae_hh_present', '1')->pluck('household_id')->unique();
            $absentHhIds    = $a5arm->where('ae_hh_present', '0')->pluck('household_id')->unique()->diff($presentHhIds);
            $netsExpected   = $sel->keys()->sum(fn($id) => (int)($form4aMap->get($id, [])['nb_moustiquaires_etude'] ?? 0));
            $absentByArm[$armKey] = [
                'selected'      => $selectedTotal,
                'present'       => $presentHhIds->count(),
                'absent'        => $absentHhIds->count(),
                'nets_expected' => $netsExpected,
                'nets_followed' => $b5arm->count(),
            ];
        }

        // ── Daily tablet activity ──
        $aeJoined = $form5a
            ->filter(fn($r) => ($r['ae_tablette_id'] ?? '') !== '' && ($r['ad_evt_date'] ?? '') !== '')
            ->map(fn($r) => ['tablet' => $r['ae_tablette_id'], 'date' => $r['ad_evt_date']]);
        $allAeDates = $aeJoined->pluck('date')->unique()->sort()->values();
        $aeTabletActivity = $aeJoined->groupBy('tablet')->map(function ($rows, $tablet) use ($allAeDates) {
            $perDate = $rows->groupBy('date')->map->count();
            return ['tablet' => $tablet, 'par_date' => $perDate->toArray(), 'total' => $rows->count()];
        })->sortKeys()->values();
        $aeTabletDatasets = $aeTabletActivity->map(function ($tab, $i) use ($allAeDates) {
            return [
                'label' => 'Tablette ' . $tab['tablet'],
                'data'  => $allAeDates->map(fn($d) => $tab['par_date'][$d] ?? 0)->values()->toArray(),
                'color' => self::TABLET_COLORS[$i % count(self::TABLET_COLORS)],
            ];
        })->values();

        return compact('total', 'anySymptom', 'continueUse', 'bedbugs', 'bbOnNet',
                       'symptomCounts', 'rows', 'byArm', 'aeTabletActivity', 'allAeDates', 'aeTabletDatasets',
                       'symptomsByArm', 'absentByArm');
    }

    private function groupStats(Collection $visits, string $field, array $labels): Collection
    {
        return $visits->groupBy($field)->map(fn($g, $k) => [
            'label'     => $labels[$k] ?? ($k ?: 'Non défini'),
            'total'     => $g->count(),
            'consented' => $g->where('consent_accepted', '1')->count(),
        ])->values();
    }
}
