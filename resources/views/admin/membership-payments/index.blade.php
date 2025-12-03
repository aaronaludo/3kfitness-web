@extends('layouts.admin')
@section('title', 'Membership Payments')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $activeMemberships = $data;
                $archivedMemberships = $archivedData;
                $sortedPayrollHistory = $payrollHistory ?? collect();
                $printSource = $showArchived ? $archivedMemberships : $activeMemberships;
                $printStartIndex = method_exists($printSource, 'firstItem') ? ($printSource->firstItem() ?? 1) : 1;
                $printMemberships = collect($printSource->items())->values()->map(function ($item, $idx) use ($printStartIndex) {
                    $expirationAt = $item->expiration_at ? \Carbon\Carbon::parse($item->expiration_at) : null;
                    $createdAt = $item->created_at ? \Carbon\Carbon::parse($item->created_at) : null;
                    $updatedAt = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at) : null;

                    $statusMap = [
                        0 => 'Pending',
                        1 => 'Approved',
                        2 => 'Rejected',
                    ];
                    $member = $item->user;
                    $memberName = trim((optional($member)->first_name ?? '') . ' ' . (optional($member)->last_name ?? ''));
                    $membership = $item->membership;
                    $currency = optional($membership)->currency ?: 'PHP';
                    $price = optional($membership)->price;

                    $classes = collect(optional($item->user)->userSchedules)->map(function ($userSchedule) {
                        $schedule = $userSchedule->schedule;
                        if (!$schedule) {
                            return null;
                        }
                        return [
                            'id' => $schedule->id,
                            'name' => $schedule->name,
                        ];
                    })->filter()->unique('id')->values();

                    return [
                        'number' => $item->id,
                        'id' => $item->id,
                        'member' => $memberName ?: '—',
                        'member_email' => optional($member)->email ?? '',
                        'membership' => optional($membership)->name ?: '—',
                        'expiration' => $expirationAt ? $expirationAt->format('M j, Y g:i A') : '—',
                        'created' => $createdAt ? $createdAt->format('M j, Y g:i A') : '—',
                        'updated' => $updatedAt ? $updatedAt->format('M j, Y g:i A') : '—',
                        'status' => $statusMap[$item->isapproved] ?? 'Pending',
                        'amount' => trim(($currency ? $currency . ' ' : '') . number_format((float) $price, 2)),
                        'classes' => $classes->pluck('name')->all(),
                        'created_by' => $item->created_by ?: '',
                    ];
                });

                $printPayload = [
                    'title' => $showArchived ? 'Archived membership payments' : 'Membership payments',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name', request('member_name')),
                        'search_column' => request('search_column'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printMemberships->count(),
                    'items' => $printMemberships,
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Membership Payments</h2></div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary ms-2">
                        <i class="fa-solid fa-chart-line"></i>&nbsp;&nbsp;&nbsp;Sales
                    </a>
                    <form action="{{ route('admin.staff-account-management.membership-payments.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name', request('member_name')) }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <button
                            class="btn btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            aria-label="Open printable/PDF view of filtered membership payments"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            <span class="ms-1">Print</span>
                        </button>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.membership-payments', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.membership-payments', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>
            </div>
            @php
                $statusFilter = request('status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All memberships',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'pending' => [
                        'label' => 'Pending',
                        'count' => $statusTallies['pending'] ?? null,
                    ],
                    'approved' => [
                        'label' => 'Approved',
                        'count' => $statusTallies['approved'] ?? null,
                    ],
                    'rejected' => [
                        'label' => 'Rejected',
                        'count' => $statusTallies['rejected'] ?? null,
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
                                <h4 class="fw-semibold mb-1">Membership payments</h4>
                                <p class="text-muted mb-0">Quickly spot pending approvals or focus on recent renewals using the filters below.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedMemberships->total() }} archived payments
                                    @else
                                        Showing {{ $activeMemberships->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.membership-payments') }}" method="GET" id="membership-payment-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="membership-payment-status-filter" value="{{ $statusFilter }}">
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
                                                placeholder="Search members or plans"
                                                value="{{ request('name', request('member_name')) }}"
                                                aria-label="Search membership payments"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.staff-account-management.membership-payments', ['show_archived' => 1]) : route('admin.staff-account-management.membership-payments') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#membershipPaymentFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="membershipPaymentFiltersModal" tabindex="-1" aria-labelledby="membershipPaymentFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="membershipPaymentFiltersModalLabel">Advanced filters</h5>
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
                                                        <option value="member_name" {{ request('search_column', 'member_name') == 'member_name' ? 'selected' : '' }}>Member Name</option>
                                                        <option value="membership" {{ request('search_column') == 'membership' ? 'selected' : '' }}>Membership</option>
                                                        <option value="expiration_at" {{ request('search_column') == 'expiration_at' ? 'selected' : '' }}>Expiration Date</option>
                                                        <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                                        <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                                        <option value="status" {{ request('search_column') == 'status' ? 'selected' : '' }}>Status</option>
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
                                            <th class="sortable" data-column="member_name">Member Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="membership">Membership <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="expiration_date">Expiration Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="status">Status <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="amount">Amount <i class="fa fa-sort"></i></th>
                                            <th>Classes Enrolled</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($activeMemberships as $item)
                                            @php
                                                $rowNumber = ($activeMemberships->firstItem() ?? 0) + $loop->index;
                                                $expirationAt = $item->expiration_at ? \Carbon\Carbon::parse($item->expiration_at) : null;
                                                $createdAt = $item->created_at ? \Carbon\Carbon::parse($item->created_at) : null;
                                                $updatedAt = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at) : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->user->first_name }} {{ $item->user->last_name }}</td>
                                                <td>{{ $item->membership->name }}</td>
                                                <td>{{ $expirationAt ? $expirationAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $createdAt ? $createdAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $updatedAt ? $updatedAt->format('F j, Y g:iA') : '' }}</td>
                                                <td>
                                                    @php
                                                        $statusMap = [
                                                            0 => ['label' => 'Pending',  'class' => 'bg-warning text-dark'],
                                                            1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                            2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                                        ];
                                                        $s = $statusMap[$item->isapproved] ?? $statusMap[0];
                                                    @endphp
                                                
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge {{ $s['class'] }} px-3 py-2">
                                                            {{ $s['label'] }}
                                                        </span>
                                                
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#umStatusModal-{{ $item->id }}"
                                                            aria-label="Change Status"
                                                        >
                                                            Change
                                                        </button>
                                                    </div>
                                                
                                                    <!-- Status Change Modal -->
                                                    <div class="modal fade" id="umStatusModal-{{ $item->id }}" tabindex="-1" aria-labelledby="umStatusModalLabel-{{ $item->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <form
                                                                method="POST"
                                                                action="{{ route('admin.staff-account-management.membership-payments.isapprove') }}"
                                                                class="modal-content"
                                                                id="umStatusForm-{{ $item->id }}"
                                                                {{-- Optional: if you have a related count (e.g., active usages), pass it here. If not present, guard stays hidden. --}}
                                                                data-related-count="{{ $item->memberships_count ?? 0 }}"
                                                            >
                                                                @csrf
                                                                {{-- Keep POST to match your current route; add @method('PUT') if your route expects PUT --}}
                                                                {{-- @method('PUT') --}}
                                                
                                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="umStatusModalLabel-{{ $item->id }}">Change Membership Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                
                                                                <div class="modal-body">
                                                                    {{-- Choices --}}
                                                                    <div class="mb-3">
                                                                        <label for="umStatusSelect-{{ $item->id }}" class="form-label fw-semibold">Select status</label>
                                                                        <select class="form-select" id="umStatusSelect-{{ $item->id }}" name="isapproved">
                                                                            <option value="0" {{ $item->isapproved == 0 ? 'selected' : '' }}>Pending</option>
                                                                            <option value="1" {{ $item->isapproved == 1 ? 'selected' : '' }}>Approve</option>
                                                                            <option value="2" {{ $item->isapproved == 2 ? 'selected' : '' }}>Reject</option>
                                                                        </select>
                                                                    </div>
                                                
                                                                    {{-- Conditional warning for Reject when there are related records --}}
                                                                    <div id="umRejectGuard-{{ $item->id }}" class="border rounded p-3 bg-light d-none">
                                                                        <div class="d-flex align-items-start gap-2">
                                                                            <i class="fa-solid fa-triangle-exclamation text-danger mt-1"></i>
                                                                            <div>
                                                                                <div class="fw-semibold text-danger mb-1">Heads up before rejecting</div>
                                                                                <div class="small text-muted">
                                                                                    This membership may have linked records (e.g., usage, payments, or sessions).
                                                                                    Rejecting could impact users. Please confirm you understand before proceeding.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-check mt-3">
                                                                            <input class="form-check-input" type="checkbox" value="1" id="umRejectConfirm-{{ $item->id }}">
                                                                            <label class="form-check-label" for="umRejectConfirm-{{ $item->id }}">
                                                                                I understand the impact of rejecting.
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        Cancel
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary" id="umSaveStatusBtn-{{ $item->id }}">
                                                                        Save
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                
                                                    <script>
                                                    (function() {
                                                        const form     = document.getElementById('umStatusForm-{{ $item->id }}');
                                                        const select   = document.getElementById('umStatusSelect-{{ $item->id }}');
                                                        const guardBox = document.getElementById('umRejectGuard-{{ $item->id }}');
                                                        const confirmC = document.getElementById('umRejectConfirm-{{ $item->id }}');
                                                        const saveBtn  = document.getElementById('umSaveStatusBtn-{{ $item->id }}');
                                                
                                                        // Interpret any positive integer as "has related records"
                                                        const relatedCount = parseInt(form.dataset.relatedCount, 10);
                                                        const hasRelated   = Number.isFinite(relatedCount) && relatedCount > 0;
                                                
                                                        function updateGuard() {
                                                            const rejectChosen = select.value === '2';
                                                            if (hasRelated && rejectChosen) {
                                                                guardBox.classList.remove('d-none');
                                                                saveBtn.disabled = !confirmC.checked;
                                                            } else {
                                                                guardBox.classList.add('d-none');
                                                                saveBtn.disabled = false;
                                                                if (confirmC) confirmC.checked = false;
                                                            }
                                                        }
                                                
                                                        // Init on open (Bootstrap modal event)
                                                        const modalEl = document.getElementById('umStatusModal-{{ $item->id }}');
                                                        modalEl.addEventListener('shown.bs.modal', updateGuard);
                                                
                                                        select.addEventListener('change', updateGuard);
                                                        if (confirmC) {
                                                            confirmC.addEventListener('change', () => {
                                                                if (select.value === '2' && hasRelated) {
                                                                    saveBtn.disabled = !confirmC.checked;
                                                                }
                                                            });
                                                        }
                                                    })();
                                                    </script>
                                                </td>
                                                <td>
                                                    @php
                                                        $currency = optional($item->membership)->currency ?: 'PHP';
                                                        $price = optional($item->membership)->price ?: 0;
                                                    @endphp
                                                    {{ $currency }} {{ number_format((float) $price, 2) }}
                                                </td>
                                                <td>
                                                    @php
                                                        $classes = collect(optional($item->user)->userSchedules)
                                                            ->map(function ($userSchedule) {
                                                                $schedule = $userSchedule->schedule;
                                                                if (!$schedule) {
                                                                    return null;
                                                                }
                                                                return [
                                                                    'id' => $schedule->id,
                                                                    'name' => $schedule->name,
                                                                ];
                                                            })
                                                            ->filter()
                                                            ->unique('id')
                                                            ->values();
                                                    @endphp
                                                    @if($classes->isNotEmpty())
                                                        @foreach($classes as $class)
                                                            <a
                                                                href="{{ route('admin.gym-management.schedules.view', $class['id']) }}"
                                                                class="text-decoration-none"
                                                            >
                                                                {{ $class['name'] }}
                                                            </a>@if(!$loop->last), @endif
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">No classes enrolled</span>
                                                    @endif
                                                </td>
                                                <td>{{ $item->created_by }}</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <div class="action-button">
                                                            <a href="{{ route('admin.staff-account-management.membership-payments.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                                        </div>
                                                        <div class="action-button">
                                                            <a href="{{ route('admin.staff-account-management.membership-payments.receipt', $item->id) }}" title="Receipt"><i class="fa-solid fa-receipt text-primary"></i></a>
                                                        </div>
                                                        <div class="action-button">
                                                            <button type="button" data-bs-toggle="modal" data-bs-target="#membershipPaymentArchiveModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Archive" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <i class="fa-solid fa-box-archive text-danger"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="membershipPaymentArchiveModal-{{ $item->id }}" tabindex="-1" aria-labelledby="membershipPaymentArchiveModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="membershipPaymentArchiveModalLabel-{{ $item->id }}">Archive membership payment (#{{ $item->id }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.membership-payments.delete') }}" method="POST" id="membership-payment-archive-form-{{ $item->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body">
                                                                <p class="mb-3 text-muted small">Provide your password to confirm moving this membership to archive.</p>
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="membership-payment-archive-submit-button-{{ $item->id }}">
                                                                    <span id="membership-payment-archive-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Archive
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('membership-payment-archive-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('membership-payment-archive-submit-button-{{ $item->id }}');
                                                    const loader = document.getElementById('membership-payment-archive-loader-{{ $item->id }}');
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $activeMemberships->links() }}
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
                                <h4 class="fw-semibold mb-0">Archived Memberships</h4>
                                <span class="text-muted small">Showing {{ $archivedMemberships->total() }} archived</span>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Member Name</th>
                                            <th>Membership</th>
                                            <th>Status</th>
                                            <th>Expiration Date</th>
                                            <th>Updated Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($archivedMemberships as $archive)
                                            @php
                                                $archiveRowNumber = ($archivedMemberships->firstItem() ?? 0) + $loop->index;
                                                $archiveExpiration = $archive->expiration_at ? \Carbon\Carbon::parse($archive->expiration_at) : null;
                                                $archiveUpdated = $archive->updated_at ? \Carbon\Carbon::parse($archive->updated_at) : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $archiveRowNumber ?: $loop->iteration }}</td>
                                                <td>{{ optional($archive->user)->first_name }} {{ optional($archive->user)->last_name }}</td>
                                                <td>{{ optional($archive->membership)->name }}</td>
                                                <td>
                                                    @php
                                                        $statusMap = [
                                                            0 => ['label' => 'Pending',  'class' => 'bg-warning text-dark'],
                                                            1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                            2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                                        ];
                                                        $archiveStatus = $statusMap[$archive->isapproved] ?? $statusMap[0];
                                                    @endphp
                                                    <span class="badge {{ $archiveStatus['class'] }} px-3 py-2">
                                                        {{ $archiveStatus['label'] }}
                                                    </span>
                                                </td>
                                                <td>{{ $archiveExpiration ? $archiveExpiration->format('F j, Y g:iA') : '' }}</td>
                                                <td>{{ $archiveUpdated ? $archiveUpdated->format('F j, Y g:iA') : '' }}</td>
                                                <td class="action-button">
                                                    <div class="d-flex gap-2">
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#membershipPaymentArchiveRestoreModal-{{ $archive->id }}" data-id="{{ $archive->id }}" title="Restore" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-rotate-left text-success"></i>
                                                        </button>
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#membershipPaymentArchiveDeleteModal-{{ $archive->id }}" data-id="{{ $archive->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-trash text-danger"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="membershipPaymentArchiveRestoreModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="membershipPaymentArchiveRestoreModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="membershipPaymentArchiveRestoreModalLabel-{{ $archive->id }}">Restore membership payment (#{{ $archive->id }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.membership-payments.restore') }}" method="POST" id="membership-payment-archive-restore-form-{{ $archive->id }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <p class="mb-3 text-muted small">Provide your password to confirm restoring this membership.</p>
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-success" type="submit" id="membership-payment-archive-restore-submit-button-{{ $archive->id }}">
                                                                    <span id="membership-payment-archive-restore-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Restore
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="membershipPaymentArchiveDeleteModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="membershipPaymentArchiveDeleteModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="membershipPaymentArchiveDeleteModalLabel-{{ $archive->id }}">Delete membership payment (#{{ $archive->id }}) permanently?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.membership-payments.delete') }}" method="POST" id="membership-payment-archive-delete-form-{{ $archive->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <p class="mb-3 text-muted small">This action permanently removes the membership. Confirm with your password.</p>
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="membership-payment-archive-delete-submit-button-{{ $archive->id }}">
                                                                    <span id="membership-payment-archive-delete-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Delete
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('membership-payment-archive-restore-form-{{ $archive->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('membership-payment-archive-restore-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('membership-payment-archive-restore-loader-{{ $archive->id }}');
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                            <script>
                                                document.getElementById('membership-payment-archive-delete-form-{{ $archive->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('membership-payment-archive-delete-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('membership-payment-archive-delete-loader-{{ $archive->id }}');
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No archived memberships found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                {{ $archivedMemberships->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @if($sortedPayrollHistory->isNotEmpty())
                <div class="box mt-4">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h4 class="fw-semibold mb-0">Payroll History</h4>
                                <span class="text-muted small">Recent processed payrolls</span>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Staff</th>
                                            <th>Period</th>
                                            <th>Hours</th>
                                            <th>Gross</th>
                                            <th>Net</th>
                                            <th>Processed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sortedPayrollHistory as $run)
                                            @php
                                                $staff = $run->user;
                                                $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                                                $processedAt = $run->processed_at ? $run->processed_at->format('M d, Y g:i A') : '—';
                                            @endphp
                                            <tr>
                                                <td>{{ $run->id }}</td>
                                                <td>{{ $name }}</td>
                                                <td>{{ $run->period_month }}</td>
                                                <td>{{ number_format($run->total_hours, 2) }} hrs</td>
                                                <td>₱{{ number_format($run->gross_pay, 2) }}</td>
                                                <td class="text-success fw-semibold">₱{{ number_format($run->net_pay, 2) }}</td>
                                                <td>{{ $processedAt }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
            const printButton = document.getElementById('print-submit-button');
            const printForm = document.getElementById('print-form');
            const printLoader = document.getElementById('print-loader');

            function getBadgeClass(status) {
                if (status === 'Approved') return 'badge-soft-success';
                if (status === 'Pending') return 'badge-soft-warning';
                if (status === 'Rejected') return 'badge-soft-danger';
                return 'badge-soft-muted';
            }

            function buildFilters(filters) {
                const chips = [];
                if (filters.show_archived) chips.push({ value: 'Archived view' });
                if (filters.status && filters.status !== 'all') chips.push({ label: 'Status', value: filters.status });
                if (filters.search) {
                    chips.push({
                        label: 'Search',
                        value: `${filters.search}${filters.search_column ? ` (${filters.search_column})` : ''}`,
                    });
                }
                if (filters.start || filters.end) {
                    chips.push({ label: 'Date', value: `${filters.start || '—'} → ${filters.end || '—'}` });
                }
                return chips;
            }

            function buildRows(items) {
                return items.map((item) => {
                    const classes = (item.classes || []).filter(Boolean).join(', ') || 'None';
                    return [
                        item.number ?? '—',
                        `<div class="fw">${item.member || '—'}</div><div class="muted">${item.member_email || ''}</div>`,
                        `<div>${item.membership || '—'}</div><div class="muted">Expires: ${item.expiration || '—'}</div>`,
                        `<div class="fw">${item.amount || 'PHP 0.00'}</div><div class="muted">Created: ${item.created || '—'}</div><div class="muted">Updated: ${item.updated || '—'}</div>`,
                        `<span class="badge ${getBadgeClass(item.status)}">${item.status || '—'}</span>`,
                        classes,
                        item.created_by || '—',
                    ];
                });
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = buildFilters(payload.filters || {});
                const headers = ['#', 'Member', 'Membership', 'Billing', 'Status', 'Classes', 'Created By'];
                const rows = buildRows(items);

                return window.PrintPreview
                    ? PrintPreview.tryOpen(payload, headers, rows, filters)
                    : false;
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
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('membership-payment-filter-form');
            if (!form) {
                return;
            }
            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }

            const statusInput = document.getElementById('membership-payment-status-filter');
            const statusChips = form.querySelectorAll('.status-chip');
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

            statusChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const selected = this.dataset.status;
                    statusInput.value = selected;

                    statusChips.forEach(function (btn) {
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
@endsection
