<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sale = App\Models\Sale::where('invoice_number', 'FACT-GY5RZGND')->first();
if ($sale) {
    echo "Tax%: " . $sale->tax_percent . "\n";
} else {
    echo "Facture non trouvée\n";
}
