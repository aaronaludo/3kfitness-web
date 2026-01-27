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
        if (Schema::hasColumn('feedback', 'isadminread')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->renameColumn('isadminread', 'admin_confirmation_status');
            });
            return;
        }

        if (!Schema::hasColumn('feedback', 'admin_confirmation_status')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->boolean('admin_confirmation_status')->default(0)->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('feedback', 'admin_confirmation_status')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->renameColumn('admin_confirmation_status', 'isadminread');
            });
        }
    }
};
