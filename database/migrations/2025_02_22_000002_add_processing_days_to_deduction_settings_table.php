<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deduction_settings', function (Blueprint $table) {
            $table->json('processing_days')->nullable()->after('app_cut_rate');
        });
    }

    public function down(): void
    {
        Schema::table('deduction_settings', function (Blueprint $table) {
            $table->dropColumn('processing_days');
        });
    }
};
