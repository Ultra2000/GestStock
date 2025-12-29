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
        // Ajouter TVA aux lignes de devis
        Schema::table('quote_items', function (Blueprint $table) {
            // Renommer tax_rate existant en vat_rate pour cohérence
            if (Schema::hasColumn('quote_items', 'tax_rate')) {
                $table->renameColumn('tax_rate', 'vat_rate');
            } else {
                $table->decimal('vat_rate', 5, 2)->default(20.00)->after('discount_percent');
            }
            
            $table->decimal('unit_price_ht', 15, 2)->nullable()->after('unit_price');
            $table->decimal('vat_amount', 15, 2)->nullable()->after('unit_price_ht');
            $table->decimal('total_price_ht', 15, 2)->nullable()->after('vat_amount');
            $table->string('vat_category', 10)->default('S')->after('total_price');
        });

        // Ajouter totaux TVA aux devis
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('total_ht', 15, 2)->nullable()->after('total');
            $table->decimal('total_vat', 15, 2)->nullable()->after('total_ht');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            if (Schema::hasColumn('quote_items', 'vat_rate')) {
                $table->renameColumn('vat_rate', 'tax_rate');
            }
            $table->dropColumn(['unit_price_ht', 'vat_amount', 'total_price_ht', 'vat_category']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['total_ht', 'total_vat']);
        });
    }
};
