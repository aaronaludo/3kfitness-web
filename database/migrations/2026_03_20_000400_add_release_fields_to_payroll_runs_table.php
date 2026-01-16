<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_runs', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('payroll_runs', 'released_by')) {
                $table->unsignedBigInteger('released_by')->nullable()->index()->after('released_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_runs', 'released_by')) {
                $table->dropColumn('released_by');
            }
            if (Schema::hasColumn('payroll_runs', 'released_at')) {
                $table->dropColumn('released_at');
            }
        });
    }
};
