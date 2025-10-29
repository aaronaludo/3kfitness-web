@extends('layouts.admin')
@section('title', 'Classes')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                  <h2 class="title mb-0">Classes</h2>
                </div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.gym-management.schedules.create') }}">
                        <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Add
                    </a>
                    <form action="{{ route('admin.gym-management.schedules.print') }}" method="POST" id="print-form">
                        @csrf
                        <div>
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
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
                          <button class="btn btn-md btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                          </button>
                        </div>
                    </form>                      
                </div>
            </div>                            
            @php
                $statusFilter = request('status', 'all');
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
                    'active' => [
                        'label' => 'Active',
                        'count' => $statusTallies['active'] ?? null,
                    ],
                    'completed' => [
                        'label' => 'Completed',
                        'count' => $statusTallies['completed'] ?? null,
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
                                <h4 class="fw-semibold mb-1">Class schedules</h4>
                                <p class="text-muted mb-0">Stay on top of upcoming, active, and completed classes with quick filters.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $data->total() }} results</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.gym-management.schedules') }}" method="GET" id="schedule-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="schedule-status-filter" value="{{ $statusFilter }}">

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

                                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>

                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#schedule-advanced-filters"
                                        aria-expanded="{{ $advancedFiltersOpen ? 'true' : 'false' }}"
                                        aria-controls="schedule-advanced-filters"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="collapse border-top mt-4 pt-4{{ $advancedFiltersOpen ? ' show' : '' }}" id="schedule-advanced-filters">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <span class="text-muted text-uppercase small fw-semibold">Quick ranges</span>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-week">Last week</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-month">Last month</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-year">Last year</button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="search-column" class="form-label text-muted text-uppercase small">Search by</label>
                                        <select id="search-column" name="search_column" class="form-select rounded-3">
                                            <option value="" {{ request('search_column') ? '' : 'selected' }}>Best match</option>
                                            <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>ID</option>
                                            <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Class Name</option>
                                            <option value="class_code" {{ request('search_column') == 'class_code' ? 'selected' : '' }}>Class Code</option>
                                            <option value="trainer_name" {{ request('search_column') == 'trainer_name' ? 'selected' : '' }}>Trainer</option>
                                            <option value="slots" {{ request('search_column') == 'slots' ? 'selected' : '' }}>Slots</option>
                        	                <option value="class_start_date" {{ request('search_column') == 'class_start_date' ? 'selected' : '' }}>Class Start Date</option>
                                            <option value="class_end_date" {{ request('search_column') == 'class_end_date' ? 'selected' : '' }}>Class End Date</option>
                                            <option value="rejection_reason" {{ request('search_column') == 'rejection_reason' ? 'selected' : '' }}>Reject Reason</option>
                                            <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="start-date" class="form-label text-muted text-uppercase small">Start date</label>
                                        <input
                                            type="date"
                                            id="start-date"
                                            class="form-control rounded-3"
                                            name="start_date"
                                            value="{{ request('start_date') }}"
                                        />
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="end-date" class="form-label text-muted text-uppercase small">End date</label>
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
                        </form>
                    </div>
                </div>
            </div>
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
                            statusInput.value = selectedStatus;

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
                });
            </script>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Classes Created by Admin</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-hashtag"></i>
                                <h2 class="float-end">{{ $classescreatedbyadmin }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Classes Created by Staff</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-hashtag"></i>
                                <h2 class="float-end">{{ $classescreatedbystaff }}</h2>
                            </div>
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
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_name">Class Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_code">Class Code <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="trainer">Trainer <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="slots">Slots <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="total_members_enrolled">Total Members Enrolled <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="start_date">Class Start Date and Time <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="end_date">Class End Date and Time <i class="fa fa-sort"></i></th>
                                            {{-- <th class="sortable" data-column="status">Status <i class="fa fa-sort"></i></th> --}}
                                            <th class="sortable" data-column="categorization">Categorization <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="admin_acceptance">Admin Acceptance <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="reject_reason">Reject Reason <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->class_code }}</td>
                                                <td>
                                                    {{ $item->trainer_id == 0 ? 'No Trainer for now' : optional($item->user)->first_name .' '. optional($item->user)->last_name }}
                                                </td>
                                                <td>{{ $item->slots }}</td>
                                                <td>
                                                    <a href="#" 
                                                        class="show-modal text-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#userSchedulesModal"
                                                        data-id="{{ $item->id }}"
                                                        data-user-schedules="{{ $item->user_schedules }}">
                                                        {{ $item->user_schedules_count }}
                                                    </a>
                                                </td>
                                                <td>{{ $item->class_start_date }}</td>
                                                <td>{{ $item->class_end_date }}</td>
                                                {{-- <td>{{ $item->isenabled ? 'Enabled' : 'Disabled' }}</td> --}}
                                                <td>
                                                    @php
                                                        $now = now();
                                                        $start_date = \Carbon\Carbon::parse($item->class_start_date);
                                                        $end_date = \Carbon\Carbon::parse($item->class_end_date);
                                                    @endphp
                                                
                                                    @if ($now->lt($start_date))
                                                        <span class="badge rounded-pill bg-warning">Future</span>
                                                    @elseif ($now->between($start_date, $end_date))
                                                        <span class="badge rounded-pill bg-success">Present</span>
                                                    @else
                                                        <span class="badge rounded-pill bg-primary">Past</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusMap = [
                                                            0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                            1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                            2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                                        ];
                                                        $s = $statusMap[$item->isadminapproved] ?? $statusMap[0];
                                                    @endphp

                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge {{ $s['class'] }} px-3 py-2">
                                                            {{ $s['label'] }}
                                                        </span>

                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#statusModal-{{ $item->id }}"
                                                            aria-label="Change Status"
                                                        >
                                                            Change
                                                        </button>
                                                    </div>
                                                    <div class="modal fade" id="statusModal-{{ $item->id }}" tabindex="-1" aria-labelledby="statusModalLabel-{{ $item->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <form method="POST" action="{{ route('admin.gym-management.schedules.adminacceptance') }}" class="modal-content"
                                                                id="statusForm-{{ $item->id }}"
                                                                data-has-users="{{ $item->user_schedules_count > 0 ? 'true' : 'false' }}">
                                                                @csrf
                                                                @method('PUT')

                                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="statusModalLabel-{{ $item->id }}">Change Admin Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>

                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="statusSelect-{{ $item->id }}" class="form-label fw-semibold">Select status</label>
                                                                        <select class="form-select" id="statusSelect-{{ $item->id }}" name="isadminapproved">
                                                                            <option value="0" {{ $item->isadminapproved == 0 ? 'selected' : '' }}>Pending</option>
                                                                            <option value="1" {{ $item->isadminapproved == 1 ? 'selected' : '' }}>Approve</option>
                                                                            <option value="2" {{ $item->isadminapproved == 2 ? 'selected' : '' }}>Reject</option>
                                                                        </select>
                                                                    </div>

                                                                    <div id="rejectGuard-{{ $item->id }}" class="border rounded p-3 bg-light d-none">
                                                                        <div class="d-flex align-items-start gap-2">
                                                                            <i class="fa-solid fa-triangle-exclamation text-danger mt-1"></i>
                                                                            <div>
                                                                                <div class="fw-semibold text-danger mb-1">Heads up before rejecting</div>
                                                                                <div class="small text-muted">
                                                                                    This item currently has linked user schedules ({{ $item->user_schedules_count }}). Rejecting may impact users.
                                                                                    Please confirm you understand before proceeding.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-check mt-3">
                                                                            <input class="form-check-input" type="checkbox" value="1" id="rejectConfirm-{{ $item->id }}">
                                                                            <label class="form-check-label" for="rejectConfirm-{{ $item->id }}">
                                                                                I understand the impact of rejecting.
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        Cancel
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary" id="saveStatusBtn-{{ $item->id }}">
                                                                        Save
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <script>
                                                    (function() {
                                                        const form     = document.getElementById('statusForm-{{ $item->id }}');
                                                        const select   = document.getElementById('statusSelect-{{ $item->id }}');
                                                        const guardBox = document.getElementById('rejectGuard-{{ $item->id }}');
                                                        const confirmC = document.getElementById('rejectConfirm-{{ $item->id }}');
                                                        const saveBtn  = document.getElementById('saveStatusBtn-{{ $item->id }}');
                                                        const hasUsers = form.dataset.hasUsers === 'true';

                                                        function updateGuard() {
                                                            const rejectChosen = select.value === '2';
                                                            if (hasUsers && rejectChosen) {
                                                                guardBox.classList.remove('d-none');
                                                                saveBtn.disabled = !confirmC.checked;
                                                            } else {
                                                                guardBox.classList.add('d-none');
                                                                saveBtn.disabled = false;
                                                                if (confirmC) confirmC.checked = false;
                                                            }
                                                        }

                                                        const modalEl = document.getElementById('statusModal-{{ $item->id }}');
                                                        modalEl.addEventListener('shown.bs.modal', updateGuard);

                                                        select.addEventListener('change', updateGuard);
                                                        if (confirmC) {
                                                            confirmC.addEventListener('change', () => {
                                                                if (select.value === '2' && hasUsers) {
                                                                    saveBtn.disabled = !confirmC.checked;
                                                                }
                                                            });
                                                        }
                                                    })();
                                                    </script>
                                                </td>
                                                <td>
                                                    {{ $item->rejection_reason }}
                                                </td>
                                                <td>
                                                    {{ $item->created_at }}
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <!--<div class="action-button"><a href="{{ route('admin.gym-management.schedules.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>-->
                                                        <div class="action-button"><a href="{{ route('admin.gym-management.schedules.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                        <div class="action-button">
                                                            <!--<form action="{{ route('admin.gym-management.schedules.delete') }}" method="POST" style="display: inline;">-->
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
                                            <div class="modal fade" id="rejectModal-{{ $item->id }}" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Reject Reason ({{ $item->class_code }})</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.gym-management.schedules.rejectmessage') }}" method="POST" id="reject-modal-form-{{ $item->id }}">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body">
                                                                <textarea class="form-control" name="rejection_reason" id="rejectReason" rows="4" placeholder="Enter reason for rejection"></textarea>
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                                <button class="btn btn-danger" type="submit" id="reject-modal-submit-button-{{ $item->id }}">
                                                                    <span id="reject-modal-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Are you sure you want to delete this class ({{ $item->class_code }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.gym-management.schedules.delete') }}" method="POST" id="delete-modal-form-{{ $item->id }}">
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
                                                                <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                                <button class="btn btn-danger" type="submit" id="delete-modal-submit-button-{{ $item->id }}">
                                                                    <span id="delete-modal-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="userSchedulesModal" tabindex="-1" aria-labelledby="userSchedulesModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="userSchedulesModalLabel">User Schedules</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <ul id="modalUserSchedules" class="list-group">
                                                                <li class='list-group-item'>No users found</li>
                                                            </ul>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                const modal = document.getElementById("userSchedulesModal");
                                                const modalList = document.getElementById("modalUserSchedules");
                                                const modalTitle = document.getElementById("userSchedulesModalLabel");

                                                modal.addEventListener("show.bs.modal", function (event) {
                                                    let triggerElement = event.relatedTarget;
                                                    let dataUserSchedules = triggerElement.getAttribute("data-user-schedules");

                                                    const dataUserSchedulesList = JSON.parse(dataUserSchedules);
                                                    console.log(dataUserSchedulesList);

                                                    modalTitle.innerHTML = `User Schedules - 1`;

                                                    modalList.innerHTML = "";
                                                    dataUserSchedulesList.forEach(item => {
                                                        modalList.innerHTML += `<li class='list-group-item'>${item.user_id} (${item.schedule_id})</li>`;
                                                    });
                                                });
                                            });
                                            </script>
                                            <script>
                                                document.getElementById('reject-modal-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('reject-modal-submit-button-{{ $item->id }}');
                                                    const loader = document.getElementById('reject-modal-loader-{{ $item->id }}');
                                        
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                            <script>
                                                document.getElementById('delete-modal-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('delete-modal-submit-button-{{ $item->id }}');
                                                    const loader = document.getElementById('delete-modal-loader-{{ $item->id }}');
                                        
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
