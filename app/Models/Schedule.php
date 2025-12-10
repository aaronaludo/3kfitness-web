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

    public function activeUserSchedules()
    {
        return $this->hasMany(UserSchedule::class, 'schedule_id')
            ->whereHas('user', function ($query) {
                $query->where('is_archive', 0);
            });
    }

    public function scopeWithActiveEnrollmentCount($query, string $alias = 'user_schedules_count')
    {
        $alias = $alias ?: 'user_schedules_count';

        return $query->withCount([
            'activeUserSchedules as ' . $alias,
        ]);
    }

    public function rescheduleRequests()
    {
        return $this->hasMany(ScheduleRescheduleRequest::class, 'schedule_id');
    }
}
