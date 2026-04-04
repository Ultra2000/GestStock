<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'company_id')) {
                // nullable() pour ne pas bloquer les lignes existantes sans company_id
                $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
            }
            // Rendre email nullable (non obligatoire en B2B)
            if (Schema::hasColumn('suppliers', 'email')) {
                $table->string('email')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};
