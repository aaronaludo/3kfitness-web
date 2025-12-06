@extends('layouts.admin')
@section('title', 'Classes History')

@section('content')
    <div class="container-fluid">
        @php
            $activeStatus = $filters['status'] ?? 'all';
            $statusLabels = [
                'all' => ['label' => 'All statuses', 'class' => 'bg-secondary'],
                'approved' => ['label' => 'Approved', 'class' => 'bg-success'],
                'pending' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger'],
            ];
            $showArchived = $filters['show_archived'] ?? false;
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['trainer_id'] ?? null) || ($activeStatus !== 'all') || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $showArchived || ($filters['search_column'] ?? null);
            $advancedFiltersOpen = ($filters['search_column'] ?? null) || ($filters['trainer_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'all';

            $printItems = collect($classes->items())->map(function ($class) {
                $trainer = $class->user;
                $start = $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                $end = $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                $statusMeta = [
                    0 => 'Pending',
                    1 => 'Approved',
                    2 => 'Rejected',
                ];

                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'code' => $class->class_code,
                    'trainer' => $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : 'Not assigned',
                    'trainer_code' => optional($trainer)->user_code ?? null,
                    'enrollments' => $class->user_schedules_count ?? 0,
                    'start' => $start ? $start->format('M d, Y g:i A') : null,
                    'end' => $end ? $end->format('M d, Y g:i A') : null,
                    'status' => $statusMeta[$class->isadminapproved] ?? 'Pending',
                    'archive' => (int) ($class->is_archieve ?? 0) === 1 ? 'Archived' : 'Active',
                ];
            })->values();

            $printAllItems = collect($printAllClasses ?? [])->map(function ($class) {
                $trainer = $class->trainer ?? null;
                $start = $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                $end = $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                $statusMeta = [
                    0 => 'Pending',
                    1 => 'Approved',
                    2 => 'Rejected',
                ];

                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'code' => $class->class_code,
                    'trainer' => $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : 'Not assigned',
                    'trainer_code' => optional($trainer)->user_code ?? null,
                    'enrollments' => $class->user_schedules_count ?? 0,
                    'start' => $start ? $start->format('M d, Y g:i A') : null,
                    'end' => $end ? $end->format('M d, Y g:i A') : null,
                    'status' => $statusMeta[$class->isadminapproved] ?? 'Pending',
                    'archive' => (int) ($class->is_archieve ?? 0) === 1 ? 'Archived' : 'Active',
                ];
            })->values();

            $printPayload = [
                'title' => $showArchived ? 'Archived class history' : 'Class history',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'trainer_id' => $filters['trainer_id'] ?? null,
                    'search_column' => $filters['search_column'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                    'archived' => $showArchived,
                ],
                'count' => $printItems->count(),
                'items' => $printItems,
            ];

            $printAllPayload = [
                'title' => $showArchived ? 'Archived class history (all pages)' : 'Class history (all pages)',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'trainer_id' => $filters['trainer_id'] ?? null,
                    'search_column' => $filters['search_column'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                    'archived' => $showArchived,
                    'scope' => 'all',
                ],
                'count' => $printAllItems->count(),
                'items' => $printAllItems,
            ];
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Classes History</h2>
                    <p class="text-muted mb-0 small">Finished classes with enrollment counts and admin status.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center h-100">
                    <form action="{{ route('admin.history.classes.print') }}" method="POST" id="class-history-print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="trainer_id" value="{{ $filters['trainer_id'] ?? '' }}">
                        <input type="hidden" name="search_column" value="{{ $filters['search_column'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <input type="hidden" name="show_archived" value="{{ $showArchived ? 1 : 0 }}">
                        <button
                            type="submit"
                            class="btn btn-danger"
                            id="class-history-print-submit"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered classes"
                        >
                            <i class="fa-solid fa-print me-2"></i>Print
                            <span id="class-history-print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-calendar-days me-2"></i>View classes
                    </a>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.classes', array_merge(request()->except(['show_archived', 'page']), [])) }}"
                        >
                            <i class="fa-solid fa-rotate-left me-2"></i>Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.classes', array_merge(request()->except(['page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive me-2"></i>View archived
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">History</span>
                                <h4 class="fw-semibold mb-1">Completed classes</h4>
                                <p class="text-muted mb-0">
                                    Filter finished sessions by trainer, admin status, or completion window. Showing
                                    {{ $showArchived ? 'archived' : 'active' }} records.
                                </p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Classes</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['classes'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Enrollments</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['enrollments'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Trainers</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['trainers'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.history.classes') }}" method="GET" id="class-history-filter-form" class="mt-3">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif
                            <input type="hidden" name="status" id="class-history-status-filter" value="{{ $activeStatus }}">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                @foreach ($statusLabels as $key => $label)
                                    @php
                                        $count = $statusTallies[$key] ?? null;
                                    @endphp
                                    <button
                                        type="button"
                                        class="btn btn-sm rounded-pill px-3 class-history-status-chip {{ $activeStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                        data-status="{{ $key }}"
                                        aria-label="Filter classes by {{ strtolower($label['label']) }}"
                                    >
                                        {{ $label['label'] }}
                                        @if(!is_null($count))
                                            <span class="badge bg-transparent {{ $activeStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $count }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                    <div class="position-relative">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input
                                            type="search"
                                            id="search"
                                            name="search"
                                            class="form-control rounded-pill ps-5"
                                            value="{{ $filters['search'] ?? '' }}"
                                            placeholder="Search class, code, or trainer"
                                            aria-label="Search class history records"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasFilters)
                                        <a href="{{ route('admin.history.classes', $showArchived ? ['show_archived' => 1] : []) }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#classHistoryFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="classHistoryFiltersModal" tabindex="-1" aria-labelledby="classHistoryFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="classHistoryFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill class-history-range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill class-history-range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill class-history-range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label for="trainer_id" class="form-label text-muted text-uppercase small mb-1">Trainer</label>
                                                    <select id="trainer_id" name="trainer_id" class="form-select rounded-3">
                                                        <option value="">All trainers</option>
                                                        @foreach ($trainerOptions as $trainer)
                                                            @php
                                                                $trainerName = trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
                                                            @endphp
                                                            <option
                                                                value="{{ $trainer->id }}"
                                                                {{ (string) ($filters['trainer_id'] ?? '') === (string) $trainer->id ? 'selected' : '' }}
                                                            >
                                                                {{ $trainerName !== '' ? $trainerName : 'Unnamed Trainer' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="status" class="form-label text-muted text-uppercase small mb-1">Admin status</label>
                                                    <select id="status" name="status_display" class="form-select rounded-3">
                                                        <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>All</option>
                                                        <option value="approved" {{ $activeStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                                        <option value="pending" {{ $activeStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="rejected" {{ $activeStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="class-history-search-column" class="form-label text-muted text-uppercase small mb-1">Search by</label>
                                                    <select id="class-history-search-column" name="search_column" class="form-select rounded-3">
                                                        <option value="" disabled {{ ($filters['search_column'] ?? '') ? '' : 'selected' }}>Select Option</option>
                                                        <option value="id" {{ ($filters['search_column'] ?? '') === 'id' ? 'selected' : '' }}>#</option>
                                                        <option value="name" {{ ($filters['search_column'] ?? '') === 'name' ? 'selected' : '' }}>Class Name</option>
                                                        <option value="class_code" {{ ($filters['search_column'] ?? '') === 'class_code' ? 'selected' : '' }}>Class Code</option>
                                                        <option value="trainer_name" {{ ($filters['search_column'] ?? '') === 'trainer_name' ? 'selected' : '' }}>Trainer Name</option>
                                                        <option value="trainer_code" {{ ($filters['search_column'] ?? '') === 'trainer_code' ? 'selected' : '' }}>Trainer Code</option>
                                                        <option value="trainer_email" {{ ($filters['search_column'] ?? '') === 'trainer_email' ? 'selected' : '' }}>Trainer Email</option>
                                                        <option value="enrollments" {{ ($filters['search_column'] ?? '') === 'enrollments' ? 'selected' : '' }}>Members</option>
                                                        <option value="class_start_date" {{ ($filters['search_column'] ?? '') === 'class_start_date' ? 'selected' : '' }}>Class Start Date</option>
                                                        <option value="class_end_date" {{ ($filters['search_column'] ?? '') === 'class_end_date' ? 'selected' : '' }}>Class End Date</option>
                                                        <option value="status" {{ ($filters['search_column'] ?? '') === 'status' ? 'selected' : '' }}>Admin Status</option>
                                                        <option value="archive" {{ ($filters['search_column'] ?? '') === 'archive' ? 'selected' : '' }}>Archive</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Ended range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="class-history-start-date" class="form-label small text-muted mb-1">From</label>
                                                            <input
                                                                type="date"
                                                                id="class-history-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ $filters['start_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="class-history-end-date" class="form-label small text-muted mb-1">To</label>
                                                            <input
                                                                type="date"
                                                                id="class-history-end-date"
                                                                class="form-control rounded-3"
                                                                name="end_date"
                                                                value="{{ $filters['end_date'] ?? '' }}"
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

            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>Trainer Code</th>
                                        <th>Members</th>
                                        <th>Class Window</th>
                                        <th>Status</th>
                                        <th>Archive</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($classes as $index => $class)
                                        @php
                                            $trainer = $class->user;
                                            $trainerName = $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : '';
                                            $start = $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                                            $end = $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                                            $statusValue = $class->isadminapproved;
                                            $statusMeta = [
                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                            ][$statusValue] ?? ['label' => 'Pending', 'class' => 'bg-warning text-dark'];
                                            $archiveLabel = (int) ($class->is_archieve ?? 0) === 1 ? 'Archived' : 'Active';
                                        @endphp
                                        <tr>
                                            <td>{{ $class->id ?? '—' }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $class->name }}</div>
                                                <div class="text-muted small">{{ $class->class_code }}</div>
                                            </td>
                                            <td>
                                                @if($trainer)
                                                    <div>{{ $trainerName }}</div>
                                                    <div class="text-muted small">{{ $trainer->email ?? '—' }}</div>
                                                @else
                                                    <span class="text-muted">Not assigned</span>
                                                @endif
                                            </td>
                                            <td><span class="text-muted small">{{ optional($trainer)->user_code ?? '—' }}</span></td>
                                            <td>{{ $class->user_schedules_count ?? 0 }}</td>
                                            <td>
                                                @if($start || $end)
                                                    <div>{{ $start ? $start->format('M d, Y g:i A') : '—' }}</div>
                                                    <div class="text-muted small">to {{ $end ? $end->format('M d, Y g:i A') : '—' }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">
                                                    {{ $statusMeta['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $archiveLabel === 'Archived' ? 'bg-secondary' : 'bg-success' }} px-3 py-2">
                                                    {{ $archiveLabel }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    <a
                                                        href="{{ route('admin.gym-management.schedules.view', $class->id) }}"
                                                        class="btn btn-outline-primary btn-sm"
                                                    >
                                                        View
                                                    </a>
                                                    <a
                                                        href="{{ route('admin.gym-management.schedules.users', $class->id) }}"
                                                        class="btn btn-outline-secondary btn-sm"
                                                    >
                                                        Members
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                No completed classes found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $classes->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('class-history-print-submit');
            const printForm = document.getElementById('class-history-print-form');
            const printLoader = document.getElementById('class-history-print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) {
                    const searchLabel = filters.search_column ? `${filters.search} (${filters.search_column})` : filters.search;
                    chips.push({ label: 'Search', value: searchLabel });
                }
                if (filters.trainer_id) chips.push({ label: 'Trainer ID', value: filters.trainer_id });
                if (filters.status && filters.status !== 'all') chips.push({ label: 'Status', value: filters.status });
                if (filters.archived) chips.push({ value: 'Archived records' });
                if (filters.start || filters.end) chips.push({ label: 'Ended', value: `${filters.start || '—'} → ${filters.end || '—'}` });
                return chips;
            }

            function buildRows(items) {
                return (items || []).map((item) => {
                    const trainer = item.trainer ? `<div class="muted">${item.trainer}</div>` : '';

                    return [
                        item.id ?? '—',
                        `<div class="fw">${item.name || '—'}</div><div class="muted">${item.code || ''}</div>`,
                        trainer || 'Not assigned',
                        item.trainer_code || '—',
                        item.enrollments ?? 0,
                        `<div>${item.start || '—'}</div><div class="muted">${item.end ? 'to ' + item.end : ''}</div>`,
                        item.status || '—',
                        item.archive || '—',
                    ];
                });
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = buildFilters(payload.filters || {});
                const headers = ['#', 'Class', 'Trainer', 'Trainer Code', 'Members', 'Class Window', 'Status', 'Archive'];
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
            const form = document.getElementById('class-history-filter-form');
            if (!form) {
                return;
            }

            const rangeButtons = form.querySelectorAll('.class-history-range-chip');
            const startInput = document.getElementById('class-history-start-date');
            const endInput = document.getElementById('class-history-end-date');
            const statusInput = document.getElementById('class-history-status-filter');
            const statusSelect = document.getElementById('status');
            const statusButtons = form.querySelectorAll('.class-history-status-chip');

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

            function setActiveStatus(status) {
                if (statusInput) statusInput.value = status;
                if (statusSelect) statusSelect.value = status;

                statusButtons.forEach((btn) => {
                    const isActive = btn.dataset.status === status;
                    btn.classList.toggle('btn-dark', isActive);
                    btn.classList.toggle('btn-outline-secondary', !isActive);
                });
            }

            statusButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const selectedStatus = this.dataset.status || '';
                    setActiveStatus(selectedStatus);
                    form.submit();
                });
            });

            if (statusSelect) {
                statusSelect.addEventListener('change', function () {
                    setActiveStatus(this.value || '');
                });
            }

            if (statusInput && statusInput.value) {
                setActiveStatus(statusInput.value);
            }

            rangeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const range = this.dataset.range;
                    applyRange(range);
                });
            });
        });
    </script>
@endsection
