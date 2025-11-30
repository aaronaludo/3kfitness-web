<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleRescheduleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'trainer_id',
        'recurring_days',
        'proposed_start_time',
        'proposed_end_time',
        'proposed_series_start_date',
        'proposed_series_end_date',
        'notes',
        'admin_comment',
        'status',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'recurring_days' => 'array',
        'proposed_series_start_date' => 'date',
        'proposed_series_end_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
