<?php

namespace App\Http\Controllers\Concerns;

use App\Services\RedCapService;
use Illuminate\Support\Collection;

/**
 * Queries des suivis de cohorte (utilisé par QueriesController, qui fournit q() et visitOf()).
 *
 *  - Cohorte A (6, 12, 24, 36 mois) : Visite Ménage, Questionnaire Base, Membres du ménage,
 *    Formulaire Avant Suivi, Form 7 (une instance par moustiquaire), Form 8, Form 9.
 *  - Cohorte B (6, 12, 18, 24, 36 mois) : Form 10 uniquement, une instance par moustiquaire
 *    de l'étude (prélevée pour analyse puis remplacée).
 */
trait FollowUpQueryChecks
{
    /** Catalogue des codes (affiché dans la documentation) : code => [sévérité, titre] */
    public const FU_QUERY_CODES = [
        // ── Cohorte A — Avant Suivi / complétude ──
        'Q-FUA-01' => ['warning',  'Formulaires Cohorte A saisis pour un ménage hors Cohorte A'],
        'Q-FUA-02' => ['warning',  'Formulaire Avant Suivi non renseigné (suivi en cours)'],
        'Q-FUA-03' => ['critical', 'Fenêtre de suivi (fu_number) différente de l\'événement'],
        'Q-FUA-04' => ['critical', 'Date de suivi invalide (manquante, future ou antérieure à la baseline)'],
        'Q-FUA-05' => ['critical', 'Suivi non réalisé sans raison'],
        'Q-FUA-06' => ['warning',  'Suivi réalisé mais raison de non-réalisation renseignée'],
        'Q-FUA-07' => ['warning',  'Ménage absent mais suivi déclaré réalisé'],
        'Q-FUA-08' => ['warning',  'Suivi non réalisé mais formulaires moustiquaires saisis'],
        'Q-FUA-09' => ['warning',  'Coordonnées GPS du suivi manquantes'],
        'Q-FUA-10' => ['warning',  'Nouvelles moustiquaires déclarées sans nombre ou sans source'],
        'Q-FUA-11' => ['warning',  'Visite Ménage manquante (suivi réalisé)'],
        'Q-FUA-12' => ['warning',  'Questionnaire Base manquant (suivi réalisé)'],
        'Q-FUA-13' => ['warning',  'Membres du ménage non mis à jour'],
        'Q-FUA-14' => ['critical', 'Form 7 incomplet : moins de fiches que de moustiquaires distribuées'],
        'Q-FUA-15' => ['critical', 'Form 8 non renseigné (suivi réalisé)'],
        'Q-FUA-16' => ['warning',  'Form 9 incomplet : moins de fiches que d\'autres moustiquaires déclarées'],
        'Q-FUA-17' => ['warning',  'Form 9 saisi alors que Form 8 ≠ Oui'],
        'Q-FUA-18' => ['warning',  'Form 8 = Oui sans nombre d\'autres moustiquaires'],
        // ── Cohorte A — Form 7 (par moustiquaire) ──
        'Q-FUA-20' => ['critical', 'Form 7 : moment du suivi différent de l\'événement'],
        'Q-FUA-21' => ['critical', 'Form 7 : code de la moustiquaire manquant'],
        'Q-FUA-22' => ['critical', 'Form 7 : code absent des moustiquaires distribuées au ménage'],
        'Q-FUA-23' => ['warning',  'Form 7 : numéro et code de la moustiquaire ne correspondent pas'],
        'Q-FUA-24' => ['critical', 'Form 7 : même moustiquaire saisie plusieurs fois'],
        'Q-FUA-25' => ['critical', 'Form 7 : présence de la moustiquaire non renseignée'],
        'Q-FUA-26' => ['warning',  'Form 7 : moustiquaire absente mais état / utilisation renseignés'],
        'Q-FUA-27' => ['critical', 'Form 7 : utilisée la nuit dernière mais non utilisée la semaine'],
        'Q-FUA-28' => ['warning',  'Form 7 : utilisée chaque nuit mais pas la nuit dernière'],
        'Q-FUA-29' => ['warning',  'Form 7 : moustiquaire rangée mais utilisée la nuit dernière'],
        'Q-FUA-30' => ['warning',  'Form 7 : utilisée la nuit dernière sans nombre de dormeurs'],
        'Q-FUA-31' => ['warning',  'Form 7 : même personne sélectionnée plusieurs fois'],
        'Q-FUA-32' => ['warning',  'Form 7 : numéro de personne absent de la liste des membres'],
        'Q-FUA-33' => ['warning',  'Form 7 : trous déclarés mais aucun trou compté'],
        'Q-FUA-34' => ['warning',  'Form 7 : plus de trous réparés que de trous comptés'],
        'Q-FUA-35' => ['warning',  'Form 7 : plus de fiches que de moustiquaires distribuées'],
        // ── Cohorte A — Form 9 ──
        'Q-FUA-40' => ['warning',  'Form 9 : observation de la moustiquaire non renseignée'],
        'Q-FUA-41' => ['critical', 'Form 9 : utilisée la nuit dernière mais non utilisée la semaine'],
        // ── Cohorte A — Membres / Questionnaire Base ──
        'Q-FUA-50' => ['warning',  'Membres : statut actuel du membre non renseigné'],
        'Q-FUA-51' => ['warning',  'Membres : changement de statut sans date ou sans raison'],
        'Q-FUA-52' => ['warning',  'Membres : nouveau membre sans date d\'entrée'],
        'Q-FUA-53' => ['warning',  'Nombre de membres (Quest. Base) ≠ membres présents'],
        // ── Cohorte B — Form 10 ──
        'Q-FUB-01' => ['warning',  'Form 10 saisi pour un ménage hors Cohorte B'],
        'Q-FUB-02' => ['critical', 'Form 10 : moment du suivi différent de l\'événement'],
        'Q-FUB-03' => ['critical', 'Form 10 : date de visite invalide (manquante, future ou antérieure à la baseline)'],
        'Q-FUB-04' => ['critical', 'Form 10 : code de la moustiquaire manquant'],
        'Q-FUB-05' => ['critical', 'Form 10 : code absent des moustiquaires du ménage'],
        'Q-FUB-06' => ['critical', 'Form 10 : même moustiquaire saisie plusieurs fois'],
        'Q-FUB-07' => ['critical', 'Form 10 : moustiquaire déjà prélevée à un suivi précédent'],
        'Q-FUB-08' => ['critical', 'Form 10 : vérification de la moustiquaire non renseignée'],
        'Q-FUB-09' => ['critical', 'Form 10 : moustiquaire prélevée sans code de remplacement'],
        'Q-FUB-10' => ['critical', 'Form 10 : code de remplacement identique au code prélevé'],
        'Q-FUB-11' => ['critical', 'Form 10 : code de remplacement déjà attribué'],
        'Q-FUB-12' => ['warning',  'Form 10 : moustiquaire non vérifiée mais réponses renseignées'],
        'Q-FUB-13' => ['critical', 'Form 10 : utilisée la nuit dernière mais non utilisée la semaine'],
        'Q-FUB-14' => ['warning',  'Form 10 : utilisée chaque nuit mais pas la nuit dernière'],
        'Q-FUB-15' => ['info',     'Form 10 : moustiquaires du ménage non encore prélevées'],
    ];

    private const FU_F7  = 'form_7_suivi_des_moustiquaires_de_ltude';
    private const FU_F9  = 'form_9_autres_moustiquaires_du_mnage';
    private const FU_F10 = 'form_10_suivi_cohorte_b';
    private const FU_MEM = 'section_1_membres_du_mnage';

    /** Code attendu de la fenêtre de suivi (fu_number / attrition_followup_evt / cb_followup_event) */
    private const FU_A_EVENT_CODE = [
        'suivi_6_mois_arm_1' => '1', 'suivi_12_mois_arm_1' => '2', 'suivi_24_mois_arm_1' => '3', 'suivi_36_mois_arm_1' => '4',
    ];
    private const FU_B_EVENT_CODE = [
        'suivi_6_mois_arm_1' => '1', 'suivi_12_mois_arm_1' => '2', 'suivi_18_mois_coho_arm_1' => '3',
        'suivi_24_mois_arm_1' => '4', 'suivi_36_mois_arm_1' => '5',
    ];
    private const FU_NOT_USED_WEEK = ['4', '5'];   // « Non utilisée la semaine dernière » / « Jamais utilisée »

    // ── Helpers ──────────────────────────────────────────────────────────────

    private static function fuNorm(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $code));
    }

    private static function fuFilled(array $r, array $fields): bool
    {
        foreach ($fields as $f) {
            if (($r[$f] ?? '') !== '') return true;
        }
        return false;
    }

    /** Moustiquaires de l'étude distribuées à la baseline : [household_id => [instance => code normalisé]] */
    private static function fuBaselineNets(array $studyNetRaw): array
    {
        $out = [];
        foreach ($studyNetRaw as $r) {
            if (($r['redcap_repeat_instrument'] ?? '') !== 'section_5_moustiquaires_imprgnes_dinsecticide_appa') continue;
            if (($r['redcap_event_name'] ?? 'baseline_et_distri_arm_1') !== 'baseline_et_distri_arm_1') continue;
            if (($r['household_id'] ?? '') === '' || ($r['redcap_repeat_instance'] ?? '') === '') continue;
            $out[$r['household_id']][(string) $r['redcap_repeat_instance']] = self::fuNorm($r['net_identifier_man'] ?? '');
        }
        return $out;
    }

    /** Nombre de membres enregistrés à la baseline : [household_id => n] */
    private static function fuBaselineMembers(array $memberRaw): array
    {
        $out = [];
        foreach ($memberRaw as $r) {
            if (($r['redcap_repeat_instrument'] ?? '') !== self::FU_MEM || ($r['redcap_repeat_instance'] ?? '') === '') continue;
            if (($r['redcap_event_name'] ?? 'baseline_et_distri_arm_1') !== 'baseline_et_distri_arm_1') continue;
            $out[$r['household_id']] = ($out[$r['household_id']] ?? 0) + 1;
        }
        return $out;
    }

    /** Problème de date : null si la date est valide */
    private static function fuDateIssue(string $date, string $baselineDate): ?string
    {
        if ($date === '') return 'date manquante';
        if ($date > now()->toDateString()) return "date dans le futur ({$date})";
        if ($baselineDate !== '' && $date <= $baselineDate) return "date ({$date}) antérieure ou égale à la visite baseline ({$baselineDate})";
        return null;
    }

    private static function fuEventLabel(string $event): string
    {
        return self::EVENT_LABELS[$event] ?? $event;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // COHORTE A
    // ══════════════════════════════════════════════════════════════════════════

    private function checkCohortAFollowUp(array $raw, Collection $visits, string $event, array $baseNets, array $baseMembers): array
    {
        $expected = self::FU_A_EVENT_CODE[$event] ?? null;
        if ($expected === null) return [];
        $evLabel = self::fuEventLabel($event);

        $rows = collect($raw)->filter(fn($r) => ($r['household_id'] ?? '') !== '');
        $main = $rows->filter(fn($r) => ($r['redcap_repeat_instrument'] ?? '') === '')->keyBy('household_id');
        $rep  = fn(string $i) => $rows->filter(fn($r) =>
            ($r['redcap_repeat_instrument'] ?? '') === $i && ($r['redcap_repeat_instance'] ?? '') !== '')->groupBy('household_id');
        $f7ByHh  = $rep(self::FU_F7);
        $f9ByHh  = $rep(self::FU_F9);
        $memByHh = $rep(self::FU_MEM);

        $avantFields  = ['fu_date', 'fu_hh_present', 'fu_done', 'fu_number'];
        $visiteFields = ['a_date_visite_id', 'visit_status', 'a_initiales_id'];
        $qbFields     = ['b_date_visit', 'consent_accepted', 'hh_members_n'];

        $ids = $main->keys()->merge($f7ByHh->keys())->merge($f9ByHh->keys())->merge($memByHh->keys())->unique();
        $results = [];

        foreach ($ids as $id) {
            $hh  = $this->visitOf($visits, $id) ?? [];
            $m   = $main->get($id, []);
            $f7  = $f7ByHh->get($id, collect());
            $f9  = $f9ByHh->get($id, collect());
            $mem = $memByHh->get($id, collect());

            $hasAvant  = self::fuFilled($m, $avantFields);
            $hasF8     = ($m['fu_check_other_nets'] ?? '') !== '';
            $hasNetFrm = $f7->isNotEmpty() || $f9->isNotEmpty() || $hasF8;
            $hasVisite = self::fuFilled($m, $visiteFields);
            $hasQb     = self::fuFilled($m, $qbFields);
            if (!$hasAvant && !$hasNetFrm && !$hasVisite && !$hasQb && $mem->isEmpty()) continue;

            $ctx = fn(string $form, array $extra = []) => array_merge([
                'event'        => $event,
                'form'         => $form,
                'household_id' => $id,
                'tablet'       => ($m['a_tablette_id'] ?? '') ?: ($hh['a_tablette_id'] ?? ''),
                'initials'     => strtoupper(($m['a_initiales_id'] ?? '') ?: (($f7->first()['a_fw_initials'] ?? '') ?: ($hh['a_initiales_id'] ?? ''))),
            ], $extra);

            // ── Ménage hors Cohorte A ──
            if (($hh['consent_accepted'] ?? '') !== '1' || ($hh['study_cohort'] ?? '') !== '1') {
                if ($hasAvant || $hasNetFrm) {
                    $cohort = match ($hh['study_cohort'] ?? '') { '2' => 'Cohorte B', '1' => 'Cohorte A (non consenti)', default => 'inconnu à la baseline' };
                    $results[] = $this->q('Q-FUA-01', 'warning',
                        self::FU_QUERY_CODES['Q-FUA-01'][1],
                        "Au {$evLabel}, des formulaires réservés à la Cohorte A (Avant Suivi, Form 7, 8 ou 9) sont saisis pour le ménage {$id}, qui n'est pas un ménage consenti de la Cohorte A ({$cohort}). Les ménages de la Cohorte B ne remplissent que le Form 10.",
                        "Vérifier l'identifiant du ménage saisi. S'il s'agit d'une erreur de ménage, déplacer ou supprimer ces formulaires ; s'il s'agit d'un ménage Cohorte B, saisir le Form 10 à la place.",
                        $ctx('formulaire_avant_suivi', ['field' => 'study_cohort (baseline)', 'value' => $cohort]));
                }
                continue;
            }

            $fuDone   = $m['fu_done'] ?? '';
            $netsExp  = count($baseNets[$id] ?? []);
            $otherYes = ($m['fu_check_other_nets'] ?? '') === '1';
            $otherN   = (int) ($m['fu_other_nets_n'] ?? 0);
            $baseDate = $hh['a_date_visite_id'] ?? '';

            // ── Formulaire Avant Suivi ──
            if (!$hasAvant || $fuDone === '') {
                $results[] = $this->q('Q-FUA-02', 'warning', self::FU_QUERY_CODES['Q-FUA-02'][1],
                    "Au {$evLabel}, des données existent pour le ménage {$id} mais la question Q5 du Formulaire Avant Suivi (« Êtes-vous en mesure de faire le suivi ? », fu_done) n'est pas renseignée. Le statut du suivi ne peut pas être déterminé.",
                    "Compléter le Formulaire Avant Suivi (Q1 à Q5) pour ce ménage.",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_done', 'value' => 'vide']));
            }
            if (($m['fu_number'] ?? '') !== '' && $m['fu_number'] !== $expected) {
                $results[] = $this->q('Q-FUA-03', 'critical', self::FU_QUERY_CODES['Q-FUA-03'][1],
                    "Ménage {$id} : la fenêtre de suivi déclarée (fu_number = {$m['fu_number']}) ne correspond pas à l'événement REDCap {$evLabel} (code attendu {$expected}). Les données ont probablement été saisies dans le mauvais événement.",
                    "Vérifier le moment réel du suivi : corriger fu_number, ou déplacer les données vers le bon événement.",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_number', 'value' => "{$m['fu_number']} (attendu {$expected})"]));
            }
            if ($hasAvant && ($issue = self::fuDateIssue($m['fu_date'] ?? '', $baseDate))) {
                $results[] = $this->q('Q-FUA-04', 'critical', self::FU_QUERY_CODES['Q-FUA-04'][1],
                    "Ménage {$id}, Formulaire Avant Suivi ({$evLabel}) : {$issue}.",
                    "Corriger la date de visite du suivi (fu_date).",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_date', 'value' => $m['fu_date'] ?? 'vide', 'extra' => "Baseline : " . ($baseDate ?: '—')]));
            }
            if ($fuDone === '0' && ($m['fu_notdone_reason'] ?? '') === '') {
                $results[] = $this->q('Q-FUA-05', 'critical', self::FU_QUERY_CODES['Q-FUA-05'][1],
                    "Ménage {$id} : le suivi est déclaré non réalisé (fu_done = Non) mais la raison (Q6, fu_notdone_reason) n'est pas renseignée.",
                    "Renseigner la raison de non-réalisation du suivi (absence, refus, maladie, autre).",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_notdone_reason', 'value' => 'vide']));
            }
            if ($fuDone === '1' && ($m['fu_notdone_reason'] ?? '') !== '') {
                $results[] = $this->q('Q-FUA-06', 'warning', self::FU_QUERY_CODES['Q-FUA-06'][1],
                    "Ménage {$id} : le suivi est déclaré réalisé (fu_done = Oui) mais une raison de non-réalisation est renseignée (fu_notdone_reason = {$m['fu_notdone_reason']}). Note : dans REDCap, Q6 n'a pas de logique de branchement et s'affiche toujours.",
                    "Effacer la raison de non-réalisation si le suivi a bien été réalisé, ou corriger fu_done.",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_done / fu_notdone_reason', 'value' => "fu_done=1, fu_notdone_reason={$m['fu_notdone_reason']}"]));
            }
            if (($m['fu_hh_present'] ?? '') === '0' && $fuDone === '1') {
                $results[] = $this->q('Q-FUA-07', 'warning', self::FU_QUERY_CODES['Q-FUA-07'][1],
                    "Ménage {$id} : le ménage est déclaré absent (fu_hh_present = Absent) mais le suivi est déclaré réalisé (fu_done = Oui).",
                    "Vérifier la disponibilité du ménage et corriger l'une des deux réponses.",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_hh_present / fu_done', 'value' => 'fu_hh_present=0, fu_done=1']));
            }
            if ($fuDone === '0' && $hasNetFrm) {
                $results[] = $this->q('Q-FUA-08', 'warning', self::FU_QUERY_CODES['Q-FUA-08'][1],
                    "Ménage {$id} : le suivi est déclaré non réalisé (fu_done = Non) mais des formulaires moustiquaires sont saisis (Form 7 : {$f7->count()}, Form 8 : " . ($hasF8 ? 'oui' : 'non') . ", Form 9 : {$f9->count()}).",
                    "Si le suivi a bien eu lieu, corriger fu_done à Oui ; sinon supprimer les formulaires saisis par erreur.",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_done', 'value' => '0, formulaires présents']));
            }
            if (($m['fu_hh_same_location'] ?? '') === '1' && (($m['fu_hh_lat'] ?? '') === '' || ($m['fu_hh_long'] ?? '') === '')) {
                $results[] = $this->q('Q-FUA-09', 'warning', self::FU_QUERY_CODES['Q-FUA-09'][1],
                    "Ménage {$id} : la latitude et/ou la longitude du suivi (Q3.1) ne sont pas renseignées.",
                    "Relever les coordonnées GPS lors de la prochaine visite ou les compléter si elles ont été notées.",
                    $ctx('formulaire_avant_suivi', ['field' => 'fu_hh_lat / fu_hh_long', 'value' => 'vide']));
            }
            if (($m['autre_moustiquaire'] ?? '') === '1') {
                $sources = ['oth_net_src_anc', 'oth_net_src_vacc', 'oth_net_src_hf', 'oth_net_src_school',
                            'oth_net_src_friend', 'oth_net_src_pharm', 'oth_net_src_market', 'oth_net_source'];
                $noNumber = (int) ($m['nb_total_moustiquaire'] ?? 0) <= 0;
                $noSource = !collect($sources)->contains(fn($f) => ($m[$f] ?? '') === '1');
                if ($noNumber || $noSource) {
                    $what = implode(' et ', array_filter([$noNumber ? 'le nombre (Q7) est vide ou nul' : '', $noSource ? 'aucune source n\'est cochée Oui' : '']));
                    $results[] = $this->q('Q-FUA-10', 'warning', self::FU_QUERY_CODES['Q-FUA-10'][1],
                        "Ménage {$id} : le foyer déclare s'être procuré des moustiquaires depuis la baseline (autre_moustiquaire = Oui) mais {$what}.",
                        "Compléter le nombre de moustiquaires obtenues et leur(s) source(s).",
                        $ctx('formulaire_avant_suivi', ['field' => 'nb_total_moustiquaire / oth_net_src_*', 'value' => 'nb=' . (($m['nb_total_moustiquaire'] ?? '') ?: 'vide')]));
                }
            }

            // ── Complétude des formulaires (suivi réalisé) ──
            if ($fuDone === '1') {
                if (!$hasVisite) {
                    $results[] = $this->q('Q-FUA-11', 'warning', self::FU_QUERY_CODES['Q-FUA-11'][1],
                        "Ménage {$id} : le suivi {$evLabel} est réalisé mais le formulaire Visite Ménage n'est pas rempli à cet événement.",
                        "Remplir le formulaire Visite Ménage (date, agent, tablette, GPS, statut de visite).",
                        $ctx('form_1a_visite_menage', ['field' => 'a_date_visite_id / visit_status', 'value' => 'vide']));
                }
                if (!$hasQb) {
                    $results[] = $this->q('Q-FUA-12', 'warning', self::FU_QUERY_CODES['Q-FUA-12'][1],
                        "Ménage {$id} : le suivi {$evLabel} est réalisé mais le Questionnaire Base n'est pas rempli à cet événement.",
                        "Remplir le Questionnaire Base du suivi.",
                        $ctx('questionnaire_base', ['field' => 'b_date_visit / hh_members_n', 'value' => 'vide']));
                }
                $memBase = $baseMembers[$id] ?? 0;
                if ($mem->count() < $memBase || $mem->isEmpty()) {
                    $results[] = $this->q('Q-FUA-13', 'warning', self::FU_QUERY_CODES['Q-FUA-13'][1],
                        "Ménage {$id} : {$mem->count()} membre(s) mis à jour au {$evLabel} pour {$memBase} membre(s) enregistré(s) à la baseline. Le statut de chaque membre (présent, parti, décédé, absent) doit être mis à jour.",
                        "Mettre à jour la liste des membres du ménage : une ligne par membre de la baseline, plus les nouveaux membres.",
                        $ctx(self::FU_MEM, ['field' => 'section_1 (nombre)', 'value' => "Suivi : {$mem->count()}  |  Baseline : {$memBase}"]));
                }
                if ($f7->count() < $netsExp) {
                    $results[] = $this->q('Q-FUA-14', 'critical', self::FU_QUERY_CODES['Q-FUA-14'][1],
                        "Ménage {$id} : {$f7->count()} Form 7 saisi(s) au {$evLabel} pour {$netsExp} moustiquaire(s) de l'étude distribuée(s) à la baseline. Chaque moustiquaire doit avoir sa fiche Form 7, même absente.",
                        "Saisir un Form 7 pour chaque moustiquaire manquante (y compris celles qui ne sont plus présentes).",
                        $ctx(self::FU_F7, ['field' => 'form_7 (nombre)', 'value' => "Saisi : {$f7->count()}  |  Distribué : {$netsExp}"]));
                }
                if (!$hasF8) {
                    $results[] = $this->q('Q-FUA-15', 'critical', self::FU_QUERY_CODES['Q-FUA-15'][1],
                        "Ménage {$id} : le suivi {$evLabel} est réalisé mais le Form 8 (« Avez-vous d'autres moustiquaires ? ») n'est pas renseigné.",
                        "Renseigner le Form 8 (fu_check_other_nets) et, si Oui, le nombre d'autres moustiquaires.",
                        $ctx('form_8_vrification_autres_moustiquaires', ['field' => 'fu_check_other_nets', 'value' => 'vide']));
                }
                if ($otherYes && $otherN > 0 && $f9->count() < $otherN) {
                    $results[] = $this->q('Q-FUA-16', 'warning', self::FU_QUERY_CODES['Q-FUA-16'][1],
                        "Ménage {$id} : {$otherN} autre(s) moustiquaire(s) déclarée(s) au Form 8 mais seulement {$f9->count()} Form 9 saisi(s).",
                        "Saisir un Form 9 pour chaque autre moustiquaire du ménage, ou corriger le nombre déclaré au Form 8.",
                        $ctx(self::FU_F9, ['field' => 'fu_other_nets_n / form_9 (nombre)', 'value' => "Déclaré : {$otherN}  |  Saisi : {$f9->count()}"]));
                }
            }
            if (!$otherYes && $f9->isNotEmpty()) {
                $results[] = $this->q('Q-FUA-17', 'warning', self::FU_QUERY_CODES['Q-FUA-17'][1],
                    "Ménage {$id} : {$f9->count()} Form 9 saisi(s) alors que le Form 8 n'indique pas d'autres moustiquaires (fu_check_other_nets = " . (($m['fu_check_other_nets'] ?? '') ?: 'vide') . ").",
                    "Corriger le Form 8 à Oui (avec le nombre) si le ménage possède d'autres moustiquaires, sinon supprimer les Form 9.",
                    $ctx(self::FU_F9, ['field' => 'fu_check_other_nets', 'value' => ($m['fu_check_other_nets'] ?? '') ?: 'vide']));
            }
            if ($otherYes && $otherN <= 0) {
                $results[] = $this->q('Q-FUA-18', 'warning', self::FU_QUERY_CODES['Q-FUA-18'][1],
                    "Ménage {$id} : le Form 8 indique d'autres moustiquaires (Oui) mais leur nombre (fu_other_nets_n) est vide ou nul.",
                    "Renseigner le nombre d'autres moustiquaires.",
                    $ctx('form_8_vrification_autres_moustiquaires', ['field' => 'fu_other_nets_n', 'value' => ($m['fu_other_nets_n'] ?? '') ?: 'vide']));
            }

            // ── Form 7 (par moustiquaire) ──
            $results = array_merge($results, $this->checkForm7($f7, $id, $event, $expected, $baseNets[$id] ?? [], $baseDate, $ctx,
                max($mem->count(), $baseMembers[$id] ?? 0, (int) ($m['hh_members_n'] ?? 0))));

            // ── Form 9 ──
            foreach ($f9 as $r) {
                $inst = $r['redcap_repeat_instance'];
                if ($otherYes && ($r['other_household_nets'] ?? '') === '') {
                    $results[] = $this->q('Q-FUA-40', 'warning', self::FU_QUERY_CODES['Q-FUA-40'][1],
                        "Ménage {$id}, Form 9 #{$inst} : la question « Pouvez-vous me montrer la moustiquaire ? » (other_household_nets) n'est pas renseignée.",
                        "Indiquer si la moustiquaire a été observée, puis compléter la fiche.",
                        $ctx(self::FU_F9, ['instance' => $inst, 'field' => 'other_household_nets', 'value' => 'vide']));
                }
                if (($r['other_net_used_last_night'] ?? '') === '1' && in_array($r['other_net_use_lastweek'] ?? '', self::FU_NOT_USED_WEEK, true)) {
                    $results[] = $this->q('Q-FUA-41', 'critical', self::FU_QUERY_CODES['Q-FUA-41'][1],
                        "Ménage {$id}, Form 9 #{$inst} : quelqu'un a dormi sous la moustiquaire la nuit dernière (Q122 = Oui) mais elle est déclarée non utilisée la semaine dernière (Q125 = {$r['other_net_use_lastweek']}).",
                        "Corriger l'une des deux réponses.",
                        $ctx(self::FU_F9, ['instance' => $inst, 'field' => 'other_net_used_last_night / other_net_use_lastweek', 'value' => "1 / {$r['other_net_use_lastweek']}"]));
                }
            }

            // ── Membres du ménage ──
            foreach ($mem as $r) {
                $inst = $r['redcap_repeat_instance'];
                $name = trim($r['c_hhm_name'] ?? '') ?: "membre #{$inst}";
                $status = $r['member_status'] ?? '';
                if ($status === '') {
                    $results[] = $this->q('Q-FUA-50', 'warning', self::FU_QUERY_CODES['Q-FUA-50'][1],
                        "Ménage {$id}, {$name} : le statut actuel du membre (présent, parti, décédé, absence temporaire) n'est pas renseigné au {$evLabel}.",
                        "Renseigner member_status pour ce membre.",
                        $ctx(self::FU_MEM, ['instance' => $inst, 'field' => 'member_status', 'value' => 'vide']));
                } elseif ($status !== '1' && (($r['member_status_date'] ?? '') === '' || trim($r['member_status_reason'] ?? '') === '')) {
                    $results[] = $this->q('Q-FUA-51', 'warning', self::FU_QUERY_CODES['Q-FUA-51'][1],
                        "Ménage {$id}, {$name} : changement de statut (member_status = {$status}) sans date et/ou sans raison.",
                        "Compléter la date du changement (member_status_date) et la raison (member_status_reason).",
                        $ctx(self::FU_MEM, ['instance' => $inst, 'field' => 'member_status_date / member_status_reason',
                            'value' => 'date=' . (($r['member_status_date'] ?? '') ?: 'vide') . ', raison=' . (trim($r['member_status_reason'] ?? '') !== '' ? 'ok' : 'vide')]));
                }
                if (($r['member_baseline'] ?? '') === '0' && ($r['member_join_date'] ?? '') === '') {
                    $results[] = $this->q('Q-FUA-52', 'warning', self::FU_QUERY_CODES['Q-FUA-52'][1],
                        "Ménage {$id}, {$name} : nouveau membre (absent de la baseline) sans date d'entrée dans le ménage.",
                        "Renseigner member_join_date.",
                        $ctx(self::FU_MEM, ['instance' => $inst, 'field' => 'member_join_date', 'value' => 'vide']));
                }
            }

            // ── Questionnaire Base : nombre de membres ──
            $declared = $m['hh_members_n'] ?? '';
            if ($declared !== '' && $mem->isNotEmpty() && $mem->every(fn($r) => ($r['member_status'] ?? '') !== '')) {
                $present = $mem->whereIn('member_status', ['1', '4'])->count();
                if ((int) $declared !== $present) {
                    $results[] = $this->q('Q-FUA-53', 'warning', self::FU_QUERY_CODES['Q-FUA-53'][1],
                        "Ménage {$id} : le Questionnaire Base du {$evLabel} déclare {$declared} membre(s) (hh_members_n) alors que {$present} membre(s) sont présents ou temporairement absents dans la liste des membres.",
                        "Corriger hh_members_n ou mettre à jour le statut des membres.",
                        $ctx('questionnaire_base', ['field' => 'hh_members_n / member_status', 'value' => "Déclaré : {$declared}  |  Liste : {$present}"]));
                }
            }
        }
        return $results;
    }

    private function checkForm7(Collection $f7, string $id, string $event, string $expected, array $nets, string $baseDate, \Closure $ctx, int $maxMember): array
    {
        $results = [];
        $codesSeen = [];
        $numsSeen  = [];
        $dupReported = false;

        foreach ($f7 as $r) {
            $inst  = $r['redcap_repeat_instance'];
            $code  = self::fuNorm($r['fu_itn_id_saisie'] ?? '');
            $num   = (string) ($r['fu_num_hh_itn'] ?? '');
            $label = $code !== '' ? $code : ($num !== '' ? "n° {$num}" : "#{$inst}");
            $c     = fn(string $field, string $value) => $ctx(self::FU_F7, [
                'instance' => $inst, 'field' => $field, 'value' => $value,
                'extra'    => "ID filet : {$label} | Date : " . (($r['afw_date'] ?? '') ?: '—'),
                'initials' => strtoupper(($r['a_fw_initials'] ?? '') ?: $ctx(self::FU_F7)['initials']),
            ]);
            $present = $r['net_present'] ?? '';
            $used    = $r['net_used_last_night'] ?? '';
            $use7    = $r['itn_utilisation_7j'] ?? '';

            if (($r['attrition_followup_evt'] ?? '') !== '' && $r['attrition_followup_evt'] !== $expected) {
                $results[] = $this->q('Q-FUA-20', 'critical', self::FU_QUERY_CODES['Q-FUA-20'][1],
                    "Ménage {$id}, Form 7 ({$label}) : le moment du suivi déclaré (attrition_followup_evt = {$r['attrition_followup_evt']}) ne correspond pas à l'événement REDCap (code attendu {$expected}).",
                    "Corriger le moment du suivi ou déplacer la fiche vers le bon événement.",
                    $c('attrition_followup_evt', "{$r['attrition_followup_evt']} (attendu {$expected})"));
            }
            if (($issue = self::fuDateIssue($r['afw_date'] ?? '', $baseDate)) && ($r['afw_date'] ?? '') !== '') {
                $results[] = $this->q('Q-FUA-04', 'critical', self::FU_QUERY_CODES['Q-FUA-04'][1],
                    "Ménage {$id}, Form 7 ({$label}) : {$issue}.",
                    "Corriger la date de suivi (afw_date).",
                    $c('afw_date', $r['afw_date']));
            }
            if ($code === '') {
                $results[] = $this->q('Q-FUA-21', 'critical', self::FU_QUERY_CODES['Q-FUA-21'][1],
                    "Ménage {$id}, Form 7 #{$inst} : le code de la moustiquaire (fu_itn_id_saisie) n'est pas saisi. Sans code, la moustiquaire ne peut pas être reliée à la distribution.",
                    "Saisir le code imprimé sur l'étiquette de la moustiquaire.",
                    $c('fu_itn_id_saisie', 'vide'));
            } elseif (!empty($nets) && !in_array($code, $nets, true)) {
                $results[] = $this->q('Q-FUA-22', 'critical', self::FU_QUERY_CODES['Q-FUA-22'][1],
                    "Ménage {$id}, Form 7 #{$inst} : le code {$code} ne fait pas partie des moustiquaires distribuées à ce ménage à la baseline (" . implode(', ', array_filter($nets)) . ").",
                    "Vérifier le code sur l'étiquette (erreur de frappe probable) et le corriger.",
                    $c('fu_itn_id_saisie', $code));
            } elseif ($num !== '' && isset($nets[$num]) && $nets[$num] !== '' && $nets[$num] !== $code) {
                $results[] = $this->q('Q-FUA-23', 'warning', self::FU_QUERY_CODES['Q-FUA-23'][1],
                    "Ménage {$id}, Form 7 #{$inst} : la moustiquaire n° {$num} correspond au code {$nets[$num]} à la baseline, mais le code saisi est {$code}.",
                    "Corriger le numéro de la moustiquaire (fu_num_hh_itn) ou le code saisi.",
                    $c('fu_num_hh_itn / fu_itn_id_saisie', "n° {$num} / {$code}"));
            }
            if (!$dupReported && (($code !== '' && isset($codesSeen[$code])) || ($num !== '' && isset($numsSeen[$num])))) {
                $dupReported = true;
                $results[] = $this->q('Q-FUA-24', 'critical', self::FU_QUERY_CODES['Q-FUA-24'][1],
                    "Ménage {$id} : la moustiquaire {$label} est saisie dans plusieurs Form 7 au même suivi (instances " . ($codesSeen[$code] ?? $numsSeen[$num]) . " et {$inst}).",
                    "Supprimer la fiche en double ou corriger le numéro / code de la moustiquaire.",
                    $c('fu_num_hh_itn / fu_itn_id_saisie', $label));
            }
            if ($code !== '') $codesSeen[$code] ??= $inst;
            if ($num !== '')  $numsSeen[$num]   ??= $inst;

            if ($present === '') {
                $results[] = $this->q('Q-FUA-25', 'critical', self::FU_QUERY_CODES['Q-FUA-25'][1],
                    "Ménage {$id}, Form 7 ({$label}) : la question Q5 « La moustiquaire est-elle toujours présente ? » (net_present) n'est pas renseignée. Toute la suite de la fiche en dépend.",
                    "Renseigner net_present puis compléter la fiche.",
                    $c('net_present', 'vide'));
                continue;
            }
            if ($present === '0') {
                if (self::fuFilled($r, ['net_location', 'net_used_last_night', 'itn_utilisation_7j', 'itn_has_holes', 'itn_ever_washed'])) {
                    $results[] = $this->q('Q-FUA-26', 'warning', self::FU_QUERY_CODES['Q-FUA-26'][1],
                        "Ménage {$id}, Form 7 ({$label}) : la moustiquaire est déclarée absente (net_present = Non) mais des réponses sur son emplacement, son utilisation ou son état sont enregistrées (valeurs restées après changement de réponse).",
                        "Vérifier la présence : si elle était présente, corriger net_present à Oui ; sinon effacer les réponses masquées.",
                        $c('net_present / net_location / net_used_last_night / itn_has_holes', 'net_present=0, réponses présentes'));
                }
                continue;
            }

            if ($used === '1' && in_array($use7, self::FU_NOT_USED_WEEK, true)) {
                $results[] = $this->q('Q-FUA-27', 'critical', self::FU_QUERY_CODES['Q-FUA-27'][1],
                    "Ménage {$id}, Form 7 ({$label}) : quelqu'un a dormi sous la moustiquaire la nuit dernière (Q12 = Oui) mais elle est déclarée non utilisée la semaine dernière (Q15 = {$use7}).",
                    "Corriger Q12 ou Q15.",
                    $c('net_used_last_night / itn_utilisation_7j', "1 / {$use7}"));
            }
            if ($use7 === '1' && $used === '0') {
                $results[] = $this->q('Q-FUA-28', 'warning', self::FU_QUERY_CODES['Q-FUA-28'][1],
                    "Ménage {$id}, Form 7 ({$label}) : la moustiquaire est déclarée utilisée chaque nuit (Q15 = 7 nuits) mais personne n'a dormi dessous la nuit dernière (Q12 = Non).",
                    "Vérifier les deux réponses avec l'agent.",
                    $c('net_used_last_night / itn_utilisation_7j', '0 / 1'));
            }
            if ($used === '1' && in_array($r['net_location'] ?? '', ['4', '5'], true)) {
                $results[] = $this->q('Q-FUA-29', 'warning', self::FU_QUERY_CODES['Q-FUA-29'][1],
                    "Ménage {$id}, Form 7 ({$label}) : la moustiquaire a été trouvée rangée (Q9 = {$r['net_location']}) alors que quelqu'un aurait dormi dessous la nuit dernière.",
                    "Vérifier l'emplacement observé et l'utilisation déclarée.",
                    $c('net_location / net_used_last_night', "{$r['net_location']} / 1"));
            }
            if ($used === '1') {
                $nPers = (int) ($r['fu_num_pers_net'] ?? 0);
                if ($nPers <= 0) {
                    $results[] = $this->q('Q-FUA-30', 'warning', self::FU_QUERY_CODES['Q-FUA-30'][1],
                        "Ménage {$id}, Form 7 ({$label}) : quelqu'un a dormi sous la moustiquaire la nuit dernière mais le nombre de personnes (Q14) est vide ou nul.",
                        "Renseigner le nombre de personnes et leur numéro dans le ménage.",
                        $c('fu_num_pers_net', ($r['fu_num_pers_net'] ?? '') ?: 'vide'));
                } else {
                    $persons = [];
                    for ($i = 1; $i <= min($nPers, 5); $i++) {
                        $p = $r["fu_hh_pers{$i}_num"] ?? '';
                        if ($p !== '') $persons[] = $p;
                    }
                    if (($r['bed_net_duplicate_check'] ?? '') === '1' || count($persons) !== count(array_unique($persons))) {
                        $results[] = $this->q('Q-FUA-31', 'warning', self::FU_QUERY_CODES['Q-FUA-31'][1],
                            "Ménage {$id}, Form 7 ({$label}) : la même personne est sélectionnée plusieurs fois parmi les dormeurs (" . implode(', ', $persons) . ").",
                            "Corriger les numéros des personnes ayant dormi sous la moustiquaire.",
                            $c('fu_hh_pers1_num … fu_hh_pers5_num', implode(', ', $persons)));
                    }
                    $tooHigh = $maxMember > 0 ? array_filter($persons, fn($p) => (int) $p > $maxMember) : [];
                    if ($tooHigh) {
                        $results[] = $this->q('Q-FUA-32', 'warning', self::FU_QUERY_CODES['Q-FUA-32'][1],
                            "Ménage {$id}, Form 7 ({$label}) : numéro(s) de personne " . implode(', ', $tooHigh) . " supérieur(s) au nombre de membres du ménage ({$maxMember}).",
                            "Vérifier le numéro de la personne dans la liste des membres, ou ajouter le membre manquant.",
                            $c('fu_hh_persN_num', implode(', ', $tooHigh) . " (max {$maxMember})"));
                    }
                }
            }
            if (($r['itn_has_holes'] ?? '') === '1' && ($r['net_location'] ?? '') !== '6') {
                $total = (int) ($r['itn_hole_size1'] ?? 0) + (int) ($r['itn_hole_size2'] ?? 0)
                       + (int) ($r['itn_hole_size3'] ?? 0) + (int) ($r['itn_hole_size4'] ?? 0);
                $repaired = (int) ($r['itn_hole_repaired_full'] ?? 0) + (int) ($r['itn_hole_repaired_partial'] ?? 0);
                if ($total === 0 && $repaired === 0) {
                    $results[] = $this->q('Q-FUA-33', 'warning', self::FU_QUERY_CODES['Q-FUA-33'][1],
                        "Ménage {$id}, Form 7 ({$label}) : la moustiquaire a des trous (Q21 = Oui) mais aucun trou n'est compté (tailles 1 à 4 = 0 ou vides). Le pHI ne peut pas être calculé.",
                        "Compter les trous par taille, ou corriger Q21 si la moustiquaire n'a pas de trou.",
                        $c('itn_hole_size1 … itn_hole_size4', '0'));
                } elseif ($total > 0 && (int) ($r['itn_hole_repaired_full'] ?? 0) > $total) {
                    $results[] = $this->q('Q-FUA-34', 'warning', self::FU_QUERY_CODES['Q-FUA-34'][1],
                        "Ménage {$id}, Form 7 ({$label}) : {$r['itn_hole_repaired_full']} trou(s) complètement réparé(s) pour {$total} trou(s) compté(s).",
                        "Vérifier le comptage des trous et des réparations.",
                        $c('itn_hole_repaired_full / itn_hole_size*', "{$r['itn_hole_repaired_full']} / {$total}"));
                }
            }
        }

        if (!empty($nets) && $f7->count() > count($nets)) {
            $results[] = $this->q('Q-FUA-35', 'warning', self::FU_QUERY_CODES['Q-FUA-35'][1],
                "Ménage {$id} : {$f7->count()} Form 7 saisis pour " . count($nets) . " moustiquaire(s) de l'étude distribuée(s) à la baseline.",
                "Rechercher les fiches en double ou saisies pour une autre moustiquaire.",
                $ctx(self::FU_F7, ['field' => 'form_7 (nombre)', 'value' => "Saisi : {$f7->count()}  |  Distribué : " . count($nets)]));
        }
        return $results;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // COHORTE B — Form 10 (les suivis sont traités dans l'ordre chronologique)
    // ══════════════════════════════════════════════════════════════════════════

    /** @param array $rawByEvent [event => raw rows] */
    private function checkCohortBFollowUp(array $rawByEvent, Collection $visits, array $baseNets): array
    {
        $results     = [];
        $withdrawn   = [];   // code => libellé du suivi où il a été prélevé
        $hhExtra     = [];   // household_id => [codes de remplacement reçus aux suivis précédents]
        $allBaseline = [];
        foreach ($baseNets as $id => $nets) foreach ($nets as $c) if ($c !== '') $allBaseline[$c] = $id;
        $replUsed = [];      // code de remplacement => [household_id, libellé]

        foreach (RedCapService::COHORT_B_EVENTS as $event) {
            $expected = self::FU_B_EVENT_CODE[$event];
            $evLabel  = self::fuEventLabel($event);
            $rows     = $this->form10Rows($rawByEvent[$event] ?? []);
            $newWithdrawn = [];
            $newRepl      = [];

            foreach ($rows->groupBy('household_id') as $id => $f10) {
                $hh = $this->visitOf($visits, $id) ?? [];
                $baseCtx = fn(array $r, array $extra) => array_merge([
                    'event'        => $event,
                    'form'         => self::FU_F10,
                    'instance'     => $r['redcap_repeat_instance'] ?? '',
                    'household_id' => $id,
                    'tablet'       => $hh['a_tablette_id'] ?? '',
                    'initials'     => strtoupper(($r['fw_cb_initials'] ?? '') ?: ($hh['a_initiales_id'] ?? '')),
                ], $extra);

                if (($hh['consent_accepted'] ?? '') !== '1' || ($hh['study_cohort'] ?? '') !== '2') {
                    $cohort = match ($hh['study_cohort'] ?? '') { '1' => 'Cohorte A', '2' => 'Cohorte B (non consenti)', default => 'inconnu à la baseline' };
                    $results[] = $this->q('Q-FUB-01', 'warning', self::FU_QUERY_CODES['Q-FUB-01'][1],
                        "Au {$evLabel}, {$f10->count()} Form 10 saisi(s) pour le ménage {$id}, qui n'est pas un ménage consenti de la Cohorte B ({$cohort}). Le Form 10 est réservé à la Cohorte B.",
                        "Vérifier l'identifiant du ménage. Pour un ménage Cohorte A, utiliser le Formulaire Avant Suivi et les Form 7, 8, 9.",
                        $baseCtx($f10->first(), ['field' => 'study_cohort (baseline)', 'value' => $cohort]));
                    continue;
                }

                $valid    = array_values(array_filter(array_merge($baseNets[$id] ?? [], $hhExtra[$id] ?? [])));
                $seen     = [];
                $baseDate = $hh['a_date_visite_id'] ?? '';

                foreach ($f10 as $r) {
                    $code  = self::fuNorm(($r['itn_unique_id_scan'] ?? '') ?: ($r['itn_unique_id_saisi'] ?? ''));
                    $repl  = self::fuNorm(($r['itn_unique_id_scan_2'] ?? '') ?: ($r['itn_unique_id_saisi_2'] ?? ''));
                    $inst  = $r['redcap_repeat_instance'] ?? '';
                    $label = $code !== '' ? $code : ('#' . ($inst ?: '1'));
                    $c     = fn(string $field, string $value) => $baseCtx($r, [
                        'field' => $field, 'value' => $value,
                        'extra' => "ID filet : {$label} | Remplacement : " . ($repl ?: '—') . ' | Date : ' . (($r['fw_cb_date_visit'] ?? '') ?: '—'),
                    ]);
                    $verified = $r['itn_study_verified'] ?? '';

                    if (($r['cb_followup_event'] ?? '') !== '' && $r['cb_followup_event'] !== $expected) {
                        $results[] = $this->q('Q-FUB-02', 'critical', self::FU_QUERY_CODES['Q-FUB-02'][1],
                            "Ménage {$id}, Form 10 ({$label}) : le moment du suivi déclaré (cb_followup_event = {$r['cb_followup_event']}) ne correspond pas à l'événement REDCap {$evLabel} (code attendu {$expected}).",
                            "Corriger le moment du suivi ou déplacer la fiche vers le bon événement.",
                            $c('cb_followup_event', "{$r['cb_followup_event']} (attendu {$expected})"));
                    }
                    if ($issue = self::fuDateIssue($r['fw_cb_date_visit'] ?? '', $baseDate)) {
                        $results[] = $this->q('Q-FUB-03', 'critical', self::FU_QUERY_CODES['Q-FUB-03'][1],
                            "Ménage {$id}, Form 10 ({$label}) : {$issue}.",
                            "Corriger la date de visite du suivi (fw_cb_date_visit).",
                            $c('fw_cb_date_visit', ($r['fw_cb_date_visit'] ?? '') ?: 'vide'));
                    }
                    if ($code === '') {
                        $results[] = $this->q('Q-FUB-04', 'critical', self::FU_QUERY_CODES['Q-FUB-04'][1],
                            "Ménage {$id}, Form 10 : le code de la moustiquaire prélevée n'est ni scanné ni saisi.",
                            "Scanner le QR code ou saisir le code de la moustiquaire prélevée.",
                            $c('itn_unique_id_scan / itn_unique_id_saisi', 'vide'));
                    } else {
                        if (isset($withdrawn[$code])) {
                            $results[] = $this->q('Q-FUB-07', 'critical', self::FU_QUERY_CODES['Q-FUB-07'][1],
                                "Ménage {$id}, Form 10 : la moustiquaire {$code} a déjà été prélevée au {$withdrawn[$code]}. Une moustiquaire prélevée ne peut pas être prélevée à nouveau.",
                                "Vérifier le code scanné ; au suivi précédent, la moustiquaire a été remplacée par une nouvelle (code de remplacement).",
                                $c('itn_unique_id_scan / itn_unique_id_saisi', $code));
                        } elseif (!empty($valid) && !in_array($code, $valid, true)) {
                            $results[] = $this->q('Q-FUB-05', 'critical', self::FU_QUERY_CODES['Q-FUB-05'][1],
                                "Ménage {$id}, Form 10 : le code {$code} ne correspond à aucune moustiquaire du ménage (distribuées à la baseline ou reçues en remplacement : " . implode(', ', $valid) . ").",
                                "Vérifier le code (erreur de saisie probable) ou l'identifiant du ménage.",
                                $c('itn_unique_id_scan / itn_unique_id_saisi', $code));
                        }
                        if (isset($seen[$code])) {
                            $results[] = $this->q('Q-FUB-06', 'critical', self::FU_QUERY_CODES['Q-FUB-06'][1],
                                "Ménage {$id} : la moustiquaire {$code} est saisie dans plusieurs Form 10 au {$evLabel}.",
                                "Supprimer la fiche en double ou corriger le code.",
                                $c('itn_unique_id_scan / itn_unique_id_saisi', $code));
                        }
                        $seen[$code] = true;
                        $newWithdrawn[$code] = true;
                    }

                    if ($verified === '') {
                        $results[] = $this->q('Q-FUB-08', 'critical', self::FU_QUERY_CODES['Q-FUB-08'][1],
                            "Ménage {$id}, Form 10 ({$label}) : la question Q01 « Cette moustiquaire a-t-elle été reçue dans le cadre de l'étude ? » (itn_study_verified) n'est pas renseignée.",
                            "Renseigner itn_study_verified puis compléter la fiche.",
                            $c('itn_study_verified', 'vide'));
                        continue;
                    }
                    if ($verified === '0') {
                        if (self::fuFilled($r, ['itn_found_location', 'itn_used_last_night', 'itn_use_lastweek', 'fwb_itn_ever_washed', 'itn_main_user'])) {
                            $results[] = $this->q('Q-FUB-12', 'warning', self::FU_QUERY_CODES['Q-FUB-12'][1],
                                "Ménage {$id}, Form 10 ({$label}) : la moustiquaire n'est pas vérifiée comme moustiquaire de l'étude (Q01 = Non) mais des réponses sur son emplacement, son utilisation ou son lavage sont enregistrées.",
                                "Vérifier Q01 : si la moustiquaire est bien de l'étude, corriger à Oui ; sinon effacer les réponses masquées.",
                                $c('itn_study_verified / itn_used_last_night', 'itn_study_verified=0, réponses présentes'));
                        }
                        continue;
                    }

                    // Moustiquaire vérifiée → doit être remplacée
                    if ($repl === '') {
                        $results[] = $this->q('Q-FUB-09', 'critical', self::FU_QUERY_CODES['Q-FUB-09'][1],
                            "Ménage {$id}, Form 10 ({$label}) : la moustiquaire est prélevée mais le code de la moustiquaire de remplacement n'est pas saisi. La nouvelle moustiquaire ne pourra pas être suivie.",
                            "Scanner ou saisir le code de la moustiquaire de remplacement remise au ménage.",
                            $c('itn_unique_id_scan_2 / itn_unique_id_saisi_2', 'vide'));
                    } elseif ($repl === $code) {
                        $results[] = $this->q('Q-FUB-10', 'critical', self::FU_QUERY_CODES['Q-FUB-10'][1],
                            "Ménage {$id}, Form 10 : le code de remplacement ({$repl}) est identique au code de la moustiquaire prélevée.",
                            "Scanner le code de la NOUVELLE moustiquaire remise au ménage.",
                            $c('itn_unique_id_scan_2 / itn_unique_id_saisi_2', $repl));
                    } elseif (isset($allBaseline[$repl]) || isset($replUsed[$repl]) || isset($newRepl[$repl])) {
                        $where = isset($allBaseline[$repl]) ? "distribuée à la baseline au ménage {$allBaseline[$repl]}"
                            : (isset($replUsed[$repl]) ? "déjà remise en remplacement au ménage {$replUsed[$repl][0]} ({$replUsed[$repl][1]})"
                            : "déjà remise en remplacement au ménage {$newRepl[$repl]} ({$evLabel})");
                        $results[] = $this->q('Q-FUB-11', 'critical', self::FU_QUERY_CODES['Q-FUB-11'][1],
                            "Ménage {$id}, Form 10 ({$label}) : le code de remplacement {$repl} est déjà attribué (moustiquaire {$where}).",
                            "Vérifier le code de la moustiquaire de remplacement (doublon de scan probable).",
                            $c('itn_unique_id_scan_2 / itn_unique_id_saisi_2', $repl));
                    }
                    if ($repl !== '' && $repl !== $code) {
                        $newRepl[$repl] ??= $id;
                    }

                    $used = $r['itn_used_last_night'] ?? '';
                    $use7 = $r['itn_use_lastweek'] ?? '';
                    if ($used === '1' && in_array($use7, self::FU_NOT_USED_WEEK, true)) {
                        $results[] = $this->q('Q-FUB-13', 'critical', self::FU_QUERY_CODES['Q-FUB-13'][1],
                            "Ménage {$id}, Form 10 ({$label}) : quelqu'un a dormi sous la moustiquaire la nuit dernière (Q04 = Oui) mais elle est déclarée non utilisée la semaine dernière (Q06 = {$use7}).",
                            "Corriger Q04 ou Q06.",
                            $c('itn_used_last_night / itn_use_lastweek', "1 / {$use7}"));
                    }
                    if ($use7 === '1' && $used === '0') {
                        $results[] = $this->q('Q-FUB-14', 'warning', self::FU_QUERY_CODES['Q-FUB-14'][1],
                            "Ménage {$id}, Form 10 ({$label}) : la moustiquaire est déclarée utilisée chaque nuit (Q06) mais personne n'a dormi dessous la nuit dernière (Q04 = Non).",
                            "Vérifier les deux réponses avec l'agent.",
                            $c('itn_used_last_night / itn_use_lastweek', '0 / 1'));
                    }
                }

                $expectedNets = count($baseNets[$id] ?? []);
                if ($expectedNets > 0 && count($seen) < $expectedNets) {
                    $results[] = $this->q('Q-FUB-15', 'info', self::FU_QUERY_CODES['Q-FUB-15'][1],
                        "Ménage {$id} : " . count($seen) . " moustiquaire(s) prélevée(s) au {$evLabel} sur {$expectedNets} distribuée(s). Le suivi du ménage est partiel.",
                        "Compléter un Form 10 pour chaque moustiquaire restante du ménage lors d'un passage ultérieur.",
                        $baseCtx($f10->first(), ['instance' => '', 'field' => 'form_10 (nombre)', 'value' => count($seen) . " / {$expectedNets}"]));
                }
            }

            // Propagation vers les suivis suivants
            foreach (array_keys($newWithdrawn) as $code) $withdrawn[$code] ??= $evLabel;
            foreach ($newRepl as $code => $id) {
                $hhExtra[$id][] = $code;
                $replUsed[$code] ??= [$id, $evLabel];
            }
        }
        return $results;
    }

    /** Lignes Form 10 : instances répétées, ou ligne non répétée quand le formulaire n'est pas répétable à l'événement */
    private function form10Rows(array $raw): Collection
    {
        $keys = ['fw_cb_date_visit', 'itn_study_verified', 'itn_unique_id_scan', 'itn_unique_id_saisi', 'cb_followup_event'];
        return collect($raw)->filter(fn($r) => ($r['household_id'] ?? '') !== '' && (
            (($r['redcap_repeat_instrument'] ?? '') === self::FU_F10 && ($r['redcap_repeat_instance'] ?? '') !== '')
            || (($r['redcap_repeat_instrument'] ?? '') === '' && self::fuFilled($r, $keys))
        ))->values();
    }
}
