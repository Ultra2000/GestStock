<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Synchronisation automatique des statuts PPF (Chorus Pro)
 * Toutes les 30 minutes, on vérifie les factures en attente de statut.
 */
Schedule::call(function () {
    $companies = \App\Models\Company::whereHas('integrations', function ($q) {
        $q->where('type', 'ppf')->where('is_active', true);
    })->get();

    foreach ($companies as $company) {
        try {
            $ppfService = new \App\Services\Integration\PpfService($company);
            $ppfService->syncAllPendingInvoices();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("PPF sync failed for company {$company->id}: " . $e->getMessage());
        }
    }
})->everyThirtyMinutes()->name('ppf-sync-statuses')->withoutOverlapping();
