<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMPANY SETTINGS ===\n";
$company = \App\Models\Company::first();
echo "Name: " . $company->name . "\n";
echo "SIRET: " . $company->siret . "\n";
echo "Address: " . $company->address . "\n";
echo "City: " . $company->city . "\n";
echo "Zip Code: " . $company->zip_code . "\n";
echo "Country Code: " . $company->country_code . "\n";
echo "Currency: " . $company->currency . "\n";
echo "Tax Number: " . $company->tax_number . "\n";

echo "\n=== CUSTOMER ===\n";
$customer = \App\Models\Customer::first();
echo "Name: " . $customer->name . "\n";
echo "SIRET: " . $customer->siret . "\n";
echo "Address: " . $customer->address . "\n";
echo "City: " . $customer->city . "\n";
echo "Zip Code: " . $customer->zip_code . "\n";
echo "Country Code: " . $customer->country_code . "\n";

echo "\n=== SALE ===\n";
$sale = \App\Models\Sale::with(['company', 'customer', 'items.product'])->first();
echo "Invoice Number: " . $sale->invoice_number . "\n";
echo "Total: " . $sale->total . "\n";
echo "Tax Percent: " . $sale->tax_percent . "\n";
echo "Items count: " . $sale->items->count() . "\n";
