<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'issued_at',
        'is_active',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
