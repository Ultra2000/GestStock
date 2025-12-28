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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('currency')->default('XOF')->after('settings')->comment('ISO 4217 currency code (e.g., USD, EUR, XOF)');
            $table->string('country_code')->nullable()->after('currency')->comment('ISO country code for IP geolocation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['currency', 'country_code']);
        });
    }
};
