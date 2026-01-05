<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Feedback;

use App\Models\Membership;
use App\Models\Schedule;
use App\Models\MembershipPayment;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $weekdayLookup = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];
        $weekdayIndex = [
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6,
        ];
        $weekdayFromIndex = array_flip($weekdayIndex);

        $parseDate = function ($value) {
            try {
                return $value ? Carbon::parse($value) : null;
            } catch (\Throwable $th) {
                return null;
            }
        };

        $buildUpcomingOccurrences = function (Schedule $schedule) use ($now, $weekdayIndex, $weekdayFromIndex, $parseDate) {
            $seriesStart = $parseDate($schedule->series_start_date) ?? $parseDate($schedule->class_start_date);
            $seriesEnd = $parseDate($schedule->series_end_date) ?? $parseDate($schedule->class_end_date);
            $seriesEnd = $seriesEnd ? $seriesEnd->copy()->endOfDay() : null;

            if ($seriesStart && $seriesEnd && $seriesEnd->lt($seriesStart)) {
                return [collect(), 0];
            }

            $startTimeString = $schedule->class_start_time
                ?? ($schedule->class_start_date ? $parseDate($schedule->class_start_date)?->format('H:i:s') : null);
            $endTimeString = $schedule->class_end_time
                ?? ($schedule->class_end_date ? $parseDate($schedule->class_end_date)?->format('H:i:s') : null);

            $dayKeysRaw = $schedule->recurring_days;
            $dayKeys = is_array($dayKeysRaw) ? $dayKeysRaw : json_decode($dayKeysRaw ?? '[]', true);
            $recurringDays = collect($dayKeys)->filter(fn ($d) => array_key_exists($d, $weekdayIndex))->values();

            $occurrences = collect();
            $totalCount = 0;

            if ($recurringDays->isEmpty()) {
                $firstStart = $parseDate($schedule->class_start_date) ?? $seriesStart;
                $firstEnd = $parseDate($schedule->class_end_date);
                if ($firstStart && $firstStart->gte($now)) {
                    $occurrences->push([
                        'start' => $firstStart,
                        'end' => $firstEnd,
                    ]);
                    $totalCount = 1;
                }
            } else {
                $cursor = $seriesStart ? $seriesStart->copy()->startOfDay() : $now->copy()->startOfDay();
                if ($cursor->lt($now->copy()->startOfDay())) {
                    $cursor = $now->copy()->startOfDay();
                }

                $endBoundary = $seriesEnd ?: $cursor->copy()->addMonths(3);

                for (; $cursor->lte($endBoundary); $cursor->addDay()) {
                    $dayKey = $weekdayFromIndex[$cursor->dayOfWeek] ?? null;
                    if (!$dayKey || !$recurringDays->contains($dayKey)) {
                        continue;
                    }

                    $start = $startTimeString
                        ? Carbon::parse($cursor->format('Y-m-d') . ' ' . $startTimeString)
                        : $cursor->copy();

                    if ($start->lt($now)) {
                        continue;
                    }

                    $end = $endTimeString
                        ? Carbon::parse($cursor->format('Y-m-d') . ' ' . $endTimeString)
                        : null;

                    $totalCount++;

                    if ($occurrences->count() < 3) {
                        $occurrences->push([
                            'start' => $start,
                            'end' => $end,
                        ]);
                    }
                }
            }

            return [$occurrences, $totalCount];
        };

        $gym_members_count = User::where('role_id', 3)->count();
        $staffs_count = User::where('role_id', 2)->count();
        $feedbacks_count = Feedback::count();
        $memberships_count = Membership::count();
        $classes_count = Schedule::count();
        $membership_payment_count = MembershipPayment::where('isapproved', 0)->count();
        $upcomingClasses = Schedule::where('is_archieve', 0)
            ->where('istrainerapproved', 1)
            ->with('user')
            ->where(function ($query) use ($now) {
                $query->whereNotNull('class_start_date')
                    ->where('class_start_date', '>=', $now)
                    ->orWhere(function ($sub) use ($now) {
                        $sub->whereNotNull('series_end_date')
                            ->whereDate('series_end_date', '>=', $now->toDateString());
                    })
                    ->orWhere(function ($sub) use ($now) {
                        $sub->whereNotNull('class_end_date')
                            ->where('class_end_date', '>=', $now);
                    });
            })
            ->get()
            ->map(function ($schedule) use ($buildUpcomingOccurrences, $weekdayLookup, $parseDate) {
                [$occurrences, $count] = $buildUpcomingOccurrences($schedule);

                if ($occurrences->isEmpty()) {
                    return null;
                }

                $dayKeysRaw = $schedule->recurring_days;
                $dayKeys = is_array($dayKeysRaw) ? $dayKeysRaw : json_decode($dayKeysRaw ?? '[]', true);
                $cadenceLabel = collect($dayKeys)->map(function ($d) use ($weekdayLookup) {
                    return $weekdayLookup[$d] ?? ucfirst((string) $d);
                })->filter()->implode(', ');
                if ($cadenceLabel === '') {
                    $cadenceLabel = 'One-time session';
                }

                $startTimeString = $schedule->class_start_time
                    ?? ($schedule->class_start_date ? $parseDate($schedule->class_start_date)?->format('H:i:s') : null);
                $endTimeString = $schedule->class_end_time
                    ?? ($schedule->class_end_date ? $parseDate($schedule->class_end_date)?->format('H:i:s') : null);

                $timeRange = ($startTimeString && $endTimeString)
                    ? Carbon::parse($startTimeString)->format('g:i A') . ' - ' . Carbon::parse($endTimeString)->format('g:i A')
                    : ($startTimeString ? Carbon::parse($startTimeString)->format('g:i A') : null);

                $seriesStart = $parseDate($schedule->series_start_date) ?? $parseDate($schedule->class_start_date);
                $seriesEnd = $parseDate($schedule->series_end_date) ?? $parseDate($schedule->class_end_date);

                $seriesRange = $seriesStart
                    ? $seriesStart->format('M j, Y') . ($seriesEnd ? ' → ' . $seriesEnd->format('M j, Y') : '')
                    : ($seriesEnd ? 'Until ' . $seriesEnd->format('M j, Y') : 'Series not set');

                $occurrenceDisplay = $occurrences->map(function ($occ) {
                    $start = $occ['start'] ?? null;
                    $end = $occ['end'] ?? null;
                    $label = $start ? $start->format('M j, Y g:i A') : '—';

                    if ($start && $end) {
                        $label .= ' - ' . $end->format('g:i A');
                    }

                    return [
                        'start' => $start,
                        'end' => $end,
                        'label' => $label,
                    ];
                });

                $schedule->setAttribute('upcoming_occurrences', $occurrenceDisplay);
                $schedule->setAttribute('upcoming_occurrence_count', $count);
                $schedule->setAttribute('next_occurrence', $occurrenceDisplay->first()['start'] ?? null);
                $schedule->setAttribute('time_range_label', $timeRange);
                $schedule->setAttribute('cadence_label', $cadenceLabel);
                $schedule->setAttribute('series_range_label', $seriesRange);

                return $schedule;
            })
            ->filter()
            ->sortBy(function ($schedule) {
                $next = $schedule->getAttribute('next_occurrence');

                return $next instanceof Carbon ? $next->timestamp : PHP_INT_MAX;
            })
            ->take(5)
            ->values();
        $latestStaff = User::where('role_id', 2)
            ->latest()
            ->limit(5)
            ->get();
        $latestAdmins = User::where('role_id', 1)
            ->latest()
            ->limit(5)
            ->get();
        
        $gym_members = User::where('role_id', 3)->limit(10)->get();
        $logs = Log::orderBy('id', 'desc')->limit(10)->get();

        // Build last 6 month labels (oldest -> newest)
        $months = collect(range(5, 0))->map(function ($i) use ($now) {
            return $now->copy()->subMonths($i);
        });
        $chartLabels = $months->map(fn ($d) => $d->format('M Y'));

        // Helpers
        $countByMonth = function ($query, string $dateColumn = 'created_at') use ($months) {
            $map = $months->mapWithKeys(function ($d) {
                return [$d->format('Y-m') => 0];
            });

            $rows = (clone $query)
                ->selectRaw("DATE_FORMAT($dateColumn, '%Y-%m') as ym, COUNT(*) as c")
                ->where($dateColumn, '>=', $months->first()->copy()->startOfMonth())
                ->groupBy('ym')
                ->orderBy('ym')
                ->pluck('c', 'ym');

            foreach ($rows as $ym => $c) {
                if ($map->has($ym)) {
                    $map[$ym] = (int) $c;
                }
            }
            return $map->values(); // aligned counts
        };

        // Dynamic datasets
        $membersPerMonth = $countByMonth(User::where('role_id', 3));
        $membershipsPerMonth = $countByMonth(Membership::query());
        $classesPerMonth = $countByMonth(Schedule::query());
        $approvedMembershipPaymentsPerMonth = $countByMonth(MembershipPayment::where('isapproved', 1), 'updated_at');

        return view(
            'admin.dashboard.index',
            compact(
                'gym_members_count',
                'staffs_count',
                'feedbacks_count',
                'gym_members',
                'memberships_count',
                'classes_count',
                'membership_payment_count',
                'logs',
                'upcomingClasses',
                'latestStaff',
                'latestAdmins'
            ) + [
                'chartLabels' => $chartLabels,
                'membersPerMonth' => $membersPerMonth,
                'membershipsPerMonth' => $membershipsPerMonth,
                'classesPerMonth' => $classesPerMonth,
                'approvedMembershipPaymentsPerMonth' => $approvedMembershipPaymentsPerMonth,
            ]
        );
    }
}
