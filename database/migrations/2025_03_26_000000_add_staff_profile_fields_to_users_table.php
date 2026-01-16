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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'employment_type')) {
                $table->string('employment_type', 32)->nullable();
            }
            if (!Schema::hasColumn('users', 'expected_hours_per_week')) {
                $table->decimal('expected_hours_per_week', 6, 2)->nullable();
            }
            if (!Schema::hasColumn('users', 'tin_number')) {
                $table->string('tin_number', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'sss_number')) {
                $table->string('sss_number', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'philhealth_number')) {
                $table->string('philhealth_number', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'pagibig_number')) {
                $table->string('pagibig_number', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'allow_system_login')) {
                $table->boolean('allow_system_login')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'allow_system_login')) {
                $table->dropColumn('allow_system_login');
            }
            if (Schema::hasColumn('users', 'pagibig_number')) {
                $table->dropColumn('pagibig_number');
            }
            if (Schema::hasColumn('users', 'philhealth_number')) {
                $table->dropColumn('philhealth_number');
            }
            if (Schema::hasColumn('users', 'sss_number')) {
                $table->dropColumn('sss_number');
            }
            if (Schema::hasColumn('users', 'tin_number')) {
                $table->dropColumn('tin_number');
            }
            if (Schema::hasColumn('users', 'expected_hours_per_week')) {
                $table->dropColumn('expected_hours_per_week');
            }
            if (Schema::hasColumn('users', 'employment_type')) {
                $table->dropColumn('employment_type');
            }
        });
    }
};
