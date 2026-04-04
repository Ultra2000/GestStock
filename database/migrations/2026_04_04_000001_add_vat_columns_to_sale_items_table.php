<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->default(20)->after('unit_price');
            }
            if (!Schema::hasColumn('sale_items', 'unit_price_ht')) {
                $table->decimal('unit_price_ht', 10, 2)->default(0)->after('vat_rate');
            }
            if (!Schema::hasColumn('sale_items', 'vat_amount')) {
                $table->decimal('vat_amount', 10, 2)->default(0)->after('unit_price_ht');
            }
            if (!Schema::hasColumn('sale_items', 'total_price_ht')) {
                $table->decimal('total_price_ht', 10, 2)->default(0)->after('vat_amount');
            }
            if (!Schema::hasColumn('sale_items', 'vat_category')) {
                $table->string('vat_category', 5)->default('S')->after('total_price_ht');
            }
        });

        // Changer quantity integer -> decimal si nécessaire
        // (SQLite ne supporte pas modifyColumn sans doctrine/dbal)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('quantity', 10, 2)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['vat_rate', 'unit_price_ht', 'vat_amount', 'total_price_ht', 'vat_category'],
                fn ($col) => Schema::hasColumn('sale_items', $col)
            ));
        });
    }
};
