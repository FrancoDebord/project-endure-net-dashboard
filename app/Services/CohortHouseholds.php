<?php

namespace App\Services;

use App\Support\HouseholdHelpers;
use Illuminate\Support\Collection;

/**
 * Liste des ménages d'une cohorte (baseline) enrichie : bras, village, grappe, DAG,
 * coordonnées et codes des moustiquaires de l'étude. Commune aux suivis Cohorte A et B.
 */
class CohortHouseholds
{
    use HouseholdHelpers;

    /** Ménages consentis d'une cohorte ('1' = A, '2' = B), indexés par household_id */
    public function forCohort(string $cohort, array $idRaw, array $contactsRaw = [], array $studyNetRaw = [], array $dagsRaw = []): Collection
    {
        $dagLabels = collect($dagsRaw)->pluck('data_access_group_name', 'unique_group_name');

        $contacts = collect($contactsRaw)
            ->filter(fn($r) => ($r['household_id'] ?? '') !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        $netCodes = collect($studyNetRaw)
            ->filter(fn($r) => ($r['redcap_repeat_instrument'] ?? '') === 'section_5_moustiquaires_imprgnes_dinsecticide_appa'
                && ($r['redcap_repeat_instance'] ?? '') !== '')
            ->groupBy('household_id')
            ->map(fn($g) => $g->sortBy(fn($r) => (int) $r['redcap_repeat_instance'])
                ->mapWithKeys(fn($r) => [(string) $r['redcap_repeat_instance'] => trim($r['net_identifier_man'] ?? '')])
                ->all());

        return collect($idRaw)
            ->filter(fn($r) => ($r['household_id'] ?? '') !== ''
                && ($r['redcap_repeat_instrument'] ?? '') === ''
                && ($r['consent_accepted'] ?? '') === '1'
                && ($r['study_cohort'] ?? '') === $cohort)
            ->unique('household_id')
            ->keyBy('household_id')
            ->map(function ($r) use ($dagLabels, $contacts, $netCodes) {
                $id         = $r['household_id'];
                $parsed     = self::parseHouseholdId($id);
                $villageKey = ($r['village'] ?? '') ?: ($parsed['village'] ?? '');
                $brasKey    = self::resolveBrasKey($r);
                $dag        = $r['redcap_data_access_group'] ?? '';
                $c          = $contacts->get($id, []);
                return [
                    'id'           => $id,
                    'brasKey'      => $brasKey,
                    'bras'         => self::BRAS_LABELS[$brasKey] ?? '—',
                    'village'      => self::VILLAGE_LABELS[$villageKey] ?? ($villageKey ?: '—'),
                    'cluster'      => self::CLUSTER_LABELS[self::extractCluster($r)] ?? '—',
                    'dag'          => $dag,
                    'dagLabel'     => $dagLabels[$dag] ?? ($dag !== '' ? $dag : 'Sans DAG'),
                    'chef'         => trim($c['b_hhh_fullname'] ?? ''),
                    'chefPhone'    => trim($c['b_hhh_phone'] ?? ''),
                    'contact'      => trim($c['b_hh_point_contact'] ?? ''),
                    'contactPhone' => trim($c['b_hh_point_contact_phone'] ?? ''),
                    'address'      => trim($c['b_hh_address'] ?? ''),
                    'netCodes'     => $netCodes->get($id, []),
                ];
            });
    }


    /** Liste des DAG présents parmi les ménages */
    public static function dags(Collection $households): array
    {
        return $households->groupBy('dag')->map(fn($g, $k) => [
            'key'   => $k,
            'label' => $g->first()['dagLabel'],
            'count' => $g->count(),
        ])->sortBy('label', SORT_NATURAL)->values()->all();
    }
}
