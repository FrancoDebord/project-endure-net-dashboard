<?php

namespace App\Services;

use App\Models\CbSelectedHousehold;
use App\Models\CbSelectionDraw;
use App\Models\CbSelectionPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sélection aléatoire des ménages Cohorte B pour le prélèvement des moustiquaires.
 *
 * Pour chaque suivi et chaque bras, on tire au hasard des ménages jusqu'à couvrir la cible
 * de moustiquaires (30 par défaut). Ensuite, au fil des Form 10 saisis dans REDCap :
 *  - « Compléter » : si les moustiquaires prélevées + celles attendues des ménages non encore
 *    visités n'atteignent pas la cible, on tire d'autres ménages pour combler le déficit ;
 *  - « Libérer » : si la cible est atteinte, les ménages non visités sont désélectionnés et
 *    redeviennent disponibles pour les autres suivis.
 *
 * Un ménage est éligible s'il est du bon bras, a encore au moins une moustiquaire de l'étude non
 * prélevée, n'a pas de Form 10 à ce suivi et n'est pas sélectionné (non libéré) à un autre suivi.
 */
class CohortBNetSelectionService
{
    public const ARMS           = ['1' => 'PERMANET DUAL', '2' => 'INTERCEPTOR G2'];
    public const ARM_COLORS     = ['1' => '#5B21B6', '2' => '#0369A1'];
    public const DEFAULT_TARGET = 30;

    private ?array $context = null;

    public function __construct(private CohortBFollowUpService $followUp) {}

    private static function norm(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    /**
     * Ménages Cohorte B enrichis des moustiquaires encore disponibles (non prélevées à un suivi).
     * @return array{households: Collection, timepoints: array}
     */
    public function context(): array
    {
        if ($this->context !== null) return $this->context;

        $data = $this->followUp->loadAll();

        // Moustiquaires de l'étude prélevées (Form 10 vérifié), tous suivis confondus
        $withdrawnCodes = [];   // hh => [code => true]
        $withdrawnCount = [];   // hh => nb de Form 10 vérifiés
        foreach ($data['timepoints'] as $tp) {
            foreach ($tp['netRows'] as $r) {
                if (($r['verified'] ?? '') !== '1') continue;
                $withdrawnCount[$r['hh']] = ($withdrawnCount[$r['hh']] ?? 0) + 1;
                if ($r['code'] !== '') $withdrawnCodes[$r['hh']][$r['code']] = true;
            }
        }

        $households = $data['households']->map(function ($h, $id) use ($withdrawnCodes, $withdrawnCount) {
            $availableCodes = collect($h['netCodes'])
                ->filter(fn($c) => self::norm($c) === '' || !isset($withdrawnCodes[$id][self::norm($c)]))
                ->all();
            return $h + [
                'availableCount' => max(0, count($h['netCodes']) - ($withdrawnCount[$id] ?? 0)),
                'availableCodes' => $availableCodes,
            ];
        });

        return $this->context = ['households' => $households, 'timepoints' => $data['timepoints']];
    }

    /** Statut du ménage au suivi donné (non_visite / en_cours / realise) et nb de moustiquaires prélevées */
    private function tpStatus(string $tp, string $id): array
    {
        $s = $this->context()['timepoints'][$tp]['statusById'][$id] ?? null;
        return [
            'status'    => $s['status'] ?? 'non_visite',
            'withdrawn' => (int) ($s['netsPresent'] ?? 0),
            'assessed'  => (int) ($s['netsAssessed'] ?? 0),
            'date'      => $s['date'] ?? '',
            'agent'     => $s['agent'] ?? '',
        ];
    }

    /** Ménages tirables pour ce suivi × bras */
    public function eligiblePool(string $tp, string $arm): Collection
    {
        // Indisponibles : déjà sélectionnés à ce suivi, ou en attente de visite à un autre suivi.
        // Un ménage visité à un autre suivi reste tirable s'il lui reste des moustiquaires non prélevées
        // (ex. moustiquaire introuvable lors de la visite).
        $busy = CbSelectedHousehold::whereNull('released_at')->get(['timepoint', 'household_id'])
            ->filter(fn($s) => $s->timepoint === $tp
                || $this->tpStatus($s->timepoint, $s->household_id)['status'] === 'non_visite')
            ->pluck('household_id')->flip();

        return $this->context()['households']
            ->filter(fn($h, $id) => $h['brasKey'] === $arm
                && $h['availableCount'] > 0
                && !$busy->has($id)
                && $this->tpStatus($tp, $id)['status'] === 'non_visite')
            ->values();
    }

    // ── Tirage ─────────────────────────────────────────────────────────────

    public const SEED_MAX = 999999999;

    /** Clé de tri pseudo-aléatoire, déterminée par la graine : SHA-256("graine:valeur") en hexadécimal */
    public static function sortKey(int $seed, string $value): string
    {
        return hash('sha256', $seed . ':' . $value);
    }

    /**
     * Ordre aléatoire reproductible des ménages : tri par SHA-256(graine:household_id).
     * Avec $spread, les grappes sont ordonnées par SHA-256(graine:grappe:<nom>) puis parcourues
     * à tour de rôle (1er ménage de chaque grappe, puis 2e, …) pour répartir les prélèvements.
     * Le résultat ne dépend que de la graine et de l'ensemble des ménages (pas de leur ordre d'entrée).
     *
     * @param array<array{id: string, cluster: string, nets: int}> $pool
     */
    public static function randomOrder(array $pool, int $seed, bool $spread): array
    {
        usort($pool, fn($a, $b) => strcmp(self::sortKey($seed, $a['id']), self::sortKey($seed, $b['id'])));
        if (!$spread) return $pool;

        $groups = [];
        foreach ($pool as $h) $groups[$h['cluster']][] = $h;
        uksort($groups, fn($a, $b) => strcmp(self::sortKey($seed, "grappe:$a"), self::sortKey($seed, "grappe:$b")));

        $out = [];
        for ($i = 0; count($out) < count($pool); $i++) {
            foreach ($groups as $g) {
                if (isset($g[$i])) $out[] = $g[$i];
            }
        }
        return $out;
    }

    /**
     * Algorithme de tirage (pur, rejouable) : parcours de l'ordre aléatoire en cumulant les moustiquaires
     * jusqu'à couvrir $needNets, puis $reserve ménages supplémentaires.
     * @return array<array{id: string, cluster: string, nets: int}>
     */
    public static function pick(array $pool, int $seed, bool $spread, int $needNets, int $reserve): array
    {
        $picked = [];
        $nets   = 0;
        $extra  = 0;
        foreach (self::randomOrder($pool, $seed, $spread) as $h) {
            if ($nets >= $needNets) {
                if ($extra >= $reserve) break;
                $extra++;
            }
            $picked[] = $h;
            $nets    += $h['nets'];
        }
        return $picked;
    }

    public static function newSeed(): int
    {
        return random_int(1, self::SEED_MAX);
    }

    /**
     * Tire des ménages jusqu'à couvrir $needNets moustiquaires, plus $reserve ménages de réserve,
     * et journalise le tirage (graine, paramètres, ménages éligibles, ménages retenus).
     * @return array{households: int, nets: int, poolSize: int, seed: int}
     */
    private function drawInto(CbSelectionPlan $plan, int $needNets, int $reserve, int $batch, ?int $seed): array
    {
        $seed ??= self::newSeed();
        $pool = $this->eligiblePool($plan->timepoint, $plan->arm)
            ->map(fn($h) => ['id' => $h['id'], 'cluster' => $h['cluster'], 'nets' => $h['availableCount']])
            ->sortBy('id')->values()->all();
        $picked = self::pick($pool, $seed, $plan->spread_clusters, $needNets, $reserve);
        $nets   = array_sum(array_column($picked, 'nets'));

        CbSelectionDraw::create([
            'plan_id' => $plan->id, 'timepoint' => $plan->timepoint, 'arm' => $plan->arm, 'batch' => $batch,
            'seed' => $seed, 'need_nets' => $needNets, 'reserve' => $reserve, 'spread_clusters' => $plan->spread_clusters,
            'pool' => $pool, 'picked' => array_column($picked, 'id'), 'created_by' => auth()->id(),
        ]);

        $rank = (int) CbSelectedHousehold::where('plan_id', $plan->id)->max('rank');
        DB::transaction(function () use ($picked, $plan, $batch, &$rank) {
            foreach ($picked as $h) {
                CbSelectedHousehold::updateOrCreate(
                    ['timepoint' => $plan->timepoint, 'household_id' => $h['id']],   // un ménage libéré peut être retiré à nouveau
                    [
                        'plan_id'        => $plan->id,
                        'arm'            => $plan->arm,
                        'rank'           => ++$rank,
                        'batch'          => $batch,
                        'nets_planned'   => min(255, $h['nets']),
                        'released_at'    => null,
                        'release_reason' => null,
                        'released_by'    => null,
                        'created_by'     => auth()->id(),
                    ]
                );
            }
        });

        return ['households' => count($picked), 'nets' => $nets, 'poolSize' => count($pool), 'seed' => $seed];
    }

    /** Refuse toute réinitialisation / libération / nouveau tirage sur un tirage verrouillé */
    private static function assertUnlocked(?CbSelectionPlan $plan, string $action): void
    {
        if ($plan && $plan->isLocked()) {
            throw new \RuntimeException("Tirage verrouillé le {$plan->locked_at->format('d/m/Y H:i')} : {$action} impossible.");
        }
    }

    /** Verrouille définitivement le tirage d'un suivi × bras */
    public function lock(string $tp, string $arm): CbSelectionPlan
    {
        $plan = CbSelectionPlan::where(['timepoint' => $tp, 'arm' => $arm])->firstOrFail();
        if (!$plan->households()->whereNull('released_at')->exists()) {
            throw new \RuntimeException('Aucun ménage sélectionné : rien à verrouiller.');
        }
        if (!$plan->isLocked()) {
            $plan->update(['locked_at' => now(), 'locked_by' => auth()->id()]);
        }
        return $plan;
    }

    /** Tirage initial pour un suivi × bras */
    public function draw(string $tp, string $arm, int $target, int $reserve, bool $spread, ?int $seed = null): array
    {
        $plan = CbSelectionPlan::firstOrNew(['timepoint' => $tp, 'arm' => $arm]);
        self::assertUnlocked($plan->exists ? $plan : null, 'nouveau tirage');
        if ($plan->exists && $plan->households()->whereNull('released_at')->exists()) {
            throw new \RuntimeException('Une sélection est déjà active pour ce bras. Utilisez « Compléter » ou réinitialisez-la.');
        }
        $plan->fill(['target_nets' => $target, 'spread_clusters' => $spread, 'created_by' => auth()->id()])->save();

        $batch = (int) $plan->households()->max('batch') + 1;
        return $this->drawInto($plan, $target, $reserve, $batch, $seed);
    }

    /** Complète la sélection lorsque les moustiquaires prélevées + attendues n'atteignent pas la cible */
    public function complete(string $tp, string $arm, int $reserve, ?int $seed = null): array
    {
        $plan = CbSelectionPlan::where(['timepoint' => $tp, 'arm' => $arm])->firstOrFail();
        $deficit = $this->armState($plan)['deficit'];
        if ($deficit <= 0 && $reserve <= 0) {
            throw new \RuntimeException('Aucun déficit : les ménages sélectionnés suffisent à atteindre la cible.');
        }
        $batch = (int) $plan->households()->max('batch') + 1;
        return $this->drawInto($plan, $deficit, $reserve, $batch, $seed) + ['deficit' => $deficit];
    }

    /** Désélectionne les ménages non encore visités (redeviennent disponibles pour les autres suivis) */
    public function releasePending(string $tp, string $arm, string $reason): int
    {
        $plan = CbSelectionPlan::where(['timepoint' => $tp, 'arm' => $arm])->firstOrFail();
        self::assertUnlocked($plan, 'libération des ménages');
        $ids  = collect($this->armState($plan)['rows'])->where('state', 'pending')->pluck('rowId');

        return CbSelectedHousehold::whereIn('id', $ids)->update([
            'released_at' => now(), 'release_reason' => $reason, 'released_by' => auth()->id(),
        ]);
    }

    public function releaseOne(CbSelectedHousehold $row, string $reason): void
    {
        self::assertUnlocked($row->plan, 'libération d\'un ménage');
        if ($this->tpStatus($row->timepoint, $row->household_id)['status'] !== 'non_visite') {
            throw new \RuntimeException("Le ménage {$row->household_id} a déjà un Form 10 à ce suivi : il ne peut pas être libéré.");
        }
        $row->update(['released_at' => now(), 'release_reason' => $reason, 'released_by' => auth()->id()]);
    }

    public function reset(string $tp, string $arm): void
    {
        self::assertUnlocked(CbSelectionPlan::where(['timepoint' => $tp, 'arm' => $arm])->first(), 'réinitialisation');
        // Les ménages sélectionnés sont supprimés (ils redeviennent disponibles), mais le plan et le
        // journal des tirages sont conservés : les tirages sont seulement marqués comme annulés.
        CbSelectedHousehold::where(['timepoint' => $tp, 'arm' => $arm])->delete();
        CbSelectionDraw::where(['timepoint' => $tp, 'arm' => $arm])->whereNull('reset_at')
            ->update(['reset_at' => now(), 'reset_by' => auth()->id()]);
    }

    // ── État ───────────────────────────────────────────────────────────────

    /** État d'un plan : ménages (ordre de visite) + bilan moustiquaires */
    public function armState(CbSelectionPlan $plan): array
    {
        $households = $this->context()['households'];
        $target     = $plan->target_nets;

        $rows = [];
        foreach ($plan->households()->orderBy('rank')->get() as $sel) {
            $h  = $households->get($sel->household_id, ['id' => $sel->household_id]);
            $st = $this->tpStatus($plan->timepoint, $sel->household_id);
            $rows[] = $h + [
                'rowId'       => $sel->id,
                'rank'        => $sel->rank,
                'batch'       => $sel->batch,
                // moustiquaires encore disponibles dans le ménage (capacité)
                'netsAvailable' => isset($h['availableCount']) ? min($sel->nets_planned, $h['availableCount']) : $sel->nets_planned,
                'state'       => $sel->released_at ? 'released' : ($st['status'] !== 'non_visite' ? 'visited' : 'pending'),
                'tp'          => $st,
                'releasedAt'  => $sel->released_at,
                'releaseReason' => $sel->release_reason,
            ];
        }

        // Quantité à prélever : dans l'ordre de visite, chaque ménage retenu donne TOUTES ses moustiquaires
        // disponibles, jusqu'à couvrir la cible (le total peut donc la dépasser légèrement). Les ménages
        // suivants (toTake = 0) sont en réserve et ne servent que si un ménage précédent fournit moins que prévu.
        $withdrawn = collect($rows)->where('state', 'visited')->sum(fn($r) => $r['tp']['withdrawn']);
        $remaining = max(0, $target - $withdrawn);
        foreach ($rows as &$r) {
            $r['toTake']  = 0;
            $r['reserve'] = false;
            if ($r['state'] !== 'pending') continue;
            // Pas de retrait partiel : un ménage retenu donne toutes ses moustiquaires disponibles
            $r['reserve'] = $remaining <= 0;
            $r['toTake']  = $r['reserve'] ? 0 : $r['netsAvailable'];
            $remaining   -= $r['toTake'];
        }
        unset($r);

        $active   = collect($rows)->where('state', '!=', 'released');
        $pending  = $active->where('state', 'pending');
        $planned  = $pending->sum('toTake');
        $capacity = $pending->sum('netsAvailable');

        // Ménages du bras avec un Form 10 à ce suivi mais hors sélection
        $selectedIds = $active->pluck('id')->flip();
        $offSelection = $households
            ->filter(fn($h, $id) => $h['brasKey'] === $plan->arm && !$selectedIds->has($id)
                && $this->tpStatus($plan->timepoint, $id)['status'] !== 'non_visite')
            ->map(fn($h, $id) => ['id' => $id, 'withdrawn' => $this->tpStatus($plan->timepoint, $id)['withdrawn']])
            ->values()->all();

        return [
            'plan'          => $plan,
            'rows'          => $rows,
            'target'        => $target,
            'withdrawn'     => $withdrawn,
            'plannedNets'   => $planned,
            'projected'     => $withdrawn + $planned,
            'spareNets'     => $capacity - $planned,   // moustiquaires des ménages de réserve
            'deficit'       => max(0, $target - $withdrawn - $capacity),
            'reached'       => $withdrawn >= $target,
            'pendingCount'  => $pending->count(),
            'reserveCount'  => $pending->where('reserve', true)->count(),
            'visitedCount'  => $active->where('state', 'visited')->count(),
            'releasedCount' => collect($rows)->where('state', 'released')->count(),
            'offSelection'  => $offSelection,
            'draws'         => $this->draws($plan),
            'lockedAt'      => $plan->locked_at,
            'lockedBy'      => $plan->locker?->name ?? '—',
        ];
    }

    /**
     * Journal des tirages d'un plan, chacun rejoué à partir de sa graine et de la liste des ménages
     * éligibles enregistrée : 'verified' = le rejeu redonne exactement les mêmes ménages dans le même ordre.
     */
    public function draws(CbSelectionPlan $plan): array
    {
        return CbSelectionDraw::with(['creator', 'resetter'])->where('plan_id', $plan->id)->orderBy('id')->get()
            ->map(fn(CbSelectionDraw $d) => [
                'id'       => $d->id,
                'batch'    => $d->batch,
                'date'     => $d->created_at,
                'user'     => $d->creator?->name ?? '—',
                'seed'     => $d->seed,
                'needNets' => $d->need_nets,
                'reserve'  => $d->reserve,
                'spread'   => $d->spread_clusters,
                'poolSize' => count($d->pool),
                'poolNets' => array_sum(array_column($d->pool, 'nets')),
                'picked'   => $d->picked,
                'resetAt'  => $d->reset_at,
                'resetBy'  => $d->resetter?->name ?? '—',
                'verified' => array_column(self::pick($d->pool, $d->seed, $d->spread_clusters, $d->need_nets, $d->reserve), 'id') === $d->picked,
            ])->all();
    }

    /** État des deux bras pour un suivi */
    public function state(string $tp): array
    {
        $plans = CbSelectionPlan::where('timepoint', $tp)->get()->keyBy('arm');
        $out = [];
        foreach (self::ARMS as $arm => $label) {
            $out[$arm] = [
                'label'    => $label,
                'color'    => self::ARM_COLORS[$arm],
                'poolSize' => ($pool = $this->eligiblePool($tp, $arm))->count(),
                'poolNets' => $pool->sum('availableCount'),
                'state'    => $plans->has($arm) ? $this->armState($plans[$arm]) : null,
            ];
        }
        return $out;
    }

    /** Nombre de ménages actifs (non libérés) par suivi — pour les onglets */
    public static function activeCounts(): array
    {
        return CbSelectedHousehold::whereNull('released_at')
            ->selectRaw('timepoint, count(*) as n')->groupBy('timepoint')->pluck('n', 'timepoint')->all();
    }
}
