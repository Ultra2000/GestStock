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
