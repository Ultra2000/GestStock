<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_orders', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('name');
            $table->boolean('auto_generate')->default(true)->after('status');
            $table->boolean('auto_send_invoice')->default(false)->after('auto_generate');
            $table->timestamp('next_execution')->nullable()->after('auto_send_invoice');
            $table->integer('max_executions')->nullable()->after('next_execution');
            $table->integer('executions_count')->default(0)->after('max_executions');
            $table->decimal('total_amount', 12, 2)->default(0)->after('executions_count');
        });

        // Initialiser next_execution depuis next_order_date pour les lignes existantes
        \DB::statement('UPDATE recurring_orders SET next_execution = next_order_date WHERE next_execution IS NULL');
        \DB::statement('UPDATE recurring_orders SET total_amount = `total` WHERE total_amount = 0');
    }

    public function down(): void
    {
        Schema::table('recurring_orders', function (Blueprint $table) {
            $table->dropColumn(['reference', 'auto_generate', 'auto_send_invoice', 'next_execution', 'max_executions', 'executions_count', 'total_amount']);
        });
    }
};
