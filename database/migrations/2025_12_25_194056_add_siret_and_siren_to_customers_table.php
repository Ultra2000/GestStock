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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('registration_number')->nullable()->after('name'); // SIREN
            $table->string('siret')->nullable()->after('registration_number'); // SIRET
            $table->string('tax_number')->nullable()->after('siret'); // TVA Intra
            $table->string('zip_code')->nullable()->after('address'); // Code Postal (manquant aussi)
            $table->string('country_code')->default('FR')->after('country'); // Code Pays ISO
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['registration_number', 'siret', 'tax_number', 'zip_code', 'country_code']);
        });
    }
};
