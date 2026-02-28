<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout des mentions obligatoires 2026 sur les factures :
 * - nature_operation : Vente de biens / Prestation de services / Mixte
 * - delivery_address : Adresse de livraison (si différente du siège)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('nature_operation', 20)->nullable()->after('notes')
                ->comment('goods=Vente de biens, services=Prestation de services, mixed=Mixte');
            $table->text('delivery_address')->nullable()->after('nature_operation')
                ->comment('Adresse de livraison si différente du siège');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['nature_operation', 'delivery_address']);
        });
    }
};
