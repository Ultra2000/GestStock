<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class BackfillProductBarcodes extends Command
{
    protected $signature = 'products:generate-barcodes {--rewrite : Réécrit les codes existants}';
    protected $description = 'Génère les codes internes pour les produits sans code (ou tous avec --rewrite)';

    public function handle(): int
    {
        $rewrite = $this->option('rewrite');
        $query = Product::query();
        if (!$rewrite) {
            $query->whereNull('code')->orWhere('code', '');
        }

        $count = 0;
        $this->info($rewrite ? 'Réécriture des codes...' : 'Génération des codes manquants...');

        $query->chunkById(100, function ($products) use (&$count, $rewrite) {
            foreach ($products as $product) {
                $old = $product->code;
                $product->code = Product::generateInternalCode();
                $product->save();
                $count++;
                $this->line('Produit #'.$product->id.' : '.($old ?: '[vide]').' -> '.$product->code);
            }
        });

        $this->info("Terminé. $count produits mis à jour.");
        return Command::SUCCESS;
    }
}
