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
                $nowForPrint = now();
                $printSchedules = collect($printSource->items())->map(function ($item) use ($weekdayLookup, $nowForPrint) {
                    $startDate = $item->class_start_date ? \Carbon\Carbon::parse($item->class_start_date) : null;
                    $endDate   = $item->class_end_date ? \Carbon\Carbon::parse($item->class_end_date) : null;
                    $dayKeys   = is_array($item->recurring_days) ? $item->recurring_days : json_decode($item->recurring_days ?? '[]', true);
                    $cadence   = collect($dayKeys ?? [])->map(function ($d) use ($weekdayLookup) {
                        return $weekdayLookup[$d] ?? ucfirst($d);
                    })->filter()->implode(', ');

                    $statusLabel = 'Past';
                    if ($startDate && $nowForPrint->lt($startDate)) {
                        $statusLabel = 'Upcoming';
                    } elseif ($startDate && $endDate && $nowForPrint->between($startDate, $endDate)) {
                        $statusLabel = 'Present';
                    }

                    $adminAcceptance = $item->isadminapproved == 0 ? 'Pending' :
                        ($item->isadminapproved == 1 ? 'Approve' :
                        ($item->isadminapproved == 2 ? 'Reject' : ''));

                    $trainerName = $item->trainer_id == 0
                        ? 'No Trainer for now'
                        : trim((optional($item->user)->first_name ?? '') . ' ' . (optional($item->user)->last_name ?? ''));

                    $timeRange = $item->class_start_time && $item->class_end_time
                        ? \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A')
                        : null;

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'class_code' => $item->class_code,
                        'trainer' => $trainerName ?: '—',
                        'trainer_rate' => $item->trainer_rate_per_hour !== null
                            ? number_format((float) $item->trainer_rate_per_hour, 2)
                            : null,
                        'slots' => $item->slots,
                        'enrolled' => $item->user_schedules_count ?? 0,
                        'start' => $startDate ? $startDate->format('M j, Y g:i A') : 'Not set',
                        'end' => $endDate ? $endDate->format('M j, Y g:i A') : '—',
                        'time_range' => $timeRange,
                        'cadence' => $cadence ?: 'One-time',
                        'status' => $statusLabel,
                        'admin_status' => $adminAcceptance,
                        'rejection_reason' => $item->rejection_reason ?: '',
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printPayload = [
                    'title' => $showArchived ? 'Archived classes' : 'Class schedules',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'search_column' => request('search_column'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printSchedules->count(),
                    'items' => $printSchedules,
                ];

                $printAllSchedules = collect($printAllSource ?? [])->map(function ($item) use ($weekdayLookup, $nowForPrint) {
                    $startDate = $item->class_start_date ? \Carbon\Carbon::parse($item->class_start_date) : null;
                    $endDate   = $item->class_end_date ? \Carbon\Carbon::parse($item->class_end_date) : null;
                    $dayKeys   = is_array($item->recurring_days) ? $item->recurring_days : json_decode($item->recurring_days ?? '[]', true);
                    $cadence   = collect($dayKeys ?? [])->map(function ($d) use ($weekdayLookup) {
                        return $weekdayLookup[$d] ?? ucfirst($d);
                    })->filter()->implode(', ');

                    $statusLabel = 'Past';
                    if ($startDate && $nowForPrint->lt($startDate)) {
                        $statusLabel = 'Upcoming';
                    } elseif ($startDate && $endDate && $nowForPrint->between($startDate, $endDate)) {
                        $statusLabel = 'Present';
                    }

                    $adminAcceptance = $item->isadminapproved == 0 ? 'Pending' :
                        ($item->isadminapproved == 1 ? 'Approve' :
                        ($item->isadminapproved == 2 ? 'Reject' : ''));

                    $trainerName = $item->trainer_id == 0
                        ? 'No Trainer for now'
                        : trim((optional($item->user)->first_name ?? '') . ' ' . (optional($item->user)->last_name ?? ''));

                    $timeRange = $item->class_start_time && $item->class_end_time
                        ? \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A')
                        : null;

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'class_code' => $item->class_code,
                        'trainer' => $trainerName ?: '—',
                        'trainer_rate' => $item->trainer_rate_per_hour !== null
                            ? number_format((float) $item->trainer_rate_per_hour, 2)
                            : null,
                        'slots' => $item->slots,
                        'enrolled' => $item->user_schedules_count ?? 0,
                        'start' => $startDate ? $startDate->format('M j, Y g:i A') : 'Not set',
                        'end' => $endDate ? $endDate->format('M j, Y g:i A') : '—',
                        'time_range' => $timeRange,
                        'cadence' => $cadence ?: 'One-time',
                        'status' => $statusLabel,
                        'admin_status' => $adminAcceptance,
                        'rejection_reason' => $item->rejection_reason ?: '',
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printAllPayload = [
                    'title' => $showArchived ? 'Archived classes (all pages)' : 'Class schedules (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'search_column' => request('search_column'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                        'scope' => 'all',
                    ],
                    'count' => $printAllSchedules->count(),
                    'items' => $printAllSchedules,
                ];
            @endphp
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
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.gym-management.schedules', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.gym-management.schedules', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
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
                                                    <label for="search-column" class="form-label text-muted text-uppercase small mb-1">Search by</label>
                                                    <select id="search-column" name="search_column" class="form-select rounded-3">
                                                        <option value="" disabled {{ request('search_column') ? '' : 'selected' }}>Select Option</option>
                                                        <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>#</option>
                                                        <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Class Name</option>
                                                        <option value="class_code" {{ request('search_column') == 'class_code' ? 'selected' : '' }}>Class Code</option>
                                                        <option value="trainer_name" {{ request('search_column') == 'trainer_name' ? 'selected' : '' }}>Trainer</option>
                                                        <option value="trainer_rate_per_hour" {{ request('search_column') == 'trainer_rate_per_hour' ? 'selected' : '' }}>Trainer Rate Per Hour</option>
                                                        <option value="slots" {{ request('search_column') == 'slots' ? 'selected' : '' }}>Slots</option>
                        	                            <option value="class_start_date" {{ request('search_column') == 'class_start_date' ? 'selected' : '' }}>Class Start Date</option>
                                                        <option value="class_end_date" {{ request('search_column') == 'class_end_date' ? 'selected' : '' }}>Class End Date</option>
                                                        <option value="rejection_reason" {{ request('search_column') == 'rejection_reason' ? 'selected' : '' }}>Reject Reason</option>
                                                        <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
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
                        if (status === 'Upcoming') return 'badge-soft-info';
                        if (status === 'Present') return 'badge-soft-success';
                        if (status === 'Past') return 'badge-soft-secondary';
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
                            const rejection = item.rejection_reason
                                ? `<div class="muted">Reason: ${item.rejection_reason}</div>`
                                : '';
                            const timeRange = item.time_range
                                ? `<div class="muted">Time: ${item.time_range}</div>`
                                : '';
                            const trainerRate = item.trainer_rate
                                ? `<div class="muted">₱${item.trainer_rate} / hr</div>`
                                : '';
                            return [
                                item.id ?? '—',
                                `<div class="fw">${item.name || '—'}</div><div class="muted">${item.class_code || ''}</div>`,
                                `<div>${item.trainer || '—'}</div>${trainerRate}`,
                                `<div>${item.start || 'Not set'}</div><div class="muted">${item.end || '—'}</div>${timeRange}<div class="muted">Cadence: ${item.cadence || '—'}</div>`,
                                `<div class="fw">${item.slots ?? 0} slots</div><div class="muted">${item.enrolled ?? 0} enrolled</div>`,
                                `<span class="badge ${getBadgeClass(item.status)}">${item.status || '—'}</span>`,
                                `<div>${item.admin_status || '—'}</div>${rejection}`,
                                `<div>${item.created_at || ''}</div><div class="muted">${item.updated_at || ''}</div>`,
                            ];
                        });
                    }

                    function renderPrintWindow(payload) {
                        const rawItems = payload && payload.items ? payload.items : [];
                        const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                        const filters = buildFilters(payload.filters || {});
                        const headers = ['#', 'Class', 'Trainer', 'Schedule', 'Enrollment', 'Status', 'Admin', 'Audit'];
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

                            form.submit();
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

            @php
                $pendingRescheduleCount = $pendingRescheduleRequests->where('status', 0)->count();
                $resolvedRescheduleCount = $pendingRescheduleRequests->where('status', '!=', 0)->count();
                $formatRequestTime = function ($time) {
                    try {
                        return \Carbon\Carbon::parse($time)->format('g:i A');
                    } catch (\Exception $e) {
                        return $time;
                    }
                };
            @endphp

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Trainer cadence</span>
                                <h5 class="fw-semibold mb-1">Reschedule requests</h5>
                                <p class="text-muted mb-0">Trainers can propose a new cadence and time. Approving will update the class schedule automatically.</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Pending: {{ $pendingRescheduleCount }}</span>
                                <span class="badge bg-secondary text-white px-3 py-2 rounded-pill ms-2">Resolved: {{ $resolvedRescheduleCount }}</span>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>Requested cadence</th>
                                        <th>Series window</th>
                                        <th>Notes</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pendingRescheduleRequests as $requestItem)
                                        @php
                                            $statusMap = [
                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                            ];
                                            $statusMeta = $statusMap[$requestItem->status] ?? $statusMap[0];
                                            $classItem = $requestItem->schedule;
                                            $trainer = $requestItem->trainer;
                                            $dayList = collect($requestItem->recurring_days ?? [])->map(function ($d) use ($weekdayLookup) {
                                                return $weekdayLookup[$d] ?? ucfirst($d);
                                            })->implode(', ');
                                            $seriesRange = $requestItem->proposed_series_start_date && $requestItem->proposed_series_end_date
                                                ? $requestItem->proposed_series_start_date->format('M j, Y') . ' → ' . $requestItem->proposed_series_end_date->format('M j, Y')
                                                : 'Keep existing';
                                        @endphp
                                        <tr>
                                            <td>{{ $requestItem->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $classItem->name ?? 'Class #' . $requestItem->schedule_id }}</div>
                                                <div class="text-muted small">{{ $classItem->class_code ?? '' }}</div>
                                            </td>
                                            <td>
                                                {{ $trainer ? ($trainer->first_name . ' ' . $trainer->last_name) : 'Trainer' }}
                                                @if($trainer && $trainer->email)
                                                    <div class="text-muted small">{{ $trainer->email }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $dayList ?: '—' }}</div>
                                                <div class="text-muted small">{{ $formatRequestTime($requestItem->proposed_start_time) }} - {{ $formatRequestTime($requestItem->proposed_end_time) }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $seriesRange }}</div>
                                                <div class="text-muted small">Requested {{ $requestItem->created_at ? $requestItem->created_at->format('M j, Y g:i A') : '' }}</div>
                                            </td>
                                            <td class="text-muted">
                                                {{ $requestItem->notes ?: '—' }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">{{ $statusMeta['label'] }}</span>
                                                @if($requestItem->responded_at)
                                                    <div class="text-muted small mt-1">Handled {{ $requestItem->responded_at->format('M j, Y') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if((int) $requestItem->status === 0)
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-success"
                                                            data-resched-action
                                                            data-mode="approve"
                                                            data-id="{{ $requestItem->id }}"
                                                        >
                                                            Approve
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            data-resched-action
                                                            data-mode="reject"
                                                            data-id="{{ $requestItem->id }}"
                                                        >
                                                            Reject
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">No action needed</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr id="reschedule-inline-{{ $requestItem->id }}" class="resched-inline d-none">
                                            <td colspan="8">
                                                <div class="border rounded-4 p-3 bg-light-subtle">
                                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="badge bg-success bg-opacity-10 text-success rounded-circle p-3">
                                                                <i class="fa-solid fa-calendar-check"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-uppercase text-muted small mb-1 resched-inline-title">Approve reschedule</p>
                                                                <h6 class="mb-0 fw-semibold">{{ $classItem->name ?? 'Class' }} ({{ $classItem->class_code ?? '—' }})</h6>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary resched-inline-cancel" data-id="{{ $requestItem->id }}">Close</button>
                                                    </div>
                                                    <div class="row g-3 align-items-stretch mt-3">
                                                        <div class="col-12 col-md-5">
                                                            <div class="border rounded-4 p-3 h-100 bg-white">
                                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                                    <div class="text-success fs-5">
                                                                        <i class="fa-solid fa-calendar-check"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-semibold">Requested cadence</div>
                                                                        <div class="text-muted small">{{ $dayList ?: 'The requested cadence' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                                    <div class="text-success fs-5">
                                                                        <i class="fa-solid fa-clock"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-semibold">Time</div>
                                                                        <div class="text-muted small">{{ $formatRequestTime($requestItem->proposed_start_time) }} – {{ $formatRequestTime($requestItem->proposed_end_time) }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex align-items-start gap-3">
                                                                    <div class="text-success fs-5">
                                                                        <i class="fa-solid fa-repeat"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-semibold">Series window</div>
                                                                        <div class="text-muted small">{{ $seriesRange }}</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-7 d-flex flex-column">
                                                            <form method="POST" action="{{ route('admin.gym-management.schedules.reschedules.update', $requestItem->id) }}" class="d-flex flex-column h-100">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" class="resched-status-input" value="1">
                                                                <label class="form-label fw-semibold resched-label">Internal comment (optional)</label>
                                                                <textarea class="form-control flex-grow-1" name="admin_comment" rows="6" placeholder="Add a note for the trainer or staff">{{ old('admin_comment') }}</textarea>
                                                                <div class="d-flex gap-2 justify-content-end mt-3">
                                                                    <button type="button" class="btn btn-light resched-inline-cancel" data-id="{{ $requestItem->id }}">Cancel</button>
                                                                    <button type="submit" class="btn btn-success resched-submit-btn">
                                                                        <i class="fa-solid fa-circle-check me-2"></i><span class="resched-submit-text">Approve request</span>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                No reschedule requests yet. Trainers can request changes from the class detail screen.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
                </div>
                <div class="col-lg-12">
                    <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                    <tr>
                                            <th class="sortable" data-column="id"># <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_name">Class</th>
                                            <th class="sortable" data-column="trainer">Trainer</th>
                                            <th class="sortable" data-column="start_date">Schedule</th>
                                            <th class="sortable" data-column="slots">Enrollment</th>
                                            <th class="sortable" data-column="admin_acceptance">Admin Acceptance</th>
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
                                                $latestReschedule = $pendingReschedules->first();
                                            @endphp
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $item->name }}</div>
                                                    <div class="text-muted small">{{ $item->class_code }}</div>
                                                </td>
                                                <td>
                                                    {{ $item->trainer_id == 0 ? 'No Trainer for now' : optional($item->user)->first_name .' '. optional($item->user)->last_name }}
                                                </td>
                                                <td class="small">
                                                    <div class="fw-semibold">
                                                        {{ $start_date ? $start_date->format('M j, Y g:iA') : 'Not set' }}
                                                    </div>
                                                    <div class="text-muted">
                                                        {{ $end_date ? $end_date->format('M j, Y g:iA') : '—' }}
                                                    </div>
                                                    <div class="text-muted">Time: {{ $item->class_start_time && $item->class_end_time ? \Carbon\Carbon::parse($item->class_start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($item->class_end_time)->format('g:i A') : '—' }}</div>
                                                    <div class="text-muted">Cadence: {{ $dayLabel ?: 'One-time' }}</div>
                                                    <div class="mt-1">
                                                        @php $now = now(); @endphp
                                                        @if ($start_date && $now->lt($start_date))
                                                            <span class="badge rounded-pill bg-warning">Upcoming</span>
                                                        @elseif ($start_date && $end_date && $now->between($start_date, $end_date))
                                                            <span class="badge rounded-pill bg-success">Present</span>
                                                        @elseif ($end_date && $now->gt($end_date))
                                                            <span class="badge rounded-pill bg-primary">Past</span>
                                                        @else
                                                            <span class="badge rounded-pill bg-primary">Past</span>
                                                        @endif
                                                    </div>
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

                                                        @if((int)$item->isadminapproved !== 1)
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-primary btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#statusModal-{{ $item->id }}"
                                                                aria-label="Change Status"
                                                            >
                                                                Change
                                                            </button>
                                                        @endif
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
                                                                    <div class="mb-3 d-none" id="rejectReasonGroup-{{ $item->id }}">
                                                                        <label for="rejectReasonInput-{{ $item->id }}" class="form-label fw-semibold">Rejection reason</label>
                                                                        <textarea
                                                                            class="form-control"
                                                                            id="rejectReasonInput-{{ $item->id }}"
                                                                            name="rejection_reason"
                                                                            rows="3"
                                                                            placeholder="Provide rejection reason">{{ old('rejection_reason', $item->rejection_reason) }}</textarea>
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
                                                        const reasonGroup = document.getElementById('rejectReasonGroup-{{ $item->id }}');
                                                        const reasonInput = document.getElementById('rejectReasonInput-{{ $item->id }}');

                                                        function updateGuard() {
                                                            const rejectChosen = select.value === '2';
                                                            if (reasonGroup && reasonInput) {
                                                                if (rejectChosen) {
                                                                    reasonGroup.classList.remove('d-none');
                                                                    reasonInput.required = true;
                                                                } else {
                                                                    reasonGroup.classList.add('d-none');
                                                                    reasonInput.required = false;
                                                                }
                                                            }
                                                            if (hasUsers && rejectChosen) {
                                                                if (guardBox) {
                                                                    guardBox.classList.remove('d-none');
                                                                }
                                                                if (confirmC) {
                                                                    saveBtn.disabled = !confirmC.checked;
                                                                }
                                                            } else {
                                                                if (guardBox) {
                                                                    guardBox.classList.add('d-none');
                                                                }
                                                                saveBtn.disabled = false;
                                                                if (confirmC) {
                                                                    confirmC.checked = false;
                                                                }
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
                                                <td class="small">
                                                    <div class="fw-semibold">
                                                        {{ $pendingCount ? $pendingCount . ' pending' : 'No pending' }}
                                                    </div>
                                                    @if($latestReschedule)
                                                        <div class="text-muted">Requested {{ $latestReschedule->created_at? $latestReschedule->created_at->format('M j, Y') : '' }}</div>
                                                        <div class="text-muted">Notes: {{ $latestReschedule->notes ?: '—' }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.gym-management.schedules.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                        <div class="action-button">
                                                            <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <i class="fa-solid fa-trash text-danger"></i>
                                                            </button>
                                                        </div> 
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow rounded-4">
                                                        <div class="modal-header border-0 pb-0">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                                </div>
                                                                <div>
                                                                    <p class="text-uppercase text-muted small mb-1">Delete class</p>
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
                                                                    Deleting will remove this class{{ $item->user_schedules_count ? ' and its enrollments' : '' }}. This action cannot be undone.
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
                                                                    Delete class
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
                                                <th>Admin Acceptance</th>
                                                <th>Updated Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($archivedData as $archive)
                                                @php
                                                    $archiveStart = $archive->class_start_date ? \Carbon\Carbon::parse($archive->class_start_date) : null;
                                                    $archiveEnd = $archive->class_end_date ? \Carbon\Carbon::parse($archive->class_end_date) : null;
                                                @endphp
                                                <tr>
                                                    <td>{{ $archive->id }}</td>
                                                    <td>{{ $archive->name }}</td>
                                                    <td>{{ $archive->class_code }}</td>
                                                    <td>{{ $archive->trainer_id == 0 ? 'No Trainer for now' : optional($archive->user)->first_name .' '. optional($archive->user)->last_name }}</td>
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
                                                    <td>
                                                        @php
                                                            $statusMap = [
                                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                                            ];
                                                            $archivedStatus = $statusMap[$archive->isadminapproved] ?? $statusMap[0];
                                                        @endphp
                                                        <span class="badge {{ $archivedStatus['class'] }} px-3 py-2">
                                                            {{ $archivedStatus['label'] }}
                                                        </span>
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
            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }
        });
    </script>
@endsection
