<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\ClassAttendance;
use App\Models\DeductionSetting;
use App\Models\MembershipPayment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

    private function calculateDeductions(float $gross, array $settings, bool $applyAppCut = true, ?User $person = null): array
    {
        $hasTin = $person ? !empty($person->tin_number) : true;
        $hasSss = $person ? !empty($person->sss_number) : true;
        $hasPhilhealth = $person ? !empty($person->philhealth_number) : true;
        $hasPagibig = $person ? !empty($person->pagibig_number) : true;

        $sss = ($hasTin && $hasSss) ? round($gross * ($settings['sss_rate'] / 100), 2) : 0.0;
        $philhealth = ($hasTin && $hasPhilhealth) ? round($gross * ($settings['philhealth_rate'] / 100), 2) : 0.0;
        $pagibigBase = $settings['pagibig_cap'] > 0 ? min($gross, $settings['pagibig_cap']) : $gross;
        $pagibig = ($hasTin && $hasPagibig) ? round($pagibigBase * ($settings['pagibig_rate'] / 100), 2) : 0.0;
        $appCut = $applyAppCut ? round($gross * ($settings['app_cut_rate'] / 100), 2) : 0.0;
        $total = $sss + $philhealth + $pagibig + $appCut;

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'app_cut' => $appCut,
            'total' => $total,
        ];
    }

    private function getActiveProcessingRanges(array $settings, ?Carbon $referenceDate = null): array
    {
        $ranges = $settings['processing_day_ranges'] ?? [];
        if (!is_array($ranges) || empty($ranges)) {
            return [];
        }

        $referenceDate = $referenceDate ? $referenceDate->copy() : Carbon::now();
        $day = (int) $referenceDate->day;

        return collect($ranges)
            ->filter(function ($range) use ($day) {
                $process = (int) ($range['process'] ?? 0);
                return $process === $day;
            })
            ->values()
            ->toArray();
    }

    private function dateMatchesProcessingRanges($date, Carbon $targetMonth, array $ranges): bool
    {
        if (!$date) {
            return false;
        }

        $parsed = $date instanceof Carbon ? $date : Carbon::parse($date);
        if ($parsed->year !== $targetMonth->year || $parsed->month !== $targetMonth->month) {
            return false;
        }

        $day = (int) $parsed->day;
        foreach ($ranges as $range) {
            $from = (int) ($range['from'] ?? 1);
            $to = (int) ($range['to'] ?? 31);
            if ($day >= $from && $day <= $to) {
                return true;
            }
        }

        return false;
    }

    private function filterAttendancesForProcessingRanges($attendances, Carbon $targetMonth, array $ranges)
    {
        $collection = collect($attendances);
        if (empty($ranges)) {
            return $collection;
        }

        return $collection->filter(function ($attendance) use ($targetMonth, $ranges) {
            $dates = [
                $attendance->clockin_at ?? null,
                $attendance->clockout_at ?? null,
                $attendance->created_at ?? null,
            ];

            foreach ($dates as $date) {
                if ($this->dateMatchesProcessingRanges($date, $targetMonth, $ranges)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function buildTrainerScheduleDetails(
        User $trainer,
        Carbon $startOfMonth,
        Carbon $endOfMonth,
        array $processingRanges = [],
        array $processedSessionLookup = []
    )
    {
        $trainer->loadMissing(['trainerSchedules.activeUserSchedules.user']);

        $scheduleIds = collect($trainer->trainerSchedules ?? [])->pluck('id')->filter()->values();
        $classAttendanceLookup = collect();
        if ($scheduleIds->isNotEmpty()) {
            $classAttendanceLookup = ClassAttendance::whereIn('schedule_id', $scheduleIds)
                ->whereBetween('session_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->get()
                ->groupBy('schedule_id');
        }

        $now = Carbon::now();
        $trainerAttendances = Attendance::where('user_id', $trainer->id)
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

        $applyProcessingRanges = !empty($processingRanges);
        $applyProcessedLookup = !empty($processedSessionLookup);

        return collect($trainer->trainerSchedules ?? [])->map(function ($schedule) use ($now, $startOfMonth, $endOfMonth, $trainerAttendances, $weekdayKeys, $weekdayLabels, $classAttendanceLookup, $processingRanges, $applyProcessingRanges, $processedSessionLookup, $applyProcessedLookup) {
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

            $classAttendances = $classAttendanceLookup->get($schedule->id, collect());
            $classAttendanceByDate = $classAttendances
                ->filter(function ($attendance) {
                    return !empty($attendance->session_date);
                })
                ->groupBy(function ($attendance) {
                    return $attendance->session_date instanceof Carbon
                        ? $attendance->session_date->toDateString()
                        : Carbon::parse($attendance->session_date)->toDateString();
                })
                ->map(function ($items) {
                    return $items->count();
                });

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

            $occurrenceDetails = $occurrences->map(function ($occurrenceDate) use ($startTimeString, $durationMinutes, $now, $trainerAttendances, $isSalaryEligible, $schedule, $classAttendanceByDate) {
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

                $sessionDateKey = $occurrenceDate->toDateString();
                $hasClassAttendance = $classAttendanceByDate->has($sessionDateKey);

                $attendanceRecords = $clockMatches->map(function ($attendance) {
                    return [
                        'clockin_at' => $attendance['clockin'],
                        'clockout_at' => $attendance['clockout'],
                    ];
                })->values();

                if ($attendanceRecords->isEmpty() && $hasClassAttendance) {
                    $attendanceRecords = collect([[
                        'clockin_at' => null,
                        'clockout_at' => null,
                    ]]);
                }

                $hasAttendance = $attendanceRecords->isNotEmpty();
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
                    'attendance' => $attendanceRecords,
                    'potential_salary' => $potentialSalary,
                    'payroll_salary' => $payrollSalary,
                    'payroll_hours' => $payrollHours,
                ];
            });

            if ($applyProcessingRanges) {
                $occurrenceDetails = $occurrenceDetails
                    ->filter(function ($occurrence) use ($processingRanges) {
                        $start = $occurrence['start'] ?? null;
                        if (!($start instanceof Carbon)) {
                            return false;
                        }
                        $day = (int) $start->day;
                        foreach ($processingRanges as $range) {
                            $from = (int) ($range['from'] ?? 1);
                            $to = (int) ($range['to'] ?? 31);
                            if ($day >= $from && $day <= $to) {
                                return true;
                            }
                        }
                        return false;
                    })
                    ->values();
            }
            if ($applyProcessedLookup) {
                $occurrenceDetails = $occurrenceDetails
                    ->filter(function ($occurrence) use ($schedule, $processedSessionLookup) {
                        $start = $occurrence['start'] ?? null;
                        if (!($start instanceof Carbon)) {
                            return true;
                        }
                        $dateKey = $start->toDateString();
                        $scheduleId = $schedule->id ?? null;
                        if ($scheduleId && !empty($processedSessionLookup['by_schedule_id'][$scheduleId][$dateKey])) {
                            return false;
                        }
                        $classCode = $schedule->class_code ?? null;
                        if ($classCode && !empty($processedSessionLookup['by_class_code'][$classCode][$dateKey])) {
                            return false;
                        }
                        return true;
                    })
                    ->values();
            }

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
            if ($applyProcessingRanges || $applyProcessedLookup) {
                $inMonth = $inMonth && $occurrenceDetails->isNotEmpty();
            }

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
                $dateList = $paidDates->isNotEmpty()
                    ? $paidDates
                    : collect([$start ? $start->format('M d, Y') : '—']);
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
                    'date' => $dateList->implode(', '),
                    'dates' => $dateList->values()->toArray(),
                    'time' => $detail['time_range'] ?? ($start || $end
                        ? trim(($start ? $start->format('g:i A') : '') . ($end ? ' - ' . $end->format('g:i A') : ''))
                        : '—'),
                    'hours' => $detail['payroll_hours'] ?? $detail['hours'] ?? 0,
                    'scheduled_hours' => $detail['hours'] ?? 0,
                    'salary' => $detail['payroll_salary'] ?? $detail['summary_salary'] ?? $detail['display_salary'] ?? 0,
                    'rate' => $schedule->trainer_rate_per_hour ?? null,
                    'attendance' => $attendance->toArray(),
                    'recurrence' => $detail['recurring_label'] ?? '',
                    'status' => ($detail['has_attendance'] ?? false) ? 'Present' : 'Absent',
                ];
            })
            ->values()
            ->toArray();
    }

    private function formatProcessedSessionSeries($scheduleDetails): array
    {
        return collect($scheduleDetails ?? [])
            ->filter(function ($detail) {
                return ($detail['salary_eligible'] ?? false) && ($detail['in_month'] ?? false);
            })
            ->map(function ($detail) {
                $schedule = $detail['schedule'] ?? null;
                $paidDates = collect($detail['paid_dates'] ?? [])->filter();
                if ($paidDates->isEmpty()) {
                    return null;
                }

                $sessions = $paidDates->map(function ($date) {
                    try {
                        $parsed = Carbon::parse($date);
                        return [
                            'date' => $parsed->toDateString(),
                            'label' => $parsed->format('M j, Y'),
                            'day' => $parsed->day,
                            'status' => 'Completed (paid)',
                        ];
                    } catch (\Throwable $th) {
                        return [
                            'date' => $date,
                            'label' => $date,
                            'status' => 'Completed (paid)',
                        ];
                    }
                })->filter()->values();

                return [
                    'schedule_id' => $schedule->id ?? null,
                    'schedule_name' => $schedule->name ?? 'Class schedule',
                    'class_code' => $schedule->class_code ?? null,
                    'time_range' => $detail['time_range'] ?? null,
                    'hours_per_session' => $detail['hours_per_occurrence'] ?? $detail['hours'] ?? 0,
                    'payroll_hours' => $detail['payroll_hours'] ?? 0,
                    'payroll_salary' => $detail['payroll_salary'] ?? 0,
                    'sessions' => $sessions->toArray(),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function buildProcessedSessionLookup($runs): array
    {
        $byScheduleId = [];
        $byClassCode = [];

        foreach (collect($runs ?? []) as $run) {
            $seriesItems = $run->processed_session_series ?? [];
            foreach ($seriesItems as $series) {
                $scheduleId = $series['schedule_id'] ?? null;
                $classCode = $series['class_code'] ?? null;
                $sessions = $series['sessions'] ?? [];
                foreach ($sessions as $session) {
                    $date = $session['date'] ?? null;
                    if (!$date) {
                        continue;
                    }
                    try {
                        $dateKey = Carbon::parse($date)->toDateString();
                    } catch (\Throwable $th) {
                        $dateKey = (string) $date;
                    }

                    if ($scheduleId) {
                        $byScheduleId[$scheduleId][$dateKey] = true;
                    }
                    if ($classCode) {
                        $byClassCode[$classCode][$dateKey] = true;
                    }
                }
            }
        }

        return [
            'by_schedule_id' => $byScheduleId,
            'by_class_code' => $byClassCode,
        ];
    }

    private function buildProcessedAttendanceIdSet($runs): array
    {
        return collect($runs ?? [])
            ->flatMap(function ($run) {
                return $run->processed_attendance_ids ?? [];
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function buildProcessedMembershipPaymentIdSet($runs): array
    {
        return collect($runs ?? [])
            ->flatMap(function ($run) {
                $payments = $run->processed_membership_payments_approved ?? [];
                $items = is_array($payments) ? ($payments['items'] ?? []) : [];
                return collect($items)->pluck('id');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function buildProcessedAttendanceIdSetWithFallback($runs, $attendanceRecords, Carbon $targetMonth, array $settings): array
    {
        $processedIds = $this->buildProcessedAttendanceIdSet($runs);
        $ranges = $settings['processing_day_ranges'] ?? [];
        if (empty($ranges)) {
            return $processedIds;
        }

        $attendanceRecords = collect($attendanceRecords ?? []);

        foreach (collect($runs ?? []) as $run) {
            if (!empty($run->processed_attendance_ids)) {
                continue;
            }
            $processedAt = $run->processed_at ?? null;
            if (!$processedAt) {
                continue;
            }
            $day = (int) $processedAt->day;
            $runRanges = collect($ranges)
                ->filter(function ($range) use ($day) {
                    return (int) ($range['process'] ?? 0) === $day;
                })
                ->values()
                ->toArray();

            if (empty($runRanges)) {
                continue;
            }

            $runAttendances = $this->filterAttendancesForProcessingRanges($attendanceRecords, $targetMonth, $runRanges);
            $ids = collect($runAttendances)->pluck('id')->filter()->values()->toArray();
            if (!empty($ids)) {
                $processedIds = array_values(array_unique(array_merge($processedIds, $ids)));
            }
        }

        return $processedIds;
    }

    private function mergeProcessedSessionSeries($runs): array
    {
        $merged = [];
        $sessionIndex = [];

        foreach (collect($runs ?? []) as $run) {
            $seriesItems = $run->processed_session_series ?? [];
            foreach ($seriesItems as $series) {
                $scheduleId = $series['schedule_id'] ?? null;
                $classCode = $series['class_code'] ?? null;
                $key = $scheduleId ? ('id:' . $scheduleId) : ($classCode ? ('code:' . $classCode) : ('series:' . md5(json_encode($series))));

                if (!isset($merged[$key])) {
                    $merged[$key] = [
                        'schedule_id' => $scheduleId,
                        'schedule_name' => $series['schedule_name'] ?? null,
                        'class_code' => $classCode,
                        'time_range' => $series['time_range'] ?? null,
                        'sessions' => [],
                    ];
                    $sessionIndex[$key] = [];
                }

                foreach ($series['sessions'] ?? [] as $session) {
                    $date = $session['date'] ?? null;
                    if (!$date) {
                        continue;
                    }
                    $parsed = null;
                    try {
                        $parsed = Carbon::parse($date);
                        $dateKey = $parsed->toDateString();
                    } catch (\Throwable $th) {
                        $dateKey = (string) $date;
                    }

                    if (!empty($sessionIndex[$key][$dateKey])) {
                        continue;
                    }

                    $label = $session['label'] ?? ($parsed ? $parsed->format('M j, Y') : $dateKey);
                    $merged[$key]['sessions'][] = [
                        'date' => $dateKey,
                        'label' => $label,
                        'status' => $session['status'] ?? null,
                        'day' => $parsed ? $parsed->day : null,
                    ];
                    $sessionIndex[$key][$dateKey] = true;
                }
            }
        }

        foreach ($merged as $key => $series) {
            $merged[$key]['sessions'] = collect($series['sessions'] ?? [])
                ->sortBy(function ($session) {
                    $date = $session['date'] ?? null;
                    try {
                        return Carbon::parse($date)->getTimestamp();
                    } catch (\Throwable $th) {
                        return PHP_INT_MAX;
                    }
                })
                ->values()
                ->toArray();
        }

        return array_values($merged);
    }

    private function summarizeProcessedRuns($runs): array
    {
        $collection = collect($runs ?? []);
        if ($collection->isEmpty()) {
            return [
                'count' => 0,
                'hours' => 0,
                'gross' => 0,
                'net' => 0,
                'sss' => 0,
                'philhealth' => 0,
                'pagibig' => 0,
                'app_cut' => 0,
                'last_processed_at' => null,
            ];
        }

        $lastProcessedAt = $collection->pluck('processed_at')->filter()->sortDesc()->first();

        return [
            'count' => $collection->count(),
            'hours' => round($collection->sum(fn ($run) => (float) ($run->total_hours ?? 0)), 2),
            'gross' => round($collection->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
            'net' => round($collection->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
            'sss' => round($collection->sum(fn ($run) => (float) ($run->deduction_sss ?? 0)), 2),
            'philhealth' => round($collection->sum(fn ($run) => (float) ($run->deduction_philhealth ?? 0)), 2),
            'pagibig' => round($collection->sum(fn ($run) => (float) ($run->deduction_pagibig ?? 0)), 2),
            'app_cut' => round($collection->sum(fn ($run) => (float) ($run->deduction_app_cut ?? 0)), 2),
            'last_processed_at' => $lastProcessedAt,
        ];
    }

    private function formatProcessedSessionSeriesForPayslip($processedSeries): array
    {
        return collect($processedSeries ?? [])
            ->filter(function ($series) {
                return !empty($series['sessions']);
            })
            ->map(function ($series) {
                $sessions = collect($series['sessions'] ?? [])
                    ->map(function ($session) {
                        $date = $session['date'] ?? null;
                        $label = $session['label'] ?? null;
                        $parsed = null;
                        if ($date) {
                            try {
                                $parsed = Carbon::parse($date);
                            } catch (\Throwable $th) {
                                $parsed = null;
                            }
                        }

                        if (!$label && $parsed) {
                            $label = $parsed->format('M d, Y');
                        }

                        return [
                            'label' => $label,
                            'status' => $session['status'] ?? null,
                            'sort' => $parsed ? $parsed->getTimestamp() : PHP_INT_MAX,
                        ];
                    })
                    ->filter(function ($session) {
                        return !empty($session['label']);
                    })
                    ->sortBy('sort')
                    ->values();

                $dateLabels = $sessions->pluck('label')->filter()->values();
                $dateList = $dateLabels->isNotEmpty() ? $dateLabels : collect(['—']);
                $timeRange = $series['time_range'] ?? null;
                $attendance = $sessions->map(function ($session) use ($timeRange) {
                    $status = trim((string) ($session['status'] ?? ''));
                    if ($status !== '') {
                        return $status;
                    }

                    $range = trim((string) ($timeRange ?? ''));
                    if ($range !== '') {
                        return $range;
                    }

                    return 'Attendance recorded';
                })->filter()->values();

                $hours = (float) ($series['payroll_hours'] ?? 0);
                if ($hours <= 0) {
                    $hoursPerSession = (float) ($series['hours_per_session'] ?? 0);
                    if ($hoursPerSession > 0 && $sessions->isNotEmpty()) {
                        $hours = $hoursPerSession * $sessions->count();
                    }
                }

                $salaryValue = (float) ($series['payroll_salary'] ?? 0);
                $rate = null;
                $seriesRate = $series['rate'] ?? ($series['trainer_rate_per_hour'] ?? null);
                if (is_numeric($seriesRate)) {
                    $rate = (float) $seriesRate;
                }
                if (is_null($rate) && $hours > 0 && $salaryValue > 0) {
                    $rate = round($salaryValue / $hours, 2);
                }

                $attendancePayload = $attendance->isNotEmpty()
                    ? $attendance->toArray()
                    : ($timeRange ? [$timeRange] : ['Attendance recorded']);

                return [
                    'title' => $series['schedule_name'] ?? 'Class schedule',
                    'code' => $series['class_code'] ?? null,
                    'date' => $dateList->implode(', '),
                    'dates' => $dateList->values()->toArray(),
                    'time' => $timeRange ?? '—',
                    'hours' => $hours,
                    'salary' => $salaryValue,
                    'rate' => $rate,
                    'attendance' => $attendancePayload,
                    'recurrence' => '',
                    'status' => 'Present',
                ];
            })
            ->values()
            ->toArray();
    }

    private function paginateCollection($items, int $perPage, string $pageName, Request $request): LengthAwarePaginator
    {
        $collection = collect($items ?? []);
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $pageItems = $collection->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator($pageItems, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]))->withQueryString();
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('member_name', ''));
        if ($search === '') {
            $search = trim((string) $request->input('search', ''));
        }
        $period = $request->input('period_month');
        $processedFromInput = $request->input('processed_from');
        $processedToInput = $request->input('processed_to');
        $roleFilter = $request->input('role', 'all');
        if (!in_array($roleFilter, ['all', 'staff', 'trainer'], true)) {
            $roleFilter = 'all';
        }
        $releaseStatus = $request->input('release_status', 'all');
        if (!in_array($releaseStatus, ['all', 'released', 'pending'], true)) {
            $releaseStatus = 'all';
        }
        $sortProcessed = strtolower((string) $request->input('sort_processed', ''));
        $sortReleased = strtolower((string) $request->input('sort_released', ''));
        $sortProcessed = in_array($sortProcessed, ['asc', 'desc'], true) ? $sortProcessed : null;
        $sortReleased = in_array($sortReleased, ['asc', 'desc'], true) ? $sortReleased : null;
        $deductionSettings = $this->currentDeductionSettings();
        $appCutRate = max((float) $request->input('app_cut_rate', $deductionSettings['app_cut_rate']), 0);

        $processedFrom = null;
        $processedTo = null;

        try {
            if (!empty($processedFromInput)) {
                $processedFrom = Carbon::parse($processedFromInput)->startOfDay();
            }
        } catch (\Throwable $th) {
            $processedFrom = null;
        }

        try {
            if (!empty($processedToInput)) {
                $processedTo = Carbon::parse($processedToInput)->endOfDay();
            }
        } catch (\Throwable $th) {
            $processedTo = null;
        }

        $baseQuery = PayrollRun::with(['user', 'processedByUser', 'releasedByUser'])
            ->when($search, function ($query) use ($search) {
                $like = '%' . $search . '%';
                $integerSearch = ctype_digit($search) ? (int) $search : null;
                $parsedDate = null;
                try {
                    $parsedDate = Carbon::parse($search)->toDateString();
                } catch (\Throwable $th) {
                    $parsedDate = null;
                }

                $query->where(function ($subQuery) use ($like, $integerSearch, $parsedDate) {
                    $subQuery
                        ->whereHas('user', function ($userQuery) use ($like) {
                            $userQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                                ->orWhere('email', 'like', $like)
                                ->orWhere('user_code', 'like', $like);
                        })
                        ->orWhere('period_month', 'like', $like)
                        ->orWhere('processed_at', 'like', $like)
                        ->orWhere('created_at', 'like', $like)
                        ->orWhere('updated_at', 'like', $like);

                    if (!is_null($integerSearch)) {
                        $subQuery->orWhere('id', $integerSearch)
                            ->orWhere('user_id', $integerSearch);
                    }

                    if ($parsedDate) {
                        $subQuery->orWhereDate('processed_at', $parsedDate)
                            ->orWhereDate('created_at', $parsedDate)
                            ->orWhereDate('updated_at', $parsedDate);
                    }
                });
            })
            ->when($period, function ($query, $period) {
                $query->where('period_month', $period);
            })
            ->when($roleFilter !== 'all', function ($query) use ($roleFilter) {
                $roleId = $roleFilter === 'trainer' ? 5 : 2;
                $query->whereHas('user', function ($userQuery) use ($roleId) {
                    $userQuery->where('role_id', $roleId);
                });
            })
            ->when($releaseStatus !== 'all', function ($query) use ($releaseStatus) {
                if ($releaseStatus === 'released') {
                    $query->whereNotNull('released_at');
                } elseif ($releaseStatus === 'pending') {
                    $query->whereNull('released_at');
                }
            })
            ->when($processedFrom || $processedTo, function ($query) use ($processedFrom, $processedTo) {
                $query->where(function ($dateQuery) use ($processedFrom, $processedTo) {
                    if ($processedFrom) {
                        $dateQuery->where(function ($sub) use ($processedFrom) {
                            $sub->whereNotNull('processed_at')->where('processed_at', '>=', $processedFrom)
                                ->orWhere(function ($fallback) use ($processedFrom) {
                                    $fallback->whereNull('processed_at')->where('created_at', '>=', $processedFrom);
                                });
                        });
                    }

                    if ($processedTo) {
                        $dateQuery->where(function ($sub) use ($processedTo) {
                            $sub->whereNotNull('processed_at')->where('processed_at', '<=', $processedTo)
                                ->orWhere(function ($fallback) use ($processedTo) {
                                    $fallback->whereNull('processed_at')->where('created_at', '<=', $processedTo);
                                });
                        });
                    }
                });
            });

        $applyOrdering = function ($query) use ($sortProcessed, $sortReleased) {
            if ($sortProcessed) {
                $query->orderByRaw("COALESCE(processed_at, created_at) {$sortProcessed}");
            }
            if ($sortReleased) {
                $query->orderBy('released_at', $sortReleased);
            }
            if (!$sortProcessed && !$sortReleased) {
                $query->orderByDesc('processed_at');
            }
            $query->orderByDesc('id');
        };

        $printAllRuns = (clone $baseQuery);
        $applyOrdering($printAllRuns);
        $printAllRuns = $printAllRuns->get();

        $runs = (clone $baseQuery);
        $applyOrdering($runs);
        $runs = $runs->paginate(10)->withQueryString();

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
                $processedAttendanceIds = $run->processed_attendance_ids ?? [];
                if (is_array($processedAttendanceIds) && !empty($processedAttendanceIds)) {
                    $attendanceRecords = Attendance::whereIn('id', $processedAttendanceIds)
                        ->orderBy('clockin_at')
                        ->get();
                } else {
                    $attendanceRecords = Attendance::where('user_id', $user->id)
                        ->where('is_archive', 0)
                        ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                            $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                                ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                                ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
                        })
                        ->orderBy('clockin_at')
                        ->get();

                    $fallbackIds = $this->buildProcessedAttendanceIdSetWithFallback(
                        [$run],
                        $attendanceRecords,
                        $targetMonth,
                        $deductionSettings
                    );
                    if (!empty($fallbackIds)) {
                        $attendanceRecords = $attendanceRecords
                            ->filter(function ($attendance) use ($fallbackIds) {
                                return in_array($attendance->id, $fallbackIds, true);
                            })
                            ->values();
                    }
                }

                $entryDateBounds = $attendanceRecords->reduce(function ($carry, $attendance) {
                    $times = collect([$attendance->clockin_at ?? null, $attendance->clockout_at ?? null])
                        ->filter()
                        ->map(fn ($dt) => $dt instanceof Carbon ? $dt : Carbon::parse($dt));

                    if ($times->isEmpty()) {
                        return $carry;
                    }

                    $min = $times->min();
                    $max = $times->max();

                    if (is_null($carry['start']) || $min->lt($carry['start'])) {
                        $carry['start'] = $min;
                    }
                    if (is_null($carry['end']) || $max->gt($carry['end'])) {
                        $carry['end'] = $max;
                    }

                    return $carry;
                }, ['start' => null, 'end' => null]);

                $entryStart = $entryDateBounds['start']
                    ? $entryDateBounds['start']->copy()
                    : $startOfMonth;
                $entryEnd = $entryDateBounds['end']
                    ? $entryDateBounds['end']->copy()
                    : $endOfMonth;

                $entries = $attendanceRecords
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

                $staffCodeKey = strtolower(trim($user->user_code ?? ''));
                $membershipPayments = collect();
                if ($staffCodeKey !== '') {
                    $membershipPayments = MembershipPayment::where('isapproved', 1)
                        ->where('is_archive', 0)
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                        ->get()
                        ->filter(function ($payment) use ($staffCodeKey, $entryStart, $entryEnd) {
                            $createdBy = strtolower(trim($payment->created_by ?? ''));
                            if ($createdBy !== $staffCodeKey) {
                                return false;
                            }

                            $approvedAt = $payment->updated_at ?: $payment->created_at;
                            if (!$approvedAt) {
                                return false;
                            }

                            $approved = $approvedAt instanceof Carbon ? $approvedAt : Carbon::parse($approvedAt);
                            return $approved->between($entryStart, $entryEnd);
                        })
                        ->map(function ($payment) {
                            $member = $payment->user;
                            $membership = $payment->membership;
                            return [
                                'id' => $payment->id,
                                'member_name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: '—',
                                'member_code' => $member->user_code ?? '—',
                                'membership' => $membership->name ?? '—',
                                'currency' => $membership->currency ?? 'PHP',
                                'price' => (float) ($membership->price ?? 0),
                                'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—',
                                'expiration_at' => $payment->expiration_at ? Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—',
                                'updated_at' => $payment->updated_at ? $payment->updated_at->format('M d, Y g:i A') : '—',
                            ];
                        })
                        ->values();
                }

                $mpTotal = $membershipPayments->sum(fn ($item) => $item['price'] ?? 0);
                $firstMembershipPayment = $membershipPayments->first();
                $mpCurrency = is_array($firstMembershipPayment) && array_key_exists('currency', $firstMembershipPayment)
                    ? $firstMembershipPayment['currency']
                    : 'PHP';

                $storedMembershipPayments = $run->processed_membership_payments_approved;
                if (is_array($storedMembershipPayments) && !empty($storedMembershipPayments)) {
                    $storedItems = collect($storedMembershipPayments['items'] ?? []);
                    $storedFirstItem = $storedItems->first();

                    $membershipPaymentPayload = [
                        'count' => (int) ($storedMembershipPayments['count'] ?? $storedItems->count()),
                        'total' => round(
                            (float) ($storedMembershipPayments['total'] ?? $storedItems->sum(fn ($item) => $item['price'] ?? 0)),
                            2
                        ),
                        'currency' => $storedMembershipPayments['currency']
                            ?? (is_array($storedFirstItem) && array_key_exists('currency', $storedFirstItem) ? $storedFirstItem['currency'] : 'PHP'),
                        'items' => $storedItems->values(),
                    ];
                } else {
                    $membershipPaymentPayload = [
                        'count' => $membershipPayments->count(),
                        'total' => round($mpTotal, 2),
                        'currency' => $mpCurrency,
                        'items' => $membershipPayments,
                    ];
                }

                $payslipDetails[$run->id] = [
                    'entries' => $entries,
                    'membership_payments' => $membershipPaymentPayload,
                    'assignments' => [],
                ];
            } elseif ($user->role_id === 5) {
                $processedSeries = collect($run->processed_session_series ?? []);
                if ($processedSeries->isNotEmpty()) {
                    $assignments = $this->formatProcessedSessionSeriesForPayslip($processedSeries);
                } else {
                    $scheduleDetails = $this->buildTrainerScheduleDetails($user, $startOfMonth, $endOfMonth);
                    $assignments = $this->formatTrainerAssignmentsForPayslip($scheduleDetails);
                }

                $payslipDetails[$run->id] = [
                    'entries' => [],
                    'membership_payments' => [
                        'count' => 0,
                        'total' => 0,
                        'currency' => 'PHP',
                        'items' => collect(),
                    ],
                    'assignments' => $assignments,
                ];
            }
        }

        $appCutTotal = $printAllRuns->sum(function ($run) use ($appCutRate) {
            $stored = $run->deduction_app_cut ?? null;
            if (!is_null($stored)) {
                return (float) $stored;
            }

            $gross = (float) ($run->gross_pay ?? 0);
            return round($gross * ($appCutRate / 100), 2);
        });

        $totals = [
            'gross' => round($printAllRuns->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
            'net' => round($printAllRuns->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
            'sss' => round($printAllRuns->sum(fn ($run) => (float) ($run->deduction_sss ?? 0)), 2),
            'philhealth' => round($printAllRuns->sum(fn ($run) => (float) ($run->deduction_philhealth ?? 0)), 2),
            'pagibig' => round($printAllRuns->sum(fn ($run) => (float) ($run->deduction_pagibig ?? 0)), 2),
            'app_cut' => round($appCutTotal, 2),
        ];

        return view('admin.payrolls.index', [
            'runs' => $runs,
            'printAllRuns' => $printAllRuns,
            'deductionSettings' => $deductionSettings,
            'payslipDetails' => $payslipDetails,
            'totals' => $totals,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function report()
    {
        $deductionSettings = $this->currentDeductionSettings();
        $appCutRate = max((float) ($deductionSettings['app_cut_rate'] ?? 0), 0);

        $runs = PayrollRun::with('user')
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->get();

        $appCutTotal = $runs->sum(function ($run) use ($appCutRate) {
            $stored = $run->deduction_app_cut ?? null;
            if (!is_null($stored)) {
                return (float) $stored;
            }

            $gross = (float) ($run->gross_pay ?? 0);
            return round($gross * ($appCutRate / 100), 2);
        });

        $totals = [
            'gross' => round($runs->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
            'net' => round($runs->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
            'sss' => round($runs->sum(fn ($run) => (float) ($run->deduction_sss ?? 0)), 2),
            'philhealth' => round($runs->sum(fn ($run) => (float) ($run->deduction_philhealth ?? 0)), 2),
            'pagibig' => round($runs->sum(fn ($run) => (float) ($run->deduction_pagibig ?? 0)), 2),
            'app_cut' => round($appCutTotal, 2),
        ];

        return view('admin.payrolls.report', [
            'totals' => $totals,
            'runsCount' => $runs->count(),
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
        $activeProcessingRanges = $this->getActiveProcessingRanges($deductionSettings, Carbon::now());

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

        $staffPaymentLookup = MembershipPayment::where('isapproved', 1)
            ->where('is_archive', 0)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->with(['membership', 'user'])
            ->get()
            ->groupBy(function ($payment) {
                return strtolower(trim($payment->created_by ?? ''));
            });

        $attendanceByUser = Attendance::whereIn('user_id', $staffIds)
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
            ->orderByDesc('processed_at')
            ->get()
            ->groupBy('user_id');

        $summaries = $staffMembers->map(function ($staff) use ($attendanceByUser, $deductionSettings, $staffPaymentLookup, $startOfMonth, $endOfMonth, $targetMonth, $activeProcessingRanges, $processedRuns, $month) {
            $staffRuns = $processedRuns->get($staff->id, collect());
            $attendanceRecordsAll = $attendanceByUser->get($staff->id) ?? collect();
            $processedAttendanceIds = $this->buildProcessedAttendanceIdSetWithFallback($staffRuns, $attendanceRecordsAll, $targetMonth, $deductionSettings);
            $processedMembershipIds = $this->buildProcessedMembershipPaymentIdSet($staffRuns);
            $attendanceRecords = $this->filterAttendancesForProcessingRanges(
                $attendanceRecordsAll,
                $targetMonth,
                $activeProcessingRanges
            );
            if (!empty($processedAttendanceIds)) {
                $attendanceRecords = collect($attendanceRecords)
                    ->filter(function ($attendance) use ($processedAttendanceIds) {
                        return !in_array($attendance->id, $processedAttendanceIds, true);
                    })
                    ->values();
            }
            $entries = $attendanceRecords->map(function ($attendance) use ($staff) {
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

            $deductions = $this->calculateDeductions($gross, $deductionSettings, false, $staff);
            $net = max($gross - $deductions['total'], 0);

            $entryDateBounds = $entries->reduce(function ($carry, $entry) {
                $times = collect([$entry['clockin_at'] ?? null, $entry['clockout_at'] ?? null])
                    ->filter()
                    ->map(fn ($dt) => $dt instanceof Carbon ? $dt : Carbon::parse($dt));
                if ($times->isEmpty()) {
                    return $carry;
                }
                $min = $times->min();
                $max = $times->max();
                if (is_null($carry['start']) || $min->lt($carry['start'])) {
                    $carry['start'] = $min;
                }
                if (is_null($carry['end']) || $max->gt($carry['end'])) {
                    $carry['end'] = $max;
                }
                return $carry;
            }, ['start' => null, 'end' => null]);

            $entryStart = $entryDateBounds['start']
                ? $entryDateBounds['start']->copy()
                : $startOfMonth;
            $entryEnd = $entryDateBounds['end']
                ? $entryDateBounds['end']->copy()
                : $endOfMonth;

            $staffCodeKey = strtolower(trim($staff->user_code ?? ''));
            $matchedPayments = $staffCodeKey !== '' ? ($staffPaymentLookup->get($staffCodeKey) ?? collect()) : collect();
            $membershipPaymentItems = $matchedPayments
                ->filter(function ($payment) use ($entryStart, $entryEnd, $processedMembershipIds) {
                    if (!empty($processedMembershipIds) && in_array($payment->id, $processedMembershipIds, true)) {
                        return false;
                    }
                    $approvedAt = $payment->updated_at ?: $payment->created_at;
                    if (!$approvedAt) {
                        return false;
                    }
                    $approved = $approvedAt instanceof Carbon ? $approvedAt : Carbon::parse($approvedAt);
                    return $approved->between($entryStart, $entryEnd);
                })
                ->map(function ($payment) {
                $member = $payment->user;
                $membership = $payment->membership;
                return [
                    'id' => $payment->id,
                    'member_name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: '—',
                    'member_code' => $member->user_code ?? '—',
                    'membership' => $membership->name ?? '—',
                    'currency' => $membership->currency ?? 'PHP',
                    'price' => (float) ($membership->price ?? 0),
                    'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—',
                    'expiration_at' => $payment->expiration_at ? Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—',
                    'updated_at' => $payment->updated_at ? $payment->updated_at->format('M d, Y g:i A') : '—',
                ];
            })->values();

            $membershipPaymentTotal = $membershipPaymentItems->sum(fn ($item) => $item['price'] ?? 0);
            $membershipPaymentCurrency = $membershipPaymentItems->first()['currency'] ?? 'PHP';
            $latestProcessedRun = $staffRuns->sortByDesc('processed_at')->first();
            $processedTotals = $this->summarizeProcessedRuns($staffRuns);

            return [
                'staff' => $staff,
                'entries' => $entries,
                'total_hours' => $totalHours,
                'gross_pay' => $gross,
                'net_pay' => $net,
                'deductions' => $deductions,
                'pending_entries' => $entries->where('status', 'pending')->count(),
                'completed_entries' => $entries->where('status', 'complete')->count(),
                'membership_payments' => [
                    'count' => $membershipPaymentItems->count(),
                    'total' => round($membershipPaymentTotal, 2),
                    'currency' => $membershipPaymentCurrency,
                    'items' => $membershipPaymentItems,
                ],
                'processed_run' => $latestProcessedRun,
                'processed_runs' => $staffRuns,
                'processed_totals' => $processedTotals,
                'period_month' => $month,
            ];
        })->filter(fn ($summary) => $summary['entries']->count() > 0)->values();

        $stats = [
            'staff_count' => $summaries->count(),
            'pending_entries' => $summaries->sum(fn ($summary) => $summary['pending_entries']),
            'total_hours' => $summaries->sum(fn ($summary) => $summary['total_hours']),
            'projected_net' => $summaries->sum(fn ($summary) => $summary['net_pay']),
        ];
        $staffSummariesDisplay = $summaries
            ->filter(fn ($summary) => (float) ($summary['total_hours'] ?? 0) > 0)
            ->values();
        $staffSummariesPaginated = $this->paginateCollection($staffSummariesDisplay, 10, 'staff_page', $request);

        $trainers = User::where('role_id', 5)
            ->where('is_archive', 0)
            ->with(['trainerSchedules.activeUserSchedules.user'])
            ->get();

        $trainerProcessedRuns = PayrollRun::whereIn('user_id', $trainers->pluck('id'))
            ->where('period_month', $month)
            ->orderByDesc('processed_at')
            ->get()
            ->groupBy('user_id');

        $trainerAssignments = $trainers
            ->map(function ($trainer) use ($startOfMonth, $endOfMonth, $trainerProcessedRuns, $deductionSettings, $activeProcessingRanges) {
                $processedRuns = $trainerProcessedRuns->get($trainer->id, collect());
                $processedLookup = $this->buildProcessedSessionLookup($processedRuns);
                $scheduleDetails = $this->buildTrainerScheduleDetails($trainer, $startOfMonth, $endOfMonth, $activeProcessingRanges, $processedLookup);
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
                $deductions = $this->calculateDeductions($gross, $deductionSettings, true, $trainer);
                $net = max($gross - $deductions['total'], 0);

                $latestProcessedRun = $processedRuns->sortByDesc('processed_at')->first();
                $processedTotals = $this->summarizeProcessedRuns($processedRuns);
                $processedSeries = $this->mergeProcessedSessionSeries($processedRuns);

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
                    'processed_run' => $latestProcessedRun,
                    'processed_runs' => $processedRuns,
                    'processed_totals' => $processedTotals,
                    'processed_series' => $processedSeries,
                ];
            })
            ->filter(fn ($assignment) => $assignment['assignments_count'] > 0)
            ->values();
        $trainerAssignmentsDisplay = $trainerAssignments
            ->filter(fn ($assignment) => (float) ($assignment['total_hours'] ?? 0) > 0)
            ->values();
        $trainerStats = [
            'trainer_count' => $trainerAssignmentsDisplay->count(),
            'payable_classes' => $trainerAssignmentsDisplay->sum(function ($assignment) {
                return (int) ($assignment['payable_assignments_count'] ?? 0);
            }),
            'total_hours' => $trainerAssignmentsDisplay->sum(function ($assignment) {
                return (float) ($assignment['total_hours'] ?? 0);
            }),
            'projected_net' => $trainerAssignmentsDisplay->sum(function ($assignment) {
                return (float) ($assignment['net_pay'] ?? 0);
            }),
        ];
        $trainerAssignmentsPaginated = $this->paginateCollection($trainerAssignmentsDisplay, 10, 'trainer_page', $request);

        return view('admin.payrolls.process', [
            'summaries' => $staffSummariesPaginated,
            'summariesAll' => $summaries,
            'stats' => $stats,
            'search' => $search,
            'month' => $month,
            'monthLabel' => $targetMonth->format('F Y'),
            'trainerAssignments' => $trainerAssignmentsPaginated,
            'trainerStats' => $trainerStats,
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
        $existingRuns = PayrollRun::where('user_id', $staff->id)
            ->where('period_month', $request->month)
            ->get();
        $processedMembershipIds = $this->buildProcessedMembershipPaymentIdSet($existingRuns);

        $attendanceRecords = Attendance::where('user_id', $staff->id)
            ->where('is_archive', 0)
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('clockin_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('clockout_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })
            ->orderBy('clockin_at')
            ->get();
        $processedAttendanceIds = $this->buildProcessedAttendanceIdSetWithFallback(
            $existingRuns,
            $attendanceRecords,
            $targetMonth,
            $deductionSettings
        );
        $activeProcessingRanges = $this->getActiveProcessingRanges($deductionSettings, Carbon::now());
        $attendanceRecords = $this->filterAttendancesForProcessingRanges($attendanceRecords, $targetMonth, $activeProcessingRanges);
        if (!empty($processedAttendanceIds)) {
            $attendanceRecords = collect($attendanceRecords)
                ->filter(function ($attendance) use ($processedAttendanceIds) {
                    return !in_array($attendance->id, $processedAttendanceIds, true);
                })
                ->values();
        }

        $entryDateBounds = $attendanceRecords->reduce(function ($carry, $attendance) {
            $times = collect([$attendance->clockin_at ?? null, $attendance->clockout_at ?? null])
                ->filter()
                ->map(fn ($dt) => $dt instanceof Carbon ? $dt : Carbon::parse($dt));

            if ($times->isEmpty()) {
                return $carry;
            }

            $min = $times->min();
            $max = $times->max();

            if (is_null($carry['start']) || $min->lt($carry['start'])) {
                $carry['start'] = $min;
            }
            if (is_null($carry['end']) || $max->gt($carry['end'])) {
                $carry['end'] = $max;
            }

            return $carry;
        }, ['start' => null, 'end' => null]);

        $entryStart = $entryDateBounds['start'] ? $entryDateBounds['start']->copy() : $startOfMonth;
        $entryEnd = $entryDateBounds['end'] ? $entryDateBounds['end']->copy() : $endOfMonth;

        $entries = $attendanceRecords->map(function ($attendance) use ($staff) {
            $clockIn = $attendance->clockin_at ? Carbon::parse($attendance->clockin_at) : null;
            $clockOut = $attendance->clockout_at ? Carbon::parse($attendance->clockout_at) : null;

            $hours = null;
            if ($clockIn && $clockOut && $clockOut->greaterThan($clockIn)) {
                $hours = round($clockOut->diffInMinutes($clockIn) / 60, 2);
            }

            $amount = $hours ? round($hours * (float) ($staff->rate_per_hour ?? 0), 2) : 0;

            return [
                'id' => $attendance->id,
                'hours' => $hours ?? 0,
                'amount' => $amount,
            ];
        });

        if ($entries->isEmpty()) {
            return redirect()->back()->with('error', 'No payroll entries found for this staff and month.');
        }

        $totalHours = $entries->sum('hours');
        $gross = round($entries->sum('amount'), 2);

        $staffCodeKey = strtolower(trim($staff->user_code ?? ''));
        $membershipPayments = collect();
        if ($staffCodeKey !== '') {
            $membershipPayments = MembershipPayment::where('isapproved', 1)
                ->where('is_archive', 0)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->with(['membership', 'user'])
                ->get()
                ->filter(function ($payment) use ($staffCodeKey, $entryStart, $entryEnd, $processedMembershipIds) {
                    if (!empty($processedMembershipIds) && in_array($payment->id, $processedMembershipIds, true)) {
                        return false;
                    }
                    $createdBy = strtolower(trim($payment->created_by ?? ''));
                    if ($createdBy !== $staffCodeKey) {
                        return false;
                    }

                    $approvedAt = $payment->updated_at ?: $payment->created_at;
                    if (!$approvedAt) {
                        return false;
                    }

                    $approved = $approvedAt instanceof Carbon ? $approvedAt : Carbon::parse($approvedAt);
                    return $approved->between($entryStart, $entryEnd);
                })
                ->map(function ($payment) {
                    $member = $payment->user;
                    $membership = $payment->membership;
                    return [
                        'id' => $payment->id,
                        'member_name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: '—',
                        'member_code' => $member->user_code ?? '—',
                        'membership' => $membership->name ?? '—',
                        'currency' => $membership->currency ?? 'PHP',
                        'price' => (float) ($membership->price ?? 0),
                        'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—',
                        'expiration_at' => $payment->expiration_at ? Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—',
                        'updated_at' => $payment->updated_at ? $payment->updated_at->format('M d, Y g:i A') : '—',
                    ];
                })
                ->values();
        }

        $firstMembershipPayment = $membershipPayments->first();
        $processedMembershipPayments = [
            'count' => $membershipPayments->count(),
            'total' => round($membershipPayments->sum(fn ($item) => $item['price'] ?? 0), 2),
            'currency' => is_array($firstMembershipPayment) && array_key_exists('currency', $firstMembershipPayment)
                ? $firstMembershipPayment['currency']
                : 'PHP',
            'items' => $membershipPayments,
        ];

        $deductions = $this->calculateDeductions($gross, $deductionSettings, false, $staff);

        $net = max($gross - $deductions['total'], 0);

        $processedAttendanceIdsForRun = $entries
            ->filter(function ($entry) {
                return (float) ($entry['hours'] ?? 0) > 0;
            })
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $runPayload = [
            'user_id' => $staff->id,
            'period_month' => $request->month,
            'total_hours' => $totalHours,
            'gross_pay' => $gross,
            'net_pay' => $net,
            'deduction_sss' => $deductions['sss'],
            'deduction_philhealth' => $deductions['philhealth'],
            'deduction_pagibig' => $deductions['pagibig'],
            'deduction_app_cut' => $deductions['app_cut'],
            'processed_by' => Auth::id(),
            'processed_at' => Carbon::now(),
            'released_at' => null,
            'released_by' => null,
            'processed_session_series' => null,
            'processed_membership_payments_approved' => $processedMembershipPayments,
        ];

        if (Schema::hasColumn('payroll_runs', 'processed_attendance_ids')) {
            $runPayload['processed_attendance_ids'] = $processedAttendanceIdsForRun;
        }

        PayrollRun::create($runPayload);

        return redirect()->route('admin.payrolls.index')->with('success', 'Payroll processed and saved for ' . trim($staff->first_name . ' ' . $staff->last_name));
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

        try {
            $targetMonth = Carbon::createFromFormat('Y-m', $request->month);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Invalid payroll month provided.');
        }

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $activeProcessingRanges = $this->getActiveProcessingRanges($deductionSettings, Carbon::now());
        $existingRuns = PayrollRun::where('user_id', $trainer->id)
            ->where('period_month', $request->month)
            ->get();
        $processedLookup = $this->buildProcessedSessionLookup($existingRuns);
        $scheduleDetails = $this->buildTrainerScheduleDetails($trainer, $startOfMonth, $endOfMonth, $activeProcessingRanges, $processedLookup);
        $eligibleSchedules = collect($scheduleDetails)
            ->filter(function ($detail) {
                return ($detail['salary_eligible'] ?? false)
                    && ($detail['in_month'] ?? false)
                    && (int) ($detail['past_paid_count'] ?? 0) > 0;
            });

        if ($eligibleSchedules->isEmpty()) {
            return redirect()->back()->with('error', 'No payroll-eligible trainer assignments with attendance found for this month.');
        }

        $totalHours = $eligibleSchedules->sum(fn ($detail) => (float) ($detail['payroll_hours'] ?? 0));
        $gross = round($eligibleSchedules->sum(fn ($detail) => (float) ($detail['payroll_salary'] ?? 0)), 2);

        $deductions = $this->calculateDeductions($gross, $deductionSettings, true, $trainer);

        $net = max($gross - $deductions['total'], 0);

        $processedSessionSeries = $this->formatProcessedSessionSeries($scheduleDetails);

        PayrollRun::create([
            'user_id' => $trainer->id,
            'period_month' => $request->month,
            'total_hours' => $totalHours,
            'gross_pay' => $gross,
            'net_pay' => $net,
            'deduction_sss' => $deductions['sss'],
            'deduction_philhealth' => $deductions['philhealth'],
            'deduction_pagibig' => $deductions['pagibig'],
            'deduction_app_cut' => $deductions['app_cut'],
            'processed_by' => Auth::id(),
            'processed_at' => Carbon::now(),
            'released_at' => null,
            'released_by' => null,
            'processed_session_series' => $processedSessionSeries,
            'processed_membership_payments_approved' => null,
        ]);

        $trainerName = trim($trainer->first_name . ' ' . $trainer->last_name);

        return redirect()->route('admin.payrolls.index')->with('success', 'Trainer payroll processed and saved for ' . ($trainerName !== '' ? $trainerName : 'trainer'));
    }

    public function release(PayrollRun $run)
    {
        if ($run->released_at) {
            return redirect()->back()->with('error', 'This payroll run has already been released.');
        }

        $run->released_at = Carbon::now();
        $run->released_by = Auth::id();
        $run->save();

        $staff = $run->user;
        $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'staff member';

        return redirect()->back()->with('success', 'Cash released for ' . ($name !== '' ? $name : 'staff member') . '.');
    }
    
    public function view($id)
    {
        $data = Payroll::findOrFail($id);

        return view('admin.payrolls.view', compact('data'));
    }
    
    public function clockin(Request $request)
    {
        $user = $request->user();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('is_archive', 0)
            ->whereDate('clockin_at', now()->toDateString())
            ->orderByDesc('clockin_at')
            ->first();
    
        if (!$attendance || $attendance->clockout_at) {
            $attendance = new Attendance();
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

        $attendance = Attendance::where('user_id', $user->id)
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
