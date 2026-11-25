<?php

namespace App\Http\Controllers;

use App\Models\CbSelectedHousehold;
use App\Models\CbSelectionDraw;
use App\Services\CohortBFollowUpService;
use App\Services\CohortBNetSelectionService;
use App\Services\RedCapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Sélection aléatoire des ménages Cohorte B pour le prélèvement des moustiquaires
 * (cible par bras et par suivi), complément et libération des ménages restants.
 */
class CohortBSelectionController extends Controller
{
    public function __construct(private CohortBNetSelectionService $selection) {}

    private function validTp(?string $tp): string
    {
        return array_key_exists((string) $tp, CohortBFollowUpService::FU_B_LABELS) ? $tp : array_key_first(CohortBFollowUpService::FU_B_LABELS);
    }

    private function back(string $tp): RedirectResponse
    {
        return redirect()->route('cb.selection.index', ['suivi' => $tp]);
    }

    /** GET /cohorte-b/selection?suivi=suivi6 */
    public function index(Request $request): View|RedirectResponse
    {
        set_time_limit(300);
        $tp = $this->validTp($request->query('suivi'));

        if ($request->boolean('refresh')) {
            // Recharger les Form 10 depuis REDCap pour mettre à jour les moustiquaires prélevées
            Cache::store('file')->forget(CohortBFollowUpService::COMPUTED_CACHE_KEY);
            foreach (RedCapService::COHORT_B_EVENTS as $event) {
                Cache::forget("redcap.fu_b.$event");
            }
            return $this->back($tp);
        }

        return view('cohort-b-selection', [
            'tp'           => $tp,
            'suivis'       => CohortBFollowUpService::FU_B_LABELS,
            'arms'         => $this->selection->state($tp),
            'activeCounts' => CohortBNetSelectionService::activeCounts(),
            'defaultTarget'=> CohortBNetSelectionService::DEFAULT_TARGET,
        ]);
    }

    /** GET /cohorte-b/selection/print?suivi=suivi6&arm=1 — liste imprimable pour les équipes */
    public function print(Request $request): View
    {
        set_time_limit(300);
        $tp   = $this->validTp($request->query('suivi'));
        $arms = collect($this->selection->state($tp))
            ->when($request->filled('arm'), fn($c) => $c->only([$request->query('arm')]))
            ->filter(fn($a) => $a['state'] !== null && count($a['state']['rows']) > 0);

        return view('cohort-b-selection-print', [
            'suiviLabel' => CohortBFollowUpService::FU_B_LABELS[$tp],
            'arms'       => $arms,
            'onlyPending'=> $request->query('only') === 'pending',
            'members'    => \App\Services\CohortHouseholds::baselineMembers(app(RedCapService::class)->getMemberData()),
            // vt = « village|tablette » (les n° de tablette se répètent d'un village à l'autre)
            'village'    => (string) (explode('|', (string) $request->query('vt', ''), 2)[0] ?? ''),
            'tablet'     => (string) (explode('|', (string) $request->query('vt', ''), 2)[1] ?? $request->query('tablet', '')),
        ]);
    }

    /** POST — tirage initial */
    public function draw(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'suivi'   => 'required|in:' . implode(',', array_keys(CohortBFollowUpService::FU_B_LABELS)),
            'arm'     => 'nullable|in:1,2',
            'target'  => 'required|integer|min:1|max:500',
            'reserve' => 'nullable|integer|min:0|max:50',
            'spread'  => 'nullable|boolean',
            'seed'    => 'nullable|integer|min:1|max:' . CohortBNetSelectionService::SEED_MAX,
        ]);

        $msgs = [];
        foreach ($v['arm'] ? [$v['arm']] : array_keys(CohortBNetSelectionService::ARMS) as $arm) {
            $label = CohortBNetSelectionService::ARMS[$arm];
            try {
                $r = $this->selection->draw($v['suivi'], $arm, (int) $v['target'], (int) ($v['reserve'] ?? 0), $request->boolean('spread'),
                    isset($v['seed']) ? (int) $v['seed'] : null);
                $msgs[] = "{$label} (graine {$r['seed']}) : {$r['households']} ménage(s) tiré(s) parmi {$r['poolSize']} éligibles, {$r['nets']} moustiquaire(s) à prélever pour une cible de {$v['target']} (toutes les moustiquaires de chaque ménage)"
                        . ($r['nets'] < (int) $v['target'] ? ' — ⚠ pas assez de ménages éligibles pour atteindre la cible' : '');
            } catch (\RuntimeException $e) {
                $msgs[] = "{$label} : " . $e->getMessage();
            }
        }
        return $this->back($v['suivi'])->with('success', implode("\n", $msgs));
    }

    /** POST — compléter la sélection d'un bras (déficit de moustiquaires) */
    public function complete(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'suivi'   => 'required|in:' . implode(',', array_keys(CohortBFollowUpService::FU_B_LABELS)),
            'arm'     => 'required|in:1,2',
            'reserve' => 'nullable|integer|min:0|max:50',
            'seed'    => 'nullable|integer|min:1|max:' . CohortBNetSelectionService::SEED_MAX,
        ]);
        $label = CohortBNetSelectionService::ARMS[$v['arm']];
        try {
            $r = $this->selection->complete($v['suivi'], $v['arm'], (int) ($v['reserve'] ?? 0), isset($v['seed']) ? (int) $v['seed'] : null);
        } catch (\RuntimeException $e) {
            return $this->back($v['suivi'])->with('error', "{$label} : " . $e->getMessage());
        }
        if ($r['households'] === 0) {
            return $this->back($v['suivi'])->with('error', "{$label} : aucun ménage éligible restant pour compléter.");
        }
        return $this->back($v['suivi'])->with('success',
            "{$label} (graine {$r['seed']}) : déficit de {$r['deficit']} moustiquaire(s) — {$r['households']} ménage(s) ajouté(s) ({$r['nets']} moustiquaire(s) attendue(s))."
            . ($r['nets'] < $r['deficit'] ? ' ⚠ Le déficit n\'est pas entièrement couvert : plus de ménages éligibles.' : ''));
    }

    /** POST — libérer tous les ménages non visités d'un bras */
    public function release(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'suivi' => 'required|in:' . implode(',', array_keys(CohortBFollowUpService::FU_B_LABELS)),
            'arm'   => 'required|in:1,2',
        ]);
        $state = collect($this->selection->state($v['suivi'])[$v['arm']]['state'] ?? []);
        $reason = ($state['reached'] ?? false) ? 'Cible atteinte' : 'Libéré manuellement';
        try {
            $n = $this->selection->releasePending($v['suivi'], $v['arm'], $reason);
        } catch (\RuntimeException $e) {
            return $this->back($v['suivi'])->with('error', $e->getMessage());
        }

        return $this->back($v['suivi'])->with('success',
            CohortBNetSelectionService::ARMS[$v['arm']] . " : {$n} ménage(s) non visité(s) libéré(s) — de nouveau disponibles pour les autres suivis.");
    }

    /**
     * GET — export CSV d'un tirage pour vérification indépendante : ménages éligibles au moment du tirage,
     * clé SHA-256(graine:household_id), rang dans l'ordre aléatoire et ménages retenus.
     */
    public function exportDraw(CbSelectionDraw $draw): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $order  = CohortBNetSelectionService::randomOrder($draw->pool, $draw->seed, $draw->spread_clusters);
        $picked = array_flip($draw->picked);
        $name   = "tirage-cohorteB_{$draw->timepoint}_bras{$draw->arm}_lot{$draw->batch}_graine{$draw->seed}.csv";

        return response()->streamDownload(function () use ($draw, $order, $picked) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $meta = [
                ['Suivi', CohortBFollowUpService::FU_B_LABELS[$draw->timepoint] ?? $draw->timepoint],
                ['Bras', CohortBNetSelectionService::ARMS[$draw->arm] ?? $draw->arm],
                ['Lot', $draw->batch === 1 ? 'Tirage initial' : 'Complément ' . ($draw->batch - 1)],
                ['Graine', $draw->seed],
                ['Moustiquaires à couvrir', $draw->need_nets],
                ['Ménages de réserve', $draw->reserve],
                ['Répartition entre grappes', $draw->spread_clusters ? 'oui' : 'non'],
                ['Date', $draw->created_at?->format('d/m/Y H:i')],
                ['Par', $draw->creator?->name ?? '—'],
                ['État', $draw->reset_at
                    ? 'Annulé par réinitialisation le ' . $draw->reset_at->format('d/m/Y H:i') . ' par ' . ($draw->resetter?->name ?? '—')
                    : 'Actif'],
                ['Méthode', 'Ordre = tri croissant de SHA-256("<graine>:<household_id>")'
                    . ($draw->spread_clusters ? ' dans chaque grappe ; grappes triées par SHA-256("<graine>:grappe:<grappe>") et parcourues à tour de rôle' : '')
                    . ' ; ménages retenus dans cet ordre jusqu\'à couvrir les moustiquaires, puis les ménages de réserve.'],
                [],
                ['Ordre aléatoire', 'Ménage', 'Grappe', 'Moustiquaires disponibles', 'Clé SHA-256', 'Retenu', 'Ordre de visite (dans ce lot)'],
            ];
            foreach ($meta as $line) fputcsv($out, $line, ';');
            foreach ($order as $i => $h) {
                fputcsv($out, [
                    $i + 1, $h['id'], $h['cluster'], $h['nets'],
                    CohortBNetSelectionService::sortKey($draw->seed, $h['id']),
                    isset($picked[$h['id']]) ? 'oui' : '',
                    isset($picked[$h['id']]) ? $picked[$h['id']] + 1 : '',
                ], ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** POST — verrouiller définitivement le tirage d'un bras */
    public function lock(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'suivi' => 'required|in:' . implode(',', array_keys(CohortBFollowUpService::FU_B_LABELS)),
            'arm'   => 'required|in:1,2',
        ]);
        try {
            $this->selection->lock($v['suivi'], $v['arm']);
        } catch (\RuntimeException $e) {
            return $this->back($v['suivi'])->with('error', $e->getMessage());
        }
        return $this->back($v['suivi'])->with('success', CohortBNetSelectionService::ARMS[$v['arm']]
            . ' : tirage verrouillé. Les ménages sélectionnés ne peuvent plus être libérés ni supprimés.');
    }

    /** POST — libérer un ménage */
    public function releaseOne(CbSelectedHousehold $row): RedirectResponse
    {
        try {
            $this->selection->releaseOne($row, 'Libéré individuellement');
        } catch (\RuntimeException $e) {
            return $this->back($row->timepoint)->with('error', $e->getMessage());
        }
        return $this->back($row->timepoint)->with('success', "Ménage {$row->household_id} libéré.");
    }

    /** POST — supprimer la sélection d'un bras pour ce suivi */
    public function reset(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'suivi' => 'required|in:' . implode(',', array_keys(CohortBFollowUpService::FU_B_LABELS)),
            'arm'   => 'required|in:1,2',
        ]);
        try {
            $this->selection->reset($v['suivi'], $v['arm']);
        } catch (\RuntimeException $e) {
            return $this->back($v['suivi'])->with('error', $e->getMessage());
        }
        return $this->back($v['suivi'])->with('success', CohortBNetSelectionService::ARMS[$v['arm']] . ' : sélection supprimée.');
    }
}
