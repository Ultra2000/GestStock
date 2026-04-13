<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Période
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_label')->nullable(); // "Janvier 2026", "T1 2026"

            // TVA collectée par taux (cases CA3)
            $table->decimal('base_20', 15, 2)->default(0);   // Base HT taux 20%
            $table->decimal('vat_20', 15, 2)->default(0);    // TVA 20%
            $table->decimal('base_10', 15, 2)->default(0);   // Base HT taux 10%
            $table->decimal('vat_10', 15, 2)->default(0);    // TVA 10%
            $table->decimal('base_55', 15, 2)->default(0);   // Base HT taux 5.5%
            $table->decimal('vat_55', 15, 2)->default(0);    // TVA 5.5%
            $table->decimal('base_21', 15, 2)->default(0);   // Base HT taux 2.1%
            $table->decimal('vat_21', 15, 2)->default(0);    // TVA 2.1%
            $table->decimal('base_other', 15, 2)->default(0); // Base autres taux
            $table->decimal('vat_other', 15, 2)->default(0);  // TVA autres taux

            // Totaux TVA collectée
            $table->decimal('total_vat_collected', 15, 2)->default(0); // Ligne 11

            // TVA déductible
            $table->decimal('vat_deductible_goods', 15, 2)->default(0);  // Ligne 20 (achats)
            $table->decimal('vat_deductible_assets', 15, 2)->default(0); // Ligne 19 (immobilisations)
            $table->decimal('total_vat_deductible', 15, 2)->default(0);  // Ligne 21

            // Résultat
            $table->decimal('vat_due', 15, 2)->default(0);    // Ligne 55 (à payer)
            $table->decimal('vat_credit', 15, 2)->default(0); // Ligne 56 (crédit)

            // Métadonnées
            $table->enum('status', ['draft', 'validated'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_declarations');
    }
};
