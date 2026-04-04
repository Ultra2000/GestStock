<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->default(20)->after('unit_price');
            }
            if (!Schema::hasColumn('purchase_items', 'unit_price_ht')) {
                $table->decimal('unit_price_ht', 10, 2)->default(0)->after('vat_rate');
            }
            if (!Schema::hasColumn('purchase_items', 'vat_amount')) {
                $table->decimal('vat_amount', 10, 2)->default(0)->after('unit_price_ht');
            }
            if (!Schema::hasColumn('purchase_items', 'total_price_ht')) {
                $table->decimal('total_price_ht', 10, 2)->default(0)->after('vat_amount');
            }
            // Renommer subtotal -> total_price si l'ancienne colonne existe encore
            if (Schema::hasColumn('purchase_items', 'subtotal') && !Schema::hasColumn('purchase_items', 'total_price')) {
                $table->renameColumn('subtotal', 'total_price');
            } elseif (!Schema::hasColumn('purchase_items', 'total_price')) {
                $table->decimal('total_price', 10, 2)->default(0);
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('purchase_items', function (Blueprint $table) {
                $table->decimal('quantity', 10, 2)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['vat_rate', 'unit_price_ht', 'vat_amount', 'total_price_ht'],
                fn ($col) => Schema::hasColumn('purchase_items', $col)
            ));
            if (Schema::hasColumn('purchase_items', 'total_price')) {
                $table->renameColumn('total_price', 'subtotal');
            }
        });
    }
};
