<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grand Livre Comptable - Table immuable pour FEC
     * 
     * Cette table stocke les écritures comptables FIGÉES au moment de la validation.
     * Une fois créée, une ligne ne doit JAMAIS être modifiée (principe d'immutabilité comptable).
     * Pour corriger : on passe une écriture de contre-passation (avoir).
     */
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Référence au document source (polymorphique)
            $table->string('source_type')->nullable(); // App\Models\Sale, App\Models\Purchase
            $table->unsignedBigInteger('source_id')->nullable();
            $table->index(['source_type', 'source_id']);
            
            // Date de l'écriture (date de la facture, pas created_at)
            $table->date('entry_date');
            
            // Numéro de pièce FIGÉ (copie du invoice_number au moment de la validation)
            $table->string('piece_number', 50);
            
            // Journal (VTE, ACH, BQ, CAI, OD) - FIGÉ
            $table->string('journal_code', 10);
            
            // Compte général PCG (ex: 707000, 411000) - FIGÉ
            $table->string('account_number', 15);
            
            // Compte auxiliaire pour lettrage (ex: CLI-00045, FRN-00012)
            $table->string('account_auxiliary', 50)->nullable();
            
            // Libellé de l'écriture
            $table->string('label', 255);
            
            // Montants (un seul rempli par ligne, l'autre à 0)
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            
            // Informations TVA figées
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('vat_base', 15, 2)->nullable(); // Base HT pour calcul TVA
            
            // Devise (pour multi-devises futur)
            $table->string('currency', 3)->default('EUR');
            
            // Lettrage comptable (pour rapprochement)
            $table->string('lettering', 10)->nullable();
            $table->date('lettering_date')->nullable();
            
            // Verrouillage - CRITIQUE : une fois true, lecture seule
            $table->boolean('is_locked')->default(true);
            
            // Référence à une écriture de contre-passation
            $table->foreignId('reversal_of_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            
            // Métadonnées pour traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('creation_source', 50)->default('auto'); // auto, manual, import
            
            $table->timestamps();
            
            // Index pour performance FEC
            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'journal_code']);
            $table->index(['company_id', 'account_number']);
            $table->index(['company_id', 'piece_number']);
            $table->index(['company_id', 'account_auxiliary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
