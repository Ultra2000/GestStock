<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tutorial_videos', 'views_count')) {
            Schema::table('tutorial_videos', function (Blueprint $table) {
                $table->unsignedBigInteger('views_count')->default(0)->after('sort_order');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tutorial_videos', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
