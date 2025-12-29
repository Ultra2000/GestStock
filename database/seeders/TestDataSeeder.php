<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée des données de test pour produits, clients et fournisseurs
     */
    public function run(): void
    {
        $this->command->info('🌱 Création des données de test...');

        // Récupérer la première entreprise ou en créer une
        $company = Company::first();
        if (!$company) {
            $this->command->error('Aucune entreprise trouvée. Veuillez d\'abord créer une entreprise.');
            return;
        }

        $companyId = $company->id;
        $this->command->info("📦 Entreprise: {$company->name} (ID: {$companyId})");

        // Créer un entrepôt par défaut si nécessaire
        $warehouse = Warehouse::where('company_id', $companyId)->first();
        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name' => 'Entrepôt Principal',
                'code' => 'WH-MAIN',
                'address' => '123 Rue du Commerce',
                'city' => 'Paris',
                'is_default' => true,
                'is_active' => true,
            ]);
            $this->command->info("🏭 Entrepôt créé: {$warehouse->name}");
        }

        // ========================================
        // FOURNISSEURS
        // ========================================
        $this->command->info('📋 Création des fournisseurs...');
        
        $suppliers = [
            [
                'name' => 'Tech Distribution SARL',
                'email' => 'contact@techdistrib.fr',
                'phone' => '01 23 45 67 89',
                'address' => '45 Avenue des Technologies',
                'city' => 'Lyon',
                'country' => 'France',
                'notes' => 'Fournisseur principal de matériel informatique',
            ],
            [
                'name' => 'Bureau Pro',
                'email' => 'commandes@bureaupro.fr',
                'phone' => '01 98 76 54 32',
                'address' => '12 Rue des Bureaux',
                'city' => 'Marseille',
                'country' => 'France',
                'notes' => 'Fournitures de bureau et papeterie',
            ],
            [
                'name' => 'Import Asia',
                'email' => 'sales@importasia.com',
                'phone' => '+33 1 11 22 33 44',
                'address' => '78 Boulevard International',
                'city' => 'Paris',
                'country' => 'France',
                'notes' => 'Importation produits électroniques',
            ],
            [
                'name' => 'Mobilier Express',
                'email' => 'pro@mobilierexpress.fr',
                'phone' => '04 55 66 77 88',
                'address' => '25 Zone Industrielle Nord',
                'city' => 'Bordeaux',
                'country' => 'France',
                'notes' => 'Mobilier de bureau et rangement',
            ],
            [
                'name' => 'Alimentaire Gros',
                'email' => 'commande@alimentairegros.fr',
                'phone' => '05 44 33 22 11',
                'address' => '8 Marché de Gros',
                'city' => 'Rungis',
                'country' => 'France',
                'notes' => 'Produits alimentaires en gros',
            ],
        ];

        $supplierIds = [];
        foreach ($suppliers as $data) {
            $supplier = Supplier::create(array_merge($data, ['company_id' => $companyId]));
            $supplierIds[] = $supplier->id;
        }
        $this->command->info("   ✅ " . count($suppliers) . " fournisseurs créés");

        // ========================================
        // CLIENTS
        // ========================================
        $this->command->info('👥 Création des clients...');
        
        $customers = [
            [
                'name' => 'Martin Dupont',
                'email' => 'martin.dupont@email.fr',
                'phone' => '06 12 34 56 78',
                'address' => '15 Rue de la Paix',
                'city' => 'Paris',
                'zip_code' => '75001',
                'country' => 'France',
                'country_code' => 'FR',
                'notes' => 'Client fidèle depuis 2020',
            ],
            [
                // SIRET du dataset Chorus Pro - Structure Publique
                'name' => 'MOA 10771702097150',
                'email' => 'USER_PUBLIQUE_2_10771702097150@cpro.fr',
                'phone' => '01 42 42 42 42',
                'address' => '100 Avenue des Champs-Élysées',
                'city' => 'Paris',
                'zip_code' => '75008',
                'country' => 'France',
                'country_code' => 'FR',
                'registration_number' => '107717020',
                'siret' => '10771702097150',
                'tax_number' => 'FR10771702097',
                'notes' => 'Structure Publique - Dataset Chorus Pro',
            ],
            [
                'name' => 'Marie Leroy',
                'email' => 'marie.leroy@gmail.com',
                'phone' => '06 98 76 54 32',
                'address' => '8 Place du Marché',
                'city' => 'Lyon',
                'zip_code' => '69001',
                'country' => 'France',
                'country_code' => 'FR',
            ],
            [
                // SIRET du dataset Chorus Pro - Structure Publique
                'name' => 'Destinataire 44905388818963',
                'email' => 'USER_PUBLIQUE_1_44905388818963@cpro.fr',
                'phone' => '04 78 90 12 34',
                'address' => '25 Rue Gastronomique',
                'city' => 'Lyon',
                'zip_code' => '69002',
                'country' => 'France',
                'country_code' => 'FR',
                'registration_number' => '449053888',
                'siret' => '44905388818963',
                'notes' => 'Structure Publique - Dataset Chorus Pro',
            ],
            [
                'name' => 'Jean-Pierre Bernard',
                'email' => 'jpbernard@free.fr',
                'phone' => '06 11 22 33 44',
                'address' => '3 Impasse des Lilas',
                'city' => 'Marseille',
                'zip_code' => '13001',
                'country' => 'France',
                'country_code' => 'FR',
            ],
            [
                // SIRET du dataset Chorus Pro - Structure Privée
                'name' => 'MOE 12409381695205',
                'email' => 'USER_PRIVE_1_12409381695205@cpro.fr',
                'phone' => '01 55 66 77 88',
                'address' => '42 Boulevard de la Santé',
                'city' => 'Paris',
                'zip_code' => '75013',
                'country' => 'France',
                'country_code' => 'FR',
                'registration_number' => '124093816',
                'siret' => '12409381695205',
                'notes' => 'Structure Privée - Dataset Chorus Pro',
            ],
            [
                // SIRET du dataset Chorus Pro - Structure Publique
                'name' => 'Destinataire 46096855178036',
                'email' => 'USER_PUBLIQUE_ACHETEUR_3_46096855178036@cpro.fr',
                'phone' => '01 44 55 66 77',
                'address' => '10 Rue de l\'École',
                'city' => 'Nantes',
                'zip_code' => '44000',
                'country' => 'France',
                'country_code' => 'FR',
                'registration_number' => '460968551',
                'siret' => '46096855178036',
                'notes' => 'Structure Publique - Dataset Chorus Pro',
            ],
            [
                'name' => 'Pierre Moreau',
                'email' => 'p.moreau@outlook.com',
                'phone' => '06 77 88 99 00',
                'address' => '56 Avenue Victor Hugo',
                'city' => 'Bordeaux',
                'zip_code' => '33000',
                'country' => 'France',
                'country_code' => 'FR',
            ],
            [
                // SIRET du dataset Chorus Pro - Structure Privée
                'name' => 'Valideur 25019885130961',
                'email' => 'USER_PRIVE_2_25019885130961@cpro.fr',
                'phone' => '09 87 65 43 21',
                'address' => 'Station F, 5 Parvis Alan Turing',
                'city' => 'Paris',
                'zip_code' => '75013',
                'country' => 'France',
                'country_code' => 'FR',
                'registration_number' => '250198851',
                'siret' => '25019885130961',
                'notes' => 'Structure Privée - Dataset Chorus Pro',
            ],
            [
                // SIRET du dataset Chorus Pro - Structure Privée
                'name' => 'Fournisseur 35068473658377',
                'email' => 'USER_PRIVE_3_35068473658377@cpro.fr',
                'phone' => '05 61 62 63 64',
                'address' => '1 Place du Village',
                'city' => 'Toulouse',
                'zip_code' => '31000',
                'country' => 'France',
                'country_code' => 'FR',
                'registration_number' => '350684736',
                'siret' => '35068473658377',
                'notes' => 'Structure Privée - Dataset Chorus Pro',
            ],
        ];

        foreach ($customers as $data) {
            Customer::create(array_merge($data, ['company_id' => $companyId]));
        }
        $this->command->info("   ✅ " . count($customers) . " clients créés");

        // ========================================
        // PRODUITS
        // ========================================
        $this->command->info('📦 Création des produits...');
        
        $products = [
            // Informatique
            [
                'name' => 'Ordinateur portable HP ProBook',
                'description' => 'Ordinateur portable 15.6" - Intel i5 - 8Go RAM - 256Go SSD',
                'purchase_price' => 450.00,
                'price' => 699.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 15,
                'min_stock' => 3,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[0],
            ],
            [
                'name' => 'Écran Dell 24" Full HD',
                'description' => 'Moniteur LED 24 pouces - 1920x1080 - HDMI/VGA',
                'purchase_price' => 120.00,
                'price' => 189.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 25,
                'min_stock' => 5,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[0],
            ],
            [
                'name' => 'Clavier sans fil Logitech',
                'description' => 'Clavier AZERTY sans fil avec pavé numérique',
                'purchase_price' => 25.00,
                'price' => 45.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 50,
                'min_stock' => 10,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[0],
            ],
            [
                'name' => 'Souris optique USB',
                'description' => 'Souris filaire USB ergonomique',
                'purchase_price' => 8.00,
                'price' => 15.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 100,
                'min_stock' => 20,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[0],
            ],
            [
                'name' => 'Câble HDMI 2m',
                'description' => 'Câble HDMI 2.0 haute vitesse - 2 mètres',
                'purchase_price' => 5.00,
                'price' => 12.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 75,
                'min_stock' => 15,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[2],
            ],
            
            // Fournitures bureau
            [
                'name' => 'Ramette papier A4 80g',
                'description' => 'Papier blanc A4 - 500 feuilles - 80g/m²',
                'purchase_price' => 3.50,
                'price' => 5.99,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 200,
                'min_stock' => 50,
                'unit' => 'ramette',
                'supplier_id' => $supplierIds[1],
            ],
            [
                'name' => 'Stylo bille bleu (lot de 10)',
                'description' => 'Stylos bille pointe moyenne - encre bleue',
                'purchase_price' => 2.00,
                'price' => 4.50,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 150,
                'min_stock' => 30,
                'unit' => 'lot',
                'supplier_id' => $supplierIds[1],
            ],
            [
                'name' => 'Classeur A4 dos 80mm',
                'description' => 'Classeur à levier A4 - Dos 80mm - Couleurs assorties',
                'purchase_price' => 2.50,
                'price' => 4.99,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 80,
                'min_stock' => 20,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[1],
            ],
            [
                'name' => 'Post-it 76x76mm (lot 12)',
                'description' => 'Notes repositionnables jaunes - 12 blocs de 100 feuilles',
                'purchase_price' => 8.00,
                'price' => 14.90,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 60,
                'min_stock' => 15,
                'unit' => 'lot',
                'supplier_id' => $supplierIds[1],
            ],
            [
                'name' => 'Agrafeuse de bureau',
                'description' => 'Agrafeuse métal - Capacité 25 feuilles',
                'purchase_price' => 6.00,
                'price' => 12.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 40,
                'min_stock' => 10,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[1],
            ],
            
            // Mobilier
            [
                'name' => 'Chaise de bureau ergonomique',
                'description' => 'Fauteuil de bureau avec accoudoirs - Hauteur réglable',
                'purchase_price' => 85.00,
                'price' => 149.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 20,
                'min_stock' => 5,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[3],
            ],
            [
                'name' => 'Bureau droit 160x80cm',
                'description' => 'Bureau professionnel - Plateau mélaminé - Pieds métal',
                'purchase_price' => 150.00,
                'price' => 249.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 10,
                'min_stock' => 2,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[3],
            ],
            [
                'name' => 'Armoire de rangement haute',
                'description' => 'Armoire 2 portes - 4 étagères - H180xL80xP40cm',
                'purchase_price' => 120.00,
                'price' => 199.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 8,
                'min_stock' => 2,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[3],
            ],
            
            // Électronique
            [
                'name' => 'Imprimante laser monochrome',
                'description' => 'Imprimante laser A4 - 30 ppm - WiFi - Recto-verso',
                'purchase_price' => 180.00,
                'price' => 279.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 12,
                'min_stock' => 3,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[2],
            ],
            [
                'name' => 'Téléphone IP Cisco',
                'description' => 'Téléphone VoIP professionnel - Écran LCD',
                'purchase_price' => 75.00,
                'price' => 129.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 18,
                'min_stock' => 5,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[2],
            ],
            [
                'name' => 'Webcam HD 1080p',
                'description' => 'Webcam USB - Full HD - Microphone intégré',
                'purchase_price' => 35.00,
                'price' => 59.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 30,
                'min_stock' => 8,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[2],
            ],
            [
                'name' => 'Casque audio avec micro',
                'description' => 'Casque stéréo USB - Microphone antibruit',
                'purchase_price' => 28.00,
                'price' => 49.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 35,
                'min_stock' => 10,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[2],
            ],
            
            // Consommables
            [
                'name' => 'Cartouche encre noire HP',
                'description' => 'Cartouche d\'encre originale HP 302 XL Noir',
                'purchase_price' => 22.00,
                'price' => 35.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 45,
                'min_stock' => 10,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[0],
            ],
            [
                'name' => 'Toner laser Brother',
                'description' => 'Toner compatible Brother TN-2420 - 3000 pages',
                'purchase_price' => 35.00,
                'price' => 55.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 25,
                'min_stock' => 5,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[0],
            ],
            [
                'name' => 'Clé USB 32Go',
                'description' => 'Clé USB 3.0 - 32 Go - Lecture 100 Mo/s',
                'purchase_price' => 6.00,
                'price' => 12.00,
                'vat_rate_purchase' => 20,
                'vat_rate_sale' => 20,
                'stock' => 60,
                'min_stock' => 15,
                'unit' => 'pièce',
                'supplier_id' => $supplierIds[2],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::create(array_merge($data, [
                'company_id' => $companyId,
                'prices_include_vat' => false,
            ]));
        }
        $this->command->info("   ✅ " . count($products) . " produits créés");

        // ========================================
        // RÉSUMÉ
        // ========================================
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('✅ DONNÉES DE TEST CRÉÉES AVEC SUCCÈS !');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->table(
            ['Élément', 'Quantité'],
            [
                ['Fournisseurs', count($suppliers)],
                ['Clients', count($customers)],
                ['Produits', count($products)],
            ]
        );
    }
}
