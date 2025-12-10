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
        Schema::table('schedule_reschedule_requests', function (Blueprint $table) {
            $table->json('target_session_dates')->nullable()->after('proposed_series_end_date');
            $table->json('proposed_session_dates')->nullable()->after('target_session_dates');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->json('session_overrides')->nullable()->after('recurring_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_reschedule_requests', function (Blueprint $table) {
            $table->dropColumn(['target_session_dates', 'proposed_session_dates']);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('session_overrides');
        });
    }
};
