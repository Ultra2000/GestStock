<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pour les templates de planning
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('schedule_data'); // Contient les horaires pour chaque jour de la semaine
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Table pour les exceptions de planning récurrent
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('exception_date'); // Date spécifique de l'exception
            $table->time('start_time')->nullable(); // Null = jour off
            $table->time('end_time')->nullable();
            $table->time('break_duration')->nullable();
            $table->string('shift_type')->nullable();
            $table->text('reason')->nullable(); // Raison de l'exception
            $table->enum('type', ['modified', 'cancelled'])->default('modified');
            $table->timestamps();

            $table->unique(['schedule_id', 'exception_date']);
        });

        // Table pour les notifications de planning
        Schema::create('schedule_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'published', 'modified', 'cancelled'
            $table->date('week_start')->nullable();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Ajouter un champ pour lier un schedule à un template
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('company_id')->constrained('schedule_templates')->nullOnDelete();
            $table->foreignId('parent_schedule_id')->nullable()->after('template_id'); // Pour les exceptions
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'parent_schedule_id']);
        });

        Schema::dropIfExists('schedule_notifications');
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('schedule_templates');
    }
};
