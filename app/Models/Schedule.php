<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    
    protected $casts = [
        'recurring_days' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function user_schedules()
    {
        return $this->hasMany(UserSchedule::class, 'schedule_id');
    }

    public function rescheduleRequests()
    {
        return $this->hasMany(ScheduleRescheduleRequest::class, 'schedule_id');
    }
}
