<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute le champ "Franchise en base de TVA" pour les auto-entrepreneurs
     * et micro-entreprises (Art. 293 B du CGI)
     */
    public function up(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->boolean('is_vat_franchise')->default(false)
                ->after('accounting_software_version')
                ->comment('Franchise en base de TVA - Art. 293 B du CGI');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn('is_vat_franchise');
        });
    }
};
