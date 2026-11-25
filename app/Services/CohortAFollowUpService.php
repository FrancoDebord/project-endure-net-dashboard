<?php

namespace App\Services;

use App\Support\HouseholdHelpers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Suivi Cohorte A (6, 12, 24, 36 mois) — agrégation des formulaires
 * Visite Ménage, Questionnaire Base, Membres, Avant Suivi, Form 7, Form 8, Form 9.
 */
class CohortAFollowUpService
{
    use HouseholdHelpers;

    public function __construct(private RedCapService $redcap) {}

    // ══════════════════════════════════════════════════════════════════════════
    // SUIVI COHORTE A (6, 12, 24, 36 mois)
    // Formulaires : Visite Ménage, Questionnaire Base, Membres du ménage,
    //               Formulaire Avant Suivi, Form 7, Form 8, Form 9
    // ══════════════════════════════════════════════════════════════════════════

    /** Code de la fenêtre de suivi (fu_number / attrition_followup_evt) */
    public const FU_A_CODES  = ['suivi6' => '1', 'suivi12' => '2', 'suivi24' => '3', 'suivi36' => '4'];
    public const FU_A_LABELS = ['suivi6' => '6 mois', 'suivi12' => '12 mois', 'suivi24' => '24 mois', 'suivi36' => '36 mois'];

    public const STATUS_LABELS = [
        'non_visite'  => 'Non visité',
        'en_cours'    => 'En cours / à compléter',
        'non_realise' => 'Non réalisé',
        'realise'     => 'Suivi réalisé',
    ];

    private const FU_NOTDONE_REASONS = [
        '1' => 'Répondant éligible absent/indisponible', '2' => 'Refus de participer',
        '3' => 'Répondant malade/incapable de répondre', '99' => 'Autre',
    ];
    private const NET_ABSENCE_REASONS = [
        '1' => 'Volée', '2' => 'Détruite accidentellement', '3' => 'Vendue',
        '4' => 'Donnée à la famille', '5' => 'Donnée à d\'autres personnes', '6' => 'Jetée',
        '7' => 'Utilisée pour d\'autres usages', '10' => 'Utilisée par la famille ailleurs',
        '99' => 'Autre', '9' => 'Ne sait pas',
    ];
    private const NET_LOCATIONS = [
        '1' => 'Suspendue au-dessus du couchage', '2' => 'Suspendue, repliée/attachée',
        '3' => 'Non suspendue, non rangée', '4' => 'Rangée sans emballage',
        '5' => 'Rangée dans son emballage', '6' => 'Temporairement indisponible',
    ];
    private const NET_USE_7N = [
        '1' => 'Chaque nuit (7)', '2' => 'La plupart (5-6)', '3' => 'Quelques nuits (1-4)',
        '4' => 'Non utilisée la semaine', '5' => 'Jamais utilisée', '98' => 'Ne sait pas',
    ];
    // ── Form 7 : listes de choix détaillées ──
    private const NET_MATERIAL_USES = [
        '1' => 'Fenêtre / porte / avant-toit', '2' => 'Protection des plantes / cultures', '3' => 'Pêche',
        '4' => 'Séchage du poisson', '5' => 'Literie / rembourrage', '6' => 'Latrines / clôture',
        '7' => 'Réparation d\'autres moustiquaires', '8' => 'Découpée : corde / attache / divers',
        '96' => 'Autre', '98' => 'Ne sait pas',
    ];
    private const NET_NOT_HANGING = [
        '1' => 'Trop difficile à suspendre', '2' => 'Trop courte', '3' => 'Pas d\'espace',
        '4' => 'Personne pour la suspendre', '5' => 'Sera suspendue plus tard', '6' => 'Suspendue uniquement la nuit',
        '7' => 'Gardée jusqu\'à usure des autres', '96' => 'Autre', '98' => 'Ne sait pas',
    ];
    private const NET_SLEEPING_PLACES = [
        '1' => 'Lit avec cadre (complet)', '2' => 'Lit à structure simple', '3' => 'Matelas en mousse',
        '4' => 'Natte en paille', '5' => 'Sur herbe', '6' => 'À même le sol', '7' => 'Jamais utilisée',
    ];
    private const NET_NOT_USED_REASONS = [
        '1' => 'Pas de moustiques', '2' => 'Pas de paludisme', '3' => 'Trop chaud', '4' => 'N\'aime pas l\'odeur',
        '5' => 'Sensation d\'enfermement', '6' => 'Trop vieille / déchirée', '7' => 'Trop sale',
        '8' => 'Non disponible (lavage…)', '9' => 'Utilisateur absent la nuit dernière', '10' => 'Pas nécessaire',
        '96' => 'Autre', '98' => 'Ne sait pas',
    ];
    private const WASH_PRODUCTS = [
        '1' => 'Savon en barre', '2' => 'Détergent (OMO…)', '3' => 'Eau de javel', '4' => 'Mélange de produits', '5' => 'Eau seule',
    ];
    private const WASH_DRY_LOCATIONS = [
        '1' => 'Dehors, sur le sol', '2' => 'Dehors, sur un fil', '3' => 'Dehors, buisson / clôture',
        '4' => 'Intérieur, sur un fil', '5' => 'Intérieur, meuble / sol',
    ];
    private const WASH_DRY_EXPOSURE = ['1' => 'À l\'ombre', '2' => 'Au soleil'];
    private const HOLE_CAUSES = [
        '1' => 'Déchirée par un objet', '2' => 'Tirée et déchirée', '3' => 'Brûlée par une flamme',
        '4' => 'Rats ou souris', '5' => 'Couture ouverte', '6' => 'Autre manière', '7' => 'Ne se souvient pas',
    ];
    private const MODIFICATION_TYPES = [
        '1' => 'Forme modifiée', '2' => 'Ajout pour allonger', '3' => 'Ajout pour renforcer', '4' => 'Autre',
    ];
    /** Réponses ouvertes du Form 7 : champ => question */
    private const F7_OPEN_FIELDS = [
        'net_absence_reason_other'   => 'Q7.1 Sort de la moustiquaire (autre)',
        'net_material_use_other'     => 'Q8.1 Usage du matériau (autre)',
        'net_not_kept_reason'        => 'Q8.2 Pourquoi pas gardée',
        'net_not_hanging_reason_oth' => 'Q10.1 Non suspendue (autre)',
        'no_use_lst_nght_roth'       => 'Q13.1 Non utilisée (autre)',
        'itn_modification_other'     => 'Q30.1 Modification (autre)',
    ];

    private const OTHER_NET_BRANDS = [
        '1' => 'PermaNet 2.0', '2' => 'Dawa Plus', '3' => 'Olyset', '4' => 'Duranet', '5' => 'Interceptor',
        '6' => 'Netprotect', '7' => 'MagNet', '8' => 'Royal Sentry', '9' => 'Yorkool', '10' => 'SafeNet',
        '11' => 'Veeralin', '12' => 'Yahe', '15' => 'PermaNet 3.0', '16' => 'Interceptor G2',
        '17' => 'Olyset Plus', '96' => 'Autre', '97' => 'Fabriquée sur mesure', '98' => 'Sans marque',
    ];
    private const NEW_NET_SOURCES = [
        'oth_net_src_anc' => 'Soins prénatals', 'oth_net_src_vacc' => 'Vaccination enfant',
        'oth_net_src_hf' => 'Autre établissement de santé', 'oth_net_src_school' => 'École',
        'oth_net_src_friend' => 'Proches / amis', 'oth_net_src_pharm' => 'Magasin / pharmacie',
        'oth_net_src_market' => 'Marché / vendeur ambulant', 'oth_net_source' => 'Autre source',
    ];
    private const MEMBER_STATUS = [
        '1' => 'Présent', '2' => 'A quitté définitivement', '3' => 'Décédé', '4' => 'Absence temporaire',
    ];

    /**
     * Charge tous les suivis Cohorte A.
     * @return array{households: Collection, timepoints: array, dags: array}
     */
    public const COMPUTED_CACHE_KEY = 'cohortA.followup.computed.v2';

    public function loadAll(): array
    {
        // Résultat calculé mis en cache (fichier) : évite ~10 s de calcul à chaque affichage.
        // Vidé par RedCapService::clearCache() (bouton « Actualiser »).
        return Cache::store('file')->remember(self::COMPUTED_CACHE_KEY, now()->addMinutes(10), fn() => $this->compute());
    }

    private function compute(): array
    {
        // Ne préchauffer que les données utiles (pas warmAll : l'export des membres
        // dépasse régulièrement le délai REDCap et bloquerait la page)
        $this->redcap->warm(array_merge(
            ['identification', 'study_nets', 'contacts', 'dags'],
            array_map(fn($k) => "fu_a_$k", array_keys(RedCapService::COHORT_A_EVENTS))
        ));

        $households = $this->households(
            $this->redcap->getIdentificationData(),
            $this->redcap->getBaselineContacts(),
            $this->redcap->getStudyNetData(),
            $this->redcap->getDags(),
        );
        $baselineNets = $households->map(fn($h) => count($h['netCodes']));

        $timepoints = [];
        foreach (RedCapService::COHORT_A_EVENTS as $key => $event) {
            $timepoints[$key] = $this->build(
                $this->redcap->getCohortAFollowUpData($event), $households, $baselineNets, $key
            );
        }

        $dags = CohortHouseholds::dags($households);

        return compact('households', 'timepoints', 'dags');
    }

    /** Ménages Cohorte A consentis (baseline), indexés par household_id */
    public function households(array $idRaw, array $contactsRaw = [], array $studyNetRaw = [], array $dagsRaw = []): Collection
    {
        return (new CohortHouseholds)->forCohort('1', $idRaw, $contactsRaw, $studyNetRaw, $dagsRaw);
    }

    /** Indice proportionnel de trous (OMS) : poids 1 / 23 / 196 / 576 */
    private static function phi(array $r): ?int
    {
        if (($r['net_present'] ?? '') !== '1' || ($r['net_location'] ?? '') === '6') return null;
        $holes = $r['itn_has_holes'] ?? '';
        if ($holes === '0') return 0;
        if ($holes !== '1') return null;
        return (int) ($r['itn_hole_size1'] ?? 0) * 1
             + (int) ($r['itn_hole_size2'] ?? 0) * 23
             + (int) ($r['itn_hole_size3'] ?? 0) * 196
             + (int) ($r['itn_hole_size4'] ?? 0) * 576;
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

    /** Répartition d'un champ checkbox (export flat champ___code) : libellé => nombre de fiches */
    private static function countChecked(Collection $rows, string $field, array $labels): array
    {
        $out = [];
        foreach ($labels as $code => $label) {
            $n = $rows->where("{$field}___{$code}", '1')->count();
            if ($n > 0) $out[$label] = $n;
        }
        arsort($out);
        return $out;
    }

    private static function median(Collection $values): ?float
    {
        $s = $values->sort()->values();
        if ($s->isEmpty()) return null;
        $m = intdiv($s->count(), 2);
        return $s->count() % 2 ? (float) $s[$m] : ($s[$m - 1] + $s[$m]) / 2;
    }

    /** Indicateurs moustiquaires de l'étude (Form 7) */
    private function cohortANetStats(Collection $f7): array
    {
        $present = $f7->where('net_present', '1');
        $absent  = $f7->where('net_present', '0');
        $otherUse  = $absent->whereIn('net_absence_reason', ['7', '10']);
        $notHung   = $present->whereIn('net_location', ['3', '4', '5']);
        $notUsed   = $present->where('net_used_last_night', '0');
        $washedRows= $present->where('itn_ever_washed', '1');
        $holeRows  = $present->where('itn_has_holes', '1')->where('net_location', '!=', '6');
        $washCounts= $washedRows->pluck('itn_wash_count_6m')->filter(fn($v) => is_numeric($v))->map(fn($v) => (int) $v);
        $durations = $present->pluck('net_duration_months')->filter(fn($v) => is_numeric($v) && (int) $v !== 98)->map(fn($v) => (int) $v);
        $sleepers  = $present->where('net_used_last_night', '1')->pluck('fu_num_pers_net')->filter(fn($v) => is_numeric($v) && (int) $v > 0)->map(fn($v) => (int) $v);
        $phis    = $f7->map(fn($r) => self::phi($r))->filter(fn($v) => $v !== null)->values();
        $sorted  = $phis->sort()->values();
        $median  = $sorted->isEmpty() ? null
            : ($sorted->count() % 2 ? $sorted[intdiv($sorted->count(), 2)]
               : ($sorted[$sorted->count() / 2 - 1] + $sorted[$sorted->count() / 2]) / 2);

        return [
            'assessed'      => $f7->count(),
            'present'       => $present->count(),
            'absent'        => $f7->where('net_present', '0')->count(),
            'hanging'       => $present->whereIn('net_location', ['1', '2'])->count(),
            'usedLastNight' => $present->where('net_used_last_night', '1')->count(),
            'everyNight'    => $present->where('itn_utilisation_7j', '1')->count(),
            'washed'        => $present->where('itn_ever_washed', '1')->count(),
            'withHoles'     => $present->where('itn_has_holes', '1')->count(),
            'repaired'      => $present->filter(fn($r) => (int) ($r['itn_hole_repaired_full'] ?? 0) + (int) ($r['itn_hole_repaired_partial'] ?? 0) > 0)->count(),
            'phiAssessed'   => $phis->count(),
            'phiGood'       => $phis->filter(fn($v) => $v <= 64)->count(),
            'phiAcceptable' => $phis->filter(fn($v) => $v >= 65 && $v <= 642)->count(),
            'phiTorn'       => $phis->filter(fn($v) => $v >= 643)->count(),
            'phiMedian'     => $median,
            'absenceReasons'=> self::countBy($f7->where('net_present', '0'), 'net_absence_reason', self::NET_ABSENCE_REASONS),
            'locations'     => self::countBy($present, 'net_location', self::NET_LOCATIONS),
            'use7'          => self::countBy($present, 'itn_utilisation_7j', self::NET_USE_7N),
            // ── Détail Form 7 ──
            'otherUse'      => $otherUse->count(),
            'materialUses'  => self::countChecked($otherUse, 'net_material_use', self::NET_MATERIAL_USES),
            'notHung'       => $notHung->count(),
            'notHangingReasons' => self::countBy($notHung, 'net_not_hanging_reason', self::NET_NOT_HANGING),
            'sleepingPlaces'=> self::countBy($present, 'net_sleeping_place_type', self::NET_SLEEPING_PLACES),
            'notUsed'       => $notUsed->count(),
            'notUsedReasons'=> self::countBy($notUsed, 'no_used_last_night_rson', self::NET_NOT_USED_REASONS),
            'sleepersMean'  => $sleepers->isEmpty() ? null : round($sleepers->avg(), 1),
            'durationMedian'=> self::median($durations),
            'washCountMedian' => self::median($washCounts),
            'washCountMean' => $washCounts->isEmpty() ? null : round($washCounts->avg(), 1),
            'washProducts'  => self::countBy($washedRows, 'itn_lastwash_product', self::WASH_PRODUCTS),
            'washDryLocations' => self::countBy($washedRows, 'itn_lastwash_dry_location', self::WASH_DRY_LOCATIONS),
            'washDryExposure'  => self::countBy($washedRows, 'itn_lastwash_dry_exposure', self::WASH_DRY_EXPOSURE),
            'holeAssessed'  => $holeRows->count(),
            'holeCauses'    => self::countChecked($holeRows, 'itn_hole_cause', self::HOLE_CAUSES),
            'modified'      => $holeRows->where('itn_modified', '1')->count(),
            'modificationTypes' => self::countBy($holeRows->where('itn_modified', '1'), 'itn_modification_type', self::MODIFICATION_TYPES),
        ];
    }

    public function build(array $raw, Collection $cohortA, Collection $baselineNets, string $key): array
    {
        $expectedCode = self::FU_A_CODES[$key];
        $rows   = collect($raw)->filter(fn($r) => ($r['household_id'] ?? '') !== '');
        $main   = $rows->filter(fn($r) => ($r['redcap_repeat_instrument'] ?? '') === '')->keyBy('household_id');
        $repeat = fn(string $instr) => $rows->filter(fn($r) =>
            ($r['redcap_repeat_instrument'] ?? '') === $instr && ($r['redcap_repeat_instance'] ?? '') !== '');

        $f7All  = $repeat('form_7_suivi_des_moustiquaires_de_ltude');
        $f9All  = $repeat('form_9_autres_moustiquaires_du_mnage');
        $memAll = $repeat('section_1_membres_du_mnage');
        $f7ByHh = $f7All->groupBy('household_id');
        $f9ByHh = $f9All->groupBy('household_id');
        $memByHh= $memAll->groupBy('household_id');

        $filled = fn(array $r, array $fields) => collect($fields)->contains(fn($f) => ($r[$f] ?? '') !== '');
        $alerts = [];

        // Ménages ayant des données à cet événement mais hors Cohorte A
        $outside = $main->keys()->merge($f7ByHh->keys())->merge($memByHh->keys())->unique()
            ->reject(fn($id) => $cohortA->has($id))
            ->filter(function ($id) use ($main, $f7ByHh, $f9ByHh, $filled) {
                $m = $main->get($id, []);
                return $f7ByHh->has($id) || $f9ByHh->has($id)
                    || $filled($m, ['fu_date', 'fu_done', 'fu_check_other_nets']);
            })->values();
        foreach ($outside as $id) {
            $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => 'Formulaires Cohorte A (Avant suivi / Form 7 / 8 / 9) saisis pour un ménage hors Cohorte A'];
        }

        $hhRows = $cohortA->map(function ($hh, $id) use ($main, $f7ByHh, $f9ByHh, $memByHh, $baselineNets, $filled, $expectedCode, &$alerts) {
            $m    = $main->get($id, []);
            $f7   = $f7ByHh->get($id, collect());
            $f9   = $f9ByHh->get($id, collect());
            $mem  = $memByHh->get($id, collect());

            $forms = [
                'visite'  => $filled($m, ['a_date_visite_id', 'visit_status', 'a_initiales_id']),
                'qb'      => $filled($m, ['b_date_visit', 'consent_accepted', 'hh_members_n']),
                'membres' => $mem->count() > 0,
                'avant'   => $filled($m, ['fu_date', 'fu_hh_present', 'fu_done']),
                'f7'      => $f7->count() > 0,
                'f8'      => ($m['fu_check_other_nets'] ?? '') !== '',
                'f9'      => $f9->count() > 0,
            ];
            $hasAny = in_array(true, $forms, true);
            $fuDone = $m['fu_done'] ?? '';
            $status = !$hasAny ? 'non_visite'
                : ($fuDone === '1' ? 'realise' : ($fuDone === '0' ? 'non_realise' : 'en_cours'));

            $netsExpected = (int) $baselineNets->get($id, 0);
            $otherNetsYes = ($m['fu_check_other_nets'] ?? '') === '1';
            $otherNetsN   = $otherNetsYes ? (int) ($m['fu_other_nets_n'] ?? 0) : 0;

            // Formulaires manquants (uniquement si le suivi est réalisé)
            $missing = [];
            if ($status === 'realise') {
                if (!$forms['visite'])  $missing[] = 'Visite';
                if (!$forms['qb'])      $missing[] = 'Quest. Base';
                if (!$forms['membres']) $missing[] = 'Membres';
                if (!$forms['f8'])      $missing[] = 'Form 8';
                if ($f7->count() < $netsExpected) $missing[] = 'Form 7 (' . $f7->count() . '/' . $netsExpected . ')';
                if ($otherNetsYes && $f9->count() < $otherNetsN) $missing[] = 'Form 9 (' . $f9->count() . '/' . $otherNetsN . ')';
            }

            // Contrôles de cohérence
            if (($m['fu_number'] ?? '') !== '' && $m['fu_number'] !== $expectedCode) {
                $alerts[] = ['type' => 'critical', 'hh' => $id, 'msg' => "Fenêtre de suivi (fu_number = {$m['fu_number']}) différente de l'événement"];
            }
            $badEvt = $f7->filter(fn($r) => ($r['attrition_followup_evt'] ?? '') !== '' && $r['attrition_followup_evt'] !== $expectedCode)->count();
            if ($badEvt) {
                $alerts[] = ['type' => 'critical', 'hh' => $id, 'msg' => "{$badEvt} Form 7 avec un moment de suivi différent de l'événement"];
            }
            $dupNets = $f7->pluck('fu_num_hh_itn')->filter()->duplicates()->unique()->values();
            if ($dupNets->isNotEmpty()) {
                $alerts[] = ['type' => 'critical', 'hh' => $id, 'msg' => 'Moustiquaire(s) n° ' . $dupNets->implode(', ') . ' saisie(s) plusieurs fois dans le Form 7'];
            }
            if (!$otherNetsYes && $f9->count() > 0) {
                $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => 'Form 9 rempli alors que Form 8 ≠ Oui'];
            }
            if ($f7->count() > $netsExpected && $netsExpected > 0) {
                $alerts[] = ['type' => 'warning', 'hh' => $id, 'msg' => "Plus de Form 7 ({$f7->count()}) que de moustiquaires distribuées ({$netsExpected})"];
            }

            $date = ($m['fu_date'] ?? '') ?: (($m['a_date_visite_id'] ?? '') ?: (($m['b_date_visit'] ?? '') ?: ($f7->first()['afw_date'] ?? '')));
            $agent = ($m['a_initiales_id'] ?? '') ?: (($m['b_agent_initiales'] ?? '') ?: ($f7->first()['a_fw_initials'] ?? ''));

            return $hh + [
                'status'        => $status,
                'date'          => $date,
                'agent'         => strtoupper($agent),
                'forms'         => $forms,
                'missing'       => $missing,
                'hhPresent'     => $m['fu_hh_present'] ?? '',
                'moved'         => ($m['fu_hh_same_location'] ?? '') === '0',
                'notDoneReason' => $m['fu_notdone_reason'] ?? '',
                'newNets'       => ($m['autre_moustiquaire'] ?? '') === '1',
                'newNetsN'      => (int) ($m['nb_total_moustiquaire'] ?? 0),
                'otherNetsYes'  => $otherNetsYes,
                'otherNetsN'    => $otherNetsN,
                'netsExpected'  => $netsExpected,
                'f7Count'       => $f7->count(),
                'netsPresent'   => $f7->where('net_present', '1')->count(),
                'f9Count'       => $f9->count(),
                'memCount'      => $mem->count(),
                '_m'            => $m,
            ];
        })->values();

        // Restreindre les instances répétées aux ménages de la Cohorte A
        $f7A  = $f7All->filter(fn($r) => $cohortA->has($r['household_id']));
        $f9A  = $f9All->filter(fn($r) => $cohortA->has($r['household_id']));
        $memA = $memAll->filter(fn($r) => $cohortA->has($r['household_id']));

        $summarize = function (Collection $hhs, Collection $f7, Collection $f9, Collection $mem) {
            $mains   = $hhs->pluck('_m');
            $done    = $hhs->where('status', 'realise');
            $notDone = $mains->where('fu_done', '0');
            return [
                'hhExpected'    => $hhs->count(),
                'hhVisited'     => $hhs->where('status', '!=', 'non_visite')->count(),
                'hhDone'        => $done->count(),
                'hhNotDone'     => $hhs->where('status', 'non_realise')->count(),
                'hhInProgress'  => $hhs->where('status', 'en_cours')->count(),
                'hhAbsent'      => $mains->where('fu_hh_present', '0')->count(),
                'hhMoved'       => $hhs->where('moved', true)->count(),
                'hhIncomplete'  => $done->filter(fn($h) => !empty($h['missing']))->count(),
                'notDoneReasons'=> self::countBy($notDone, 'fu_notdone_reason', self::FU_NOTDONE_REASONS),
                'netsExpected'  => $hhs->sum('netsExpected'),
                'netsExpectedDone' => $done->sum('netsExpected'),
                'nets'          => $this->cohortANetStats($f7),
                'hhNewNets'     => $hhs->where('newNets', true)->count(),
                'newNetsTotal'  => $hhs->sum('newNetsN'),
                'newNetSources' => collect(self::NEW_NET_SOURCES)
                    ->map(fn($lbl, $f) => $mains->where($f, '1')->count())
                    ->filter()->sortDesc()->mapWithKeys(fn($n, $f) => [self::NEW_NET_SOURCES[$f] => $n])->all(),
                'hhOtherNets'   => $hhs->where('otherNetsYes', true)->count(),
                'otherNetsDeclared' => $hhs->sum('otherNetsN'),
                'f9Count'       => $f9->count(),
                'otherNetsUsed' => $f9->where('other_net_used_last_night', '1')->count(),
                'otherNetBrands'=> self::countBy($f9, 'fw_other_net_brand', self::OTHER_NET_BRANDS),
                'members'       => $mem->count(),
                'memberStatus'  => self::countBy($mem, 'member_status', self::MEMBER_STATUS),
                'membersNew'    => $mem->where('member_baseline', '0')->count(),
            ];
        };

        $all  = $summarize($hhRows, $f7A, $f9A, $memA);
        $byArm = [];
        foreach (self::BRAS_LABELS as $bk => $bl) {
            $ids = $hhRows->where('brasKey', $bk)->pluck('id')->flip();
            $byArm[$bk] = ['label' => $bl] + $summarize(
                $hhRows->where('brasKey', $bk),
                $f7A->filter(fn($r) => $ids->has($r['household_id'])),
                $f9A->filter(fn($r) => $ids->has($r['household_id'])),
                $memA->filter(fn($r) => $ids->has($r['household_id']))
            );
        }

        // Détail par moustiquaire (Form 7)
        $netRows = $f7A->map(function ($r) use ($cohortA) {
            $hh = $cohortA->get($r['household_id'], []);
            return [
                'hh'       => $r['household_id'],
                'bras'     => $hh['bras'] ?? '—',
                'village'  => $hh['village'] ?? '—',
                'num'      => $r['fu_num_hh_itn'] ?? '',
                'code'     => $r['fu_itn_id_saisie'] ?? '',
                'date'     => $r['afw_date'] ?? '',
                'present'  => $r['net_present'] ?? '',
                'absence'  => self::NET_ABSENCE_REASONS[$r['net_absence_reason'] ?? ''] ?? '',
                'location' => self::NET_LOCATIONS[$r['net_location'] ?? ''] ?? '',
                'used'     => $r['net_used_last_night'] ?? '',
                'use7'     => self::NET_USE_7N[$r['itn_utilisation_7j'] ?? ''] ?? '',
                'washed'   => $r['itn_ever_washed'] ?? '',
                'phi'      => self::phi($r),
            ];
        })->sortBy(fn($r) => [$r['hh'], (int) $r['num']])->values()->all();

        // Réponses ouvertes du Form 7
        $openAnswers = [];
        foreach ($f7A as $r) {
            foreach (self::F7_OPEN_FIELDS as $field => $question) {
                $txt = trim((string) ($r[$field] ?? ''));
                if ($txt === '') continue;
                $hh = $cohortA->get($r['household_id'], []);
                $openAnswers[] = [
                    'question' => $question,
                    'text'     => $txt,
                    'hh'       => $r['household_id'],
                    'bras'     => $hh['bras'] ?? '—',
                    'code'     => ($r['fu_itn_id_saisie'] ?? '') ?: ('n° ' . ($r['fu_num_hh_itn'] ?? '?')),
                    'date'     => $r['afw_date'] ?? '',
                    'agent'    => strtoupper($r['a_fw_initials'] ?? ''),
                ];
            }
        }
        $order7 = array_flip(array_values(self::F7_OPEN_FIELDS));
        usort($openAnswers, fn($a, $b) => [$order7[$a['question']], $a['hh']] <=> [$order7[$b['question']], $b['hh']]);

        // Statut compact de chaque ménage (page de suivi individuel)
        $statusById = $hhRows->mapWithKeys(fn($h) => [$h['id'] => [
            'status'        => $h['status'],
            'date'          => $h['date'],
            'agent'         => $h['agent'],
            'missing'       => $h['missing'],
            'notDoneReason' => self::FU_NOTDONE_REASONS[$h['notDoneReason']] ?? '',
            'moved'         => $h['moved'],
            'netsAssessed'  => $h['f7Count'],
            'netsPresent'   => $h['netsPresent'],
            'netsExpected'  => $h['netsExpected'],
        ]])->all();

        // Tableau : uniquement les ménages visités (1 600+ ménages attendus par suivi)
        $order = ['non_realise' => 0, 'en_cours' => 1, 'realise' => 2];
        $households = $hhRows->where('status', '!=', 'non_visite')
            ->map(fn($h) => collect($h)->except(['_m', 'netCodes', 'address'])->all())
            ->sortBy(fn($h) => [$order[$h['status']], $h['village'], $h['id']])->values()->all();

        // Avancement par village / grappe
        $byCluster = $hhRows->groupBy(fn($h) => $h['village'] . '|' . $h['cluster'])
            ->map(function ($g) {
                $first = $g->first();
                return [
                    'village'  => $first['village'],
                    'cluster'  => $first['cluster'],
                    'bras'     => $first['bras'],
                    'brasKey'  => $first['brasKey'],
                    'expected' => $g->count(),
                    'visited'  => $g->where('status', '!=', 'non_visite')->count(),
                    'done'     => $g->where('status', 'realise')->count(),
                    'notDone'  => $g->where('status', 'non_realise')->count(),
                ];
            })->sortBy(fn($c) => [$c['village'], $c['cluster']])->values()->all();

        return [
            'key'        => $key,
            'label'      => self::FU_A_LABELS[$key],
            'summary'    => $all,
            'byArm'      => $byArm,
            'households' => $households,
            'byCluster'  => $byCluster,
            'statusById' => $statusById,
            'netRows'    => $netRows,
            'openAnswers'=> $openAnswers,
            'alerts'     => $alerts,
            'outside'    => $outside->all(),
        ];
    }

}
