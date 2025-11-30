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
        Schema::create('schedule_reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('recurring_days');
            $table->time('proposed_start_time');
            $table->time('proposed_end_time');
            $table->date('proposed_series_start_date')->nullable();
            $table->date('proposed_series_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_comment')->nullable();
            $table->tinyInteger('status')->default(0); // 0 = pending, 1 = approved, 2 = rejected
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_reschedule_requests');
    }
};
