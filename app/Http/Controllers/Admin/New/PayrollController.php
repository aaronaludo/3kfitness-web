<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\Schedule;
use App\Models\Attendance2;
use App\Models\DeductionSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    private function currentDeductionSettings(): array
    {
        $setting = DeductionSetting::orderByDesc('id')->first();
        $processingDays = [];
        $processingDayRanges = [];
        if ($setting && is_array($setting->processing_days)) {
            $processingDays = collect($setting->processing_days)
                ->map(function ($day) {
                    if (is_string($day) && strtolower($day) === 'eom') {
                        return 'eom';
                    }
                    $int = (int) $day;
                    return $int >= 1 && $int <= 31 ? $int : null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        if ($setting && is_array($setting->processing_day_ranges)) {
            $processingDayRanges = collect($setting->processing_day_ranges)
                ->map(function ($range) {
                    $from = (int) ($range['from'] ?? 0);
                    $to = (int) ($range['to'] ?? 0);
                    $process = (int) ($range['process'] ?? 0);
                    if ($from < 1 || $from > 31 || $process < 1 || $process > 31) {
                        return null;
                    }
                    if ($to < 1 || $to > 31) {
                        $to = 31;
                    }
                    if ($from > $to) {
                        [$from, $to] = [$to, $from];
                    }
                    return [
                        'from' => $from,
                        'to' => $to,
                        'process' => $process,
                    ];
                })
                ->filter()
                ->values()
                ->toArray();
        }

        return [
            'sss_rate' => (float) ($setting->sss_rate ?? 4.5),
            'philhealth_rate' => (float) ($setting->philhealth_rate ?? 2.5),
            'pagibig_rate' => (float) ($setting->pagibig_rate ?? 2.0),
            'pagibig_cap' => (float) ($setting->pagibig_cap ?? 5000),
            'app_cut_rate' => (float) ($setting->app_cut_rate ?? 0),
            'processing_days' => $processingDays,
            'processing_day_ranges' => $processingDayRanges,
        ];
    }

    private function calculateDeductions(float $gross, array $settings): array
    {
        $sss = round($gross * ($settings['sss_rate'] / 100), 2);
        $philhealth = round($gross * ($settings['philhealth_rate'] / 100), 2);
        $pagibigBase = $settings['pagibig_cap'] > 0 ? min($gross, $settings['pagibig_cap']) : $gross;
        $pagibig = round($pagibigBase * ($settings['pagibig_rate'] / 100), 2);
        $appCut = round($gross * ($settings['app_cut_rate'] / 100), 2);
        $total = $sss + $philhealth + $pagibig + $appCut;

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'app_cut' => $appCut,
            'total' => $total,
        ];
    }

    private function buildTrainerScheduleDetails(User $trainer, Carbon $startOfMonth, Carbon $endOfMonth)
    {
        $trainer->loadMissing(['trainerSchedules.activeUserSchedules.user']);

        $now = Carbon::now();
        $trainerAttendances = Attendance2::where('user_id', $trainer->id)
            ->where('is_archive', 0)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
            ->get()
            ->map(function ($attendance) {
                return [
                    'clockin' => $attendance->clockin_at ? Carbon::parse($attendance->clockin_at) : null,
                    'clockout' => $attendance->clockout_at ? Carbon::parse($attendance->clockout_at) : null,
                ];
            });

        $weekdayKeys = [
            0 => 'sun',
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
        ];
        $weekdayLabels = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];

        return collect($trainer->trainerSchedules ?? [])->map(function ($schedule) use ($now, $startOfMonth, $endOfMonth, $trainerAttendances, $weekdayKeys, $weekdayLabels) {
            $seriesStart = !empty($schedule->series_start_date)
                ? Carbon::parse($schedule->series_start_date)->startOfDay()
                : (!empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date)->startOfDay() : null);
            $seriesEnd = !empty($schedule->series_end_date)
                ? Carbon::parse($schedule->series_end_date)->endOfDay()
                : (!empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date)->endOfDay() : null);

            $startTimeString = $schedule->class_start_time
                ?? (!empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date)->format('H:i:s') : null);
            $endTimeString = $schedule->class_end_time
                ?? (!empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date)->format('H:i:s') : null);

            $startTime = $startTimeString ? Carbon::parse($startTimeString) : null;
            $endTime = $endTimeString ? Carbon::parse($endTimeString) : null;
            $durationMinutes = ($startTime && $endTime && $endTime->greaterThan($startTime))
                ? $endTime->diffInMinutes($startTime)
                : 0;
            $durationHours = $durationMinutes > 0 ? $durationMinutes / 60 : 0;

            $hasValidWindow = $seriesStart && $seriesEnd && $seriesEnd->greaterThanOrEqualTo($seriesStart) && $durationMinutes > 0;
            $hasRate = !is_null($schedule->trainer_rate_per_hour);
            $isArchived = isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1;
            $isSalaryEligible = $hasValidWindow && $hasRate && !$isArchived;
            $dayKeys = is_array($schedule->recurring_days)
                ? $schedule->recurring_days
                : json_decode($schedule->recurring_days ?? '[]', true);
            $recurringDays = collect($dayKeys)->filter(fn ($d) => isset($weekdayLabels[$d]))->values();
            $recurringLabel = $recurringDays->map(fn ($d) => $weekdayLabels[$d])->implode(', ');

            $periodStart = $seriesStart ? $seriesStart->copy() : $startOfMonth->copy()->startOfDay();
            $periodEnd = $seriesEnd ? $seriesEnd->copy() : $endOfMonth->copy()->endOfDay();
            if ($periodStart->lt($startOfMonth)) {
                $periodStart = $startOfMonth->copy()->startOfDay();
            }
            if ($periodEnd->gt($endOfMonth)) {
                $periodEnd = $endOfMonth->copy()->endOfDay();
            }

            $occurrences = collect();
            if ($recurringDays->isEmpty()) {
                if ($seriesStart && $seriesStart->between($periodStart, $periodEnd, true)) {
                    $occurrences->push($seriesStart->copy());
                }
            } else {
                for ($cursor = $periodStart->copy(); $cursor->lte($periodEnd); $cursor->addDay()) {
                    $dayKey = $weekdayKeys[$cursor->dayOfWeek] ?? null;
                    if (!$dayKey || !$recurringDays->contains($dayKey)) {
                        continue;
                    }
                    $occurrences->push($cursor->copy());
                }
            }

            $occurrenceDetails = $occurrences->map(function ($occurrenceDate) use ($startTimeString, $durationMinutes, $now, $trainerAttendances, $isSalaryEligible, $schedule) {
                $occurrenceStart = $startTimeString
                    ? Carbon::parse($occurrenceDate->format('Y-m-d') . ' ' . $startTimeString)
                    : $occurrenceDate->copy();
                $occurrenceEnd = $durationMinutes > 0
                    ? $occurrenceStart->copy()->addMinutes($durationMinutes)
                    : $occurrenceStart->copy();

                $clockMatches = $trainerAttendances->filter(function ($attendance) use ($occurrenceStart, $occurrenceEnd) {
                    $clockIn = $attendance['clockin'];
                    $clockOut = $attendance['clockout'];

                    $overlapsStart = $clockIn && $clockIn->between($occurrenceStart, $occurrenceEnd, true);
                    $overlapsEnd = $clockOut && $clockOut->between($occurrenceStart, $occurrenceEnd, true);
                    $spansRange = $clockIn && $clockOut && $clockIn->lte($occurrenceStart) && $clockOut->gte($occurrenceEnd);
                    $clockInOnly = $clockIn && !$clockOut && $clockIn->between($occurrenceStart, $occurrenceEnd, true);

                    return $overlapsStart || $overlapsEnd || $spansRange || $clockInOnly;
                })->values();

                $hasAttendance = $clockMatches->isNotEmpty();
                $potentialSalary = $isSalaryEligible
                    ? (float) ($schedule->trainer_rate_per_hour ?? 0) * ($durationMinutes > 0 ? $durationMinutes / 60 : 0)
                    : 0;

                $isPastOccurrence = $occurrenceEnd->lt($now);
                $payrollSalary = ($isPastOccurrence && $hasAttendance) ? $potentialSalary : 0;
                $payrollHours = ($isPastOccurrence && $hasAttendance) ? ($durationMinutes > 0 ? $durationMinutes / 60 : 0) : 0;

                return [
                    'start' => $occurrenceStart,
                    'end' => $occurrenceEnd,
                    'hours' => $durationMinutes > 0 ? $durationMinutes / 60 : 0,
                    'category' => $isPastOccurrence ? 'past' : 'future',
                    'has_attendance' => $hasAttendance,
                    'attendance' => $clockMatches->map(function ($attendance) {
                        return [
                            'clockin_at' => $attendance['clockin'],
                            'clockout_at' => $attendance['clockout'],
                        ];
                    })->values(),
                    'potential_salary' => $potentialSalary,
                    'payroll_salary' => $payrollSalary,
                    'payroll_hours' => $payrollHours,
                ];
            });

            $pastOccurrences = $occurrenceDetails->where('category', 'past');
            $futureOccurrences = $occurrenceDetails->where('category', 'future');
            $payrollOccurrences = $pastOccurrences->where('has_attendance', true);

            $payrollSalary = $payrollOccurrences->sum('payroll_salary');
            $payrollHours = $payrollOccurrences->sum('payroll_hours');
            $pastPotentialSalary = $pastOccurrences->sum('potential_salary');
            $futurePotentialSalary = $futureOccurrences->sum('potential_salary');
            $summarySalary = $pastPotentialSalary + $futurePotentialSalary;
            $hasAttendance = $payrollOccurrences->isNotEmpty();

            $students = collect($schedule->activeUserSchedules ?? [])->map(function ($userSchedule) {
                $user = $userSchedule->user ?? null;
                if (!$user) {
                    return null;
                }

                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                return $fullName !== '' ? $fullName : ($user->email ?? null);
            })->filter()->unique()->values();

            $category = ($seriesEnd && $seriesEnd->lt($now)) ? 'past' : 'future';
            $inMonth = $periodStart->lte($endOfMonth) && $periodEnd->gte($startOfMonth);

            return [
                'schedule' => $schedule,
                'start' => $seriesStart,
                'end' => $seriesEnd,
                'start_date' => $seriesStart ? $seriesStart->toDateString() : null,
                'end_date' => $seriesEnd ? $seriesEnd->toDateString() : null,
                'hours' => $durationHours,
                'hours_per_occurrence' => $durationHours,
                'display_salary' => $payrollSalary + $futurePotentialSalary,
                'summary_salary' => $summarySalary,
                'payroll_salary' => $payrollSalary,
                'payroll_hours' => $payrollHours,
                'salary_eligible' => $isSalaryEligible,
                'students' => $students,
                'category' => $category,
                'in_month' => $inMonth,
                'has_attendance' => $hasAttendance,
                'attendances' => $payrollOccurrences->flatMap(function ($occurrence) {
                    return $occurrence['attendance'];
                })->values(),
                'recurring_days' => $recurringDays,
                'recurring_label' => $recurringLabel,
                'occurrence_dates' => $occurrenceDetails->map(fn ($occ) => $occ['start']->format('Y-m-d'))->values(),
                'past_dates' => $pastOccurrences->map(fn ($occ) => $occ['start']->format('Y-m-d'))->values(),
                'future_dates' => $futureOccurrences->map(fn ($occ) => $occ['start']->format('Y-m-d'))->values(),
                'paid_dates' => $payrollOccurrences->map(fn ($occ) => $occ['start']->format('Y-m-d'))->values(),
                'past_occurrence_count' => $pastOccurrences->count(),
                'future_occurrence_count' => $futureOccurrences->count(),
                'past_paid_count' => $payrollOccurrences->count(),
                'future_potential_salary' => $futurePotentialSalary,
                'past_potential_salary' => $pastPotentialSalary,
                'time_range' => ($startTimeString && $endTimeString)
                    ? Carbon::parse($startTimeString)->format('g:i A') . ' - ' . Carbon::parse($endTimeString)->format('g:i A')
                    : null,
            ];
        });
    }

    private function formatTrainerAssignmentsForPayslip($scheduleDetails): array
    {
        return collect($scheduleDetails ?? [])
            ->filter(function ($detail) {
                $att = collect($detail['attendances'] ?? []);
                return $att->isNotEmpty() || ($detail['has_attendance'] ?? false);
            })
            ->map(function ($detail) {
                $schedule = $detail['schedule'];
                $start = $detail['start'];
                $end = $detail['end'];
                $paidDates = collect($detail['paid_dates'] ?? $detail['occurrence_dates'] ?? collect())->map(function ($date) {
                    try {
                        return Carbon::parse($date)->format('M d, Y');
                    } catch (\Throwable $th) {
                        return $date;
                    }
                })->filter()->values();
                $attendance = collect($detail['attendances'] ?? collect())->map(function ($record) {
                    $clockIn = $record['clockin_at'] ?? null;
                    $clockOut = $record['clockout_at'] ?? null;

                    $label = '';
                    if ($clockIn) {
                        $label .= $clockIn->format('g:i A');
                    }

                    if ($clockOut) {
                        $label .= $label !== '' ? ' - ' . $clockOut->format('g:i A') : $clockOut->format('g:i A');
                    }

                    return $label !== '' ? $label : 'Attendance recorded';
                })->filter()->values();

                return [
                    'title' => $schedule->name ?? 'Class schedule',
                    'code' => $schedule->class_code ?? ($schedule->id ?? 'N/A'),
                    'date' => $paidDates->isNotEmpty()
                        ? $paidDates->implode(', ')
                        : ($start ? $start->format('M d, Y') : '—'),
                    'time' => $detail['time_range'] ?? ($start || $end
                        ? trim(($start ? $start->format('g:i A') : '') . ($end ? ' - ' . $end->format('g:i A') : ''))
                        : '—'),
                    'hours' => $detail['payroll_hours'] ?? $detail['hours'] ?? 0,
                    'scheduled_hours' => $detail['hours'] ?? 0,
                    'salary' => $detail['payroll_salary'] ?? $detail['summary_salary'] ?? $detail['display_salary'] ?? 0,
                    'attendance' => $attendance->toArray(),
                    'recurrence' => $detail['recurring_label'] ?? '',
                    'status' => ($detail['has_attendance'] ?? false) ? 'Present' : 'Absent',
                ];
            })
            ->values()
            ->toArray();
    }
    public function index(Request $request)
    {
        $search = $request->input('member_name');
        $searchColumn = $request->input('search_column');
        $period = $request->input('period_month');
        $deductionSettings = $this->currentDeductionSettings();
        $appCutRate = max((float) $request->input('app_cut_rate', $deductionSettings['app_cut_rate']), 0);

        $allowedColumns = ['id', 'name', 'email', 'user_code', 'period_month', 'processed_at', 'created_at', 'updated_at'];
        if (!in_array($searchColumn, $allowedColumns, true)) {
            $searchColumn = null;
        }

        $baseQuery = PayrollRun::with('user')
            ->when($search, function ($query, $search) use ($searchColumn) {
                $query->where(function ($subQuery) use ($search, $searchColumn) {
                    if ($searchColumn === 'id') {
                        return $subQuery->where('id', $search);
                    }

                    if ($searchColumn === 'name') {
                        return $subQuery->whereHas('user', function ($userQuery) use ($search) {
                            $userQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }

                    if ($searchColumn === 'email') {
                        return $subQuery->whereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%");
                        });
                    }

                    if ($searchColumn === 'user_code') {
                        return $subQuery->whereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('user_code', 'like', "%{$search}%");
                        });
                    }

                    if ($searchColumn === 'period_month') {
                        return $subQuery->where('period_month', 'like', "%{$search}%");
                    }

                    if (in_array($searchColumn, ['processed_at', 'created_at', 'updated_at'], true)) {
                        return $subQuery->where($searchColumn, 'like', "%{$search}%");
                    }

                    $subQuery->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($period, function ($query, $period) {
                $query->where('period_month', $period);
            });

        $printAllRuns = (clone $baseQuery)
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->get();

        $runs = (clone $baseQuery)
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $payslipDetails = [];
        foreach ($runs as $run) {
            $user = $run->user;
            if (!$user || empty($run->period_month)) {
                continue;
            }

            try {
                $targetMonth = Carbon::createFromFormat('Y-m', $run->period_month);
            } catch (\Throwable $th) {
                continue;
            }

            $startOfMonth = $targetMonth->copy()->startOfMonth();
            $endOfMonth = $targetMonth->copy()->endOfMonth();

            if ($user->role_id === 2) {
                $entries = Attendance2::where('user_id', $user->id)
                    ->where('is_archive', 0)
                    ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                        $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                            ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                            ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
                    })
                    ->orderBy('clockin_at')
                    ->get()
                    ->map(function ($attendance) use ($user) {
                        $clockIn = $attendance->clockin_at ? Carbon::parse($attendance->clockin_at) : null;
                        $clockOut = $attendance->clockout_at ? Carbon::parse($attendance->clockout_at) : null;

                        $hours = ($clockIn && $clockOut && $clockOut->greaterThan($clockIn))
                            ? round($clockOut->diffInMinutes($clockIn) / 60, 2)
                            : 0;

                        return [
                            'id' => $attendance->id,
                            'clockin' => $clockIn ? $clockIn->format('M d, Y g:i A') : '—',
                            'clockout' => $clockOut ? $clockOut->format('M d, Y g:i A') : '—',
                            'hours' => $hours,
                            'amount' => $hours > 0 ? round($hours * (float) ($user->rate_per_hour ?? 0), 2) : 0,
                            'status' => $clockOut ? 'complete' : 'pending',
                        ];
                    })
                    ->values()
                    ->all();

                $payslipDetails[$run->id] = [
                    'entries' => $entries,
                    'assignments' => [],
                ];
            } elseif ($user->role_id === 5) {
                $scheduleDetails = $this->buildTrainerScheduleDetails($user, $startOfMonth, $endOfMonth);
                $assignments = $this->formatTrainerAssignmentsForPayslip($scheduleDetails);

                $payslipDetails[$run->id] = [
                    'entries' => [],
                    'assignments' => $assignments,
                ];
            }
        }

        $appCutTotal = $printAllRuns->sum(function ($run) use ($appCutRate) {
            $stored = $run->deduction_app_cut ?? null;
            if (!is_null($stored) && (float) $stored !== 0.0) {
                return (float) $stored;
            }

            $gross = (float) ($run->gross_pay ?? 0);
            return round($gross * ($appCutRate / 100), 2);
        });

        return view('admin.payrolls.index', [
            'runs' => $runs,
            'printAllRuns' => $printAllRuns,
            'deductionSettings' => $deductionSettings,
            'payslipDetails' => $payslipDetails,
        ]);
    }

    public function updateDeductions(Request $request)
    {
        $data = $request->validate([
            'sss_rate' => 'required|numeric|min:0',
            'philhealth_rate' => 'required|numeric|min:0',
            'pagibig_rate' => 'required|numeric|min:0',
            'pagibig_cap' => 'required|numeric|min:0',
            'app_cut_rate' => 'nullable|numeric|min:0',
            'processing_days' => 'nullable|array',
            'processing_days.*' => 'nullable',
            'processing_day_ranges' => 'nullable|array',
            'processing_day_ranges.from' => 'nullable|array',
            'processing_day_ranges.to' => 'nullable|array',
            'processing_day_ranges.process' => 'nullable|array',
        ]);

        $setting = DeductionSetting::orderByDesc('id')->first() ?? new DeductionSetting();
        $processingDays = collect($data['processing_days'] ?? [])
            ->map(function ($day) {
                if (is_string($day) && strtolower($day) === 'eom') {
                    return 'eom';
                }
                $int = (int) $day;
                return $int >= 1 && $int <= 31 ? $int : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $rangeInputs = $data['processing_day_ranges'] ?? ['from' => [], 'to' => [], 'process' => []];
        $processingDayRanges = collect($rangeInputs['from'] ?? [])
            ->map(function ($from, $index) use ($rangeInputs) {
                $to = $rangeInputs['to'][$index] ?? null;
                $process = $rangeInputs['process'][$index] ?? null;
                $fromInt = (int) $from;
                $toInt = is_null($to) ? 31 : (int) $to;
                $processInt = (int) $process;

                if ($fromInt < 1 || $fromInt > 31 || $processInt < 1 || $processInt > 31) {
                    return null;
                }
                if ($toInt < 1 || $toInt > 31) {
                    $toInt = 31;
                }
                if ($fromInt > $toInt) {
                    [$fromInt, $toInt] = [$toInt, $fromInt];
                }

                return [
                    'from' => $fromInt,
                    'to' => $toInt,
                    'process' => $processInt,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        $setting->fill([
            'sss_rate' => $data['sss_rate'],
            'philhealth_rate' => $data['philhealth_rate'],
            'pagibig_rate' => $data['pagibig_rate'],
            'pagibig_cap' => $data['pagibig_cap'],
            'app_cut_rate' => $data['app_cut_rate'] ?? 0,
            'processing_days' => $processingDays,
            'processing_day_ranges' => $processingDayRanges,
        ]);
        $setting->save();

        return redirect()->back()->with('success', 'Deduction settings updated.');
    }

    public function process(Request $request)
    {
        $search = $request->input('search');
        $month = $request->input('month', now()->format('Y-m'));
        $deductionSettings = $this->currentDeductionSettings();

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

        $summaries = $staffMembers->map(function ($staff) use ($attendanceByUser, $deductionSettings) {
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

            $deductions = $this->calculateDeductions($gross, $deductionSettings);
            $net = max($gross - $deductions['total'], 0);

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
                    'app_cut' => 0,
                    'total' => 0,
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
            ->with(['trainerSchedules.activeUserSchedules.user'])
            ->get();

        $trainerProcessedRuns = PayrollRun::whereIn('user_id', $trainers->pluck('id'))
            ->where('period_month', $month)
            ->get()
            ->keyBy('user_id');

        $trainerAssignments = $trainers
            ->map(function ($trainer) use ($startOfMonth, $endOfMonth, $trainerProcessedRuns, $deductionSettings) {
                $scheduleDetails = $this->buildTrainerScheduleDetails($trainer, $startOfMonth, $endOfMonth);
                $salaryEligibleSchedules = $scheduleDetails->where('salary_eligible', true)->where('in_month', true);
                $payableSchedules = $salaryEligibleSchedules->filter(fn ($detail) => ($detail['past_paid_count'] ?? 0) > 0);

                $totals = [
                    'future_total' => $salaryEligibleSchedules->sum('future_potential_salary'),
                    'past_total' => $salaryEligibleSchedules->sum('payroll_salary'),
                    'future_count' => $salaryEligibleSchedules->sum('future_occurrence_count'),
                    'past_count' => $salaryEligibleSchedules->sum('past_occurrence_count'),
                    'future_payroll_count' => $salaryEligibleSchedules->sum('future_occurrence_count'),
                    'past_payroll_count' => $salaryEligibleSchedules->sum('past_paid_count'),
                ];

                $projectedGross = round($salaryEligibleSchedules->sum(function ($detail) {
                    return ($detail['payroll_salary'] ?? 0) + ($detail['future_potential_salary'] ?? 0);
                }), 2);
                $gross = round($salaryEligibleSchedules->sum('payroll_salary'), 2);
                $deductions = $this->calculateDeductions($gross, $deductionSettings);
                $net = max($gross - $deductions['total'], 0);

                $processedRun = $trainerProcessedRuns->get($trainer->id);

                if ($processedRun) {
                    // Once processed, display zeroed values to mirror staff behavior and prevent reprocessing.
                    $gross = 0;
                    $net = 0;
                    $deductions = ['sss' => 0, 'philhealth' => 0, 'pagibig' => 0, 'app_cut' => 0, 'total' => 0];
                    $salaryEligibleSchedules = collect();
                }

                return [
                    'trainer' => $trainer,
                    'details' => $scheduleDetails,
                    'entries_for_month' => $payableSchedules->values(),
                    'total_salary' => $projectedGross,
                    'payable_salary' => $gross,
                    'total_hours' => $salaryEligibleSchedules->sum('payroll_hours'),
                    'projected_hours' => $salaryEligibleSchedules->sum(function ($detail) {
                        $futureHours = ($detail['future_occurrence_count'] ?? 0) * ($detail['hours_per_occurrence'] ?? 0);
                        return ($detail['payroll_hours'] ?? 0) + $futureHours;
                    }),
                    'assignments_count' => $scheduleDetails->count(),
                    'salary_assignments_count' => $salaryEligibleSchedules->sum(function ($detail) {
                        return ($detail['past_occurrence_count'] ?? 0) + ($detail['future_occurrence_count'] ?? 0);
                    }),
                    'payable_assignments_count' => $payableSchedules->sum('past_paid_count'),
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
            'deductionSettings' => $deductionSettings,
        ]);
    }
    
    public function processStaff(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'month'    => 'required|date_format:Y-m',
        ]);

        $deductionSettings = $this->currentDeductionSettings();
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

        $deductions = $this->calculateDeductions($gross, $deductionSettings);

        $net = max($gross - $deductions['total'], 0);

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
                'deduction_app_cut' => $deductions['app_cut'],
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

        $deductionSettings = $this->currentDeductionSettings();
        $trainer = User::where('id', $request->trainer_id)
            ->where('role_id', 5)
            ->where('is_archive', 0)
            ->with(['trainerSchedules.activeUserSchedules.user'])
            ->firstOrFail();

        $now = Carbon::now();
        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $request->month);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Invalid payroll month provided.');
        }

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $trainerAttendances = Attendance2::where('user_id', $trainer->id)
            ->where('is_archive', 0)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
            ->get()
            ->map(function ($attendance) {
                return [
                    'clockin' => $attendance->clockin_at ? Carbon::parse($attendance->clockin_at) : null,
                    'clockout' => $attendance->clockout_at ? Carbon::parse($attendance->clockout_at) : null,
                ];
            });

        $weekdayKeys = [
            0 => 'sun',
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
        ];

        $eligibleSchedules = collect($trainer->trainerSchedules ?? [])->map(function ($schedule) use ($startOfMonth, $endOfMonth, $now, $trainerAttendances, $weekdayKeys) {
            $start = !empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date) : null;
            $end = !empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date) : null;

            $seriesStart = !empty($schedule->series_start_date)
                ? Carbon::parse($schedule->series_start_date)->startOfDay()
                : (!empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date)->startOfDay() : null);
            $seriesEnd = !empty($schedule->series_end_date)
                ? Carbon::parse($schedule->series_end_date)->endOfDay()
                : (!empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date)->endOfDay() : null);

            $startTimeString = $schedule->class_start_time
                ?? (!empty($schedule->class_start_date) ? Carbon::parse($schedule->class_start_date)->format('H:i:s') : null);
            $endTimeString = $schedule->class_end_time
                ?? (!empty($schedule->class_end_date) ? Carbon::parse($schedule->class_end_date)->format('H:i:s') : null);

            $startTime = $startTimeString ? Carbon::parse($startTimeString) : null;
            $endTime = $endTimeString ? Carbon::parse($endTimeString) : null;

            $durationMinutes = ($startTime && $endTime && $endTime->greaterThan($startTime))
                ? $endTime->diffInMinutes($startTime)
                : 0;
            $durationHours = $durationMinutes > 0 ? $durationMinutes / 60 : 0;

            $hasValidWindow = $seriesStart && $seriesEnd && $seriesEnd->greaterThanOrEqualTo($seriesStart) && $durationMinutes > 0;
            $hasRate = !is_null($schedule->trainer_rate_per_hour);
            $isArchived = isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1;
            $isSalaryEligible = $hasValidWindow && $hasRate && !$isArchived;

            $inMonth = $seriesStart
                ? $seriesStart->lte($endOfMonth) && ($seriesEnd ? $seriesEnd->gte($startOfMonth) : true)
                : true;

            $dayKeys = is_array($schedule->recurring_days)
                ? $schedule->recurring_days
                : json_decode($schedule->recurring_days ?? '[]', true);
            $recurringDays = collect($dayKeys)->filter(fn ($d) => in_array($d, $weekdayKeys, true));

            $periodStart = $seriesStart ? $seriesStart->copy() : $startOfMonth->copy()->startOfDay();
            $periodEnd = $seriesEnd ? $seriesEnd->copy() : $endOfMonth->copy()->endOfDay();
            if ($periodStart->lt($startOfMonth)) {
                $periodStart = $startOfMonth->copy()->startOfDay();
            }
            if ($periodEnd->gt($endOfMonth)) {
                $periodEnd = $endOfMonth->copy()->endOfDay();
            }

            $occurrences = collect();
            if ($recurringDays->isEmpty()) {
                if ($seriesStart && $seriesStart->between($periodStart, $periodEnd, true)) {
                    $occurrences->push($seriesStart->copy());
                }
            } else {
                for ($cursor = $periodStart->copy(); $cursor->lte($periodEnd); $cursor->addDay()) {
                    $dayKey = $weekdayKeys[$cursor->dayOfWeek] ?? null;
                    if (!$dayKey || !$recurringDays->contains($dayKey)) {
                        continue;
                    }
                    $occurrences->push($cursor->copy());
                }
            }

            $paidOccurrences = $occurrences->map(function ($occurrenceDate) use ($durationMinutes, $startTimeString, $trainerAttendances, $isSalaryEligible, $schedule, $now) {
                $occurrenceStart = $startTimeString
                    ? Carbon::parse($occurrenceDate->format('Y-m-d') . ' ' . $startTimeString)
                    : $occurrenceDate->copy();
                $occurrenceEnd = $durationMinutes > 0
                    ? $occurrenceStart->copy()->addMinutes($durationMinutes)
                    : $occurrenceStart->copy();

                $clockMatches = $trainerAttendances->filter(function ($attendance) use ($occurrenceStart, $occurrenceEnd) {
                    $clockIn = $attendance['clockin'];
                    $clockOut = $attendance['clockout'];

                    $overlapsStart = $clockIn && $clockIn->between($occurrenceStart, $occurrenceEnd, true);
                    $overlapsEnd = $clockOut && $clockOut->between($occurrenceStart, $occurrenceEnd, true);
                    $spansRange = $clockIn && $clockOut && $clockIn->lte($occurrenceStart) && $clockOut->gte($occurrenceEnd);
                    $clockInOnly = $clockIn && !$clockOut && $clockIn->between($occurrenceStart, $occurrenceEnd, true);

                    return $overlapsStart || $overlapsEnd || $spansRange || $clockInOnly;
                });

                $isPastOccurrence = $occurrenceEnd->lt($now);
                $hasAttendance = $clockMatches->isNotEmpty();
                $potentialSalary = $isSalaryEligible
                    ? (float) ($schedule->trainer_rate_per_hour ?? 0) * ($durationMinutes > 0 ? $durationMinutes / 60 : 0)
                    : 0;
                $payrollSalary = ($isPastOccurrence && $hasAttendance) ? $potentialSalary : 0;
                $payrollHours = ($isPastOccurrence && $hasAttendance) ? ($durationMinutes > 0 ? $durationMinutes / 60 : 0) : 0;

                return [
                    'is_past' => $isPastOccurrence,
                    'has_attendance' => $hasAttendance,
                    'payroll_salary' => $payrollSalary,
                    'payroll_hours' => $payrollHours,
                ];
            })->filter(fn ($occurrence) => $occurrence['is_past']);

            return [
                'salary_eligible' => $isSalaryEligible,
                'in_month' => $inMonth,
                'paid_occurrences' => $paidOccurrences,
            ];
        })->filter(fn ($detail) => $detail['salary_eligible'] && $detail['in_month'] && $detail['paid_occurrences']->isNotEmpty());

        if ($eligibleSchedules->isEmpty()) {
            return redirect()->back()->with('error', 'No payroll-eligible trainer assignments with attendance found for this month.');
        }

        $totalHours = $eligibleSchedules->sum(fn ($detail) => $detail['paid_occurrences']->sum('payroll_hours'));
        $gross = round($eligibleSchedules->sum(fn ($detail) => $detail['paid_occurrences']->sum('payroll_salary')), 2);

        $deductions = $this->calculateDeductions($gross, $deductionSettings);

        $net = max($gross - $deductions['total'], 0);

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
                'deduction_app_cut' => $deductions['app_cut'],
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
