<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'isadminread',
    ];

    protected $casts = [
        'isadminread' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
