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
        // 1. Ajouter les colonnes GPS aux warehouses (sites de travail)
        Schema::table('warehouses', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->integer('gps_radius')->default(100)->after('longitude'); // Rayon en mètres
            $table->boolean('requires_gps_check')->default(false)->after('gps_radius');
            $table->boolean('requires_qr_check')->default(false)->after('requires_gps_check');
        });

        // 2. Table des tokens QR dynamiques
        Schema::create('attendance_qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->foreignId('used_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'token', 'expires_at']);
        });

        // 3. Améliorer la table attendances
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('employee_id')->constrained()->nullOnDelete();
            
            // Données GPS clock_in
            $table->decimal('clock_in_latitude', 10, 8)->nullable()->after('clock_in_location');
            $table->decimal('clock_in_longitude', 11, 8)->nullable()->after('clock_in_latitude');
            $table->integer('clock_in_accuracy')->nullable()->after('clock_in_longitude'); // Précision en mètres
            $table->string('clock_in_qr_token', 64)->nullable()->after('clock_in_accuracy');
            
            // Données GPS clock_out
            $table->decimal('clock_out_latitude', 10, 8)->nullable()->after('clock_out_location');
            $table->decimal('clock_out_longitude', 11, 8)->nullable()->after('clock_out_latitude');
            $table->integer('clock_out_accuracy')->nullable()->after('clock_out_longitude');
            $table->string('clock_out_qr_token', 64)->nullable()->after('clock_out_accuracy');
            
            // Statuts de validation
            $table->enum('clock_in_validation', ['pending', 'valid', 'invalid', 'manual'])->default('pending')->after('clock_out_qr_token');
            $table->enum('clock_out_validation', ['pending', 'valid', 'invalid', 'manual'])->nullable()->after('clock_in_validation');
            $table->text('validation_notes')->nullable()->after('clock_out_validation');
        });

        // 4. Table des logs de tentatives de pointage (audit)
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('action', ['clock_in', 'clock_out', 'break_start', 'break_end']);
            $table->enum('status', ['success', 'failed']);
            $table->string('failure_reason')->nullable(); // gps_out_of_range, qr_invalid, qr_expired, permission_denied, etc.
            
            // Données GPS
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('gps_accuracy')->nullable();
            $table->decimal('distance_from_site', 8, 2)->nullable(); // Distance en mètres
            
            // Token QR
            $table->string('qr_token', 64)->nullable();
            $table->boolean('qr_valid')->nullable();
            
            // Informations techniques
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn([
                'warehouse_id',
                'clock_in_latitude', 'clock_in_longitude', 'clock_in_accuracy', 'clock_in_qr_token',
                'clock_out_latitude', 'clock_out_longitude', 'clock_out_accuracy', 'clock_out_qr_token',
                'clock_in_validation', 'clock_out_validation', 'validation_notes',
            ]);
        });

        Schema::dropIfExists('attendance_qr_tokens');

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'gps_radius', 'requires_gps_check', 'requires_qr_check']);
        });
    }
};
