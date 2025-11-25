<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payroll;
use App\Models\Schedule;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->member_name;
    
        $data = Payroll::query()
            ->with('user')
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($subQuery) use ($search) {
                    $subQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.payrolls.index', compact('data'));
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
            ->where('is_archive', 0)
            ->with(['payrolls' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where(function ($inner) use ($startOfMonth, $endOfMonth) {
                    $inner->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                        ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
                })->orderBy('clockin_at', 'desc');
            }]);

        if ($search) {
            $staffQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staffMembers = $staffQuery->orderBy('first_name')->get();

        $summaries = $staffMembers->map(function ($staff) {
            $entries = $staff->payrolls->map(function ($payroll) use ($staff) {
                $clockIn = $payroll->clockin_at ? Carbon::parse($payroll->clockin_at) : null;
                $clockOut = $payroll->clockout_at ? Carbon::parse($payroll->clockout_at) : null;

                $hours = null;
                if ($clockIn && $clockOut && $clockOut->greaterThan($clockIn)) {
                    $hours = round($clockOut->diffInMinutes($clockIn) / 60, 2);
                }

                $amount = $hours ? round($hours * (float) ($staff->rate_per_hour ?? 0), 2) : null;

                return [
                    'id' => $payroll->id,
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
        })->filter(fn ($summary) => $summary['entries']->count() > 0)->values();

        $stats = [
            'staff_count' => $summaries->count(),
            'pending_entries' => $summaries->sum(fn ($summary) => $summary['pending_entries']),
            'total_hours' => $summaries->sum(fn ($summary) => $summary['total_hours']),
            'projected_net' => $summaries->sum(fn ($summary) => $summary['net_pay']),
        ];

        $trainerAssignments = User::where('role_id', 5)
            ->where('is_archive', 0)
            ->with(['trainerSchedules.user_schedules.user'])
            ->get()
            ->map(function ($trainer) use ($targetMonth) {
                $now = Carbon::now();
                $scheduleDetails = collect($trainer->trainerSchedules ?? [])->map(function ($schedule) use ($now) {
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
                    ];
                });

                $futureDetails = $scheduleDetails->where('category', 'future');
                $pastDetails = $scheduleDetails->where('category', 'past');

                $salaryEligibleSchedules = $scheduleDetails->where('salary_eligible', true);

                $totals = [
                    'future_total' => $futureDetails->sum('summary_salary'),
                    'past_total' => $pastDetails->sum('summary_salary'),
                    'future_count' => $futureDetails->count(),
                    'past_count' => $pastDetails->count(),
                    'future_payroll_count' => $futureDetails->where('salary_eligible', true)->count(),
                    'past_payroll_count' => $pastDetails->where('salary_eligible', true)->count(),
                ];

                return [
                    'trainer' => $trainer,
                    'details' => $scheduleDetails,
                    'total_salary' => $salaryEligibleSchedules->sum('summary_salary'),
                    'assignments_count' => $scheduleDetails->count(),
                    'salary_assignments_count' => $salaryEligibleSchedules->count(),
                    'totals' => $totals,
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
    
    public function view($id)
    {
        $data = Payroll::findOrFail($id);

        return view('admin.payrolls.view', compact('data'));
    }
    
    public function clockin(Request $request)
    {
        $user = $request->user();
        
        $payroll = Payroll::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->first();
    
        if (!$payroll || $payroll->clockout_at) {
            $payroll = new Payroll();
            $payroll->user_id = $user->id;
            $payroll->clockin_at = now();
            $payroll->save();
    
            return redirect()->back()->with('success', 'Clocked in successfully.');
        }
        
        return redirect()->back()->with('error', 'You must clock out before clocking in again.');
    }
    
    public function clockout(Request $request)
    {
        $user = $request->user();
        
        $payroll = Payroll::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->first();
    
    
        if ($payroll && !$payroll->clockout_at) {
            $payroll->clockout_at = now();
            $payroll->save();

            return redirect()->back()->with('success', 'Clocked out successfully.');
        }
        
        return redirect()->back()->with('error', 'You must clock out before clocking out again.');
    }
}
