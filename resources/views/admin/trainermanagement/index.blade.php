@extends('layouts.admin')
@section('title', 'Trainer Management - Index')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $printSource = $showArchived ? $archivedData : $trainers;
                $printTrainers = collect($printSource->items())->map(function ($item) {
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
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                        'created_by' => $item->created_by ?: '',
                    ];
                })->values();

                $printPayload = [
                    'title' => $showArchived ? 'Archived trainers' : 'Trainer directory',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'search_column' => request('search_column'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printTrainers->count(),
                    'items' => $printTrainers,
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
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <button
                            class="btn btn-danger"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
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
                $advancedFiltersOpen = request()->filled('search_column') || request()->filled('start_date') || request()->filled('end_date');
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
                                                placeholder="Search trainers"
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
                                                    <label for="search-column" class="form-label text-muted text-uppercase small mb-1">Search by</label>
                                                    <select id="search-column" name="search_column" class="form-select rounded-3">
                                                        <option value="" disabled {{ request('search_column') ? '' : 'selected' }}>Select Option</option>
                                                        <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>#</option>
                                                        <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Name</option>
                                                        <option value="phone_number" {{ request('search_column') == 'phone_number' ? 'selected' : '' }}>Phone Number</option>
                                                        <option value="email" {{ request('search_column') == 'email' ? 'selected' : '' }}>Email</option>
                                                        <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                                        <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                                        <option value="created_by" {{ request('search_column') == 'created_by' ? 'selected' : '' }}>Created By</option>
                                                    </select>
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
                            <div class="table-responsive">
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
                                                <td>{{ $item->user_code }}</td>
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

                                                    $scheduleDetails = $trainerSchedules->map(function ($schedule) use ($now) {
                                                        $start = !empty($schedule->class_start_date) ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                        $end = !empty($schedule->class_end_date) ? \Carbon\Carbon::parse($schedule->class_end_date) : null;

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

                                                    $futureScheduleDetails = $scheduleDetails->filter(function ($detail) {
                                                        return $detail['category'] === 'future';
                                                    });

                                                    $pastScheduleDetails = $scheduleDetails->filter(function ($detail) {
                                                        return $detail['category'] === 'past';
                                                    });

                                                    $futureScheduleCount = $futureScheduleDetails->count();
                                                    $pastScheduleCount = $pastScheduleDetails->count();
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
                                                    <div class="d-flex">
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
                                                            <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <i class="fa-solid fa-trash text-danger"></i>
                                                            </button>
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
                                                    'generated_at' => now()->format('M d, Y g:i A'),
                                                    'summary' => [
                                                        'total' => $totalAssignments,
                                                        'future' => $futureScheduleCount,
                                                        'past' => $pastScheduleCount,
                                                        'future_hours' => (float) $futureHours,
                                                        'past_hours' => (float) $pastHours,
                                                    ],
                                                    'items' => $scheduleDetails->map(function ($detail) {
                                                        $schedule = $detail['schedule'];
                                                        $start = $detail['start'];
                                                        $end = $detail['end'];
                                                        $students = $detail['students'] ?? collect();
                                                        $categoryLabel = $detail['category'] === 'past' ? 'Past' : 'Upcoming';

                                                        return [
                                                            'name' => $schedule->name ?? 'Unnamed Schedule',
                                                            'class_code' => $schedule->class_code ?? null,
                                                            'category' => $detail['category'],
                                                            'category_label' => $categoryLabel,
                                                            'start_label' => $start ? $start->format('M j, Y g:i A') : 'Not set',
                                                            'end_label' => $end ? $end->format('M j, Y g:i A') : '—',
                                                            'start_date' => $detail['start_date'] ?? null,
                                                            'end_date' => $detail['end_date'] ?? null,
                                                            'hours' => isset($detail['hours']) ? (float) $detail['hours'] : null,
                                                            'students' => collect($students)->values()->all(),
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
                                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header align-items-center">
                                                            <h5 class="modal-title mb-0" id="assignmentsModalLabel-{{ $item->id }}">Assignments for {{ $item->first_name }} {{ $item->last_name }}</h5>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-secondary btn-sm"
                                                                    data-print-modal="assignmentsModal-{{ $item->id }}"
                                                                    data-print='@json($assignmentPrintPayload)'
                                                                >
                                                                    <i class="fa-solid fa-print me-1"></i>Print
                                                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" data-print-loader></span>
                                                                </button>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($scheduleDetails->isNotEmpty())
                                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                                                    <span class="badge bg-dark text-white rounded-pill px-3 py-2" data-role="total-count">
                                                                        {{ $totalAssignments }} {{ $totalAssignments === 1 ? 'assignment' : 'assignments' }}
                                                                    </span>
                                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                                                        Upcoming: <span data-role="future-count">{{ $futureScheduleCount }} {{ $futureScheduleCount === 1 ? 'assignment' : 'assignments' }}</span>
                                                                    </span>
                                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">
                                                                        Past: <span data-role="past-count">{{ $pastScheduleCount }} {{ $pastScheduleCount === 1 ? 'assignment' : 'assignments' }}</span>
                                                                    </span>
                                                                    <span class="badge bg-light text-muted rounded-pill px-3 py-2">
                                                                        Hours (upcoming): <span data-role="future-hours">{{ number_format($futureHours, 2) }} hrs</span>
                                                                    </span>
                                                                    <span class="badge bg-light text-muted rounded-pill px-3 py-2">
                                                                        Hours (past): <span data-role="past-hours">{{ number_format($pastHours, 2) }} hrs</span>
                                                                    </span>
                                                                </div>
                                                                <div class="row g-3 mb-4">
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <div class="d-flex align-items-center justify-content-between">
                                                                                <div class="d-flex align-items-center gap-2">
                                                                                    <span class="badge bg-success text-white rounded-circle p-2"><i class="fa-solid fa-calendar-check"></i></span>
                                                                                    <span class="text-muted small text-uppercase fw-semibold">Upcoming</span>
                                                                                </div>
                                                                                <span class="text-muted small" data-role="future-count">
                                                                                    {{ $futureScheduleCount }} {{ $futureScheduleCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fw-semibold">Hours</span>
                                                                                <span class="badge bg-success-subtle text-success rounded-pill px-3" data-role="future-hours">{{ number_format($futureHours, 2) }} hrs</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <div class="d-flex align-items-center justify-content-between">
                                                                                <div class="d-flex align-items-center gap-2">
                                                                                    <span class="badge bg-secondary text-white rounded-circle p-2"><i class="fa-solid fa-clipboard-check"></i></span>
                                                                                    <span class="text-muted small text-uppercase fw-semibold">Past</span>
                                                                                </div>
                                                                                <span class="text-muted small" data-role="past-count">
                                                                                    {{ $pastScheduleCount }} {{ $pastScheduleCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fw-semibold">Hours</span>
                                                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3" data-role="past-hours">{{ number_format($pastHours, 2) }} hrs</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="assignment-filters border rounded-3 p-3 mb-4 bg-light">
                                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Assignment category filter">
                                                                            <button type="button" class="btn btn-outline-secondary active" data-category-filter="all">All</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="future">Upcoming</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="past">Past</button>
                                                                        </div>
                                                                        <button type="button" class="btn btn-link btn-sm ms-auto text-decoration-none px-0" data-filter-reset>Reset filters</button>
                                                                    </div>
                                                                    <div class="row g-2 mt-3">
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label small text-muted mb-1">Start date from</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-start>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label small text-muted mb-1">End date until</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-end>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="assignment-list">
                                                                    @foreach($scheduleDetails as $detail)
                                                                        @php
                                                                            $schedule = $detail['schedule'];
                                                                            $start = $detail['start'];
                                                                            $end = $detail['end'];
                                                                            $students = $detail['students'];
                                                                            $category = $detail['category'];
                                                                            $categoryLabel = $category === 'future' ? 'Upcoming' : 'Past';
                                                                            $badgeClass = $category === 'future' ? 'bg-success text-white' : 'bg-secondary';
                                                                            $rangeStart = $start ? $start->format('F j, Y g:i A') : 'N/A';
                                                                            $rangeEnd = $end ? $end->format('F j, Y g:i A') : null;
                                                                            $hours = $detail['hours'];
                                                                            $displaySalary = $detail['display_salary'];
                                                                            $summarySalary = $detail['summary_salary'];
                                                                            $isSalaryEligible = $detail['salary_eligible'];
                                                                        @endphp
                                                                            <div
                                                                                class="border rounded-3 p-3 mb-3 assignment-card"
                                                                                data-assignment-card
                                                                                data-category="{{ $category }}"
                                                                                data-start="{{ $detail['start_date'] ?? '' }}"
                                                                                data-end="{{ $detail['end_date'] ?? '' }}"
                                                                                data-hours="{{ $hours }}"
                                                                            >
                                                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                                                <div class="d-flex align-items-start gap-3">
                                                                                    <span class="badge bg-dark-subtle text-dark rounded-circle p-2"><i class="fa-solid fa-dumbbell"></i></span>
                                                                                    <div>
                                                                                        <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                                                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                                                                            @if(!empty($schedule->class_code))
                                                                                                <span class="badge bg-light text-muted border">Code: {{ $schedule->class_code }}</span>
                                                                                            @endif
                                                                                            @if($hours > 0)
                                                                                                <span class="badge bg-primary-subtle text-primary">{{ number_format($hours, 2) }} hrs</span>
                                                                                            @endif
                                                                                        </div>
                                                                                        @if($start || $end)
                                                                                            <span class="text-muted small d-block mt-1">
                                                                                                {{ $rangeStart }}
                                                                                                @if($rangeEnd)
                                                                                                    &ndash; {{ $rangeEnd }}
                                                                                                @endif
                                                                                            </span>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                                <span class="badge {{ $badgeClass }}">{{ $categoryLabel }}</span>
                                                                            </div>
                                                                            <div class="mt-3">
                                                                                <span class="text-muted small text-uppercase fw-semibold">Students</span>
                                                                                @if($students->isNotEmpty())
                                                                                    <ul class="list-unstyled mb-0 small mt-1">
                                                                                        @foreach($students as $student)
                                                                                            <li>{{ $student }}</li>
                                                                                        @endforeach
                                                                                    </ul>
                                                                                @else
                                                                                    <p class="text-muted small mb-0">No students assigned.</p>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="text-muted mb-0">No schedules assigned.</p>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel-{{ $item->id }}">Move trainer ({{ $item->email }}) to archive?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.trainer-management.delete') }}" method="POST" id="main-form-{{ $item->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                    <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Archive
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
                                {{ $trainers->links() }}
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

                                                    $archivedScheduleDetails = $archivedSchedules->map(function ($schedule) use ($archivedNow) {
                                                        $start = !empty($schedule->class_start_date) ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                        $end = !empty($schedule->class_end_date) ? \Carbon\Carbon::parse($schedule->class_end_date) : null;

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
                                                            $isPast = $end->lt($archivedNow);
                                                        } elseif ($start) {
                                                            $isPast = $start->lt($archivedNow);
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
                                                    'generated_at' => now()->format('M d, Y g:i A'),
                                                    'summary' => [
                                                        'total' => $archivedTotalAssignments,
                                                        'future' => $archivedFutureCount,
                                                        'past' => $archivedPastCount,
                                                        'future_hours' => (float) $archivedFutureHours,
                                                        'past_hours' => (float) $archivedPastHours,
                                                    ],
                                                    'items' => $archivedScheduleDetails->map(function ($detail) {
                                                        $schedule = $detail['schedule'];
                                                        $start = $detail['start'];
                                                        $end = $detail['end'];
                                                        $students = $detail['students'] ?? collect();
                                                        $categoryLabel = $detail['category'] === 'past' ? 'Past' : 'Upcoming';

                                                        return [
                                                            'name' => $schedule->name ?? 'Unnamed Schedule',
                                                            'class_code' => $schedule->class_code ?? null,
                                                            'category' => $detail['category'],
                                                            'category_label' => $categoryLabel,
                                                            'start_label' => $start ? $start->format('M j, Y g:i A') : 'Not set',
                                                            'end_label' => $end ? $end->format('M j, Y g:i A') : '—',
                                                            'start_date' => $detail['start_date'] ?? null,
                                                            'end_date' => $detail['end_date'] ?? null,
                                                            'hours' => isset($detail['hours']) ? (float) $detail['hours'] : null,
                                                            'students' => collect($students)->values()->all(),
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
                                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header align-items-center">
                                                            <h5 class="modal-title mb-0" id="archiveAssignmentsModalLabel-{{ $archive->id }}">Assignments for {{ $archive->first_name }} {{ $archive->last_name }}</h5>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-secondary btn-sm"
                                                                    data-print-modal="archiveAssignmentsModal-{{ $archive->id }}"
                                                                    data-print='@json($archivedAssignmentPrintPayload)'
                                                                >
                                                                    <i class="fa-solid fa-print me-1"></i>Print
                                                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" data-print-loader></span>
                                                                </button>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($archivedScheduleDetails->isNotEmpty())
                                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                                                    <span class="badge bg-dark text-white rounded-pill px-3 py-2" data-role="total-count">
                                                                        {{ $archivedTotalAssignments }} {{ $archivedTotalAssignments === 1 ? 'assignment' : 'assignments' }}
                                                                    </span>
                                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                                                        Upcoming: <span data-role="future-count">{{ $archivedFutureCount }} {{ $archivedFutureCount === 1 ? 'assignment' : 'assignments' }}</span>
                                                                    </span>
                                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">
                                                                        Past: <span data-role="past-count">{{ $archivedPastCount }} {{ $archivedPastCount === 1 ? 'assignment' : 'assignments' }}</span>
                                                                    </span>
                                                                    <span class="badge bg-light text-muted rounded-pill px-3 py-2">
                                                                        Hours (upcoming): <span data-role="future-hours">{{ number_format($archivedFutureHours, 2) }} hrs</span>
                                                                    </span>
                                                                    <span class="badge bg-light text-muted rounded-pill px-3 py-2">
                                                                        Hours (past): <span data-role="past-hours">{{ number_format($archivedPastHours, 2) }} hrs</span>
                                                                    </span>
                                                                </div>
                                                                <div class="row g-3 mb-4">
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <div class="d-flex align-items-center justify-content-between">
                                                                                <div class="d-flex align-items-center gap-2">
                                                                                    <span class="badge bg-success text-white rounded-circle p-2"><i class="fa-solid fa-calendar-check"></i></span>
                                                                                    <span class="text-muted small text-uppercase fw-semibold">Upcoming</span>
                                                                                </div>
                                                                                <span class="text-muted small" data-role="future-count">
                                                                                    {{ $archivedFutureCount }} {{ $archivedFutureCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fw-semibold">Hours</span>
                                                                                <span class="badge bg-success-subtle text-success rounded-pill px-3" data-role="future-hours">{{ number_format($archivedFutureHours, 2) }} hrs</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <div class="d-flex align-items-center justify-content-between">
                                                                                <div class="d-flex align-items-center gap-2">
                                                                                    <span class="badge bg-secondary text-white rounded-circle p-2"><i class="fa-solid fa-clipboard-check"></i></span>
                                                                                    <span class="text-muted small text-uppercase fw-semibold">Past</span>
                                                                                </div>
                                                                                <span class="text-muted small" data-role="past-count">
                                                                                    {{ $archivedPastCount }} {{ $archivedPastCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fw-semibold">Hours</span>
                                                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3" data-role="past-hours">{{ number_format($archivedPastHours, 2) }} hrs</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="assignment-filters border rounded-3 p-3 mb-4 bg-light">
                                                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                                                        <div class="btn-group btn-group-sm" role="group" aria-label="Assignment category filter">
                                                                            <button type="button" class="btn btn-outline-secondary active" data-category-filter="all">All</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="future">Upcoming</button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-category-filter="past">Past</button>
                                                                        </div>
                                                                        <button type="button" class="btn btn-link btn-sm ms-auto text-decoration-none px-0" data-filter-reset>Reset filters</button>
                                                                    </div>
                                                                    <div class="row g-2 mt-3">
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label small text-muted mb-1">Start date from</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-start>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <label class="form-label small text-muted mb-1">End date until</label>
                                                                            <input type="date" class="form-control form-control-sm" data-filter-end>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="assignment-list">
                                                                    @foreach($archivedScheduleDetails as $detail)
                                                                        @php
                                                                            $schedule = $detail['schedule'];
                                                                            $start = $detail['start'];
                                                                            $end = $detail['end'];
                                                                            $students = $detail['students'];
                                                                            $category = $detail['category'];
                                                                            $categoryLabel = $category === 'future' ? 'Upcoming' : 'Past';
                                                                            $badgeClass = $category === 'future' ? 'bg-success text-white' : 'bg-secondary';
                                                                            $rangeStart = $start ? $start->format('F j, Y g:i A') : 'N/A';
                                                                            $rangeEnd = $end ? $end->format('F j, Y g:i A') : null;
                                                                            $hours = $detail['hours'];
                                                                            $displaySalary = $detail['display_salary'];
                                                                            $summarySalary = $detail['summary_salary'];
                                                                            $isSalaryEligible = $detail['salary_eligible'];
                                                                        @endphp
                                                                        <div
                                                                            class="border rounded-3 p-3 mb-3 assignment-card"
                                                                            data-assignment-card
                                                                            data-category="{{ $category }}"
                                                                            data-start="{{ $detail['start_date'] ?? '' }}"
                                                                            data-end="{{ $detail['end_date'] ?? '' }}"
                                                                            data-hours="{{ $hours }}"
                                                                        >
                                                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                                                <div class="d-flex align-items-start gap-3">
                                                                                    <span class="badge bg-dark-subtle text-dark rounded-circle p-2"><i class="fa-solid fa-dumbbell"></i></span>
                                                                                    <div>
                                                                                        <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                                                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                                                                            @if(!empty($schedule->class_code))
                                                                                                <span class="badge bg-light text-muted border">Code: {{ $schedule->class_code }}</span>
                                                                                            @endif
                                                                                            @if($hours > 0)
                                                                                                <span class="badge bg-primary-subtle text-primary">{{ number_format($hours, 2) }} hrs</span>
                                                                                            @endif
                                                                                        </div>
                                                                                        @if($start || $end)
                                                                                            <span class="text-muted small d-block mt-1">
                                                                                                {{ $rangeStart }}
                                                                                                @if($rangeEnd)
                                                                                                    &ndash; {{ $rangeEnd }}
                                                                                                @endif
                                                                                            </span>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                                <span class="badge {{ $badgeClass }}">{{ $categoryLabel }}</span>
                                                                            </div>
                                                                            <div class="mt-3">
                                                                                <span class="text-muted small text-uppercase fw-semibold">Students</span>
                                                                                @if($students->isNotEmpty())
                                                                                    <ul class="list-unstyled mb-0 small mt-1">
                                                                                        @foreach($students as $student)
                                                                                            <li>{{ $student }}</li>
                                                                                        @endforeach
                                                                                    </ul>
                                                                                @else
                                                                                    <p class="text-muted small mb-0">No students assigned.</p>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="text-muted mb-0">No schedules assigned.</p>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                                {{ $archivedData->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('trainer-filter-form');
            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }

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
                    if (filters.show_archived) chips.push('Archived view');
                    if (filters.status && filters.status !== 'all') {
                        const statusMap = {
                            assigned: 'Assigned to classes',
                            unassigned: 'No upcoming classes',
                        };
                        chips.push(`Status: ${statusMap[filters.status] || filters.status}`);
                    }
                    if (filters.search) {
                        chips.push(
                            `Search: ${filters.search}${filters.search_column ? ` (${filters.search_column})` : ''}`
                        );
                    }
                    if (filters.start || filters.end) {
                        chips.push(`Date: ${filters.start || '—'} → ${filters.end || '—'}`);
                    }
                    return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
                }

                function buildPrintRows(items) {
                    return items.map((item) => `
                        <tr>
                            <td>${item.id ?? '—'}</td>
                            <td>
                                <div class="fw">${item.name || '—'}</div>
                                <div class="muted">${item.email || ''}</div>
                            </td>
                            <td>${item.phone || '—'}</td>
                            <td>${item.salary ? '₱' + item.salary : '—'}</td>
                            <td><span class="badge ${getStatusBadgeClass(item.status)}">${item.status || '—'}</span></td>
                            <td>
                                <div>${item.created_at || ''}</div>
                                <div class="muted">${item.updated_at || ''}</div>
                                <div class="muted">${item.created_by || ''}</div>
                            </td>
                        </tr>
                    `).join('');
                }

                function renderPrintWindow(payload) {
                    const items = payload.items || [];
                    const filters = payload.filters || {};
                    const rows = buildPrintRows(items);
                    const html = `
                        <!doctype html>
                        <html>
                            <head>
                                <title>${payload.title || 'Trainer directory'}</title>
                                <style>
                                    :root { color-scheme: light; }
                                    body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
                                    .sheet { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; }
                                    .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
                                    .title { margin: 0; font-size: 22px; }
                                    .muted { color: #6b7280; font-size: 12px; }
                                    .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
                                    .pill { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
                                    table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
                                    th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
                                    th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
                                    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
                                    .badge-soft-warning { background: #fef9c3; color: #854d0e; }
                                    .badge-soft-success { background: #dcfce7; color: #166534; }
                                    .badge-soft-secondary { background: #e5e7eb; color: #374151; }
                                    .badge-soft-muted { background: #f3f4f6; color: #6b7280; }
                                    .fw { font-weight: 700; }
                                </style>
                            </head>
                            <body>
                                <div class="sheet">
                                    <div class="header">
                                        <div>
                                            <h1 class="title">${payload.title || 'Trainer directory'}</h1>
                                            <div class="muted">Generated ${payload.generated_at || ''}</div>
                                            <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                        </div>
                                    </div>
                                    <div class="pill-row">${buildPrintFilters(filters)}</div>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Trainer</th>
                                                <th>Contact</th>
                                                <th>Est. Salary</th>
                                                <th>Status</th>
                                                <th>Audit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${rows || '<tr><td colspan="6" style="text-align:center; padding:16px;">No trainers for this view.</td></tr>'}
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
                }

                if (printButton && printForm) {
                    printButton.addEventListener('click', function (e) {
                        const rawPayload = printButton.dataset.print;
                        if (!rawPayload) {
                            return;
                        }

                        e.preventDefault();
                        if (printLoader) printLoader.classList.remove('d-none');
                        printButton.disabled = true;

                        let payload = null;
                        try {
                            payload = JSON.parse(rawPayload);
                        } catch (err) {
                            payload = null;
                        }

                        const opened = payload ? renderPrintWindow(payload) : false;
                        if (!opened) {
                            printButton.disabled = false;
                            if (printLoader) printLoader.classList.add('d-none');
                            printForm.submit();
                            return;
                        }

                        setTimeout(() => {
                            printButton.disabled = false;
                            if (printLoader) printLoader.classList.add('d-none');
                        }, 300);
                    });
                }
            }

            const assignmentModals = document.querySelectorAll('[data-assignment-modal]');

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

            const formatHours = function (value) {
                return Number(value || 0).toFixed(2);
            };

            const buildAssignmentFilterChips = function (filters) {
                const chips = [];
                if (filters.category && filters.category !== 'all') {
                    chips.push(filters.category === 'future' ? 'Upcoming only' : 'Past only');
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
                        const category = (item.category || '').toLowerCase() === 'past' ? 'past' : 'future';
                        const badgeClass = category === 'past' ? 'badge-soft-secondary' : 'badge-soft-success';
                        const categoryLabel = item.category_label || (category === 'past' ? 'Past' : 'Upcoming');
                        const endLabel = item.end_label && item.end_label !== '—' ? item.end_label : '';

                        return `
                            <tr>
                                <td>${idx + 1}</td>
                                <td>
                                    <div class="fw">${escapeHtml(item.name || '—')}</div>
                                    <div class="muted">${escapeHtml(item.class_code || '')}</div>
                                </td>
                                <td>
                                    <div>${escapeHtml(item.start_label || 'Not set')}</div>
                                    <div class="muted">${escapeHtml(endLabel ? `Ends ${endLabel}` : '')}</div>
                                </td>
                                <td>${item.hours !== null && item.hours !== undefined ? formatHours(item.hours) + ' hrs' : '—'}</td>
                                <td><span class="badge ${badgeClass}">${escapeHtml(categoryLabel)}</span></td>
                                <td>${studentsMarkup}</td>
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
                                .pill { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
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
                                .fw { font-weight: 700; }
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
                                            <div class="muted">Hours: ${formatHours(summary.future_hours)}</div>
                                        </div>
                                        <div class="stat-card">
                                            <div class="stat-value">${summary.past}</div>
                                            <div class="stat-label">Past</div>
                                            <div class="muted">Hours: ${formatHours(summary.past_hours)}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildAssignmentFilterChips(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Class</th>
                                            <th>Schedule</th>
                                            <th>Hours</th>
                                            <th>Status</th>
                                            <th>Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="6" style="text-align:center; padding:16px;">No assignments for this view.</td></tr>'}
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
                const summaryEls = {
                    totalCount: modalEl.querySelectorAll('[data-role="total-count"]'),
                    futureCount: modalEl.querySelectorAll('[data-role="future-count"]'),
                    pastCount: modalEl.querySelectorAll('[data-role="past-count"]'),
                    futureHours: modalEl.querySelectorAll('[data-role="future-hours"]'),
                    pastHours: modalEl.querySelectorAll('[data-role="past-hours"]'),
                };

                if (!cards.length) {
                    return;
                }

                let activeCategory = 'all';

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
                    summaryEls.totalCount.forEach(function (el) {
                        el.textContent = pluralize(totalCount, 'assignment', 'assignments');
                    });
                    summaryEls.futureCount.forEach(function (el) {
                        el.textContent = pluralize(futureCount, 'assignment', 'assignments');
                    });
                    summaryEls.pastCount.forEach(function (el) {
                        el.textContent = pluralize(pastCount, 'assignment', 'assignments');
                    });
                    summaryEls.futureHours.forEach(function (el) {
                        el.textContent = `${futureHours.toFixed(2)} hrs`;
                    });
                    summaryEls.pastHours.forEach(function (el) {
                        el.textContent = `${pastHours.toFixed(2)} hrs`;
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

                function applyFilters() {
                    const visibleCards = [];

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
                            card.classList.remove('d-none');
                            visibleCards.push(card);
                        } else {
                            card.classList.add('d-none');
                        }
                    });

                    updateSummary(visibleCards);
                }

                categoryButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const selected = this.dataset.categoryFilter || 'all';
                        if (selected === activeCategory) {
                            return;
                        }
                        activeCategory = selected;

                        setActiveCategoryButton(activeCategory);
                        applyFilters();
                    });
                });

                if (startInput) {
                    startInput.addEventListener('change', applyFilters);
                }

                if (endInput) {
                    endInput.addEventListener('change', applyFilters);
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
                        applyFilters();
                    });
                }

                modalEl.addEventListener('shown.bs.modal', applyFilters);
                setActiveCategoryButton(activeCategory);
                applyFilters();
            });
        });
    </script>
@endsection
