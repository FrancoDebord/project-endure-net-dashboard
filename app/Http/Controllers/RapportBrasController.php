<?php

namespace App\Http\Controllers;

use App\Services\RedCapService;
use Illuminate\View\View;

class RapportBrasController extends Controller
{
    // Cibles protocole par cohorte (identiques pour les deux bras)
    private const TARGETS = [
        '1' => ['hh' => 611, 'label' => 'Cohorte A'],
        '2' => ['hh' => 310, 'label' => 'Cohorte B'],
    ];
    private const NETS_PER_HH   = 3;
    private const TARGET_HH_ARM = 921; // 611 + 310

    private const BRAS_LABELS = ['1' => 'PERMANET DUAL', '2' => 'INTERCEPTOR G2'];
    private const BRAS_COLORS = ['1' => 'pmd',           '2' => 'ig2'];

    private const BRAS_FROM_CLUSTER = [
        '1' => '1', '2' => '2', '3' => '2',
        '4' => '2', '5' => '1', '6' => '2',
        '7' => '1', '8' => '1',
    ];
    private const CLUSTER_FROM_ID = [
        'DJ' => ['01' => '1',  '02' => '2'],
        'GB' => ['01' => '3',  '02' => '4',  '03' => '5'],
        'MI' => ['01' => '6',  '02' => '7',  '03' => '8', '04' => '9'],
        'DO' => ['01' => '10'],
        'AG' => ['01' => '11'],
        'AK' => ['01' => '12'],
    ];

    public function __construct(private RedCapService $redcap) {}

    public function index(): View
    {
        ['arms' => $arms, 'global' => $global] = $this->buildData();
        return view('rapport-bras', compact('arms', 'global'));
    }

    public function pdf(): \Illuminate\Http\Response
    {
        ['arms' => $arms, 'global' => $global] = $this->buildData();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rapport-bras-pdf', compact('arms', 'global'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('rapport-bras-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildData(): array
    {
        set_time_limit(120);
        $this->redcap->warmAll();

        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $studyNetRaw = $this->redcap->getStudyNetData();

        // ── Consentement ────────────────────────────────────────────────────
        $idMap = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->keyBy('household_id');

        $qbConsentMap = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('consent_accepted', 'household_id');

        $consented = fn(string $id): bool =>
            (($idMap->get($id, [])['consent_accepted'] ?? '') ?: ($qbConsentMap->get($id, ''))) === '1';

        // ── Moustiquaires par ménage ─────────────────────────────────────────
        $netsByHh = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'section_5_moustiquaires_imprgnes_dinsecticide_appa' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')
            ->map->count();

        // ── Ménages enrôlés avec bras et cohorte ────────────────────────────
        $enrolled = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->filter(fn($r) => $consented($r['household_id']))
            ->map(fn($r) => array_merge($r, [
                'bras'   => $this->resolveBras($r),
                'nets'   => $netsByHh->get($r['household_id'], 0),
            ]))
            ->filter(fn($r) => $r['bras'] !== '');

        // ── Agrégation bras × cohorte ────────────────────────────────────────
        $arms = [];
        foreach (['1', '2'] as $brasKey) {
            $brasHh = $enrolled->filter(fn($r) => $r['bras'] === $brasKey);

            $cohorts = [];
            foreach (['1', '2'] as $cohortKey) {
                $g          = $brasHh->filter(fn($r) => ($r['study_cohort'] ?? '') === $cohortKey);
                $targetHh   = self::TARGETS[$cohortKey]['hh'];
                $targetNets = $targetHh * self::NETS_PER_HH;
                $hhCount    = $g->count();
                $netsCount  = $g->sum('nets');

                $cohorts[$cohortKey] = [
                    'label'       => self::TARGETS[$cohortKey]['label'],
                    'hh'          => $hhCount,
                    'nets'        => $netsCount,
                    'target_hh'   => $targetHh,
                    'target_nets' => $targetNets,
                    'rate_hh'     => $targetHh  > 0 ? round($hhCount   / $targetHh   * 100, 1) : 0,
                    'rate_nets'   => $targetNets > 0 ? round($netsCount / $targetNets * 100, 1) : 0,
                    'gap_hh'      => max(0, $targetHh   - $hhCount),
                    'gap_nets'    => max(0, $targetNets  - $netsCount),
                ];
            }

            $totalHh    = $brasHh->count();
            $totalNets  = $brasHh->sum('nets');
            $tHh        = self::TARGET_HH_ARM;
            $tNets      = $tHh * self::NETS_PER_HH;

            $arms[$brasKey] = [
                'label'       => self::BRAS_LABELS[$brasKey],
                'color'       => self::BRAS_COLORS[$brasKey],
                'hh'          => $totalHh,
                'nets'        => $totalNets,
                'target_hh'   => $tHh,
                'target_nets' => $tNets,
                'rate_hh'     => $tHh   > 0 ? round($totalHh   / $tHh   * 100, 1) : 0,
                'rate_nets'   => $tNets > 0 ? round($totalNets / $tNets * 100, 1) : 0,
                'gap_hh'      => max(0, $tHh   - $totalHh),
                'gap_nets'    => max(0, $tNets  - $totalNets),
                'cohorts'     => $cohorts,
            ];
        }

        // ── Global ──────────────────────────────────────────────────────────
        $global = [
            'hh'          => $enrolled->count(),
            'nets'        => $enrolled->sum('nets'),
            'target_hh'   => self::TARGET_HH_ARM * 2,
            'target_nets' => self::TARGET_HH_ARM * 2 * self::NETS_PER_HH,
        ];
        $global['rate_hh']   = $global['target_hh']   > 0 ? round($global['hh']   / $global['target_hh']   * 100, 1) : 0;
        $global['rate_nets'] = $global['target_nets']  > 0 ? round($global['nets'] / $global['target_nets'] * 100, 1) : 0;
        $global['gap_hh']    = max(0, $global['target_hh']   - $global['hh']);
        $global['gap_nets']  = max(0, $global['target_nets']  - $global['nets']);

        return compact('arms', 'global');
    }

    private function resolveBras(array $hh): string
    {
        $cluster = ($hh['cluster_djigbe']  ?? '')
                ?: ($hh['cluster_gbonou']  ?? '')
                ?: ($hh['cluster_miniffi'] ?? '');

        if (isset(self::BRAS_FROM_CLUSTER[$cluster])) {
            return self::BRAS_FROM_CLUSTER[$cluster];
        }

        // Inférence depuis l'ID ménage (format VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE)
        $parts = explode('-', $hh['household_id'] ?? '');
        if (count($parts) === 5) {
            $brasCode = strtoupper($parts[2]);
            if ($brasCode === 'PD') return '1';
            if ($brasCode === 'G2') return '2';
        }
        return '';
    }
}
