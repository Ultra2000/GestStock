<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = App\Models\Sale::latest()->take(5)->get();
echo "=== Analyse des ventes ===\n\n";

foreach ($sales as $s) {
    echo "Facture: " . $s->invoice_number . "\n";
    echo "  - Total TTC stocké: " . $s->total . " FCFA\n";
    echo "  - Total HT stocké: " . ($s->total_ht ?? 'NULL') . " FCFA\n";
    echo "  - TVA % stocké: " . ($s->tax_percent ?? 'NULL') . "%\n";
    echo "  - TVA montant stocké: " . ($s->total_vat ?? 'NULL') . " FCFA\n";
    echo "  - Remise %: " . ($s->discount_percent ?? 0) . "%\n";
    
    // Calculer depuis les items
    $itemsTotal = $s->items->sum('total_price');
    echo "  - Somme items: " . $itemsTotal . " FCFA\n";
    echo "\n";
}
