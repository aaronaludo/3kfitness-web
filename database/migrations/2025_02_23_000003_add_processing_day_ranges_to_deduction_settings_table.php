<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deduction_settings', function (Blueprint $table) {
            $table->json('processing_day_ranges')->nullable()->after('processing_days');
        });
    }

    public function down(): void
    {
        Schema::table('deduction_settings', function (Blueprint $table) {
            $table->dropColumn('processing_day_ranges');
        });
    }
};
