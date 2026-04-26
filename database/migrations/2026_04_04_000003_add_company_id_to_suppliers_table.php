<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            if (!Schema::hasColumn('suppliers', 'company_id')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete()->after('id');
                });
            }
        } catch (\Exception $e) {
            // Ignorer si la colonne existe déjà (erreur typique SQLite en tests)
        }
        
        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        } catch (\Exception $e) {
            // Ignorer
        }
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
