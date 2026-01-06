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
        // Table pour stocker la situation URSSAF de l'entreprise
        Schema::create('urssaf_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Identifiants URSSAF
            $table->string('siret');
            $table->string('numero_compte')->nullable(); // Numéro de compte URSSAF
            $table->string('urssaf_region')->nullable(); // Ex: IDF, PACA, etc.
            
            // Situation financière
            $table->decimal('solde_debiteur', 12, 2)->default(0); // Dette courante
            $table->decimal('solde_crediteur', 12, 2)->default(0); // Crédit éventuel
            $table->date('derniere_echeance_payee')->nullable();
            $table->date('prochaine_echeance')->nullable();
            $table->decimal('montant_prochaine_echeance', 12, 2)->nullable();
            
            // Conformité
            $table->enum('statut_conformite', ['conforme', 'dette', 'contentieux', 'inconnu'])->default('inconnu');
            $table->date('attestation_vigilance_validite')->nullable();
            $table->string('attestation_vigilance_url')->nullable();
            
            // Synchronisation
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_data')->nullable(); // Données brutes de l'API
            
            $table->timestamps();
            
            $table->unique(['company_id', 'siret']);
        });

        // Table pour l'historique des cotisations
        Schema::create('urssaf_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Période
            $table->string('periode'); // Format AAAAMM (ex: 202601)
            $table->date('date_exigibilite');
            
            // Montants
            $table->decimal('cotisations_patronales', 12, 2)->default(0);
            $table->decimal('cotisations_salariales', 12, 2)->default(0);
            $table->decimal('total_cotisations', 12, 2)->default(0);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->decimal('reste_du', 12, 2)->default(0);
            
            // Statut
            $table->enum('statut', ['a_payer', 'paye', 'partiel', 'retard', 'contentieux'])->default('a_payer');
            $table->date('date_paiement')->nullable();
            $table->string('reference_paiement')->nullable();
            
            // Détails DSN
            $table->integer('effectif')->nullable();
            $table->decimal('masse_salariale', 12, 2)->nullable();
            
            $table->json('details')->nullable(); // Détail par type de cotisation
            
            $table->timestamps();
            
            $table->unique(['company_id', 'periode']);
        });

        // Table pour l'historique des paiements URSSAF
        Schema::create('urssaf_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('urssaf_contribution_id')->nullable()->constrained()->nullOnDelete();
            
            // Paiement
            $table->date('date_paiement');
            $table->decimal('montant', 12, 2);
            $table->enum('mode_paiement', ['prelevement', 'virement', 'tipi', 'cheque', 'autre'])->default('prelevement');
            $table->string('reference')->nullable();
            
            // Statut
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'rembourse'])->default('en_attente');
            $table->string('motif_rejet')->nullable();
            
            // Traçabilité
            $table->string('origine')->default('api'); // api, manuel, import
            $table->json('metadata')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urssaf_payments');
        Schema::dropIfExists('urssaf_contributions');
        Schema::dropIfExists('urssaf_accounts');
    }
};
