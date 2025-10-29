<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutCategory extends Model
{
    use HasFactory;

    protected $table = 'workout_categories';
    
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
