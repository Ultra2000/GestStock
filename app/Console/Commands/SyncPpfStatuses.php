<?php

namespace App\Console\Commands;

use App\Services\Integration\PpfService;
use Illuminate\Console\Command;

class SyncPpfStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ppf:sync-statuses {--company= : ID de la société (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les statuts des factures depuis Chorus Pro / PPF';

    /**
     * Execute the console command.
     */
    public function handle(PpfService $ppfService)
    {
        $this->info('Synchronisation des statuts PPF...');
        
        $companyId = $this->option('company');
        
        try {
            $synced = $ppfService->syncAllPendingInvoices($companyId ? (int) $companyId : null);
            
            $this->info("✓ {$synced} facture(s) synchronisée(s).");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
