<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'sss_rate',
        'philhealth_rate',
        'pagibig_rate',
        'pagibig_cap',
        'app_cut_rate',
        'processing_days',
        'processing_day_ranges',
    ];

    protected $casts = [
        'processing_days' => 'array',
        'processing_day_ranges' => 'array',
    ];
}
