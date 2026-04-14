<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // Valeurs par défaut
        DB::table('app_settings')->insert([
            ['key' => 'trial_days', 'value' => '180', 'type' => 'integer', 'label' => 'Durée de la période d\'essai (jours)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
