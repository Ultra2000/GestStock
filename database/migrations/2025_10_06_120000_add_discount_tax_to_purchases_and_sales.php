<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('total');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('discount_percent');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('total');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
    }
};