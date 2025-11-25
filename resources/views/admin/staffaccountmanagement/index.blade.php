@extends('layouts.admin')
@section('title', 'Staff Account Management')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
            @endphp
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Staff Account Management</h2></div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.staff-account-management.add') }}"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add</a>
                    <form action="{{ route('admin.staff-account-management.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="payroll_status" value="{{ request('payroll_status', 'all') }}">
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.index', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.index', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>

            </div>
            @php
                $payrollStatus = request('payroll_status', 'all');
                $payrollTallies = $payrollTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All staff',
                        'count' => $payrollTallies['all'] ?? null,
                    ],
                    'with-payrolls' => [
                        'label' => 'With payrolls',
                        'count' => $payrollTallies['with-payrolls'] ?? null,
                    ],
                    'no-payrolls' => [
                        'label' => 'No payrolls',
                        'count' => $payrollTallies['no-payrolls'] ?? null,
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
                                <h4 class="fw-semibold mb-1">Staff directory</h4>
                                <p class="text-muted mb-0">Filter by payroll activity or narrow results to specific timeframes.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived staff
                                    @else
                                        Showing {{ $data->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.index') }}" method="GET" id="staff-filter-form" class="mt-4">
                            <input type="hidden" name="payroll_status" id="staff-status-filter" value="{{ $payrollStatus }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $payrollStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary' }}"
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
                                                placeholder="Search staff"
                                                value="{{ request('name') }}"
                                                aria-label="Search staff"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.staff-account-management.index', ['show_archived' => 1]) : route('admin.staff-account-management.index') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#staffFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="staffFiltersModal" tabindex="-1" aria-labelledby="staffFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="staffFiltersModalLabel">Advanced filters</h5>
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
                                                        <option value="email" {{ request('search_column') == 'email' ? 'selected' : '' }}>Email</option>
                                                        <option value="role_id" {{ request('search_column') == 'role_id' ? 'selected' : '' }}>Role</option>
                                                        <option value="phone_number" {{ request('search_column') == 'phone_number' ? 'selected' : '' }}>Contact Number</option>
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
                                            <th class="sortable" data-column="name">Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="email">Email <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="type">Type <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="phone_number">Phone Number <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="rate_per_hour">Rate per hour <i class="fa fa-sort"></i></th>
                                            <th>Net Pay (This Month)</th>
                                            <th class="sortable" data-column="payrolls">Payrolls <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_by">Created By <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach ($data as $item)
                                            @php
                                                $currentMonth = \Carbon\Carbon::now()->month;
                                                $payrollsThisMonth = $item->payrolls
                                                    ->filter(function ($payroll) use ($currentMonth) {
                                                        if (empty($payroll->clockin_at) || empty($payroll->clockout_at)) {
                                                            return false;
                                                        }

                                                        return \Carbon\Carbon::parse($payroll->clockin_at)->month === $currentMonth;
                                                    })
                                                    ->values();

                                                $totalHours = $payrollsThisMonth->sum(function ($payroll) {
                                                    $clockIn = \Carbon\Carbon::parse($payroll->clockin_at);
                                                    $clockOut = \Carbon\Carbon::parse($payroll->clockout_at);

                                                    if ($clockOut->lessThanOrEqualTo($clockIn)) {
                                                        return 0;
                                                    }

                                                    return $clockOut->diffInMinutes($clockIn) / 60;
                                                });

                                                $grossPay = (float) $item->rate_per_hour * $totalHours;
                                                $sssEmployee = round($grossPay * 0.045, 2);
                                                $philhealthEmployee = round($grossPay * 0.025, 2);
                                                $pagibigEmployee = round(min($grossPay, 5000) * 0.02, 2);
                                                $netPay = $grossPay - ($sssEmployee + $philhealthEmployee + $pagibigEmployee);
                                                $netPay = max($netPay, 0);
                                                $totalAmount = $grossPay;
                                            @endphp
                                            <tr>
                                                <td>{{ $item->user_code }}</td>
                                                <td>{{ $item->first_name }} {{ $item->last_name }}</td>
                                                <td>{{ $item->email }}</td>
                                                <td>{{ $item->role->name }}</td>
                                                <td>{{ $item->phone_number }}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>₱{{ number_format((float) $item->rate_per_hour, 2) }}</td>
                                                <td>
                                                    @if($totalHours > 0)
                                                        ₱{{ number_format($netPay, 2) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td><button class="btn btn-primary see-more" data-bs-toggle="modal" data-bs-target="#detailsModal-{{ $item->id }}">See More</button></td>
                                                <td>{{ $item->created_by }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.staff-account-management.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                        <div class="action-button"><a href="{{ route('admin.staff-account-management.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                        <div class="action-button">
                                                            <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <i class="fa-solid fa-trash text-danger"></i>
                                                            </button>
                                                        </div> 
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- Delete Modal --}}
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Are you sure you want to delete ({{ $item->email }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.delete') }}" method="POST" id="main-form-{{ $item->id }}">
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
                                                                <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                    <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Details / Payroll Modal --}}
                                            <div class="modal fade" id="detailsModal-{{ $item->id }}" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="modalTitle">Payrolls: (Gross Pay: ₱{{ number_format($totalAmount, 2) }})</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div id="modalContent">
                                                                @if($payrollsThisMonth->isNotEmpty())
                                                                    @foreach($payrollsThisMonth as $payroll)
                                                                    <div class="mb-3 border-bottom pb-2">
                                                                        <div class="d-flex gap-2">
                                                                            <strong>ID:</strong> <span>{{ $payroll->id }}</span>
                                                                        </div>
                                                                        <div class="d-flex gap-2">
                                                                            <strong>Clock In Date:</strong> <span>{{ $payroll->clockin_at }}</span>
                                                                        </div>
                                                                        <div class="d-flex gap-2">
                                                                            <strong>Clock Out Date:</strong> <span>{{ $payroll->clockout_at }}</span>
                                                                        </div>
                                                                        <div class="d-flex gap-2">
                                                                            <strong>Total Hours:</strong> 
                                                                            <span>
                                                                            {{ $payroll->clockout_at 
                                                                                ? number_format(\Carbon\Carbon::parse($payroll->clockin_at)->diffInMinutes(\Carbon\Carbon::parse($payroll->clockout_at)) / 60, 2)
                                                                                : 'Wait for clockout' }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="d-none gap-2">
                                                                            <strong>SSS:</strong> 
                                                                            <span>
                                                                                {{ $sssEmployee }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="d-none gap-2">
                                                                            <strong>PhilHealth:</strong> 
                                                                            <span>
                                                                                {{ $philhealthEmployee }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="d-none gap-2">
                                                                            <strong>Pag-IBIG:</strong> 
                                                                            <span>
                                                                                {{ $pagibigEmployee }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="d-flex gap-2">
                                                                            <strong>Net Pay:</strong> 
                                                                            <span>
                                                                            {{
                                                                            $payroll->clockout_at 
                                                                            ? "₱" . number_format((\Carbon\Carbon::parse($payroll->clockin_at)->diffInMinutes(\Carbon\Carbon::parse($payroll->clockout_at)) / 60) * (float) $item->rate_per_hour, 2)
                                                                            : 'Wait for clockout'
                                                                            }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                @else
                                                                    <p class="text-muted mb-0">No payroll records for the current month.</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                                                            {{-- UPDATED START: Replaced inline onclick with data-attributes --}}
                                                            @php
                                                                $filteredPayrolls = $payrollsThisMonth
                                                                    ->map(function ($p) {
                                                                        return [
                                                                            'id' => $p->id,
                                                                            'clockin' => $p->clockin_at,
                                                                            'clockout' => $p->clockout_at,
                                                                            'hours' => $p->clockout_at 
                                                                                ? \Carbon\Carbon::parse($p->clockin_at)->diffInMinutes(\Carbon\Carbon::parse($p->clockout_at)) / 60 
                                                                                : 'Pending',
                                                                        ];
                                                                    })
                                                                    ->values();
                                                            @endphp

                                                            <button
                                                                type="button"
                                                                class="btn btn-primary download-payslip"
                                                                data-name="{{ $item->first_name . ' ' . $item->last_name }}"
                                                                data-rate="{{ number_format((float) $item->rate_per_hour, 2, '.', '') }}"
                                                                data-total="{{ number_format((float) $netPay, 2, '.', '') }}"
                                                                data-sss="{{ number_format((float) $sssEmployee, 2, '.', '') }}"
                                                                data-philhealth="{{ number_format((float) $philhealthEmployee, 2, '.', '') }}"
                                                                data-pagibig="{{ number_format((float) $pagibigEmployee, 2, '.', '') }}"
                                                                data-payrolls='@json($filteredPayrolls)'
                                                            >
                                                                Download Payslip
                                                            </button>
                                                            {{-- UPDATED END --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Keep the per-item delete submit blocker --}}
                                            <script>
                                                document.getElementById('main-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                    const loader = document.getElementById('loader-{{ $item->id }}');
                                        
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
                @endif

                @if ($showArchived)
                <div class="box mt-5">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h4 class="fw-semibold mb-0">Archived Staff</h4>
                                <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Contact Number</th>
                                            <th>Created Date</th>
                                            <th>Rate per hour</th>
                                            <th>Net Pay (This Month)</th>
                                            <th>Payrolls</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($archivedData as $archive)
                                            @php
                                                $archiveCurrentMonth = \Carbon\Carbon::now()->month;
                                                $archivePayrollsThisMonth = $archive->payrolls
                                                    ->filter(function ($payroll) use ($archiveCurrentMonth) {
                                                        if (empty($payroll->clockin_at) || empty($payroll->clockout_at)) {
                                                            return false;
                                                        }

                                                        return \Carbon\Carbon::parse($payroll->clockin_at)->month === $archiveCurrentMonth;
                                                    })
                                                    ->values();

                                                $archiveTotalHours = $archivePayrollsThisMonth->sum(function ($payroll) {
                                                    $clockIn = \Carbon\Carbon::parse($payroll->clockin_at);
                                                    $clockOut = \Carbon\Carbon::parse($payroll->clockout_at);

                                                    if ($clockOut->lessThanOrEqualTo($clockIn)) {
                                                        return 0;
                                                    }

                                                    return $clockOut->diffInMinutes($clockIn) / 60;
                                                });

                                                $archiveGrossPay = (float) ($archive->rate_per_hour ?? 0) * $archiveTotalHours;
                                                $archiveSssEmployee = round($archiveGrossPay * 0.045, 2);
                                                $archivePhilhealthEmployee = round($archiveGrossPay * 0.025, 2);
                                                $archivePagibigEmployee = round(min($archiveGrossPay, 5000) * 0.02, 2);
                                                $archiveNetPay = $archiveGrossPay - ($archiveSssEmployee + $archivePhilhealthEmployee + $archivePagibigEmployee);
                                                $archiveNetPay = max($archiveNetPay, 0);
                                            @endphp
                                            <tr>
                                                <td>{{ $archive->id }}</td>
                                                <td>{{ $archive->first_name }} {{ $archive->last_name }}</td>
                                                <td>{{ $archive->email }}</td>
                                                <td>{{ optional($archive->role)->name }}</td>
                                                <td>{{ $archive->phone_number }}</td>
                                                <td>{{ $archive->created_at }}</td>
                                                <td>₱{{ number_format((float) $archive->rate_per_hour, 2) }}</td>
                                                <td>
                                                    @if($archiveTotalHours > 0)
                                                        ₱{{ number_format($archiveNetPay, 2) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $archive->payrolls_count ?? $archive->payrolls->count() }}</td>
                                                <td class="action-button">
                                                    <div class="d-flex gap-2">
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveRestoreModal-{{ $archive->id }}"
                                                            data-id="{{ $archive->id }}"
                                                            title="Restore"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-rotate-left text-success"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveDeleteModal-{{ $archive->id }}"
                                                            data-id="{{ $archive->id }}"
                                                            title="Delete"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-trash text-danger"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="archiveRestoreModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveRestoreModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore staff ({{ $archive->email }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
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
                                                            <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete archived staff ({{ $archive->email }}) permanently?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
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
                                                document.getElementById('archive-restore-modal-form-{{ $archive->id }}')?.addEventListener('submit', function (e) {
                                                    const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                            <script>
                                                document.getElementById('archive-delete-modal-form-{{ $archive->id }}')?.addEventListener('submit', function (e) {
                                                    const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No archived staff found.</td>
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
            const form = document.getElementById('staff-filter-form');
            if (!form) {
                return;
            }

            const statusInput = document.getElementById('staff-status-filter');
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
        });
    </script>

    {{-- UPDATED START: Single canvas on page (moved outside the loop, removed duplicates) --}}
    <canvas id="payslipCanvas" width="800" height="1200" style="display: none;"></canvas>
    {{-- UPDATED END --}}

    {{-- UPDATED START: Robust JS that reads data-attributes and generates the payslip --}}
    <script>
    (function () {
        // Attach click handlers to all "Download Payslip" buttons
        document.querySelectorAll('.download-payslip').forEach(btn => {
            btn.addEventListener('click', () => {
                const d = btn.dataset;

                const total      = parseFloat(d.total || 0);
                const rate       = parseFloat(d.rate || 0);
                const sss        = parseFloat(d.sss || 0);
                const philhealth = parseFloat(d.philhealth || 0);
                const pagibig    = parseFloat(d.pagibig || 0);
                const name       = d.name || '';

                let payrolls = [];
                try {
                    payrolls = JSON.parse(d.payrolls || '[]');
                } catch (e) {
                    payrolls = [];
                }

                downloadPayslip(total, name, rate, sss, philhealth, pagibig, payrolls);
            });
        });

        function downloadPayslip(totalAmount, name, rate, sss, philhealth, pagibig, payrolls) {
            const canvas = document.getElementById("payslipCanvas");
            const ctx = canvas.getContext("2d");

            // Setup
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Border
            ctx.strokeStyle = "#000000";
            ctx.lineWidth = 2;
            ctx.strokeRect(40, 40, canvas.width - 80, canvas.height - 80);

            // Header
            ctx.fillStyle = "#000000";
            ctx.font = "bold 28px Arial";
            ctx.textAlign = "center";
            ctx.fillText("OFFICIAL PAYSLIP", canvas.width / 2, 90);

            ctx.font = "16px Arial";
            ctx.fillText("3kfitnes GYM", canvas.width / 2, 120);
            ctx.fillText("--------", canvas.width / 2, 145);
            ctx.fillText("--------", canvas.width / 2, 170);

            ctx.textAlign = "left";
            let y = 210;

            const monthStr = "{{ \Carbon\Carbon::now()->format('F d, Y') }}";

            // Employee and Date Info
            ctx.font = "bold 18px Arial";
            ctx.fillText(`Employee Name:`, 60, y);
            ctx.font = "18px Arial";
            ctx.fillText(`${name}`, 220, y);

            y += 30;
            ctx.font = "bold 18px Arial";
            ctx.fillText(`Payroll Period:`, 60, y);
            ctx.font = "18px Arial";
            ctx.fillText(`${monthStr}`, 220, y);

            y += 30;
            ctx.font = "bold 18px Arial";
            ctx.fillText(`Hourly Rate:`, 60, y);
            ctx.font = "18px Arial";
            ctx.fillText(`₱${Number.isFinite(rate) ? rate.toFixed(2) : '0.00'}`, 220, y);

            y += 40;
            ctx.beginPath();
            ctx.moveTo(60, y);
            ctx.lineTo(canvas.width - 60, y);
            ctx.stroke();

            // Payroll Entries
            y += 30;
            ctx.font = "bold 18px Arial";
            ctx.fillText("Payroll Details", 60, y);

            ctx.font = "16px Arial";
            (payrolls || []).forEach((p, index) => {
                y += 30;
                ctx.fillText(`Entry ${index + 1}`, 60, y);
                ctx.fillText(`Payroll ID: ${p.id}`, 80, y += 25);
                ctx.fillText(`Clock In: ${p.clockin}`, 80, y += 20);
                ctx.fillText(`Clock Out: ${p.clockout}`, 80, y += 20);
                ctx.fillText(`Hours Worked: ${p.hours}`, 80, y += 20);
            });

            y += 30;
            ctx.beginPath();
            ctx.moveTo(60, y);
            ctx.lineTo(canvas.width - 60, y);
            ctx.stroke();

            // Deductions
            y += 30;
            ctx.font = "bold 18px Arial";
            ctx.fillText("Deductions", 60, y);

            ctx.font = "16px Arial";
            ctx.fillText(`SSS: ₱${Number.isFinite(sss) ? sss.toFixed(2) : '0.00'}`, 80, y += 30);
            ctx.fillText(`PhilHealth: ₱${Number.isFinite(philhealth) ? philhealth.toFixed(2) : '0.00'}`, 80, y += 25);
            ctx.fillText(`Pag-IBIG: ₱${Number.isFinite(pagibig) ? pagibig.toFixed(2) : '0.00'}`, 80, y += 25);

            // Total
            y += 40;
            ctx.font = "bold 18px Arial";
            ctx.fillText(`Net Pay: ₱${Number.isFinite(totalAmount) ? totalAmount.toFixed(2) : '0.00'}`, 60, y);

            // Footer / Note
            y += 60;
            ctx.font = "italic 14px Arial";
            ctx.fillText("This is a system-generated payslip. No signature required.", 60, y);

            // Download as image
            const link = document.createElement("a");
            link.download = "payslip.png";
            link.href = canvas.toDataURL("image/png");
            link.click();
        }
    })();
    </script>
    {{-- UPDATED END --}}
@endsection
