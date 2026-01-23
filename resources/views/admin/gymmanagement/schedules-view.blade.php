@extends('layouts.admin')
@section('title', 'View Schedule')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $data->loadMissing(['user', 'classAttendances.user'])->loadCount(['activeUserSchedules as user_schedules_count']);

        $image = $data->image ? asset($data->image) : asset('assets/images/icon.png');
        $startDate = $data->class_start_date ? \Carbon\Carbon::parse($data->class_start_date) : null;
        $endDate = $data->class_end_date ? \Carbon\Carbon::parse($data->class_end_date) : null;
        $seriesStart = $data->series_start_date ? \Carbon\Carbon::parse($data->series_start_date) : null;
        $seriesEnd = $data->series_end_date ? \Carbon\Carbon::parse($data->series_end_date) : null;
        $startTime = $data->class_start_time ? \Carbon\Carbon::parse($data->class_start_time) : ($startDate ?: null);
        $endTime = $data->class_end_time ? \Carbon\Carbon::parse($data->class_end_time) : ($endDate ?: null);
        $timeRange = $startTime && $endTime
            ? $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A')
            : ($startTime ? $startTime->format('g:i A') : 'Not set');

        $weekdayLookup = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];
        $dayKeysRaw = $data->recurring_days ?? [];
        $dayKeys = is_array($dayKeysRaw) ? $dayKeysRaw : json_decode($dayKeysRaw ?? '[]', true);
        $dayKeys = is_array($dayKeys) ? $dayKeys : [];
        $cadence = collect($dayKeys)
            ->map(fn ($d) => $weekdayLookup[$d] ?? ucfirst((string) $d))
            ->filter()
            ->implode(', ');
        $cadenceText = $cadence !== '' ? $cadence : 'One-time session';

        $now = now();
        $statusLabel = 'No schedule set';
        $statusClass = 'neutral';
        if ($startDate && $endDate) {
            if ($now->lt($startDate)) {
                $statusLabel = 'Upcoming';
                $statusClass = 'warning';
            } elseif ($now->between($startDate, $endDate)) {
                $statusLabel = 'Ongoing';
                $statusClass = 'success';
            } else {
                $statusLabel = 'Completed';
                $statusClass = 'neutral';
            }
        } elseif ($startDate) {
            $statusLabel = $now->lt($startDate) ? 'Upcoming' : 'Completed';
            $statusClass = $now->lt($startDate) ? 'warning' : 'neutral';
        }

        $adminStatus = match ((int) ($data->isadminapproved ?? 0)) {
            1 => 'Approved',
            2 => 'Rejected',
            default => 'Pending approval',
        };
        $adminClass = match ((int) ($data->isadminapproved ?? 0)) {
            1 => 'success',
            2 => 'danger',
            default => 'warning',
        };

        $trainerName = $data->trainer_id == 0
            ? 'No trainer for now'
            : trim((optional($data->user)->first_name ?? '') . ' ' . (optional($data->user)->last_name ?? ''));
        $trainerName = $trainerName !== '' ? $trainerName : 'Unassigned';

        $rateText = $data->trainer_rate_per_hour !== null
            ? number_format((float) $data->trainer_rate_per_hour, 2)
            : null;

        $slots = $data->slots ?? null;
        $enrolled = $data->user_schedules_count ?? 0;
        $fill = $slots ? min(100, round(($enrolled / max($slots, 1)) * 100)) : null;
        $classCode = $data->class_code ?? '—';

        $recurringDayKeys = collect($dayKeys)->map(function ($d) {
            return strtolower($d);
        })->toArray();
        $formatTimeLabel = function ($startTime, $endTime) {
            try {
                if ($startTime && $endTime) {
                    return \Carbon\Carbon::parse($startTime)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($endTime)->format('g:i A');
                }
                if ($startTime) {
                    return \Carbon\Carbon::parse($startTime)->format('g:i A');
                }
                if ($endTime) {
                    return \Carbon\Carbon::parse($endTime)->format('g:i A');
                }
            } catch (\Throwable $th) {
                return null;
            }

            return null;
        };
        $sessionOverridesRaw = is_array($data->session_overrides)
            ? $data->session_overrides
            : json_decode($data->session_overrides ?? '[]', true);
        $sessionOverrides = collect($sessionOverridesRaw ?? [])
            ->map(function ($override) {
                try {
                    $originalCarbon = isset($override['original_date'])
                        ? \Carbon\Carbon::parse($override['original_date'])->startOfDay()
                        : null;
                } catch (\Throwable $th) {
                    $originalCarbon = null;
                }

                if (! $originalCarbon) {
                    return null;
                }

                try {
                    $newCarbon = isset($override['new_date'])
                        ? \Carbon\Carbon::parse($override['new_date'])->startOfDay()
                        : null;
                } catch (\Throwable $th) {
                    $newCarbon = null;
                }

                return [
                    'original_date' => $originalCarbon->toDateString(),
                    'original_carbon' => $originalCarbon,
                    'new_date' => $newCarbon ? $newCarbon->toDateString() : null,
                    'new_carbon' => $newCarbon,
                    'start_time' => $override['start_time'] ?? null,
                    'end_time' => $override['end_time'] ?? null,
                ];
            })
            ->filter()
            ->keyBy('original_date');
        $sessionOccurrences = [];
        $nowSession = now();
        $sessionTimeLabel = $data->class_start_time && $data->class_end_time
            ? \Carbon\Carbon::parse($data->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($data->class_end_time)->format('g:i A')
            : ($data->class_start_time ? \Carbon\Carbon::parse($data->class_start_time)->format('g:i A') : null);
        $computeStatus = function ($sessionStart, $sessionEnd) use ($nowSession) {
            $status = 'Upcoming';
            if ($nowSession->between($sessionStart, $sessionEnd, true)) {
                $status = 'Ongoing';
            } elseif ($nowSession->gt($sessionEnd)) {
                $status = 'Completed';
            }

            $statusClass = 'bg-secondary';
            if ($status === 'Upcoming') {
                $statusClass = 'bg-warning text-dark';
            } elseif ($status === 'Ongoing') {
                $statusClass = 'bg-info text-dark';
            } elseif ($status === 'Completed') {
                $statusClass = 'bg-success';
            }

            return [$status, $statusClass];
        };

        if ($seriesStart && $seriesEnd && count($recurringDayKeys)) {
            $cursor = $seriesStart->copy()->startOfDay();
            while ($cursor->lte($seriesEnd)) {
                $dayKey = strtolower(substr($cursor->format('D'), 0, 3));
                if (in_array($dayKey, $recurringDayKeys, true)) {
                    $sessionDateKey = $cursor->toDateString();
                    $sessionStart = $data->class_start_time
                        ? $cursor->copy()->setTimeFromTimeString($data->class_start_time)
                        : $cursor->copy()->startOfDay();
                    $sessionEnd = $data->class_end_time
                        ? $cursor->copy()->setTimeFromTimeString($data->class_end_time)
                        : $cursor->copy()->endOfDay();

                    $override = $sessionOverrides[$sessionDateKey] ?? null;

                    if ($override) {
                        $sessionOccurrences[] = [
                            'date_key' => $sessionDateKey,
                            'label' => $cursor->format('M j, Y'),
                            'weekday' => $weekdayLookup[$dayKey] ?? ucfirst($dayKey),
                            'time' => $sessionTimeLabel,
                            'status' => 'Rescheduled',
                            'status_class' => 'bg-secondary',
                            'sort_key' => $sessionStart->timestamp,
                            'is_rescheduled' => true,
                            'is_override' => false,
                            'reschedule_target_label' => $override['new_carbon']
                                ? $override['new_carbon']->format('M j, Y') . ($formatTimeLabel($override['start_time'], $override['end_time']) ? ' • ' . $formatTimeLabel($override['start_time'], $override['end_time']) : '')
                                : null,
                        ];

                        $overrideDate = $override['new_carbon'] ?: $cursor->copy();
                        $overrideStart = $overrideDate->copy();
                        $overrideEnd = $overrideDate->copy();
                        $overrideStartTime = $override['start_time'] ?? $data->class_start_time;
                        $overrideEndTime = $override['end_time'] ?? $data->class_end_time;

                        if ($overrideStartTime) {
                            $overrideStart->setTimeFromTimeString($overrideStartTime);
                        } else {
                            $overrideStart->startOfDay();
                        }

                        if ($overrideEndTime) {
                            $overrideEnd->setTimeFromTimeString($overrideEndTime);
                            if ($overrideStartTime && $overrideEnd->lt($overrideStart)) {
                                $overrideEnd->addDay();
                            }
                        } elseif ($overrideStartTime) {
                            $overrideEnd = $overrideStart->copy();
                        } else {
                            $overrideEnd->endOfDay();
                        }

                        [$overrideStatus, $overrideStatusClass] = $computeStatus($overrideStart, $overrideEnd);
                        $overrideTimeLabel = $formatTimeLabel($overrideStartTime, $overrideEndTime) ?? $sessionTimeLabel;

                        $sessionOccurrences[] = [
                            'date_key' => $overrideDate->toDateString(),
                            'label' => $overrideDate->format('M j, Y'),
                            'weekday' => $overrideDate->format('l'),
                            'time' => $overrideTimeLabel,
                            'status' => $overrideStatus,
                            'status_class' => $overrideStatusClass,
                            'sort_key' => $overrideStart->timestamp,
                            'is_rescheduled' => false,
                            'is_override' => true,
                            'rescheduled_from' => $cursor->format('M j, Y'),
                        ];
                    } else {
                        [$sessionStatus, $statusClass] = $computeStatus($sessionStart, $sessionEnd);

                        $sessionOccurrences[] = [
                            'date_key' => $sessionDateKey,
                            'label' => $cursor->format('M j, Y'),
                            'weekday' => $weekdayLookup[$dayKey] ?? ucfirst($dayKey),
                            'time' => $sessionTimeLabel,
                            'status' => $sessionStatus,
                            'status_class' => $statusClass,
                            'sort_key' => $sessionStart->timestamp,
                            'is_rescheduled' => false,
                            'is_override' => false,
                        ];
                    }
                }

                $cursor->addDay();
            }
        }

        if (!count($sessionOccurrences) && $startDate) {
            $sessionStart = $startDate->copy();
            $sessionEnd = $endDate ?: ($data->class_end_time
                ? $sessionStart->copy()->setTimeFromTimeString($data->class_end_time)
                : $sessionStart->copy()->endOfDay());

            $override = $sessionOverrides[$sessionStart->toDateString()] ?? null;

            if ($override) {
                $sessionOccurrences[] = [
                    'date_key' => $sessionStart->toDateString(),
                    'label' => $sessionStart->format('M j, Y'),
                    'weekday' => $sessionStart->format('l'),
                    'time' => $sessionTimeLabel ?? $sessionStart->format('g:i A'),
                    'status' => 'Rescheduled',
                    'status_class' => 'bg-secondary',
                    'sort_key' => $sessionStart->timestamp,
                    'is_rescheduled' => true,
                    'is_override' => false,
                    'reschedule_target_label' => $override['new_carbon']
                        ? $override['new_carbon']->format('M j, Y') . ($formatTimeLabel($override['start_time'], $override['end_time']) ? ' • ' . $formatTimeLabel($override['start_time'], $override['end_time']) : '')
                        : null,
                ];

                $overrideDate = $override['new_carbon'] ?: $sessionStart->copy();
                $overrideStart = $overrideDate->copy();
                $overrideEnd = $overrideDate->copy();
                $overrideStartTime = $override['start_time'] ?? $data->class_start_time;
                $overrideEndTime = $override['end_time'] ?? $data->class_end_time;

                if ($overrideStartTime) {
                    $overrideStart->setTimeFromTimeString($overrideStartTime);
                } else {
                    $overrideStart->startOfDay();
                }

                if ($overrideEndTime) {
                    $overrideEnd->setTimeFromTimeString($overrideEndTime);
                    if ($overrideStartTime && $overrideEnd->lt($overrideStart)) {
                        $overrideEnd->addDay();
                    }
                } elseif ($overrideStartTime) {
                    $overrideEnd = $overrideStart->copy();
                } else {
                    $overrideEnd->endOfDay();
                }

                [$overrideStatus, $overrideStatusClass] = $computeStatus($overrideStart, $overrideEnd);
                $overrideTimeLabel = $formatTimeLabel($overrideStartTime, $overrideEndTime) ?? $sessionTimeLabel ?? $sessionStart->format('g:i A');

                $sessionOccurrences[] = [
                    'date_key' => $overrideDate->toDateString(),
                    'label' => $overrideDate->format('M j, Y'),
                    'weekday' => $overrideDate->format('l'),
                    'time' => $overrideTimeLabel,
                    'status' => $overrideStatus,
                    'status_class' => $overrideStatusClass,
                    'sort_key' => $overrideStart->timestamp,
                    'is_rescheduled' => false,
                    'is_override' => true,
                    'rescheduled_from' => $sessionStart->format('M j, Y'),
                ];
            } else {
                [$sessionStatus, $statusClass] = $computeStatus($sessionStart, $sessionEnd);

                $sessionOccurrences[] = [
                    'date_key' => $sessionStart->toDateString(),
                    'label' => $sessionStart->format('M j, Y'),
                    'weekday' => $sessionStart->format('l'),
                    'time' => $sessionTimeLabel ?? $sessionStart->format('g:i A'),
                    'status' => $sessionStatus,
                    'status_class' => $statusClass,
                    'sort_key' => $sessionStart->timestamp,
                    'is_rescheduled' => false,
                    'is_override' => false,
                ];
            }
        }
        if (count($sessionOccurrences)) {
            $sessionOccurrences = collect($sessionOccurrences)
                ->sortBy('sort_key')
                ->values()
                ->all();
        }
        $sessionCount = is_array($sessionOccurrences) ? count($sessionOccurrences) : 0;
        $sessionLimit = 10;

        $attendanceBySession = $data->classAttendances
            ->sortByDesc('attended_at')
            ->groupBy(function ($attendance) {
                return optional($attendance->session_date)->toDateString() ?? null;
            })
            ->filter(function ($value, $key) {
                return !empty($key);
            });

        $attendanceSessions = collect($sessionOccurrences)
            ->filter(function ($session) {
                return empty($session['is_rescheduled']) && !empty($session['date_key']);
            })
            ->values();

        $selectedAttendanceSessionKey = '';
        if ($attendanceSessions->count()) {
            $todayKey = $nowSession->toDateString();
            $selectedSession = $attendanceSessions->firstWhere('date_key', $todayKey);
            if (!$selectedSession) {
                $selectedSession = $attendanceSessions->first(function ($session) use ($nowSession) {
                    return isset($session['sort_key']) && $session['sort_key'] >= $nowSession->timestamp;
                });
            }
            if (!$selectedSession) {
                $selectedSession = $attendanceSessions->first();
            }
            $selectedAttendanceSessionKey = $selectedSession['date_key'] ?? '';
        }
    @endphp

    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img src="{{ $image }}" alt="{{ $data->name }}" class="detail-avatar">
                <div class="flex-grow-1">
                    <div class="hero-label mb-1">Class schedule</div>
                    <h2 class="hero-title mb-1">{{ $data->name }}</h2>
                    <div class="hero-subtitle">Class code: {{ $classCode }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="detail-pill">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>{{ $trainerName }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-clock"></i>
                            <span>{{ $timeRange }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-repeat"></i>
                            <span>{{ $cadenceText }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2 ms-auto">
                    <span class="detail-badge {{ $statusClass }}">
                        <i class="fa-solid fa-calendar-check"></i>
                        {{ $statusLabel }}
                    </span>
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-solid fa-shield-heart"></i></span>
                        {{ $adminStatus }}
                    </span>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        @if($sessionCount > $sessionLimit)
                            <a href="#session-series" class="btn btn-light">
                                <i class="fa-solid fa-calendar-week me-1"></i>Show more sessions
                            </a>
                        @endif
                        <a href="{{ route('admin.gym-management.schedules.users', $data->id) }}" class="btn btn-outline-light">
                            <i class="fa-solid fa-users me-1"></i>Enrollees
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-stats-grid">
            <div class="detail-stat">
                <span class="label">Schedule window</span>
                <div class="value">{{ $startDate ? $startDate->format('M d, Y g:i A') : 'Not set' }}</div>
                <div class="hint">{{ $endDate ? 'Ends ' . $endDate->format('M d, Y g:i A') : 'No end date yet' }}</div>
            </div>
            <div class="detail-stat">
                <span class="label">Enrollment</span>
                <div class="value">
                    {{ $slots ? "{$enrolled} / {$slots}" : $enrolled }} participants
                </div>
                <div class="hint">
                    {{ $slots ? ($fill . '% of capacity') : 'Slots not limited' }}
                </div>
                @if($fill !== null)
                    <div class="stat-progress">
                        <div class="bar" style="width: {{ $fill }}%;"></div>
                    </div>
                @endif
            </div>
            <div class="detail-stat">
                <span class="label">Cadence</span>
                <div class="value">{{ $cadenceText }}</div>
                @if($dayKeys)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($dayKeys as $day)
                            <span class="pill-soft">
                                <span class="icon"><i class="fa-regular fa-calendar-check"></i></span>
                                {{ $weekdayLookup[$day] ?? ucfirst((string) $day) }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="hint">One-time class</div>
                @endif
            </div>
            <div class="detail-stat">
                <span class="label">Trainer</span>
                <div class="value">{{ $trainerName }}</div>
                <div class="hint">
                    {{ $rateText ? 'Rate: ' . $rateText . ' / hr' : 'No rate set' }}
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0">Class details</h5>
                <span class="text-muted detail-meta">Updated {{ optional($data->updated_at)->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="table-responsive">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th scope="row">Class code</th>
                            <td>{{ $classCode }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Trainer</th>
                            <td>{{ $trainerName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Trainer rate</th>
                            <td>{{ $rateText ? $rateText . ' / hr' : '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Time</th>
                            <td>{{ $timeRange }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Recurring days</th>
                            <td>{{ $cadenceText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Series range</th>
                            <td>
                                {{ $seriesStart ? $seriesStart->format('M d, Y') : '—' }}
                                @if($seriesEnd)
                                    &nbsp;to&nbsp;{{ $seriesEnd->format('M d, Y') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Slots</th>
                            <td>{{ $slots ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Enrolled</th>
                            <td>{{ $enrolled }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Admin status</th>
                            <td>
                                <span class="detail-badge {{ $adminClass }}">{{ $adminStatus }}</span>
                                @if(($data->rejection_reason ?? '') && ((int) ($data->isadminapproved ?? 0) === 2))
                                    <div class="text-muted small mt-1">{{ $data->rejection_reason }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>{{ optional($data->created_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Updated</th>
                            <td>{{ optional($data->updated_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="detail-card mt-4" id="session-series">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Series of sessions</h5>
                    <div class="text-muted detail-meta">Generated from the cadence and series window.</div>
                </div>
                @if($seriesStart && $seriesEnd)
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-regular fa-calendar"></i></span>
                        {{ $seriesStart->format('M d, Y') }} — {{ $seriesEnd->format('M d, Y') }}
                    </span>
                @endif
            </div>

            @if(count($sessionOccurrences))
                <div class="d-flex flex-column gap-3 session-list">
                    @foreach($sessionOccurrences as $index => $session)
                        @php
                            $isHiddenSession = $sessionCount > $sessionLimit && $index >= $sessionLimit;
                        @endphp
                        <div class="d-flex align-items-start gap-3 {{ $isHiddenSession ? 'd-none extra-session' : '' }}">
                            <div class="d-flex flex-column align-items-center">
                                <span class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></span>
                                @if($index < count($sessionOccurrences) - 1)
                                    <span class="flex-grow-1" style="width: 2px; background: #e9ecef; min-height: 24px;"></span>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    @php
                                        $isRescheduled = !empty($session['is_rescheduled']);
                                        $rescheduleTarget = $session['reschedule_target_label'] ?? null;
                                        $rescheduledFrom = $session['rescheduled_from'] ?? null;
                                    @endphp
                                    <div>
                                        <div class="fw-semibold {{ $isRescheduled ? 'text-decoration-line-through text-muted' : '' }}">{{ $session['label'] }}</div>
                                        <div class="text-muted small">
                                            {{ $session['weekday'] }}{{ $session['time'] ? ' • ' . $session['time'] : '' }}
                                            @if($isRescheduled && $rescheduleTarget)
                                                <span class="ms-2 fst-italic">→ {{ $rescheduleTarget }}</span>
                                            @elseif(!$isRescheduled && $rescheduledFrom)
                                                <span class="ms-2 fst-italic">From {{ $rescheduledFrom }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge {{ $session['status_class'] }} px-3 py-2">{{ $session['status'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if($sessionCount > $sessionLimit)
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm align-self-start"
                            id="session-toggle-btn"
                            data-expanded-text="Show less sessions"
                            data-collapsed-text="Show more sessions"
                        >
                            <i class="fa-solid fa-ellipsis-h me-1"></i><span class="label-text">Show more sessions</span>
                        </button>
                    @endif
                </div>
            @else
                <div class="text-muted">No sessions generated yet. Add a series window and cadence to preview occurrences.</div>
            @endif
        </div>

        <div class="detail-card mt-4" id="attendance-history">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Attendance history</h5>
                    <div class="text-muted detail-meta">View members who attended each session in the series.</div>
                </div>
                <span class="detail-chip">
                    <span class="icon"><i class="fa-solid fa-user-check"></i></span>
                    {{ $data->classAttendances->count() }} records
                </span>
            </div>

            @if($attendanceSessions->count())
                <div class="d-flex flex-nowrap gap-2 overflow-auto pb-2">
                    @foreach($attendanceSessions as $session)
                        @php
                            $sessionKey = $session['date_key'] ?? '';
                            $isActiveSession = $sessionKey === $selectedAttendanceSessionKey;
                        @endphp
                        <button
                            type="button"
                            class="btn btn-sm rounded-pill text-start attendance-session-chip {{ $isActiveSession ? 'btn-danger' : 'btn-outline-secondary' }}"
                            data-session-key="{{ $sessionKey }}"
                        >
                            <span class="d-block fw-semibold">{{ $session['label'] }}</span>
                            <span class="d-block small attendance-session-subtitle {{ $isActiveSession ? 'text-white-50' : 'text-muted' }}">
                                {{ $session['weekday'] }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-3">
                    @foreach($attendanceSessions as $session)
                        @php
                            $sessionKey = $session['date_key'] ?? '';
                            $attendees = $sessionKey ? $attendanceBySession->get($sessionKey, collect()) : collect();
                            $isHiddenPanel = $sessionKey !== $selectedAttendanceSessionKey;
                        @endphp
                        <div class="attendance-session-panel {{ $isHiddenPanel ? 'd-none' : '' }}" data-session-key="{{ $sessionKey }}">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $session['label'] }}</div>
                                    <div class="text-muted small">
                                        {{ $session['weekday'] }}{{ $session['time'] ? ' • ' . $session['time'] : '' }}
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark">{{ $attendees->count() }} present</span>
                            </div>

                            @if($attendees->count())
                                <div class="border rounded-3 overflow-hidden">
                                    @foreach($attendees as $attendance)
                                        @php
                                            $attendee = $attendance->user;
                                            $attendeeName = trim(($attendee->first_name ?? '') . ' ' . ($attendee->last_name ?? ''));
                                            $attendeeName = $attendeeName !== '' ? $attendeeName : ($attendee->email ?? 'Member');
                                            $attendedLabel = $attendance->attended_at
                                                ? $attendance->attended_at->format('M d, Y g:i A')
                                                : null;
                                        @endphp
                                        <div class="d-flex align-items-start justify-content-between gap-3 p-3 {{ $loop->last ? '' : 'border-bottom' }}">
                                            <div>
                                                <div class="fw-semibold">{{ $attendeeName }}</div>
                                                @if(!empty($attendee?->email))
                                                    <div class="text-muted small">{{ $attendee->email }}</div>
                                                @endif
                                                @if(!empty($attendee?->phone_number))
                                                    <div class="text-muted small">{{ $attendee->phone_number }}</div>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <div class="text-muted small">Checked in</div>
                                                <div class="fw-semibold">{{ $attendedLabel ?? '—' }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted">No attendance recorded for this session.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted">No sessions available yet to display attendance history.</div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggleBtn = document.getElementById('session-toggle-btn');

            if (toggleBtn) {
                var hiddenItems = document.querySelectorAll('.session-list .extra-session');
                var labelEl = toggleBtn.querySelector('.label-text');
                var expandedText = toggleBtn.getAttribute('data-expanded-text') || 'Show less sessions';
                var collapsedText = toggleBtn.getAttribute('data-collapsed-text') || 'Show more sessions';
                var expanded = false;

                var setState = function (show) {
                    hiddenItems.forEach(function (item) {
                        item.classList.toggle('d-none', !show);
                    });

                    if (labelEl) {
                        labelEl.textContent = show ? expandedText : collapsedText;
                    } else {
                        toggleBtn.textContent = show ? expandedText : collapsedText;
                    }
                };

                setState(expanded);

                toggleBtn.addEventListener('click', function () {
                    expanded = !expanded;
                    setState(expanded);
                });
            }

            var sessionChips = document.querySelectorAll('.attendance-session-chip');
            var sessionPanels = document.querySelectorAll('.attendance-session-panel');

            if (!sessionChips.length || !sessionPanels.length) {
                return;
            }

            var setActiveSession = function (sessionKey) {
                sessionPanels.forEach(function (panel) {
                    panel.classList.toggle('d-none', panel.dataset.sessionKey !== sessionKey);
                });

                sessionChips.forEach(function (chip) {
                    var isActive = chip.dataset.sessionKey === sessionKey;
                    chip.classList.toggle('btn-danger', isActive);
                    chip.classList.toggle('btn-outline-secondary', !isActive);

                    var subtitle = chip.querySelector('.attendance-session-subtitle');
                    if (subtitle) {
                        subtitle.classList.toggle('text-white-50', isActive);
                        subtitle.classList.toggle('text-muted', !isActive);
                    }
                });
            };

            sessionChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var key = chip.dataset.sessionKey;
                    if (key) {
                        setActiveSession(key);
                    }
                });
            });
        });
    </script>
@endsection
