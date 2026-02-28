<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('siret', 14)->nullable()->after('name');
            $table->string('siren', 9)->nullable()->after('siret');
            $table->string('tax_number', 20)->nullable()->after('siren'); // N° TVA intra
            $table->string('zip_code', 10)->nullable()->after('address');
            $table->string('country_code', 2)->default('FR')->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['siret', 'siren', 'tax_number', 'zip_code', 'country_code']);
        });
    }
};
