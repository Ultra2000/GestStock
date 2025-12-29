<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter TVA aux lignes de vente
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(20.00)->after('unit_price');
            $table->decimal('unit_price_ht', 15, 2)->nullable()->after('vat_rate');
            $table->decimal('vat_amount', 15, 2)->nullable()->after('unit_price_ht');
            $table->decimal('total_price_ht', 15, 2)->nullable()->after('vat_amount');
            $table->string('vat_category', 10)->default('S')->after('total_price');
        });

        // Ajouter TVA aux lignes d'achat
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(20.00)->after('unit_price');
            $table->decimal('unit_price_ht', 15, 2)->nullable()->after('vat_rate');
            $table->decimal('vat_amount', 15, 2)->nullable()->after('unit_price_ht');
            $table->decimal('total_price_ht', 15, 2)->nullable()->after('vat_amount');
        });

        // Ajouter totaux TVA aux ventes
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_ht', 15, 2)->nullable()->after('total');
            $table->decimal('total_vat', 15, 2)->nullable()->after('total_ht');
        });

        // Ajouter totaux TVA aux achats
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_ht', 15, 2)->nullable()->after('total');
            $table->decimal('total_vat', 15, 2)->nullable()->after('total_ht');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'unit_price_ht', 'vat_amount', 'total_price_ht', 'vat_category']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'unit_price_ht', 'vat_amount', 'total_price_ht']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['total_ht', 'total_vat']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['total_ht', 'total_vat']);
        });
    }
};
