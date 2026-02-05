@extends('layouts.admin')
@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('title', 'Trainer Management - Index')

@section('content')
    <style>
        .manual-clock-modal .modal-content {
            border-radius: 22px;
            border: none;
            background: #f6f5fb;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
        }
        .manual-clock-modal .modal-header {
            border-bottom: none;
        }
        .manual-clock-hero {
            text-align: center;
        }
        .manual-clock-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 12px;
            border: 1px solid transparent;
        }
        .manual-clock-icon--success {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
            border-color: rgba(34, 197, 94, 0.35);
        }
        .manual-clock-icon--danger {
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.35);
        }
        .manual-clock-icon--warning {
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
            border-color: rgba(245, 158, 11, 0.35);
        }
        .manual-clock-icon--loading {
            background: rgba(148, 163, 184, 0.18);
            color: #475569;
            border-color: rgba(148, 163, 184, 0.35);
        }
        .manual-clock-title {
            font-weight: 800;
            margin-bottom: 4px;
        }
        .manual-clock-subtitle {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 16px;
        }
        .manual-clock-member-card {
            background: #fff;
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            gap: 12px;
            align-items: center;
            border: 1px solid #eceef6;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }
        .manual-clock-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            flex: 0 0 auto;
        }
        .manual-clock-member-info {
            flex: 1;
            min-width: 0;
        }
        .manual-clock-member-name {
            font-weight: 700;
            margin-bottom: 2px;
        }
        .manual-clock-member-meta {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 2px;
            word-break: break-word;
        }
        .manual-clock-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 6px;
        }
        .manual-clock-chip {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .manual-clock-chip--active {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
            border-color: rgba(34, 197, 94, 0.35);
        }
        .manual-clock-chip--inactive {
            background: rgba(148, 163, 184, 0.15);
            color: #64748b;
            border-color: rgba(148, 163, 184, 0.35);
        }
        .manual-clock-warning {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }
        .manual-clock-warning-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(245, 158, 11, 0.15);
            color: #b45309;
            flex: 0 0 auto;
            font-size: 1rem;
        }
        .manual-clock-warning-title {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 2px;
            color: #7c2d12;
        }
        .manual-clock-warning-subtitle {
            font-size: 0.75rem;
            color: #9a3412;
            margin-bottom: 0;
        }
        @media (max-width: 575px) {
            .manual-clock-member-card {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        .assignment-modal .modal-body { background: #f8fafc; }
        .assignment-modal .payroll-summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .assignment-modal .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .assignment-modal .summary-item { padding: 14px 18px; }
        .assignment-modal .summary-item + .summary-item { border-left: 1px solid #e5e7eb; }
        .assignment-modal .summary-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
        }
        .assignment-modal .summary-value { font-size: 20px; font-weight: 700; color: #111827; }
        .assignment-modal .status-pill {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .assignment-modal .payroll-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }
        .assignment-modal .payroll-card-toggle { background: #f8fafc; border: 0; }
        .assignment-modal .payroll-card-toggle:focus { box-shadow: none; }
        .assignment-modal .payroll-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4338ca;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .assignment-modal .filter-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
        }
        .assignment-modal .payroll-table {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow-x: hidden;
            background: #fff;
        }
        .assignment-modal .payroll-table thead th {
            background: #f8fafc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
        }
        .assignment-modal .assignment-row { cursor: default; font-size: 0.9rem; }
        .assignment-modal .assignment-col { min-width: 0; }
        .assignment-modal .assignment-row.d-none { display: none !important; }
        .assignment-modal .modal-dialog {
            margin-top: 4.5rem;
            margin-bottom: 2rem;
        }
        .assignment-modal .modal-content { font-size: 0.94rem; }
        .assignment-modal .modal-title { font-size: 1.1rem; }
        .assignment-modal .modal-body { overflow-x: hidden; }
        .assignment-modal .assignment-list,
        .assignment-modal .payroll-table,
        .assignment-modal .assignment-row,
        .assignment-modal .series-sessions,
        .assignment-modal .series-panel {
            max-width: 100%;
        }
        .assignment-modal .series-panel {
            width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        .assignment-modal .series-item { max-width: 100%; }
        .assignment-modal .series-item div { overflow-wrap: anywhere; }
        .students-modal {
            z-index: 1085;
        }
        .assignment-modal .col-students { text-align: left; }
        .students-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1080;
            display: none;
        }
        .students-modal-backdrop.is-visible {
            display: block;
        }
        .assignment-modal .assignment-hours-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .assignment-modal .students-toggle {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #6b7280;
        }
        .assignment-modal .students-modal-list {
            max-height: 240px;
            overflow-y: auto;
        }
        .students-modal .modal-content {
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
        }
        .students-modal .modal-header {
            border-bottom: 1px solid #e5e7eb;
        }
        .students-modal .students-modal-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #4338ca;
            font-size: 18px;
        }
        .students-modal .students-modal-count {
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 600;
        }
        .students-modal .students-modal-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .students-modal .student-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }
        .students-modal .student-item:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }
        .students-modal .student-name {
            font-weight: 600;
            color: #0f172a;
        }
        .students-modal .student-code {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #475569;
        }
        .assignment-modal .assignment-pagination .page-link {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            min-width: 34px;
            text-align: center;
            padding: 4px 10px;
        }
        .assignment-modal .assignment-pagination .pagination {
            gap: 6px;
        }
        .assignment-modal .assignment-pagination .page-item {
            margin: 0;
        }
        .assignment-modal .assignment-pagination .page-item.active .page-link {
            background: #e5e7eb;
            border-color: #e5e7eb;
            color: #111827;
        }
        .assignment-modal .assignment-pagination .page-item.disabled .page-link {
            color: #cbd5e1;
        }
        .assignment-modal .assignment-date-list { display: none; }
        .assignment-modal .assignment-date-range { display: block; }
        .assignment-modal .series-sessions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .assignment-modal .series-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .assignment-modal .series-panel {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px 12px;
            max-height: 240px;
            overflow: auto;
            box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.4);
        }
        .assignment-modal .series-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
        }
        .assignment-modal .series-item + .series-item {
            border-top: 1px dashed #e2e8f0;
            padding-top: 10px;
            margin-top: 4px;
        }
        .assignment-modal .series-dot {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 14px;
            margin-top: 4px;
        }
        .assignment-modal .series-dot .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #2563eb;
            box-shadow: 0 0 0 4px #eff6ff;
        }
        .assignment-modal .series-dot .line {
            width: 2px;
            height: 20px;
            background: #e2e8f0;
            margin-top: 4px;
        }
        .assignment-modal .session-toggle {
            color: #2563eb;
            text-decoration: underline;
        }
        @media (min-width: 992px) {
            .assignment-modal .assignment-table-head,
            .assignment-modal .assignment-row {
                display: grid !important;
                grid-template-columns: 90px minmax(220px, 1fr) 120px 220px minmax(260px, 1.1fr) 170px;
                column-gap: 16px;
                align-items: start;
            }
            .assignment-modal .assignment-row {
                display: grid !important;
                row-gap: 8px;
            }
            .assignment-modal .assignment-col {
                width: auto;
            }
            .assignment-modal .col-rate { text-align: right; }
            .assignment-modal .col-series { text-align: left; }
        }
        @media (max-width: 991.98px) {
            .assignment-modal .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .assignment-modal .summary-item { border-left: 0; border-top: 1px solid #e5e7eb; }
            .assignment-modal .summary-item:nth-child(1),
            .assignment-modal .summary-item:nth-child(2) { border-top: 0; }
            .assignment-modal .summary-item:nth-child(odd) { border-right: 1px solid #e5e7eb; }
        }
        @media (max-width: 575.98px) {
            .assignment-modal .modal-dialog { margin-top: 3.5rem; }
            .assignment-modal .summary-grid { grid-template-columns: minmax(0, 1fr); }
            .assignment-modal .summary-item { border-right: 0; }
        }
    </style>
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $printSource = $showArchived ? $archivedData : $trainers;
                $printAllSource = $showArchived ? ($printAllArchived ?? collect()) : ($printAllActive ?? collect());
                $weekdayLookup = [
                    'sun' => 'Sunday',
                    'mon' => 'Monday',
                    'tue' => 'Tuesday',
                    'wed' => 'Wednesday',
                    'thu' => 'Thursday',
                    'fri' => 'Friday',
                    'sat' => 'Saturday',
                ];
                $formatAssignmentDuration = function ($hours) {
                    if ($hours === null) {
                        return '—';
                    }
                    $totalMinutes = (int) round($hours * 60);
                    if ($totalMinutes < 1) {
                        return '0 min';
                    }
                    $hrs = intdiv($totalMinutes, 60);
                    $mins = $totalMinutes % 60;
                    $parts = [];
                    if ($hrs > 0) {
                        $parts[] = $hrs . ' ' . ($hrs === 1 ? 'hr' : 'hrs');
                    }
                    if ($mins > 0) {
                        $parts[] = $mins . ' ' . ($mins === 1 ? 'min' : 'mins');
                    }
                    return implode(' ', $parts);
                };
                $buildScheduleSeries = function ($schedule, $start, $end, $now, $trainerAttendances = null) use ($weekdayLookup) {
                    $seriesStart = null;
                    if (!empty($schedule->series_start_date)) {
                        $seriesStart = \Carbon\Carbon::parse($schedule->series_start_date)->startOfDay();
                    } elseif ($start instanceof \Carbon\Carbon) {
                        $seriesStart = $start->copy()->startOfDay();
                    } elseif (!empty($schedule->class_start_date)) {
                        $seriesStart = \Carbon\Carbon::parse($schedule->class_start_date)->startOfDay();
                    }

                    $seriesEnd = null;
                    if (!empty($schedule->series_end_date)) {
                        $seriesEnd = \Carbon\Carbon::parse($schedule->series_end_date)->endOfDay();
                    } elseif ($end instanceof \Carbon\Carbon) {
                        $seriesEnd = $end->copy()->endOfDay();
                    } elseif (!empty($schedule->class_end_date)) {
                        $seriesEnd = \Carbon\Carbon::parse($schedule->class_end_date)->endOfDay();
                    }

                    if (!$seriesStart) {
                        return [
                            'sessions' => collect(),
                            'actual_sessions' => collect(),
                            'labels' => collect(),
                            'range' => '—',
                        ];
                    }

                    if (!$seriesEnd) {
                        $seriesEnd = $seriesStart->copy()->endOfDay();
                    }

                    if ($seriesEnd->lt($seriesStart)) {
                        $seriesEnd = $seriesStart->copy()->endOfDay();
                    }

                    $startTimeString = $schedule->class_start_time ?? ($start instanceof \Carbon\Carbon ? $start->format('H:i:s') : null);
                    $endTimeString = $schedule->class_end_time ?? ($end instanceof \Carbon\Carbon ? $end->format('H:i:s') : null);

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

                    $defaultTimeLabel = $formatTimeLabel($startTimeString, $endTimeString);

                    $dayKeys = is_array($schedule->recurring_days)
                        ? $schedule->recurring_days
                        : json_decode($schedule->recurring_days ?? '[]', true);
                    $recurringDays = collect($dayKeys ?? [])
                        ->map(function ($day) {
                            return strtolower($day);
                        })
                        ->filter(function ($day) use ($weekdayLookup) {
                            return array_key_exists($day, $weekdayLookup);
                        })
                        ->values();
                    $weekdayKeys = [0 => 'sun', 1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat'];

                    $sessionOverridesRaw = is_array($schedule->session_overrides)
                        ? $schedule->session_overrides
                        : json_decode($schedule->session_overrides ?? '[]', true);
                    $sessionOverrides = collect($sessionOverridesRaw ?? [])
                        ->map(function ($override) {
                            try {
                                $originalCarbon = isset($override['original_date'])
                                    ? \Carbon\Carbon::parse($override['original_date'])->startOfDay()
                                    : null;
                            } catch (\Throwable $th) {
                                $originalCarbon = null;
                            }

                            if (!$originalCarbon) {
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

                    $trainerAttendances = $trainerAttendances instanceof \Illuminate\Support\Collection
                        ? $trainerAttendances
                        : collect($trainerAttendances ?? []);

                    $hasAttendance = function ($sessionStart, $sessionEnd) use ($trainerAttendances) {
                        return $trainerAttendances->contains(function ($attendance) use ($sessionStart, $sessionEnd) {
                            $clockIn = $attendance['clockin'] ?? null;
                            $clockOut = $attendance['clockout'] ?? null;

                            $overlapsStart = $clockIn && $clockIn->between($sessionStart, $sessionEnd, true);
                            $overlapsEnd = $clockOut && $clockOut->between($sessionStart, $sessionEnd, true);
                            $spansRange = $clockIn && $clockOut && $clockIn->lte($sessionStart) && $clockOut->gte($sessionEnd);
                            $clockInOnly = $clockIn && !$clockOut && $clockIn->between($sessionStart, $sessionEnd, true);

                            return $overlapsStart || $overlapsEnd || $spansRange || $clockInOnly;
                        });
                    };

                    $resolveAttendanceStatus = function ($sessionStart, $sessionEnd) use ($now, $hasAttendance) {
                        $isPast = $sessionEnd->lt($now);
                        if (!$isPast) {
                            return ['Upcoming', 'bg-warning-subtle text-warning', false];
                        }

                        $hasMatch = $hasAttendance($sessionStart, $sessionEnd);
                        if ($hasMatch) {
                            return ['Present', 'bg-success-subtle text-success', true];
                        }

                        return ['Absent', 'bg-danger-subtle text-danger', true];
                    };

                    $occurrences = collect();
                    if ($recurringDays->isEmpty()) {
                        $occurrences->push($seriesStart->copy());
                    } else {
                        $cursor = $seriesStart->copy();
                        $guard = 0;
                        $limit = max(1, $seriesStart->diffInDays($seriesEnd) + 2);
                        while ($cursor->lte($seriesEnd) && $guard < $limit) {
                            $dayKey = $weekdayKeys[$cursor->dayOfWeek] ?? null;
                            if ($dayKey && $recurringDays->contains($dayKey)) {
                                $occurrences->push($cursor->copy());
                            }
                            $cursor->addDay();
                            $guard += 1;
                        }
                    }
                    if ($occurrences->isEmpty() && $start instanceof \Carbon\Carbon) {
                        $occurrences->push($start->copy()->startOfDay());
                    }

                    $sessions = collect();

                    foreach ($occurrences as $occurrenceDate) {
                        $sessionStart = $occurrenceDate->copy();
                        if ($startTimeString) {
                            $sessionStart->setTimeFromTimeString($startTimeString);
                        } else {
                            $sessionStart->startOfDay();
                        }

                        $sessionEnd = $occurrenceDate->copy();
                        if ($endTimeString) {
                            $sessionEnd->setTimeFromTimeString($endTimeString);
                            if ($startTimeString && $sessionEnd->lt($sessionStart)) {
                                $sessionEnd->addDay();
                            }
                        } elseif ($startTimeString) {
                            $sessionEnd = $sessionStart->copy();
                        } else {
                            $sessionEnd->endOfDay();
                        }

                        $sessionDateKey = $occurrenceDate->toDateString();
                        $weekdayLabel = $occurrenceDate->format('l');
                        $override = $sessionOverrides[$sessionDateKey] ?? null;

                        if ($override) {
                            $rescheduleTargetLabel = null;
                            if ($override['new_carbon']) {
                                $targetTimeLabel = $formatTimeLabel($override['start_time'] ?? $startTimeString, $override['end_time'] ?? $endTimeString);
                                $rescheduleTargetLabel = $override['new_carbon']->format('F j, Y');
                                if ($targetTimeLabel) {
                                    $rescheduleTargetLabel .= ' • ' . $targetTimeLabel;
                                }
                            }

                            $sessions->push([
                                'label' => $occurrenceDate->format('F j, Y'),
                                'short' => $occurrenceDate->format('M d'),
                                'weekday' => $weekdayLabel,
                                'time' => $defaultTimeLabel,
                                'status' => 'Rescheduled',
                                'status_class' => 'bg-secondary-subtle text-secondary',
                                'sort_key' => $sessionStart->timestamp,
                                'is_rescheduled' => true,
                                'is_override' => false,
                                'reschedule_target_label' => $rescheduleTargetLabel,
                                'date' => $sessionDateKey,
                            ]);

                            $overrideDate = $override['new_carbon'] ?: $occurrenceDate->copy();
                            $overrideStart = $overrideDate->copy();
                            $overrideEnd = $overrideDate->copy();
                            $overrideStartTime = $override['start_time'] ?? $startTimeString;
                            $overrideEndTime = $override['end_time'] ?? $endTimeString;

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

                            [$overrideStatus, $overrideStatusClass, $overrideIsPast] = $resolveAttendanceStatus($overrideStart, $overrideEnd);
                            $overrideTimeLabel = $formatTimeLabel($overrideStartTime, $overrideEndTime) ?? $defaultTimeLabel;

                            $sessions->push([
                                'label' => $overrideDate->format('F j, Y'),
                                'short' => $overrideDate->format('M d'),
                                'weekday' => $overrideDate->format('l'),
                                'time' => $overrideTimeLabel,
                                'status' => $overrideStatus,
                                'status_class' => $overrideStatusClass,
                                'sort_key' => $overrideStart->timestamp,
                                'is_rescheduled' => false,
                                'is_override' => true,
                                'rescheduled_from' => $occurrenceDate->format('F j, Y'),
                                'date' => $overrideDate->toDateString(),
                                'is_past' => $overrideIsPast,
                            ]);
                        } else {
                            [$sessionStatus, $statusClass, $sessionIsPast] = $resolveAttendanceStatus($sessionStart, $sessionEnd);

                            $sessions->push([
                                'label' => $occurrenceDate->format('F j, Y'),
                                'short' => $occurrenceDate->format('M d'),
                                'weekday' => $weekdayLabel,
                                'time' => $defaultTimeLabel,
                                'status' => $sessionStatus,
                                'status_class' => $statusClass,
                                'sort_key' => $sessionStart->timestamp,
                                'is_rescheduled' => false,
                                'is_override' => false,
                                'date' => $sessionDateKey,
                                'is_past' => $sessionIsPast,
                            ]);
                        }
                    }

                    if ($sessions->isNotEmpty()) {
                        $sessions = $sessions->sortBy('sort_key')->values();
                    }

                    $actualSessions = $sessions->filter(function ($session) {
                        return empty($session['is_rescheduled']);
                    })->values();

                    $dateLabels = $actualSessions->pluck('short')->unique()->values();
                    $rangeLabel = '—';
                    if ($actualSessions->isNotEmpty()) {
                        $firstDate = \Carbon\Carbon::parse($actualSessions->first()['date'])->format('F j, Y');
                        $lastDate = \Carbon\Carbon::parse($actualSessions->last()['date'])->format('F j, Y');
                        $rangeLabel = $firstDate === $lastDate ? $firstDate : $firstDate . ' → ' . $lastDate;
                    }

                    return [
                        'sessions' => $sessions,
                        'actual_sessions' => $actualSessions,
                        'labels' => $dateLabels,
                        'range' => $rangeLabel,
                    ];
                };
                $mapTrainer = function ($item) {
                    $name = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''));
                    $trainerSchedules = collect($item->trainerSchedules ?? []);
                    $salaryEligibleSchedules = $trainerSchedules->filter(function ($schedule) {
                        if (is_null($schedule->trainer_rate_per_hour)) {
                            return false;
                        }

                        if (isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1) {
                            return false;
                        }

                        return !empty($schedule->class_start_date) && !empty($schedule->class_end_date);
                    });

                    $totalSalary = $salaryEligibleSchedules->sum(function ($schedule) {
                        $start = $schedule->class_start_date ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                        $end = $schedule->class_end_date ? \Carbon\Carbon::parse($schedule->class_end_date) : null;

                        if (!$start || !$end || !$end->greaterThan($start)) {
                            return 0;
                        }

                        $hours = $end->diffInMinutes($start) / 60;

                        return (float) $schedule->trainer_rate_per_hour * $hours;
                    });

                    $now = now();
                    $hasUpcoming = $trainerSchedules->contains(function ($schedule) use ($now) {
                        $start = $schedule->class_start_date ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                        return $start ? $start->greaterThan($now) : false;
                    });
                    $statusLabel = $hasUpcoming ? 'Assigned' : 'No upcoming classes';

                    return [
                        'id' => $item->user_code ?? $item->id,
                        'name' => $name ?: '—',
                        'phone' => $item->phone_number ?: '—',
                        'email' => $item->email ?: '—',
                        'salary' => $totalSalary > 0 ? number_format($totalSalary, 2) : null,
                        'status' => $statusLabel,
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('F j, Y g:iA') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('F j, Y g:iA') : '',
                        'created_by' => $item->created_by ?: '',
                    ];
                };

                $printTrainers = collect($printSource->items() ?? [])->map($mapTrainer)->values();
                $printAllTrainers = collect($printAllSource ?? [])->map($mapTrainer)->values();

                $printUser = auth()->user();
                $printUserName = $printUser
                    ? trim(($printUser->first_name ?? '') . ' ' . ($printUser->last_name ?? ''))
                    : '';
                if ($printUser && $printUserName === '') {
                    $printUserName = $printUser->name ?? $printUser->email ?? '';
                }
                $printUserRole = $printUser ? optional($printUser->role)->name : null;
                $printGeneratedBy = $printUserName !== '' ? $printUserName : '—';
                if ($printUserRole) {
                    $printGeneratedBy .= " ({$printUserRole})";
                }

                $printPayload = [
                    'title' => $showArchived ? 'Archived trainers' : 'Trainer directory',
                    'generated_at' => now()->format('F j, Y g:iA'),
                    'meta' => [
                        'generated_by' => $printGeneratedBy,
                    ],
                    'filters' => [
                        'search' => request('name'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printTrainers->count(),
                    'items' => $printTrainers,
                ];

                $printAllPayload = [
                    'title' => $showArchived ? 'Archived trainers (all pages)' : 'Trainer directory (all pages)',
                    'generated_at' => now()->format('F j, Y g:iA'),
                    'meta' => [
                        'generated_by' => $printGeneratedBy,
                    ],
                    'filters' => [
                        'search' => request('name'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                        'scope' => 'all',
                    ],
                    'count' => $printAllTrainers->count(),
                    'items' => $printAllTrainers,
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Trainer Management</h2>
                </div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.trainer-management.add') }}"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add</a>
                    <form action="{{ route('admin.trainer-management.print') }}" method="POST" id="print-form" class="ms-2">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <button
                            class="btn btn-danger"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered trainers"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                    @if ($showArchived ?? request()->boolean('show_archived'))
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.trainer-management.index', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.trainer-management.index', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>
            </div>

            @php
                $showArchived = request()->boolean('show_archived');
                $trainerStatus = $statusFilter ?? request('status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All trainers',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'assigned' => [
                        'label' => 'Assigned to classes',
                        'count' => $statusTallies['assigned'] ?? null,
                    ],
                    'unassigned' => [
                        'label' => 'No upcoming classes',
                        'count' => $statusTallies['unassigned'] ?? null,
                    ],
                ];
                $advancedFiltersOpen = request()->filled('start_date') || request()->filled('end_date');
            @endphp

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Trainer directory</h4>
                                <p class="text-muted mb-0">Identify trainers with active class assignments or narrow results by specific criteria.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived trainers
                                    @else
                                        Showing {{ $trainers->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.trainer-management.index') }}" method="GET" id="trainer-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="trainer-status-filter" value="{{ $trainerStatus }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $trainerStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                            data-status="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
                                            @if(!is_null($option['count']))
                                                <span class="badge bg-transparent {{ $trainerStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $option['count'] }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 240px;">
                                        <div class="position-relative">
                                            <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                            <input
                                                type="search"
                                                class="form-control rounded-pill ps-5"
                                                name="name"
                                                placeholder="Search name, code, email, phone"
                                                value="{{ request('name') }}"
                                                aria-label="Search trainers"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.trainer-management.index', ['show_archived' => 1]) : route('admin.trainer-management.index') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#trainerFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="trainerFiltersModal" tabindex="-1" aria-labelledby="trainerFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="trainerFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>


                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Date range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="start-date" class="form-label small text-muted mb-1">Start date</label>
                                                            <input
                                                                type="date"
                                                                id="start-date"
                                                                class="form-control rounded-3"
                                                                name="start_date"
                                                                value="{{ request('start_date') }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="end-date" class="form-label small text-muted mb-1">End date</label>
                                                            <input
                                                                type="date"
                                                                id="end-date"
                                                                class="form-control rounded-3"
                                                                name="end_date"
                                                                value="{{ request('end_date') }}"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fa-solid fa-magnifying-glass me-2"></i>Apply filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 my-3">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @php
                    $actionFeedbackMessage = session('success') ?? session('error');
                    $actionFeedbackIsError = session()->has('error');
                @endphp
                @if ($actionFeedbackMessage)
                    <div class="modal fade" id="actionFeedbackModal" tabindex="-1" aria-labelledby="actionFeedbackModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-body p-4 text-center">
                                    <div class="display-5 mb-3 {{ $actionFeedbackIsError ? 'text-danger' : 'text-success' }}">
                                        <i class="fa-solid {{ $actionFeedbackIsError ? 'fa-circle-exclamation' : 'fa-circle-check' }}"></i>
                                    </div>
                                    <h5 class="fw-semibold mb-2" id="actionFeedbackModalLabel">
                                        {{ $actionFeedbackIsError ? 'Something went wrong' : 'Action completed' }}
                                    </h5>
                                    <p class="text-muted mb-0">{{ $actionFeedbackMessage }}</p>
                                </div>
                                <div class="modal-footer border-0 justify-content-center pb-4 pt-0">
                                    <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Got it</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if (!$showArchived)
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id"># <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="user_code">User Code <i class="fa fa-sort"></i></th>
                                            {{-- <th class="sortable" data-column="membership_name">Membership Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="expiration_date">Membership Expiration Date <i class="fa fa-sort"></i></th> --}}
                                            <th class="sortable" data-column="name">Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="phone_number">Phone Number <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="email">Email <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_by">Created By <i class="fa fa-sort"></i></th>
                                            <th>Assignments</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @forelse($trainers as $item)
                                            @php
                                                $createdAt = $item->created_at ? \Carbon\Carbon::parse($item->created_at) : null;
                                                $updatedAt = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at) : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>
                                                    <span class="text-muted small">{{ $item->user_code ?? '—' }}</span>
                                                </td>
                                                {{-- <td>
                                                    {{ 
                                                        optional($item->membershipPayments()
                                                            ->where('isapproved', 1)
                                                            ->where('expiration_at', '>=', $current_time)
                                                            ->orderBy('created_at', 'desc')
                                                            ->first()
                                                        )->membership->name ?? 'No Membership' 
                                                    }}
                                                </td>
                                                <td>
                                                    {{ 
                                                        optional($item->membershipPayments()
                                                            ->where('isapproved', 1)
                                                            ->where('expiration_at', '>=', $current_time)
                                                            ->orderBy('created_at', 'desc')
                                                            ->first()
                                                        )->expiration_at ?? 'No Expiration Date' 
                                                    }}
                                                </td> --}}
                                                <td>{{ $item->first_name }} {{ $item->last_name }}</td>
                                                <td>{{ $item->phone_number }}</td>
                                                <td>{{ $item->email }}</td>
                                                @php
                                                    $latestMembershipPayment = $item->membershipPayments()
                                                        ->where('isapproved', 1)
                                                        ->where('expiration_at', '>=', now())
                                                        ->where('is_archive', 0)
                                                        ->with('membership')
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                                                    $membership = optional($latestMembershipPayment)->membership;
                                                    $paymentIsArchived = (int) optional($latestMembershipPayment)->is_archive === 1;
                                                    $membershipIsArchived = (int) optional($membership)->is_archive === 1;
                                                    $membershipActive = $latestMembershipPayment && !$paymentIsArchived && $membership && !$membershipIsArchived;
                                                    $membershipDaysRemaining = null;
                                                    if ($membershipActive && $latestMembershipPayment && $latestMembershipPayment->expiration_at) {
                                                        $expirationDate = \Carbon\Carbon::parse($latestMembershipPayment->expiration_at);
                                                        $membershipDaysRemaining = max(0, now()->diffInDays($expirationDate, false));
                                                    }
                                                    $trainerSchedules = collect($item->trainerSchedules ?? []);

                                                    $salaryEligibleSchedules = $trainerSchedules->filter(function ($schedule) {
                                                        if (is_null($schedule->trainer_rate_per_hour)) {
                                                            return false;
                                                        }

                                                        if (isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1) {
                                                            return false;
                                                        }

                                                        return !empty($schedule->class_start_date) && !empty($schedule->class_end_date);
                                                    });

                                                    $totalSalary = $salaryEligibleSchedules->sum(function ($schedule) {
                                                        $start = $schedule->class_start_date ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                        $end = $schedule->class_end_date ? \Carbon\Carbon::parse($schedule->class_end_date) : null;

                                                        if (!$start || !$end || !$end->greaterThan($start)) {
                                                            return 0;
                                                        }

                                                        $hours = $end->diffInMinutes($start) / 60;

                                                        return (float) $schedule->trainer_rate_per_hour * $hours;
                                                    });

                                                    $now = \Carbon\Carbon::now();

                                                    $scheduleDetails = $trainerSchedules
                                                        ->map(function ($schedule) use ($now, $buildScheduleSeries) {
                                                            $start = !empty($schedule->class_start_date) ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                            $end = !empty($schedule->class_end_date) ? \Carbon\Carbon::parse($schedule->class_end_date) : null;
                                                            $seriesStart = !empty($schedule->series_start_date)
                                                                ? \Carbon\Carbon::parse($schedule->series_start_date)->startOfDay()
                                                                : ($start ? $start->copy()->startOfDay() : null);
                                                            $seriesEnd = !empty($schedule->series_end_date)
                                                                ? \Carbon\Carbon::parse($schedule->series_end_date)->endOfDay()
                                                                : ($end ? $end->copy()->endOfDay() : null);

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
                                                                $displayName = $fullName !== '' ? $fullName : ($user->email ?? null);
                                                                $memberCode = $user->user_code ?? null;

                                                                if ($displayName && $memberCode) {
                                                                    return "{$displayName} ({$memberCode})";
                                                                }

                                                                if ($displayName) {
                                                                    return $displayName;
                                                                }

                                                                if ($memberCode) {
                                                                    return $memberCode;
                                                                }

                                                                return null;
                                                            })->filter()->unique()->values();

                                                            $seriesPreview = $buildScheduleSeries($schedule, $start, $end, $now, collect());
                                                            $previewSessions = $seriesPreview['actual_sessions'] ?? collect();
                                                            $hasUpcomingSession = $previewSessions->contains(function ($session) {
                                                                return empty($session['is_past']);
                                                            });
                                                            $hasSessionHistory = $previewSessions->isNotEmpty();

                                                            if ($hasSessionHistory) {
                                                                $category = $hasUpcomingSession ? 'future' : 'past';
                                                            } else {
                                                                $isPast = false;
                                                                if ($seriesEnd) {
                                                                    $isPast = $seriesEnd->lt($now);
                                                                } elseif ($end) {
                                                                    $isPast = $end->lt($now);
                                                                } elseif ($start) {
                                                                    $isPast = $start->lt($now);
                                                                }
                                                                $category = $isPast ? 'past' : 'future';
                                                            }

                                                            return [
                                                                'schedule' => $schedule,
                                                                'start' => $start,
                                                                'end' => $end,
                                                                'series_start' => $seriesStart,
                                                                'series_end' => $seriesEnd,
                                                                'start_date' => $seriesStart ? $seriesStart->toDateString() : ($start ? $start->toDateString() : null),
                                                                'end_date' => $seriesEnd ? $seriesEnd->toDateString() : ($end ? $end->toDateString() : null),
                                                                'hours' => $hours,
                                                                'display_salary' => $displaySalary,
                                                                'summary_salary' => $summarySalary,
                                                                'salary_eligible' => $isSalaryEligible,
                                                                'students' => $students,
                                                                'category' => $category,
                                                            ];
                                                        })
                                                        ->sortBy(function ($detail) {
                                                            return $detail['start'] ? $detail['start']->getTimestamp() : PHP_INT_MAX;
                                                        })
                                                        ->values();

                                                    $attendanceStartCandidates = $scheduleDetails
                                                        ->pluck('series_start')
                                                        ->filter()
                                                        ->sortBy(function ($date) {
                                                            return $date instanceof \Carbon\Carbon ? $date->timestamp : PHP_INT_MAX;
                                                        })
                                                        ->values();
                                                    $attendanceEndCandidates = $scheduleDetails
                                                        ->pluck('series_end')
                                                        ->filter()
                                                        ->sortByDesc(function ($date) {
                                                            return $date instanceof \Carbon\Carbon ? $date->timestamp : 0;
                                                        })
                                                        ->values();
                                                    $attendanceWindowStart = $attendanceStartCandidates->first();
                                                    $attendanceWindowEnd = $attendanceEndCandidates->first();
                                                    $trainerAttendances = collect();
                                                    if ($attendanceWindowStart && $attendanceWindowEnd) {
                                                        $windowStart = $attendanceWindowStart->copy()->startOfDay();
                                                        $windowEnd = $attendanceWindowEnd->copy()->endOfDay();
                                                        $trainerAttendances = \App\Models\Attendance2::where('user_id', $item->id)
                                                            ->where('is_archive', 0)
                                                            ->where(function ($query) use ($windowStart, $windowEnd) {
                                                                $query->whereBetween('clockin_at', [$windowStart, $windowEnd])
                                                                    ->orWhereBetween('clockout_at', [$windowStart, $windowEnd])
                                                                    ->orWhereBetween('created_at', [$windowStart, $windowEnd]);
                                                            })
                                                            ->get()
                                                            ->map(function ($attendance) {
                                                                return [
                                                                    'clockin' => $attendance->clockin_at ? \Carbon\Carbon::parse($attendance->clockin_at) : null,
                                                                    'clockout' => $attendance->clockout_at ? \Carbon\Carbon::parse($attendance->clockout_at) : null,
                                                                ];
                                                            });
                                                    }

                                                    $futureScheduleDetails = $scheduleDetails->filter(function ($detail) {
                                                        return $detail['category'] === 'future';
                                                    });

                                                    $pastScheduleDetails = $scheduleDetails->filter(function ($detail) {
                                                        return $detail['category'] === 'past';
                                                    });

                                                    $futureScheduleCount = $futureScheduleDetails->count();
                                                    $pastScheduleCount = $pastScheduleDetails->count();
                                                    $assignmentLabel = $futureScheduleCount > 0 ? 'Assigned' : 'No upcoming classes';
                                                    $assignmentActive = $futureScheduleCount > 0;
                                                    $profilePicture = $item->profile_picture
                                                        ? asset($item->profile_picture)
                                                        : asset('assets/images/profile-45x45.png');
                                                    $trainerCode = $item->user_code ?: $item->id;
                                                @endphp
                                                <td>{{ $createdAt ? $createdAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $updatedAt ? $updatedAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $item->created_by }}</td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-sm rounded-pill"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#assignmentsModal-{{ $item->id }}"
                                                    >
                                                        View assignments
                                                    </button>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Manual attendance actions">
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-success manual-clock-button"
                                                                data-email="{{ $item->email }}"
                                                                data-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                                data-phone="{{ $item->phone_number }}"
                                                                data-member-code="{{ $trainerCode }}"
                                                                data-membership="{{ $assignmentLabel }}"
                                                                data-membership-active="{{ $assignmentActive ? '1' : '0' }}"
                                                                data-membership-days="{{ $membershipDaysRemaining ?? '' }}"
                                                                data-avatar="{{ $profilePicture }}"
                                                                data-action="clockin"
                                                            >
                                                                <i class="fa-regular fa-clock me-1"></i>Clock In
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-secondary manual-clock-button"
                                                                data-email="{{ $item->email }}"
                                                                data-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                                data-phone="{{ $item->phone_number }}"
                                                                data-member-code="{{ $trainerCode }}"
                                                                data-membership="{{ $assignmentLabel }}"
                                                                data-membership-active="{{ $assignmentActive ? '1' : '0' }}"
                                                                data-membership-days="{{ $membershipDaysRemaining ?? '' }}"
                                                                data-avatar="{{ $profilePicture }}"
                                                                data-action="clockout"
                                                            >
                                                                <i class="fa-solid fa-right-from-bracket me-1"></i>Clock Out
                                                            </button>
                                                        </div>

                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="action-button"><a href="{{ route('admin.trainer-management.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                            <div class="action-button"><a href="{{ route('admin.trainer-management.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                            <div class="action-button">
                                                                <!--<form action="{{ route('admin.trainer-management.delete') }}" method="POST" style="display: inline;">-->
                                                                <!--    @csrf-->
                                                                <!--    @method('DELETE')-->
                                                                <!--    <input type="hidden" name="id" value="{{ $item->id }}">-->
                                                                <!--    <button type="submit" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">-->
                                                                <!--        <i class="fa-solid fa-trash text-danger"></i>-->
                                                                <!--    </button>-->
                                                                <!--</form>-->
                                                                <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Archive" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                    <i class="fa-solid fa-box-archive text-danger"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php
                                                $totalAssignments = $scheduleDetails->count();
                                                $futureHours = $futureScheduleDetails->sum('hours');
                                                $pastHours = $pastScheduleDetails->sum('hours');

                                                $assignmentPrintPayload = [
                                                    'title' => 'Assignments for ' . trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')),
                                                    'trainer' => [
                                                        'name' => trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')),
                                                        'email' => $item->email ?? '',
                                                        'phone' => $item->phone_number ?? '',
                                                        'code' => $item->user_code ?? $item->id,
                                                    ],
                                                    'generated_at' => now()->format('F j, Y g:iA'),
                                                    'generated_by' => $printGeneratedBy ?? null,
                                                    'summary' => [
                                                        'total' => $totalAssignments,
                                                        'future' => $futureScheduleCount,
                                                        'past' => $pastScheduleCount,
                                                        'future_hours' => (float) $futureHours,
                                                        'past_hours' => (float) $pastHours,
                                                    ],
                                                    'items' => $scheduleDetails->map(function ($detail) use ($buildScheduleSeries, $trainerAttendances, $now) {
                                                        $schedule = $detail['schedule'];
                                                        $start = $detail['start'];
                                                        $end = $detail['end'];
                                                        $students = $detail['students'] ?? collect();
                                                        $categoryLabel = $detail['category'] === 'past' ? 'Past' : 'Upcoming';
                                                        $seriesData = $buildScheduleSeries($schedule, $start, $end, $now, $trainerAttendances);
                                                        $seriesSessions = $seriesData['sessions'] ?? collect();

                                                        return [
                                                            'name' => $schedule->name ?? 'Unnamed Schedule',
                                                            'class_code' => $schedule->class_code ?? null,
                                                            'category' => $detail['category'],
                                                            'category_label' => $categoryLabel,
                                                            'start_label' => $start ? $start->format('F j, Y g:iA') : 'Not set',
                                                            'end_label' => $end ? $end->format('F j, Y g:iA') : '—',
                                                            'start_date' => $detail['start_date'] ?? null,
                                                            'end_date' => $detail['end_date'] ?? null,
                                                            'hours' => isset($detail['hours']) ? (float) $detail['hours'] : null,
                                                            'students' => collect($students)->values()->all(),
                                                            'series_range' => $seriesData['range'] ?? null,
                                                            'series_sessions' => $seriesSessions->map(function ($session) {
                                                                return [
                                                                    'label' => $session['label'] ?? '',
                                                                    'weekday' => $session['weekday'] ?? '',
                                                                    'time' => $session['time'] ?? '',
                                                                    'status' => $session['status'] ?? '',
                                                                ];
                                                            })->values()->all(),
                                                        ];
                                                    })->values(),
                                                    'filters' => [
                                                        'category' => 'all',
                                                        'start' => null,
                                                        'end' => null,
                                                    ],
                                                ];
                                            @endphp
                                            <div class="modal fade assignment-modal" data-assignment-modal id="assignmentsModal-{{ $item->id }}" tabindex="-1" aria-labelledby="assignmentsModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                                        <div class="modal-header border-0 pb-0">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex flex-wrap align-items-start gap-2">
                                                                    <div>
                                                                        <h5 class="modal-title fw-semibold mb-1" id="assignmentsModalLabel-{{ $item->id }}">Assignments Summary - {{ $item->first_name }} {{ $item->last_name }}</h5>
                                                                        <div class="text-muted small">Updated: {{ $assignmentPrintPayload['generated_at'] }}</div>
                                                                    </div>
                                                                    <div class="ms-auto text-end">
                                                                        <span class="text-muted small">Assignments list - filters enabled</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body pt-2">
                                                            @if($scheduleDetails->isNotEmpty())
                                                                @php
                                                                    $totalAssignmentHours = $futureHours + $pastHours;
                                                                @endphp
                                                                <div class="payroll-summary-card mb-3">
                                                                    <div class="summary-grid">
                                                                        <div class="summary-item">
                                                                            <div class="summary-label">Total</div>
                                                                            <div class="summary-value" data-role="total-count">
                                                                                {{ $totalAssignments }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="summary-item">
                                                                            <div class="summary-label">Upcoming</div>
                                                                            <div class="summary-value" data-role="future-count">
                                                                                {{ $futureScheduleCount }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="summary-item">
                                                                        <div class="summary-label">Completed</div>
                                                                            <div class="summary-value" data-role="past-count">
                                                                                {{ $pastScheduleCount }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="summary-item">
                                                                            <div class="summary-label">Total hours</div>
                                                                            <div class="summary-value" data-role="total-hours">{{ $formatAssignmentDuration($totalAssignmentHours) }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="filter-card p-3 mb-3">
                                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Assignment category filter">
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="all">All</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="future">Upcoming</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="past">Complete</button>
                                                                        </div>
                                                                        <button type="button" class="btn btn-link btn-sm ms-auto text-decoration-none px-0" data-filter-reset>Reset filters</button>
                                                                    </div>
                                                                    <div class="row g-2 mt-3">
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label text-muted text-uppercase small mb-1">Start date from</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-start>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label text-muted text-uppercase small mb-1">End date until</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-end>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="assignment-list">
                                                                    <div class="payroll-table p-3">
                                                                        <div class="d-none d-lg-flex fw-semibold text-muted text-uppercase small pb-2 border-bottom assignment-table-head">
                                                                            <div class="assignment-col col-code">Code</div>
                                                                            <div class="assignment-col flex-grow-1">Type</div>
                                                                            <div class="assignment-col col-rate">Rate/hr</div>
                                                                            <div class="assignment-col col-date">Date</div>
                                                                            <div class="assignment-col col-series">Series of sessions</div>
                                                                            <div class="assignment-col col-students">Students</div>
                                                                        </div>
                                                                        @foreach($scheduleDetails as $detailIndex => $detail)
                                                                            @php
                                                                                $schedule = $detail['schedule'];
                                                                                $start = $detail['start'];
                                                                                $end = $detail['end'];
                                                                                $students = $detail['students'];
                                                                                $category = $detail['category'];
                                                                                $hoursBadgeClass = $category === 'future' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                                                                                $hours = $detail['hours'];
                                                                                $hoursLabel = $formatAssignmentDuration($hours);
                                                                                $codeLabel = $schedule->class_code ?? '—';
                                                                                $rateValue = $schedule->trainer_rate_per_hour ?? null;
                                                                                $rateLabel = $rateValue !== null ? '₱' . number_format((float) $rateValue, 2) : '—';
                                                                                $seriesData = $buildScheduleSeries($schedule, $start, $end, $now, $trainerAttendances);
                                                                                $seriesSessions = $seriesData['sessions'] ?? collect();
                                                                                $actualSeriesSessions = $seriesData['actual_sessions'] ?? collect();
                                                                                $dateLabels = $seriesData['labels'] ?? collect();
                                                                                $dateRangeLabel = $seriesData['range'] ?? '—';
                                                                                $dateList = $dateLabels->isNotEmpty()
                                                                                    ? $dateLabels
                                                                                    : collect([$start ? $start->format('M d') : '—']);
                                                                                $dateDisplay = 'range';
                                                                                $studentsPayload = $students->values()->all();
                                                                            @endphp
                                                                            <div
                                                                                class="assignment-row d-flex flex-column flex-lg-row gap-3 py-3 border-bottom"
                                                                                data-assignment-card
                                                                                data-date-display="{{ $dateDisplay }}"
                                                                                data-category="{{ $category }}"
                                                                                data-start="{{ $detail['start_date'] ?? '' }}"
                                                                                data-end="{{ $detail['end_date'] ?? '' }}"
                                                                                data-hours="{{ $hours }}"
                                                                            >
                                                                                <div class="assignment-col col-code">
                                                                                    <span class="badge bg-light text-muted border">{{ $codeLabel }}</span>
                                                                                </div>
                                                                                <div class="assignment-col flex-grow-1">
                                                                                    <div class="fw-semibold">{{ $schedule->name ?? 'Unnamed Schedule' }}</div>
                                                                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                                                                        <span class="assignment-hours-badge {{ $hoursBadgeClass }}">{{ $hoursLabel }}</span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="assignment-col col-rate">
                                                                                    @if($rateValue !== null)
                                                                                        <div class="fw-semibold">{{ $rateLabel }}</div>
                                                                                        <div class="text-muted small">per hr</div>
                                                                                    @else
                                                                                        <div class="text-muted small">—</div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="assignment-col col-date text-muted small">
                                                                                    <div class="assignment-date-list">
                                                                                        @foreach($dateList as $dateItem)
                                                                                            <div>• {{ $dateItem }}</div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                    <div class="assignment-date-range">{{ $dateRangeLabel }}</div>
                                                                                </div>
                                                                                <div class="assignment-col col-series">
                                                                                    @if($seriesSessions->isNotEmpty())
                                                                                        @php
                                                                                            $seriesCollapseId = 'assignment-series-' . $item->id . '-' . $detailIndex;
                                                                                        @endphp
                                                                                        <div class="series-sessions">
                                                                                            <div class="series-header">
                                                                                                <button
                                                                                                    type="button"
                                                                                                    class="btn btn-link btn-sm px-0 session-toggle"
                                                                                                    data-bs-toggle="collapse"
                                                                                                    data-bs-target="#{{ $seriesCollapseId }}"
                                                                                                    aria-expanded="false"
                                                                                                    aria-controls="{{ $seriesCollapseId }}"
                                                                                                    data-session-toggle
                                                                                                    data-collapsed-text="Show all sessions"
                                                                                                    data-expanded-text="Hide sessions"
                                                                                                >
                                                                                                    Show all sessions
                                                                                                </button>
                                                                                            </div>
                                                                                            <div class="collapse" id="{{ $seriesCollapseId }}">
                                                                                                <div class="series-panel">
                                                                                                    <div class="d-flex flex-column">
                                                                                                        @foreach($seriesSessions as $sessionIndex => $session)
                                                                                                            @php
                                                                                                                $isRescheduled = !empty($session['is_rescheduled']);
                                                                                                                $rescheduleTarget = $session['reschedule_target_label'] ?? null;
                                                                                                                $rescheduledFrom = $session['rescheduled_from'] ?? null;
                                                                                                            @endphp
                                                                                                            <div class="series-item">
                                                                                                                <div class="series-dot">
                                                                                                                    <span class="dot"></span>
                                                                                                                    @if($sessionIndex < $seriesSessions->count() - 1)
                                                                                                                        <span class="line"></span>
                                                                                                                    @endif
                                                                                                                </div>
                                                                                                                <div>
                                                                                                                    <div class="fw-semibold {{ $isRescheduled ? 'text-decoration-line-through text-muted' : '' }}">
                                                                                                                        {{ $session['label'] ?? '—' }}
                                                                                                                    </div>
                                                                                                                    <div class="text-muted small">
                                                                                                                        {{ $session['weekday'] ?? '' }}
                                                                                                                        @if(!empty($session['time']))
                                                                                                                            • {{ $session['time'] }}
                                                                                                                        @endif
                                                                                                                    </div>
                                                                                                                    @if($isRescheduled && $rescheduleTarget)
                                                                                                                        <div class="text-muted small fst-italic">→ {{ $rescheduleTarget }}</div>
                                                                                                                    @elseif(!$isRescheduled && $rescheduledFrom)
                                                                                                                        <div class="text-muted small fst-italic">From {{ $rescheduledFrom }}</div>
                                                                                                                    @endif
                                                                                                                    <span class="badge {{ $session['status_class'] ?? 'bg-secondary' }} px-2 py-1">{{ $session['status'] ?? '' }}</span>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        @endforeach
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @else
                                                                                        <span class="text-muted small">Series not set</span>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="assignment-col col-students">
                                                                                    @if($students->isNotEmpty())
                                                                                        <button
                                                                                            class="btn btn-light btn-sm students-toggle"
                                                                                            type="button"
                                                                                            data-students='@json($studentsPayload)'
                                                                                            data-students-title="{{ $schedule->name ?? 'Students' }}"
                                                                                        >
                                                                                            See more ({{ $students->count() }})
                                                                                        </button>
                                                                                    @else
                                                                                        <span class="text-muted small">No students</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="assignment-pagination d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3" data-pagination>
                                                                    <div class="text-muted small" data-page-status>Showing 0 to 0 of 0 assignments</div>
                                                                    <nav aria-label="Assignment pagination" style="height: auto !important;margin: 0px !important">
                                                                        <ul class="pagination pagination-sm mb-0" data-page-list>
                                                                            <li class="page-item" data-page-prev-item>
                                                                                <button type="button" class="page-link" data-page-prev aria-label="Previous">‹</button>
                                                                            </li>
                                                                            <li class="page-item" data-page-next-item>
                                                                                <button type="button" class="page-link" data-page-next aria-label="Next">›</button>
                                                                            </li>
                                                                        </ul>
                                                                    </nav>
                                                                </div>
                                                            @else
                                                                <div class="filter-card p-4 text-center text-muted">
                                                                    No schedules assigned.
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button
                                                                type="button"
                                                                class="btn btn-danger"
                                                                data-print-modal="assignmentsModal-{{ $item->id }}"
                                                                data-print='@json($assignmentPrintPayload)'
                                                            >
                                                                <i class="fa-solid fa-print me-1"></i>Print
                                                                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" data-print-loader></span>
                                                            </button>
                                                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow rounded-4">
                                                        <div class="modal-header border-0 pb-0">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                                </div>
                                                                <div>
                                                                    <p class="text-uppercase text-muted small mb-1">Archive trainer</p>
                                                                    <h5 class="fw-semibold mb-0" id="deleteModalLabel-{{ $item->id }}">
                                                                        {{ $item->first_name }} {{ $item->last_name }} ({{ $item->email }})
                                                                    </h5>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.trainer-management.delete') }}" method="POST" id="main-form-{{ $item->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body pt-3">
                                                                <div class="alert alert-danger bg-opacity-10 text-danger border-0 rounded-3">
                                                                    Archiving will move this trainer to the archived list. You can restore them later if needed.
                                                                </div>
                                                                <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                                <div class="input-group">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                    <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Archive trainer
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('main-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                    const loader = document.getElementById('loader-{{ $item->id }}');
                                        
                                                    // Disable the button and show loader
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No trainers found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="text-muted small">
                                    Showing {{ $trainers->firstItem() ?? 0 }} to {{ $trainers->lastItem() ?? 0 }} of {{ $trainers->total() }} results
                                </div>
                                <div class="ms-auto">
                                    {{ $trainers->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if ($showArchived)
                <div class="box mt-5">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h4 class="fw-semibold mb-0">Archived Trainers</h4>
                                <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>User Code</th>
                                            <th>Name</th>
                                            <th>Phone Number</th>
                                            <th>Email</th>
                                            <th>Estimated Salary</th>
                                            <th>Created Date</th>
                                            <th>Updated Date</th>
                                            <th>Assignments</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($archivedData as $archive)
                                            @php
                                                $archiveCreated = $archive->created_at ? \Carbon\Carbon::parse($archive->created_at) : null;
                                                $archiveUpdated = $archive->updated_at ? \Carbon\Carbon::parse($archive->updated_at) : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $archive->id }}</td>
                                                <td>
                                                    <span class="text-muted small">{{ $archive->user_code ?? '—' }}</span>
                                                </td>
                                                <td>{{ $archive->first_name }} {{ $archive->last_name }}</td>
                                                <td>{{ $archive->phone_number }}</td>
                                                <td>{{ $archive->email }}</td>
                                                @php
                                                    $archivedSchedules = collect($archive->trainerSchedules ?? []);
                                                    $archivedSalaryEligible = $archivedSchedules->filter(function ($schedule) {
                                                        if (is_null($schedule->trainer_rate_per_hour)) {
                                                            return false;
                                                        }

                                                        if (isset($schedule->is_archieve) && (int) $schedule->is_archieve === 1) {
                                                            return false;
                                                        }

                                                        return !empty($schedule->class_start_date) && !empty($schedule->class_end_date);
                                                    });
                                                    $archivedTotalSalary = $archivedSalaryEligible->sum(function ($schedule) {
                                                        $start = $schedule->class_start_date ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                        $end = $schedule->class_end_date ? \Carbon\Carbon::parse($schedule->class_end_date) : null;

                                                        if (!$start || !$end || !$end->greaterThan($start)) {
                                                            return 0;
                                                        }

                                                        $hours = $end->diffInMinutes($start) / 60;

                                                        return (float) $schedule->trainer_rate_per_hour * $hours;
                                                    });

                                                    $archivedNow = \Carbon\Carbon::now();

                                                    $archivedScheduleDetails = $archivedSchedules
                                                        ->map(function ($schedule) use ($archivedNow, $buildScheduleSeries) {
                                                            $start = !empty($schedule->class_start_date) ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                            $end = !empty($schedule->class_end_date) ? \Carbon\Carbon::parse($schedule->class_end_date) : null;
                                                            $seriesStart = !empty($schedule->series_start_date)
                                                                ? \Carbon\Carbon::parse($schedule->series_start_date)->startOfDay()
                                                                : ($start ? $start->copy()->startOfDay() : null);
                                                            $seriesEnd = !empty($schedule->series_end_date)
                                                                ? \Carbon\Carbon::parse($schedule->series_end_date)->endOfDay()
                                                                : ($end ? $end->copy()->endOfDay() : null);

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
                                                                $displayName = $fullName !== '' ? $fullName : ($user->email ?? null);
                                                                $memberCode = $user->user_code ?? null;

                                                                if ($displayName && $memberCode) {
                                                                    return "{$displayName} ({$memberCode})";
                                                                }

                                                                if ($displayName) {
                                                                    return $displayName;
                                                                }

                                                                if ($memberCode) {
                                                                    return $memberCode;
                                                                }

                                                                return null;
                                                            })->filter()->unique()->values();

                                                            $seriesPreview = $buildScheduleSeries($schedule, $start, $end, $archivedNow, collect());
                                                            $previewSessions = $seriesPreview['actual_sessions'] ?? collect();
                                                            $hasUpcomingSession = $previewSessions->contains(function ($session) {
                                                                return empty($session['is_past']);
                                                            });
                                                            $hasSessionHistory = $previewSessions->isNotEmpty();

                                                            if ($hasSessionHistory) {
                                                                $category = $hasUpcomingSession ? 'future' : 'past';
                                                            } else {
                                                                $isPast = false;
                                                                if ($seriesEnd) {
                                                                    $isPast = $seriesEnd->lt($archivedNow);
                                                                } elseif ($end) {
                                                                    $isPast = $end->lt($archivedNow);
                                                                } elseif ($start) {
                                                                    $isPast = $start->lt($archivedNow);
                                                                }
                                                                $category = $isPast ? 'past' : 'future';
                                                            }

                                                            return [
                                                                'schedule' => $schedule,
                                                                'start' => $start,
                                                                'end' => $end,
                                                                'series_start' => $seriesStart,
                                                                'series_end' => $seriesEnd,
                                                                'start_date' => $seriesStart ? $seriesStart->toDateString() : ($start ? $start->toDateString() : null),
                                                                'end_date' => $seriesEnd ? $seriesEnd->toDateString() : ($end ? $end->toDateString() : null),
                                                                'hours' => $hours,
                                                                'display_salary' => $displaySalary,
                                                                'summary_salary' => $summarySalary,
                                                                'salary_eligible' => $isSalaryEligible,
                                                                'students' => $students,
                                                                'category' => $category,
                                                            ];
                                                        })
                                                        ->sortBy(function ($detail) {
                                                            return $detail['start'] ? $detail['start']->getTimestamp() : PHP_INT_MAX;
                                                        })
                                                        ->values();

                                                    $archivedAttendanceStartCandidates = $archivedScheduleDetails
                                                        ->pluck('series_start')
                                                        ->filter()
                                                        ->sortBy(function ($date) {
                                                            return $date instanceof \Carbon\Carbon ? $date->timestamp : PHP_INT_MAX;
                                                        })
                                                        ->values();
                                                    $archivedAttendanceEndCandidates = $archivedScheduleDetails
                                                        ->pluck('series_end')
                                                        ->filter()
                                                        ->sortByDesc(function ($date) {
                                                            return $date instanceof \Carbon\Carbon ? $date->timestamp : 0;
                                                        })
                                                        ->values();
                                                    $archivedAttendanceWindowStart = $archivedAttendanceStartCandidates->first();
                                                    $archivedAttendanceWindowEnd = $archivedAttendanceEndCandidates->first();
                                                    $archivedTrainerAttendances = collect();
                                                    if ($archivedAttendanceWindowStart && $archivedAttendanceWindowEnd) {
                                                        $windowStart = $archivedAttendanceWindowStart->copy()->startOfDay();
                                                        $windowEnd = $archivedAttendanceWindowEnd->copy()->endOfDay();
                                                        $archivedTrainerAttendances = \App\Models\Attendance2::where('user_id', $archive->id)
                                                            ->where('is_archive', 0)
                                                            ->where(function ($query) use ($windowStart, $windowEnd) {
                                                                $query->whereBetween('clockin_at', [$windowStart, $windowEnd])
                                                                    ->orWhereBetween('clockout_at', [$windowStart, $windowEnd])
                                                                    ->orWhereBetween('created_at', [$windowStart, $windowEnd]);
                                                            })
                                                            ->get()
                                                            ->map(function ($attendance) {
                                                                return [
                                                                    'clockin' => $attendance->clockin_at ? \Carbon\Carbon::parse($attendance->clockin_at) : null,
                                                                    'clockout' => $attendance->clockout_at ? \Carbon\Carbon::parse($attendance->clockout_at) : null,
                                                                ];
                                                            });
                                                    }

                                                    $archivedFutureDetails = $archivedScheduleDetails->filter(function ($detail) {
                                                        return $detail['category'] === 'future';
                                                    });

                                                    $archivedPastDetails = $archivedScheduleDetails->filter(function ($detail) {
                                                        return $detail['category'] === 'past';
                                                    });

                                                    $archivedFutureCount = $archivedFutureDetails->count();
                                                    $archivedPastCount = $archivedPastDetails->count();
                                                @endphp
                                                <td>
                                                    @if($archivedSalaryEligible->isNotEmpty())
                                                        ₱{{ number_format($archivedTotalSalary, 2) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $archiveCreated ? $archiveCreated->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $archiveUpdated ? $archiveUpdated->format('F j, Y g:iA') : '' }}</td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-sm rounded-pill"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#archiveAssignmentsModal-{{ $archive->id }}"
                                                    >
                                                        View assignments
                                                    </button>
                                                </td>
                                                <td class="action-button">
                                                    <div class="d-flex gap-2">
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#archiveRestoreModal-{{ $archive->id }}" data-id="{{ $archive->id }}" title="Restore" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-rotate-left text-success"></i>
                                                        </button>
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#archiveDeleteModal-{{ $archive->id }}" data-id="{{ $archive->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-trash text-danger"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @php
                                                $archivedTotalAssignments = $archivedScheduleDetails->count();
                                                $archivedFutureHours = $archivedFutureDetails->sum('hours');
                                                $archivedPastHours = $archivedPastDetails->sum('hours');

                                                $archivedAssignmentPrintPayload = [
                                                    'title' => 'Assignments for ' . trim(($archive->first_name ?? '') . ' ' . ($archive->last_name ?? '')),
                                                    'trainer' => [
                                                        'name' => trim(($archive->first_name ?? '') . ' ' . ($archive->last_name ?? '')),
                                                        'email' => $archive->email ?? '',
                                                        'phone' => $archive->phone_number ?? '',
                                                        'code' => $archive->user_code ?? $archive->id,
                                                    ],
                                                    'generated_at' => now()->format('F j, Y g:iA'),
                                                    'generated_by' => $printGeneratedBy ?? null,
                                                    'summary' => [
                                                        'total' => $archivedTotalAssignments,
                                                        'future' => $archivedFutureCount,
                                                        'past' => $archivedPastCount,
                                                        'future_hours' => (float) $archivedFutureHours,
                                                        'past_hours' => (float) $archivedPastHours,
                                                    ],
                                                    'items' => $archivedScheduleDetails->map(function ($detail) use ($buildScheduleSeries, $archivedTrainerAttendances, $archivedNow) {
                                                        $schedule = $detail['schedule'];
                                                        $start = $detail['start'];
                                                        $end = $detail['end'];
                                                        $students = $detail['students'] ?? collect();
                                                        $categoryLabel = $detail['category'] === 'past' ? 'Past' : 'Upcoming';
                                                        $seriesData = $buildScheduleSeries($schedule, $start, $end, $archivedNow, $archivedTrainerAttendances);
                                                        $seriesSessions = $seriesData['sessions'] ?? collect();

                                                        return [
                                                            'name' => $schedule->name ?? 'Unnamed Schedule',
                                                            'class_code' => $schedule->class_code ?? null,
                                                            'category' => $detail['category'],
                                                            'category_label' => $categoryLabel,
                                                            'start_label' => $start ? $start->format('F j, Y g:iA') : 'Not set',
                                                            'end_label' => $end ? $end->format('F j, Y g:iA') : '—',
                                                            'start_date' => $detail['start_date'] ?? null,
                                                            'end_date' => $detail['end_date'] ?? null,
                                                            'hours' => isset($detail['hours']) ? (float) $detail['hours'] : null,
                                                            'students' => collect($students)->values()->all(),
                                                            'series_range' => $seriesData['range'] ?? null,
                                                            'series_sessions' => $seriesSessions->map(function ($session) {
                                                                return [
                                                                    'label' => $session['label'] ?? '',
                                                                    'weekday' => $session['weekday'] ?? '',
                                                                    'time' => $session['time'] ?? '',
                                                                    'status' => $session['status'] ?? '',
                                                                ];
                                                            })->values()->all(),
                                                        ];
                                                    })->values(),
                                                    'filters' => [
                                                        'category' => 'all',
                                                        'start' => null,
                                                        'end' => null,
                                                    ],
                                                ];
                                            @endphp
                                            <div class="modal fade assignment-modal" data-assignment-modal id="archiveAssignmentsModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveAssignmentsModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                                        <div class="modal-header border-0 pb-0">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex flex-wrap align-items-start gap-2">
                                                                    <div>
                                                                        <h5 class="modal-title fw-semibold mb-1" id="archiveAssignmentsModalLabel-{{ $archive->id }}">Assignments Summary - {{ $archive->first_name }} {{ $archive->last_name }}</h5>
                                                                        <div class="text-muted small">Updated: {{ $archivedAssignmentPrintPayload['generated_at'] }}</div>
                                                                    </div>
                                                                    <div class="ms-auto text-end">
                                                                        <span class="text-muted small">Assignments list - filters enabled</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body pt-2">
                                                            @if($archivedScheduleDetails->isNotEmpty())
                                                                @php
                                                                    $archivedTotalAssignmentHours = $archivedFutureHours + $archivedPastHours;
                                                                @endphp
                                                                <div class="payroll-summary-card mb-3">
                                                                    <div class="summary-grid">
                                                                        <div class="summary-item">
                                                                            <div class="summary-label">Total</div>
                                                                            <div class="summary-value" data-role="total-count">
                                                                                {{ $archivedTotalAssignments }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="summary-item">
                                                                            <div class="summary-label">Upcoming</div>
                                                                            <div class="summary-value" data-role="future-count">
                                                                                {{ $archivedFutureCount }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="summary-item">
                                                                        <div class="summary-label">Completed</div>
                                                                            <div class="summary-value" data-role="past-count">
                                                                                {{ $archivedPastCount }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="summary-item">
                                                                            <div class="summary-label">Total hours</div>
                                                                            <div class="summary-value" data-role="total-hours">{{ $formatAssignmentDuration($archivedTotalAssignmentHours) }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="filter-card p-3 mb-3">
                                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Assignment category filter">
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="all">All</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="future">Upcoming</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="past">Complete</button>
                                                                        </div>
                                                                        <button type="button" class="btn btn-link btn-sm ms-auto text-decoration-none px-0" data-filter-reset>Reset filters</button>
                                                                    </div>
                                                                    <div class="row g-2 mt-3">
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label text-muted text-uppercase small mb-1">Start date from</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-start>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label text-muted text-uppercase small mb-1">End date until</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-end>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="assignment-list">
                                                                    <div class="payroll-table p-3">
                                                                        <div class="d-none d-lg-flex fw-semibold text-muted text-uppercase small pb-2 border-bottom assignment-table-head">
                                                                            <div class="assignment-col col-code">Code</div>
                                                                            <div class="assignment-col flex-grow-1">Type</div>
                                                                            <div class="assignment-col col-rate">Rate/hr</div>
                                                                            <div class="assignment-col col-date">Date</div>
                                                                            <div class="assignment-col col-series">Series of sessions</div>
                                                                            <div class="assignment-col col-students">Students</div>
                                                                        </div>
                                                                        @foreach($archivedScheduleDetails as $detailIndex => $detail)
                                                                            @php
                                                                                $schedule = $detail['schedule'];
                                                                                $start = $detail['start'];
                                                                                $end = $detail['end'];
                                                                                $students = $detail['students'];
                                                                                $category = $detail['category'];
                                                                                $hoursBadgeClass = $category === 'future' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                                                                                $hours = $detail['hours'];
                                                                                $hoursLabel = $formatAssignmentDuration($hours);
                                                                                $codeLabel = $schedule->class_code ?? '—';
                                                                                $rateValue = $schedule->trainer_rate_per_hour ?? null;
                                                                                $rateLabel = $rateValue !== null ? '₱' . number_format((float) $rateValue, 2) : '—';
                                                                                $seriesData = $buildScheduleSeries($schedule, $start, $end, $archivedNow, $archivedTrainerAttendances);
                                                                                $seriesSessions = $seriesData['sessions'] ?? collect();
                                                                                $actualSeriesSessions = $seriesData['actual_sessions'] ?? collect();
                                                                                $dateLabels = $seriesData['labels'] ?? collect();
                                                                                $dateRangeLabel = $seriesData['range'] ?? '—';
                                                                                $dateList = $dateLabels->isNotEmpty()
                                                                                    ? $dateLabels
                                                                                    : collect([$start ? $start->format('M d') : '—']);
                                                                                $dateDisplay = 'range';
                                                                                $studentsPayload = $students->values()->all();
                                                                            @endphp
                                                                            <div
                                                                                class="assignment-row d-flex flex-column flex-lg-row gap-3 py-3 border-bottom"
                                                                                data-assignment-card
                                                                                data-date-display="{{ $dateDisplay }}"
                                                                                data-category="{{ $category }}"
                                                                                data-start="{{ $detail['start_date'] ?? '' }}"
                                                                                data-end="{{ $detail['end_date'] ?? '' }}"
                                                                                data-hours="{{ $hours }}"
                                                                            >
                                                                                <div class="assignment-col col-code">
                                                                                    <span class="badge bg-light text-muted border">{{ $codeLabel }}</span>
                                                                                </div>
                                                                                <div class="assignment-col flex-grow-1">
                                                                                    <div class="fw-semibold">{{ $schedule->name ?? 'Unnamed Schedule' }}</div>
                                                                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                                                                        <span class="assignment-hours-badge {{ $hoursBadgeClass }}">{{ $hoursLabel }}</span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="assignment-col col-rate">
                                                                                    @if($rateValue !== null)
                                                                                        <div class="fw-semibold">{{ $rateLabel }}</div>
                                                                                        <div class="text-muted small">per hr</div>
                                                                                    @else
                                                                                        <div class="text-muted small">—</div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="assignment-col col-date text-muted small">
                                                                                    <div class="assignment-date-list">
                                                                                        @foreach($dateList as $dateItem)
                                                                                            <div>• {{ $dateItem }}</div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                    <div class="assignment-date-range">{{ $dateRangeLabel }}</div>
                                                                                </div>
                                                                                <div class="assignment-col col-series">
                                                                                    @if($seriesSessions->isNotEmpty())
                                                                                        @php
                                                                                            $seriesCollapseId = 'assignment-series-archive-' . $archive->id . '-' . $detailIndex;
                                                                                        @endphp
                                                                                        <div class="series-sessions">
                                                                                            <div class="series-header">
                                                                                                <button
                                                                                                    type="button"
                                                                                                    class="btn btn-link btn-sm px-0 session-toggle"
                                                                                                    data-bs-toggle="collapse"
                                                                                                    data-bs-target="#{{ $seriesCollapseId }}"
                                                                                                    aria-expanded="false"
                                                                                                    aria-controls="{{ $seriesCollapseId }}"
                                                                                                    data-session-toggle
                                                                                                    data-collapsed-text="Show all sessions"
                                                                                                    data-expanded-text="Hide sessions"
                                                                                                >
                                                                                                    Show all sessions
                                                                                                </button>
                                                                                            </div>
                                                                                            <div class="collapse" id="{{ $seriesCollapseId }}">
                                                                                                <div class="series-panel">
                                                                                                    <div class="d-flex flex-column">
                                                                                                        @foreach($seriesSessions as $sessionIndex => $session)
                                                                                                            @php
                                                                                                                $isRescheduled = !empty($session['is_rescheduled']);
                                                                                                                $rescheduleTarget = $session['reschedule_target_label'] ?? null;
                                                                                                                $rescheduledFrom = $session['rescheduled_from'] ?? null;
                                                                                                            @endphp
                                                                                                            <div class="series-item">
                                                                                                                <div class="series-dot">
                                                                                                                    <span class="dot"></span>
                                                                                                                    @if($sessionIndex < $seriesSessions->count() - 1)
                                                                                                                        <span class="line"></span>
                                                                                                                    @endif
                                                                                                                </div>
                                                                                                                <div>
                                                                                                                    <div class="fw-semibold {{ $isRescheduled ? 'text-decoration-line-through text-muted' : '' }}">
                                                                                                                        {{ $session['label'] ?? '—' }}
                                                                                                                    </div>
                                                                                                                    <div class="text-muted small">
                                                                                                                        {{ $session['weekday'] ?? '' }}
                                                                                                                        @if(!empty($session['time']))
                                                                                                                            • {{ $session['time'] }}
                                                                                                                        @endif
                                                                                                                    </div>
                                                                                                                    @if($isRescheduled && $rescheduleTarget)
                                                                                                                        <div class="text-muted small fst-italic">→ {{ $rescheduleTarget }}</div>
                                                                                                                    @elseif(!$isRescheduled && $rescheduledFrom)
                                                                                                                        <div class="text-muted small fst-italic">From {{ $rescheduledFrom }}</div>
                                                                                                                    @endif
                                                                                                                    <span class="badge {{ $session['status_class'] ?? 'bg-secondary' }} px-2 py-1">{{ $session['status'] ?? '' }}</span>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        @endforeach
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @else
                                                                                        <span class="text-muted small">Series not set</span>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="assignment-col col-students">
                                                                                    @if($students->isNotEmpty())
                                                                                        <button
                                                                                            class="btn btn-light btn-sm students-toggle"
                                                                                            type="button"
                                                                                            data-students='@json($studentsPayload)'
                                                                                            data-students-title="{{ $schedule->name ?? 'Students' }}"
                                                                                        >
                                                                                            See more ({{ $students->count() }})
                                                                                        </button>
                                                                                    @else
                                                                                        <span class="text-muted small">No students</span>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="assignment-pagination d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3" data-pagination>
                                                                    <div class="text-muted small" data-page-status>Showing 0 to 0 of 0 assignments</div>
                                                                    <nav aria-label="Assignment pagination">
                                                                        <ul class="pagination pagination-sm mb-0" data-page-list>
                                                                            <li class="page-item" data-page-prev-item>
                                                                                <button type="button" class="page-link" data-page-prev aria-label="Previous">‹</button>
                                                                            </li>
                                                                            <li class="page-item" data-page-next-item>
                                                                                <button type="button" class="page-link" data-page-next aria-label="Next">›</button>
                                                                            </li>
                                                                        </ul>
                                                                    </nav>
                                                                </div>
                                                            @else
                                                                <div class="filter-card p-4 text-center text-muted">
                                                                    No schedules assigned.
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button
                                                                type="button"
                                                                class="btn btn-danger"
                                                                data-print-modal="archiveAssignmentsModal-{{ $archive->id }}"
                                                                data-print='@json($archivedAssignmentPrintPayload)'
                                                            >
                                                                <i class="fa-solid fa-print me-1"></i>Print
                                                                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" data-print-loader></span>
                                                            </button>
                                                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="archiveRestoreModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveRestoreModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore trainer ({{ $archive->email }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.trainer-management.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-success" type="submit" id="archive-restore-modal-submit-button-{{ $archive->id }}">
                                                                    <span id="archive-restore-modal-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Restore
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="archiveDeleteModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveDeleteModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete archived trainer ({{ $archive->email }}) permanently?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.trainer-management.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="archive-delete-modal-submit-button-{{ $archive->id }}">
                                                                    <span id="archive-delete-modal-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Delete
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('archive-restore-modal-form-{{ $archive->id }}').addEventListener('submit', function () {
                                                    const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                                document.getElementById('archive-delete-modal-form-{{ $archive->id }}').addEventListener('submit', function () {
                                                    const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">No archived trainers found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="text-muted small">
                                    Showing {{ $archivedData->firstItem() ?? 0 }} to {{ $archivedData->lastItem() ?? 0 }} of {{ $archivedData->total() }} archived trainers
                                </div>
                                <div class="ms-auto">
                                    {{ $archivedData->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div
        class="modal fade manual-clock-modal"
        id="manualClockModal"
        tabindex="-1"
        aria-labelledby="manualClockModalLabel"
        aria-hidden="true"
        data-default-avatar="{{ asset('assets/images/profile-45x45.png') }}"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    <h5 class="modal-title" id="manualClockModalLabel">Manual attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="manual-clock-hero">
                        <div class="manual-clock-icon manual-clock-icon--success" id="manualClockIcon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h4 class="manual-clock-title" id="manualClockTitle">Clocked In Successfully</h4>
                        <p class="manual-clock-subtitle" id="manualClockSubtitle">Attendance updated.</p>
                    </div>
                    <div class="manual-clock-member-card">
                        <img src="{{ asset('assets/images/profile-45x45.png') }}" alt="Trainer photo" class="manual-clock-avatar" id="manualClockAvatar">
                        <div class="manual-clock-member-info">
                            <div class="manual-clock-member-name" id="manualClockMemberName">Trainer</div>
                            <div class="manual-clock-member-meta" id="manualClockMemberEmail">trainer@email.com</div>
                            <div class="manual-clock-member-meta" id="manualClockMemberPhone">No phone</div>
                            <div class="manual-clock-chips">
                                <span class="manual-clock-chip" id="manualClockMemberCode">#---</span>
                                <span class="manual-clock-chip manual-clock-chip--inactive" id="manualClockMemberMembership">No upcoming classes</span>
                            </div>
                        </div>
                    </div>
                    <div class="manual-clock-warning d-none" id="manualClockWarning">
                        <div class="manual-clock-warning-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <div class="manual-clock-warning-title" id="manualClockWarningTitle">
                                Warning: Your membership is about to expire soon.
                            </div>
                            <div class="manual-clock-warning-subtitle" id="manualClockWarningSubtitle">
                                Please renew your membership to avoid any interruptions.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" id="manualClockCloseBtn">Close</button>
                    <button type="button" class="btn btn-danger px-4 d-none" id="manualClockConfirmBtn">Confirm Clock Out</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade students-modal" data-students-modal id="assignmentStudentsModal" tabindex="-1" aria-labelledby="assignmentStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="students-modal-icon"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <h5 class="modal-title fw-semibold mb-0" id="assignmentStudentsModalLabel" data-students-modal-title>Students</h5>
                            <div class="text-muted small">Class participants</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge students-modal-count" data-students-count>0 students</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <ul class="list-unstyled mb-0 students-modal-list" data-students-list></ul>
                    <div class="text-muted small d-none" data-students-empty>No students assigned.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="students-modal-backdrop" data-students-backdrop></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('trainer-filter-form');
            const manualClockButtons = document.querySelectorAll('.manual-clock-button');
            const manualClockModalEl = document.getElementById('manualClockModal');
            const manualClockIconEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockIcon') : null;
            const manualClockTitleEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockTitle') : null;
            const manualClockSubtitleEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockSubtitle') : null;
            const manualClockAvatarEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockAvatar') : null;
            const manualClockMemberNameEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberName') : null;
            const manualClockMemberEmailEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberEmail') : null;
            const manualClockMemberPhoneEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberPhone') : null;
            const manualClockMemberCodeEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberCode') : null;
            const manualClockMemberMembershipEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberMembership') : null;
            const manualClockWarningEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockWarning') : null;
            const manualClockWarningTitleEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockWarningTitle') : null;
            const manualClockWarningSubtitleEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockWarningSubtitle') : null;
            const manualClockCloseBtn = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockCloseBtn') : null;
            const manualClockConfirmBtn = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockConfirmBtn') : null;
            const manualClockModal = manualClockModalEl && typeof bootstrap !== 'undefined'
                ? new bootstrap.Modal(manualClockModalEl)
                : null;
            const csrfMeta = document.querySelector("meta[name='csrf-token']");
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            const defaultAvatar = manualClockModalEl ? manualClockModalEl.getAttribute('data-default-avatar') : '';
            const pendingRequest = { action: null, member: null, button: null };
            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }

            const iconMap = {
                success: { icon: 'fa-solid fa-circle-check', className: 'manual-clock-icon--success' },
                confirm: { icon: 'fa-regular fa-clock', className: 'manual-clock-icon--danger' },
                error: { icon: 'fa-solid fa-triangle-exclamation', className: 'manual-clock-icon--warning' },
                loading: { icon: 'fa-solid fa-spinner fa-spin', className: 'manual-clock-icon--loading' }
            };

            const parseDaysRemaining = function (value) {
                if (value === undefined || value === null || value === '') {
                    return null;
                }
                const parsed = parseInt(value, 10);
                return Number.isFinite(parsed) ? parsed : null;
            };

            const buildWarningCopy = function (daysRemaining) {
                if (daysRemaining === 0) {
                    return {
                        title: 'Warning: Your membership expires today.',
                        subtitle: 'Please renew your membership to avoid any interruptions.'
                    };
                }
                const dayLabel = daysRemaining === 1 ? '1 day' : `${daysRemaining} days`;
                return {
                    title: `Warning: Your membership is about to expire in ${dayLabel}.`,
                    subtitle: 'Please renew your membership to avoid any interruptions.'
                };
            };

            const updateWarning = function (member, action) {
                if (!manualClockWarningEl) {
                    return;
                }
                const daysRemaining = member && typeof member.membershipDaysRemaining === 'number'
                    ? member.membershipDaysRemaining
                    : null;
                const shouldShow = action === 'clockin' && typeof daysRemaining === 'number' && daysRemaining <= 7;

                if (!shouldShow) {
                    manualClockWarningEl.classList.add('d-none');
                    return;
                }

                const copy = buildWarningCopy(daysRemaining);
                if (manualClockWarningTitleEl) manualClockWarningTitleEl.textContent = copy.title;
                if (manualClockWarningSubtitleEl) manualClockWarningSubtitleEl.textContent = copy.subtitle;
                manualClockWarningEl.classList.remove('d-none');
            };

            const normalizeMemberData = function (button) {
                const memberCode = button.dataset.memberCode || '';
                const formattedCode = memberCode.startsWith('#') ? memberCode : `#${memberCode || '---'}`;
                return {
                    name: button.dataset.name || 'Trainer',
                    email: button.dataset.email || 'No email',
                    phone: button.dataset.phone || 'No phone',
                    code: formattedCode,
                    membership: button.dataset.membership || 'No upcoming classes',
                    membershipActive: button.dataset.membershipActive === '1',
                    membershipDaysRemaining: parseDaysRemaining(button.dataset.membershipDays),
                    avatar: button.dataset.avatar || defaultAvatar
                };
            };

            const updateMemberCard = function (member) {
                if (manualClockAvatarEl) {
                    manualClockAvatarEl.src = member.avatar || defaultAvatar || manualClockAvatarEl.src;
                }
                if (manualClockMemberNameEl) manualClockMemberNameEl.textContent = member.name || 'Trainer';
                if (manualClockMemberEmailEl) manualClockMemberEmailEl.textContent = member.email || 'No email';
                if (manualClockMemberPhoneEl) manualClockMemberPhoneEl.textContent = member.phone || 'No phone';
                if (manualClockMemberCodeEl) manualClockMemberCodeEl.textContent = member.code || '#---';
                if (manualClockMemberMembershipEl) {
                    manualClockMemberMembershipEl.textContent = member.membership || 'No upcoming classes';
                    manualClockMemberMembershipEl.classList.remove('manual-clock-chip--active', 'manual-clock-chip--inactive');
                    manualClockMemberMembershipEl.classList.add(member.membershipActive ? 'manual-clock-chip--active' : 'manual-clock-chip--inactive');
                }
            };

            const setModalState = function (state, options) {
                const config = iconMap[state] || iconMap.success;
                if (manualClockIconEl) {
                    manualClockIconEl.className = `manual-clock-icon ${config.className}`;
                    manualClockIconEl.innerHTML = `<i class="${config.icon}"></i>`;
                }

                if (manualClockTitleEl) manualClockTitleEl.textContent = options.title || 'Attendance update';
                if (manualClockSubtitleEl) {
                    manualClockSubtitleEl.textContent = options.subtitle || '';
                    manualClockSubtitleEl.classList.toggle('d-none', !options.subtitle);
                }

                if (manualClockConfirmBtn && manualClockCloseBtn) {
                    const showConfirm = state === 'confirm';
                    manualClockConfirmBtn.classList.toggle('d-none', !showConfirm);
                    manualClockCloseBtn.textContent = showConfirm ? 'Cancel' : 'Close';
                }
            };

            const showManualClockModal = function () {
                if (manualClockModal) {
                    manualClockModal.show();
                } else if (manualClockTitleEl) {
                    alert(manualClockTitleEl.textContent);
                }
            };

            const isErrorMessage = function (message) {
                return /unable|invalid|no data|no valid|already|cannot|error|unexpected/i.test(message);
            };

            const setButtonLoading = function (button, loadingText) {
                if (!button) {
                    return;
                }
                if (!button.dataset.originalHtml) {
                    button.dataset.originalHtml = button.innerHTML;
                }
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}`;
            };

            const resetButton = function (button) {
                if (!button) {
                    return;
                }
                button.disabled = false;
                if (button.dataset.originalHtml) {
                    button.innerHTML = button.dataset.originalHtml;
                    delete button.dataset.originalHtml;
                }
            };

            const submitManualClock = function (action, member, button) {
                if (!csrfToken || !member.email || !action) {
                    updateMemberCard(member);
                    updateWarning(member, action);
                    setModalState('error', {
                        title: 'Unable to update attendance',
                        subtitle: 'Missing required trainer details.'
                    });
                    showManualClockModal();
                    return;
                }

                setButtonLoading(button, action === 'clockout' ? 'Clocking out...' : 'Clocking in...');
                updateMemberCard(member);
                updateWarning(member, action);
                setModalState('loading', {
                    title: action === 'clockout' ? 'Clocking Out' : 'Clocking In',
                    subtitle: 'Please wait while we update attendance.'
                });
                showManualClockModal();

                fetch("{{ route('admin.staff-account-management.attendances.scanner2.fetch') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ result: member.email, action: action })
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            return { data: 'Unable to process attendance right now.' };
                        });
                    })
                    .then(function (data) {
                        const message = data && data.data ? data.data : `${member.name}'s attendance was updated.`;
                        const state = isErrorMessage(message) ? 'error' : 'success';
                        setModalState(state, {
                            title: state === 'success'
                                ? (action === 'clockout' ? 'Clocked Out Successfully' : 'Clocked In Successfully')
                                : 'Unable to update attendance',
                            subtitle: message
                        });
                    })
                    .catch(function () {
                        setModalState('error', {
                            title: 'Unable to update attendance',
                            subtitle: 'Unable to process attendance right now.'
                        });
                    })
                    .finally(function () {
                        resetButton(button);
                    });
            };

            if (manualClockConfirmBtn) {
                manualClockConfirmBtn.addEventListener('click', function () {
                    if (!pendingRequest.action || !pendingRequest.member || !pendingRequest.button) {
                        return;
                    }
                    submitManualClock(pendingRequest.action, pendingRequest.member, pendingRequest.button);
                });
            }

            if (manualClockModalEl) {
                manualClockModalEl.addEventListener('hidden.bs.modal', function () {
                    pendingRequest.action = null;
                    pendingRequest.member = null;
                    pendingRequest.button = null;
                    if (manualClockConfirmBtn) {
                        manualClockConfirmBtn.classList.add('d-none');
                    }
                    if (manualClockCloseBtn) {
                        manualClockCloseBtn.textContent = 'Close';
                    }
                    if (manualClockWarningEl) {
                        manualClockWarningEl.classList.add('d-none');
                    }
                });
            }

            manualClockButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetButton = this;
                    const action = targetButton.dataset.action;
                    const member = normalizeMemberData(targetButton);

                    pendingRequest.action = action;
                    pendingRequest.member = member;
                    pendingRequest.button = targetButton;

                    updateMemberCard(member);
                    updateWarning(member, action);

                    if (action === 'clockout') {
                        setModalState('confirm', {
                            title: 'Clock Out',
                            subtitle: 'Please confirm clocking out the trainer.'
                        });
                        showManualClockModal();
                        return;
                    }

                    submitManualClock(action, member, targetButton);
                });
            });

            if (form) {
                const statusInput = document.getElementById('trainer-status-filter');
                const chipButtons = form.querySelectorAll('.status-chip');
                const rangeButtons = form.querySelectorAll('.range-chip');
                const startInput = document.getElementById('start-date');
                const endInput = document.getElementById('end-date');
                const printForm = document.getElementById('print-form');
                const printButton = document.getElementById('print-submit-button');
                const printLoader = document.getElementById('print-loader');

                function formatDate(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                function applyRange(range) {
                    const today = new Date();
                    const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    const start = new Date(end);

                    if (range === 'last-week') {
                        start.setDate(start.getDate() - 7);
                    } else if (range === 'last-month') {
                        start.setMonth(start.getMonth() - 1);
                    } else if (range === 'last-year') {
                        start.setFullYear(start.getFullYear() - 1);
                    }

                    if (startInput) startInput.value = formatDate(start);
                    if (endInput) endInput.value = formatDate(end);
                    form.submit();
                }

                chipButtons.forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        const selectedStatus = this.dataset.status;
                        if (statusInput) {
                            statusInput.value = selectedStatus;
                        }

                        chipButtons.forEach(function (btn) {
                            btn.classList.remove('btn-dark', 'text-white', 'shadow-sm');
                            if (!btn.classList.contains('btn-outline-secondary')) {
                                btn.classList.add('btn-outline-secondary');
                            }
                        });

                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-dark', 'text-white', 'shadow-sm');

                        form.submit();
                    });
                });

                rangeButtons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        applyRange(this.dataset.range);
                    });
                });

                function getStatusBadgeClass(status) {
                    if (!status) return 'badge-soft-muted';
                    const normalized = status.toLowerCase();
                    if (normalized.includes('assigned')) return 'badge-soft-success';
                    if (normalized.includes('no upcoming')) return 'badge-soft-warning';
                    return 'badge-soft-secondary';
                }

                function buildPrintFilters(filters) {
                    const chips = [];
                    if (filters.show_archived) chips.push({ value: 'Archived view' });
                    if (filters.status && filters.status !== 'all') {
                        const statusMap = {
                            assigned: 'Assigned to classes',
                            unassigned: 'No upcoming classes',
                        };
                        chips.push({ label: 'Status', value: statusMap[filters.status] || filters.status });
                    }
                if (filters.search) {
                    chips.push({
                        label: 'Search',
                        value: filters.search,
                    });
                }
                    if (filters.start || filters.end) {
                        chips.push({ label: 'Date', value: `${filters.start || '—'} → ${filters.end || '—'}` });
                    }
                    return chips;
                }

                function buildPrintRows(items) {
                    return items.map((item) => ([
                        item.id ?? '—',
                        `<div class="fw">${item.name || '—'}</div><div class="muted">${item.email || ''}</div>`,
                        item.phone || '—',
                        item.salary ? `₱${item.salary}` : '—',
                        `<span class="badge ${getStatusBadgeClass(item.status)}">${item.status || '—'}</span>`,
                        `<div>${item.created_by || '—'}</div><div class="muted">${item.created_at || ''}</div>`,
                    ]));
                }

                function renderPrintWindow(payload) {
                    const rawItems = payload && payload.items ? payload.items : [];
                    const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                    const filters = buildPrintFilters(payload.filters || {});
                    const headers = ['#', 'Trainer', 'Contact', 'Est. Salary', 'Status', 'Created By'];
                    const rows = buildPrintRows(items);

                    return window.PrintPreview
                        ? PrintPreview.tryOpen(payload, headers, rows, filters)
                        : false;
                }

                if (printButton && printForm) {
                    printButton.addEventListener('click', async function (e) {
                        const rawPayload = printButton.dataset.print;
                        const rawAllPayload = printButton.dataset.printAll;
                        if (!rawPayload) {
                            return;
                        }

                        e.preventDefault();
                        if (printLoader) printLoader.classList.remove('d-none');
                        printButton.disabled = true;

                        let payload = null;
                        let allPayload = null;
                        try {
                            payload = JSON.parse(rawPayload);
                        } catch (err) {
                            payload = null;
                        }
                        try {
                            allPayload = rawAllPayload ? JSON.parse(rawAllPayload) : null;
                        } catch (err) {
                            allPayload = null;
                        }

                        const scope = window.PrintPreview && PrintPreview.chooseScope
                            ? await PrintPreview.chooseScope()
                            : 'current';

                        if (!scope) {
                            printButton.disabled = false;
                            if (printLoader) printLoader.classList.add('d-none');
                            return;
                        }

                        const payloadToUse = scope === 'all' && allPayload ? allPayload : payload;
                        const handled = payloadToUse ? renderPrintWindow(payloadToUse) : false;
                        if (!handled) {
                            printForm.submit();
                        }

                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                    });
                }
            }

            const assignmentModals = document.querySelectorAll('[data-assignment-modal]');
            const globalStudentsModal = document.getElementById('assignmentStudentsModal');
            const globalStudentsBackdrop = document.querySelector('[data-students-backdrop]');
            const globalStudentsModalTitle = globalStudentsModal ? globalStudentsModal.querySelector('[data-students-modal-title]') : null;
            const globalStudentsModalList = globalStudentsModal ? globalStudentsModal.querySelector('[data-students-list]') : null;
            const globalStudentsModalEmpty = globalStudentsModal ? globalStudentsModal.querySelector('[data-students-empty]') : null;
            const globalStudentsModalCount = globalStudentsModal ? globalStudentsModal.querySelector('[data-students-count]') : null;

            const pluralize = function (count, singular, plural) {
                return `${count} ${count === 1 ? singular : plural}`;
            };

            const toDate = function (value) {
                if (!value) {
                    return null;
                }
                const parts = value.split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) {
                    return null;
                }
                return new Date(parts[0], parts[1] - 1, parts[2]);
            };

            const escapeHtml = function (value) {
                if (value === null || value === undefined) {
                    return '';
                }
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const formatDuration = function (value) {
                const totalMinutes = Math.round(Number(value || 0) * 60);
                if (totalMinutes < 1) {
                    return '0 min';
                }
                const hrs = Math.floor(totalMinutes / 60);
                const mins = totalMinutes % 60;
                const parts = [];
                if (hrs > 0) {
                    parts.push(`${hrs} ${hrs === 1 ? 'hr' : 'hrs'}`);
                }
                if (mins > 0) {
                    parts.push(`${mins} ${mins === 1 ? 'min' : 'mins'}`);
                }
                return parts.join(' ');
            };

            const buildAssignmentFilterChips = function (filters) {
                const chips = [];
                if (filters.category && filters.category !== 'all') {
                    chips.push(filters.category === 'future' ? 'Upcoming only' : 'Complete only');
                }
                if (filters.start || filters.end) {
                    chips.push(`Date: ${filters.start || '—'} → ${filters.end || '—'}`);
                }

                return chips.length
                    ? chips.map((chip) => `<span class="pill">${escapeHtml(chip)}</span>`).join('')
                    : '<span class="muted">No filters applied</span>';
            };

            const buildAssignmentRows = function (items) {
                return items
                    .map(function (item, idx) {
                        const students = Array.isArray(item.students) ? item.students : [];
                        const studentsMarkup = students.length
                            ? `<ul>${students.map((student) => `<li>${escapeHtml(student)}</li>`).join('')}</ul>`
                            : '<div class="muted">No students</div>';
                        const sessions = Array.isArray(item.series_sessions) ? item.series_sessions : [];
                        const sessionBadgeClass = function (status) {
                            const normalized = String(status || '').toLowerCase();
                            if (normalized.includes('present') || normalized.includes('completed')) {
                                return 'badge-soft-success';
                            }
                            if (normalized.includes('absent')) {
                                return 'badge-soft-danger';
                            }
                            if (normalized.includes('upcoming')) {
                                return 'badge-soft-warning';
                            }
                            if (normalized.includes('resched')) {
                                return 'badge-soft-secondary';
                            }
                            return 'badge-soft-secondary';
                        };
                        const sessionsMarkup = sessions.length
                            ? `<ul class="series-list">
                                ${sessions.map((session) => `
                                    <li class="series-item">
                                        <div class="fw">${escapeHtml(session.label || '—')}</div>
                                        <div class="series-meta">${escapeHtml(session.weekday || '')}${session.time ? ' • ' + escapeHtml(session.time) : ''}</div>
                                        <span class="badge ${sessionBadgeClass(session.status)}">${escapeHtml(session.status || '')}</span>
                                    </li>
                                `).join('')}
                               </ul>`
                            : '<div class="muted">No sessions</div>';
                        const category = (item.category || '').toLowerCase() === 'past' ? 'past' : 'future';
                        const badgeClass = category === 'past' ? 'badge-soft-secondary' : 'badge-soft-success';
                        const categoryLabel = item.category_label || (category === 'past' ? 'Past' : 'Upcoming');
                        const rangeLabel = item.series_range || item.start_label || '—';

                        return `
                            <tr>
                                <td>${idx + 1}</td>
                                <td>
                                    <div class="fw">${escapeHtml(item.name || '—')}</div>
                                    <div class="muted">${escapeHtml(item.class_code || '')}</div>
                                </td>
                                <td>
                                    <div>${escapeHtml(rangeLabel)}</div>
                                </td>
                        <td>${item.hours !== null && item.hours !== undefined ? formatDuration(item.hours) : '—'}</td>
                                <td><span class="badge ${badgeClass}">${escapeHtml(categoryLabel)}</span></td>
                                <td>${studentsMarkup}</td>
                                <td>${sessionsMarkup}</td>
                            </tr>
                        `;
                    })
                    .join('');
            };

            const summarizeAssignments = function (items) {
                return items.reduce(
                    function (acc, item) {
                        const category = (item.category || '').toLowerCase() === 'past' ? 'past' : 'future';
                        const hours = Number(item.hours || 0);
                        acc.total += 1;
                        if (category === 'past') {
                            acc.past += 1;
                            acc.past_hours += hours;
                        } else {
                            acc.future += 1;
                            acc.future_hours += hours;
                        }
                        return acc;
                    },
                    { total: 0, future: 0, past: 0, future_hours: 0, past_hours: 0 }
                );
            };

            const renderAssignmentPrintWindow = function (payload) {
                const items = payload.items || [];
                const filters = payload.filters || {};
                const summary = payload.summary || summarizeAssignments(items);
                const trainer = payload.trainer || {};
                const rows = buildAssignmentRows(items);
                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>${escapeHtml(payload.title || 'Trainer assignments')}</title>
                            <style>
                                :root { color-scheme: light; }
                                body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
                                .sheet { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; }
                                .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
                                .title { margin: 0; font-size: 22px; }
                                .muted { color: #6b7280; font-size: 12px; }
                                .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
                                .pill {
                                    --pill-bg: #f5f7fb;
                                    --pill-border: #d5deec;
                                    --pill-text: #111827;
                                    --pill-dot: #9ca3af;
                                    background: var(--pill-bg);
                                    border: 1px solid var(--pill-border);
                                    border-radius: 10px;
                                    padding: 6px 10px;
                                    font-size: 12px;
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 6px;
                                    letter-spacing: 0.01em;
                                }
                                .pill::before {
                                    content: '';
                                    width: 8px;
                                    height: 8px;
                                    border-radius: 50%;
                                    background: var(--pill-dot);
                                    opacity: 0.9;
                                }
                                .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 14px; }
                                .stat-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; }
                                .stat-value { font-size: 20px; font-weight: 700; color: #111827; }
                                .stat-label { color: #6b7280; font-size: 12px; }
                                table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
                                th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
                                th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
                                .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
                                .badge-soft-success { background: #dcfce7; color: #166534; }
                                .badge-soft-secondary { background: #e5e7eb; color: #374151; }
                                .badge-soft-warning { background: #fef9c3; color: #854d0e; }
                                .badge-soft-danger { background: #fee2e2; color: #b91c1c; }
                                .fw { font-weight: 700; }
                                .series-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
                                .series-item { padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; }
                                .series-meta { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
                            </style>
                        </head>
                        <body>
                            <div class="sheet">
                                <div class="header">
                                    <div>
                                        <h1 class="title">${escapeHtml(payload.title || 'Trainer assignments')}</h1>
                                        <div class="muted">Trainer: ${escapeHtml(trainer.name || '—')} ${trainer.code ? `(${escapeHtml(trainer.code)})` : ''}</div>
                                        <div class="muted">Contact: ${escapeHtml(trainer.email || '—')} ${trainer.phone ? ' • ' + escapeHtml(trainer.phone) : ''}</div>
                                        <div class="muted">Generated ${escapeHtml(payload.generated_at || '')}</div>
                                        ${payload.generated_by ? `<div class="muted">Generated by ${escapeHtml(payload.generated_by)}</div>` : ''}
                                        <div class="muted">Showing ${items.length} assignment(s)</div>
                                    </div>
                                    <div class="stat-grid">
                                        <div class="stat-card">
                                            <div class="stat-value">${summary.total}</div>
                                            <div class="stat-label">Total assignments</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-value">${summary.future}</div>
                                            <div class="stat-label">Upcoming</div>
                                            <div class="muted">Hours: ${formatDuration(summary.future_hours)}</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-value">${summary.past}</div>
                                            <div class="stat-label">Past</div>
                                            <div class="muted">Hours: ${formatDuration(summary.past_hours)}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildAssignmentFilterChips(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Class</th>
                                            <th>Date range</th>
                                            <th>Hours</th>
                                            <th>Status</th>
                                            <th>Students</th>
                                            <th>Series sessions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="7" style="text-align:center; padding:16px;">No assignments for this view.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                            <script>window.print();<\/script>
                        </body>
                    </html>
                `;

                const printWindow = window.open('', '_blank', 'width=1200,height=900');
                if (!printWindow) return false;
                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
                return true;
            };

            const assignmentPrintButtons = document.querySelectorAll('[data-print-modal][data-print]');
            assignmentPrintButtons.forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.preventDefault();

                    const modalId = button.dataset.printModal;
                    const rawPayload = button.dataset.print;
                    const loader = button.querySelector('[data-print-loader]');
                    const modalEl = modalId ? document.getElementById(modalId) : null;

                    if (loader) {
                        loader.classList.remove('d-none');
                    }
                    button.disabled = true;

                    let payload = null;
                    try {
                        payload = JSON.parse(rawPayload || '{}');
                    } catch (err) {
                        payload = null;
                    }

                    if (!payload) {
                        button.disabled = false;
                        if (loader) loader.classList.add('d-none');
                        return;
                    }

                    const filters = { category: 'all', start: null, end: null };
                    if (modalEl) {
                        const activeCategoryButton = modalEl.querySelector('[data-category-filter].btn-dark');
                        filters.category = activeCategoryButton ? activeCategoryButton.dataset.categoryFilter || 'all' : 'all';
                        const startInput = modalEl.querySelector('[data-filter-start]');
                        const endInput = modalEl.querySelector('[data-filter-end]');
                        filters.start = startInput ? startInput.value : null;
                        filters.end = endInput ? endInput.value : null;
                    }

                    const startDate = toDate(filters.start || '');
                    const endDate = toDate(filters.end || '');

                    const filteredItems = (payload.items || []).filter(function (item) {
                        const category = (item.category || '').toLowerCase() === 'past' ? 'past' : 'future';
                        if (filters.category !== 'all' && category !== filters.category) {
                            return false;
                        }

                        if (startDate || endDate) {
                            const itemStart = toDate(item.start_date || '');
                            const itemEnd = toDate(item.end_date || '');
                            const scheduleStart = itemStart || itemEnd;
                            const scheduleEnd = itemEnd || itemStart;

                            if (startDate && scheduleEnd && scheduleEnd < startDate) {
                                return false;
                            }

                            if (endDate && scheduleStart && scheduleStart > endDate) {
                                return false;
                            }

                            if ((startDate && !scheduleEnd) || (endDate && !scheduleStart)) {
                                return false;
                            }
                        }

                        return true;
                    });

                    const summary = summarizeAssignments(filteredItems);
                    const payloadForPrint = Object.assign({}, payload, {
                        items: filteredItems,
                        filters: filters,
                        summary: summary,
                    });

                    renderAssignmentPrintWindow(payloadForPrint);

                    setTimeout(function () {
                        button.disabled = false;
                        if (loader) loader.classList.add('d-none');
                    }, 300);
                });
            });

            assignmentModals.forEach(function (modalEl) {
                const categoryButtons = modalEl.querySelectorAll('[data-category-filter]');
                const startInput = modalEl.querySelector('[data-filter-start]');
                const endInput = modalEl.querySelector('[data-filter-end]');
                const resetButton = modalEl.querySelector('[data-filter-reset]');
                const cards = Array.from(modalEl.querySelectorAll('[data-assignment-card]'));
                const pagination = {
                    container: modalEl.querySelector('[data-pagination]'),
                    status: modalEl.querySelector('[data-page-status]'),
                    list: modalEl.querySelector('[data-page-list]'),
                    prev: modalEl.querySelector('[data-page-prev]'),
                    next: modalEl.querySelector('[data-page-next]'),
                    prevItem: modalEl.querySelector('[data-page-prev-item]'),
                    nextItem: modalEl.querySelector('[data-page-next-item]'),
                };
                const summaryEls = {
                    totalCount: modalEl.querySelectorAll('[data-role="total-count"]'),
                    futureCount: modalEl.querySelectorAll('[data-role="future-count"]'),
                    pastCount: modalEl.querySelectorAll('[data-role="past-count"]'),
                    totalHours: modalEl.querySelectorAll('[data-role="total-hours"]'),
                    futureHours: modalEl.querySelectorAll('[data-role="future-hours"]'),
                    pastHours: modalEl.querySelectorAll('[data-role="past-hours"]'),
                };
                const studentsModal = globalStudentsModal;
                const studentsModalTitle = globalStudentsModalTitle;
                const studentsModalList = globalStudentsModalList;
                const studentsModalEmpty = globalStudentsModalEmpty;
                const studentsModalCount = globalStudentsModalCount;
                const studentsBackdrop = globalStudentsBackdrop;
                let parentModalEl = modalEl;
                let studentsModalInstance = null;

                if (!cards.length) {
                    return;
                }

                let activeCategory = 'all';
                let currentPage = 1;
                const pageSize = 3;

                function setActiveCategoryButton(targetCategory) {
                    categoryButtons.forEach(function (btn) {
                        const btnCategory = btn.dataset.categoryFilter || 'all';
                        btn.classList.remove('btn-dark', 'text-white');
                        if (!btn.classList.contains('btn-outline-secondary')) {
                            btn.classList.add('btn-outline-secondary');
                        }
                        if (btnCategory === targetCategory) {
                            btn.classList.remove('btn-outline-secondary');
                            btn.classList.add('btn-dark', 'text-white');
                        }
                    });
                }

                function updateSummary(visibleCards) {
                    let futureCount = 0;
                    let pastCount = 0;
                    let futureHours = 0;
                    let pastHours = 0;

                    visibleCards.forEach(function (card) {
                        const category = card.dataset.category === 'past' ? 'past' : 'future';
                        const hours = Number(card.dataset.hours || 0);

                        if (category === 'future') {
                            futureCount += 1;
                            futureHours += hours;
                        } else {
                            pastCount += 1;
                            pastHours += hours;
                        }
                    });

                    const totalCount = futureCount + pastCount;
                    const totalHours = futureHours + pastHours;
                    summaryEls.totalCount.forEach(function (el) {
                        el.textContent = String(totalCount);
                    });
                    summaryEls.futureCount.forEach(function (el) {
                        el.textContent = String(futureCount);
                    });
                    summaryEls.pastCount.forEach(function (el) {
                        el.textContent = String(pastCount);
                    });
                    summaryEls.totalHours.forEach(function (el) {
                        el.textContent = formatDuration(totalHours);
                    });
                    summaryEls.futureHours.forEach(function (el) {
                        el.textContent = formatDuration(futureHours);
                    });
                    summaryEls.pastHours.forEach(function (el) {
                        el.textContent = formatDuration(pastHours);
                    });
                }

                function matchesDateRange(card) {
                    const filterStart = startInput ? toDate(startInput.value) : null;
                    const filterEnd = endInput ? toDate(endInput.value) : null;

                    if (!filterStart && !filterEnd) {
                        return true;
                    }

                    const startDate = toDate(card.dataset.start);
                    const endDate = toDate(card.dataset.end);

                    const scheduleStart = startDate || endDate;
                    const scheduleEnd = endDate || startDate;

                    if (filterStart && scheduleEnd && scheduleEnd < filterStart) {
                        return false;
                    }

                    if (filterEnd && scheduleStart && scheduleStart > filterEnd) {
                        return false;
                    }

                    if ((filterStart && !scheduleEnd) || (filterEnd && !scheduleStart)) {
                        return false;
                    }

                    return true;
                }

                function renderPageNumbers(totalPages) {
                    if (!pagination.list || !pagination.nextItem) {
                        return;
                    }

                    pagination.list.querySelectorAll('[data-page-number]').forEach(function (el) {
                        el.remove();
                    });

                    for (let page = 1; page <= totalPages; page += 1) {
                        const li = document.createElement('li');
                        li.className = `page-item${page === currentPage ? ' active' : ''}`;
                        li.setAttribute('data-page-number', String(page));

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'page-link';
                        button.textContent = String(page);
                        button.addEventListener('click', function () {
                            if (page === currentPage) {
                                return;
                            }
                            currentPage = page;
                            applyFilters(false);
                        });

                        li.appendChild(button);
                        pagination.list.insertBefore(li, pagination.nextItem);
                    }
                }

                function updatePagination(totalItems, totalPages, startIndex, endIndex) {
                    if (!pagination.container) {
                        return;
                    }

                    if (totalItems <= pageSize) {
                        pagination.container.classList.add('d-none');
                    } else {
                        pagination.container.classList.remove('d-none');
                    }

                    const safeStart = totalItems === 0 ? 0 : startIndex + 1;
                    const safeEnd = totalItems === 0 ? 0 : Math.min(endIndex, totalItems);
                    const assignmentWord = totalItems === 1 ? 'assignment' : 'assignments';
                    const statusText = `Showing ${safeStart} to ${safeEnd} of ${totalItems} ${assignmentWord}`;
                    if (pagination.status) {
                        pagination.status.textContent = statusText;
                    }

                    if (pagination.prevItem) {
                        pagination.prevItem.classList.toggle('disabled', currentPage <= 1);
                    }
                    if (pagination.nextItem) {
                        pagination.nextItem.classList.toggle('disabled', currentPage >= totalPages);
                    }
                    if (pagination.prev) {
                        pagination.prev.disabled = currentPage <= 1;
                    }
                    if (pagination.next) {
                        pagination.next.disabled = currentPage >= totalPages;
                    }

                    renderPageNumbers(totalPages);
                }

                function applyFilters(resetPage) {
                    if (resetPage) {
                        currentPage = 1;
                    }

                    const filteredCards = [];

                    cards.forEach(function (card) {
                        const category = card.dataset.category || 'future';
                        let isVisible = true;

                        if (activeCategory !== 'all' && category !== activeCategory) {
                            isVisible = false;
                        }

                        if (isVisible && !matchesDateRange(card)) {
                            isVisible = false;
                        }

                        if (isVisible) {
                            filteredCards.push(card);
                        }
                    });

                    updateSummary(filteredCards);

                    const totalItems = filteredCards.length;
                    const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
                    if (currentPage > totalPages) {
                        currentPage = totalPages;
                    }

                    const startIndex = (currentPage - 1) * pageSize;
                    const endIndex = startIndex + pageSize;

                    cards.forEach(function (card) {
                        card.classList.add('d-none');
                    });

                    filteredCards.slice(startIndex, endIndex).forEach(function (card) {
                        card.classList.remove('d-none');
                    });

                    updatePagination(totalItems, totalPages, startIndex, endIndex);
                }

                categoryButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const selected = this.dataset.categoryFilter || 'all';
                        if (selected === activeCategory) {
                            return;
                        }
                        activeCategory = selected;

                        setActiveCategoryButton(activeCategory);
                        applyFilters(true);
                    });
                });

                if (startInput) {
                    startInput.addEventListener('change', function () {
                        applyFilters(true);
                    });
                }

                if (endInput) {
                    endInput.addEventListener('change', function () {
                        applyFilters(true);
                    });
                }

                if (resetButton) {
                    resetButton.addEventListener('click', function () {
                        activeCategory = 'all';
                        if (startInput) {
                            startInput.value = '';
                        }
                        if (endInput) {
                            endInput.value = '';
                        }

                        setActiveCategoryButton(activeCategory);
                        applyFilters(true);
                    });
                }

                if (pagination.prev) {
                    pagination.prev.addEventListener('click', function () {
                        if (currentPage <= 1) {
                            return;
                        }
                        currentPage -= 1;
                        applyFilters(false);
                    });
                }

                if (pagination.next) {
                    pagination.next.addEventListener('click', function () {
                        currentPage += 1;
                        applyFilters(false);
                    });
                }

                const studentButtons = modalEl.querySelectorAll('[data-students-title][data-students]');
                studentButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        if (!studentsModal || !studentsModalList || !studentsModalEmpty) {
                            return;
                        }
                        event.preventDefault();
                        event.stopPropagation();
                        const title = button.getAttribute('data-students-title') || 'Students';
                        if (studentsModalTitle) {
                            studentsModalTitle.textContent = title;
                        }
                        let students = [];
                        const raw = button.getAttribute('data-students') || '[]';
                        try {
                            students = JSON.parse(raw);
                        } catch (error) {
                            students = [];
                        }
                        if (!Array.isArray(students)) {
                            students = [];
                        }
                        studentsModalList.innerHTML = '';
                        if (students.length) {
                            students.forEach(function (student) {
                                const li = document.createElement('li');
                                li.className = 'student-item';
                                const text = String(student);
                                const match = text.match(/^(.*)\s\(([^)]+)\)\s*$/);
                                const name = match ? match[1].trim() : text;
                                const code = match ? match[2].trim() : '';
                                const nameEl = document.createElement('span');
                                nameEl.className = 'student-name';
                                nameEl.textContent = name;
                                li.appendChild(nameEl);
                                if (code) {
                                    const codeEl = document.createElement('span');
                                    codeEl.className = 'student-code';
                                    codeEl.textContent = code;
                                    li.appendChild(codeEl);
                                }
                                studentsModalList.appendChild(li);
                            });
                            studentsModalEmpty.classList.add('d-none');
                        } else {
                            studentsModalEmpty.classList.remove('d-none');
                        }
                        if (studentsModalCount) {
                            const label = students.length === 1 ? 'student' : 'students';
                            studentsModalCount.textContent = `${students.length} ${label}`;
                        }

                        if (studentsBackdrop) {
                            studentsBackdrop.classList.add('is-visible');
                        }
                        if (typeof bootstrap !== 'undefined') {
                            studentsModalInstance = bootstrap.Modal.getOrCreateInstance(studentsModal, {
                                backdrop: false,
                                keyboard: true,
                                focus: false,
                            });
                            studentsModalInstance.show();
                        }
                    });
                });

                if (studentsModal) {
                    studentsModal.addEventListener('hidden.bs.modal', function () {
                        if (studentsBackdrop) {
                            studentsBackdrop.classList.remove('is-visible');
                        }
                        if (parentModalEl && parentModalEl.classList.contains('show')) {
                            document.body.classList.add('modal-open');
                        }
                    });
                }

                modalEl.addEventListener('shown.bs.modal', function () {
                    applyFilters(true);
                });
                setActiveCategoryButton(activeCategory);
                applyFilters(true);
            });

            const sessionToggles = document.querySelectorAll('[data-session-toggle]');
            sessionToggles.forEach(function (btn) {
                const collapsedLabel = btn.dataset.collapsedText || 'Show all sessions';
                const expandedLabel = btn.dataset.expandedText || 'Hide sessions';
                const targetSelector = btn.getAttribute('data-bs-target');
                const targetEl = targetSelector ? document.querySelector(targetSelector) : null;

                btn.textContent = collapsedLabel;
                if (!targetEl) {
                    return;
                }

                targetEl.addEventListener('shown.bs.collapse', function () {
                    btn.textContent = expandedLabel;
                });
                targetEl.addEventListener('hidden.bs.collapse', function () {
                    btn.textContent = collapsedLabel;
                });
            });
        });
    </script>
@endsection
