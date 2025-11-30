<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use Illuminate\Http\Request;

class TrainerPayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || (int) $user->role_id !== 5) {
            return response()->json(['message' => 'Trainer access only'], 403);
        }

        $runs = PayrollRun::with('user')
            ->where('user_id', $user->id)
            ->orderByDesc('processed_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($run) {
                return [
                    'id' => $run->id,
                    'period_month' => $run->period_month,
                    'total_hours' => $run->total_hours,
                    'gross_pay' => $run->gross_pay,
                    'net_pay' => $run->net_pay,
                    'deduction_sss' => $run->deduction_sss,
                    'deduction_philhealth' => $run->deduction_philhealth,
                    'deduction_pagibig' => $run->deduction_pagibig,
                    'processed_at' => optional($run->processed_at)->toIso8601String(),
                    'created_at' => optional($run->created_at)->toIso8601String(),
                    'updated_at' => optional($run->updated_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $runs,
        ]);
    }
}
