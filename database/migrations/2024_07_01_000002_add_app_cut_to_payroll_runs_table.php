<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'deduction_app_cut')) {
                $table->decimal('deduction_app_cut', 12, 2)->default(0)->after('deduction_pagibig');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_runs', 'deduction_app_cut')) {
                $table->dropColumn('deduction_app_cut');
            }
        });
    }
};
