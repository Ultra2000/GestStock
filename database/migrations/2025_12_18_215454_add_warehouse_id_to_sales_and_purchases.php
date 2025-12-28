<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter warehouse_id aux ventes
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained('warehouses')->nullOnDelete();
            }
        });

        // Ajouter warehouse_id aux achats
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('supplier_id')->constrained('warehouses')->nullOnDelete();
            }
        });

        // Corriger la contrainte unique du code entrepôt (unique par company, pas global)
        Schema::table('warehouses', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte unique globale si elle existe
            try {
                $table->dropUnique(['code']);
            } catch (\Exception $e) {
                // La contrainte n'existe peut-être pas
            }
        });
        
        Schema::table('warehouses', function (Blueprint $table) {
            // Ajouter une contrainte unique par company_id
            $table->unique(['company_id', 'code'], 'warehouses_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_company_code_unique');
            $table->unique('code');
        });
    }
};
