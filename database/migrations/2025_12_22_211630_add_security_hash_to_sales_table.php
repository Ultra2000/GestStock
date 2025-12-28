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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('security_hash', 64)->nullable()->after('notes')->comment('SHA-256 hash for NF525 compliance');
            $table->string('previous_hash', 64)->nullable()->after('security_hash')->comment('Hash of the previous invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['security_hash', 'previous_hash']);
        });
    }
};
