<?php

namespace App\Console\Commands;

use App\Models\AeSelectedHousehold;
use App\Models\AeSelectionRun;
use App\Services\RedCapService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Importe dans ae_selected_households les ménages Agbondjedo (AD)
 * présents dans les données Adverse Events REDCap mais absents de la table.
 */
class ImportAgbondjedoAeHouseholds extends Command
{
    protected $signature = 'ae:import-agbondjedo {--dry-run : Affiche sans insérer}';

    protected $description = 'Importe les ménages Agbondjedo (AD) ayant des données AE REDCap et absents de ae_selected_households';

    public function __construct(private readonly RedCapService $redcap)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDry = $this->option('dry-run');
        $this->info('Récupération des données REDCap...');

        // ── 1. Ménages AD avec un enregistrement AE non-répétant ─────────────────
        $aeData = collect($this->redcap->getAdverseEventsData());
        $aeAdIds = $aeData
            ->filter(fn($r) =>
                str_starts_with($r['household_id'] ?? '', 'AD-') &&
                ($r['redcap_repeat_instrument'] ?? '') === ''
            )
            ->pluck('household_id')
            ->unique()
            ->values();

        $this->line("  → {$aeAdIds->count()} ménage(s) AD trouvé(s) dans les données AE REDCap.");

        if ($aeAdIds->isEmpty()) {
            $this->warn('Aucun ménage AD dans les données AE. Fin.');
            return self::SUCCESS;
        }

        // ── 2. Lesquels sont déjà dans la table ? ────────────────────────────────
        $existing = AeSelectedHousehold::whereIn('household_id', $aeAdIds)
            ->pluck('household_id')
            ->flip();

        $toInsert = $aeAdIds->reject(fn($id) => $existing->has($id))->values();

        $this->line("  → {$existing->count()} déjà présent(s) dans ae_selected_households.");
        $this->line("  → {$toInsert->count()} à insérer.");

        if ($toInsert->isEmpty()) {
            $this->info('Rien à insérer — table déjà à jour.');
            return self::SUCCESS;
        }

        // ── 3. Données d'identification, Form 1b, Form 4a, Moustiquaires ─────────
        $this->info('Récupération des données complémentaires...');

        $idData     = collect($this->redcap->getIdentificationData());
        $form1bData = collect($this->redcap->getForm1bData());
        $form4aData = collect($this->redcap->getForm4aData());
        $netData    = collect($this->redcap->getStudyNetData());

        // Map ménage → nb moustiquaires
        $form4aMap = $form4aData
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->pluck('nb_moustiquaires_etude', 'household_id');

        // Map ménage → codes moustiquaires (répétant)
        $netCodesMap = $netData
            ->filter(fn($r) =>
                $r['household_id'] !== '' &&
                ($r['redcap_repeat_instrument'] ?? '') !== '' &&
                !empty($r['net_identifier_man'])
            )
            ->groupBy('household_id')
            ->map(fn($rows) =>
                $rows->pluck('net_identifier_man')->filter()->unique()->sort()->values()->toArray()
            );

        // Map ménage → Form 1b (non-répétant)
        $form1bMap = $form1bData
            ->filter(fn($r) => $r['household_id'] !== '' && ($r['redcap_repeat_instrument'] ?? '') === '')
            ->keyBy('household_id');

        // Map ménage → identification (non-répétant, consentis)
        $idMap = $idData
            ->filter(fn($r) => $r['household_id'] !== '')
            ->unique('household_id')
            ->keyBy('household_id');

        // ── 4. Construire les enregistrements à insérer ──────────────────────────
        $records = $toInsert->map(function (string $id) use ($idMap, $form1bMap, $form4aMap, $netCodesMap) {
            $r = $idMap->get($id);
            if (!$r) {
                $this->warn("  [SKIP] $id : pas de données d'identification dans REDCap.");
                return null;
            }

            $arm = $this->inferArm($r);
            if ($arm === '') {
                $this->warn("  [SKIP] $id : impossible de déterminer le bras (PD/G2).");
                return null;
            }

            $form1b = $form1bMap->get($id, []);

            return [
                'household_id'           => $id,
                'arm'                    => $arm,
                'village'                => $r['village']                    ?? 'AD',
                'cluster_id'             => $r['cluster_id']                 ?? '13',
                'dag'                    => $r['redcap_data_access_group']   ?? null,
                'study_cohort'           => $r['study_cohort']               ?? null,
                'numero_menage'          => $r['numero_menage']              ?? null,
                'head_name'              => $form1b['b_hhh_fullname']               ?? null,
                'head_phone'             => $form1b['b_hhh_phone']                   ?? null,
                'contact_name'           => $form1b['b_hh_point_contact']            ?? null,
                'contact_phone'          => $form1b['b_hh_point_contact_phone']      ?? null,
                'moment_visite'          => $form1b['b_hh_moment_visite']            ?? null,
                'adresse'                => $form1b['b_hh_address']                  ?? null,
                'nb_moustiquaires_etude' => ($v = (int)($form4aMap->get($id, 0) ?: 0)) ? $v : null,
                'net_codes'              => json_encode($netCodesMap->get($id, [])),
                'form_completed'         => 0,
                'form_completed_at'      => null,
                'form_completed_by'      => null,
            ];
        })->filter()->values();

        if ($records->isEmpty()) {
            $this->warn('Aucun enregistrement valide à insérer après vérification des données REDCap.');
            return self::SUCCESS;
        }

        // ── 5. Aperçu ────────────────────────────────────────────────────────────
        $this->table(
            ['household_id', 'arm', 'village', 'cluster_id', 'nb_moustiquaires'],
            $records->map(fn($r) => [
                $r['household_id'],
                $r['arm'],
                $r['village'] ?? '—',
                $r['cluster_id'] ?? '—',
                $r['nb_moustiquaires_etude'] ?? '—',
            ])->toArray()
        );

        if ($isDry) {
            $this->info('Mode dry-run : aucun enregistrement inséré.');
            return self::SUCCESS;
        }

        // ── 6. Créer un run et insérer ───────────────────────────────────────────
        if (!$this->confirm("{$records->count()} ménage(s) seront insérés. Continuer ?", true)) {
            $this->line('Annulé.');
            return self::SUCCESS;
        }

        $run = AeSelectionRun::create([
            'arm'            => null,
            'target_count'   => $records->count(),
            'selected_count' => $records->count(),
            'notes'          => 'Import manuel — ménages Agbondjedo (AD) avec données AE REDCap',
            'created_by'     => null,
        ]);

        $insertData = $records->map(fn($r) => array_merge($r, ['run_id' => $run->id]))->toArray();

        AeSelectedHousehold::insert($insertData);

        $this->info("✓ {$records->count()} ménage(s) insérés dans ae_selected_households (run_id={$run->id}).");

        return self::SUCCESS;
    }

    private function inferArm(array $row): string
    {
        $bras = $row['bras'] ?? '';
        if ($bras === '1') return 'PD';
        if ($bras === '2') return 'G2';

        $parts = explode('-', $row['household_id'] ?? '');
        if (count($parts) >= 3) {
            $code = strtoupper($parts[2]);
            if ($code === 'PD') return 'PD';
            if ($code === 'G2') return 'G2';
        }
        return '';
    }
}
