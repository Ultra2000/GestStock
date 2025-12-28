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
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_amount', 15, 2)->default(0); // Fond de caisse
            $table->decimal('closing_amount', 15, 2)->nullable(); // Montant en fermeture
            $table->decimal('expected_amount', 15, 2)->nullable(); // Montant attendu (calculé)
            $table->decimal('difference', 15, 2)->nullable(); // Écart de caisse
            $table->integer('sales_count')->default(0); // Nombre de ventes
            $table->decimal('total_sales', 15, 2)->default(0); // Total des ventes
            $table->decimal('total_cash', 15, 2)->default(0); // Total espèces
            $table->decimal('total_card', 15, 2)->default(0); // Total carte
            $table->decimal('total_mobile', 15, 2)->default(0); // Total mobile money
            $table->decimal('total_other', 15, 2)->default(0); // Autres modes
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        // Ajouter colonne cash_session_id aux ventes
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_session_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('payment_method')->default('cash')->after('status'); // cash, card, mobile, mixed
            $table->json('payment_details')->nullable()->after('payment_method'); // Détails paiement mixte
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_session_id']);
            $table->dropColumn(['cash_session_id', 'payment_method', 'payment_details']);
        });
        Schema::dropIfExists('cash_sessions');
    }
};
