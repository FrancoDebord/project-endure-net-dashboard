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

    // ── Infos projet ────────────────────────────────────────────────────────────

    public function getProjectInfo(): array
    {
        return Cache::remember('redcap.project_info', now()->addHour(), fn() =>
            $this->request(['content' => 'project'])
        );
    }

    public function getInstruments(): array
    {
        return Cache::remember('redcap.instruments', now()->addHour(), fn() =>
            $this->request(['content' => 'instrument'])
        );
    }

    public function getEvents(): array
    {
        return Cache::remember('redcap.events', now()->addHour(), fn() =>
            $this->request(['content' => 'event'])
        );
    }

    // ── Baseline & Distribution ──────────────────────────────────────────────

    public function getVisiteData(): array
    {
        return Cache::remember('redcap.visite', now()->addMinutes(30), fn() =>
            $this->request([
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
        return Cache::remember('redcap.identification', now()->addMinutes(30), fn() =>
            $this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_1a_identification',
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
        return Cache::remember('redcap.questionnaire_base', now()->addMinutes(30), fn() =>
            $this->request([
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
        return Cache::remember('redcap.members', now()->addMinutes(30), fn() =>
            $this->request([
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
        return Cache::remember('redcap.old_nets', now()->addMinutes(30), fn() =>
            $this->request([
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
        return Cache::remember('redcap.study_nets', now()->addMinutes(30), fn() =>
            $this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'section_5_moustiquaires_imprgnes_dinsecticide_appa',
                'fields'  => implode(',', [
                    'household_id', 'net_identifier_man', 'net_code_written',
                    'e_net_received_in_study', 'is_study_net', 'instance_net',
                ]),
            ])
        );
    }

    public function getForm4aData(): array
    {
        return Cache::remember('redcap.form4a', now()->addMinutes(30), fn() =>
            $this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'baseline_et_distri_arm_1',
                'forms'   => 'form_4a_renseigner_le_nombre_de_moustiquaires_donn',
                'fields'  => implode(',', [
                    'household_id', 'nb_moustiquaires_etude',
                ]),
            ])
        );
    }

    // ── User Acceptability ───────────────────────────────────────────────────

    public function getUserAcceptabilityData(string $event = 'user_acceptability_arm_1'): array
    {
        return Cache::remember("redcap.ua.$event", now()->addMinutes(30), fn() =>
            $this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => $event,
                'forms'   => 'form_6_user_acceptability',
                'fields'  => implode(',', [
                    'household_id',
                    'net_like', 'net_overall_satisfaction',
                    'net_continue_use', 'net_recommend',
                    'net_easy_use', 'net_size_adequate',
                    'net_design_importance',
                ]),
            ])
        );
    }

    // ── Adverse Events ───────────────────────────────────────────────────────
    // form_5a : infos génériques par ménage (non-répétant)
    // form_5b : données par moustiquaire (répétant)

    public function getAdverseEventsData(): array
    {
        return Cache::remember('redcap.adverse_events', now()->addMinutes(30), fn() =>
            $this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => 'adverse_events_arm_1',
                'forms'   => 'form_5a_informations_gnriques_adverse_events,form_5_adverse_avents',
                'fields'  => implode(',', [
                    'household_id', 'ad_evt_date', 'tablet_code',
                    // form_5a — ménage
                    'bedbugs_community_problem', 'bedbugs_current_house',
                    'bb_seen_on_net_ever', 'bb_stop_net_use_ever',
                    // form_5b — par moustiquaire
                    'ae_any_symptom',
                    'ae_itch_adult', 'ae_faceburn_adult', 'ae_sneeze_adult',
                    'ae_rhinorrhea_adult', 'ae_headache_adult', 'ae_nausea_adult',
                    'ae_eyeirrit_adult', 'ae_tearing_adult', 'ae_badsmell_adult',
                    'itn_continue_use',
                ]),
            ])
        );
    }

    // ── Follow-up (6, 12, 18, 24, 36 mois) ──────────────────────────────────

    public function getFollowUpData(string $event): array
    {
        return Cache::remember("redcap.followup.$event", now()->addMinutes(30), fn() =>
            $this->request([
                'content' => 'record', 'type' => 'flat',
                'events'  => $event,
                'forms'   => 'form_7_suivi_des_moustiquaires_de_ltude,form_10_suivi_cohorte_b',
                'fields'  => implode(',', [
                    'household_id',
                    // Form 7 — Cohorte A
                    'afw_date', 'a_fw_initials',
                    'net_present', 'net_used_last_night', 'itn_has_holes',
                    'itn_utilisation_7j', 'net_location', 'net_duration_months',
                    'fw_study_net_id_scan', 'fw_study_net_id_saisie',
                    // Form 10 — Cohorte B
                    'fw_cb_date_visit', 'fw_cb_initials',
                    'itn_study_verified', 'itn_found_location',
                    'itn_used_last_night', 'itn_use_lastweek',
                    'itn_unique_id_scan', 'itn_unique_id_saisi',
                ]),
            ])
        );
    }

    // ── Parallel warm-up ────────────────────────────────────────────────────
    // Fires all dashboard API calls concurrently so get*() methods just hit cache.

    public function warmAll(): void
    {
        $t30  = now()->addMinutes(30);
        $t60  = now()->addHour();

        $fuFields = implode(',', [
            'household_id',
            'afw_date', 'a_fw_initials',
            'net_present', 'net_used_last_night', 'itn_has_holes',
            'itn_utilisation_7j', 'net_location', 'net_duration_months',
            'fw_study_net_id_scan', 'fw_study_net_id_saisie',
            'fw_cb_date_visit', 'fw_cb_initials',
            'itn_study_verified', 'itn_found_location',
            'itn_used_last_night', 'itn_use_lastweek',
            'itn_unique_id_scan', 'itn_unique_id_saisi',
        ]);

        $uaFields = implode(',', [
            'household_id',
            'net_like', 'net_overall_satisfaction',
            'net_continue_use', 'net_recommend',
            'net_easy_use', 'net_size_adequate',
            'net_design_importance',
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
                $t30,
            ],
            'identification' => [
                'redcap.identification',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_1a_identification',
                 'fields'=>implode(',',['household_id','village','cluster_djigbe','cluster_gbonou','cluster_miniffi','cluster_id','bras','study_cohort','numero_menage','consent_accepted'])],
                $t30,
            ],
            'questionnaire_base' => [
                'redcap.questionnaire_base',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1',
                 'fields'=>implode(',',['household_id','b_date_visit','b_tablette','b_geo_latitude','b_geo_longitude','a_household_eligible','consent_accepted','hh_members_n','moustiquaire','nb_moustiquaires','b_nb_pieces','b_nb_pieces_sleep','sleep_total_spaces','b_sleep_bed_count','b_sleep_bed_count_mat','b_sleep_mattress_count','b_sleep_carpet_other_count','b_sleep_mat_count','b_sleep_other_space_count','b_hh_educated','b_hh_education_level'])],
                $t30,
            ],
            'members' => [
                'redcap.members',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'section_1_membres_du_mnage',
                 'fields'=>implode(',',['household_id','c_hhm_name','c_sex','c_age_years','c_live_here','c_spent_last_night'])],
                $t30,
            ],
            'old_nets' => [
                'redcap.old_nets',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_3b_anciennes_moustiquaires_du_mnage',
                 'fields'=>implode(',',['household_id','d_old_net_brand','d_old_net_source','d_old_net_duration'])],
                $t30,
            ],
            'study_nets' => [
                'redcap.study_nets',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'section_5_moustiquaires_imprgnes_dinsecticide_appa',
                 'fields'=>implode(',',['household_id','net_identifier_man','net_code_written','e_net_received_in_study','is_study_net','instance_net'])],
                $t30,
            ],
            'form4a' => [
                'redcap.form4a',
                ['content'=>'record','type'=>'flat','events'=>'baseline_et_distri_arm_1','forms'=>'form_4a_renseigner_le_nombre_de_moustiquaires_donn',
                 'fields'=>implode(',',['household_id','nb_moustiquaires_etude'])],
                $t30,
            ],
            'adverse_events' => [
                'redcap.adverse_events',
                ['content'=>'record','type'=>'flat','events'=>'adverse_events_arm_1',
                 'forms'=>'form_5a_informations_gnriques_adverse_events,form_5_adverse_avents',
                 'fields'=>implode(',',['household_id','ad_evt_date','tablet_code','bedbugs_community_problem','bedbugs_current_house','bb_seen_on_net_ever','bb_stop_net_use_ever','ae_any_symptom','ae_itch_adult','ae_faceburn_adult','ae_sneeze_adult','ae_rhinorrhea_adult','ae_headache_adult','ae_nausea_adult','ae_eyeirrit_adult','ae_tearing_adult','ae_badsmell_adult','itn_continue_use'])],
                $t30,
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
            'fu_6' => [
                'redcap.followup.suivi_6_mois_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'suivi_6_mois_arm_1','forms'=>'form_7_suivi_des_moustiquaires_de_ltude,form_10_suivi_cohorte_b','fields'=>$fuFields],
                $t30,
            ],
            'fu_12' => [
                'redcap.followup.suivi_12_mois_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'suivi_12_mois_arm_1','forms'=>'form_7_suivi_des_moustiquaires_de_ltude,form_10_suivi_cohorte_b','fields'=>$fuFields],
                $t30,
            ],
            'fu_18' => [
                'redcap.followup.suivi_18_mois_coho_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'suivi_18_mois_coho_arm_1','forms'=>'form_7_suivi_des_moustiquaires_de_ltude,form_10_suivi_cohorte_b','fields'=>$fuFields],
                $t30,
            ],
            'fu_24' => [
                'redcap.followup.suivi_24_mois_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'suivi_24_mois_arm_1','forms'=>'form_7_suivi_des_moustiquaires_de_ltude,form_10_suivi_cohorte_b','fields'=>$fuFields],
                $t30,
            ],
            'fu_36' => [
                'redcap.followup.suivi_36_mois_arm_1',
                ['content'=>'record','type'=>'flat','events'=>'suivi_36_mois_arm_1','forms'=>'form_7_suivi_des_moustiquaires_de_ltude,form_10_suivi_cohorte_b','fields'=>$fuFields],
                $t30,
            ],
        ];

        // Only fetch what is not already in cache
        $cold = array_filter($tasks, fn($t) => !Cache::has($t[0]));
        if (empty($cold)) return;

        $responses = Http::pool(function ($pool) use ($cold) {
            $reqs = [];
            foreach ($cold as $poolKey => [$cacheKey, $params]) {
                $reqs[] = $pool->as($poolKey)
                    ->withoutVerifying()
                    ->timeout(120)
                    ->asForm()
                    ->post($this->apiUrl, array_merge(
                        ['token' => $this->token, 'format' => 'json'],
                        $params
                    ));
            }
            return $reqs;
        });

        foreach ($cold as $poolKey => [$cacheKey, $params, $ttl]) {
            $r = $responses[$poolKey] ?? null;
            if ($r && !($r instanceof \Throwable) && $r->successful()) {
                Cache::put($cacheKey, $r->json(), $ttl);
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
            'adverse_events',
        ];
        foreach ($keys as $key) {
            Cache::forget("redcap.$key");
        }

        foreach (['user_acceptability_arm_1', 'suivi_24_mois_arm_1'] as $e) {
            Cache::forget("redcap.ua.$e");
        }

        foreach (['suivi_6_mois_arm_1','suivi_12_mois_arm_1','suivi_18_mois_coho_arm_1','suivi_24_mois_arm_1','suivi_36_mois_arm_1'] as $e) {
            Cache::forget("redcap.followup.$e");
        }
    }
}
