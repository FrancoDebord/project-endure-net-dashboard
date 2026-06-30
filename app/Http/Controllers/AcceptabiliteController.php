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
        'AD' => 'Agbondjedo',
    ];

    private const CLUSTER_DISPLAY = [
        '1'  => 'Djigbe 1',     '2'  => 'Djigbe 2',
        '3'  => 'Gbonou 1',     '4'  => 'Gbonou 2',     '5'  => 'Gbonou 3',
        '6'  => 'Miniffi 1',    '7'  => 'Miniffi 2',    '8'  => 'Miniffi 3',
        '9'  => 'Miniffi 4',
        '10' => 'Dogbo 1',  '11' => 'Agao 1',  '12' => 'Akoba 1',
        '13' => 'Agbondjedo 2',
    ];

    private const CLUSTER_LABELS = [
        '1'  => 'Djigbe 1',      '2'  => 'Djigbe 2',
        '3'  => 'Gbonou 1',      '4'  => 'Gbonou 2',      '5'  => 'Gbonou 3',
        '6'  => 'Miniffi 1',     '7'  => 'Miniffi 2',     '8'  => 'Miniffi 3',
        '9'  => 'Miniffi 4',
        '10' => 'Dogbo 1',       '11' => 'Agao 1',        '12' => 'Akoba 1',
        '13' => 'Agbondjedo 2',
    ];

    public function __construct(private RedCapService $redcap) {}

    // ── GET /acceptabilite ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        set_time_limit(300);
        $fullPool = $this->buildMergedPool();
        $stats    = $this->computeStats($fullPool);

        // Cluster options depuis le pool fusionné
        $clusterOptions = $fullPool
            ->pluck('cluster_id')->filter()->unique()->sort()->values();

        // Filtres
        $pool = $fullPool;
        if ($request->filled('arm'))     $pool = $pool->filter(fn($h) => $h['arm'] === $request->arm)->values();
        if ($request->filled('cluster')) $pool = $pool->filter(fn($h) => (string)($h['cluster_id'] ?? '') === (string)$request->cluster)->values();
        if ($request->filled('ua_status')) {
            // UA status → ne conserve que les ménages acceptabilité (AE-only exclus)
            $pool = match($request->ua_status) {
                'visited' => $pool->filter(fn($h) =>  $h['in_acceptabilite'] &&  $h['ua_visited'] && !$h['ua_absent'])->values(),
                'pending' => $pool->filter(fn($h) =>  $h['in_acceptabilite'] && !$h['ua_visited'])->values(),
                'absent'  => $pool->filter(fn($h) =>  $h['in_acceptabilite'] &&  $h['ua_absent'])->values(),
                default   => $pool,
            };
        }
        if ($request->filled('ae_status')) {
            // AE status → ne conserve que les ménages AE (acceptabilité-only exclus)
            $pool = match($request->ae_status) {
                'selected' => $pool->filter(fn($h) =>  $h['in_ae'])->values(),
                'visited'  => $pool->filter(fn($h) =>  $h['in_ae'] &&  $h['ae_visited'] && !$h['ae_absent'])->values(),
                'pending'  => $pool->filter(fn($h) =>  $h['in_ae'] && !$h['ae_visited'])->values(),
                'absent'   => $pool->filter(fn($h) =>  $h['in_ae'] &&  $h['ae_absent'])->values(),
                'none'     => $pool->filter(fn($h) => !$h['in_ae'])->values(),
                default    => $pool,
            };
        }
        if ($request->filled('search')) {
            $q = strtolower($request->search);
            $pool = $pool->filter(fn($h) =>
                str_contains(strtolower($h['household_id'] ?? ''), $q) ||
                str_contains(strtolower($h['head_name']    ?? ''), $q) ||
                str_contains(strtolower($h['contact_name'] ?? ''), $q)
            )->values();
        }
        if ($request->filled('tablet')) {
            $tab = str_pad($request->tablet, 2, '0', STR_PAD_LEFT);
            $pool = $pool->filter(fn($h) => (explode('-', $h['household_id'] ?? '')[3] ?? '') === $tab)->values();
        }
        if ($request->filled('visit_status')) {
            $pool = match($request->visit_status) {
                'pending' => $pool->filter(fn($h) =>
                    ($h['in_acceptabilite'] && !$h['ua_visited']) ||
                    ($h['in_ae'] && !$h['ae_visited'])
                )->values(),
                'done' => $pool->filter(fn($h) =>
                    (!$h['in_acceptabilite'] || $h['ua_visited']) &&
                    (!$h['in_ae'] || $h['ae_visited'])
                )->values(),
                'absent' => $pool->filter(fn($h) =>
                    ($h['in_acceptabilite'] && $h['ua_absent']) ||
                    ($h['in_ae'] && $h['ae_absent'])
                )->values(),
                default => $pool,
            };
        }
        if ($request->filled('household_type')) {
            $pool = match($request->household_type) {
                'ua'      => $pool->filter(fn($h) =>  $h['in_acceptabilite'])->values(),
                'ae'      => $pool->filter(fn($h) =>  $h['in_ae'])->values(),
                'ua_ae'   => $pool->filter(fn($h) =>  $h['in_acceptabilite'] &&  $h['in_ae'])->values(),
                'ua_only' => $pool->filter(fn($h) =>  $h['in_acceptabilite'] && !$h['in_ae'])->values(),
                'ae_only' => $pool->filter(fn($h) => !$h['in_acceptabilite'] &&  $h['in_ae'])->values(),
                default   => $pool,
            };
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
        // buildSymptomsByArm ne concerne que les ménages Cohorte A (UA survey)
        $symptomStats   = $this->buildSymptomsByArm(
            $fullPool->filter(fn($h) => $h['in_acceptabilite'])->values()
        );

        return view('acceptabilite', compact('households', 'stats', 'clusterDisplay', 'clusterOptions', 'symptomStats'));
    }

    // ── GET /acceptabilite/pdf ─────────────────────────────────────────────────
    public function pdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(600);

        $pool = $this->buildMergedPool();

        $arm     = $request->input('arm');
        $cluster = $request->input('cluster');

        if ($arm)     $pool = $pool->filter(fn($h) => $h['arm'] === $arm)->values();
        if ($cluster) $pool = $pool->filter(fn($h) => (string)($h['cluster_id'] ?? '') === (string)$cluster)->values();

        if ($request->filled('ua_status')) {
            $pool = match($request->ua_status) {
                'visited' => $pool->filter(fn($h) =>  $h['in_acceptabilite'] &&  $h['ua_visited'] && !$h['ua_absent'])->values(),
                'pending' => $pool->filter(fn($h) =>  $h['in_acceptabilite'] && !$h['ua_visited'])->values(),
                'absent'  => $pool->filter(fn($h) =>  $h['in_acceptabilite'] &&  $h['ua_absent'])->values(),
                default   => $pool,
            };
        }
        if ($request->filled('ae_status')) {
            $pool = match($request->ae_status) {
                'selected' => $pool->filter(fn($h) =>  $h['in_ae'])->values(),
                'visited'  => $pool->filter(fn($h) =>  $h['in_ae'] &&  $h['ae_visited'] && !$h['ae_absent'])->values(),
                'pending'  => $pool->filter(fn($h) =>  $h['in_ae'] && !$h['ae_visited'])->values(),
                'absent'   => $pool->filter(fn($h) =>  $h['in_ae'] &&  $h['ae_absent'])->values(),
                'none'     => $pool->filter(fn($h) => !$h['in_ae'])->values(),
                default    => $pool,
            };
        }
        if ($request->filled('search')) {
            $q = strtolower($request->search);
            $pool = $pool->filter(fn($h) =>
                str_contains(strtolower($h['household_id'] ?? ''), $q) ||
                str_contains(strtolower($h['head_name']    ?? ''), $q) ||
                str_contains(strtolower($h['contact_name'] ?? ''), $q)
            )->values();
        }
        if ($request->filled('tablet')) {
            $tab = str_pad($request->tablet, 2, '0', STR_PAD_LEFT);
            $pool = $pool->filter(fn($h) => (explode('-', $h['household_id'] ?? '')[3] ?? '') === $tab)->values();
        }
        if ($request->filled('visit_status')) {
            $pool = match($request->visit_status) {
                'pending' => $pool->filter(fn($h) =>
                    ($h['in_acceptabilite'] && !$h['ua_visited']) ||
                    ($h['in_ae'] && !$h['ae_visited'])
                )->values(),
                'done' => $pool->filter(fn($h) =>
                    (!$h['in_acceptabilite'] || $h['ua_visited']) &&
                    (!$h['in_ae'] || $h['ae_visited'])
                )->values(),
                'absent' => $pool->filter(fn($h) =>
                    ($h['in_acceptabilite'] && $h['ua_absent']) ||
                    ($h['in_ae'] && $h['ae_absent'])
                )->values(),
                default => $pool,
            };
        }
        if ($request->filled('household_type')) {
            $pool = match($request->household_type) {
                'ua'      => $pool->filter(fn($h) =>  $h['in_acceptabilite'])->values(),
                'ae'      => $pool->filter(fn($h) =>  $h['in_ae'])->values(),
                'ua_ae'   => $pool->filter(fn($h) =>  $h['in_acceptabilite'] &&  $h['in_ae'])->values(),
                'ua_only' => $pool->filter(fn($h) =>  $h['in_acceptabilite'] && !$h['in_ae'])->values(),
                'ae_only' => $pool->filter(fn($h) => !$h['in_acceptabilite'] &&  $h['in_ae'])->values(),
                default   => $pool,
            };
        }

        $filters = $request->only(['arm', 'cluster', 'ua_status', 'ae_status', 'visit_status', 'household_type', 'search', 'tablet']);
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
                    'now', 'arm', 'cluster', 'clusterLabel', 'filters')
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

    // ── GET /acceptabilite/rapport-journalier ─────────────────────────────────
    // UA  : ac_tablet · ac_date_interview · ac_hh_present
    // AE  : ae_tablette_id · ad_evt_date  · ae_hh_present
    public function rapportJournalierPdf(Request $request): \Illuminate\Http\Response
    {
        set_time_limit(600);

        $dateDebut = $request->input('date_debut') ?: null;
        $dateFin   = $request->input('date_fin')   ?: null;

        // Chauffe le cache (même endpoint que buildPool)
        $this->warmAcceptabilite();

        // ── User Acceptability ────────────────────────────────────────────────
        $uaByDate = [];
        foreach ($this->redcap->getUserAcceptabilityData() as $r) {
            $date   = $r['ac_date_interview'] ?? '';
            $tablet = trim($r['ac_tablet']        ?? '');

            // Ignorer si date ou tablette manquante / vide / égale à "0"
            if ($date === '' || $tablet === '' || $tablet === '0') continue;
            if ($dateDebut && $date < $dateDebut) continue;
            if ($dateFin   && $date > $dateFin)   continue;

            $tabKey = str_pad($tablet, 2, '0', STR_PAD_LEFT);

            $uaByDate[$date][$tabKey] ??= ['visited' => 0, 'present' => 0, 'absent' => 0];
            $uaByDate[$date][$tabKey]['visited']++;
            if (($r['ac_hh_present'] ?? '') === '1') $uaByDate[$date][$tabKey]['present']++;
            if (($r['ac_hh_present'] ?? '') === '0') $uaByDate[$date][$tabKey]['absent']++;
        }

        // ── Adverse Events (enregistrements non-répétants uniquement) ─────────
        $aeByDate = [];
        foreach ($this->redcap->getAdverseEventsData() as $r) {
            if (($r['redcap_repeat_instrument'] ?? '') !== '') continue;

            $date   = $r['ad_evt_date']      ?? '';
            $tablet = trim($r['ae_tablette_id'] ?? '');

            if ($date === '' || $tablet === '' || $tablet === '0') continue;
            if ($dateDebut && $date < $dateDebut) continue;
            if ($dateFin   && $date > $dateFin)   continue;

            $tabKey = str_pad($tablet, 2, '0', STR_PAD_LEFT);

            $aeByDate[$date][$tabKey] ??= ['visited' => 0, 'present' => 0, 'absent' => 0];
            $aeByDate[$date][$tabKey]['visited']++;
            if (($r['ae_hh_present'] ?? '') === '1') $aeByDate[$date][$tabKey]['present']++;
            if (($r['ae_hh_present'] ?? '') === '0') $aeByDate[$date][$tabKey]['absent']++;
        }

        // ── Fusion et tri ─────────────────────────────────────────────────────
        $allDates = array_unique(array_merge(array_keys($uaByDate), array_keys($aeByDate)));
        sort($allDates);

        $reportData = [];
        foreach ($allDates as $date) {
            $tablets = array_unique(array_merge(
                array_keys($uaByDate[$date] ?? []),
                array_keys($aeByDate[$date] ?? [])
            ));
            sort($tablets);

            $rows = [];
            foreach ($tablets as $tab) {
                $rows[] = [
                    'tablet' => $tab,
                    'ua'     => $uaByDate[$date][$tab] ?? ['visited' => 0, 'present' => 0, 'absent' => 0],
                    'ae'     => $aeByDate[$date][$tab] ?? ['visited' => 0, 'present' => 0, 'absent' => 0],
                ];
            }
            $reportData[$date] = $rows;
        }

        $now = now()->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'acceptabilite-rapport-pdf',
            compact('reportData', 'now', 'dateDebut', 'dateFin')
        );
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
            'isPhpEnabled'         => false,
            'defaultMediaType'     => 'print',
            'dpi'                  => 96,
            'defaultFont'          => 'dejavu sans',
            'enable_php'           => false,
        ]);

        $slug = ($dateDebut ?? 'debut') . '_' . ($dateFin ?? 'fin');
        return $pdf->download("rapport-journalier-ua-ae-{$slug}.pdf");
    }

    // ── Stats ──────────────────────────────────────────────────────────────────
    private function computeStats(Collection $pool): array
    {
        $uaPool = $pool->filter(fn($h) => $h['in_acceptabilite'] ?? false);
        $aePool = $pool->filter(fn($h) => $h['in_ae'] ?? false);

        $uaTotal   = $uaPool->count();
        $uaDone    = $uaPool->where('ua_visited', true)->count();
        $uaPresent = $uaPool->where('ua_present', true)->count();
        $uaAbsent  = $uaPool->where('ua_absent', true)->count();

        $aeTotal   = $aePool->count();
        $aeDone    = $aePool->where('ae_visited', true)->count();
        $aePresent = $aePool->where('ae_present', true)->count();
        $aeAbsent  = $aePool->where('ae_absent', true)->count();

        return [
            'ua_total'   => $uaTotal,
            'ua_visited' => $uaDone,
            'ua_pending' => $uaTotal - $uaDone,
            'ua_rate'    => $uaTotal > 0 ? round($uaDone / $uaTotal * 100, 1) : 0,
            'ua_present' => $uaPresent,
            'ua_absent'  => $uaAbsent,
            'ae_total'   => $aeTotal,
            'ae_visited' => $aeDone,
            'ae_pending' => $aeTotal - $aeDone,
            'ae_rate'    => $aeTotal > 0 ? round($aeDone / $aeTotal * 100, 1) : 0,
            'ae_present' => $aePresent,
            'ae_absent'  => $aeAbsent,
        ];
    }

    // ── Pool fusionné : Cohorte A (UA) + AE sélectionnés ─────────────────────
    private function buildMergedPool(): Collection
    {
        $redcapPool = $this->buildPool();
        $redcapIds  = $redcapPool->pluck('household_id')->flip();

        // Annoter chaque ménage REDCap avec ses flags source
        $merged = $redcapPool->map(fn($hh) => array_merge($hh, [
            'in_acceptabilite' => true,
            'in_ae'            => $hh['is_ae_selected'],
        ]));

        // Ménages AE qui ne sont PAS dans la Cohorte A
        $aeAll  = AeSelectedHousehold::all();
        $aeOnly = $aeAll->reject(fn($ae) => $redcapIds->has($ae->household_id));

        if ($aeOnly->isNotEmpty()) {
            // Données AE REDCap (déjà en cache via warmAcceptabilite)
            $aeData    = collect($this->redcap->getAdverseEventsData());
            $aeVisited = $aeData
                ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
                ->pluck('household_id')->flip();
            $aePresent = $aeData
                ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '' && ($r['ae_hh_present'] ?? '') === '1')
                ->pluck('household_id')->flip();
            $aeAbsent  = $aeData
                ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '' && ($r['ae_hh_present'] ?? '') === '0')
                ->pluck('household_id')->flip();

            $aeOnlyRecords = $aeOnly->map(fn($ae) => [
                'household_id'     => $ae->household_id,
                'arm'              => $ae->arm,
                'study_cohort'     => $ae->study_cohort ?? null,
                'village'          => $ae->village,
                'village_label'    => self::VILLAGE_LABELS[$ae->village] ?? ($ae->village ?? '—'),
                'cluster_id'       => $ae->cluster_id,
                'head_name'        => $ae->head_name ?? null,
                'head_phone'       => null,
                'contact_name'     => $ae->contact_name ?? null,
                'contact_phone'    => null,
                'moment_visite'    => $ae->best_time ?? null,
                'adresse'          => $ae->visit_address ?? null,
                'ua_visited'       => false,
                'ua_present'       => false,
                'ua_absent'        => false,
                'is_ae_selected'   => true,
                'ae_visited'       => $aeVisited->has($ae->household_id),
                'ae_present'       => $aePresent->has($ae->household_id),
                'ae_absent'        => $aeAbsent->has($ae->household_id),
                'in_acceptabilite' => false,
                'in_ae'            => true,
            ]);

            $merged = $merged->concat($aeOnlyRecords);
        }

        return $merged->sortBy(['cluster_id', 'arm', 'household_id'])->values();
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

        // UA présent (ac_hh_present = 1) / absent (= 0)
        $uaPresent = $uaData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['ac_hh_present'] ?? '') === '1')
            ->pluck('household_id')->flip();
        $uaAbsent = $uaData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['ac_hh_present'] ?? '') === '0')
            ->pluck('household_id')->flip();

        // Un enregistrement AE non-répétant = visité
        $aeVisited = $aeData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('household_id')
            ->flip();

        // AE présent (ae_hh_present = 1) / absent (= 0) — sur l'enregistrement non-répétant
        $aePresent = $aeData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '' && ($r['ae_hh_present'] ?? '') === '1')
            ->pluck('household_id')->flip();
        $aeAbsent = $aeData
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '' && ($r['ae_hh_present'] ?? '') === '0')
            ->pluck('household_id')->flip();

        // Ménages sélectionnés AE depuis la DB
        $aeSelectedIds = AeSelectedHousehold::pluck('household_id')->flip();

        return $idData
            ->filter(fn($r) =>
                ($r['household_id'] ?? '') !== '' &&
                ($r['consent_accepted'] ?? '') === '1' &&
                ($r['study_cohort']    ?? '') === '1'
            )
            ->unique('household_id')
            ->map(function ($r) use ($form1bMap, $uaVisited, $uaPresent, $uaAbsent, $aeVisited, $aePresent, $aeAbsent, $aeSelectedIds) {
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
                    'ua_present'     => $uaPresent->has($id),
                    'ua_absent'      => $uaAbsent->has($id),
                    'is_ae_selected' => $isAe,
                    'ae_visited'     => $isAe && $aeVisited->has($id),
                    'ae_present'     => $isAe && $aePresent->has($id),
                    'ae_absent'      => $isAe && $aeAbsent->has($id),
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
                 'fields'=>implode(',',['household_id','ac_tablet','ac_date_interview','ac_hh_present',
                     'net_like','net_overall_satisfaction',
                     'net_continue_use','net_recommend','net_easy_use','net_size_adequate','net_design_importance',
                     'net_like_reason___1','net_like_reason___2','net_like_reason___3',
                     'net_like_reason___4','net_like_reason___5','net_like_reason___6',
                     'net_like_reason___7','net_like_reason___8','net_like_reason___99',
                     'net_dislike_reason___1','net_dislike_reason___2','net_dislike_reason___3',
                     'net_dislike_reason___4','net_dislike_reason___5','net_dislike_reason___6',
                     'net_dislike_reason___7','net_dislike_reason___8','net_dislike_reason___9',
                     'net_dislike_reason___10','net_dislike_reason___11','net_dislike_reason___12',
                     'net_dislike_reason___13','net_dislike_reason___99',
                     'net_continue_no_reason'])],
            ],
            'ae' => [
                'redcap.adverse_events',
                ['content'=>'record','type'=>'flat','events'=>'adverse_events_arm_1',
                 'forms'=>'form_5a_informations_gnriques_adverse_events,form_5_adverse_avents',
                 'fields'=>implode(',',['household_id','ad_evt_date','ae_tablette_id',
                     'ae_hh_present',
                     'ad_evt_respondent',
                     'ae_itn_n','ae_itn_present_n',
                     'bedbugs_community_problem','bedbugs_current_house',
                     'bedbug_seen_first_use','bb_net_old','bb_bed_frame',
                     'bb_bed_linen','bb_mattress','bb_clothing',
                     'bb_walls_floor','bb_luggage','bb_other_specify',
                     'bb_net_eff_att',
                     'bb_seen_on_net_ever','bb_stop_net_use_ever',
                     'bb_net_action',
                     'ae_net_users_n',
                     'ae_any_symptom',
                     'ae_itch_adult','ae_itch_adult_age',
                     'ae_faceburn_adult','ae_faceburn_adult_age',
                     'ae_sneeze_adult','ae_sneeze_adult_age',
                     'ae_rhinorrhea_adult','ae_rhinorrhea_adult_age',
                     'ae_headache_adult','ae_headache_adult_age',
                     'ae_nausea_adult','ae_nausea_adult_age',
                     'ae_eyeirrit_adult','ae_eyeirrit_adult_age',
                     'ae_tearing_adult','ae_tearing_adult_age',
                     'ae_badsmell_adult','ae_badsmell_adult_age',
                     'ae_other_symptom',
                     'itn_continue_use','itn_nonuse_reason',
                     'ae_reported_physician'])],
            ],
        ];

        $cold = array_filter($tasks, fn($t) => !\Illuminate\Support\Facades\Cache::has($t[0]));
        if (empty($cold)) return;

        $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($cold, $apiUrl, $token) {
            $reqs = [];
            foreach ($cold as $key => [, $params]) {
                $reqs[] = $pool->as($key)
                    ->withoutVerifying()->timeout(120)->asForm()
                    ->post($apiUrl, array_merge(['token' => $token, 'format' => 'json'], $params));
            }
            return $reqs;
        });

        foreach ($cold as $key => [$cacheKey]) {
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

    // Symptômes AE par bras et groupe d'âge (adultes / enfants)
    private function buildSymptomsByArm(Collection $pool): array
    {
        $aeData    = collect($this->redcap->getAdverseEventsData());
        $armByHh   = $pool->pluck('arm', 'household_id');

        // Uniquement les enregistrements répétants form_5b (par moustiquaire)
        $form5b = $aeData->filter(fn($r) =>
            ($r['household_id'] ?? '') !== '' &&
            ($r['redcap_repeat_instrument'] ?? '') === 'form_5_adverse_avents'
        );

        $symptomDefs = [
            ['key' => 'itch',       'label' => 'Démangeaisons'],
            ['key' => 'faceburn',   'label' => 'Brûlures visage'],
            ['key' => 'sneeze',     'label' => 'Éternuements'],
            ['key' => 'rhinorrhea', 'label' => 'Rhinorrhée'],
            ['key' => 'headache',   'label' => 'Maux de tête'],
            ['key' => 'nausea',     'label' => 'Nausées'],
            ['key' => 'eyeirrit',   'label' => 'Irritation yeux'],
            ['key' => 'tearing',    'label' => 'Larmoiements'],
            ['key' => 'badsmell',   'label' => 'Mauvaise odeur'],
        ];

        $result = [];
        foreach (['PD' => 'PERMANET DUAL', 'G2' => 'INTERCEPTOR G2'] as $arm => $label) {
            $recs = $form5b->filter(fn($r) => $armByHh->get($r['household_id']) === $arm);
            $totalWithSymptoms = $recs->where('ae_any_symptom', '1')->unique('household_id')->count();

            $symptoms = [];
            foreach ($symptomDefs as $def) {
                $adultField = 'ae_' . $def['key'] . '_adult';
                $childField = 'ae_' . $def['key'] . '_child';
                $adult = $recs->where($adultField, '1')->unique('household_id')->count();
                $child = $recs->where($childField, '1')->unique('household_id')->count();
                if ($adult > 0 || $child > 0) {
                    $symptoms[] = [
                        'label' => $def['label'],
                        'adult' => $adult,
                        'child' => $child,
                    ];
                }
            }

            usort($symptoms, fn($a, $b) => ($b['adult'] + $b['child']) - ($a['adult'] + $a['child']));

            $result[$arm] = [
                'label'               => $label,
                'total_with_symptoms' => $totalWithSymptoms,
                'total_records'       => $recs->unique('household_id')->count(),
                'symptoms'            => $symptoms,
            ];
        }

        return $result;
    }
}
