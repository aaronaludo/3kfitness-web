<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'description',
        'price',
        'year',
        'month',
        'week',
        'class_limit_per_month',
    ];
    
    public function membershipPayments()
    {
        return $this->hasMany(MembershipPayment::class);
    }
}
