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
        Schema::create('trainer_banners', function (Blueprint $table) {
            $table->id();
            $table->string('background_image')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('button_text')->nullable();
            $table->string('pricing_text')->nullable();
            $table->string('tag_icon', 50)->nullable();
            $table->string('tag_text')->nullable();
            $table->string('schedule_button_icon', 50)->nullable();
            $table->string('schedule_button_text')->nullable();
            $table->string('footnote_prefix')->nullable();
            $table->string('footnote_price')->nullable();
            $table->string('footnote_suffix')->nullable();
            $table->string('stat_one_icon', 50)->nullable();
            $table->string('stat_one_value')->nullable();
            $table->string('stat_one_label')->nullable();
            $table->string('stat_two_icon', 50)->nullable();
            $table->string('stat_two_value')->nullable();
            $table->string('stat_two_label')->nullable();
            $table->string('stat_three_icon', 50)->nullable();
            $table->string('stat_three_value')->nullable();
            $table->string('stat_three_label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainer_banners');
    }
};
