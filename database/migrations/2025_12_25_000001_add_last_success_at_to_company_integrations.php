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
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->timestamp('last_success_at')->nullable()->after('last_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->dropColumn('last_success_at');
        });
    }
};
