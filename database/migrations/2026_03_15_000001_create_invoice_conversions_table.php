<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_conversions', function (Blueprint $table) {
            $table->id();

            // Utilisateur (nullable pour conversions publiques)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Fichier source
            $table->string('original_filename');
            $table->string('original_mime_type', 100);
            $table->unsignedInteger('original_size')->default(0); // en octets

            // Données extraites par l'IA
            $table->json('extracted_data')->nullable();

            // Fournisseur IA utilisé
            $table->string('ai_provider', 50)->default('gemini'); // gemini, claude
            $table->string('tier', 20)->default('free'); // free, pro

            // Statut du traitement
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'downloaded'])
                  ->default('pending');
            $table->text('error_message')->nullable();

            // Fichier de sortie Factur-X
            $table->string('output_pdf_path')->nullable();
            $table->string('output_xml_path')->nullable();

            // Tracking (pour rate-limiting des utilisateurs anonymes)
            $table->string('ip_address', 45)->nullable();
            $table->string('session_id')->nullable();

            // Statistiques
            $table->unsignedSmallInteger('processing_time_ms')->nullable();

            $table->timestamps();

            // Index pour rate-limiting et statistiques
            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_conversions');
    }
};
