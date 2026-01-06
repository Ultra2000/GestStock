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
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Comptes de base (Plan Comptable Général Français)
            $table->string('account_customers')->default('411000'); // Clients
            $table->string('account_suppliers')->default('401000'); // Fournisseurs
            $table->string('account_sales')->default('707000'); // Ventes de marchandises
            $table->string('account_purchases')->default('607000'); // Achats de marchandises
            $table->string('account_vat_collected')->default('445710'); // TVA collectée
            $table->string('account_vat_deductible')->default('445660'); // TVA déductible
            $table->string('account_bank')->default('512000'); // Banque
            $table->string('account_cash')->default('530000'); // Caisse
            $table->string('account_discounts_granted')->default('709000'); // Rabais accordés
            $table->string('account_discounts_received')->default('609000'); // Rabais obtenus
            
            // Journaux comptables
            $table->string('journal_sales')->default('VTE'); // Journal des ventes
            $table->string('journal_purchases')->default('ACH'); // Journal des achats
            $table->string('journal_bank')->default('BQ'); // Journal de banque
            $table->string('journal_cash')->default('CAI'); // Journal de caisse
            $table->string('journal_misc')->default('OD'); // Opérations diverses
            
            // Paramètres FEC
            $table->string('fec_siren')->nullable(); // SIREN de l'entreprise
            $table->string('fec_company_name')->nullable(); // Raison sociale
            $table->string('accounting_software')->default('GestStock'); // Logiciel comptable
            $table->string('accounting_software_version')->default('1.0');
            
            $table->timestamps();
            
            // Un seul paramétrage par entreprise
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
