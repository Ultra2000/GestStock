<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Synchronisation automatique des statuts PPF (Chorus Pro)
 * Toutes les 5 minutes pour les factures récentes (< 48h),
 * toutes les 30 minutes pour les plus anciennes.
 * L'API PISTE ne supporte pas de webhook/push.
 */
Schedule::call(function () {
    $companies = \App\Models\Company::whereHas('integrations', function ($q) {
        $q->where('service_name', 'ppf')->where('is_active', true);
    })->get();

    foreach ($companies as $company) {
        try {
            $ppfService = app(\App\Services\Integration\PpfService::class);
            $ppfService->syncRecentPendingInvoices($company->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("PPF sync (recent) failed for company {$company->id}: " . $e->getMessage());
        }
    }
})->everyFiveMinutes()->name('ppf-sync-recent')->withoutOverlapping();

Schedule::call(function () {
    $companies = \App\Models\Company::whereHas('integrations', function ($q) {
        $q->where('service_name', 'ppf')->where('is_active', true);
    })->get();

    foreach ($companies as $company) {
        try {
            $ppfService = app(\App\Services\Integration\PpfService::class);
            $ppfService->syncAllPendingInvoices($company->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("PPF sync (all) failed for company {$company->id}: " . $e->getMessage());
        }
    }
})->everyThirtyMinutes()->name('ppf-sync-all')->withoutOverlapping();

/**
 * Traitement des commandes récurrentes dues
 * Toutes les heures — génère automatiquement les commandes planifiées
 */
Schedule::command('orders:process-recurring')
    ->hourly()
    ->name('process-recurring-orders')
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Traitement des présences RH
 * Tous les jours à 23h30 — clôture les pointages du jour
 */
Schedule::command('hr:process-attendance')
    ->dailyAt('23:30')
    ->name('process-attendance')
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Calcul des commissions
 * Tous les 1er du mois à 01h00 — calcule les commissions du mois précédent
 */
Schedule::command('hr:calculate-commissions')
    ->monthlyOn(1, '01:00')
    ->name('calculate-commissions')
    ->withoutOverlapping()
    ->runInBackground();

/**
 * Nettoyage fichiers conversions > 7 jours
 */
Schedule::command('conversions:clean --days=7')
    ->dailyAt('03:00')
    ->name('clean-old-conversions')
    ->withoutOverlapping();

/**
 * Rappels expiration trial
 * Tous les jours à 09h00 — envoie les emails J-7, J-3 et J expiration
 */
Schedule::command('notifications:send-trial-reminders')
    ->dailyAt('09:00')
    ->name('send-trial-reminders')
    ->withoutOverlapping()
    ->runInBackground();
