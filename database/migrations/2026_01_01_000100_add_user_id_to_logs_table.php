<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add user tracking to logs so entries can be tied back to a specific account.
     */
    public function up(): void
    {
        if (Schema::hasColumn('logs', 'user_id')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('role_name')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Remove the user reference from logs.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('logs', 'user_id')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
