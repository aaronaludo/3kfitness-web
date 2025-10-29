<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    
    public function user()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function user_schedules()
    {
        return $this->hasMany(UserSchedule::class, 'schedule_id');
    }
}
