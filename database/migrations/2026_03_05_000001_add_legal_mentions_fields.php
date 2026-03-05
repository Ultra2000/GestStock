<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === COMPANY : mentions légales obligatoires ===
        Schema::table('companies', function (Blueprint $table) {
            $table->string('forme_juridique', 50)->nullable()->after('name');        // SAS, SARL, EURL, SA, EI, etc.
            $table->string('capital_social', 50)->nullable()->after('forme_juridique'); // "10 000 €"
            $table->string('code_naf', 10)->nullable()->after('registration_number'); // Code APE/NAF ex: 6201Z
            $table->string('rcs_number', 100)->nullable()->after('siret');            // "RCS Paris B 123 456 789"
            $table->string('rm_number', 100)->nullable()->after('rcs_number');        // Répertoire des Métiers
        });

        // === ACCOUNTING_SETTINGS : pénalités de retard ===
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->decimal('penalty_rate', 5, 2)->nullable()->after('vat_regime');   // Taux pénalités de retard (ex: 10.00 %)
            $table->decimal('recovery_fee', 8, 2)->default(40.00)->after('penalty_rate'); // Indemnité forfaitaire recouvrement
            $table->text('payment_terms')->nullable()->after('recovery_fee');         // Conditions de paiement libres
        });

        // === SALE : date de livraison / prestation ===
        Schema::table('sales', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('delivery_address');     // Date de livraison/prestation
            $table->date('due_date')->nullable()->after('delivery_date');             // Date d'échéance de paiement
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['forme_juridique', 'capital_social', 'code_naf', 'rcs_number', 'rm_number']);
        });

        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn(['penalty_rate', 'recovery_fee', 'payment_terms']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['delivery_date', 'due_date']);
        });
    }
};
