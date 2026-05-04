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
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_reference_unique');
            $table->unique(['reference', 'company_id'], 'inventories_reference_company_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_reference_company_unique');
            $table->unique('reference', 'inventories_reference_unique');
        });
    }
};
