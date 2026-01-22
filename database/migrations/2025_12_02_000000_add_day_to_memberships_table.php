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
        if (!Schema::hasColumn('memberships', 'day')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->integer('day')->nullable()->default(0)->after('week');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('memberships', 'day')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropColumn('day');
            });
        }
    }
};
