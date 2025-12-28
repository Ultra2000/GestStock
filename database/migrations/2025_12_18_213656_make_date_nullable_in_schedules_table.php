<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Rendre date nullable pour supporter les horaires récurrents par day_of_week
            $table->date('date')->nullable()->change();
            
            // Ajouter index pour les requêtes récurrentes
            $table->index('day_of_week', 'schedules_day_of_week_index');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->dropIndex('schedules_day_of_week_index');
        });
    }
};
