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
        'admin_confirmation_status',
    ];

    protected $casts = [
        'admin_confirmation_status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
