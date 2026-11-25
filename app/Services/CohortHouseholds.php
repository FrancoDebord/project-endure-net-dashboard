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


    /**
     * Membres du ménage enregistrés à la baseline (formulaire section_1_membres_du_mnage) :
     * [household_id => [['num', 'name', 'sex' (M/F), 'age' (« 56 ans » / « 8 mois »)], …]] dans l'ordre des instances.
     */
    public static function baselineMembers(array $memberRaw): array
    {
        $out = [];
        foreach ($memberRaw as $r) {
            if (($r['redcap_repeat_instrument'] ?? '') !== 'section_1_membres_du_mnage' || ($r['redcap_repeat_instance'] ?? '') === '') continue;
            if (($r['redcap_event_name'] ?? 'baseline_et_distri_arm_1') !== 'baseline_et_distri_arm_1') continue;

            $years  = trim((string) ($r['c_age_years'] ?? ''));
            $months = trim((string) ($r['c_age_months'] ?? ''));
            $age = match (true) {
                is_numeric($years) && (int) $years > 0 => (int) $years . ' an' . ((int) $years > 1 ? 's' : ''),
                is_numeric($months) && (int) $months > 0 => (int) $months . ' mois',
                is_numeric($years) => '< 1 an',
                default => '?',
            };
            $out[$r['household_id']][(int) $r['redcap_repeat_instance']] = [
                'num'  => trim((string) ($r['c_numero_personne'] ?? '')) ?: (string) $r['redcap_repeat_instance'],
                'name' => trim((string) ($r['c_hhm_name'] ?? '')) ?: '(sans nom)',
                'sex'  => ['1' => 'M', '2' => 'F'][$r['c_sex'] ?? ''] ?? '?',
                'age'  => $age,
            ];
        }
        foreach ($out as &$list) {
            ksort($list);
            $list = array_values($list);
        }
        return $out;
    }

    /** Numéro de tablette = avant-dernière composante de l'identifiant (AG-01-PD-01-001 → « 01 ») */
    public static function tablet(string $householdId): string
    {
        $parts = explode('-', trim($householdId));
        return count($parts) >= 2 ? $parts[count($parts) - 2] : '';
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
