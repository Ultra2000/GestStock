<?php
/**
 * Script pour préparer les données et tester l'envoi via PpfService
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\Customer;
use App\Models\Sale;

echo "=== VERIFICATION DES DONNEES ===\n\n";

// 1. Vérifier/Mettre à jour la Company
$company = Company::first();
echo "COMPANY:\n";
echo "  Nom: {$company->name}\n";
echo "  SIRET: {$company->siret}\n";
echo "  City: " . ($company->city ?? 'MANQUANT') . "\n";
echo "  Zip: " . ($company->zip_code ?? 'MANQUANT') . "\n";
echo "  Country: " . ($company->country_code ?? 'MANQUANT') . "\n";

// Mettre à jour les champs manquants pour la company
if (empty($company->city) || empty($company->zip_code) || empty($company->country_code)) {
    // Parser l'adresse pour extraire code postal et ville
    // Adresse actuelle: "8 RUE DE LONDRES 75009 PARIS"
    if (preg_match('/(\d{5})\s+([A-Z]+)/', $company->address, $matches)) {
        $company->zip_code = $matches[1];
        $company->city = $matches[2];
    } else {
        $company->zip_code = '75009';
        $company->city = 'PARIS';
    }
    $company->country_code = 'FR';
    $company->save();
    echo "  -> Company mise à jour!\n";
}

// 2. Vérifier le Customer
echo "\nCUSTOMER (premier client):\n";
$customer = Customer::first();
echo "  Nom: {$customer->name}\n";
echo "  SIRET: " . ($customer->siret ?? 'MANQUANT') . "\n";
echo "  City: " . ($customer->city ?? 'MANQUANT') . "\n";
echo "  Zip: " . ($customer->zip_code ?? 'MANQUANT') . "\n";
echo "  Country: " . ($customer->country_code ?? 'MANQUANT') . "\n";

// Mettre à jour le customer avec un SIRET de test Chorus Pro
if (empty($customer->siret)) {
    // SIRET de test pour le sandbox Chorus Pro
    $customer->siret = '46096855178036';
    $customer->address = '1 RUE DE TEST';
    $customer->zip_code = '75001';
    $customer->city = 'PARIS';
    $customer->country_code = 'FR';
    $customer->save();
    echo "  -> Customer mis à jour avec SIRET de test sandbox!\n";
}

// 3. Vérifier la Sale
echo "\nSALE (première vente):\n";
$sale = Sale::with(['company', 'customer', 'items.product'])->first();
echo "  Numéro: {$sale->invoice_number}\n";
echo "  Total: {$sale->total}\n";
echo "  Tax%: {$sale->tax_percent}\n";
echo "  PPF Status: " . ($sale->ppf_status ?? 'NULL') . "\n";
echo "  PPF ID: " . ($sale->ppf_id ?? 'NULL') . "\n";
echo "  Items: " . $sale->items->count() . "\n";

echo "\n=== DONNEES PRETE POUR TEST ===\n";
echo "Vous pouvez maintenant envoyer la facture via l'interface Filament\n";
echo "ou exécuter: php test_ppf_real.php\n";
