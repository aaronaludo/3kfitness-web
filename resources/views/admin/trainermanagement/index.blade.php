@extends('layouts.admin')
@section('title', 'Trainer Management - Index')

@section('content')
    <div class="container-fluid">
        <div class="row">
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
                        <button class="btn btn-danger" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
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
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $trainerStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary' }}"
                                            data-status="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
                                            @if(!is_null($option['count']))
                                                <span class="badge bg-transparent text-muted fw-semibold ms-2">{{ $option['count'] }}</span>
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
                                                        <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>ID</option>
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
                @if (!$showArchived)
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            {{-- <th class="sortable" data-column="membership_name">Membership Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="expiration_date">Membership Expiration Date <i class="fa fa-sort"></i></th> --}}
                                            <th class="sortable" data-column="name">Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="phone_number">Phone Number <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="email">Email <i class="fa fa-sort"></i></th>
                                            <th>Assignments</th>
                                            <th>Estimated Salary</th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_by">Created By <i class="fa fa-sort"></i></th>
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

                                                    $futureSalaryTotal = $futureScheduleDetails->sum('summary_salary');
                                                    $pastSalaryTotal = $pastScheduleDetails->sum('summary_salary');

                                                    $futureSalaryAssignments = $futureScheduleDetails->filter(function ($detail) {
                                                        return $detail['salary_eligible'];
                                                    })->count();

                                                    $pastSalaryAssignments = $pastScheduleDetails->filter(function ($detail) {
                                                        return $detail['salary_eligible'];
                                                    })->count();
                                                @endphp
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#assignmentsModal-{{ $item->id }}"
                                                    >
                                                        View Assignments
                                                    </button>
                                                </td>
                                                <td>
                                                    @if($salaryEligibleSchedules->isNotEmpty())
                                                        ₱{{ number_format($totalSalary, 2) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $createdAt ? $createdAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $updatedAt ? $updatedAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $item->created_by }}</td>
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
                                            <div class="modal fade assignment-modal" data-assignment-modal id="assignmentsModal-{{ $item->id }}" tabindex="-1" aria-labelledby="assignmentsModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header align-items-center">
                                                            <h5 class="modal-title mb-0" id="assignmentsModalLabel-{{ $item->id }}">Assignments for {{ $item->first_name }} {{ $item->last_name }}</h5>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-print-modal="assignmentsModal-{{ $item->id }}">
                                                                    <i class="fa-solid fa-print me-1"></i>Print
                                                                </button>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($scheduleDetails->isNotEmpty())
                                                                <div class="row g-3 mb-4">
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <span class="text-muted small text-uppercase fw-semibold d-block">Upcoming assignments</span>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fs-5 fw-semibold" data-role="future-total">₱{{ number_format($futureSalaryTotal, 2) }}</span>
                                                                                <span class="text-muted small" data-role="future-count">
                                                                                    {{ $futureScheduleCount }} {{ $futureScheduleCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <span class="text-muted small d-block mt-1" data-role="future-payroll-count">
                                                                                {{ $futureSalaryAssignments }} payroll {{ $futureSalaryAssignments === 1 ? 'class' : 'classes' }}
                                                                            </span>
                                                                            <span class="text-muted small d-block">Class duration × rate for eligible entries</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <span class="text-muted small text-uppercase fw-semibold d-block">Past assignments</span>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fs-5 fw-semibold" data-role="past-total">₱{{ number_format($pastSalaryTotal, 2) }}</span>
                                                                                <span class="text-muted small" data-role="past-count">
                                                                                    {{ $pastScheduleCount }} {{ $pastScheduleCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <span class="text-muted small d-block mt-1" data-role="past-payroll-count">
                                                                                {{ $pastSalaryAssignments }} payroll {{ $pastSalaryAssignments === 1 ? 'class' : 'classes' }}
                                                                            </span>
                                                                            <span class="text-muted small d-block">Totals exclude archived or incomplete data</span>
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
                                                                            data-salary="{{ $displaySalary }}"
                                                                            data-summary-salary="{{ $summarySalary }}"
                                                                            data-salary-eligible="{{ $isSalaryEligible ? 1 : 0 }}"
                                                                        >
                                                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                                                <div>
                                                                                    <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                                                                    @if(!empty($schedule->class_code))
                                                                                        <span class="text-muted small d-block">Code: {{ $schedule->class_code }}</span>
                                                                                    @endif
                                                                                    @if($start || $end)
                                                                                        <span class="text-muted small d-block">
                                                                                            {{ $rangeStart }}
                                                                                            @if($rangeEnd)
                                                                                                &ndash; {{ $rangeEnd }}
                                                                                            @endif
                                                                                        </span>
                                                                                    @endif
                                                                                </div>
                                                                                <span class="badge {{ $badgeClass }}">{{ $categoryLabel }}</span>
                                                                            </div>
                                                                            @if(!is_null($schedule->trainer_rate_per_hour))
                                                                                <div class="mt-3">
                                                                                    <span class="text-muted small d-block">Rate: ₱{{ number_format((float) $schedule->trainer_rate_per_hour, 2) }} per hour</span>
                                                                                    @if($displaySalary > 0)
                                                                                        <span class="text-muted small d-block">Estimated salary: ₱{{ number_format($displaySalary, 2) }}</span>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                            @if($hours > 0)
                                                                                <span class="text-muted small d-block mt-2">Duration: {{ number_format($hours, 2) }} {{ $hours === 1.0 ? 'hour' : 'hours' }}</span>
                                                                            @endif
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
                                                <td colspan="9" class="text-center text-muted">No trainers found.</td>
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
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Phone Number</th>
                                            <th>Email</th>
                                            <th>Assignments</th>
                                            <th>Estimated Salary</th>
                                            <th>Created Date</th>
                                            <th>Updated Date</th>
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

                                                    $archivedFutureSalaryTotal = $archivedFutureDetails->sum('summary_salary');
                                                    $archivedPastSalaryTotal = $archivedPastDetails->sum('summary_salary');

                                                    $archivedFuturePayrollCount = $archivedFutureDetails->filter(function ($detail) {
                                                        return $detail['salary_eligible'];
                                                    })->count();

                                                    $archivedPastPayrollCount = $archivedPastDetails->filter(function ($detail) {
                                                        return $detail['salary_eligible'];
                                                    })->count();
                                                @endphp
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#archiveAssignmentsModal-{{ $archive->id }}"
                                                    >
                                                        View Assignments
                                                    </button>
                                                </td>
                                                <td>
                                                    @if($archivedSalaryEligible->isNotEmpty())
                                                        ₱{{ number_format($archivedTotalSalary, 2) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $archiveCreated ? $archiveCreated->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $archiveUpdated ? $archiveUpdated->format('F j, Y g:iA') : '' }}</td>
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
                                            <div class="modal fade assignment-modal" data-assignment-modal id="archiveAssignmentsModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveAssignmentsModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header align-items-center">
                                                            <h5 class="modal-title mb-0" id="archiveAssignmentsModalLabel-{{ $archive->id }}">Assignments for {{ $archive->first_name }} {{ $archive->last_name }}</h5>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-print-modal="archiveAssignmentsModal-{{ $archive->id }}">
                                                                    <i class="fa-solid fa-print me-1"></i>Print
                                                                </button>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($archivedScheduleDetails->isNotEmpty())
                                                                <div class="row g-3 mb-4">
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <span class="text-muted small text-uppercase fw-semibold d-block">Upcoming assignments</span>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fs-5 fw-semibold" data-role="future-total">₱{{ number_format($archivedFutureSalaryTotal, 2) }}</span>
                                                                                <span class="text-muted small" data-role="future-count">
                                                                                    {{ $archivedFutureCount }} {{ $archivedFutureCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <span class="text-muted small d-block mt-1" data-role="future-payroll-count">
                                                                                {{ $archivedFuturePayrollCount }} payroll {{ $archivedFuturePayrollCount === 1 ? 'class' : 'classes' }}
                                                                            </span>
                                                                            <span class="text-muted small d-block">Class duration × rate for eligible entries</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="border rounded-3 p-3 h-100 bg-light assignment-summary-card">
                                                                            <span class="text-muted small text-uppercase fw-semibold d-block">Past assignments</span>
                                                                            <div class="d-flex align-items-baseline justify-content-between mt-2">
                                                                                <span class="fs-5 fw-semibold" data-role="past-total">₱{{ number_format($archivedPastSalaryTotal, 2) }}</span>
                                                                                <span class="text-muted small" data-role="past-count">
                                                                                    {{ $archivedPastCount }} {{ $archivedPastCount === 1 ? 'assignment' : 'assignments' }}
                                                                                </span>
                                                                            </div>
                                                                            <span class="text-muted small d-block mt-1" data-role="past-payroll-count">
                                                                                {{ $archivedPastPayrollCount }} payroll {{ $archivedPastPayrollCount === 1 ? 'class' : 'classes' }}
                                                                            </span>
                                                                            <span class="text-muted small d-block">Totals exclude archived or incomplete data</span>
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
                                                                            data-salary="{{ $displaySalary }}"
                                                                            data-summary-salary="{{ $summarySalary }}"
                                                                            data-salary-eligible="{{ $isSalaryEligible ? 1 : 0 }}"
                                                                        >
                                                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                                                <div>
                                                                                    <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                                                                    @if(!empty($schedule->class_code))
                                                                                        <span class="text-muted small d-block">Code: {{ $schedule->class_code }}</span>
                                                                                    @endif
                                                                                    @if($start || $end)
                                                                                        <span class="text-muted small d-block">
                                                                                            {{ $rangeStart }}
                                                                                            @if($rangeEnd)
                                                                                                &ndash; {{ $rangeEnd }}
                                                                                            @endif
                                                                                        </span>
                                                                                    @endif
                                                                                </div>
                                                                                <span class="badge {{ $badgeClass }}">{{ $categoryLabel }}</span>
                                                                            </div>
                                                                            @if(!is_null($schedule->trainer_rate_per_hour))
                                                                                <div class="mt-3">
                                                                                    <span class="text-muted small d-block">Rate: ₱{{ number_format((float) $schedule->trainer_rate_per_hour, 2) }} per hour</span>
                                                                                    @if($displaySalary > 0)
                                                                                        <span class="text-muted small d-block">Estimated salary: ₱{{ number_format($displaySalary, 2) }}</span>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                            @if($hours > 0)
                                                                                <span class="text-muted small d-block mt-2">Duration: {{ number_format($hours, 2) }} {{ $hours === 1.0 ? 'hour' : 'hours' }}</span>
                                                                            @endif
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

                if (printForm && printButton && printLoader) {
                    printForm.addEventListener('submit', function () {
                        printButton.disabled = true;
                        printLoader.classList.remove('d-none');
                    });
                }
            }

            const assignmentModals = document.querySelectorAll('[data-assignment-modal]');

            const formatCurrency = function (amount) {
                const value = Number(amount) || 0;
                return '₱' + value.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            };

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

            const gatherHeadMarkup = function () {
                return Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
                    .map(function (el) {
                        return el.outerHTML;
                    })
                    .join('');
            };

            const printStyles = `
                @page { size: A4; margin: 16mm; }
                body.print-body {
                    font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
                    background: #f5f6fa;
                    color: #1f2937;
                }
                .print-shell {
                    max-width: 980px;
                    margin: 0 auto;
                    padding: 12px 12px 32px;
                }
                .print-heading {
                    font-size: 20px;
                    font-weight: 700;
                    margin-bottom: 4px;
                    color: #0f172a;
                }
                .print-subtitle {
                    color: #6b7280;
                    font-size: 12px;
                    margin-bottom: 16px;
                }
                .print-card {
                    background: #ffffff;
                    border-radius: 14px;
                    border: 1px solid #e5e7eb;
                    box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
                    padding: 28px;
                }
                .modal-header {
                    border: none;
                    padding: 0 0 12px;
                    align-items: flex-start;
                }
                .modal-title {
                    font-size: 18px;
                    font-weight: 700;
                    color: #111827;
                }
                .modal-body {
                    padding: 0;
                }
                .modal-footer,
                .modal-header .btn-close,
                .modal-header [data-print-modal] {
                    display: none !important;
                }
                .assignment-summary-card {
                    background: linear-gradient(135deg, #f9fafb, #eef2f7);
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
                }
                .assignment-summary-card .fs-5 {
                    font-size: 1.25rem;
                    color: #0f172a;
                }
                .assignment-summary-card .text-muted {
                    color: #6b7280 !important;
                }
                .assignment-filters {
                    background: #f9fafb;
                    border: 1px dashed #d1d5db;
                    border-radius: 12px;
                }
                .assignment-filters .btn-group .btn {
                    border-color: #d1d5db;
                    color: #111827;
                }
                .assignment-filters .btn-group .btn.btn-dark {
                    background: #1f2937;
                    color: #ffffff;
                    border-color: #1f2937;
                }
                .assignment-filters input[type="date"] {
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    padding: 8px 10px;
                    font-size: 0.9rem;
                }
                .assignment-card {
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    background: #ffffff;
                    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
                }
                .assignment-card h6 {
                    font-size: 15px;
                    font-weight: 700;
                    color: #0f172a;
                }
                .assignment-card .text-muted {
                    color: #6b7280 !important;
                }
                .badge.bg-success {
                    background: #16a34a !important;
                    color: #ffffff !important;
                }
                .badge.bg-secondary {
                    background: #94a3b8 !important;
                    color: #ffffff !important;
                }
                .assignment-list {
                    display: grid;
                    gap: 12px;
                }
                ul {
                    margin-bottom: 0;
                    padding-left: 18px;
                }
                .assignment-summary-card span,
                .assignment-card span,
                .assignment-card p,
                .assignment-card li {
                    font-size: 0.95rem;
                }
            `;

            const printModalContent = function (modalId) {
                if (!modalId) {
                    return;
                }

                const modalEl = document.getElementById(modalId);
                const content = modalEl ? modalEl.querySelector('.modal-content') : null;

                if (!content) {
                    return;
                }

                const clone = content.cloneNode(true);
                clone.querySelectorAll('.modal-header .btn-close, .modal-header [data-print-modal], .modal-footer').forEach(function (el) {
                    el.remove();
                });

                const titleText = modalEl.querySelector('.modal-title')?.textContent?.trim() || 'Assignments';

                const printWindow = window.open('', '', 'width=900,height=700');
                if (!printWindow) {
                    return;
                }

                printWindow.document.open();
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                        <head>
                            <title>${titleText}</title>
                            ${gatherHeadMarkup()}
                            <style>${printStyles}</style>
                        </head>
                        <body class="print-body">
                            <div class="print-shell">
                                <div class="print-heading">${titleText}</div>
                                <div class="print-subtitle">Generated ${new Date().toLocaleString()}</div>
                                <div class="print-card">
                                    ${clone.innerHTML}
                                </div>
                            </div>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();

                setTimeout(function () {
                    printWindow.close();
                }, 300);
            };

            document.querySelectorAll('[data-print-modal]').forEach(function (button) {
                button.addEventListener('click', function () {
                    printModalContent(this.dataset.printModal);
                });
            });

            assignmentModals.forEach(function (modalEl) {
                const categoryButtons = modalEl.querySelectorAll('[data-category-filter]');
                const startInput = modalEl.querySelector('[data-filter-start]');
                const endInput = modalEl.querySelector('[data-filter-end]');
                const resetButton = modalEl.querySelector('[data-filter-reset]');
                const cards = Array.from(modalEl.querySelectorAll('[data-assignment-card]'));
                const summaryEls = {
                    futureTotal: modalEl.querySelector('[data-role="future-total"]'),
                    futureCount: modalEl.querySelector('[data-role="future-count"]'),
                    futurePayroll: modalEl.querySelector('[data-role="future-payroll-count"]'),
                    pastTotal: modalEl.querySelector('[data-role="past-total"]'),
                    pastCount: modalEl.querySelector('[data-role="past-count"]'),
                    pastPayroll: modalEl.querySelector('[data-role="past-payroll-count"]'),
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
                    let futureTotal = 0;
                    let pastTotal = 0;
                    let futureCount = 0;
                    let pastCount = 0;
                    let futurePayroll = 0;
                    let pastPayroll = 0;

                    visibleCards.forEach(function (card) {
                        const category = card.dataset.category === 'past' ? 'past' : 'future';
                        const summarySalary = Number(card.dataset.summarySalary || 0);
                        const salaryEligible = card.dataset.salaryEligible === '1' && summarySalary > 0;

                        if (category === 'future') {
                            futureCount += 1;
                            futureTotal += summarySalary;
                            if (salaryEligible) {
                                futurePayroll += 1;
                            }
                        } else {
                            pastCount += 1;
                            pastTotal += summarySalary;
                            if (salaryEligible) {
                                pastPayroll += 1;
                            }
                        }
                    });

                    if (summaryEls.futureTotal) {
                        summaryEls.futureTotal.textContent = formatCurrency(futureTotal);
                    }
                    if (summaryEls.pastTotal) {
                        summaryEls.pastTotal.textContent = formatCurrency(pastTotal);
                    }
                    if (summaryEls.futureCount) {
                        summaryEls.futureCount.textContent = pluralize(futureCount, 'assignment', 'assignments');
                    }
                    if (summaryEls.pastCount) {
                        summaryEls.pastCount.textContent = pluralize(pastCount, 'assignment', 'assignments');
                    }
                    if (summaryEls.futurePayroll) {
                        summaryEls.futurePayroll.textContent = pluralize(futurePayroll, 'payroll class', 'payroll classes');
                    }
                    if (summaryEls.pastPayroll) {
                        summaryEls.pastPayroll.textContent = pluralize(pastPayroll, 'payroll class', 'payroll classes');
                    }
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
