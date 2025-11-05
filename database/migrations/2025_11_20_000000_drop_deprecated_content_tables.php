<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('abouts');
        Schema::dropIfExists('motivational_videos');
        Schema::dropIfExists('workout_categories');
        Schema::dropIfExists('diet_categories');
        Schema::dropIfExists('helps');
    }

    public function down(): void
    {
        if (!Schema::hasTable('abouts')) {
            Schema::create('abouts', function (Blueprint $table) {
                $table->id();
                $table->longText('terms_and_conditions')->nullable();
                $table->longText('data_policy')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('motivational_videos')) {
            Schema::create('motivational_videos', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('video')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workout_categories')) {
            Schema::create('workout_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedBigInteger('trainer_id')->nullable();
                $table->string('trainer')->nullable();
                $table->string('calories')->nullable();
                $table->string('equipment')->nullable();
                $table->text('benefits')->nullable();
                $table->longText('session_details')->nullable();
                $table->string('video_url')->nullable();
                $table->string('image_url')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_role')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('diet_categories')) {
            Schema::create('diet_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('protein')->nullable();
                $table->string('fat')->nullable();
                $table->string('calories')->nullable();
                $table->text('ingredients')->nullable();
                $table->text('recipe_description')->nullable();
                $table->string('video_url')->nullable();
                $table->string('image_url')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_role')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('helps')) {
            Schema::create('helps', function (Blueprint $table) {
                $table->id();
                $table->text('content')->nullable();
                $table->timestamps();
            });
        }
    }
};
