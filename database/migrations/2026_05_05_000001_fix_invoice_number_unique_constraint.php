<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // --- PURCHASES ---
        Schema::table('purchases', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte unique globale si elle existe
            try {
                $table->dropUnique('purchases_invoice_number_unique');
            } catch (\Throwable $e) {
                // Ignoré si la contrainte n'existe pas
            }
            // Ajouter la contrainte unique par entreprise
            $table->unique(['invoice_number', 'company_id'], 'purchases_invoice_number_company_unique');
        });

        // --- SALES ---
        Schema::table('sales', function (Blueprint $table) {
            try {
                $table->dropUnique('sales_invoice_number_unique');
            } catch (\Throwable $e) {
                // Ignoré si la contrainte n'existe pas
            }
            $table->unique(['invoice_number', 'company_id'], 'sales_invoice_number_company_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            try { $table->dropUnique('purchases_invoice_number_company_unique'); } catch (\Throwable $e) {}
            $table->unique('invoice_number', 'purchases_invoice_number_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            try { $table->dropUnique('sales_invoice_number_company_unique'); } catch (\Throwable $e) {}
            $table->unique('invoice_number', 'sales_invoice_number_unique');
        });
    }
};
