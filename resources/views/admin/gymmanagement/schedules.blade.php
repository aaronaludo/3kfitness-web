@extends('layouts.admin')
@section('title', 'Classes')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $weekdayLookup = [
                    'sun' => 'Sunday',
                    'mon' => 'Monday',
                    'tue' => 'Tuesday',
                    'wed' => 'Wednesday',
                    'thu' => 'Thursday',
                    'fri' => 'Friday',
                    'sat' => 'Saturday',
                ];

                $printSource = $showArchived ? $archivedData : $data;
                $printAllSource = $showArchived ? ($printAllArchived ?? collect()) : ($printAllActive ?? collect());
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
                $nowForPrint = now();
                $resolveScheduleStatus = function ($item) use ($nowForPrint) {
                    $startDate = $item->class_start_date ? \Carbon\Carbon::parse($item->class_start_date) : null;
                    $endDate   = $item->class_end_date ? \Carbon\Carbon::parse($item->class_end_date) : null;
                    $dayKeys   = is_array($item->recurring_days) ? $item->recurring_days : json_decode($item->recurring_days ?? '[]', true);
                    $recurringDayKeys = collect($dayKeys ?? [])->map(function ($d) {
                        return strtolower($d);
                    })->toArray();
                    $seriesStart = $item->series_start_date ? \Carbon\Carbon::parse($item->series_start_date)->startOfDay() : ($startDate ? $startDate->copy()->startOfDay() : null);
                    $seriesEnd = $item->series_end_date ? \Carbon\Carbon::parse($item->series_end_date)->endOfDay() : ($endDate ? $endDate->copy()->endOfDay() : null);
                    $sessionOverridesRaw = is_array($item->session_overrides)
                        ? $item->session_overrides
                        : json_decode($item->session_overrides ?? '[]', true);
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
                    $hasOngoingSession = false;
                    $hasFutureSession = false;
                    $hasAnySession = false;

                    $computeStatus = function ($sessionStart, $sessionEnd) use (&$hasOngoingSession, &$hasFutureSession, $nowForPrint) {
                        if ($nowForPrint->between($sessionStart, $sessionEnd, true)) {
                            $hasOngoingSession = true;
                            return;
                        }

                        if ($nowForPrint->gt($sessionEnd)) {
                            return;
                        }

                        $hasFutureSession = true;
                    };

                    if ($seriesStart && $seriesEnd && count($recurringDayKeys)) {
                        $cursor = $seriesStart->copy();
                        while ($cursor->lte($seriesEnd)) {
                            $dayKey = strtolower(substr($cursor->format('D'), 0, 3));
                            if (in_array($dayKey, $recurringDayKeys, true)) {
                                $hasAnySession = true;
                                $sessionDateKey = $cursor->toDateString();
                                $sessionStart = $item->class_start_time
                                    ? $cursor->copy()->setTimeFromTimeString($item->class_start_time)
                                    : $cursor->copy()->startOfDay();
                                $sessionEnd = $item->class_end_time
                                    ? $cursor->copy()->setTimeFromTimeString($item->class_end_time)
                                    : $cursor->copy()->endOfDay();

                                $override = $sessionOverrides[$sessionDateKey] ?? null;

                                if ($override) {
                                    $overrideDate = $override['new_carbon'] ?: $cursor->copy();
                                    $overrideStart = $overrideDate->copy();
                                    $overrideEnd = $overrideDate->copy();
                                    $overrideStartTime = $override['start_time'] ?? $item->class_start_time;
                                    $overrideEndTime = $override['end_time'] ?? $item->class_end_time;

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

                                    $computeStatus($overrideStart, $overrideEnd);
                                } else {
                                    $computeStatus($sessionStart, $sessionEnd);
                                }
                            }
                            $cursor->addDay();
                        }
                    }

                    if (!$hasAnySession && $startDate) {
                        $sessionStart = $startDate->copy();
                        $sessionEnd = $endDate ?: ($item->class_end_time
                            ? $sessionStart->copy()->setTimeFromTimeString($item->class_end_time)
                            : $sessionStart->copy()->endOfDay());

                        $override = $sessionOverrides[$sessionStart->toDateString()] ?? null;

                        if ($override) {
                            $overrideDate = $override['new_carbon'] ?: $sessionStart->copy();
                            $overrideStart = $overrideDate->copy();
                            $overrideEnd = $overrideDate->copy();
                            $overrideStartTime = $override['start_time'] ?? $item->class_start_time;
                            $overrideEndTime = $override['end_time'] ?? $item->class_end_time;

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

                            $computeStatus($overrideStart, $overrideEnd);
                        } else {
                            $computeStatus($sessionStart, $sessionEnd);
                        }
                    }

                    $scheduleStatus = 'Upcoming';
                    if ($hasOngoingSession) {
                        $scheduleStatus = 'Ongoing';
                    } elseif (!$hasFutureSession) {
                        $scheduleStatus = 'Completed';
                    }

                    return $scheduleStatus;
                };
                $printSchedules = collect($printSource->items())->map(function ($item) use ($weekdayLookup, $nowForPrint, $resolveScheduleStatus) {
                    $startDate = $item->class_start_date ? \Carbon\Carbon::parse($item->class_start_date) : null;
                    $endDate   = $item->class_end_date ? \Carbon\Carbon::parse($item->class_end_date) : null;
                    $dayKeys   = is_array($item->recurring_days) ? $item->recurring_days : json_decode($item->recurring_days ?? '[]', true);
                    $cadence   = collect($dayKeys ?? [])->map(function ($d) use ($weekdayLookup) {
                        return $weekdayLookup[$d] ?? ucfirst($d);
                    })->filter()->implode(', ');

                    $seriesStart = $item->series_start_date ? \Carbon\Carbon::parse($item->series_start_date)->startOfDay() : ($startDate ? $startDate->copy()->startOfDay() : null);
                    $seriesEnd = $item->series_end_date ? \Carbon\Carbon::parse($item->series_end_date)->endOfDay() : ($endDate ? $endDate->copy()->endOfDay() : null);
                    $trainerAcceptanceStatus = (int) ($item->istrainerapproved ?? 0);
                    $statusLabel = $resolveScheduleStatus($item);
                    if ($trainerAcceptanceStatus === 0) {
                        $statusLabel = 'Series pending trainer acceptance';
                    }

                    $adminAcceptance = $item->isadminapproved == 0 ? 'Pending' :
                        ($item->isadminapproved == 1 ? 'Approve' :
                        ($item->isadminapproved == 2 ? 'Reject' : ''));

                    $trainer = optional($item->user);
                    $trainerIsArchived = (int) ($trainer->is_archive ?? 0) === 1;
                    $trainerName = $item->trainer_id == 0
                        ? 'No Trainer for now'
                        : trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
                    $trainerDisplayName = $trainerIsArchived ? '' : ($trainerName ?: '—');

                    $timeRange = $item->class_start_time && $item->class_end_time
                        ? \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A')
                        : null;
                    $seriesStartLabel = $seriesStart ? $seriesStart->format('M j, Y') : null;
                    $seriesEndLabel = $seriesEnd ? $seriesEnd->format('M j, Y') : null;

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'class_code' => $item->class_code,
                        'trainer' => $trainerDisplayName,
                        'trainer_rate' => $item->trainer_rate_per_hour !== null
                            ? number_format((float) $item->trainer_rate_per_hour, 2)
                            : null,
                        'slots' => $item->slots,
                        'enrolled' => $item->user_schedules_count ?? 0,
                        'start' => $startDate ? $startDate->format('M j, Y') : 'Not set',
                        'end' => $endDate ? $endDate->format('M j, Y') : '—',
                        'series_start' => $seriesStartLabel,
                        'series_end' => $seriesEndLabel,
                        'time_range' => $timeRange,
                        'cadence' => $cadence ?: 'One-time',
                        'status' => $statusLabel,
                        'admin_status' => $adminAcceptance,
                        'rejection_reason' => $item->rejection_reason ?: '',
                        'created_by' => $item->created_by ?? '',
                        'created_role' => $item->created_role ?? '',
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printPayload = [
                    'title' => $showArchived ? 'Archived classes' : 'Class schedules',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'meta' => [
                        'generated_by' => $printGeneratedBy,
                    ],
                    'filters' => [
                        'search' => request('name'),
                        'status' => request('status', 'all') ?: 'all',
                        'month_filter' => request('month_filter'),
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printSchedules->count(),
                    'items' => $printSchedules,
                ];

                $printAllSchedules = collect($printAllSource ?? [])->map(function ($item) use ($weekdayLookup, $nowForPrint, $resolveScheduleStatus) {
                    $startDate = $item->class_start_date ? \Carbon\Carbon::parse($item->class_start_date) : null;
                    $endDate   = $item->class_end_date ? \Carbon\Carbon::parse($item->class_end_date) : null;
                    $dayKeys   = is_array($item->recurring_days) ? $item->recurring_days : json_decode($item->recurring_days ?? '[]', true);
                    $cadence   = collect($dayKeys ?? [])->map(function ($d) use ($weekdayLookup) {
                        return $weekdayLookup[$d] ?? ucfirst($d);
                    })->filter()->implode(', ');

                    $seriesStart = $item->series_start_date ? \Carbon\Carbon::parse($item->series_start_date)->startOfDay() : ($startDate ? $startDate->copy()->startOfDay() : null);
                    $seriesEnd = $item->series_end_date ? \Carbon\Carbon::parse($item->series_end_date)->endOfDay() : ($endDate ? $endDate->copy()->endOfDay() : null);
                    $trainerAcceptanceStatus = (int) ($item->istrainerapproved ?? 0);
                    $statusLabel = $resolveScheduleStatus($item);
                    if ($trainerAcceptanceStatus === 0) {
                        $statusLabel = 'Series pending trainer acceptance';
                    }

                    $adminAcceptance = $item->isadminapproved == 0 ? 'Pending' :
                        ($item->isadminapproved == 1 ? 'Approve' :
                        ($item->isadminapproved == 2 ? 'Reject' : ''));

                    $trainer = optional($item->user);
                    $trainerIsArchived = (int) ($trainer->is_archive ?? 0) === 1;
                    $trainerName = $item->trainer_id == 0
                        ? 'No Trainer for now'
                        : trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
                    $trainerDisplayName = $trainerIsArchived ? '' : ($trainerName ?: '—');

                    $timeRange = $item->class_start_time && $item->class_end_time
                        ? \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A')
                        : null;
                    $seriesStartLabel = $seriesStart ? $seriesStart->format('M j, Y') : null;
                    $seriesEndLabel = $seriesEnd ? $seriesEnd->format('M j, Y') : null;

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'class_code' => $item->class_code,
                        'trainer' => $trainerDisplayName,
                        'trainer_rate' => $item->trainer_rate_per_hour !== null
                            ? number_format((float) $item->trainer_rate_per_hour, 2)
                            : null,
                        'slots' => $item->slots,
                        'enrolled' => $item->user_schedules_count ?? 0,
                        'start' => $startDate ? $startDate->format('M j, Y') : 'Not set',
                        'end' => $endDate ? $endDate->format('M j, Y') : '—',
                        'series_start' => $seriesStartLabel,
                        'series_end' => $seriesEndLabel,
                        'time_range' => $timeRange,
                        'cadence' => $cadence ?: 'One-time',
                        'status' => $statusLabel,
                        'admin_status' => $adminAcceptance,
                        'rejection_reason' => $item->rejection_reason ?: '',
                        'created_by' => $item->created_by ?? '',
                        'created_role' => $item->created_role ?? '',
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printAllPayload = [
                    'title' => $showArchived ? 'Archived classes (all pages)' : 'Class schedules (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'meta' => [
                        'generated_by' => $printGeneratedBy,
                    ],
                    'filters' => [
                        'search' => request('name'),
                        'status' => request('status', 'all') ?: 'all',
                        'month_filter' => request('month_filter'),
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                        'scope' => 'all',
                    ],
                    'count' => $printAllSchedules->count(),
                    'items' => $printAllSchedules,
                ];

                $pendingRescheduleCount = collect($pendingRescheduleRequests ?? [])->where('status', 0)->count();
            @endphp
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 my-4">
                <div>
                  <h1 class="title">Classes</h1>
                </div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.gym-management.schedules.create') }}">
                        <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Add
                    </a>
                    <form action="{{ route('admin.gym-management.schedules.print') }}" method="POST" id="print-form">
                        @csrf
                        <div>
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <input type="hidden" name="month_filter" value="{{ request('month_filter') }}">
                          <input
                            type="hidden"
                            name="created_start"
                            class="form-control"
                            value="{{ request('start_date') }}"
                            aria-label="Start date"
                          />
                          <input
                            type="hidden"
                            name="created_end"
                            class="form-control"
                            value="{{ request('end_date') }}"
                            aria-label="End date"
                          />
                          <button
                            class="btn btn-md btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered classes"
                          >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                          </button>
                        </div>
                    </form>
                    <a
                        class="btn btn-danger ms-2"
                        href="{{ route('admin.gym-management.schedules.reschedule-requests') }}"
                    >
                        <i class="fa-solid fa-calendar-check"></i>&nbsp;&nbsp;Reschedule requests
                        @if($pendingRescheduleCount)
                            <span class="badge bg-warning text-dark ms-2">{{ $pendingRescheduleCount }}</span>
                        @endif
                    </a>
                    @if ($showArchived)
                        <a
                            class="btn btn-danger ms-2"
                            href="{{ route('admin.gym-management.schedules', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-danger ms-2"
                            href="{{ route('admin.gym-management.schedules', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>
            </div>                            
            @if (!$showArchived)
                <div class="col-12 mb-3">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="tile tile-primary h-100">
                                <div class="tile-heading">Total Classes Created by Admin</div>
                                <div class="tile-body">
                                    <i class="fa-solid fa-hashtag"></i>
                                    <h2 class="float-end">{{ $classescreatedbyadmin }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="tile tile-primary h-100">
                                <div class="tile-heading">Total Classes Created by Staff</div>
                                <div class="tile-body">
                                    <i class="fa-solid fa-hashtag"></i>
                                    <h2 class="float-end">{{ $classescreatedbystaff }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @php
                $statusFilter = request('status', 'all');
                if ($statusFilter === 'active') {
                    $statusFilter = 'ongoing';
                }
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'upcoming' => [
                        'label' => 'Upcoming',
                        'count' => $statusTallies['upcoming'] ?? null,
                    ],
                    'ongoing' => [
                        'label' => 'Ongoing',
                        'count' => $statusTallies['ongoing'] ?? null,
                    ],
                    'completed' => [
                        'label' => 'Completed',
                        'count' => $statusTallies['completed'] ?? null,
                    ],
                ];
                $advancedFiltersOpen = request()->filled('start_date') || request()->filled('end_date');

                $baseMonth = now()->startOfMonth();
                $monthFilterOptions = collect(range(0, 36)) // current month + past 36 months
                    ->map(function ($offset) use ($baseMonth) {
                        $month = $baseMonth->copy()->subMonths($offset);
                        return [
                            'value' => $month->format('Y-m'),
                            'label' => $month->format('F Y'),
                            'start' => $month->copy()->startOfMonth()->format('Y-m-d'),
                            'end' => $month->copy()->endOfMonth()->format('Y-m-d'),
                        ];
                    })
                    ->sortByDesc('start')
                    ->values();
                $monthFilterSelection = request('month_filter');
                $startFilterValue = request('start_date');
                $endFilterValue = request('end_date');
                $currentMonthValue = $baseMonth->format('Y-m');

                if (!$monthFilterSelection) {
                    $matchedMonth = $monthFilterOptions->first(function ($option) use ($startFilterValue, $endFilterValue) {
                        return $startFilterValue && $endFilterValue
                            && $option['start'] === $startFilterValue
                            && $option['end'] === $endFilterValue;
                    });

                    if ($matchedMonth) {
                        $monthFilterSelection = $matchedMonth['value'];
                    } elseif ($startFilterValue || $endFilterValue) {
                        $monthFilterSelection = 'custom';
                    } else {
                        $monthFilterSelection = $currentMonthValue;
                    }
                }
            @endphp

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Class schedules</h4>
                                <p class="text-muted mb-0">Stay on top of upcoming, ongoing, and completed classes with quick filters.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived classes
                                    @else
                                        Showing {{ $data->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.gym-management.schedules') }}" method="GET" id="schedule-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="schedule-status-filter" value="{{ $statusFilter }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                            data-status="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
                                            @if(!is_null($option['count']))
                                                <span class="badge bg-transparent {{ $statusFilter === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $option['count'] }}</span>
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
                                                placeholder="Search"
                                                value="{{ request('name') }}"
                                                aria-label="Search"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 200px;">
                                        <select
                                            class="form-select rounded-pill"
                                            id="month-filter"
                                            name="month_filter"
                                            aria-label="Filter by month"
                                        >
                                            <option value="all" {{ $monthFilterSelection === 'all' ? 'selected' : '' }}>All months</option>
                                            @foreach ($monthFilterOptions as $option)
                                                <option
                                                    value="{{ $option['value'] }}"
                                                    data-start="{{ $option['start'] }}"
                                                    data-end="{{ $option['end'] }}"
                                                    {{ $monthFilterSelection === $option['value'] ? 'selected' : '' }}
                                                >
                                                    {{ $option['label'] }}
                                                </option>
                                            @endforeach
                                            <option value="custom" {{ $monthFilterSelection === 'custom' ? 'selected' : '' }}>Custom range</option>
                                        </select>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.gym-management.schedules', ['show_archived' => 1]) : route('admin.gym-management.schedules') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#scheduleFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="scheduleFiltersModal" tabindex="-1" aria-labelledby="scheduleFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="scheduleFiltersModalLabel">Advanced filters</h5>
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
                                                                name="start_date"
                                                                class="form-control rounded-3"
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
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const printButton = document.getElementById('print-submit-button');
                    const printForm = document.getElementById('print-form');
                    const printLoader = document.getElementById('print-loader');

                    function getBadgeClass(status) {
                        if (status === 'Series pending trainer acceptance') return 'badge-soft-muted';
                        if (status === 'Upcoming') return 'badge-soft-warning';
                        if (status === 'Ongoing') return 'badge-soft-info';
                        if (status === 'Completed') return 'badge-soft-success';
                        return 'badge-soft-muted';
                    }

                    function buildFilters(filters) {
                        const chips = [];
                        if (filters.show_archived) chips.push({ value: 'Archived view' });
                        if (filters.status && filters.status !== 'all') chips.push({ label: 'Status', value: filters.status });
                        if (filters.search) {
                            chips.push({
                                label: 'Search',
                                value: filters.search,
                            });
                        }
                        if (filters.start || filters.end) {
                            const rangeLabel = `${filters.start || '—'} → ${filters.end || '—'}`;
                            chips.push({ label: 'Date', value: `<span class="fw">${rangeLabel}</span>` });
                        }
                        return chips;
                    }

                    function buildRows(items) {
                        return items.map((item) => {
                            const timeRange = item.time_range
                                ? `<div class="muted">Time: ${item.time_range}</div>`
                                : '';
                            const trainerRate = item.trainer_rate
                                ? `<div class="muted">₱${item.trainer_rate} / hr</div>`
                                : '';
                            const creatorRole = item.created_role
                                ? `<div class="muted">${item.created_role}</div>`
                                : '';
                            const creatorName = item.created_by || '—';
                            const seriesRange = item.series_start || item.series_end
                                ? `${item.series_start || '—'} → ${item.series_end || '—'}`
                                : `${item.start || 'Not set'} → ${item.end || '—'}`;
                            return [
                                item.id ?? '—',
                                `<div class="fw">${item.name || '—'}</div><div class="muted">${item.class_code || ''}</div>`,
                                `<div>${item.trainer || '—'}</div>${trainerRate}`,
                                `<div>${seriesRange}</div>${timeRange}<div class="muted">Cadence: ${item.cadence || '—'}</div>`,
                                `<div class="fw">${item.slots ?? 0} slots</div><div class="muted">${item.enrolled ?? 0} enrolled</div>`,
                                `<span class="badge ${getBadgeClass(item.status)}">${item.status || '—'}</span>`,
                                `<div class="fw">${creatorName}</div>${creatorRole}`,
                                `<div>${item.created_at || ''}</div>`,
                            ];
                        });
                    }

                    function renderPrintWindow(payload) {
                        const rawItems = payload && payload.items ? payload.items : [];
                        const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                        const filters = buildFilters(payload.filters || {});
                        const headers = ['#', 'Class', 'Trainer', 'Schedule', 'Enrollment', 'Status', 'Created By', 'Created At'];
                        const rows = buildRows(items);

                        return window.PrintPreview
                            ? PrintPreview.tryOpen(payload, headers, rows, filters)
                            : false;
                    }

                    if (printButton && printForm) {
                        printButton.addEventListener('click', async function (e) {
                            const rawPayload = printButton.dataset.print;
                            const rawAllPayload = printButton.dataset.printAll;
                            if (!rawPayload) return;

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
                });
            </script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const form = document.getElementById('schedule-filter-form');
                    if (!form) {
                        return;
                    }

                    const statusInput = document.getElementById('schedule-status-filter');
                    const chipButtons = form.querySelectorAll('.status-chip');
                    const rangeButtons = form.querySelectorAll('.range-chip');
                    const startInput = document.getElementById('start-date');
                    const endInput = document.getElementById('end-date');
                    const monthSelect = document.getElementById('month-filter');

                    function formatDate(date) {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    }

                    function syncMonthToDates() {
                        if (!monthSelect || !startInput || !endInput) {
                            return;
                        }
                        const selectedOption = monthSelect.options[monthSelect.selectedIndex];
                        if (!selectedOption) {
                            return;
                        }

                        const selectedValue = monthSelect.value;
                        if (selectedValue === 'custom') {
                            return;
                        }

                        if (selectedValue === 'all') {
                            startInput.value = '';
                            endInput.value = '';
                            return;
                        }

                        const startValue = selectedOption.getAttribute('data-start');
                        const endValue = selectedOption.getAttribute('data-end');
                        if (startValue) startInput.value = startValue;
                        if (endValue) endInput.value = endValue;

                    }

                    function submitWithMonthSync() {
                        syncMonthToDates();
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
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
                        if (monthSelect) {
                            monthSelect.value = 'custom';
                        }
                        submitWithMonthSync();
                    }

                    if (form) {
                        form.addEventListener('submit', function () {
                            syncMonthToDates();
                        });
                    }

                    [startInput, endInput].forEach(function (input) {
                        if (!input) return;
                        input.addEventListener('input', function () {
                            if (monthSelect) {
                                monthSelect.value = 'custom';
                            }
                        });
                    });

                    if (monthSelect) {
                        monthSelect.addEventListener('change', function () {
                            syncMonthToDates();
                            submitWithMonthSync();
                        });
                    }

                    const inlineRows = document.querySelectorAll('.resched-inline');
                    const inlineCancelButtons = document.querySelectorAll('.resched-inline-cancel');
                    const actionButtons = document.querySelectorAll('[data-resched-action]');

                    function hideAllInline() {
                        inlineRows.forEach(function (row) {
                            row.classList.add('d-none');
                        });
                    }

                    function updateInline(row, mode) {
                        if (!row) return;
                        const statusInput = row.querySelector('.resched-status-input');
                        const title = row.querySelector('.resched-inline-title');
                        const submitText = row.querySelector('.resched-submit-text');
                        const submitBtn = row.querySelector('.resched-submit-btn');

                        if (statusInput) {
                            statusInput.value = mode === 'reject' ? 2 : 1;
                        }

                        if (title) {
                            title.textContent = mode === 'reject' ? 'Reject reschedule' : 'Approve reschedule';
                        }

                        if (submitText) {
                            submitText.textContent = mode === 'reject' ? 'Reject request' : 'Approve request';
                        }

                        if (submitBtn) {
                            submitBtn.classList.remove('btn-success', 'btn-danger');
                            submitBtn.classList.add(mode === 'reject' ? 'btn-danger' : 'btn-success');
                        }
                    }

                    chipButtons.forEach(function (chip) {
                        chip.addEventListener('click', function () {
                            const selectedStatus = this.dataset.status;
                            statusInput.value = selectedStatus;

                            chipButtons.forEach(function (btn) {
                                btn.classList.remove('btn-dark', 'text-white', 'shadow-sm');
                                if (!btn.classList.contains('btn-outline-secondary')) {
                                    btn.classList.add('btn-outline-secondary');
                                }
                            });

                            this.classList.remove('btn-outline-secondary');
                            this.classList.add('btn-dark', 'text-white', 'shadow-sm');

                            submitWithMonthSync();
                        });
                    });

                    rangeButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            applyRange(this.dataset.range);
                        });
                    });

                    actionButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            const id = this.dataset.id;
                            const mode = this.dataset.mode || 'approve';
                            if (!id) return;
                            const row = document.getElementById(`reschedule-inline-${id}`);
                            if (!row) return;

                            hideAllInline();
                            updateInline(row, mode);
                            row.classList.remove('d-none');
                            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                    });

                    inlineCancelButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            hideAllInline();
                        });
                    });
                });
            </script>

            
            <div class="col-lg-12">
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
            </div>
            @if (!$showArchived)
                <div class="col-lg-12">
                    <div class="box">
                    <style>
                        .pill-chip {
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            justify-content: flex-start;
                            padding: 6px 12px;
                            border-radius: 8px;
                            border: 1px solid var(--pill-border, #d5deec);
                            background: var(--pill-bg, #f5f7fb);
                            color: var(--pill-text, #1f2937);
                            font-weight: 600;
                            font-size: 0.85rem;
                            letter-spacing: 0.01em;
                            box-shadow: none;
                            width: 100%;
                        }
                        .pill-chip::before {
                            content: '';
                            width: 8px;
                            height: 8px;
                            border-radius: 50%;
                            background: var(--pill-dot, #9ca3af);
                            opacity: 0.9;
                        }
                        .pill-chip-success {
                            --pill-bg: #e8f6ef;
                            --pill-border: #c5e5d5;
                            --pill-text: #1f5133;
                            --pill-dot: #2e8b57;
                        }
                        .pill-chip-warning {
                            --pill-bg: #fff4e5;
                            --pill-border: #f3d7a6;
                            --pill-text: #7a4b00;
                            --pill-dot: #e0a100;
                        }
                        .pill-chip-info {
                            --pill-bg: #ecf2ff;
                            --pill-border: #d5def7;
                            --pill-text: #123a6d;
                            --pill-dot: #3b82f6;
                        }
                        .pill-chip-danger {
                            --pill-bg: #fbecec;
                            --pill-border: #f0c4c2;
                            --pill-text: #7b1c1c;
                            --pill-dot: #c0392b;
                        }
                        .pill-chip-muted {
                            --pill-bg: #f1f3f7;
                            --pill-border: #d5deec;
                            --pill-text: #4b5563;
                            --pill-dot: #9ca3af;
                        }
                        .resched-cell {
                            display: flex;
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 8px;
                        }
                    </style>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                            <th class="sortable" data-column="id"># <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_name">Class</th>
                                            <th class="sortable" data-column="trainer">Trainer</th>
                                            <th>User Code</th>
                                            <th class="sortable" data-column="start_date">Schedule</th>
                                            <th>Series of Session</th>
                                            <th>Trainer Status</th>
                                            <th class="sortable" data-column="slots">Enrollment</th>
                                            <th class="sortable" data-column="created_by">Created By</th>
                                            <th>Reschedule</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            @php
                                                $start_date = $item->class_start_date ? \Carbon\Carbon::parse($item->class_start_date) : null;
                                                $end_date = $item->class_end_date ? \Carbon\Carbon::parse($item->class_end_date) : null;
                                                $dayKeys = is_array($item->recurring_days) ? $item->recurring_days : json_decode($item->recurring_days ?? '[]', true);
                                                $dayLabel = collect($dayKeys ?? [])->map(function ($d) use ($weekdayLookup) {
                                                    return $weekdayLookup[$d] ?? ucfirst($d);
                                                })->implode(', ');
                                                $pendingReschedules = $pendingRescheduleRequests->where('schedule_id', $item->id);
                                                $pendingCount = $pendingReschedules->where('status', 0)->count();
                                                $seriesStart = $item->series_start_date ? \Carbon\Carbon::parse($item->series_start_date)->startOfDay() : ($start_date ? $start_date->copy()->startOfDay() : null);
                                                $seriesEnd = $item->series_end_date ? \Carbon\Carbon::parse($item->series_end_date)->endOfDay() : ($end_date ? $end_date->copy()->endOfDay() : null);
                                                $recurringDayKeys = collect($dayKeys ?? [])->map(function ($d) {
                                                    return strtolower($d);
                                                })->toArray();
                                                $sessionTimeLabel = null;
                                                if ($item->class_start_time && $item->class_end_time) {
                                                    $sessionTimeLabel = \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A');
                                                } elseif ($item->class_start_time) {
                                                    $sessionTimeLabel = \Carbon\Carbon::parse($item->class_start_time)->format('g:i A');
                                                }
                                                $allSessionOccurrences = [];
                                                $nowSession = now();
                                                $hasOngoingSession = false;
                                                $hasFutureSession = false;
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
                                                $formatRescheduleDate = function ($date) {
                                                    try {
                                                        return \Carbon\Carbon::parse($date)->format('M j, Y');
                                                    } catch (\Throwable $th) {
                                                        return $date;
                                                    }
                                                };
                                                $rescheduleTimeline = $pendingReschedules
                                                    ->sortByDesc('created_at')
                                                    ->values()
                                                    ->map(function ($reschedule) use ($formatRescheduleDate, $formatTimeLabel) {
                                                        $statusMap = [
                                                            0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark', 'dot' => 'bg-warning'],
                                                            1 => ['label' => 'Approved', 'class' => 'bg-success', 'dot' => 'bg-success'],
                                                            2 => ['label' => 'Rejected', 'class' => 'bg-danger', 'dot' => 'bg-danger'],
                                                        ];
                                                        $statusKey = (int) ($reschedule->status ?? 0);
                                                        $statusMeta = $statusMap[$statusKey] ?? $statusMap[0];

                                                        $targetDates = collect($reschedule->target_session_dates ?? []);
                                                        $proposedDates = collect($reschedule->proposed_session_dates ?? []);

                                                        $targetLabel = $targetDates->map($formatRescheduleDate)->filter()->implode(', ');
                                                        $proposedLabel = $proposedDates->map($formatRescheduleDate)->filter()->implode(', ');

                                                        return [
                                                            'target_label' => $targetLabel ?: 'Target dates not set',
                                                            'proposed_label' => $proposedLabel ?: ($targetLabel ?: 'Same dates'),
                                                            'time_label' => $formatTimeLabel($reschedule->proposed_start_time, $reschedule->proposed_end_time) ?: 'Time not set',
                                                            'created_label' => optional($reschedule->created_at)->format('M j, Y') ?? null,
                                                            'notes' => $reschedule->notes ?: null,
                                                            'status_label' => $statusMeta['label'],
                                                            'status_class' => $statusMeta['class'],
                                                            'status_dot_class' => $statusMeta['dot'],
                                                            'status_value' => $statusKey,
                                                        ];
                                                    });
                                                $sessionOverridesRaw = is_array($item->session_overrides)
                                                    ? $item->session_overrides
                                                    : json_decode($item->session_overrides ?? '[]', true);
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
                                                $computeStatus = function ($sessionStart, $sessionEnd, $shouldAffectSchedule = true) use (&$hasOngoingSession, &$hasFutureSession, $nowSession) {
                                                    $status = 'Upcoming';
                                                    if ($nowSession->between($sessionStart, $sessionEnd, true)) {
                                                        $status = 'Ongoing';
                                                        if ($shouldAffectSchedule) {
                                                            $hasOngoingSession = true;
                                                        }
                                                    } elseif ($nowSession->gt($sessionEnd)) {
                                                        $status = 'Completed';
                                                    } else {
                                                        if ($shouldAffectSchedule) {
                                                            $hasFutureSession = true;
                                                        }
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
                                                    $cursor = $seriesStart->copy();
                                                    while ($cursor->lte($seriesEnd)) {
                                                        $dayKey = strtolower(substr($cursor->format('D'), 0, 3));
                                                        if (in_array($dayKey, $recurringDayKeys, true)) {
                                                            $sessionDateKey = $cursor->toDateString();
                                                            $sessionStart = $item->class_start_time
                                                                ? $cursor->copy()->setTimeFromTimeString($item->class_start_time)
                                                                : $cursor->copy()->startOfDay();
                                                            $sessionEnd = $item->class_end_time
                                                                ? $cursor->copy()->setTimeFromTimeString($item->class_end_time)
                                                                : $cursor->copy()->endOfDay();

                                                            $override = $sessionOverrides[$sessionDateKey] ?? null;

                                                            if ($override) {
                                                                $allSessionOccurrences[] = [
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
                                                                $overrideStartTime = $override['start_time'] ?? $item->class_start_time;
                                                                $overrideEndTime = $override['end_time'] ?? $item->class_end_time;

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

                                                                $allSessionOccurrences[] = [
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

                                                                $allSessionOccurrences[] = [
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

                                                if (!count($allSessionOccurrences) && $start_date) {
                                                    $sessionStart = $start_date->copy();
                                                    $sessionEnd = $end_date ?: ($item->class_end_time
                                                        ? $sessionStart->copy()->setTimeFromTimeString($item->class_end_time)
                                                        : $sessionStart->copy()->endOfDay());

                                                    $override = $sessionOverrides[$sessionStart->toDateString()] ?? null;

                                                    if ($override) {
                                                        $allSessionOccurrences[] = [
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
                                                        $overrideStartTime = $override['start_time'] ?? $item->class_start_time;
                                                        $overrideEndTime = $override['end_time'] ?? $item->class_end_time;

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

                                                        $allSessionOccurrences[] = [
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

                                                        $allSessionOccurrences[] = [
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
                                                if (count($allSessionOccurrences)) {
                                                    $allSessionOccurrences = collect($allSessionOccurrences)
                                                        ->sortBy('sort_key')
                                                        ->values()
                                                        ->all();
                                                }

                                                $scheduleStatus = 'Upcoming';
                                                $scheduleBadgeClass = 'bg-warning';
                                                if ($hasOngoingSession) {
                                                    $scheduleStatus = 'Ongoing';
                                                    $scheduleBadgeClass = 'bg-info text-dark';
                                                } elseif (!$hasFutureSession) {
                                                    $scheduleStatus = 'Completed';
                                                    $scheduleBadgeClass = 'bg-success';
                                                }

                                                $trainer = optional($item->user);
                                                $isTrainerArchived = (int) ($trainer->is_archive ?? 0) === 1;
                                                $trainerName = $item->trainer_id == 0
                                                    ? 'No Trainer for now'
                                                    : trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
                                                $trainerDisplay = $isTrainerArchived ? '' : $trainerName;
                                                $trainerCodeDisplay = ($item->trainer_id == 0 || $isTrainerArchived)
                                                    ? '—'
                                                    : ($trainer->user_code ?? '—');
                                                $hasEnrolledUsers = ($item->user_schedules_count ?? 0) > 0;
                                                $trainerAcceptanceStatus = (int) ($item->istrainerapproved ?? 0);
                                                $trainerAcceptanceMap = [
                                                    0 => ['label' => "Waiting for the acceptance of the trainer", 'class' => 'pill-chip-muted'],
                                                    1 => ['label' => "Trainer's approved", 'class' => 'pill-chip-success'],
                                                    2 => ['label' => "Trainer's rejected", 'class' => 'pill-chip-danger'],
                                                ];
                                                $trainerAcceptancePill = $trainerAcceptanceMap[$trainerAcceptanceStatus] ?? $trainerAcceptanceMap[0];
                                            @endphp
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $item->name }}</div>
                                                    <div class="text-muted small">{{ $item->class_code }}</div>
                                                </td>
                                                <td>
                                                    {{ $trainerDisplay }}
                                                </td>
                                                <td>
                                                    <span class="text-muted small">
                                                        {{ $trainerCodeDisplay }}
                                                    </span>
                                                </td>
                                                <td class="small">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div class="d-flex flex-column gap-1">
                                                            {{-- <div class="fw-semibold">
                                                                @if($start_date || $end_date)
                                                                    {{ $start_date ? $start_date->format('M j, Y') : 'Start not set' }}
                                                                    @if($end_date)
                                                                        <span class="text-muted small">→ {{ $end_date->format('M j, Y g:i A') }}</span>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">Schedule not set</span>
                                                                @endif
                                                            </div> --}}
                                                            <div class="fw-semibold">
                                                                <i class="fa-regular fa-clock me-1"></i>
                                                                {{ $item->class_start_time && $item->class_end_time
                                                                    ? \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A')
                                                                    : 'Time not set' }}
                                                            </div>
                                                            <div class="text-muted small">
                                                                <i class="fa-solid fa-rotate me-1"></i>{{ $dayLabel ?: 'One-time' }}
                                                            </div>
                                                            @if($seriesStart || $seriesEnd)
                                                                <div class="text-muted small">
                                                                    <i class="fa-regular fa-calendar-days me-1"></i>
                                                                    Series: {{ $seriesStart ? $seriesStart->format('M j, Y') : '—' }} → {{ $seriesEnd ? $seriesEnd->format('M j, Y') : '—' }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="small">
                                                    <div class="d-flex flex-column gap-2">
                                                        @if($trainerAcceptanceStatus === 0)
                                                            <span class="pill-chip pill-chip-muted">Series pending trainer acceptance</span>
                                                        @elseif(count($allSessionOccurrences))
                                                            @php
                                                                $seriesPillClass = match ($scheduleStatus) {
                                                                    'Ongoing' => 'pill-chip-info',
                                                                    'Completed' => 'pill-chip-success',
                                                                    'Upcoming' => 'pill-chip-warning',
                                                                    default => 'pill-chip-muted',
                                                                };
                                                            @endphp
                                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                                                <span class="pill-chip {{ $seriesPillClass }}">{{ $scheduleStatus }}</span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-link btn-sm px-0"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#session-series-{{ $item->id }}"
                                                                    aria-expanded="false"
                                                                    aria-controls="session-series-{{ $item->id }}"
                                                                    data-session-toggle
                                                                    data-collapsed-text="Show all sessions"
                                                                    data-expanded-text="Hide sessions"
                                                                >
                                                                    Show all sessions
                                                                </button>
                                                            </div>
                                                            <div class="collapse mt-2" id="session-series-{{ $item->id }}">
                                                                <div class="border rounded-3 p-2 bg-light-subtle" style="max-height: 260px; overflow: auto;">
                                                                    <div class="d-flex flex-column gap-2">
                                                                        @foreach($allSessionOccurrences as $fullIndex => $session)
                                                                            <div class="d-flex align-items-start gap-2">
                                                                                <div class="d-flex flex-column align-items-center me-1">
                                                                                    <span class="rounded-circle bg-primary" style="width: 10px; height: 10px;"></span>
                                                                                    @if($fullIndex < count($allSessionOccurrences) - 1)
                                                                                        <span class="mt-1" style="width: 2px; height: 20px; background-color: #e9ecef;"></span>
                                                                                    @endif
                                                                                </div>
                                                                                <div>
                                                                                    @php
                                                                                        $isRescheduled = !empty($session['is_rescheduled']);
                                                                                        $rescheduleTarget = $session['reschedule_target_label'] ?? null;
                                                                                        $rescheduledFrom = $session['rescheduled_from'] ?? null;
                                                                                    @endphp
                                                                                    <div class="fw-semibold {{ $isRescheduled ? 'text-decoration-line-through text-muted' : '' }}">{{ $session['label'] }}</div>
                                                                                    <div class="text-muted small">
                                                                                        {{ $session['weekday'] }}{{ $session['time'] ? ' • ' . $session['time'] : '' }}
                                                                                        @if($isRescheduled && $rescheduleTarget)
                                                                                            <span class="ms-2 fst-italic">→ {{ $rescheduleTarget }}</span>
                                                                                        @elseif(!$isRescheduled && $rescheduledFrom)
                                                                                            <span class="ms-2 fst-italic">From {{ $rescheduledFrom }}</span>
                                                                                        @endif
                                                                                    </div>
                                                                                    <span class="badge {{ $session['status_class'] }} px-2 py-1">{{ $session['status'] }}</span>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">Series not set</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="small">
                                                    <span class="pill-chip {{ $trainerAcceptancePill['class'] }}">{{ $trainerAcceptancePill['label'] }}</span>
                                                </td>
                                                <td class="small">
                                                    <div class="fw-semibold">{{ $item->slots }} slots</div>
                                                    <div>
                                                        <a 
                                                            href="{{ route('admin.gym-management.schedules.users', $item->id) }}"
                                                            class="text-primary"
                                                            title="View enrolled users"
                                                        >
                                                            {{ $item->user_schedules_count }} enrolled
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="small">
                                                    <div class="fw-semibold">{{ $item->created_by ?: '—' }}</div>
                                                    @if(!empty($item->created_role))
                                                        <div class="text-muted small">{{ $item->created_role }}</div>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @php
                                                        $rescheduleCount = $rescheduleTimeline->count();
                                                    @endphp
                                                    @if($rescheduleCount)
                                                        <div class="resched-cell">
                                                            <span class="pill-chip {{ $pendingCount ? 'pill-chip-warning' : 'pill-chip-muted' }}">
                                                                {{ $pendingCount ? $pendingCount . ' pending' : 'No pending' }}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                class="btn btn-link btn-sm px-0 text-decoration-none fw-semibold"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#reschedule-series-{{ $item->id }}"
                                                                aria-expanded="false"
                                                                aria-controls="reschedule-series-{{ $item->id }}"
                                                                data-session-toggle
                                                                data-collapsed-text="Show reschedules"
                                                                data-expanded-text="Hide reschedules"
                                                            >
                                                                Show reschedules
                                                            </button>
                                                        </div>
                                                        <div class="collapse mt-2" id="reschedule-series-{{ $item->id }}">
                                                            <div class="border rounded-3 p-2 bg-light-subtle" style="max-height: 260px; overflow: auto;">
                                                                <div class="d-flex flex-column gap-2">
                                                                    @foreach($rescheduleTimeline as $reschedIndex => $resched)
                                                                        <div class="d-flex align-items-start gap-2">
                                                                            <div class="d-flex flex-column align-items-center me-1">
                                                                                <span class="rounded-circle {{ $resched['status_dot_class'] }}" style="width: 10px; height: 10px;"></span>
                                                                                @if($reschedIndex < $rescheduleCount - 1)
                                                                                    <span class="mt-1" style="width: 2px; height: 20px; background-color: #e9ecef;"></span>
                                                                                @endif
                                                                            </div>
                                                                            <div class="flex-grow-1">
                                                                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                                    <div>
                                                                                        <div class="fw-semibold">Target: {{ $resched['target_label'] }}</div>
                                                                                        <div class="fw-semibold text-success">Proposed: {{ $resched['proposed_label'] }}</div>
                                                                                        <div class="text-muted small">
                                                                                            {{ $resched['time_label'] }}
                                                                                            @if($resched['created_label'])
                                                                                                <span class="ms-2">Requested {{ $resched['created_label'] }}</span>
                                                                                            @endif
                                                                                        </div>
                                                                                        @if($resched['notes'])
                                                                                            <div class="text-muted small fst-italic">Notes: {{ $resched['notes'] }}</div>
                                                                                        @endif
                                                                                    </div>
                                                                                    <span class="badge {{ $resched['status_class'] }} px-2 py-1">{{ $resched['status_label'] }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="resched-cell">
                                                            <span class="pill-chip pill-chip-muted">No pending</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                        <div class="action-button">
                                                            <a href="{{ route('admin.gym-management.schedules.view', $item->id) }}" title="View">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </a>
                                                        </div>
                                                        @unless($hasEnrolledUsers)
                                                            @if($scheduleStatus !== 'Completed')
                                                                <div class="action-button">
                                                                    <a href="{{ route('admin.gym-management.schedules.edit', $item->id) }}" title="Edit">
                                                                        <i class="fa-solid fa-pencil text-primary"></i>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            <div class="action-button">
                                                                <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Archive" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                    <i class="fa-solid fa-box-archive text-danger"></i>
                                                                </button>
                                                            </div>
                                                        @endunless
                                                    </div>
                                                </td>
                                            </tr>
                                            @unless($hasEnrolledUsers)
                                                <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow rounded-4">
                                                            <div class="modal-header border-0 pb-0">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-uppercase text-muted small mb-1">Archive class</p>
                                                                        <h5 class="fw-semibold mb-0" id="deleteModalLabel-{{ $item->id }}">
                                                                            {{ $item->name ?? 'Class' }} ({{ $item->class_code ?? '—' }})
                                                                        </h5>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('admin.gym-management.schedules.delete') }}" method="POST" id="delete-modal-form-{{ $item->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                                <div class="modal-body pt-3">
                                                                    <div class="alert alert-danger bg-opacity-10 text-danger border-0 rounded-3">
                                                                        Archiving will move this class{{ $item->user_schedules_count ? ' and its enrollments' : '' }} to the archived list. You can restore it later if needed.
                                                                    </div>
                                                                    <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                                    <div class="input-group">
                                                                        <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                        <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 pt-0">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                    <button class="btn btn-danger" type="submit" id="delete-modal-submit-button-{{ $item->id }}">
                                                                        <span id="delete-modal-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                        Archive class
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <script>
                                                    document.getElementById('delete-modal-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                        const submitButton = document.getElementById('delete-modal-submit-button-{{ $item->id }}');
                                                        const loader = document.getElementById('delete-modal-loader-{{ $item->id }}');
                                            
                                                        submitButton.disabled = true;
                                                        loader.classList.remove('d-none');
                                                    });
                                                </script>
                                            @endunless
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($showArchived)
            <div class="col-lg-12">
                <div class="box mt-5">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                    <h4 class="fw-semibold mb-0">Archived Classes</h4>
                                    <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                                </div>
                                <div class="table-responsive mb-3">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Class Name</th>
                                                <th>Class Code</th>
                                                <th>Trainer</th>
                                                <th>Trainer Rate / Hour</th>
                                                <th>Slots</th>
                                                <th>Members</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Created By</th>
                                                <th>Updated Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($archivedData as $archive)
                                                @php
                                                    $archiveStart = $archive->class_start_date ? \Carbon\Carbon::parse($archive->class_start_date) : null;
                                                    $archiveEnd = $archive->class_end_date ? \Carbon\Carbon::parse($archive->class_end_date) : null;
                                                    $archiveTrainer = optional($archive->user);
                                                    $archiveTrainerArchived = (int) ($archiveTrainer->is_archive ?? 0) === 1;
                                                    $archiveTrainerName = $archive->trainer_id == 0
                                                        ? 'No Trainer for now'
                                                        : trim(($archiveTrainer->first_name ?? '') . ' ' . ($archiveTrainer->last_name ?? ''));
                                                    $archiveTrainerDisplay = $archiveTrainerArchived ? '' : $archiveTrainerName;
                                                @endphp
                                                <tr>
                                                    <td>{{ $archive->id }}</td>
                                                    <td>{{ $archive->name }}</td>
                                                    <td>{{ $archive->class_code }}</td>
                                                    <td>{{ $archiveTrainerDisplay }}</td>
                                                    <td>
                                                        @if($archive->trainer_id == 0 || is_null($archive->trainer_rate_per_hour))
                                                            —
                                                        @else
                                                            ₱{{ number_format((float) $archive->trainer_rate_per_hour, 2) }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $archive->slots }}</td>
                                                    <td>{{ $archive->user_schedules_count }}</td>
                                                    <td>{{ $archiveStart ? $archiveStart->format('F j, Y g:iA') : '' }}</td>
                                                    <td>{{ $archiveEnd ? $archiveEnd->format('F j, Y g:iA') : '' }}</td>
                                                    <td class="small">
                                                        <div class="fw-semibold">{{ $archive->created_by ?: '—' }}</div>
                                                        @if(!empty($archive->created_role))
                                                            <div class="text-muted small">{{ $archive->created_role }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $archive->updated_at }}</td>
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
                                                <div class="modal fade" id="archiveRestoreModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveRestoreModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore class ({{ $archive->class_code }})?</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('admin.gym-management.schedules.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
                                                                @csrf
                                                                {{-- @method('PUT') --}}
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
                                                                <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete archived class ({{ $archive->class_code }}) permanently?</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('admin.gym-management.schedules.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
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
                                                                        Submit
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <script>
                                                    document.getElementById('archive-restore-modal-form-{{ $archive->id }}').addEventListener('submit', function(e) {
                                                        const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                                        const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');

                                                        submitButton.disabled = true;
                                                        loader.classList.remove('d-none');
                                                    });
                                                </script>
                                                <script>
                                                    document.getElementById('archive-delete-modal-form-{{ $archive->id }}').addEventListener('submit', function(e) {
                                                        const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                                        const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');

                                                        submitButton.disabled = true;
                                                        loader.classList.remove('d-none');
                                                    });
                                                </script>
                                            @empty
                                                <tr>
                                                    <td colspan="12" class="text-center text-muted">No archived classes found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    {{ $archivedData->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggles = document.querySelectorAll('[data-session-toggle]');
            toggles.forEach(function (btn) {
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }
        });
    </script>
@endsection
