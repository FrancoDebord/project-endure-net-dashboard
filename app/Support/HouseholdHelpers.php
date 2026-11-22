<?php

namespace App\Support;

/**
 * Libellés et fonctions utilitaires communs sur les ménages ENDURE-Net
 * (village, grappe, bras, cohorte, parsing de household_id).
 */
trait HouseholdHelpers
{
    private const CLUSTER_LABELS = [
        '1'  => 'Djigbe / Grappe 1',    '2'  => 'Djigbe / Grappe 2',
        '3'  => 'Gbonou / Grappe 1',    '4'  => 'Gbonou / Grappe 2',    '5'  => 'Gbonou / Grappe 3',
        '6'  => 'Miniffi / Grappe 1',   '7'  => 'Miniffi / Grappe 2',   '8'  => 'Miniffi / Grappe 3',
        '9'  => 'Miniffi / Grappe 4',
        '10' => 'Dogbo / Grappe 1',     '11' => 'Agao / Grappe 1',      '12' => 'Akoba / Grappe 1',
        '13' => 'Agbondjedo / Grappe 2',
    ];
    // Mapping (village, grappe) → cluster key, derived from household ID format
    private const CLUSTER_FROM_ID = [
        'DJ' => ['01' => '1', '02' => '2'],
        'GB' => ['01' => '3', '02' => '4', '03' => '5'],
        'MI' => ['01' => '6', '02' => '7', '03' => '8'],
    ];
    // Accepte les codes numériques (1/2/3) et alphanumériques (DJ/GB/MI) selon export REDCap
    private const VILLAGE_LABELS = [
        'DJ' => 'Djigbe', '1' => 'Djigbe',
        'GB' => 'Gbonou', '2' => 'Gbonou',
        'MI' => 'Miniffi', '3' => 'Miniffi',
        'DO' => 'Dogbo',
        'AG' => 'Agao',
        'AK' => 'Akoba',
        'AD' => 'Agbondjedo',
    ];
    private const BRAS_LABELS = ['1' => 'PERMANET DUAL', '2' => 'INTERCEPTOR G2'];
    // Cluster global (1-8) → bras : PD si cluster ∈ {1,5,7,8}, G2 si ∈ {2,3,4,6}
    private const BRAS_FROM_CLUSTER = [
        '1' => '1', // Djigbe G1   → PERMANET DUAL
        '2' => '2', // Djigbe G2   → INTERCEPTOR G2
        '3' => '2', // Gbonou G1   → INTERCEPTOR G2
        '4' => '2', // Gbonou G2   → INTERCEPTOR G2
        '5' => '1', // Gbonou G3   → PERMANET DUAL
        '6' => '2', // Miniffi G1  → INTERCEPTOR G2
        '7' => '1', // Miniffi G2  → PERMANET DUAL
        '8' => '1', // Miniffi G3  → PERMANET DUAL
    ];
    private const COHORT_LABELS  = ['1' => 'Cohorte A', '2' => 'Cohorte B'];

    /**
     * Parses a household ID with format VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE.
     * Returns ['valid', 'village', 'grappe', 'bras', 'tablette', 'menage'].
     */
    private static function parseHouseholdId(string $id): array
    {
        $empty = ['valid' => false, 'village' => '', 'grappe' => '', 'bras' => '', 'tablette' => '', 'menage' => ''];
        $parts = explode('-', $id);
        if (count($parts) !== 5) return $empty;

        [$village, $grappe, $bras, $tablette, $menage] = $parts;
        $village = strtoupper($village);
        $bras    = strtoupper($bras);

        $allowedGrappes = [
            'DJ' => ['01', '02'],
            'GB' => ['01', '02', '03'],
            'MI' => ['01', '02', '03', '04'],
            'DO' => ['01'],
            'AG' => ['01'],
            'AK' => ['01'],
            'AD' => ['02'],
        ];

        $valid = isset($allowedGrappes[$village])
              && in_array($grappe, $allowedGrappes[$village])
              && in_array($bras, ['PD', 'G2'])
              && preg_match('/^(0[1-9]|1[0-9]|20)$/', $tablette)
              && preg_match('/^\d{3}$/', $menage)
              && (int)$menage >= 1;

        return compact('valid', 'village', 'grappe', 'bras', 'tablette', 'menage');
    }

    private static function inferBrasKey(string $id): string
    {
        $p = self::parseHouseholdId($id);
        if ($p['bras'] === 'G2') return '2';
        if ($p['bras'] === 'PD') return '1';
        return '';
    }

    private static function resolveBrasKey(array $hh): string
    {
        $cluster = ($hh['cluster_djigbe']  ?? '')
                ?: ($hh['cluster_gbonou']  ?? '')
                ?: ($hh['cluster_miniffi'] ?? '');
        if (isset(self::BRAS_FROM_CLUSTER[$cluster])) return self::BRAS_FROM_CLUSTER[$cluster];
        return self::inferBrasKey($hh['household_id'] ?? '');
    }

    private static function extractCluster(array $hh): string
    {
        $fromFields = ($hh['cluster_id'] ?? '')
            ?: ($hh['cluster_djigbe'] ?? '')
            ?: ($hh['cluster_gbonou'] ?? '')
            ?: ($hh['cluster_miniffi'] ?? '');
        if ($fromFields !== '') return $fromFields;

        $p = self::parseHouseholdId($hh['household_id'] ?? '');
        return self::CLUSTER_FROM_ID[$p['village']][$p['grappe']] ?? '';
    }
}
