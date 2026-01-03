<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'processed_membership_payments_approved')) {
                $table->json('processed_membership_payments_approved')->nullable()->after('processed_session_series');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_runs', 'processed_membership_payments_approved')) {
                $table->dropColumn('processed_membership_payments_approved');
            }
        });
    }
};
