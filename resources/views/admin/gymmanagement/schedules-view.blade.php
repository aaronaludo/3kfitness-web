@extends('layouts.admin')
@section('title', 'View Schedule')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $data->loadMissing(['user'])->loadCount('user_schedules');

        $image = $data->image ? asset($data->image) : asset('assets/images/default-image.png');
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
        $sessionOccurrences = [];
        $maxSessions = 6;
        $hasMoreSessions = false;
        $nowSession = now();
        $hasOngoingSession = false;
        $hasFutureSession = false;
        $sessionTimeLabel = $data->class_start_time && $data->class_end_time
            ? \Carbon\Carbon::parse($data->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($data->class_end_time)->format('g:i A')
            : ($data->class_start_time ? \Carbon\Carbon::parse($data->class_start_time)->format('g:i A') : null);

        if ($seriesStart && $seriesEnd && count($recurringDayKeys)) {
            $cursor = $seriesStart->copy()->startOfDay();
            $occurrenceCount = 0;
            while ($cursor->lte($seriesEnd)) {
                $dayKey = strtolower(substr($cursor->format('D'), 0, 3));
                if (in_array($dayKey, $recurringDayKeys, true)) {
                    $occurrenceCount++;
                    if (count($sessionOccurrences) < $maxSessions) {
                        $sessionStart = $data->class_start_time
                            ? $cursor->copy()->setTimeFromTimeString($data->class_start_time)
                            : $cursor->copy()->startOfDay();
                        $sessionEnd = $data->class_end_time
                            ? $cursor->copy()->setTimeFromTimeString($data->class_end_time)
                            : $cursor->copy()->endOfDay();

                        $sessionStatus = 'Upcoming';
                        if ($nowSession->between($sessionStart, $sessionEnd, true)) {
                            $sessionStatus = 'Ongoing';
                            $hasOngoingSession = true;
                        } elseif ($nowSession->gt($sessionEnd)) {
                            $sessionStatus = 'Completed';
                        } else {
                            $hasFutureSession = true;
                        }

                        $statusClass = 'bg-secondary';
                        if ($sessionStatus === 'Upcoming') {
                            $statusClass = 'bg-warning text-dark';
                        } elseif ($sessionStatus === 'Ongoing') {
                            $statusClass = 'bg-info text-dark';
                        } elseif ($sessionStatus === 'Completed') {
                            $statusClass = 'bg-success';
                        }

                        $sessionOccurrences[] = [
                            'label' => $cursor->format('M j, Y'),
                            'weekday' => $weekdayLookup[$dayKey] ?? ucfirst($dayKey),
                            'time' => $sessionTimeLabel,
                            'status' => $sessionStatus,
                            'status_class' => $statusClass,
                        ];
                    }

                    if ($occurrenceCount >= $maxSessions && $cursor->lt($seriesEnd)) {
                        $hasMoreSessions = true;
                        if ($hasOngoingSession || $hasFutureSession) {
                            break;
                        }
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

            $sessionStatus = 'Upcoming';
            if ($nowSession->between($sessionStart, $sessionEnd, true)) {
                $sessionStatus = 'Ongoing';
            } elseif ($nowSession->gt($sessionEnd)) {
                $sessionStatus = 'Completed';
            }

            $statusClass = 'bg-secondary';
            if ($sessionStatus === 'Upcoming') {
                $statusClass = 'bg-warning text-dark';
            } elseif ($sessionStatus === 'Ongoing') {
                $statusClass = 'bg-info text-dark';
            } elseif ($sessionStatus === 'Completed') {
                $statusClass = 'bg-success';
            }

            $sessionOccurrences[] = [
                'label' => $sessionStart->format('M j, Y'),
                'weekday' => $sessionStart->format('l'),
                'time' => $sessionTimeLabel ?? $sessionStart->format('g:i A'),
                'status' => $sessionStatus,
                'status_class' => $statusClass,
            ];
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
                        <a href="{{ route('admin.gym-management.schedules.edit', $data->id) }}" class="btn btn-light text-danger fw-semibold">
                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                        </a>
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

        <div class="detail-card mt-4">
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
                <div class="d-flex flex-column gap-3">
                    @foreach($sessionOccurrences as $index => $session)
                        <div class="d-flex align-items-start gap-3">
                            <div class="d-flex flex-column align-items-center">
                                <span class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></span>
                                @if($index < count($sessionOccurrences) - 1)
                                    <span class="flex-grow-1" style="width: 2px; background: #e9ecef; min-height: 24px;"></span>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $session['label'] }}</div>
                                        <div class="text-muted small">
                                            {{ $session['weekday'] }}{{ $session['time'] ? ' • ' . $session['time'] : '' }}
                                        </div>
                                    </div>
                                    <span class="badge {{ $session['status_class'] }} px-3 py-2">{{ $session['status'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if($hasMoreSessions)
                        <div class="text-muted small">More sessions in this series…</div>
                    @endif
                </div>
            @else
                <div class="text-muted">No sessions generated yet. Add a series window and cadence to preview occurrences.</div>
            @endif
        </div>
    </div>
@endsection
