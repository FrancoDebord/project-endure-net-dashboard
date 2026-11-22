<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RedCapService
{
    private string $apiUrl;
    private string $token;

    public function __construct()
    {
        $this->apiUrl = config('redcap.api_url');
        $this->token  = config('redcap.token');
    }

    private function request(array $params): mixed
    {
        $response = Http::withoutVerifying()
            ->timeout(120)
            ->retry(2, 3000)
            ->asForm()
            ->post($this->apiUrl, array_merge([
                'token'  => $this->token,
                'format' => 'json',
            ], $params));

        $response->throw();
        return $response->json();
    }

    // ── Exports par lots ────────────────────────────────────────────────────
    // Les gros exports (membres, moustiquaires…) dépassent le délai de REDCap
    // lorsqu'ils portent sur tous les ménages d'un coup. On les découpe par lots
    // de ménages, exécutés en parallèle, puis on fusionne les résultats.

    private const BATCH_SIZE  = 400;
    private const CONCURRENCY = 6;

    /** Formulaires exportés par lots (l'export complet dépasse le délai REDCap) */
    private const BATCHED_FORMS = [
        'section_1_membres_du_mnage',
        'form_3b_anciennes_moustiquaires_du_mnage',
        'section_5_moustiquaires_imprgnes_dinsecticide_appa',
    ];

    /**
     * Durée de cache (heures) des données des phases terminées (baseline, distribution, AE, contacts).
     * Les suivis restent à 30 min. Le bouton « Actualiser » vide tout le cache.
     */
    private const TTL_BASELINE_H = 6;

    /** Identifiants de tous les ménages (export léger : household_id seul, ~5 s) */
    private function recordIds(): array
    {
        return Cache::remember('redcap.record_ids', now()->addHours(self::TTL_BASELINE_H), function () {
            if (Cache::has('redcap.identification')) {
                return collect(Cache::get('redcap.identification'))->pluck('household_id')->filter()->unique()->values()->all();
            }
            return collect($this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1', 'fields' => 'household_id',
            ]))->pluck('household_id')->filter()->unique()->values()->all();
        });
    }

    /** Export d'un jeu de données, découpé par lots si nécessaire */
    private function requestBatched(array $params): array
    {
        $rows = $this->fetchMany(['x' => $params])['x'];
        if ($rows === null) {
            throw new \RuntimeException('REDCap : export échoué (' . ($params['forms'] ?? $params['content']) . ')');
        }
        return $rows;
    }

    /**
     * Exécute plusieurs exports en parallèle. Les exports de type « record »
     * (hors identification) sont découpés en lots de BATCH_SIZE ménages.
     * @return array<string, array|null>  null si au moins un lot a échoué
     */
    private function fetchMany(array $tasks): array
    {
        $ids  = null;
        $jobs = [];
        foreach ($tasks as $key => $params) {
            // Découpage réservé aux exports trop volumineux pour passer en une requête :
            // multiplier les lots sur les autres surcharge REDCap sans rien gagner.
            $batchable = ($params['content'] ?? '') === 'record' && empty($params['records'])
                && in_array($params['forms'] ?? '', self::BATCHED_FORMS, true);
            if ($batchable) {
                $ids ??= $this->recordIds();
            }
            if ($batchable && count($ids) > self::BATCH_SIZE) {
                foreach (array_chunk($ids, self::BATCH_SIZE) as $i => $chunk) {
                    $jobs["$key#$i"] = [$key, $params + ['records' => implode(',', $chunk)]];
                }
            } else {
                $jobs["$key#0"] = [$key, $params];
            }
        }
        if (empty($jobs)) return [];

        $responses = Http::pool(function ($pool) use ($jobs) {
            $reqs = [];
            foreach ($jobs as $jobKey => [, $params]) {
                $reqs[] = $pool->as($jobKey)
                    ->withoutVerifying()
                    ->timeout(180)
                    ->asForm()
                    ->post($this->apiUrl, array_merge(['token' => $this->token, 'format' => 'json'], $params));
            }
            return $reqs;
        }, self::CONCURRENCY);

        $out = array_fill_keys(array_keys($tasks), []);
        foreach ($jobs as $jobKey => [$key]) {
            if ($out[$key] === null) continue;
            $r = $responses[$jobKey] ?? null;
            $json = ($r && !($r instanceof \Throwable) && $r->successful()) ? $r->json() : null;
            if (!is_array($json) || isset($json['error'])) {
                $out[$key] = null;
                continue;
            }
            // Les exports « record » renvoient une liste ; les autres (projet, DAG) un objet
            $out[$key] = array_is_list($json) ? array_merge($out[$key], $json) : $json;
        }
        return $out;
    }

    // ── Infos projet ────────────────────────────────────────────────────────────

    public function getProjectInfo(): array
    {
        return Cache::remember('redcap.project_info', now()->addHour(), fn() =>
            $this->requestBatched(['content' => 'project'])
        );
    }

    public function getInstruments(): array
    {
        return Cache::remember('redcap.instruments', now()->addHour(), fn() =>
            $this->requestBatched(['content' => 'instrument'])
        );
    }

    public function getEvents(): array
    {
        return Cache::remember('redcap.events', now()->addHour(), fn() =>
            $this->requestBatched(['content' => 'event'])
        );
    }

    // ── Baseline & Distribution ──────────────────────────────────────────────

    public function getVisiteData(): array
    {
        return Cache::remember('redcap.visite', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_1a_visite_menage',
                'fields'  => implode(',', [
                    'household_id',
                    'a_date_visite_id', 'a_tablette_id', 'a_numero_tablette', 'a_initiales_id',
                    'number_visit', 'visit_status',
                ]),
            ])
        );
    }

    public function getIdentificationData(): array
    {
        return Cache::remember('redcap.identification', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_1a_identification',
                'exportDataAccessGroups' => 'true',
                'fields'  => implode(',', [
                    'household_id',
                    'village', 'cluster_djigbe', 'cluster_gbonou', 'cluster_miniffi',
                    'cluster_id', 'bras', 'study_cohort', 'numero_menage',
                    'consent_accepted',
                ]),
            ])
        );
    }

    public function getBaselineQuestionnaireData(): array
    {
        return Cache::remember('redcap.questionnaire_base', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                // No 'forms' filter: requesting by fields only forces REDCap to return
                // the full non-repeating baseline row with cross-form field access,
                // so household_id (defined in form_1a_identification) is populated.
                'fields'  => implode(',', [
                    'household_id', 'b_date_visit', 'b_tablette',
                    'b_geo_latitude', 'b_geo_longitude',
                    'a_household_eligible', 'consent_accepted',
                    'hh_members_n', 'moustiquaire', 'nb_moustiquaires',
                    'b_nb_pieces', 'b_nb_pieces_sleep', 'sleep_total_spaces',
                    'b_sleep_bed_count', 'b_sleep_bed_count_mat',
                    'b_sleep_mattress_count', 'b_sleep_carpet_other_count',
                    'b_sleep_mat_count', 'b_sleep_other_space_count',
                    'b_hh_educated', 'b_hh_education_level',
                ]),
            ])
        );
    }

    public function getMemberData(): array
    {
        return Cache::remember('redcap.members', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'section_1_membres_du_mnage',
                'fields'  => implode(',', [
                    'household_id', 'c_hhm_name', 'c_sex', 'c_age_years', 'c_live_here', 'c_spent_last_night',
                ]),
            ])
        );
    }

    public function getOldNetData(): array
    {
        return Cache::remember('redcap.old_nets', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_3b_anciennes_moustiquaires_du_mnage',
                'fields'  => implode(',', [
                    'household_id', 'd_old_net_brand', 'd_old_net_source', 'd_old_net_duration',
                ]),
            ])
        );
    }

    public function getStudyNetData(): array
    {
        return Cache::remember('redcap.study_nets', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'section_5_moustiquaires_imprgnes_dinsecticide_appa',
                'exportDataAccessGroups' => 'true',
                'fields'  => implode(',', [
                    'household_id', 'net_identifier_man', 'net_code_written',
                    'e_net_received_in_study', 'is_study_net', 'instance_net',
                ]),
            ])
        );
    }

    public function getForm4aData(): array
    {
        return Cache::remember('redcap.form4a', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_4a_renseigner_le_nombre_de_moustiquaires_donn',
                'fields'  => implode(',', [
                    'household_id', 'nb_moustiquaires_etude',
                ]),
            ])
        );
    }

    // Chef de ménage, contact, moment de visite et adresse (Form 1b Questionnaire Base Ménage)
    public function getForm1bData(): array
    {
        return Cache::remember('redcap.form1b', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_1b_questionnaire_base_menage',
                'fields'  => implode(',', [
                    'household_id',
                    'b_hhh_fullname',          // Nom du chef de ménage
                    'b_hhh_phone',             // Téléphone du chef de ménage
                    'b_hh_point_contact',      // Nom du point de contact
                    'b_hh_point_contact_phone',// Téléphone du point de contact
                    'b_hh_moment_visite',      // Meilleur moment pour visiter
                    'b_hh_address',            // Adresse du ménage
                ]),
            ])
        );
    }

    // ── User Acceptability ───────────────────────────────────────────────────

    public function getUserAcceptabilityData(string $event = 'user_acceptability_arm_1'): array
    {
        return Cache::remember("redcap.ua.$event", now()->addMinutes(30), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => $event,
                'forms'   => 'form_6_user_acceptability',
                'fields'  => implode(',', [
                    'household_id',
                    'ac_tablet', 'ac_date_interview',
                    'ac_hh_present',
                    'uac_resp_role',
                    'net_like', 'net_overall_satisfaction',
                    'net_continue_use', 'net_recommend',
                    'net_easy_use', 'net_size_adequate',
                    'net_design_importance', 'net_color_importance', 'net_taille_importance',
                    'net_easy_install',
                    'net_like_reason___1','net_like_reason___2','net_like_reason___3',
                    'net_like_reason___4','net_like_reason___5','net_like_reason___6',
                    'net_like_reason___7','net_like_reason___8','net_like_reason___99',
                    'net_dislike_reason___1','net_dislike_reason___2','net_dislike_reason___3',
                    'net_dislike_reason___4','net_dislike_reason___5','net_dislike_reason___6',
                    'net_dislike_reason___7','net_dislike_reason___8','net_dislike_reason___9',
                    'net_dislike_reason___10','net_dislike_reason___11','net_dislike_reason___12',
                    'net_dislike_reason___13','net_dislike_reason___99',
                    'net_continue_no_reason',
                    'net_use_issues', 'net_comparison_other', 'net_best_features',
                    'net_improvement_suggest', 'net_brand_preference',
                ]),
            ])
        );
    }

    // ── Adverse Events ───────────────────────────────────────────────────────
    // form_5a : infos génériques par ménage (non-répétant)
    // form_5b : données par moustiquaire (répétant)

    public function getAdverseEventsData(): array
    {
        return Cache::remember('redcap.adverse_events', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'adverse_events_arm_1',
                'forms'   => 'form_5a_informations_gnriques_adverse_events,form_5_adverse_avents',
                'fields'  => implode(',', [
                    'household_id', 'ad_evt_date', 'ae_tablette_id',
                    // form_5a — ménage
                    'ae_hh_present',
                    'ad_evt_respondent',
                    'ae_itn_n', 'ae_itn_present_n',
                    'bedbugs_community_problem', 'bedbugs_current_house',
                    'bedbug_seen_first_use', 'bb_net_old', 'bb_bed_frame',
                    'bb_bed_linen', 'bb_mattress', 'bb_clothing',
                    'bb_walls_floor', 'bb_luggage', 'bb_other_specify',
                    'bb_net_eff_att',
                    'bb_seen_on_net_ever', 'bb_stop_net_use_ever',
                    'bb_net_action',
                    // form_5b — par moustiquaire
                    'ae_net_users_n',
                    'ae_any_symptom',
                    'ae_itch_adult', 'ae_itch_adult_age',
                    'ae_faceburn_adult', 'ae_faceburn_adult_age',
                    'ae_sneeze_adult', 'ae_sneeze_adult_age',
                    'ae_rhinorrhea_adult', 'ae_rhinorrhea_adult_age',
                    'ae_headache_adult', 'ae_headache_adult_age',
                    'ae_nausea_adult', 'ae_nausea_adult_age',
                    'ae_eyeirrit_adult', 'ae_eyeirrit_adult_age',
                    'ae_tearing_adult', 'ae_tearing_adult_age',
                    'ae_badsmell_adult', 'ae_badsmell_adult_age',
                    'ae_other_symptom',
                    'itn_continue_use', 'itn_nonuse_reason',
                    'ae_reported_physician',
                ]),
            ])
        );
    }

    // ── Follow-up (6, 12, 18, 24, 36 mois) ──────────────────────────────────

    /** Suivis Cohorte A : clé interne => événement REDCap */
    public const COHORT_A_EVENTS = [
        'suivi6'  => 'suivi_6_mois_arm_1',
        'suivi12' => 'suivi_12_mois_arm_1',
        'suivi24' => 'suivi_24_mois_arm_1',
        'suivi36' => 'suivi_36_mois_arm_1',
    ];

    /** Suivis Cohorte B (Form 10 uniquement) */
    public const COHORT_B_EVENTS = [
        'suivi6'  => 'suivi_6_mois_arm_1',
        'suivi12' => 'suivi_12_mois_arm_1',
        'suivi18' => 'suivi_18_mois_coho_arm_1',
        'suivi24' => 'suivi_24_mois_arm_1',
        'suivi36' => 'suivi_36_mois_arm_1',
    ];

    /**
     * Formulaires remplis à chaque suivi Cohorte A (6, 12, 24, 36 mois) :
     * Visite Ménage, Questionnaire Base (champs clés), Membres du ménage,
     * Formulaire Avant Suivi, Form 7, Form 8, Form 9.
     * Le Form 10 est réservé à la Cohorte B.
     */
    private static function cohortAParams(string $event): array
    {
        return [
            'content' => 'record', 'type' => 'flat',
            'events'  => $event,
            'forms'   => implode(',', [
                'form_1a_visite_menage',
                'section_1_membres_du_mnage',
                'formulaire_avant_suivi',
                'form_7_suivi_des_moustiquaires_de_ltude',
                'form_8_vrification_autres_moustiquaires',
                'form_9_autres_moustiquaires_du_mnage',
            ]),
            // Questionnaire Base : seuls les champs utiles au suivi (167 champs sinon)
            'fields'  => implode(',', [
                'household_id',
                'b_date_visit', 'b_agent_initiales', 'b_tablette', 'consent_accepted',
                'hh_members_n', 'moustiquaire', 'nb_moustiquaires',
            ]),
        ];
    }

    private static function cohortBParams(string $event): array
    {
        return [
            'content' => 'record', 'type' => 'flat',
            'events'  => $event,
            // Form 10 complet (une instance par moustiquaire prélevée et remplacée)
            'forms'   => 'form_10_suivi_cohorte_b',
            'fields'  => 'household_id',
        ];
    }

    /** Coordonnées des ménages (Questionnaire Base, baseline) — listes de terrain */
    public function getBaselineContacts(): array
    {
        return Cache::remember('redcap.contacts', now()->addHours(self::TTL_BASELINE_H), fn() =>
            $this->requestBatched([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'fields'  => implode(',', [
                    'household_id', 'b_hhh_fullname', 'b_hhh_phone',
                    'b_hh_point_contact', 'b_hh_point_contact_phone', 'b_hh_address',
                ]),
            ])
        );
    }

    /** Data Access Groups du projet */
    public function getDags(): array
    {
        return Cache::remember('redcap.dags', now()->addHour(), fn() =>
            $this->requestBatched(['content' => 'dag'])
        );
    }

    public function getCohortAFollowUpData(string $event): array
    {
        return Cache::remember("redcap.fu_a.$event", now()->addMinutes(30), fn() =>
            $this->requestBatched(self::cohortAParams($event))
        );
    }

    public function getCohortBFollowUpData(string $event): array
    {
        return Cache::remember("redcap.fu_b.$event", now()->addMinutes(30), fn() =>
            $this->requestBatched(self::cohortBParams($event))
        );
    }

    // ── Parallel warm-up ────────────────────────────────────────────────────
    // Fires all dashboard API calls concurrently so get*() methods just hit cache.

    public function warmAll(): void
    {
        $this->warm();
    }

    /**
     * Préchauffe en parallèle les jeux de données indiqués (clés de tâche),
     * ou tous si $only est null. Ex. : warm(['identification', 'fu_a_suivi6']).
     */
    public function warm(?array $only = null, bool $force = false): void
    {
        $t30   = now()->addMinutes(30);
        $tLong = now()->addHours(self::TTL_BASELINE_H);
        $t60  = now()->addHour();

        $uaFields = implode(',', [
            'household_id',
            'ac_tablet', 'ac_date_interview',
            'ac_hh_present',
            'uac_resp_role',
            'net_like', 'net_overall_satisfaction',
            'net_continue_use', 'net_recommend',
            'net_easy_use', 'net_size_adequate',
            'net_design_importance', 'net_color_importance', 'net_taille_importance',
            'net_easy_install',
            'net_like_reason___1','net_like_reason___2','net_like_reason___3',
            'net_like_reason___4','net_like_reason___5','net_like_reason___6',
            'net_like_reason___7','net_like_reason___8','net_like_reason___99',
            'net_dislike_reason___1','net_dislike_reason___2','net_dislike_reason___3',
            'net_dislike_reason___4','net_dislike_reason___5','net_dislike_reason___6',
            'net_dislike_reason___7','net_dislike_reason___8','net_dislike_reason___9',
            'net_dislike_reason___10','net_dislike_reason___11','net_dislike_reason___12',
            'net_dislike_reason___13','net_dislike_reason___99',
            'net_continue_no_reason',
            'net_use_issues', 'net_comparison_other', 'net_best_features',
            'net_improvement_suggest', 'net_brand_preference',
        ]);

        // key => [cacheKey, params, ttl]
        $tasks = [
            'project_info' => [
                'redcap.project_info',
                ['content' => 'project'],
                $t60,
            ],
            'visite' => [
                'redcap.visite',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_1a_visite_menage',
                 'fields'=>implode(',',['household_id','a_date_visite_id','a_tablette_id','a_numero_tablette','a_initiales_id','number_visit','visit_status'])],
                $tLong,
            ],
            'identification' => [
                'redcap.identification',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_1a_identification',
                 'exportDataAccessGroups'=>'true',
                 'fields'=>implode(',',['household_id','village','cluster_djigbe','cluster_gbonou','cluster_miniffi','cluster_id','bras','study_cohort','numero_menage','consent_accepted'])],
                $tLong,
            ],
            'questionnaire_base' => [
                'redcap.questionnaire_base',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1',
                 'fields'=>implode(',',['household_id','b_date_visit','b_tablette','b_geo_latitude','b_geo_longitude','a_household_eligible','consent_accepted','hh_members_n','moustiquaire','nb_moustiquaires','b_nb_pieces','b_nb_pieces_sleep','sleep_total_spaces','b_sleep_bed_count','b_sleep_bed_count_mat','b_sleep_mattress_count','b_sleep_carpet_other_count','b_sleep_mat_count','b_sleep_other_space_count','b_hh_educated','b_hh_education_level'])],
                $tLong,
            ],
            'members' => [
                'redcap.members',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'section_1_membres_du_mnage',
                 'fields'=>implode(',',['household_id','c_hhm_name','c_sex','c_age_years','c_live_here','c_spent_last_night'])],
                $tLong,
            ],
            'old_nets' => [
                'redcap.old_nets',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_3b_anciennes_moustiquaires_du_mnage',
                 'fields'=>implode(',',['household_id','d_old_net_brand','d_old_net_source','d_old_net_duration'])],
                $tLong,
            ],
            'study_nets' => [
                'redcap.study_nets',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'section_5_moustiquaires_imprgnes_dinsecticide_appa',
                 'exportDataAccessGroups'=>'true',
                 'fields'=>implode(',',['household_id','net_identifier_man','net_code_written','e_net_received_in_study','is_study_net','instance_net'])],
                $tLong,
            ],
            'form4a' => [
                'redcap.form4a',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_4a_renseigner_le_nombre_de_moustiquaires_donn',
                 'fields'=>implode(',',['household_id','nb_moustiquaires_etude'])],
                $tLong,
            ],
            'form1b' => [
                'redcap.form1b',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_1b_questionnaire_base_menage',
                 'fields'=>implode(',',['household_id','b_hhh_fullname','b_hhh_phone','b_hh_point_contact','b_hh_point_contact_phone','b_hh_moment_visite','b_hh_address'])],
                $tLong,
            ],
            'adverse_events' => [
                'redcap.adverse_events',
                ['content'=>'record','type'=>'flat','events'=>'adverse_events_arm_1',
                 'forms'=>'form_5a_informations_gnriques_adverse_events,form_5_adverse_avents',
                 'fields'=>implode(',',['household_id','ad_evt_date','ae_tablette_id','ae_hh_present','ad_evt_respondent','ae_itn_n','ae_itn_present_n','bedbugs_community_problem','bedbugs_current_house','bedbug_seen_first_use','bb_net_old','bb_bed_frame','bb_bed_linen','bb_mattress','bb_clothing','bb_walls_floor','bb_luggage','bb_other_specify','bb_net_eff_att','bb_seen_on_net_ever','bb_stop_net_use_ever','bb_net_action','ae_net_users_n','ae_any_symptom','ae_itch_adult','ae_itch_adult_age','ae_faceburn_adult','ae_faceburn_adult_age','ae_sneeze_adult','ae_sneeze_adult_age','ae_rhinorrhea_adult','ae_rhinorrhea_adult_age','ae_headache_adult','ae_headache_adult_age','ae_nausea_adult','ae_nausea_adult_age','ae_eyeirrit_adult','ae_eyeirrit_adult_age','ae_tearing_adult','ae_tearing_adult_age','ae_badsmell_adult','ae_badsmell_adult_age','ae_other_symptom','itn_continue_use','itn_nonuse_reason','ae_reported_physician'])],
                $tLong,
            ],
            'ua_baseline' => [
                'redcap.ua.user_acceptability_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'user_acceptability_arm_1','forms'=>'form_6_user_acceptability','fields'=>$uaFields],
                $t30,
            ],
            'ua_24' => [
                'redcap.ua.suivi_24_mois_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'suivi_24_mois_arm_1','forms'=>'form_6_user_acceptability','fields'=>$uaFields],
                $t30,
            ],
        ];

        foreach (self::COHORT_A_EVENTS as $k => $event) {
            $tasks["fu_a_$k"] = ["redcap.fu_a.$event", self::cohortAParams($event), $t30];
        }
        foreach (self::COHORT_B_EVENTS as $k => $event) {
            $tasks["fu_b_$k"] = ["redcap.fu_b.$event", self::cohortBParams($event), $t30];
        }
        $tasks['contacts'] = ['redcap.contacts', [
            'content' => 'record', 'type' => 'flat', 'events' => 'baseline_et_distri_arm_1',
            'fields'  => 'household_id,b_hhh_fullname,b_hhh_phone,b_hh_point_contact,b_hh_point_contact_phone,b_hh_address',
        ], $tLong];
        $tasks['dags'] = ['redcap.dags', ['content' => 'dag'], $t60];

        if ($only !== null) {
            $tasks = array_intersect_key($tasks, array_flip($only));
        }

        // Seulement ce qui n'est pas en cache — sauf $force (rafraîchissement planifié) :
        // tout est rechargé, et l'ancienne valeur n'est remplacée qu'en cas de succès.
        $cold = $force ? $tasks : array_filter($tasks, fn($t) => !Cache::has($t[0]));
        if (empty($cold)) return;

        $results = $this->fetchMany(array_map(fn($t) => $t[1], $cold));
        foreach ($cold as $key => [$cacheKey, , $ttl]) {
            if (($results[$key] ?? null) !== null) {
                Cache::put($cacheKey, $results[$key], $ttl);
            }
        }
    }

    // ── Cache ────────────────────────────────────────────────────────────────

    public function clearCache(): void
    {
        $keys = [
            'project_info', 'instruments', 'events',
            'visite', 'identification', 'questionnaire_base',
            'members', 'old_nets', 'study_nets', 'form4a',
            'adverse_events', 'contacts', 'dags', 'record_ids',
        ];
        foreach ($keys as $key) {
            Cache::forget("redcap.$key");
        }
        Cache::store('file')->forget(CohortAFollowUpService::COMPUTED_CACHE_KEY);
        Cache::store('file')->forget(CohortBFollowUpService::COMPUTED_CACHE_KEY);

        foreach (['user_acceptability_arm_1', 'suivi_24_mois_arm_1'] as $e) {
            Cache::forget("redcap.ua.$e");
        }

        foreach (self::COHORT_B_EVENTS as $e) {
            Cache::forget("redcap.fu_a.$e");
            Cache::forget("redcap.fu_b.$e");
            Cache::forget("redcap.followup.$e"); // ancienne clé
        }
    }
}
