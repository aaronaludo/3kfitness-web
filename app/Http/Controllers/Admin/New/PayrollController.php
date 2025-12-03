<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\Schedule;
use App\Models\Attendance2;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('member_name');
        $period = $request->input('period_month');

        $runs = PayrollRun::with('user')
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($subQuery) use ($search) {
                    $subQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($period, function ($query, $period) {
                $query->where('period_month', $period);
            })
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.payrolls.index', [
            'runs' => $runs,
        ]);
    }

    public function process(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month', now()->format('Y-m'));

        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $month);
        } catch (\Throwable $th) {
            $targetMonth = now();
            $month = $targetMonth->format('Y-m');
        }

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $staffQuery = User::where('role_id', 2)
            ->where('is_archive', 0);

        if ($search) {
            $staffQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staffMembers = $staffQuery->orderBy('first_name')->get();
        $staffIds = $staffMembers->pluck('id');

        $attendanceByUser = Attendance2::whereIn('user_id', $staffIds)
            ->where('is_archive', 0)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
            ->orderByDesc('clockin_at')
            ->get()
            ->groupBy('user_id');
        $processedRuns = PayrollRun::whereIn('user_id', $staffMembers->pluck('id'))
            ->where('period_month', $month)
            ->get()
            ->keyBy('user_id');

        $summaries = $staffMembers->map(function ($staff) use ($attendanceByUser) {
            $entries = collect($attendanceByUser->get($staff->id) ?? [])->map(function ($attendance) use ($staff) {
                $clockIn = $attendance->clockin_at ? Carbon::parse($attendance->clockin_at) : null;
                $clockOut = $attendance->clockout_at ? Carbon::parse($attendance->clockout_at) : null;

                $hours = null;
                if ($clockIn && $clockOut && $clockOut->greaterThan($clockIn)) {
                    $hours = round($clockOut->diffInMinutes($clockIn) / 60, 2);
                }

                $amount = $hours ? round($hours * (float) ($staff->rate_per_hour ?? 0), 2) : null;

                return [
                    'id' => $attendance->id,
                    'clockin_at' => $clockIn,
                    'clockout_at' => $clockOut,
                    'hours' => $hours,
                    'amount' => $amount,
                    'status' => $clockOut ? 'complete' : 'pending',
                ];
            });

            $totalHours = $entries->sum(fn ($entry) => $entry['hours'] ?? 0);
            $gross = round($totalHours * (float) ($staff->rate_per_hour ?? 0), 2);

            $deductions = [
                'sss' => round($gross * 0.045, 2),
                'philhealth' => round($gross * 0.025, 2),
                'pagibig' => round(min($gross, 5000) * 0.02, 2),
            ];

            $net = max($gross - array_sum($deductions), 0);

            return [
                'staff' => $staff,
                'entries' => $entries,
                'total_hours' => $totalHours,
                'gross_pay' => $gross,
                'net_pay' => $net,
                'deductions' => $deductions,
                'pending_entries' => $entries->where('status', 'pending')->count(),
                'completed_entries' => $entries->where('status', 'complete')->count(),
            ];
        })->filter(fn ($summary) => $summary['entries']->count() > 0)->values()->map(function ($summary) use ($processedRuns, $month) {
            $staff = $summary['staff'];
            $run = $processedRuns->get($staff->id);

            if ($run) {
                // Zero out on-screen values once processed, but keep run info for badges/messages.
                $summary['total_hours'] = 0;
                $summary['gross_pay'] = 0;
                $summary['net_pay'] = 0;
                $summary['deductions'] = [
                    'sss' => 0,
                    'philhealth' => 0,
                    'pagibig' => 0,
                ];
                $summary['entries'] = collect(); // Hide entries after processing
                $summary['pending_entries'] = 0;
                $summary['completed_entries'] = 0;
            }

            $summary['processed_run'] = $run;
            $summary['period_month'] = $month;

            return $summary;
        });

        $stats = [
            'staff_count' => $summaries->count(),
            'pending_entries' => $summaries->sum(fn ($summary) => $summary['pending_entries']),
            'total_hours' => $summaries->sum(fn ($summary) => $summary['total_hours']),
            'projected_net' => $summaries->sum(fn ($summary) => $summary['net_pay']),
        ];

        $trainers = User::where('role_id', 5)
            ->where('is_archive', 0)
            ->with(['trainerSchedules.user_schedules.user'])
            ->get();

        $trainerProcessedRuns = PayrollRun::whereIn('user_id', $trainers->pluck('id'))
            ->where('period_month', $month)
            ->get()
            ->keyBy('user_id');

        $trainerAssignments = $trainers
            ->map(function ($trainer) use ($startOfMonth, $endOfMonth, $trainerProcessedRuns) {
                $now = Carbon::now();
                $scheduleDetails = collect($trainer->trainerSchedules ?? [])->map(function ($schedule) use ($now, $startOfMonth, $endOfMonth) {
                    $start = !empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date) : null;
                    $end = !empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date) : null;

                    $hasValidWindow = $start && $end && $end->greaterThan($start);
                    $hasRate = !is_null($schedule->trainer_rate_per_hour);
                    $isArchived = isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1;
                    $isSalaryEligible = $hasValidWindow && $hasRate && !$isArchived;

                    $hours = $hasValidWindow
                        ? $end->diffInMinutes($start) / 60
                        : 0;

                    $displaySalary = $hasRate
                        ? (float) $schedule->trainer_rate_per_hour * $hours
                        : 0;

                    $summarySalary = $isSalaryEligible
                        ? (float) $schedule->trainer_rate_per_hour * $hours
                        : 0;

                    $students = collect($schedule->user_schedules ?? [])->map(function ($userSchedule) {
                        $user = $userSchedule->user ?? null;
                        if (!$user) {
                            return null;
                        }

                        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                        return $fullName !== '' ? $fullName : ($user->email ?? null);
                    })->filter()->unique()->values();

                    $isPast = false;
                    if ($end) {
                        $isPast = $end->lt($now);
                    } elseif ($start) {
                        $isPast = $start->lt($now);
                    }

                    $category = $isPast ? 'past' : 'future';
                    $inMonth = false;
                    if ($start && $end) {
                        $inMonth = $start->lte($endOfMonth) && $end->gte($startOfMonth);
                    } elseif ($start) {
                        $inMonth = $start->between($startOfMonth, $endOfMonth, true);
                    } elseif ($end) {
                        $inMonth = $end->between($startOfMonth, $endOfMonth, true);
                    }

                    return [
                        'schedule' => $schedule,
                        'start' => $start,
                        'end' => $end,
                        'start_date' => $start ? $start->toDateString() : null,
                        'end_date' => $end ? $end->toDateString() : null,
                        'hours' => $hours,
                        'display_salary' => $displaySalary,
                        'summary_salary' => $summarySalary,
                        'salary_eligible' => $isSalaryEligible,
                        'students' => $students,
                        'category' => $category,
                        'in_month' => $inMonth,
                    ];
                });

                $futureDetails = $scheduleDetails->where('category', 'future')->where('in_month', true);
                $pastDetails = $scheduleDetails->where('category', 'past')->where('in_month', true);

                $salaryEligibleSchedules = $scheduleDetails->where('salary_eligible', true)->where('in_month', true);
                $payableSchedules = $salaryEligibleSchedules->where('category', 'past');
                $projectedSchedules = $salaryEligibleSchedules->where('category', 'future');

                $totals = [
                    'future_total' => $projectedSchedules->sum('summary_salary'),
                    'past_total' => $payableSchedules->sum('summary_salary'),
                    'future_count' => $futureDetails->count(),
                    'past_count' => $pastDetails->count(),
                    'future_payroll_count' => $projectedSchedules->count(),
                    'past_payroll_count' => $payableSchedules->count(),
                ];

                $projectedGross = round($salaryEligibleSchedules->sum('summary_salary'), 2);
                $gross = round($payableSchedules->sum('summary_salary'), 2);
                $deductions = [
                    'sss' => round($gross * 0.045, 2),
                    'philhealth' => round($gross * 0.025, 2),
                    'pagibig' => round(min($gross, 5000) * 0.02, 2),
                ];
                $net = max($gross - array_sum($deductions), 0);

                $processedRun = $trainerProcessedRuns->get($trainer->id);

                if ($processedRun) {
                    // Once processed, display zeroed values to mirror staff behavior and prevent reprocessing.
                    $gross = 0;
                    $net = 0;
                    $deductions = ['sss' => 0, 'philhealth' => 0, 'pagibig' => 0];
                    $salaryEligibleSchedules = collect();
                }

                return [
                    'trainer' => $trainer,
                    'details' => $scheduleDetails,
                    'entries_for_month' => $payableSchedules->values(),
                    'total_salary' => $projectedGross,
                    'payable_salary' => $gross,
                    'total_hours' => $payableSchedules->sum('hours'),
                    'projected_hours' => $salaryEligibleSchedules->sum('hours'),
                    'assignments_count' => $scheduleDetails->count(),
                    'salary_assignments_count' => $salaryEligibleSchedules->count(),
                    'payable_assignments_count' => $payableSchedules->count(),
                    'totals' => $totals,
                    'deductions' => $deductions,
                    'net_pay' => $net,
                    'processed_run' => $processedRun,
                ];
            })
            ->filter(fn ($assignment) => $assignment['assignments_count'] > 0)
            ->values();

        return view('admin.payrolls.process', [
            'summaries' => $summaries,
            'stats' => $stats,
            'search' => $search,
            'month' => $month,
            'monthLabel' => $targetMonth->format('F Y'),
            'trainerAssignments' => $trainerAssignments,
        ]);
    }
    
    public function processStaff(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'month'    => 'required|date_format:Y-m',
        ]);

        $staff = User::where('id', $request->staff_id)
            ->where('role_id', 2)
            ->where('is_archive', 0)
            ->firstOrFail();

        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $request->month);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Invalid payroll month provided.');
        }

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $entries = Attendance2::where('user_id', $staff->id)
            ->where('is_archive', 0)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
            ->orderBy('clockin_at')
            ->get()
            ->map(function ($attendance) use ($staff) {
                $clockIn = $attendance->clockin_at ? Carbon::parse($attendance->clockin_at) : null;
                $clockOut = $attendance->clockout_at ? Carbon::parse($attendance->clockout_at) : null;

                $hours = null;
                if ($clockIn && $clockOut && $clockOut->greaterThan($clockIn)) {
                    $hours = round($clockOut->diffInMinutes($clockIn) / 60, 2);
                }

                $amount = $hours ? round($hours * (float) ($staff->rate_per_hour ?? 0), 2) : 0;

                return [
                    'hours' => $hours ?? 0,
                    'amount' => $amount,
                ];
            });

        if ($entries->isEmpty()) {
            return redirect()->back()->with('error', 'No payroll entries found for this staff and month.');
        }

        $totalHours = $entries->sum('hours');
        $gross = round($entries->sum('amount'), 2);

        $deductions = [
            'sss' => round($gross * 0.045, 2),
            'philhealth' => round($gross * 0.025, 2),
            'pagibig' => round(min($gross, 5000) * 0.02, 2),
        ];

        $net = max($gross - array_sum($deductions), 0);

        PayrollRun::updateOrCreate(
            [
                'user_id' => $staff->id,
                'period_month' => $request->month,
            ],
            [
                'total_hours' => $totalHours,
                'gross_pay' => $gross,
                'net_pay' => $net,
                'deduction_sss' => $deductions['sss'],
                'deduction_philhealth' => $deductions['philhealth'],
                'deduction_pagibig' => $deductions['pagibig'],
                'processed_by' => Auth::id(),
                'processed_at' => Carbon::now(),
            ]
        );

        return redirect()->back()->with('success', 'Payroll processed and saved for ' . trim($staff->first_name . ' ' . $staff->last_name));
    }

    public function processTrainer(Request $request)
    {
        $request->validate([
            'trainer_id' => 'required|exists:users,id',
            'month'      => 'required|date_format:Y-m',
        ]);

        $trainer = User::where('id', $request->trainer_id)
            ->where('role_id', 5)
            ->where('is_archive', 0)
            ->with(['trainerSchedules.user_schedules.user'])
            ->firstOrFail();

        $now = Carbon::now();
        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $request->month);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Invalid payroll month provided.');
        }

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $eligibleSchedules = collect($trainer->trainerSchedules ?? [])->map(function ($schedule) use ($startOfMonth, $endOfMonth, $now) {
            $start = !empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date) : null;
            $end = !empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date) : null;

            $hasValidWindow = $start && $end && $end->greaterThan($start);
            $hasRate = !is_null($schedule->trainer_rate_per_hour);
            $isArchived = isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1;
            $isSalaryEligible = $hasValidWindow && $hasRate && !$isArchived;

            $inMonth = false;
            if ($start && $end) {
                $inMonth = $start->lte($endOfMonth) && $end->gte($startOfMonth);
            } elseif ($start) {
                $inMonth = $start->between($startOfMonth, $endOfMonth, true);
            } elseif ($end) {
                $inMonth = $end->between($startOfMonth, $endOfMonth, true);
            }

            $hours = $hasValidWindow
                ? $end->diffInMinutes($start) / 60
                : 0;

            $summarySalary = $isSalaryEligible
                ? (float) $schedule->trainer_rate_per_hour * $hours
                : 0;

            return [
                'hours' => $hours,
                'summary_salary' => $summarySalary,
                'salary_eligible' => $isSalaryEligible,
                'in_month' => $inMonth,
                'is_past' => $end
                    ? $end->lt($now)
                    : ($start ? $start->lt($now) : false),
            ];
        })->filter(fn ($detail) => $detail['salary_eligible'] && $detail['in_month'] && $detail['is_past']);

        if ($eligibleSchedules->isEmpty()) {
            return redirect()->back()->with('error', 'No payroll-eligible trainer assignments found for this month.');
        }

        $totalHours = $eligibleSchedules->sum('hours');
        $gross = round($eligibleSchedules->sum('summary_salary'), 2);

        $deductions = [
            'sss' => round($gross * 0.045, 2),
            'philhealth' => round($gross * 0.025, 2),
            'pagibig' => round(min($gross, 5000) * 0.02, 2),
        ];

        $net = max($gross - array_sum($deductions), 0);

        PayrollRun::updateOrCreate(
            [
                'user_id' => $trainer->id,
                'period_month' => $request->month,
            ],
            [
                'total_hours' => $totalHours,
                'gross_pay' => $gross,
                'net_pay' => $net,
                'deduction_sss' => $deductions['sss'],
                'deduction_philhealth' => $deductions['philhealth'],
                'deduction_pagibig' => $deductions['pagibig'],
                'processed_by' => Auth::id(),
                'processed_at' => Carbon::now(),
            ]
        );

        $trainerName = trim($trainer->first_name . ' ' . $trainer->last_name);

        return redirect()->back()->with('success', 'Trainer payroll processed and saved for ' . ($trainerName !== '' ? $trainerName : 'trainer'));
    }
    
    public function view($id)
    {
        $data = Payroll::findOrFail($id);

        return view('admin.payrolls.view', compact('data'));
    }
    
    public function clockin(Request $request)
    {
        $user = $request->user();

        $attendance = Attendance2::where('user_id', $user->id)
            ->where('is_archive', 0)
            ->whereDate('clockin_at', now()->toDateString())
            ->orderByDesc('clockin_at')
            ->first();
    
        if (!$attendance || $attendance->clockout_at) {
            $attendance = new Attendance2();
            $attendance->user_id = $user->id;
            $attendance->clockin_at = now();
            $attendance->is_archive = 0;
            $attendance->save();
    
            return redirect()->back()->with('success', 'Clocked in successfully.');
        }
        
        return redirect()->back()->with('error', 'You must clock out before clocking in again.');
    }
    
    public function clockout(Request $request)
    {
        $user = $request->user();

        $attendance = Attendance2::where('user_id', $user->id)
            ->where('is_archive', 0)
            ->whereDate('clockin_at', now()->toDateString())
            ->orderByDesc('clockin_at')
            ->first();
    
    
        if ($attendance && !$attendance->clockout_at) {
            $attendance->clockout_at = now();
            $attendance->save();

            return redirect()->back()->with('success', 'Clocked out successfully.');
        }
        
        return redirect()->back()->with('error', 'You must clock out before clocking out again.');
    }
}
