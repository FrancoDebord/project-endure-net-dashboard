<?php

use App\Services\CohortAFollowUpService;
use App\Services\RedCapService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Rafraîchissement du cache REDCap en arrière-plan, pour que les utilisateurs
 * ne subissent jamais le chargement depuis REDCap (1 à 2 min à froid).
 * Les anciennes données restent servies tant que le nouvel export n'a pas réussi.
 */
Artisan::command('redcap:warm {--followup : Uniquement les données de suivi (Cohortes A et B)}', function (RedCapService $redcap) {
    set_time_limit(0);
    $t = microtime(true);
    $only = $this->option('followup')
        ? array_merge(
            array_map(fn($k) => "fu_a_$k", array_keys(RedCapService::COHORT_A_EVENTS)),
            array_map(fn($k) => "fu_b_$k", array_keys(RedCapService::COHORT_B_EVENTS)),
            ['ua_24'],
        )
        : null;
    $redcap->warm($only, force: true);
    Cache::store('file')->forget(CohortAFollowUpService::COMPUTED_CACHE_KEY);
    Cache::store('file')->forget(\App\Services\CohortBFollowUpService::COMPUTED_CACHE_KEY);
    $this->info('Cache REDCap rafraîchi en ' . round(microtime(true) - $t, 1) . ' s');
})->purpose('Rafraîchit le cache des données REDCap');

// Nécessite l'exécution de « php artisan schedule:run » chaque minute (cron / Planificateur de tâches)
Schedule::command('redcap:warm')->cron('0 */4 * * *')->withoutOverlapping()->runInBackground();
Schedule::command('redcap:warm --followup')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();
