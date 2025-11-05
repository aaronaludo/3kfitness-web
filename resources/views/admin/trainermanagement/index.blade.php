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
                </div>
            </div>

            @php
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
                                <span class="d-block text-muted small">Showing {{ $trainers->total() }} results</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.trainer-management.index') }}" method="GET" id="trainer-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="trainer-status-filter" value="{{ $trainerStatus }}">

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

                                    <a href="{{ route('admin.trainer-management.index') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>

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
                                                <td>{{ $item->id }}</td>
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
                                            <div class="modal fade" id="assignmentsModal-{{ $item->id }}" tabindex="-1" aria-labelledby="assignmentsModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="assignmentsModalLabel-{{ $item->id }}">Assignments for {{ $item->first_name }} {{ $item->last_name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($trainerSchedules->isNotEmpty())
                                                                @if($salaryEligibleSchedules->isNotEmpty())
                                                                    <div class="alert alert-light border rounded-3 d-flex justify-content-between align-items-center mb-4">
                                                                        <div>
                                                                            <span class="fw-semibold text-muted text-uppercase small d-block">Total estimated salary</span>
                                                                            <span class="text-muted small">Class duration × rate</span>
                                                                        </div>
                                                                        <span class="fw-semibold">₱{{ number_format($totalSalary, 2) }}</span>
                                                                    </div>
                                                                @endif
                                                                @foreach($trainerSchedules as $schedule)
                                                                    <div class="mb-4">
                                                                        <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                                                        @if(!empty($schedule->class_code))
                                                                            <span class="text-muted small d-block">Code: {{ $schedule->class_code }}</span>
                                                                        @endif
                                                                        @if(!empty($schedule->class_start_date) || !empty($schedule->class_end_date))
                                                                            <span class="text-muted small d-block">
                                                                                {{ $schedule->class_start_date ?? 'N/A' }}
                                                                                @if(!empty($schedule->class_end_date))
                                                                                    &ndash; {{ $schedule->class_end_date }}
                                                                                @endif
                                                                            </span>
                                                                        @endif
                                                                        @php
                                                                            $scheduleStart = !empty($schedule->class_start_date) ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                                            $scheduleEnd = !empty($schedule->class_end_date) ? \Carbon\Carbon::parse($schedule->class_end_date) : null;
                                                                            $scheduleHours = ($scheduleStart && $scheduleEnd && $scheduleEnd->greaterThan($scheduleStart))
                                                                                ? $scheduleEnd->diffInMinutes($scheduleStart) / 60
                                                                                : 0;
                                                                            $scheduleSalary = !is_null($schedule->trainer_rate_per_hour)
                                                                                ? (float) $schedule->trainer_rate_per_hour * $scheduleHours
                                                                                : 0;
                                                                            $scheduleStudents = collect($schedule->user_schedules ?? [])->map(function ($userSchedule) {
                                                                                $user = $userSchedule->user ?? null;
                                                                                if (!$user) {
                                                                                    return null;
                                                                                }
                                                                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                                                return $fullName !== '' ? $fullName : ($user->email ?? null);
                                                                            })->filter()->unique()->values();
                                                                        @endphp
                                                                        @if(!is_null($schedule->trainer_rate_per_hour))
                                                                            <div class="mt-2">
                                                                                <span class="text-muted small d-block">Rate: ₱{{ number_format((float) $schedule->trainer_rate_per_hour, 2) }} per hour</span>
                                                                                <span class="text-muted small d-block">Estimated salary: ₱{{ number_format($scheduleSalary, 2) }}</span>
                                                                            </div>
                                                                        @endif
                                                                        <div class="mt-2">
                                                                            <span class="text-muted small text-uppercase fw-semibold">Students</span>
                                                                            @if($scheduleStudents->isNotEmpty())
                                                                                <ul class="list-unstyled mb-0 small mt-1">
                                                                                    @foreach($scheduleStudents as $student)
                                                                                        <li>{{ $student }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            @else
                                                                                <p class="text-muted small mb-0">No students assigned.</p>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endforeach
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
                                            <div class="modal fade" id="archiveAssignmentsModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveAssignmentsModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveAssignmentsModalLabel-{{ $archive->id }}">Assignments for {{ $archive->first_name }} {{ $archive->last_name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($archivedSchedules->isNotEmpty())
                                                                @if($archivedSalaryEligible->isNotEmpty())
                                                                    <div class="alert alert-light border rounded-3 d-flex justify-content-between align-items-center mb-4">
                                                                        <div>
                                                                            <span class="fw-semibold text-muted text-uppercase small d-block">Total estimated salary</span>
                                                                            <span class="text-muted small">Class duration × rate</span>
                                                                        </div>
                                                                        <span class="fw-semibold">₱{{ number_format($archivedTotalSalary, 2) }}</span>
                                                                    </div>
                                                                @endif
                                                                @foreach($archivedSchedules as $schedule)
                                                                    <div class="mb-4">
                                                                        <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                                                        @if(!empty($schedule->class_code))
                                                                            <span class="text-muted small d-block">Code: {{ $schedule->class_code }}</span>
                                                                        @endif
                                                                        @if(!empty($schedule->class_start_date) || !empty($schedule->class_end_date))
                                                                            <span class="text-muted small d-block">
                                                                                {{ $schedule->class_start_date ?? 'N/A' }}
                                                                                @if(!empty($schedule->class_end_date))
                                                                                    &ndash; {{ $schedule->class_end_date }}
                                                                                @endif
                                                                            </span>
                                                                        @endif
                                                                        @php
                                                                            $scheduleStart = !empty($schedule->class_start_date) ? \Carbon\Carbon::parse($schedule->class_start_date) : null;
                                                                            $scheduleEnd = !empty($schedule->class_end_date) ? \Carbon\Carbon::parse($schedule->class_end_date) : null;
                                                                            $scheduleHours = ($scheduleStart && $scheduleEnd && $scheduleEnd->greaterThan($scheduleStart))
                                                                                ? $scheduleEnd->diffInMinutes($scheduleStart) / 60
                                                                                : 0;
                                                                            $scheduleSalary = !is_null($schedule->trainer_rate_per_hour)
                                                                                ? (float) $schedule->trainer_rate_per_hour * $scheduleHours
                                                                                : 0;
                                                                            $scheduleStudents = collect($schedule->user_schedules ?? [])->map(function ($userSchedule) {
                                                                                $user = $userSchedule->user ?? null;
                                                                                if (!$user) {
                                                                                    return null;
                                                                                }
                                                                                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                                                                return $fullName !== '' ? $fullName : ($user->email ?? 'Unknown');
                                                                            })->filter();
                                                                        @endphp
                                                                        @if(!is_null($schedule->trainer_rate_per_hour))
                                                                            <div class="mt-2">
                                                                                <span class="text-muted small d-block">Rate: ₱{{ number_format((float) $schedule->trainer_rate_per_hour, 2) }} per hour</span>
                                                                                <span class="text-muted small d-block">Estimated salary: ₱{{ number_format($scheduleSalary, 2) }}</span>
                                                                            </div>
                                                                        @endif
                                                                        @if($scheduleStudents->isNotEmpty())
                                                                            <div class="mt-2">
                                                                                <span class="text-muted small d-block">Enrolled members:</span>
                                                                                <ul class="mb-0 small ms-3">
                                                                                    @foreach($scheduleStudents as $student)
                                                                                        <li>{{ $student }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </div>
                                                                        @else
                                                                            <span class="text-muted small">No members enrolled.</span>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
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
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('trainer-filter-form');
            if (!form) {
                return;
            }

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
        });
    </script>
@endsection
