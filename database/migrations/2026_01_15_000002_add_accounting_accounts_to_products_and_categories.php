<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les comptes comptables spécifiques aux produits et catégories
     * pour permettre une hiérarchie : Produit > Catégorie > Défaut Global
     */
    public function up(): void
    {
        // Ajouter les comptes aux produits
        Schema::table('products', function (Blueprint $table) {
            $table->string('account_sales', 15)->nullable()->after('vat_category')
                ->comment('Compte vente spécifique (ex: 701000 pour marchandises)');
            $table->string('account_purchases', 15)->nullable()->after('account_sales')
                ->comment('Compte achat spécifique (ex: 601000)');
            $table->string('account_vat_collected', 15)->nullable()->after('account_purchases')
                ->comment('Compte TVA collectée spécifique');
            $table->string('account_vat_deductible', 15)->nullable()->after('account_vat_collected')
                ->comment('Compte TVA déductible spécifique');
        });

        // Ajouter les comptes aux catégories comptables
        Schema::table('accounting_categories', function (Blueprint $table) {
            $table->string('account_number', 15)->nullable()->after('description')
                ->comment('Compte PCG par défaut pour cette catégorie');
            $table->string('account_vat', 15)->nullable()->after('account_number')
                ->comment('Compte TVA associé à cette catégorie');
            $table->decimal('default_vat_rate', 5, 2)->nullable()->after('account_vat')
                ->comment('Taux TVA par défaut (20.00, 10.00, 5.50, 2.10)');
        });

        // Ajouter une relation catégorie comptable aux produits
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('accounting_category_id')->nullable()->after('account_vat_deductible')
                ->constrained('accounting_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['accounting_category_id']);
            $table->dropColumn([
                'account_sales',
                'account_purchases', 
                'account_vat_collected',
                'account_vat_deductible',
                'accounting_category_id',
            ]);
        });

        Schema::table('accounting_categories', function (Blueprint $table) {
            $table->dropColumn([
                'account_number',
                'account_vat',
                'default_vat_rate',
            ]);
        });
    }
};
