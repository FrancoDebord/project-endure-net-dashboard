<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Suivi Cohorte B (6, 12, 18, 24, 36 mois) — Form 10 uniquement.
 * Une instance par moustiquaire de l'étude, prélevée pour les analyses de laboratoire
 * (contenu chimique ; caractéristiques physiques / bioessais / semi-terrain à 12, 24, 36 mois)
 * et remplacée par une nouvelle moustiquaire.
 */
class CohortBFollowUpService
{
    public const COMPUTED_CACHE_KEY = 'cohortB.followup.computed';

    /** Code de cb_followup_event par suivi */
    public const FU_B_CODES  = ['suivi6' => '1', 'suivi12' => '2', 'suivi18' => '3', 'suivi24' => '4', 'suivi36' => '5'];
    public const FU_B_LABELS = ['suivi6' => '6 mois', 'suivi12' => '12 mois', 'suivi18' => '18 mois', 'suivi24' => '24 mois', 'suivi36' => '36 mois'];

    public const STATUS_LABELS = [
        'non_visite' => 'Non visité',
        'en_cours'   => 'Partiel (moustiquaires restantes)',
        'realise'    => 'Complet',
    ];

    private const F10 = 'form_10_suivi_cohorte_b';

    private const LOCATIONS = [
        '1' => 'Suspendue librement', '2' => 'Suspendue, relevée/attachée', '3' => 'Non suspendue, non rangée',
        '4' => 'Rangée sans emballage', '5' => 'Rangée dans son emballage',
    ];
    private const USE_7N = [
        '1' => 'Chaque nuit (7)', '2' => 'La plupart (5-6)', '3' => 'Quelques nuits (1-4)',
        '4' => 'Non utilisée la semaine', '5' => 'Non utilisée du tout', '9' => 'Ne sait pas',
    ];
    private const SEASONAL = [
        '1' => 'Même usage toute l\'année', '2' => 'Surtout saison des pluies', '3' => 'Uniquement saison des pluies',
        '4' => 'Non utilisée', '9' => 'Ne sait pas',
    ];
    private const MAIN_USER = [
        '1' => 'Jeunes enfants uniquement', '2' => 'Jeune enfant avec adulte', '3' => 'Enfant âgé / adolescent',
        '4' => 'Adulte uniquement', '5' => 'Autre',
    ];
    private const WASH_PRODUCT = [
        '1' => 'Savon en barre', '2' => 'Détergent (OMO…)', '3' => 'Eau de javel', '4' => 'Mélange de produits', '5' => 'Aucun produit',
    ];
    private const SLEEP_PLACE = [
        '1' => 'Lit avec cadre (fini)', '2' => 'Lit (bois/bâtons)', '3' => 'Matelas en mousse', '4' => 'Natte en roseau',
        '5' => 'Herbe', '6' => 'Sol', '7' => 'Jamais utilisée',
    ];

    public function __construct(private RedCapService $redcap) {}

    /** @return array{households: Collection, timepoints: array, dags: array} */
    public function loadAll(): array
    {
        return Cache::store('file')->remember(self::COMPUTED_CACHE_KEY, now()->addMinutes(10), fn() => $this->compute());
    }

    private function compute(): array
    {
        $this->redcap->warm(array_merge(
            ['identification', 'study_nets', 'contacts', 'dags'],
            array_map(fn($k) => "fu_b_$k", array_keys(RedCapService::COHORT_B_EVENTS))
        ));

        $households = (new CohortHouseholds)->forCohort('2',
            $this->redcap->getIdentificationData(),
            $this->redcap->getBaselineContacts(),
            $this->redcap->getStudyNetData(),
            $this->redcap->getDags(),
        );

        // Les suivis sont traités dans l'ordre : une moustiquaire prélevée à un suivi
        // ne doit pas réapparaître à un suivi ultérieur.
        $timepoints = [];
        $withdrawn  = [];   // code normalisé => libellé du suivi où elle a été prélevée
        foreach (RedCapService::COHORT_B_EVENTS as $key => $event) {
            $timepoints[$key] = $this->build($this->redcap->getCohortBFollowUpData($event), $households, $key, $withdrawn);
        }

        return ['households' => $households, 'timepoints' => $timepoints, 'dags' => CohortHouseholds::dags($households)];
    }

    private static function norm(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    private static function countBy(Collection $rows, string $field, array $labels): array
    {
        $out = [];
        foreach ($labels as $code => $label) {
            $n = $rows->where($field, (string) $code)->count();
            if ($n > 0) $out[$label] = $n;
        }
        arsort($out);
        return $out;
    }

    /** Lignes Form 10 : instances répétées, ou ligne non répétée lorsque le formulaire n'est pas répétable à cet événement */
    private static function form10Rows(array $raw): Collection
    {
        $filled = fn(array $r) => collect(['fw_cb_date_visit', 'itn_study_verified', 'itn_unique_id_scan', 'itn_unique_id_saisi', 'cb_followup_event'])
            ->contains(fn($f) => ($r[$f] ?? '') !== '');

        return collect($raw)->filter(fn($r) => ($r['household_id'] ?? '') !== '' && (
            (($r['redcap_repeat_instrument'] ?? '') === self::F10 && ($r['redcap_repeat_instance'] ?? '') !== '')
            || (($r['redcap_repeat_instrument'] ?? '') === '' && $filled($r))
        ))->values();
    }

    private function netStats(Collection $f10): array
    {
        $verified = $f10->where('itn_study_verified', '1');
        $washed   = $verified->where('fwb_itn_ever_washed', '1');
        $washCounts = $washed->pluck('fwb_itn_wash_count_6m')
            ->filter(fn($v) => $v !== '' && $v !== null && (int) $v !== 99)->map(fn($v) => (int) $v);

        return [
            'assessed'      => $f10->count(),
            'verified'      => $verified->count(),
            'notVerified'   => $f10->where('itn_study_verified', '0')->count(),
            'replaced'      => $f10->filter(fn($r) => trim(($r['itn_unique_id_scan_2'] ?? '') . ($r['itn_unique_id_saisi_2'] ?? '')) !== '')->count(),
            'hanging'       => $verified->whereIn('itn_found_location', ['1', '2'])->count(),
            'usedLastNight' => $verified->where('itn_used_last_night', '1')->count(),
            'everyNight'    => $verified->where('itn_use_lastweek', '1')->count(),
            'washed'        => $washed->count(),
            'washMean'      => $washCounts->isNotEmpty() ? round($washCounts->avg(), 1) : null,
            'locations'     => self::countBy($verified, 'itn_found_location', self::LOCATIONS),
            'use7'          => self::countBy($verified, 'itn_use_lastweek', self::USE_7N),
            'seasonal'      => self::countBy($verified, 'itn_seasonal_use', self::SEASONAL),
            'mainUser'      => self::countBy($verified, 'itn_main_user', self::MAIN_USER),
            'sleepPlace'    => self::countBy($verified, 'itn_sleeping_place_type', self::SLEEP_PLACE),
            'washProduct'   => self::countBy($washed, 'fwb_itn_lastwash_product', self::WASH_PRODUCT),
        ];
    }

    public function build(array $raw, Collection $cohortB, string $key, array &$withdrawn): array
    {
        $expectedCode = self::FU_B_CODES[$key];
        $label        = self::FU_B_LABELS[$key];
        $f10All  = self::form10Rows($raw);
        $f10ByHh = $f10All->groupBy('household_id');
        $alerts  = [];

        foreach ($f10ByHh->keys()->reject(fn($id) => $cohortB->has($id)) as $id) {
            $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => 'Form 10 saisi pour un ménage hors Cohorte B'];
        }

        $newlyWithdrawn = [];
        $hhRows = $cohortB->map(function ($hh, $id) use ($f10ByHh, $expectedCode, $withdrawn, &$newlyWithdrawn, &$alerts) {
            $f10      = $f10ByHh->get($id, collect());
            $baseline = collect($hh['netCodes'])->map(fn($c) => self::norm($c))->filter()->values();
            $expected = count($hh['netCodes']);
            $codes    = $f10->map(fn($r) => self::norm(($r['itn_unique_id_scan'] ?? '') ?: ($r['itn_unique_id_saisi'] ?? '')));

            $status = $f10->isEmpty() ? 'non_visite' : ($f10->count() < $expected ? 'en_cours' : 'realise');

            // Contrôles de cohérence
            $badEvt = $f10->filter(fn($r) => ($r['cb_followup_event'] ?? '') !== '' && $r['cb_followup_event'] !== $expectedCode)->count();
            if ($badEvt) $alerts[] = ['type' => 'critical', 'hh' => $id, 'msg' => "{$badEvt} Form 10 avec un moment de suivi différent de l'événement"];
            $dups = $codes->filter()->duplicates()->unique();
            if ($dups->isNotEmpty()) $alerts[] = ['type' => 'critical', 'hh' => $id, 'msg' => 'Moustiquaire saisie plusieurs fois : ' . $dups->implode(', ')];
            $unknown = $codes->filter()->reject(fn($c) => $baseline->contains($c));
            if ($unknown->isNotEmpty()) $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => 'Code absent des moustiquaires distribuées au ménage : ' . $unknown->implode(', ')];
            foreach ($codes->filter() as $c) {
                if (isset($withdrawn[$c])) $alerts[] = ['type' => 'critical', 'hh' => $id, 'msg' => "Moustiquaire {$c} déjà prélevée au suivi {$withdrawn[$c]}"];
                $newlyWithdrawn[$c] = true;
            }
            $noCode = $f10->filter(fn($r) => trim(($r['itn_unique_id_scan'] ?? '') . ($r['itn_unique_id_saisi'] ?? '')) === '')->count();
            if ($noCode) $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => "{$noCode} Form 10 sans code de moustiquaire"];
            $noRepl = $f10->filter(fn($r) => ($r['itn_study_verified'] ?? '') === '1'
                && trim(($r['itn_unique_id_scan_2'] ?? '') . ($r['itn_unique_id_saisi_2'] ?? '')) === '')->count();
            if ($noRepl) $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => "{$noRepl} moustiquaire(s) prélevée(s) sans code de remplacement"];

            $missing = [];
            if ($status === 'en_cours') $missing[] = ($expected - $f10->count()) . ' moustiq. restante(s)';

            $first = $f10->first() ?? [];
            return $hh + [
                'status'       => $status,
                'date'         => $f10->pluck('fw_cb_date_visit')->filter()->sort()->first() ?? '',
                'agent'        => strtoupper($first['fw_cb_initials'] ?? ''),
                'missing'      => $missing,
                'netsExpected' => $expected,
                'f10Count'     => $f10->count(),
                'netsVerified' => $f10->where('itn_study_verified', '1')->count(),
            ];
        })->values();

        foreach (array_keys($newlyWithdrawn) as $c) $withdrawn[$c] ??= $label;

        $f10B = $f10All->filter(fn($r) => $cohortB->has($r['household_id']));

        $summarize = fn(Collection $hhs, Collection $f10) => [
            'hhExpected'   => $hhs->count(),
            'hhVisited'    => $hhs->where('status', '!=', 'non_visite')->count(),
            'hhDone'       => $hhs->where('status', 'realise')->count(),
            'hhPartial'    => $hhs->where('status', 'en_cours')->count(),
            'netsExpected' => $hhs->sum('netsExpected'),
            'nets'         => $this->netStats($f10),
        ];

        $all   = $summarize($hhRows, $f10B);
        $byArm = [];
        foreach (['1' => 'PERMANET DUAL', '2' => 'INTERCEPTOR G2'] as $bk => $bl) {
            $ids = $hhRows->where('brasKey', $bk)->pluck('id')->flip();
            $byArm[$bk] = ['label' => $bl] + $summarize($hhRows->where('brasKey', $bk), $f10B->filter(fn($r) => $ids->has($r['household_id'])));
        }

        $netRows = $f10B->map(function ($r) use ($cohortB) {
            $hh   = $cohortB->get($r['household_id'], []);
            $code = self::norm(($r['itn_unique_id_scan'] ?? '') ?: ($r['itn_unique_id_saisi'] ?? ''));
            return [
                'hh'       => $r['household_id'],
                'bras'     => $hh['bras'] ?? '—',
                'code'     => $code,
                'known'    => $code !== '' && collect($hh['netCodes'] ?? [])->map(fn($c) => self::norm($c))->contains($code),
                'date'     => $r['fw_cb_date_visit'] ?? '',
                'agent'    => strtoupper($r['fw_cb_initials'] ?? ''),
                'verified' => $r['itn_study_verified'] ?? '',
                'location' => self::LOCATIONS[$r['itn_found_location'] ?? ''] ?? '',
                'used'     => $r['itn_used_last_night'] ?? '',
                'use7'     => self::USE_7N[$r['itn_use_lastweek'] ?? ''] ?? '',
                'washed'   => $r['fwb_itn_ever_washed'] ?? '',
                'replacement' => self::norm(($r['itn_unique_id_scan_2'] ?? '') ?: ($r['itn_unique_id_saisi_2'] ?? '')),
            ];
        })->sortBy(fn($r) => [$r['hh'], $r['code']])->values()->all();

        $statusById = $hhRows->mapWithKeys(fn($h) => [$h['id'] => [
            'status' => $h['status'], 'date' => $h['date'], 'agent' => $h['agent'], 'missing' => $h['missing'],
            'notDoneReason' => '', 'moved' => false,
            'netsAssessed' => $h['f10Count'], 'netsPresent' => $h['netsVerified'], 'netsExpected' => $h['netsExpected'],
        ]])->all();

        $order = ['en_cours' => 0, 'realise' => 1];
        $households = $hhRows->where('status', '!=', 'non_visite')
            ->map(fn($h) => collect($h)->except(['netCodes', 'address'])->all())
            ->sortBy(fn($h) => [$order[$h['status']], $h['village'], $h['id']])->values()->all();

        $byCluster = $hhRows->groupBy(fn($h) => $h['village'] . '|' . $h['cluster'])->map(fn($g) => [
            'village'  => $g->first()['village'], 'cluster' => $g->first()['cluster'],
            'bras'     => $g->first()['bras'],    'brasKey' => $g->first()['brasKey'],
            'expected' => $g->count(),
            'visited'  => $g->where('status', '!=', 'non_visite')->count(),
            'done'     => $g->where('status', 'realise')->count(),
            'nets'     => $g->sum('f10Count'),
        ])->sortBy(fn($c) => [$c['village'], $c['cluster']])->values()->all();

        return [
            'key' => $key, 'label' => $label,
            'summary' => $all, 'byArm' => $byArm,
            'households' => $households, 'byCluster' => $byCluster,
            'statusById' => $statusById, 'netRows' => $netRows, 'alerts' => $alerts,
            'cumulativeWithdrawn' => count($withdrawn),
        ];
    }
}
