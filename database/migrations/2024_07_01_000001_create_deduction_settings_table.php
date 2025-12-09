<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deduction_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('sss_rate', 6, 3)->default(4.5); // percent
            $table->decimal('philhealth_rate', 6, 3)->default(2.5); // percent
            $table->decimal('pagibig_rate', 6, 3)->default(2.0); // percent
            $table->decimal('pagibig_cap', 12, 2)->default(5000);
            $table->decimal('app_cut_rate', 6, 3)->default(0); // percent
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_settings');
    }
};
