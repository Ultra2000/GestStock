<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amélioration du système comptable :
     * 1. Régime TVA (débits ou encaissements)
     * 2. Numérotation FEC globale
     * 3. Suivi des paiements pour écritures de règlement
     */
    public function up(): void
    {
        // 1. Ajouter le régime TVA aux paramètres comptables
        if (!Schema::hasColumn('accounting_settings', 'vat_regime')) {
            Schema::table('accounting_settings', function (Blueprint $table) {
                $table->string('vat_regime', 20)->default('debits')->after('is_vat_franchise');
            });
        }

        // 2. Ajouter le numéro d'écriture global FEC
        if (!Schema::hasColumn('accounting_entries', 'fec_sequence')) {
            Schema::table('accounting_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('fec_sequence')->nullable()->after('id');
                $table->index(['company_id', 'fec_sequence']);
            });
        }
        
        if (!Schema::hasColumn('accounting_entries', 'entry_type')) {
            Schema::table('accounting_entries', function (Blueprint $table) {
                $table->string('entry_type', 20)->default('document')->after('creation_source');
            });
        }
        
        if (!Schema::hasColumn('accounting_entries', 'payment_for_id')) {
            Schema::table('accounting_entries', function (Blueprint $table) {
                $table->foreignId('payment_for_id')->nullable()->after('reversal_of_id')
                    ->constrained('accounting_entries')->nullOnDelete();
            });
        }

        // 3. Ajouter les champs de paiement sur les ventes
        if (!Schema::hasColumn('sales', 'payment_status')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('pending')->after('status');
                $table->decimal('amount_paid', 15, 2)->default(0)->after('total_vat');
                $table->timestamp('paid_at')->nullable()->after('amount_paid');
            });
        }

        // 4. Créer une table de paiements pour tracer les règlements
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                
                $table->string('payable_type');
                $table->unsignedBigInteger('payable_id');
                
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 30);
                $table->date('payment_date');
                $table->string('reference', 100)->nullable();
                $table->string('account_number', 15);
                $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['company_id', 'payment_date']);
                $table->index(['payable_type', 'payable_id'], 'payments_payable_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        
        if (Schema::hasColumn('sales', 'payment_status')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn(['payment_status', 'amount_paid', 'paid_at']);
            });
        }

        if (Schema::hasColumn('accounting_entries', 'fec_sequence')) {
            Schema::table('accounting_entries', function (Blueprint $table) {
                if (Schema::hasColumn('accounting_entries', 'payment_for_id')) {
                    $table->dropForeign(['payment_for_id']);
                    $table->dropColumn('payment_for_id');
                }
                $table->dropColumn(['fec_sequence', 'entry_type']);
            });
        }

        if (Schema::hasColumn('accounting_settings', 'vat_regime')) {
            Schema::table('accounting_settings', function (Blueprint $table) {
                $table->dropColumn('vat_regime');
            });
        }
    }
};
