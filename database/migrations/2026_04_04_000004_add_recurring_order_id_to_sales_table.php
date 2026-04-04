<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'recurring_order_id')) {
                $table->foreignId('recurring_order_id')
                    ->nullable()
                    ->constrained('recurring_orders')
                    ->nullOnDelete()
                    ->after('company_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'recurring_order_id')) {
                $table->dropForeign(['recurring_order_id']);
                $table->dropColumn('recurring_order_id');
            }
        });
    }
};
