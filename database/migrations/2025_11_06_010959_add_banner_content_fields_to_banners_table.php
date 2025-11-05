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
        Schema::table('banners', function (Blueprint $table) {
            $table->string('tag_icon', 50)->nullable()->after('pricing_text');
            $table->string('tag_text')->nullable()->after('tag_icon');
            $table->string('schedule_button_icon', 50)->nullable()->after('tag_text');
            $table->string('schedule_button_text')->nullable()->after('schedule_button_icon');
            $table->string('footnote_prefix')->nullable()->after('schedule_button_text');
            $table->string('footnote_price')->nullable()->after('footnote_prefix');
            $table->string('footnote_suffix')->nullable()->after('footnote_price');
            $table->string('stat_one_icon', 50)->nullable()->after('footnote_suffix');
            $table->string('stat_one_value')->nullable()->after('stat_one_icon');
            $table->string('stat_one_label')->nullable()->after('stat_one_value');
            $table->string('stat_two_icon', 50)->nullable()->after('stat_one_label');
            $table->string('stat_two_value')->nullable()->after('stat_two_icon');
            $table->string('stat_two_label')->nullable()->after('stat_two_value');
            $table->string('stat_three_icon', 50)->nullable()->after('stat_two_label');
            $table->string('stat_three_value')->nullable()->after('stat_three_icon');
            $table->string('stat_three_label')->nullable()->after('stat_three_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'tag_icon',
                'tag_text',
                'schedule_button_icon',
                'schedule_button_text',
                'footnote_prefix',
                'footnote_price',
                'footnote_suffix',
                'stat_one_icon',
                'stat_one_value',
                'stat_one_label',
                'stat_two_icon',
                'stat_two_value',
                'stat_two_label',
                'stat_three_icon',
                'stat_three_value',
                'stat_three_label',
            ]);
        });
    }
};
