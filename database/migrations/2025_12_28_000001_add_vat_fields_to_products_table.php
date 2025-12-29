<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les champs TVA et prix HT/TTC pour une gestion comptable correcte
     * 
     * - TVA Déductible (achats) : taux appliqué lors de l'achat fournisseur
     * - TVA Collectée (ventes) : taux appliqué lors de la vente client
     * - Prix HT : base de calcul pour la marge
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Taux de TVA
            $table->decimal('vat_rate_purchase', 5, 2)->default(20.00)->after('purchase_price')
                ->comment('Taux TVA à l\'achat (déductible) en %');
            $table->decimal('vat_rate_sale', 5, 2)->default(20.00)->after('price')
                ->comment('Taux TVA à la vente (collectée) en %');
            
            // Prix HT explicites (pour clarté et calculs de marge)
            $table->decimal('purchase_price_ht', 12, 2)->nullable()->after('purchase_price')
                ->comment('Prix d\'achat HT');
            $table->decimal('sale_price_ht', 12, 2)->nullable()->after('price')
                ->comment('Prix de vente HT');
            
            // Indicateur si les prix saisis sont HT ou TTC
            $table->boolean('prices_include_vat')->default(false)->after('vat_rate_sale')
                ->comment('Si true, les prix saisis sont TTC, sinon HT');
            
            // Catégorie TVA pour Chorus Pro
            $table->string('vat_category', 10)->default('S')->after('vat_rate_sale')
                ->comment('Catégorie TVA: S=Standard, Z=Zero, E=Exempt, AE=Autoliquidation');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'vat_rate_purchase',
                'vat_rate_sale', 
                'purchase_price_ht',
                'sale_price_ht',
                'prices_include_vat',
                'vat_category',
            ]);
        });
    }
};
