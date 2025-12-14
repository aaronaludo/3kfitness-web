<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'processed_session_series')) {
                $table->json('processed_session_series')->nullable()->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_runs', 'processed_session_series')) {
                $table->dropColumn('processed_session_series');
            }
        });
    }
};
