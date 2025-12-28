<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'day_of_week')) {
                $table->tinyInteger('day_of_week')->nullable()->after('date');
            }
            if (!Schema::hasColumn('schedules', 'position')) {
                $table->string('position', 100)->nullable()->after('location');
            }
            if (!Schema::hasColumn('schedules', 'color')) {
                $table->string('color', 20)->nullable()->after('position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['day_of_week', 'position', 'color']);
        });
    }
};
