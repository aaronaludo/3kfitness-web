<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'processed_attendance_ids')) {
                $table->json('processed_attendance_ids')
                    ->nullable()
                    ->after('processed_membership_payments_approved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_runs', 'processed_attendance_ids')) {
                $table->dropColumn('processed_attendance_ids');
            }
        });
    }
};
