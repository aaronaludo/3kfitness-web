<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_month',
        'total_hours',
        'gross_pay',
        'net_pay',
        'deduction_sss',
        'deduction_philhealth',
        'deduction_pagibig',
        'deduction_app_cut',
        'processed_by',
        'processed_at',
        'released_by',
        'released_at',
        'processed_session_series',
        'processed_membership_payments_approved',
        'processed_attendance_ids',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'released_at' => 'datetime',
        'processed_session_series' => 'array',
        'processed_membership_payments_approved' => 'array',
        'processed_attendance_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function releasedByUser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
