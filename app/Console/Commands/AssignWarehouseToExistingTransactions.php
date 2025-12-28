<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class AssignWarehouseToExistingTransactions extends Command
{
    protected $signature = 'warehouse:assign-transactions {--dry-run : Afficher les changements sans les appliquer}';

    protected $description = 'Assigne l\'entrepôt par défaut aux ventes et achats existants qui n\'en ont pas';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $companies = Company::all();

        foreach ($companies as $company) {
            $this->info("Traitement de l'entreprise: {$company->name}");

            $defaultWarehouse = Warehouse::getDefault($company->id);

            if (!$defaultWarehouse) {
                // Créer un entrepôt par défaut s'il n'existe pas
                $defaultWarehouse = Warehouse::where('company_id', $company->id)->first();
                
                if (!$defaultWarehouse) {
                    $this->warn("  Aucun entrepôt trouvé. Création d'un entrepôt par défaut...");
                    
                    if (!$dryRun) {
                        $defaultWarehouse = Warehouse::create([
                            'company_id' => $company->id,
                            'code' => 'MAIN',
                            'name' => 'Entrepôt Principal',
                            'type' => 'warehouse',
                            'is_default' => true,
                            'is_active' => true,
                        ]);
                    }
                } else {
                    if (!$dryRun) {
                        $defaultWarehouse->setAsDefault();
                    }
                }
            }

            if (!$defaultWarehouse) {
                $this->error("  Impossible de déterminer l'entrepôt par défaut");
                continue;
            }

            $this->info("  Entrepôt par défaut: {$defaultWarehouse->name}");

            // Mettre à jour les ventes sans entrepôt
            $salesCount = Sale::where('company_id', $company->id)
                ->whereNull('warehouse_id')
                ->count();

            $this->info("  Ventes sans entrepôt: {$salesCount}");

            if (!$dryRun && $salesCount > 0) {
                Sale::where('company_id', $company->id)
                    ->whereNull('warehouse_id')
                    ->update(['warehouse_id' => $defaultWarehouse->id]);
            }

            // Mettre à jour les achats sans entrepôt
            $purchasesCount = Purchase::where('company_id', $company->id)
                ->whereNull('warehouse_id')
                ->count();

            $this->info("  Achats sans entrepôt: {$purchasesCount}");

            if (!$dryRun && $purchasesCount > 0) {
                Purchase::where('company_id', $company->id)
                    ->whereNull('warehouse_id')
                    ->update(['warehouse_id' => $defaultWarehouse->id]);
            }
        }

        if ($dryRun) {
            $this->warn("\nMode dry-run: Aucune modification effectuée.");
        } else {
            $this->info("\nTerminé!");
        }

        return Command::SUCCESS;
    }
}
