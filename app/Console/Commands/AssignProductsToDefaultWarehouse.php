<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignProductsToDefaultWarehouse extends Command
{
    protected $signature = 'products:assign-warehouse 
                            {--company= : ID de l\'entreprise (toutes si non spécifié)}
                            {--dry-run : Afficher les actions sans les exécuter}';

    protected $description = 'Assigne les produits sans entrepôt à l\'entrepôt par défaut de leur entreprise';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Mode simulation (dry-run) - Aucune modification ne sera effectuée');
        }

        // Récupérer les produits sans assignation d'entrepôt
        $query = Product::query()
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_warehouse')
                    ->whereColumn('product_warehouse.product_id', 'products.id');
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('✅ Tous les produits sont déjà assignés à un entrepôt.');
            return self::SUCCESS;
        }

        $this->info("📦 {$products->count()} produit(s) sans entrepôt trouvé(s).");

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $assigned = 0;
        $skipped = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                // Trouver l'entrepôt par défaut
                $warehouse = Warehouse::getDefault($product->company_id);
                
                if (!$warehouse) {
                    $warehouse = Warehouse::where('company_id', $product->company_id)
                        ->where('is_active', true)
                        ->first();
                }

                if (!$warehouse) {
                    $skipped++;
                    $errors[] = "Produit #{$product->id} ({$product->name}): Aucun entrepôt disponible";
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    // Assigner le produit à l'entrepôt
                    DB::table('product_warehouse')->insert([
                        'company_id' => $product->company_id,
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => $product->stock ?? 0,
                        'reserved_quantity' => 0,
                        'min_quantity' => $product->min_stock,
                        'reorder_point' => $product->min_stock,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $assigned++;

            } catch (\Exception $e) {
                $errors[] = "Produit #{$product->id}: {$e->getMessage()}";
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$assigned} produit(s) assigné(s) à leur entrepôt par défaut.");
        
        if ($skipped > 0) {
            $this->warn("⚠️  {$skipped} produit(s) non traité(s).");
            
            if ($this->option('verbose')) {
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }
        }

        return self::SUCCESS;
    }
}
