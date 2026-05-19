<?php

namespace App\Http\Controllers;

use App\Services\RedCapService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class QueriesController extends Controller
{
    private const EVENT_LABELS = [
        'baseline_et_distri_arm_1'  => 'Baseline & Distribution',
        'user_acceptability_arm_1'  => 'User Acceptability',
        'adverse_events_arm_1'      => 'Adverse Events',
        'suivi_6_mois_arm_1'        => 'Suivi 6 mois',
        'suivi_12_mois_arm_1'       => 'Suivi 12 mois',
        'suivi_18_mois_coho_arm_1'  => 'Suivi 18 mois',
        'suivi_24_mois_arm_1'       => 'Suivi 24 mois',
        'suivi_36_mois_arm_1'       => 'Suivi 36 mois',
    ];

    // Grappe globale (cluster_id 1-8) → numéro relatif au sein du village (2 chiffres)
    private const GRAPPE_RELATIVE = [
        '1' => '01', '2' => '02',            // Djigbe
        '3' => '01', '4' => '02', '5' => '03', // Gbonou
        '6' => '01', '7' => '02', '8' => '03', // Miniffi
    ];
    // Bras numérique → code dans l'identifiant
    private const BRAS_CODE = ['1' => 'PD', '2' => 'G2'];
    // Bras par grappe village-spécifique (mêmes règles que DashboardController)
    private const BRAS_FROM_DJIGBE  = ['1' => '1', '2' => '2'];
    private const BRAS_FROM_GBONOU  = ['1' => '2', '2' => '2', '3' => '1'];
    private const BRAS_FROM_MINIFFI = ['1' => '2', '2' => '1', '3' => '1'];
    // Village REDCap (code numérique ou string) → code dans l'identifiant
    private const VILLAGE_CODE = ['1' => 'DJ', '2' => 'GB', '3' => 'MI', 'DJ' => 'DJ', 'GB' => 'GB', 'MI' => 'MI'];

    private const FORM_LABELS = [
        'form_1a_visite_menage'                                  => 'Visite Ménage (Form 1A)',
        'form_1a_identification'                                 => 'Identification (Form 1A)',
        'questionnaire_base'                                     => 'Questionnaire Baseline',
        'section_1_membres_du_mnage'                             => 'Membres du ménage (Section 1)',
        'form_3b_anciennes_moustiquaires_du_mnage'               => 'Anciennes moustiquaires (Form 3B)',
        'form_4a_renseigner_le_nombre_de_moustiquaires_donn'     => 'Nombre de moustiquaires données (Form 4A)',
        'section_5_moustiquaires_imprgnes_dinsecticide_appa'     => 'Moustiquaires imprégnées de l\'étude (Section 5)',
        'form_5a_informations_gnriques_adverse_events'           => 'Adverse Events — Informations génériques (Form 5A)',
        'form_5_adverse_avents'                                  => 'Adverse Events — Par moustiquaire (Form 5B)',
        'form_6_user_acceptability'                              => 'Acceptabilité utilisateur (Form 6)',
        'form_7_suivi_des_moustiquaires_de_ltude'                => 'Suivi des moustiquaires de l\'étude (Form 7)',
        'form_10_suivi_cohorte_b'                                => 'Suivi Cohorte B (Form 10)',
    ];

    private const AE_FIELDS = [
        'ae_itch_adult', 'ae_faceburn_adult', 'ae_sneeze_adult',
        'ae_rhinorrhea_adult', 'ae_headache_adult', 'ae_nausea_adult',
        'ae_eyeirrit_adult', 'ae_tearing_adult', 'ae_badsmell_adult',
    ];

    public function __construct(private RedCapService $redcap) {}

    public function refresh(): \Illuminate\Http\RedirectResponse
    {
        $this->redcap->clearCache();
        return redirect()->route('queries');
    }

    public function index(): View
    {
        $queries = $this->resolveQueries();

        $summary = [
            'total'    => $queries->count(),
            'critical' => $queries->where('severity', 'critical')->count(),
            'warning'  => $queries->where('severity', 'warning')->count(),
            'info'     => $queries->where('severity', 'info')->count(),
        ];

        $filterForms    = $queries->pluck('form_label')->filter()->unique()->sort()->values();
        $filterTablets  = $queries->pluck('tablet')->filter()->unique()->sort()->values();
        $filterInitials = $queries->pluck('initials')->filter()->unique()->sort()->values();

        return view('queries', compact('queries', 'summary', 'filterForms', 'filterTablets', 'filterInitials'));
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $allQueries = $this->resolveQueries();

        // If the user made an explicit checkbox selection, honour it directly.
        $selected = array_values(array_filter(
            array_map('intval', (array) $request->input('selected', []))
        ));

        if (!empty($selected)) {
            $queries = $allQueries->filter(fn($q) => in_array($q['_idx'], $selected))->values();
        } else {
            // No explicit selection: apply filter params (backwards-compatible fallback).
            $rawFilters = [
                'search'   => trim($request->input('search', '')),
                'severity' => array_values(array_filter((array) $request->input('severity', []))),
                'form'     => array_values(array_filter((array) $request->input('form', []))),
                'tablet'   => array_values(array_filter((array) $request->input('tablet', []))),
                'initials' => array_values(array_filter((array) $request->input('initials', []))),
                'hh'       => trim($request->input('hh', '')),
            ];
            $queries = $allQueries->filter(function ($q) use ($rawFilters) {
                if ($rawFilters['search'] !== '' &&
                    !str_contains(
                        strtolower($q['code'].' '.$q['title'].' '.$q['household_id'].' '.$q['field'].' '.$q['value']),
                        strtolower($rawFilters['search'])
                    )
                ) return false;
                if (!empty($rawFilters['severity']) && !in_array($q['severity'],   $rawFilters['severity'])) return false;
                if (!empty($rawFilters['form'])     && !in_array($q['form_label'], $rawFilters['form']))     return false;
                if (!empty($rawFilters['tablet'])   && !in_array($q['tablet'],     $rawFilters['tablet']))   return false;
                if (!empty($rawFilters['initials']) && !in_array($q['initials'],   $rawFilters['initials'])) return false;
                if ($rawFilters['hh'] !== '' &&
                    !str_contains(strtolower($q['household_id']), strtolower($rawFilters['hh']))
                ) return false;
                return true;
            })->values();
        }

        // Collect active filters for PDF header display (only used for display, not for filtering)
        $rawFilters = $rawFilters ?? [
            'search' => '', 'severity' => [], 'form' => [], 'tablet' => [], 'initials' => [], 'hh' => '',
        ];

        $summary = [
            'total'    => $queries->count(),
            'critical' => $queries->where('severity', 'critical')->count(),
            'warning'  => $queries->where('severity', 'warning')->count(),
            'info'     => $queries->where('severity', 'info')->count(),
        ];

        $severityLabels = [
            'critical' => 'Critique',
            'warning'  => 'Avertissement',
            'info'     => 'Informatif',
        ];
        $filterLabels = [
            'search'   => 'Recherche',
            'severity' => 'Sévérité',
            'form'     => 'Formulaire',
            'tablet'   => 'Tablette',
            'initials' => 'Initiales',
            'hh'       => 'ID Ménage',
        ];

        // Build human-readable active filters for the PDF header
        $activeFilters = array_filter([
            'search'   => $rawFilters['search'],
            'severity' => !empty($rawFilters['severity'])
                ? implode(', ', array_map(fn($v) => $severityLabels[$v] ?? $v, $rawFilters['severity']))
                : '',
            'form'     => !empty($rawFilters['form'])     ? implode(', ', $rawFilters['form'])     : '',
            'tablet'   => !empty($rawFilters['tablet'])   ? implode(', ', $rawFilters['tablet'])   : '',
            'initials' => !empty($rawFilters['initials']) ? implode(', ', $rawFilters['initials']) : '',
            'hh'       => $rawFilters['hh'],
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('queries-pdf', compact(
            'queries', 'summary', 'activeFilters', 'filterLabels', 'severityLabels'
        ))->setPaper('a4', 'portrait');

        // Filename: start with tablet(s) if filtered
        $tabletPart = !empty($rawFilters['tablet'])
            ? 'tablette-' . implode('-', array_map(fn($t) => preg_replace('/[^a-zA-Z0-9]/', '', $t), $rawFilters['tablet'])) . '-'
            : '';
        $filename = $tabletPart . 'queries-endure-net-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function resolveQueries(): Collection
    {
        set_time_limit(300);
        $this->redcap->warmAll();

        $visiteRaw   = $this->redcap->getVisiteData();
        $idRaw       = $this->redcap->getIdentificationData();
        $qbRaw       = $this->redcap->getBaselineQuestionnaireData();
        $memberRaw   = $this->redcap->getMemberData();
        $oldNetRaw   = $this->redcap->getOldNetData();
        $studyNetRaw = $this->redcap->getStudyNetData();
        $form4aRaw   = $this->redcap->getForm4aData();
        $aeRaw       = $this->redcap->getAdverseEventsData();
        $uaRaw       = $this->redcap->getUserAcceptabilityData('user_acceptability_arm_1');

        $followUpRaw = [];
        foreach ([
            'suivi_6_mois_arm_1', 'suivi_12_mois_arm_1', 'suivi_18_mois_coho_arm_1',
            'suivi_24_mois_arm_1', 'suivi_36_mois_arm_1',
        ] as $event) {
            $followUpRaw[$event] = $this->redcap->getFollowUpData($event);
        }

        $visits = $this->buildVisits($visiteRaw, $idRaw, $qbRaw);
        $qb     = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $queries = collect()
            ->merge($this->checkVisitSequence($visiteRaw))
            ->merge($this->checkHouseholdIdFormat($idRaw, $visiteRaw))
            ->merge($this->checkMandatoryBaselineFields($visits, $qb))
            ->merge($this->checkConsentMissing($visits))
            ->merge($this->checkGpsMissing($visits, $qb))
            ->merge($this->checkGpsOutlier($visits, $qb))
            ->merge($this->checkSleepSpaces($qb, $visits))
            ->merge($this->checkRoomsMismatch($qb, $visits))
            ->merge($this->checkEducationSkipLogic($qb, $visits))
            ->merge($this->checkMembersMissing($visits, $memberRaw))
            ->merge($this->checkMembersCountMismatch($visits, $memberRaw))
            ->merge($this->checkOldNetsCountMismatch($visits, $oldNetRaw))
            ->merge($this->checkStudyNetsCountMismatch($visits, $studyNetRaw, $form4aRaw))
            ->merge($this->checkStudyNetNoCode($studyNetRaw, $visits))
            ->merge($this->checkNoStudyNets($visits, $studyNetRaw))
            ->merge($this->checkAESymptomWithoutDetail($aeRaw, $visits))
            ->merge($this->checkAEDetailWithoutSymptom($aeRaw, $visits))
            ->merge($this->checkBedbugContradiction($aeRaw, $visits))
            ->merge($this->checkUASatisfactionContradiction($uaRaw, $visits))
            ->merge($this->checkUAContinueContradiction($uaRaw, $visits));

        foreach ($followUpRaw as $event => $raw) {
            $queries = $queries->merge($this->checkFollowUpContradictions($raw, $visits, $event));
        }

        return $queries->sortBy('code')->values()
            ->map(fn($q, $i) => array_merge($q, ['_idx' => $i]));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function resolveBrasKey(array $hh): string
    {
        $djigbe  = $hh['cluster_djigbe']  ?? '';
        $gbonou  = $hh['cluster_gbonou']  ?? '';
        $miniffi = $hh['cluster_miniffi'] ?? '';

        // Use isset so unrecognised / '0' values fall through to the next village variable
        if (isset(self::BRAS_FROM_DJIGBE[$djigbe]))   return self::BRAS_FROM_DJIGBE[$djigbe];
        if (isset(self::BRAS_FROM_GBONOU[$gbonou]))   return self::BRAS_FROM_GBONOU[$gbonou];
        if (isset(self::BRAS_FROM_MINIFFI[$miniffi])) return self::BRAS_FROM_MINIFFI[$miniffi];

        // Last resort: parse household ID (bras field is calculated, not stored in DB)
        $id    = $hh['household_id'] ?? '';
        $parts = explode('-', $id);
        if (count($parts) === 5) {
            if (strtoupper($parts[2]) === 'PD') return '1';
            if (strtoupper($parts[2]) === 'G2') return '2';
        }
        return '';
    }

    private function buildVisits(array $visiteRaw, array $idRaw, array $qbRaw): Collection
    {
        // Keep only the primary (non-repeating) identification row per household.
        // REDCap may return extra rows for repeating instruments at the same event;
        // those rows have redcap_repeat_instrument != '' and blank identification fields.
        $idMap = collect($idRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $qbMap = collect($qbRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        return collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && $r['visit_status'] !== '')
            ->unique('household_id')
            ->map(function ($v) use ($idMap, $qbMap) {
                $id     = $v['household_id'];
                $idData = $idMap->get($id, []);
                $qb     = $qbMap->get($id, []);
                return array_merge($v, $idData, [
                    'a_household_eligible' => $qb['a_household_eligible'] ?? '',
                    'consent_accepted'     => $qb['consent_accepted'] ?? '',
                    'hh_members_n'         => $qb['hh_members_n']     ?? '',
                    'moustiquaire'         => $qb['moustiquaire']      ?? '',
                    'nb_moustiquaires'     => $qb['nb_moustiquaires']  ?? '',
                    'bras'                 => self::resolveBrasKey($idData),
                ]);
            })
            ->values();
    }

    // ── Builder ───────────────────────────────────────────────────────────────

    private function q(string $code, string $severity, string $title, string $description, string $suggestion, array $ctx): array
    {
        $event = $ctx['event'] ?? '';
        $form  = $ctx['form']  ?? '';
        return array_merge([
            'code'        => $code,
            'severity'    => $severity,
            'title'       => $title,
            'description' => $description,
            'suggestion'  => $suggestion,
            'event_label' => self::EVENT_LABELS[$event] ?? $event,
            'form_label'  => self::FORM_LABELS[$form]   ?? $form,
            'instance'    => '',
            'household_id'=> '',
            'tablet'      => '',
            'initials'    => '',
            'field'       => '',
            'value'       => '',
            'extra'       => '',
        ], $ctx);
    }

    // ── Champs obligatoires Baseline ─────────────────────────────────────────

    private function checkMandatoryBaselineFields(Collection $visits, Collection $qb): array
    {
        // Champs requis dans form_1a_identification (disponibles dans $visits)
        $idRequired = [
            'village'       => 'Village',
            'bras'          => 'Bras de l\'étude',
            'study_cohort'  => 'Cohorte',
            'numero_menage' => 'Numéro du ménage',
        ];

        // Champs requis dans questionnaire_base (disponibles dans $qb)
        $qbRequired = [
            'b_date_visit'       => 'Date de visite baseline',
            'b_tablette'         => 'Numéro de tablette',
            'hh_members_n'       => 'Nombre de membres du ménage',
            'b_nb_pieces_sleep'  => 'Nombre de pièces de couchage',
            'sleep_total_spaces' => 'Total espaces de couchage',
        ];

        $results = [];

        foreach ($visits->where('consent_accepted', '1') as $hh) {
            $id       = $hh['household_id'];
            $tablet   = $hh['a_tablette_id'] ?? '';
            $initials = $hh['a_initiales_id'] ?? '';

            // Numéro de grappe (avec fallback sur les champs village-spécifiques)
            $clusterId = $hh['cluster_id'] ?: ($hh['cluster_djigbe'] ?: ($hh['cluster_gbonou'] ?: ($hh['cluster_miniffi'] ?? '')));
            if ($clusterId === '') {
                $results[] = $this->q('Q-BASE-01', 'warning',
                    'Champ obligatoire manquant — Numéro de grappe',
                    "Le numéro de grappe (cluster_id) est obligatoire pour tout ménage consenti mais il est vide pour le ménage {$id}.",
                    "Compléter le formulaire Identification (Form 1A) en renseignant la grappe de rattachement du ménage.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_identification',
                        'household_id' => $id,
                        'tablet'       => $tablet,
                        'initials'     => $initials,
                        'field'        => 'cluster_id',
                        'value'        => 'vide',
                    ]
                );
            }

            foreach ($idRequired as $field => $label) {
                if (($hh[$field] ?? '') === '') {
                    $results[] = $this->q('Q-BASE-01', 'warning',
                        "Champ obligatoire manquant — {$label}",
                        "Le champ « {$field} » est obligatoire pour tout ménage consenti mais il est vide pour le ménage {$id}.",
                        "Compléter le formulaire Identification (Form 1A) pour ce ménage dans REDCap.",
                        [
                            'event'        => 'baseline_et_distri_arm_1',
                            'form'         => 'form_1a_identification',
                            'household_id' => $id,
                            'tablet'       => $tablet,
                            'initials'     => $initials,
                            'field'        => $field,
                            'value'        => 'vide',
                        ]
                    );
                }
            }

            $r = $qb->get($id);
            if (!$r) {
                $results[] = $this->q('Q-BASE-01', 'critical',
                    'Questionnaire baseline absent',
                    "Le ménage {$id} a consenti à l'étude mais aucun questionnaire baseline n'a été trouvé dans REDCap. L'intégralité des données de base sont manquantes.",
                    "Saisir le questionnaire baseline complet pour ce ménage dans REDCap.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'questionnaire_base',
                        'household_id' => $id,
                        'tablet'       => $tablet,
                        'initials'     => $initials,
                        'field'        => '(tous)',
                        'value'        => 'Questionnaire absent',
                    ]
                );
                continue;
            }

            foreach ($qbRequired as $field => $label) {
                if (($r[$field] ?? '') === '') {
                    $results[] = $this->q('Q-BASE-01', 'warning',
                        "Champ obligatoire manquant — {$label}",
                        "Le champ « {$field} » est obligatoire pour tout ménage consenti mais il est vide pour le ménage {$id}.",
                        "Compléter le questionnaire baseline pour ce ménage dans REDCap.",
                        [
                            'event'        => 'baseline_et_distri_arm_1',
                            'form'         => 'questionnaire_base',
                            'household_id' => $id,
                            'tablet'       => $tablet,
                            'initials'     => $initials,
                            'field'        => $field,
                            'value'        => 'vide',
                        ]
                    );
                }
            }

            // nb_moustiquaires requis uniquement si moustiquaire = 1
            if (($r['moustiquaire'] ?? '') === '1' && ($r['nb_moustiquaires'] ?? '') === '') {
                $results[] = $this->q('Q-BASE-01', 'warning',
                    'Champ obligatoire manquant — Nombre d\'anciennes moustiquaires',
                    "Le ménage {$id} possède des moustiquaires (moustiquaire = 1) mais le nombre d'anciennes moustiquaires (nb_moustiquaires) n'est pas renseigné.",
                    "Compléter le champ nb_moustiquaires dans le questionnaire baseline pour ce ménage.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'questionnaire_base',
                        'household_id' => $id,
                        'tablet'       => $tablet,
                        'initials'     => $initials,
                        'field'        => 'nb_moustiquaires',
                        'value'        => 'vide (moustiquaire = 1)',
                    ]
                );
            }
        }

        return $results;
    }

    // ── Séquence des visites ──────────────────────────────────────────────────

    private function checkVisitSequence(array $visiteRaw): array
    {
        $byHousehold = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && $r['number_visit'] !== '')
            ->groupBy('household_id');

        $results = [];
        foreach ($byHousehold as $id => $records) {
            $presentNumbers = $records
                ->pluck('number_visit')
                ->map(fn($n) => (int)$n)
                ->filter(fn($n) => $n > 0)
                ->unique()->sort()->values();

            foreach ($presentNumbers as $n) {
                if ($n <= 1) continue;
                $missing = $n - 1;
                if (!$presentNumbers->contains($missing)) {
                    $first  = $records->first();
                    $tablet = $first['a_tablette_id'] ?? '';
                    $initials = $first['a_initiales_id'] ?? '';
                    $results[] = $this->q('Q-VIS-01', 'critical',
                        "Visite {$n} sans visite {$missing} antérieure",
                        "Pour le ménage {$id}, une visite numéro {$n} est enregistrée mais aucune visite numéro {$missing} n'existe dans le formulaire Visite Ménage. Les visites doivent être numérotées séquentiellement (1, puis 2, puis 3).",
                        "Vérifier si la visite {$missing} a bien eu lieu. Si oui, créer l'enregistrement correspondant dans le formulaire Visite Ménage. Sinon, corriger le numéro de la visite {$n}.",
                        [
                            'event'        => 'baseline_et_distri_arm_1',
                            'form'         => 'form_1a_visite_menage',
                            'household_id' => $id,
                            'tablet'       => $tablet,
                            'initials'     => $initials,
                            'field'        => 'number_visit',
                            'value'        => "Visite {$n} présente — visite {$missing} absente",
                            'extra'        => 'Visites trouvées : ' . $presentNumbers->implode(', '),
                        ]
                    );
                    break; // Un seul signalement par ménage (première rupture)
                }
            }
        }
        return $results;
    }

    // ── Cohérence identifiant ménage ──────────────────────────────────────────

    private function checkHouseholdIdFormat(array $idRaw, array $visiteRaw): array
    {
        $pattern = '/^(DJ|GB|MI)-(0[1-3])-(PD|G2)-(0[1-9]|1[0-9]|20)-(00[1-9]|0[1-9]\d|[1-9]\d{2})$/';

        // Tablette et initiales par ménage (depuis form_1a_visite_menage)
        // a_numero_tablette = numéro brut (ex: 3) utilisé pour la comparaison avec l'identifiant
        // a_tablette_id     = valeur affichable (ex: "01") utilisée pour le filtre
        $tabletByHh   = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['a_numero_tablette'] ?? '') !== '')
            ->groupBy('household_id')
            ->map(fn($rows) => $rows->first()['a_numero_tablette']);
        $tabletIdByHh = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['a_tablette_id'] ?? '') !== '')
            ->groupBy('household_id')
            ->map(fn($rows) => $rows->first()['a_tablette_id']);
        $initialsById = collect($visiteRaw)
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['a_initiales_id'] ?? '') !== '')
            ->groupBy('household_id')
            ->map(fn($rows) => $rows->first()['a_initiales_id']);

        $results = [];
        $seen    = [];

        // Itère sur les lignes non-répétantes uniquement (une par ménage, avec les vrais champs)
        foreach ($idRaw as $hh) {
            $id = $hh['household_id'];
            if ($id === '' || isset($seen[$id])) continue;
            if (($hh['redcap_repeat_instrument'] ?? '') !== '') continue;
            $seen[$id] = true;

            // — Format général —
            if (!preg_match($pattern, $id, $m)) {
                $results[] = $this->q('Q-ID-01', 'critical',
                    'Identifiant ménage — format incorrect',
                    "L'identifiant « {$id} » ne respecte pas le format attendu : VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE (ex : MI-03-PD-01-024). Chaque partie doit être séparée par un tiret, avec les chiffres zéro-complétés.",
                    "Corriger l'identifiant dans REDCap pour qu'il corresponde au format : code village (DJ/GB/MI), numéro de grappe (01–03), code bras (PD/G2), numéro tablette (01–20), numéro ménage (001–999).",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_identification',
                        'household_id' => $id,
                        'tablet'       => $tabletIdByHh->get($id, ''),
                        'initials'     => $initialsById->get($id, ''),
                        'field'        => 'household_id',
                        'value'        => $id,
                    ]
                );
                continue;
            }

            [, $idVillage, $idGrappe, $idBras, $idTablet, $idMenage] = $m;

            // Village : accepte codes numériques (1/2/3) et alphanumériques (DJ/GB/MI)
            $rawVillage   = $hh['village'] ?? '';
            $fieldVillage = self::VILLAGE_CODE[$rawVillage] ?? '';

            $clusterId   = $hh['cluster_id'] ?: ($hh['cluster_djigbe'] ?: ($hh['cluster_gbonou'] ?: ($hh['cluster_miniffi'] ?? '')));
            $fieldGrappe = self::GRAPPE_RELATIVE[$clusterId] ?? '';
            $fieldBras   = self::BRAS_CODE[self::resolveBrasKey($hh)] ?? '';

            $rawTablet   = $tabletByHh->get($id, '');
            $fieldTablet = $rawTablet !== '' ? str_pad((string)$rawTablet, 2, '0', STR_PAD_LEFT) : '';
            $fieldMenage = $hh['numero_menage'] !== '' ? str_pad((string)$hh['numero_menage'], 3, '0', STR_PAD_LEFT) : '';
            $initials    = $initialsById->get($id, '');

            // — Village —
            if ($fieldVillage !== '' && $idVillage !== $fieldVillage) {
                $results[] = $this->q('Q-ID-02', 'critical',
                    'Incohérence village — identifiant vs formulaire',
                    "Pour le ménage {$id}, le village de l'identifiant (« {$idVillage} ») ne correspond pas au champ village du formulaire Identification (valeur brute : « {$rawVillage} » → code : « {$fieldVillage} »).",
                    "Vérifier et corriger le code village dans l'identifiant ou la valeur du champ village dans le formulaire.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_identification',
                        'household_id' => $id,
                        'tablet'       => $tabletIdByHh->get($id, ''),
                        'initials'     => $initials,
                        'field'        => 'village',
                        'value'        => "Identifiant : {$idVillage}  |  Formulaire : {$fieldVillage}",
                    ]
                );
            }

            // — Grappe —
            if ($fieldGrappe !== '' && $idGrappe !== $fieldGrappe) {
                $results[] = $this->q('Q-ID-03', 'critical',
                    'Incohérence grappe — identifiant vs formulaire',
                    "Pour le ménage {$id}, la grappe de l'identifiant (« {$idGrappe} ») ne correspond pas à la grappe déduite du formulaire Identification (cluster_id={$clusterId} → grappe relative={$fieldGrappe}).",
                    "Vérifier et corriger le numéro de grappe dans l'identifiant ou les champs cluster du formulaire Identification.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_identification',
                        'household_id' => $id,
                        'tablet'       => $tabletIdByHh->get($id, ''),
                        'initials'     => $initials,
                        'field'        => 'cluster_id',
                        'value'        => "Identifiant : {$idGrappe}  |  Formulaire : cluster_id={$clusterId} → grappe {$fieldGrappe}",
                    ]
                );
            }

            // — Bras —
            if ($fieldBras !== '' && $idBras !== $fieldBras) {
                $rawBras = $hh['bras'] ?? '';
                $results[] = $this->q('Q-ID-04', 'critical',
                    'Incohérence bras — identifiant vs formulaire',
                    "Pour le ménage {$id}, le bras de l'identifiant (« {$idBras} ») ne correspond pas au bras saisi dans le formulaire Identification (bras={$rawBras} → {$fieldBras}).",
                    "Vérifier le code bras dans l'identifiant. Le bras est déterminé par la grappe : corriger la grappe ou le champ bras si l'un des deux est erroné.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_identification',
                        'household_id' => $id,
                        'tablet'       => $tabletIdByHh->get($id, ''),
                        'initials'     => $initials,
                        'field'        => 'bras',
                        'value'        => "Identifiant : {$idBras}  |  Formulaire : {$fieldBras} (bras={$rawBras})",
                    ]
                );
            }

            // — Tablette —
            if ($fieldTablet !== '' && $idTablet !== $fieldTablet) {
                $results[] = $this->q('Q-ID-05', 'warning',
                    'Incohérence tablette — identifiant vs formulaire',
                    "Pour le ménage {$id}, le numéro de tablette dans l'identifiant (« {$idTablet} ») ne correspond pas au numéro de tablette enregistré dans le formulaire Visite Ménage (a_numero_tablette={$rawTablet} → {$fieldTablet}).",
                    "Vérifier et corriger le numéro de tablette dans l'identifiant ou le champ a_numero_tablette du formulaire Visite Ménage.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_visite_menage',
                        'household_id' => $id,
                        'tablet'       => $tabletIdByHh->get($id, ''),
                        'initials'     => $initials,
                        'field'        => 'a_numero_tablette',
                        'value'        => "Identifiant : {$idTablet}  |  Formulaire : {$fieldTablet}",
                    ]
                );
            }

            // — Numéro ménage —
            if ($fieldMenage !== '' && $idMenage !== $fieldMenage) {
                $rawMenage = $hh['numero_menage'];
                $results[] = $this->q('Q-ID-06', 'warning',
                    'Incohérence numéro ménage — identifiant vs formulaire',
                    "Pour le ménage {$id}, le numéro de ménage dans l'identifiant (« {$idMenage} ») ne correspond pas au numéro saisi dans le formulaire Identification (numero_menage={$rawMenage} → {$fieldMenage}).",
                    "Vérifier et corriger le numéro de ménage dans l'identifiant ou le champ numero_menage du formulaire.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_1a_identification',
                        'household_id' => $id,
                        'tablet'       => $tabletIdByHh->get($id, ''),
                        'initials'     => $initials,
                        'field'        => 'numero_menage',
                        'value'        => "Identifiant : {$idMenage}  |  Formulaire : {$fieldMenage}",
                    ]
                );
            }
        }
        return $results;
    }

    // ── Consentement ─────────────────────────────────────────────────────────

    private function checkConsentMissing(Collection $visits): array
    {
        // Le consentement n'est attendu que si :
        //   1. La visite a abouti (visit_status = 1 — répondant présent et accepte l'entretien)
        //   2. Le ménage est confirmé éligible (a_household_eligible = 1)
        $results = [];
        foreach ($visits->filter(fn($r) =>
            $r['visit_status'] === '1' &&
            $r['a_household_eligible'] === '1' &&
            $r['consent_accepted'] === ''
        ) as $hh) {
            $id = $hh['household_id'];
            $results[] = $this->q('Q-CNS-01', 'critical',
                'Consentement non renseigné',
                "Le ménage {$id} a une visite aboutie (visit_status=1) et est déclaré éligible (a_household_eligible=1), mais le champ consent_accepted est vide. Le consentement formel doit être recueilli avant de poursuivre.",
                "Ouvrir le formulaire Questionnaire Baseline pour ce ménage dans REDCap et renseigner le résultat du consentement (Accepté ou Refusé).",
                [
                    'event'        => 'baseline_et_distri_arm_1',
                    'form'         => 'questionnaire_base',
                    'household_id' => $id,
                    'tablet'       => $hh['a_tablette_id'] ?? '',
                    'initials'     => $hh['a_initiales_id'] ?? '',
                    'field'        => 'consent_accepted',
                    'value'        => 'vide',
                    'extra'        => 'Date visite : ' . ($hh['a_date_visite_id'] ?? '—'),
                ]
            );
        }
        return $results;
    }

    // ── GPS ───────────────────────────────────────────────────────────────────

    private function checkGpsMissing(Collection $visits, Collection $qb): array
    {
        $results = [];
        foreach ($visits->where('consent_accepted', '1') as $hh) {
            $id = $hh['household_id'];
            $r  = $qb->get($id);
            if (!$r || ($r['b_geo_latitude'] === '' && $r['b_geo_longitude'] === '')) {
                $results[] = $this->q('Q-GPS-01', 'warning',
                    'Coordonnées GPS manquantes',
                    "Le ménage {$id} a consenti à l'étude mais aucune coordonnée GPS n'a été enregistrée dans le questionnaire baseline. La géolocalisation est nécessaire pour la cartographie des ménages.",
                    "Effectuer une nouvelle visite pour collecter les coordonnées GPS, ou vérifier que le questionnaire baseline a bien été soumis pour ce ménage.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'questionnaire_base',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'b_geo_latitude / b_geo_longitude',
                        'value'        => 'vide',
                        'extra'        => 'Date visite : ' . ($r['b_date_visit'] ?? $hh['a_date_visite_id'] ?? '—'),
                    ]
                );
            }
        }
        return $results;
    }

    private function checkGpsOutlier(Collection $visits, Collection $qb): array
    {
        $consented = $visits->where('consent_accepted', '1')->pluck('household_id')->flip();
        $pts = $qb->filter(fn($r) =>
            $r['b_geo_latitude'] !== '' && $r['b_geo_longitude'] !== ''
            && $consented->has($r['household_id'])
        );
        if ($pts->count() < 3) return [];

        $lats = $pts->pluck('b_geo_latitude')->map(fn($v) => (float)$v)->sort()->values();
        $lngs = $pts->pluck('b_geo_longitude')->map(fn($v) => (float)$v)->sort()->values();
        $n    = $lats->count();
        $medLat = round($n % 2 ? $lats[$n >> 1] : ($lats[$n / 2 - 1] + $lats[$n / 2]) / 2, 6);
        $medLng = round($n % 2 ? $lngs[$n >> 1] : ($lngs[$n / 2 - 1] + $lngs[$n / 2]) / 2, 6);

        $visitsByHh = $visits->keyBy('household_id');
        $results = [];
        foreach ($pts as $r) {
            $dLat = abs((float)$r['b_geo_latitude']  - $medLat);
            $dLng = abs((float)$r['b_geo_longitude'] - $medLng);
            if ($dLat > 0.5 || $dLng > 0.5) {
                $id  = $r['household_id'];
                $lat = $r['b_geo_latitude'];
                $lng = $r['b_geo_longitude'];
                $hh  = $visitsByHh->get($id, []);
                $results[] = $this->q('Q-GPS-02', 'warning',
                    'Coordonnées GPS aberrantes (hors zone)',
                    "Les coordonnées GPS du ménage {$id} (lat {$lat}, lng {$lng}) s'écartent de plus de 0,5 degré de la médiane de la zone d'étude (lat {$medLat}, lng {$medLng}), soit environ 50 km. Probable erreur de géolocalisation.",
                    "Vérifier que la géolocalisation a bien été faite sur place. Possible saisie dans le mauvais champ ou activation du GPS en dehors de la zone. Corriger les coordonnées.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'questionnaire_base',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'b_geo_latitude / b_geo_longitude',
                        'value'        => "{$lat} / {$lng}",
                        'extra'        => "Médiane zone : {$medLat} / {$medLng}",
                    ]
                );
            }
        }
        return $results;
    }

    // ── Espaces de couchage ───────────────────────────────────────────────────

    private function checkSleepSpaces(Collection $qb, Collection $visits): array
    {
        $consented  = $visits->where('consent_accepted', '1')->pluck('household_id')->flip();
        $visitsByHh = $visits->keyBy('household_id');
        $results = [];
        foreach ($qb as $r) {
            if (!$consented->has($r['household_id'])) continue;
            if ($r['sleep_total_spaces'] === '') continue;
            $declared = (int)$r['sleep_total_spaces'];
            $computed = (int)$r['b_sleep_bed_count']
                      + (int)$r['b_sleep_bed_count_mat']
                      + (int)$r['b_sleep_mattress_count']
                      + (int)$r['b_sleep_carpet_other_count']
                      + (int)$r['b_sleep_mat_count']
                      + (int)$r['b_sleep_other_space_count'];
            if ($declared !== $computed && $computed > 0) {
                $id = $r['household_id'];
                $hh = $visitsByHh->get($id, []);
                $results[] = $this->q('Q-SLP-01', 'warning',
                    'Incohérence espaces de couchage',
                    "Pour le ménage {$id}, le total d'espaces de couchage déclaré ({$declared}) ne correspond pas à la somme des types détaillés (lit + matelas + natte + autre = {$computed}). Ces deux valeurs doivent être égales.",
                    "Vérifier chacun des champs individuels (b_sleep_bed_count, b_sleep_bed_count_mat, b_sleep_mattress_count, b_sleep_mat_count, etc.) et corriger soit le total soit les détails pour les rendre cohérents.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'questionnaire_base',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'sleep_total_spaces',
                        'value'        => "Déclaré : {$declared}  |  Somme détails : {$computed}",
                    ]
                );
            }
        }
        return $results;
    }

    // ── Cohérence pièces ─────────────────────────────────────────────────────

    private function checkRoomsMismatch(Collection $qb, Collection $visits): array
    {
        $consented  = $visits->where('consent_accepted', '1')->pluck('household_id')->flip();
        $visitsByHh = $visits->keyBy('household_id');
        $results    = [];
        foreach ($qb as $r) {
            if (!$consented->has($r['household_id'])) continue;
            $total = $r['b_nb_pieces']       ?? '';
            $sleep = $r['b_nb_pieces_sleep'] ?? '';
            if ($total === '' || $sleep === '') continue;
            if ((int)$total < (int)$sleep) {
                $id = $r['household_id'];
                $hh = $visitsByHh->get($id, []);
                $results[] = $this->q('Q-SLP-02', 'warning',
                    'Incohérence — pièces de couchage > total pièces',
                    "Pour le ménage {$id}, le nombre de pièces servant à dormir (b_nb_pieces_sleep = {$sleep}) est supérieur au nombre total de pièces du logement (b_nb_pieces = {$total}). Une pièce de couchage ne peut pas exister si elle n'est pas comptée dans le total.",
                    "Vérifier Q15 (nombre total de pièces du logement) et Q16 (nombre de pièces servant à dormir) dans le questionnaire baseline et corriger la valeur erronée.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'questionnaire_base',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'b_nb_pieces / b_nb_pieces_sleep',
                        'value'        => "Total pièces : {$total}  |  Pièces couchage : {$sleep}",
                    ]
                );
            }
        }
        return $results;
    }

    // ── Skip logic éducation ──────────────────────────────────────────────────

    private function checkEducationSkipLogic(Collection $qb, Collection $visits): array
    {
        $consented  = $visits->where('consent_accepted', '1')->pluck('household_id')->flip();
        $visitsByHh = $visits->keyBy('household_id');
        $results    = [];
        foreach ($qb as $r) {
            if (!$consented->has($r['household_id'])) continue;
            $educated = $r['b_hh_educated']        ?? '';
            $level    = $r['b_hh_education_level'] ?? '';
            $id       = $r['household_id'];
            $hh       = $visitsByHh->get($id, []);
            $ctx      = [
                'event'        => 'baseline_et_distri_arm_1',
                'form'         => 'questionnaire_base',
                'household_id' => $id,
                'tablet'       => $hh['a_tablette_id'] ?? '',
                'initials'     => $hh['a_initiales_id'] ?? '',
            ];

            // Niveau renseigné alors que le chef n'a pas été scolarisé (skip logic)
            if ($educated !== '1' && $level !== '') {
                $results[] = $this->q('Q-EDU-01', 'warning',
                    'Niveau d\'études renseigné — chef non scolarisé',
                    "Pour le ménage {$id}, le niveau d'études (b_hh_education_level = « {$level} ») est renseigné alors que Q10 indique que le chef n'a pas été scolarisé (b_hh_educated = " . ($educated ?: 'vide') . "). Ce champ doit rester vide selon le skip logic.",
                    "Effacer b_hh_education_level dans le questionnaire baseline, ou corriger b_hh_educated à Oui (1) si le chef a bien été scolarisé.",
                    array_merge($ctx, [
                        'field' => 'b_hh_education_level',
                        'value' => "b_hh_educated = " . ($educated ?: 'vide') . "  |  b_hh_education_level = {$level}",
                    ])
                );
            }

            // Chef scolarisé mais niveau non renseigné
            if ($educated === '1' && $level === '') {
                $results[] = $this->q('Q-EDU-01', 'warning',
                    'Niveau d\'études manquant — chef scolarisé',
                    "Pour le ménage {$id}, le chef de famille est déclaré scolarisé (b_hh_educated = 1) mais le niveau d'études (b_hh_education_level) n'est pas renseigné. Ce champ est obligatoire quand la scolarisation est confirmée (Q10 = Oui).",
                    "Renseigner le niveau d'études du chef de famille (Primaire, Secondaire ou Supérieur) dans le questionnaire baseline.",
                    array_merge($ctx, [
                        'field' => 'b_hh_education_level',
                        'value' => 'b_hh_educated = 1  |  b_hh_education_level = vide',
                    ])
                );
            }
        }
        return $results;
    }

    // ── Membres ───────────────────────────────────────────────────────────────

    private function checkMembersMissing(Collection $visits, array $memberRaw): array
    {
        $membersByHh = collect($memberRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'section_1_membres_du_mnage' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        $results = [];
        foreach ($visits->where('consent_accepted', '1') as $hh) {
            $id = $hh['household_id'];
            if ($membersByHh->get($id, 0) === 0) {
                $results[] = $this->q('Q-MBR-01', 'warning',
                    'Aucun membre du ménage enregistré',
                    "Le ménage {$id} a consenti à l'étude mais aucun membre n'a été enregistré dans le formulaire Membres du ménage (Section 1). Cette information est indispensable pour les analyses démographiques.",
                    "Compléter le formulaire Section 1 pour ce ménage en saisissant les informations de chaque membre résidant (prénom, sexe, âge, nuitée).",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'section_1_membres_du_mnage',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'c_hhm_name / c_sex / c_age_years',
                        'value'        => '0 membre',
                    ]
                );
            }
        }
        return $results;
    }

    // ── Comptages ─────────────────────────────────────────────────────────────

    private function checkMembersCountMismatch(Collection $visits, array $memberRaw): array
    {
        $membersByHh = collect($memberRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'section_1_membres_du_mnage' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        $results = [];
        foreach ($visits->where('consent_accepted', '1') as $hh) {
            $id       = $hh['household_id'];
            $declared = $hh['hh_members_n'] ?? '';
            if ($declared === '') continue;
            $actual = $membersByHh->get($id, 0);
            if ((int)$declared !== $actual) {
                $results[] = $this->q('Q-MBR-02', 'warning',
                    'Incohérence nombre de membres du ménage',
                    "Pour le ménage {$id}, le nombre de membres déclaré dans le questionnaire baseline (hh_members_n = {$declared}) ne correspond pas au nombre de membres enregistrés dans le formulaire Membres du ménage (Section 1 : {$actual} membre(s)). Ces deux valeurs doivent être identiques.",
                    "Vérifier si tous les membres ont bien été saisis dans le formulaire Section 1, ou corriger hh_members_n dans le questionnaire baseline pour qu'il reflète le nombre réel.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'section_1_membres_du_mnage',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'hh_members_n / c_hhm_name (count)',
                        'value'        => "Déclaré : {$declared}  |  Saisi : {$actual}",
                    ]
                );
            }
        }
        return $results;
    }

    private function checkOldNetsCountMismatch(Collection $visits, array $oldNetRaw): array
    {
        $oldNetsByHh = collect($oldNetRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === 'form_3b_anciennes_moustiquaires_du_mnage' &&
                $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        $results = [];
        // Condition skip logic : moustiquaire = 1 (le ménage possède des anciennes moustiquaires)
        foreach ($visits->where('consent_accepted', '1')->where('moustiquaire', '1') as $hh) {
            $id       = $hh['household_id'];
            $declared = $hh['nb_moustiquaires'] ?? '';
            if ($declared === '') continue;
            $actual = $oldNetsByHh->get($id, 0);
            if ((int)$declared !== $actual) {
                $results[] = $this->q('Q-NET-03', 'warning',
                    'Incohérence nombre d\'anciennes moustiquaires',
                    "Pour le ménage {$id}, le nombre d'anciennes moustiquaires déclaré (nb_moustiquaires = {$declared}) ne correspond pas au nombre de filets enregistrés individuellement dans le formulaire Form 3 ({$actual}). Ces deux valeurs doivent être identiques.",
                    "Vérifier le formulaire « Form 3 Enregistrer les Anciennes moustiquaires du ménage » et s'assurer que chaque filet a bien sa propre ligne. Corriger nb_moustiquaires dans le questionnaire baseline si nécessaire.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_3b_anciennes_moustiquaires_du_mnage',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'nb_moustiquaires / Form 3 (count)',
                        'value'        => "Déclaré : {$declared}  |  Saisi : {$actual}",
                    ]
                );
            }
        }
        return $results;
    }

    private function checkStudyNetsCountMismatch(Collection $visits, array $studyNetRaw, array $form4aRaw): array
    {
        // Comptage déclaré : champ nb_moustiquaires_etude dans Form 4A (non-répétant)
        $declaredByHh = collect($form4aRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') === '' &&
                ($r['nb_moustiquaires_etude'] ?? '') !== ''
            )
            ->keyBy('household_id')
            ->map(fn($r) => $r['nb_moustiquaires_etude']);

        // Comptage réel : instances répétantes de section_5
        $actualByHh = collect($studyNetRaw)
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
                && $r['redcap_repeat_instance'] !== ''
            )
            ->groupBy('household_id')->map->count();

        $results = [];
        foreach ($visits->where('consent_accepted', '1') as $hh) {
            $id       = $hh['household_id'];
            $declared = $declaredByHh->get($id);
            if ($declared === null || $declared === '') continue;
            $actual = $actualByHh->get($id, 0);
            if ((int)$declared !== $actual) {
                $results[] = $this->q('Q-NET-04', 'warning',
                    'Incohérence nombre de moustiquaires de l\'étude',
                    "Pour le ménage {$id}, le nombre de moustiquaires de l'étude déclaré dans le formulaire Form 4A (nb_moustiquaires_etude = {$declared}) ne correspond pas au nombre de filets enregistrés dans la Section 5 ({$actual} filet(s)). Ces deux valeurs doivent être identiques.",
                    "Vérifier si toutes les moustiquaires distribuées ont bien été saisies dans la Section 5 (une ligne par filet), ou corriger nb_moustiquaires_etude dans le Form 4A si le nombre distribué a changé.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'form_4a_renseigner_le_nombre_de_moustiquaires_donn',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'nb_moustiquaires_etude (Form 4A) / section_5 (count)',
                        'value'        => "Déclaré : {$declared}  |  Saisi : {$actual}",
                    ]
                );
            }
        }
        return $results;
    }

    // ── Moustiquaires ─────────────────────────────────────────────────────────

    private function checkStudyNetNoCode(array $studyNetRaw, Collection $visits): array
    {
        $consented = $visits->where('consent_accepted', '1')->pluck('household_id')->flip();

        $nets = collect($studyNetRaw)->filter(fn($r) =>
            $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
            && $r['redcap_repeat_instance'] !== ''
            && $r['net_identifier_man'] === ''
            && $consented->has($r['household_id'])
        );
        $results = [];
        foreach ($nets as $net) {
            $id = $net['household_id'];
            $hh = $visits->firstWhere('household_id', $id);
            $results[] = $this->q('Q-NET-01', 'critical',
                'Code moustiquaire manquant',
                "La moustiquaire (instance #{$net['redcap_repeat_instance']}) distribuée au ménage {$id} n'a pas de code d'identification (net_identifier_man vide). Sans ce code, le traçage et le suivi du filet sont impossibles.",
                "Scanner le QR code ou saisir manuellement le code imprimé sur la moustiquaire pour cette instance dans le formulaire Section 5.",
                [
                    'event'        => 'baseline_et_distri_arm_1',
                    'form'         => 'section_5_moustiquaires_imprgnes_dinsecticide_appa',
                    'instance'     => $net['redcap_repeat_instance'],
                    'household_id' => $id,
                    'tablet'       => $hh['a_tablette_id'] ?? '',
                    'initials'     => $hh['a_initiales_id'] ?? '',
                    'field'        => 'net_identifier_man',
                    'value'        => 'vide',
                ]
            );
        }
        return $results;
    }

    private function checkNoStudyNets(Collection $visits, array $studyNetRaw): array
    {
        $netsByHh = collect($studyNetRaw)->filter(fn($r) =>
            $r['redcap_repeat_instrument'] === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
            && $r['redcap_repeat_instance'] !== ''
        )->groupBy('household_id')->map->count();

        $results = [];
        foreach ($visits->where('consent_accepted', '1') as $hh) {
            $id = $hh['household_id'];
            if ($netsByHh->get($id, 0) === 0) {
                $cohort = match($hh['study_cohort'] ?? '') { '1' => 'Cohorte A', '2' => 'Cohorte B', default => '—' };
                $results[] = $this->q('Q-NET-02', 'warning',
                    'Aucune moustiquaire de l\'étude enregistrée',
                    "Le ménage consenti {$id} ({$cohort}) n'a aucune moustiquaire de l'étude enregistrée dans le formulaire Section 5. La distribution de filet est pourtant attendue pour tout ménage consenti.",
                    "Vérifier si la distribution a bien eu lieu. Si oui, saisir les moustiquaires distribuées dans le formulaire Section 5 avec leur code d'identification.",
                    [
                        'event'        => 'baseline_et_distri_arm_1',
                        'form'         => 'section_5_moustiquaires_imprgnes_dinsecticide_appa',
                        'household_id' => $id,
                        'tablet'       => $hh['a_tablette_id'] ?? '',
                        'initials'     => $hh['a_initiales_id'] ?? '',
                        'field'        => 'net_identifier_man',
                        'value'        => '0 moustiquaire',
                        'extra'        => $cohort,
                    ]
                );
            }
        }
        return $results;
    }

    // ── Adverse Events ────────────────────────────────────────────────────────

    private function checkAESymptomWithoutDetail(array $aeRaw, Collection $visits): array
    {
        // form_5b uniquement (données par moustiquaire)
        $form5b = collect($aeRaw)->filter(fn($r) =>
            $r['household_id'] !== '' &&
            $r['redcap_repeat_instrument'] === 'form_5_adverse_avents' &&
            $r['ae_any_symptom'] === '1'
        );

        $results = [];
        foreach ($form5b as $r) {
            $hasDetail = collect(self::AE_FIELDS)->contains(fn($f) => ($r[$f] ?? '') === '1');
            if (!$hasDetail) {
                $id = $r['household_id'];
                $hh = $visits->firstWhere('household_id', $id);
                $results[] = $this->q('Q-AE-01', 'critical',
                    'Symptôme déclaré sans type précisé',
                    "Pour le ménage {$id}, la présence d'un symptôme est cochée (ae_any_symptom = Oui) mais aucun type de symptôme spécifique n'est sélectionné parmi la liste. Violation de skip logic.",
                    "Recontacter l'agent de terrain pour identifier le ou les symptômes constatés et mettre à jour le formulaire Adverse Events (Form 5B).",
                    [
                        'event'        => 'adverse_events_arm_1',
                        'form'         => 'form_5_adverse_avents',
                        'household_id' => $id,
                        'tablet'       => $r['tablet_code'] ?? $hh['a_tablette_id'] ?? '',
                        'field'        => 'ae_itch_adult ... ae_badsmell_adult',
                        'value'        => 'ae_any_symptom=1, tous les types = 0 ou vide',
                        'extra'        => 'Instance : ' . ($r['redcap_repeat_instance'] ?? '—'),
                    ]
                );
            }
        }
        return $results;
    }

    private function checkAEDetailWithoutSymptom(array $aeRaw, Collection $visits): array
    {
        // form_5b uniquement
        $form5b = collect($aeRaw)->filter(fn($r) =>
            $r['household_id'] !== '' &&
            $r['redcap_repeat_instrument'] === 'form_5_adverse_avents' &&
            $r['ae_any_symptom'] !== '1'
        );

        $results = [];
        foreach ($form5b as $r) {
            $hasDetail = collect(self::AE_FIELDS)->contains(fn($f) => ($r[$f] ?? '') === '1');
            if ($hasDetail) {
                $id = $r['household_id'];
                $hh = $visits->firstWhere('household_id', $id);
                $results[] = $this->q('Q-AE-02', 'warning',
                    'Type de symptôme coché sans déclaration globale',
                    "Pour le ménage {$id}, un ou plusieurs types de symptômes spécifiques sont cochés mais la question générale ae_any_symptom n'est pas à Oui. Incohérence de skip logic.",
                    "Mettre ae_any_symptom = Oui si des symptômes ont bien été constatés, ou décocher les types cochés par erreur.",
                    [
                        'event'        => 'adverse_events_arm_1',
                        'form'         => 'form_5_adverse_avents',
                        'household_id' => $id,
                        'tablet'       => $r['tablet_code'] ?? $hh['a_tablette_id'] ?? '',
                        'field'        => 'ae_any_symptom',
                        'value'        => 'ae_any_symptom = ' . ($r['ae_any_symptom'] ?: 'vide') . ', mais types cochés',
                        'extra'        => 'Instance : ' . ($r['redcap_repeat_instance'] ?? '—'),
                    ]
                );
            }
        }
        return $results;
    }

    private function checkBedbugContradiction(array $aeRaw, Collection $visits): array
    {
        // form_5a uniquement (infos génériques par ménage)
        $form5a = collect($aeRaw)->filter(fn($r) =>
            $r['household_id'] !== '' &&
            ($r['redcap_repeat_instrument'] === '' ||
             $r['redcap_repeat_instrument'] === 'form_5a_informations_gnriques_adverse_events')
        );

        $results = [];
        foreach ($form5a as $r) {
            if ($r['bedbugs_current_house'] !== '0' || ($r['bb_seen_on_net_ever'] ?? '') !== '1') continue;
            $id = $r['household_id'];
            $hh = $visits->firstWhere('household_id', $id);
            $results[] = $this->q('Q-AE-03', 'warning',
                'Contradiction — punaises de lit',
                "Pour le ménage {$id}, aucune punaise n'est signalée dans la maison actuellement (bedbugs_current_house = Non) alors que des punaises auraient déjà été observées sur le filet (bb_seen_on_net_ever = Oui). Si des punaises sont sur le filet, elles proviennent de la maison.",
                "Clarifier avec l'agent ou le ménage : corriger bedbugs_current_house à Oui si des punaises ont été vues sur le filet, ou corriger bb_seen_on_net_ever à Non si c'est la bonne réponse.",
                [
                    'event'        => 'adverse_events_arm_1',
                    'form'         => 'form_5a_informations_gnriques_adverse_events',
                    'household_id' => $id,
                    'tablet'       => $r['tablet_code'] ?? $hh['a_tablette_id'] ?? '',
                    'field'        => 'bedbugs_current_house / bb_seen_on_net_ever',
                    'value'        => 'bedbugs_current_house=0, bb_seen_on_net_ever=1',
                    'extra'        => 'Date EI : ' . ($r['ad_evt_date'] ?? '—'),
                ]
            );
        }
        return $results;
    }

    // ── User Acceptability ────────────────────────────────────────────────────

    private function checkUASatisfactionContradiction(array $uaRaw, Collection $visits): array
    {
        $results = [];
        foreach (collect($uaRaw)->filter(fn($r) => $r['household_id'] !== '') as $r) {
            if ($r['net_like'] !== '1' || $r['net_overall_satisfaction'] !== '4') continue;
            $id = $r['household_id'];
            $hh = $visits->firstWhere('household_id', $id);
            $results[] = $this->q('Q-UA-01', 'warning',
                'Contradiction appréciation / satisfaction',
                "Pour le ménage {$id}, l'utilisateur déclare apprécier le filet (net_like = Oui) mais sa satisfaction globale est « Très insatisfait » (net_overall_satisfaction = 4). Ces deux réponses sont logiquement incompatibles.",
                "Recontacter l'agent pour clarifier les réponses. Probable erreur de saisie sur l'une des deux questions. Corriger la réponse erronée.",
                [
                    'event'        => 'user_acceptability_arm_1',
                    'form'         => 'form_6_user_acceptability',
                    'household_id' => $id,
                    'tablet'       => $hh['a_tablette_id'] ?? '',
                    'field'        => 'net_like / net_overall_satisfaction',
                    'value'        => 'net_like=1, net_overall_satisfaction=4 (Très insatisfait)',
                ]
            );
        }
        return $results;
    }

    private function checkUAContinueContradiction(array $uaRaw, Collection $visits): array
    {
        $results = [];
        foreach (collect($uaRaw)->filter(fn($r) => $r['household_id'] !== '') as $r) {
            if ($r['net_like'] !== '1' || $r['net_recommend'] !== '1' || $r['net_continue_use'] !== '0') continue;
            $id = $r['household_id'];
            $hh = $visits->firstWhere('household_id', $id);
            $results[] = $this->q('Q-UA-02', 'warning',
                'Contradiction — aime et recommande mais ne veut pas continuer',
                "Pour le ménage {$id}, l'utilisateur aime le filet ET le recommande à d'autres (net_like=Oui, net_recommend=Oui) mais déclare ne pas vouloir continuer à l'utiliser (net_continue_use=Non). Incohérence logique.",
                "Vérifier avec l'agent la réponse à net_continue_use. Probable inversion Oui/Non lors de la saisie. Corriger si nécessaire.",
                [
                    'event'        => 'user_acceptability_arm_1',
                    'form'         => 'form_6_user_acceptability',
                    'household_id' => $id,
                    'tablet'       => $hh['a_tablette_id'] ?? '',
                    'field'        => 'net_like / net_recommend / net_continue_use',
                    'value'        => 'net_like=1, net_recommend=1, net_continue_use=0',
                ]
            );
        }
        return $results;
    }

    // ── Follow-up ─────────────────────────────────────────────────────────────

    private function checkFollowUpContradictions(array $raw, Collection $visits, string $event): array
    {
        $results = [];
        $records = collect($raw)->filter(fn($r) =>
            ($r['redcap_repeat_instrument'] === 'form_7_suivi_des_moustiquaires_de_ltude' ||
             $r['redcap_repeat_instrument'] === 'form_10_suivi_cohorte_b')
            && $r['redcap_repeat_instance'] !== ''
        );

        foreach ($records as $r) {
            $id   = $r['household_id'];
            $hh   = $visits->firstWhere('household_id', $id);
            $isF7 = $r['redcap_repeat_instrument'] === 'form_7_suivi_des_moustiquaires_de_ltude';
            $form = $isF7 ? 'form_7_suivi_des_moustiquaires_de_ltude' : 'form_10_suivi_cohorte_b';
            $inst = $r['redcap_repeat_instance'];

            if ($isF7) {
                $present   = $r['net_present']        ?? '';
                $usedNight = $r['net_used_last_night'] ?? '';
                $hasHoles  = $r['itn_has_holes']       ?? '';
                $netId     = $r['fw_study_net_id_scan'] ?: ($r['fw_study_net_id_saisie'] ?? '—');
                $date      = $r['afw_date']      ?? '—';
                $initials  = $r['a_fw_initials'] ?? '';

                if ($present === '0' && $usedNight === '1') {
                    $results[] = $this->q('Q-FW-01', 'critical',
                        'Utilisation déclarée — filet absent',
                        "Ménage {$id} (Form 7) : le filet est signalé absent lors de la visite (net_present = Non) mais l'utilisateur déclare l'avoir utilisé la nuit précédente (net_used_last_night = Oui). Un filet absent ne peut pas être utilisé.",
                        "Corriger net_present en Oui si le filet était effectivement présent, ou corriger net_used_last_night en Non si le filet était absent.",
                        [
                            'event'        => $event,
                            'form'         => $form,
                            'instance'     => $inst,
                            'household_id' => $id,
                            'initials'     => $initials,
                            'tablet'       => $hh['a_tablette_id'] ?? '',
                            'field'        => 'net_present / net_used_last_night',
                            'value'        => 'net_present=0, net_used_last_night=1',
                            'extra'        => "ID filet : {$netId} | Date : {$date}",
                        ]
                    );
                }

                if ($present === '0' && $hasHoles === '1') {
                    $results[] = $this->q('Q-FW-02', 'warning',
                        'Trous signalés — filet absent',
                        "Ménage {$id} (Form 7) : des trous sont signalés sur le filet (itn_has_holes = Oui) alors que le filet est déclaré absent (net_present = Non). Il est impossible d'inspecter l'intégrité d'un filet absent.",
                        "Vérifier si le filet était présent lors de la visite. Corriger net_present à Oui, ou corriger itn_has_holes à Non si le filet n'était pas là.",
                        [
                            'event'        => $event,
                            'form'         => $form,
                            'instance'     => $inst,
                            'household_id' => $id,
                            'initials'     => $initials,
                            'tablet'       => $hh['a_tablette_id'] ?? '',
                            'field'        => 'net_present / itn_has_holes',
                            'value'        => 'net_present=0, itn_has_holes=1',
                            'extra'        => "ID filet : {$netId} | Date : {$date}",
                        ]
                    );
                }
            } else {
                $verified  = $r['itn_study_verified']  ?? '';
                $usedNight = $r['itn_used_last_night']  ?? '';
                $netId     = $r['itn_unique_id_scan'] ?: ($r['itn_unique_id_saisi'] ?? '—');
                $date      = $r['fw_cb_date_visit'] ?? '—';
                $initials  = $r['fw_cb_initials']  ?? '';

                if ($verified === '0' && $usedNight === '1') {
                    $results[] = $this->q('Q-FW-03', 'critical',
                        'Utilisation déclarée — filet non retrouvé (Form 10)',
                        "Ménage {$id} (Form 10) : le filet n'a pas été retrouvé ni vérifié lors de la visite (itn_study_verified = Non) mais l'utilisateur déclare l'avoir utilisé la nuit précédente (itn_used_last_night = Oui). Contradiction logique.",
                        "Corriger itn_study_verified à Oui si le filet était présent, ou corriger itn_used_last_night à Non si le filet n'a pas été retrouvé.",
                        [
                            'event'        => $event,
                            'form'         => $form,
                            'instance'     => $inst,
                            'household_id' => $id,
                            'initials'     => $initials,
                            'tablet'       => $hh['a_tablette_id'] ?? '',
                            'field'        => 'itn_study_verified / itn_used_last_night',
                            'value'        => 'itn_study_verified=0, itn_used_last_night=1',
                            'extra'        => "ID filet : {$netId} | Date : {$date}",
                        ]
                    );
                }
            }
        }
        return $results;
    }
}
