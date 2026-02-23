<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorial_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('section'); // matches UserGuide section keys
            $table->string('video_url'); // YouTube, Vimeo, or direct URL
            $table->string('video_type')->default('youtube'); // youtube, vimeo, direct
            $table->string('thumbnail_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('section');
            $table->index(['section', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorial_videos');
    }
};
