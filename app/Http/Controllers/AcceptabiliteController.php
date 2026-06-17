<?php

namespace App\Http\Controllers;

use App\Models\AeSelectedHousehold;
use App\Services\RedCapService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcceptabiliteController extends Controller
{
    private const ARM_LABELS = ['PD' => 'PERMANET DUAL', 'G2' => 'INTERCEPTOR G2'];

    private const VILLAGE_LABELS = [
        'AG' => 'Agao', 'AK' => 'Akoba', 'DJ' => 'Djigbe',
        'GB' => 'Gbonou', 'MI' => 'Miniffi', 'DO' => 'Dogbo',
    ];

    private const CLUSTER_DISPLAY = [
        '1'  => 'Djigbe 1',  '2'  => 'Djigbe 2',
        '3'  => 'Gbonou 1',  '4'  => 'Gbonou 2',  '5'  => 'Gbonou 3',
        '6'  => 'Miniffi 1', '7'  => 'Miniffi 2', '8'  => 'Miniffi 3', '9' => 'Miniffi 4',
        '10' => 'Dogbo',     '11' => 'Agao',       '12' => 'Akoba',
    ];

    private const CLUSTER_LABELS = [
        '1' =>'Djigbe / Grappe 1',   '2' =>'Djigbe / Grappe 2',
        '3' =>'Gbonou / Grappe 1',   '4' =>'Gbonou / Grappe 2',  '5' =>'Gbonou / Grappe 3',
        '6' =>'Miniffi / Grappe 1',  '7' =>'Miniffi / Grappe 2', '8' =>'Miniffi / Grappe 3',
        '9' =>'Miniffi / Grappe 4',  '10'=>'Dogbo / Grappe 1',
        '11'=>'Agao / Grappe 1',     '12'=>'Akoba / Grappe 1',
    ];

    public function __construct(private RedCapService $redcap) {}

    // ── GET /acceptabilite ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        set_time_limit(300);
        $fullPool = $this->buildPool();
        $stats    = $this->computeStats($fullPool);

        // Cluster options depuis le pool réel
        $clusterOptions = $fullPool
            ->pluck('cluster_id')->filter()->unique()->sort()->values();

        // Filtres
        $pool = $fullPool;
        if ($request->filled('arm'))     $pool = $pool->filter(fn($h) => $h['arm'] === $request->arm);
        if ($request->filled('cluster')) $pool = $pool->filter(fn($h) => (string)($h['cluster_id'] ?? '') === (string)$request->cluster);
        if ($request->filled('ua_status')) {
            $pool = match($request->ua_status) {
                'visited' => $pool->filter(fn($h) =>  $h['ua_visited']),
                'pending' => $pool->filter(fn($h) => !$h['ua_visited']),
                default   => $pool,
            };
        }
        if ($request->filled('ae_status')) {
            $pool = match($request->ae_status) {
                'selected' => $pool->filter(fn($h) =>  $h['is_ae_selected']),
                'visited'  => $pool->filter(fn($h) =>  $h['is_ae_selected'] && $h['ae_visited']),
                'pending'  => $pool->filter(fn($h) =>  $h['is_ae_selected'] && !$h['ae_visited']),
                'none'     => $pool->filter(fn($h) => !$h['is_ae_selected']),
                default    => $pool,
            };
        }
        if ($request->filled('search')) {
            $q = strtolower($request->search);
            $pool = $pool->filter(fn($h) =>
                str_contains(strtolower($h['household_id'] ?? ''), $q) ||
                str_contains(strtolower($h['head_name']    ?? ''), $q) ||
                str_contains(strtolower($h['contact_name'] ?? ''), $q)
            );
        }

        $sorted  = $pool->sortBy(['cluster_id', 'arm', 'household_id'])->values();
        $perPage = 50;
        $page    = max(1, (int) $request->input('page', 1));

        $households = new LengthAwarePaginator(
            $sorted->slice(($page - 1) * $perPage, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        $clusterDisplay = self::CLUSTER_DISPLAY;

        return view('acceptabilite', compact('households', 'stats', 'clusterDisplay', 'clusterOptions'));
    }

    // ── GET /acceptabilite/pdf ─────────────────────────────────────────────────
    public function pdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(600);

        $pool = $this->buildPool();

        $arm     = $request->input('arm');
        $cluster = $request->input('cluster');

        if ($arm)     $pool = $pool->filter(fn($h) => $h['arm'] === $arm)->values();
        if ($cluster) $pool = $pool->filter(fn($h) => (string)($h['cluster_id'] ?? '') === (string)$cluster)->values();

        $stats = $this->computeStats($pool);

        $grouped = $pool
            ->sortBy(['cluster_id', 'arm', 'household_id'])
            ->groupBy('cluster_id')
            ->sortKeys();

        $armLabels      = self::ARM_LABELS;
        $villageLabels  = self::VILLAGE_LABELS;
        $clusterDisplay = self::CLUSTER_DISPLAY;
        $now            = now()->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm');
        $clusterLabel   = $cluster ? (self::CLUSTER_LABELS[$cluster] ?? "Grappe $cluster") : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'acceptabilite-pdf',
            compact('grouped', 'stats', 'armLabels', 'villageLabels', 'clusterDisplay',
                    'now', 'arm', 'cluster', 'clusterLabel')
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

        $clusterSlug = $cluster
            ? '-' . str_replace(' ', '-', self::CLUSTER_DISPLAY[$cluster] ?? 'grappe'.$cluster)
            : '';
        $filename = 'acceptabilite-ae-cohorte-a'
            . ($arm ? '-' . strtolower($arm) : '')
            . $clusterSlug
            . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    // ── Stats ──────────────────────────────────────────────────────────────────
    private function computeStats(Collection $pool): array
    {
        $uaTotal  = $pool->count();
        $uaDone   = $pool->where('ua_visited', true)->count();
        $aeTotal  = $pool->where('is_ae_selected', true)->count();
        $aeDone   = $pool->where('is_ae_selected', true)->where('ae_visited', true)->count();

        return [
            'ua_total'   => $uaTotal,
            'ua_visited' => $uaDone,
            'ua_pending' => $uaTotal - $uaDone,
            'ua_rate'    => $uaTotal > 0 ? round($uaDone / $uaTotal * 100, 1) : 0,
            'ae_total'   => $aeTotal,
            'ae_visited' => $aeDone,
            'ae_pending' => $aeTotal - $aeDone,
            'ae_rate'    => $aeTotal > 0 ? round($aeDone / $aeTotal * 100, 1) : 0,
        ];
    }

    // ── Pool : tous ménages Cohorte A consentants ──────────────────────────────
    private function buildPool(): Collection
    {
        // Chauffe en parallèle uniquement les 4 endpoints nécessaires
        $this->warmAcceptabilite();

        $idData     = collect($this->redcap->getIdentificationData());
        $form1bData = collect($this->redcap->getForm1bData());
        $uaData     = collect($this->redcap->getUserAcceptabilityData());
        $aeData     = collect($this->redcap->getAdverseEventsData());

        $form1bMap = $form1bData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        // Un enregistrement UA = visité
        $uaVisited = $uaData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '')
            ->pluck('household_id')
            ->flip();

        // Un enregistrement AE non-répétant = visité
        $aeVisited = $aeData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('household_id')
            ->flip();

        // Ménages sélectionnés AE depuis la DB
        $aeSelectedIds = AeSelectedHousehold::pluck('household_id')->flip();

        return $idData
            ->filter(fn($r) =>
                ($r['household_id'] ?? '') !== '' &&
                ($r['consent_accepted'] ?? '') === '1' &&
                ($r['study_cohort']    ?? '') === '1'
            )
            ->unique('household_id')
            ->map(function ($r) use ($form1bMap, $uaVisited, $aeVisited, $aeSelectedIds) {
                $id  = $r['household_id'];
                $arm = $this->inferArm($r);
                if ($arm === '') return null;

                $form1b  = $form1bMap->get($id, []);
                $village = $r['village'] ?? null;
                $isAe    = $aeSelectedIds->has($id);

                return [
                    'household_id'   => $id,
                    'arm'            => $arm,
                    'study_cohort'   => $r['study_cohort'] ?? null,
                    'village'        => $village,
                    'village_label'  => self::VILLAGE_LABELS[$village] ?? $village,
                    'cluster_id'     => $r['cluster_id'] ?? null,
                    'head_name'      => $form1b['b_hhh_fullname']           ?? null,
                    'head_phone'     => $form1b['b_hhh_phone']               ?? null,
                    'contact_name'   => $form1b['b_hh_point_contact']        ?? null,
                    'contact_phone'  => $form1b['b_hh_point_contact_phone']  ?? null,
                    'moment_visite'  => $form1b['b_hh_moment_visite']        ?? null,
                    'adresse'        => $form1b['b_hh_address']              ?? null,
                    'ua_visited'     => $uaVisited->has($id),
                    'is_ae_selected' => $isAe,
                    'ae_visited'     => $isAe && $aeVisited->has($id),
                ];
            })
            ->filter()
            ->values();
    }

    // Parallélise les 4 appels REDCap nécessaires — ceux déjà en cache sont ignorés
    private function warmAcceptabilite(): void
    {
        $apiUrl = config('redcap.api_url');
        $token  = config('redcap.token');
        $t30    = now()->addMinutes(30);

        $tasks = [
            'identification' => [
                'redcap.identification',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1',
                 'forms'=>'form_1a_identification','exportDataAccessGroups'=>'true',
                 'fields'=>implode(',',['household_id','village','cluster_djigbe','cluster_gbonou',
                     'cluster_miniffi','cluster_id','bras','study_cohort','numero_menage','consent_accepted'])],
            ],
            'form1b' => [
                'redcap.form1b',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1',
                 'forms'=>'form_1b_questionnaire_base_menage',
                 'fields'=>implode(',',['household_id','b_hhh_fullname','b_hhh_phone',
                     'b_hh_point_contact','b_hh_point_contact_phone','b_hh_moment_visite','b_hh_address'])],
            ],
            'ua' => [
                'redcap.ua.user_acceptability_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'user_acceptability_arm_1',
                 'forms'=>'form_6_user_acceptability',
                 'fields'=>implode(',',['household_id','net_like','net_overall_satisfaction',
                     'net_continue_use','net_recommend','net_easy_use','net_size_adequate','net_design_importance'])],
            ],
            'ae' => [
                'redcap.adverse_events',
                ['content'=>'record','type'=>'flat','events'=>'adverse_events_arm_1',
                 'forms'=>'form_5a_informations_gnriques_adverse_events,form_5_adverse_avents',
                 'fields'=>implode(',',['household_id','ad_evt_date','bedbugs_community_problem',
                     'bedbugs_current_house','bb_seen_on_net_ever','bb_stop_net_use_ever',
                     'ae_any_symptom','itn_continue_use'])],
            ],
        ];

        $cold = array_filter($tasks, fn($t) => !\Illuminate\Support\Facades\Cache::has($t[0]));
        if (empty($cold)) return;

        $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($cold, $apiUrl, $token) {
            $reqs = [];
            foreach ($cold as $key => [$cacheKey, $params]) {
                $reqs[] = $pool->as($key)
                    ->withoutVerifying()->timeout(120)->asForm()
                    ->post($apiUrl, array_merge(['token' => $token, 'format' => 'json'], $params));
            }
            return $reqs;
        });

        foreach ($cold as $key => [$cacheKey, $params]) {
            $r = $responses[$key] ?? null;
            if ($r && !($r instanceof \Throwable) && $r->successful()) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $r->json(), $t30);
            }
        }
    }

    private function inferArm(array $row): string
    {
        $bras = $row['bras'] ?? '';
        if ($bras === '1') return 'PD';
        if ($bras === '2') return 'G2';

        $parts = explode('-', $row['household_id'] ?? '');
        if (count($parts) >= 3) {
            $code = strtoupper($parts[2]);
            if ($code === 'PD') return 'PD';
            if ($code === 'G2') return 'G2';
        }
        return '';
    }
}
