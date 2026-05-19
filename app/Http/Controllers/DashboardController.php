<?php

namespace App\Http\Controllers;

use App\Services\RedCapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const CLUSTER_LABELS = [
        '1' => 'Djigbe / Grappe 1', '2' => 'Djigbe / Grappe 2',
        '3' => 'Gbonou / Grappe 1', '4' => 'Gbonou / Grappe 2', '5' => 'Gbonou / Grappe 3',
        '6' => 'Miniffi / Grappe 1','7' => 'Miniffi / Grappe 2','8' => 'Miniffi / Grappe 3',
    ];
    // Mapping (village, grappe) → cluster key, derived from household ID format
    private const CLUSTER_FROM_ID = [
        'DJ' => ['01' => '1', '02' => '2'],
        'GB' => ['01' => '3', '02' => '4', '03' => '5'],
        'MI' => ['01' => '6', '02' => '7', '03' => '8'],
    ];
    // Accepte les codes numériques (1/2/3) et alphanumériques (DJ/GB/MI) selon export REDCap
    private const VILLAGE_LABELS = [
        'DJ' => 'Djigbe', '1' => 'Djigbe',
        'GB' => 'Gbonou', '2' => 'Gbonou',
        'MI' => 'Miniffi', '3' => 'Miniffi',
    ];
    private const BRAS_LABELS    = ['1' => 'PERMANET DUAL', '2' => 'INTERCEPTOR G2'];
    // Bras par grappe village-spécifique (valeurs des variables cluster_djigbe/gbonou/miniffi)
    private const BRAS_FROM_DJIGBE  = ['1' => '1', '2' => '2'];               // 1=PD, 2=G2
    private const BRAS_FROM_GBONOU  = ['1' => '2', '2' => '2', '3' => '1'];  // 1,2=G2, 3=PD
    private const BRAS_FROM_MINIFFI = ['1' => '2', '2' => '1', '3' => '1'];  // 1=G2, 2,3=PD
    private const COHORT_LABELS  = ['1' => 'Cohorte A', '2' => 'Cohorte B'];
    private const TABLET_COLORS  = [
        '#C41230','#374151','#D97706','#16A34A',
        '#7C3AED','#0891B2','#DB2777','#059669',
        '#EA580C','#4338CA','#0D9488','#9333EA',
    ];

    /**
     * Parses a household ID with format VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE.
     * Returns ['valid', 'village', 'grappe', 'bras', 'tablette', 'menage'].
     */
    private static function parseHouseholdId(string $id): array
    {
        $empty = ['valid' => false, 'village' => '', 'grappe' => '', 'bras' => '', 'tablette' => '', 'menage' => ''];
        $parts = explode('-', $id);
        if (count($parts) !== 5) return $empty;

        [$village, $grappe, $bras, $tablette, $menage] = $parts;
        $village = strtoupper($village);
        $bras    = strtoupper($bras);

        $valid = in_array($village, ['DJ', 'GB', 'MI'])
              && preg_match('/^0[1-3]$/', $grappe)
              && in_array($bras, ['PD', 'G2'])
              && preg_match('/^(0[1-9]|1[0-9]|20)$/', $tablette)
              && preg_match('/^\d{3}$/', $menage)
              && (int)$menage >= 1;

        return compact('valid', 'village', 'grappe', 'bras', 'tablette', 'menage');
    }

    private static function inferBrasKey(string $id): string
    {
        $p = self::parseHouseholdId($id);
        if ($p['bras'] === 'G2') return '2';
        if ($p['bras'] === 'PD') return '1';
        return '';
    }

    /**
     * Resolves the bras key ('1'=PERMANET DUAL, '2'=INTERCEPTOR G2) for a household row.
     * Priority: village-specific cluster fields → bras field → household_id parsing.
     */
    private static function resolveBrasKey(array $hh): string
    {
        $djigbe  = $hh['cluster_djigbe']  ?? '';
        $gbonou  = $hh['cluster_gbonou']  ?? '';
        $miniffi = $hh['cluster_miniffi'] ?? '';

        // Use isset so unrecognised / '0' values fall through to the next village variable
        if (isset(self::BRAS_FROM_DJIGBE[$djigbe]))   return self::BRAS_FROM_DJIGBE[$djigbe];
        if (isset(self::BRAS_FROM_GBONOU[$gbonou]))   return self::BRAS_FROM_GBONOU[$gbonou];
        if (isset(self::BRAS_FROM_MINIFFI[$miniffi])) return self::BRAS_FROM_MINIFFI[$miniffi];

        // Last resort: parse the household ID (bras field is calculated, not stored)
        return self::inferBrasKey($hh['household_id'] ?? '');
    }

    private static function extractCluster(array $hh): string
    {
        $fromFields = ($hh['cluster_id'] ?? '')
            ?: ($hh['cluster_djigbe'] ?? '')
            ?: ($hh['cluster_gbonou'] ?? '')
            ?: ($hh['cluster_miniffi'] ?? '');
        if ($fromFields !== '') return $fromFields;

        $p = self::parseHouseholdId($hh['household_id'] ?? '');
        return self::CLUSTER_FROM_ID[$p['village']][$p['grappe']] ?? '';
    }

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
        set_time_limit(300);

        // Fire all REDCap API calls concurrently; get*() methods below read from cache.
        $this->redcap->warmAll();

        // ════════════════════════════════
        // BASELINE DATA
        // ════════════════════════════════
        $visiteRaw   = $this->redcap->getVisiteData();
        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $memberRaw   = $this->redcap->getMemberData();
        $oldNetRaw   = $this->redcap->getOldNetData();
        $studyNetRaw = $this->redcap->getStudyNetData();

        $visits = $this->buildVisits($visiteRaw, $idRaw, $qbRaw);

        $baseline = $this->buildBaselineData($visits, $qbRaw, $memberRaw, $oldNetRaw, $studyNetRaw, $idRaw);

        // ════════════════════════════════
        // USER ACCEPTABILITY
        // ════════════════════════════════
        $uaRaw  = $this->redcap->getUserAcceptabilityData('user_acceptability_arm_1');
        $ua24Raw = $this->redcap->getUserAcceptabilityData('suivi_24_mois_arm_1');
        $ua  = $this->buildUAData($uaRaw, $visits);
        $ua24 = $this->buildUAData($ua24Raw, $visits);

        // ════════════════════════════════
        // ADVERSE EVENTS
        // ════════════════════════════════
        $aeRaw = $this->redcap->getAdverseEventsData();
        $ae    = $this->buildAEData($aeRaw, $visits);

        // ════════════════════════════════
        // FOLLOW-UP (6, 12, 18, 24, 36 mois)
        // ════════════════════════════════
        $followUpEvents = [
            'suivi6'  => 'suivi_6_mois_arm_1',  'suivi12' => 'suivi_12_mois_arm_1',
            'suivi18' => 'suivi_18_mois_coho_arm_1', 'suivi24' => 'suivi_24_mois_arm_1',
            'suivi36' => 'suivi_36_mois_arm_1',
        ];
        $followUp = [];
        foreach ($followUpEvents as $key => $event) {
            $raw = $this->redcap->getFollowUpData($event);
            $followUp[$key] = $this->buildFollowUpData($raw, $visits);
        }

        $reportTablets = collect($visiteRaw)
            ->filter(fn($r) => ($r['a_tablette_id'] ?? '') !== '')
            ->pluck('a_tablette_id')->unique()->sort()->values();

        $performanceChart = $this->buildPerformanceChartData($visiteRaw, $idRaw, $qbRaw);

        return view('dashboard', [
            'events'          => self::EVENTS,
            'baseline'        => $baseline,
            'ua'              => $ua,
            'ua24'            => $ua24,
            'ae'              => $ae,
            'followUp'        => $followUp,
            'projectInfo'     => $this->redcap->getProjectInfo(),
            'reportTablets'   => $reportTablets,
            'performanceChart'=> $performanceChart,
        ]);
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

        $nets = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
                && $r['redcap_repeat_instance'] !== ''
                && $consentedHhIds->has($r['household_id'])
            )
            ->map(function ($r) use ($visitDataByHh) {
                $visit = $visitDataByHh->get($r['household_id'], []);
                return [
                    'household_id' => $r['household_id'],
                    'net_code'     => $r['net_identifier_man'] ?: '—',
                    'tablette'     => $visit['a_tablette_id'] ?? '',
                    'date_visit'   => $visit['a_date_visite_id'] ?? '',
                ];
            });

        if ($dateFrom)       $nets = $nets->filter(fn($r) => $r['date_visit'] >= $dateFrom);
        if ($dateTo)         $nets = $nets->filter(fn($r) => $r['date_visit'] <= $dateTo);
        if (!empty($tablets)) $nets = $nets->filter(fn($r) => in_array($r['tablette'], $tablets));

        $netsByTablet = $nets
            ->filter(fn($r) => $r['tablette'] !== '')
            ->groupBy('tablette')->sortKeys()
            ->map(fn($tNets, $tablet) => [
                'tablet'    => $tablet,
                'visited'   => $visitStatsByTablet->get($tablet, ['visited' => 0, 'consented' => 0])['visited'],
                'consented' => $visitStatsByTablet->get($tablet, ['visited' => 0, 'consented' => 0])['consented'],
                'households' => $tNets->groupBy('household_id')->sortKeys()
                    ->map(fn($hhNets, $hhId) => [
                        'household_id' => $hhId,
                        'date_visit'   => $hhNets->first()['date_visit'],
                        'nets'         => $hhNets->pluck('net_code')->values(),
                        'total'        => $hhNets->count(),
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
            'label'     => self::BRAS_LABELS[$bras] ?? "Bras $bras",
            'total'     => $g->count(),
            'consented' => $g->where('consent_accepted', '1')->count(),
            'cohorts'   => $g->groupBy('study_cohort')->map(fn($sg, $c) => [
                'label'     => self::COHORT_LABELS[$c] ?? "Cohorte $c",
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
            ->map(fn($tNets, $tablet) => [
                'tablet'     => $tablet,
                'visited'    => $visitsByTablet->get($tablet, collect())->count(),
                'consented'  => $visitsByTablet->get($tablet, collect())->where('consent_accepted', '1')->count(),
                'households' => $tNets->groupBy('household_id')->sortKeys()
                    ->map(fn($hhNets, $hhId) => [
                        'household_id' => $hhId,
                        'date_visit'   => $hhNets->first()['date_visit'],
                        'nets'         => $hhNets->pluck('net_code')->values(),
                        'total'        => $hhNets->count(),
                    ])->values(),
                'total' => $tNets->count(),
            ])->values();

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
                'cohorts' => $brasGroup->groupBy('cohort')->sortKeys()
                    ->map(fn($g, $cohort) => [
                        'cohort' => $cohort,
                        'nets'   => $g->count(),
                    ])->values(),
            ])->values();

        return compact(
            'totalVisited', 'totalConsented',
            'byVillage', 'byBras', 'byCluster', 'cohortByBras',
            'gpsData', 'allDates', 'tabletDatasets', 'tabletActivity',
            'householdDetails', 'membersByHh',
            'studyNetsDetail', 'studyNetsByHh', 'byDag', 'netsByTablet',
            'byBrasCohortNets'
        ) + ['netsByBras' => $studyNetsDetail->groupBy('bras')->map->count()];
    }

    private function buildUAData(array $raw, Collection $visits): array
    {
        $records = collect($raw)->filter(fn($r) => $r['household_id'] !== '');
        $total   = $records->unique('household_id')->count();

        $like        = $records->where('net_like', '1')->count();
        $satisfied   = $records->whereIn('net_overall_satisfaction', ['1', '2'])->count();
        $continueUse = $records->where('net_continue_use', '1')->count();
        $recommend   = $records->where('net_recommend', '1')->count();
        $easyUse     = $records->where('net_easy_use', '1')->count();

        $satisfactionDist = $records->groupBy('net_overall_satisfaction')
            ->map->count()
            ->mapWithKeys(fn($c, $k) => [match($k) {
                '1' => 'Très satisfait', '2' => 'Satisfait',
                '3' => 'Insatisfait', '4' => 'Très insatisfait', default => "Valeur $k"
            } => $c]);

        $rows = $records->unique('household_id')->map(function ($r) use ($visits) {
            $hh         = $visits->firstWhere('household_id', $r['household_id']) ?? [];
            $brasKey    = ($hh['bras'] ?? '') ?: self::resolveBrasKey(array_merge($r, $hh));
            $villageKey = ($hh['village'] ?? '') ?: (self::parseHouseholdId($r['household_id'])['village'] ?? '');
            return [
                'id'           => $r['household_id'],
                'bras'         => self::BRAS_LABELS[$brasKey] ?? '—',
                'cohort'       => self::COHORT_LABELS[$hh['study_cohort'] ?? ''] ?? '—',
                'village'      => self::VILLAGE_LABELS[$villageKey] ?? '—',
                'cluster'      => self::CLUSTER_LABELS[self::extractCluster($hh)] ?? '—',
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

        return compact('total', 'like', 'satisfied', 'continueUse', 'recommend', 'easyUse', 'satisfactionDist', 'rows');
    }

    private function buildAEData(array $raw, Collection $visits): array
    {
        $allRecords = collect($raw)->filter(fn($r) => $r['household_id'] !== '');

        // form_5a (informations génériques — non-répétant) : punaises, etc.
        $form5a = $allRecords->filter(fn($r) =>
            $r['redcap_repeat_instrument'] === '' ||
            $r['redcap_repeat_instrument'] === 'form_5a_informations_gnriques_adverse_events'
        );

        // form_5b (par moustiquaire — répétant) : symptômes, utilisation
        $form5b = $allRecords->where('redcap_repeat_instrument', 'form_5_adverse_avents');

        $total       = $allRecords->unique('household_id')->count();
        $anySymptom  = $form5b->where('ae_any_symptom', '1')->unique('household_id')->count();
        $continueUse = $form5b->where('itn_continue_use', '1')->unique('household_id')->count();
        $bedbugs     = $form5a->where('bedbugs_current_house', '1')->count();
        $bbOnNet     = $form5a->where('bb_seen_on_net_ever', '1')->count();

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
            $hh         = $visits->firstWhere('household_id', $r['household_id']) ?? [];
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

        return compact('total', 'anySymptom', 'continueUse', 'bedbugs', 'bbOnNet', 'symptomCounts', 'rows');
    }

    private function buildFollowUpData(array $raw, Collection $visits): array
    {
        $records = collect($raw)->filter(fn($r) =>
            ($r['redcap_repeat_instrument'] === 'form_7_suivi_des_moustiquaires_de_ltude' ||
             $r['redcap_repeat_instrument'] === 'form_10_suivi_cohorte_b')
            && $r['redcap_repeat_instance'] !== ''
        );

        $total     = $records->unique('household_id')->count();
        $totalNets = $records->count();

        $form7 = $records->where('redcap_repeat_instrument', 'form_7_suivi_des_moustiquaires_de_ltude');
        $netsPresent7   = $form7->where('net_present', '1')->count();
        $netsUsed7      = $form7->where('net_used_last_night', '1')->count();
        $netsHoles7     = $form7->where('itn_has_holes', '1')->count();
        $netsUsed7days7 = $form7->where('itn_utilisation_7j', '1')->count();

        $form10 = $records->where('redcap_repeat_instrument', 'form_10_suivi_cohorte_b');
        $netsPresent10   = $form10->where('itn_study_verified', '1')->count();
        $netsUsed10      = $form10->where('itn_used_last_night', '1')->count();
        $netsUsed7days10 = $form10->where('itn_use_lastweek', '1')->count();

        $rows = $records->map(function ($r) use ($visits) {
            $hh         = $visits->firstWhere('household_id', $r['household_id']) ?? [];
            $isF7       = $r['redcap_repeat_instrument'] === 'form_7_suivi_des_moustiquaires_de_ltude';
            $netId      = $isF7
                ? ($r['fw_study_net_id_scan'] ?: $r['fw_study_net_id_saisie'] ?? '—')
                : ($r['itn_unique_id_scan'] ?: $r['itn_unique_id_saisi'] ?? '—');
            $brasKey    = ($hh['bras'] ?? '') ?: self::resolveBrasKey(array_merge($r, $hh));
            $villageKey = ($hh['village'] ?? '') ?: (self::parseHouseholdId($r['household_id'])['village'] ?? '');

            return [
                'id'         => $r['household_id'],
                'bras'       => self::BRAS_LABELS[$brasKey] ?? '—',
                'cohort'     => self::COHORT_LABELS[$hh['study_cohort'] ?? ''] ?? '—',
                'village'    => self::VILLAGE_LABELS[$villageKey] ?? '—',
                'cluster'    => self::CLUSTER_LABELS[self::extractCluster($hh)] ?? '—',
                'form'       => $isF7 ? 'Form 7 (Coh. A)' : 'Form 10 (Coh. B)',
                'net_id'     => $netId,
                'date'       => $isF7 ? ($r['afw_date'] ?? '—') : ($r['fw_cb_date_visit'] ?? '—'),
                'present'    => $isF7 ? ($r['net_present'] === '1') : ($r['itn_study_verified'] === '1'),
                'used_night' => $isF7 ? ($r['net_used_last_night'] === '1') : ($r['itn_used_last_night'] === '1'),
                'has_holes'  => $isF7 ? ($r['itn_has_holes'] === '1') : null,
                'used_7days' => $isF7 ? ($r['itn_utilisation_7j'] === '1') : ($r['itn_use_lastweek'] === '1'),
            ];
        })->values();

        $hhBrasMap = $visits->pluck('bras', 'household_id');
        $byBras = collect(['1' => 'PERMANET DUAL', '2' => 'INTERCEPTOR G2'])
            ->map(function ($brasLabel, $brasKey) use ($form7, $form10, $hhBrasMap) {
                $resolveBras = fn($r) => $hhBrasMap->get($r['household_id'], '') ?: self::inferBrasKey($r['household_id']);
                $f7  = $form7->filter(fn($r)  => $resolveBras($r) === $brasKey);
                $f10 = $form10->filter(fn($r) => $resolveBras($r) === $brasKey);
                return [
                    'label'         => $brasLabel,
                    'brasKey'       => $brasKey,
                    'form7Count'    => $f7->count(),
                    'netsPresent7'  => $f7->where('net_present', '1')->count(),
                    'netsUsed7'     => $f7->where('net_used_last_night', '1')->count(),
                    'netsHoles7'    => $f7->where('itn_has_holes', '1')->count(),
                    'netsUsed7d7'   => $f7->where('itn_utilisation_7j', '1')->count(),
                    'form10Count'   => $f10->count(),
                    'netsPresent10' => $f10->where('itn_study_verified', '1')->count(),
                    'netsUsed10'    => $f10->where('itn_used_last_night', '1')->count(),
                    'netsUsed7d10'  => $f10->where('itn_use_lastweek', '1')->count(),
                ];
            })->values();

        return compact(
            'total', 'totalNets',
            'netsPresent7', 'netsUsed7', 'netsHoles7', 'netsUsed7days7',
            'netsPresent10', 'netsUsed10', 'netsUsed7days10',
            'rows', 'byBras'
        ) + ['form7Count' => $form7->count(), 'form10Count' => $form10->count()];
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
