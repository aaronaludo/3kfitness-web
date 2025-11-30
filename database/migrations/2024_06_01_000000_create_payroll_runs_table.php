<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('period_month', 7)->index(); // YYYY-MM
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->decimal('deduction_sss', 12, 2)->default(0);
            $table->decimal('deduction_philhealth', 12, 2)->default(0);
            $table->decimal('deduction_pagibig', 12, 2)->default(0);
            $table->unsignedBigInteger('processed_by')->nullable()->index();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
