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
    </div>
@endsection
