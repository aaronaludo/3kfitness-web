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
        if (!Schema::hasColumn('feedback', 'stars')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->unsignedTinyInteger('stars')->default(5)->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('feedback', 'stars')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->dropColumn('stars');
            });
        }
    }
};
