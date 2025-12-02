@extends('layouts.admin')
@section('title', 'Attendances History')

@section('content')
    <div class="container-fluid">
        @php
            $activeStatus = $filters['status'] ?? 'completed';
            $statusLabels = [
                'all' => ['label' => 'All records', 'class' => 'bg-secondary'],
                'open' => ['label' => 'Open', 'class' => 'bg-warning text-dark'],
                'completed' => ['label' => 'Completed', 'class' => 'bg-success'],
            ];
            $showArchived = $filters['show_archived'] ?? false;
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['role_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'completed' || $showArchived;
            $advancedFiltersOpen = ($filters['role_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'completed';

            $printItems = collect($attendances->items())->map(function ($attendance) {
                $person = $attendance->user;
                $clockIn = $attendance->clockin_at ? \Carbon\Carbon::parse($attendance->clockin_at) : null;
                $clockOut = $attendance->clockout_at ? \Carbon\Carbon::parse($attendance->clockout_at) : null;
                $durationMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : null;
                $durationText = $durationMinutes !== null
                    ? sprintf('%dh %02dm', intdiv($durationMinutes, 60), $durationMinutes % 60)
                    : null;

                return [
                    'id' => $attendance->id,
                    'name' => $person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : 'Unknown user',
                    'role' => $person && $person->role ? ($person->role->name ?? null) : null,
                    'email' => $person ? ($person->email ?? null) : null,
                    'phone' => $person ? ($person->phone_number ?? null) : null,
                    'clock_in' => $clockIn ? $clockIn->format('M d, Y g:i A') : null,
                    'clock_out' => $clockOut ? $clockOut->format('M d, Y g:i A') : null,
                    'duration' => $durationText,
                    'status' => $attendance->clockout_at ? 'Completed' : 'Open',
                    'archive' => (int) ($attendance->is_archive ?? 0) === 1 ? 'Archived' : 'Active',
                ];
            })->values();

            $printPayload = [
                'title' => 'Attendance history',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'role_id' => $filters['role_id'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                    'archived' => $filters['show_archived'] ?? false,
                ],
                'count' => $printItems->count(),
                'items' => $printItems,
            ];
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Attendances History</h2>
                    <p class="text-muted mb-0 small">Completed attendance logs for members, trainers, and staff.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center h-100">
                    <form action="{{ route('admin.history.attendances.print') }}" method="POST" id="attendance-print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="role_id" value="{{ $filters['role_id'] ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="show_archived" value="{{ $showArchived ? 1 : 0 }}">
                        <button
                            type="submit"
                            class="btn btn-danger"
                            id="attendance-print-submit"
                            data-print='@json($printPayload)'
                            aria-label="Open printable/PDF view of filtered attendances"
                        >
                            <i class="fa-solid fa-print me-2"></i>Print
                            <span id="attendance-print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                    <a href="{{ route('admin.staff-account-management.attendances') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-clipboard-check me-2"></i>Live attendances
                    </a>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.attendances', array_merge(request()->except(['show_archived', 'page']), [])) }}"
                        >
                            <i class="fa-solid fa-rotate-left me-2"></i>Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.attendances', array_merge(request()->except(['page']), ['show_archived' => 1])) }}"
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
                                <h4 class="fw-semibold mb-1">Finished attendance logs</h4>
                                <p class="text-muted mb-0">Filter by role, status, or time window to find past check-ins.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Records</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['records'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Completed</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['completed'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">People</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['people'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                            @foreach ($statusLabels as $key => $label)
                                @php
                                    $count = $statusTallies[$key] ?? null;
                                @endphp
                                <button
                                    type="button"
                                    class="btn btn-sm rounded-pill px-3 attendance-status-chip {{ $activeStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                    data-status="{{ $key }}"
                                    aria-label="Filter attendances by {{ strtolower($label['label']) }}"
                                >
                                    {{ $label['label'] }}
                                    @if(!is_null($count))
                                        <span class="badge bg-transparent {{ $activeStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $count }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <form action="{{ route('admin.history.attendances') }}" method="GET" id="attendance-filter-form" class="mt-3">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif
                            <input type="hidden" name="status" id="attendance-status-filter" value="{{ $activeStatus }}">
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
                                            placeholder="Search name, email, or phone"
                                            aria-label="Search attendance records"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasFilters)
                                        <a href="{{ route('admin.history.attendances', $showArchived ? ['show_archived' => 1] : []) }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#attendanceFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="attendanceFiltersModal" tabindex="-1" aria-labelledby="attendanceFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="attendanceFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill attendance-range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill attendance-range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill attendance-range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label for="role_id" class="form-label text-muted text-uppercase small mb-1">Role</label>
                                                    <select id="role_id" name="role_id" class="form-select rounded-3">
                                                        <option value="">All roles</option>
                                                        @foreach ($roleOptions as $role)
                                                            <option
                                                                value="{{ $role->id }}"
                                                                {{ (string) ($filters['role_id'] ?? '') === (string) $role->id ? 'selected' : '' }}
                                                            >
                                                                {{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="status" class="form-label text-muted text-uppercase small mb-1">Status</label>
                                                    <select id="status" name="status_display" class="form-select rounded-3">
                                                        <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>All</option>
                                                        <option value="completed" {{ $activeStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                                                        <option value="open" {{ $activeStatus === 'open' ? 'selected' : '' }}>Open</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Clock-in range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="attendance-start-date" class="form-label small text-muted mb-1">From</label>
                                                            <input
                                                                type="date"
                                                                id="attendance-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ $filters['start_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="attendance-end-date" class="form-label small text-muted mb-1">To</label>
                                                            <input
                                                                type="date"
                                                                id="attendance-end-date"
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
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Contact</th>
                                        <th>Clock-in</th>
                                        <th>Clock-out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Archive</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attendances as $index => $attendance)
                                        @php
                                            $person = $attendance->user;
                                            $roleName = $person && $person->role ? $person->role->name : 'Unknown';
                                            $clockIn = $attendance->clockin_at ? \Carbon\Carbon::parse($attendance->clockin_at) : null;
                                            $clockOut = $attendance->clockout_at ? \Carbon\Carbon::parse($attendance->clockout_at) : null;
                                            $durationMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : null;
                                            $durationText = $durationMinutes !== null
                                                ? sprintf('%dh %02dm', intdiv($durationMinutes, 60), $durationMinutes % 60)
                                                : '—';
                                            $statusValue = $attendance->clockout_at ? 'completed' : 'open';
                                            $statusMeta = [
                                                'open' => ['label' => 'Open', 'class' => 'bg-warning text-dark'],
                                                'completed' => ['label' => 'Completed', 'class' => 'bg-success'],
                                            ][$statusValue];
                                            $archiveLabel = (int) ($attendance->is_archive ?? 0) === 1 ? 'Archived' : 'Active';
                                        @endphp
                                        <tr>
                                            <td>{{ $attendance->id ?? '—' }}</td>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : 'Unknown user' }}
                                                </div>
                                                <div class="text-muted small">ID: {{ $person->id ?? '—' }}</div>
                                            </td>
                                            <td>{{ $roleName }}</td>
                                            <td>
                                                <div>{{ $person->email ?? '—' }}</div>
                                                <div class="text-muted small">{{ $person->phone_number ?? '—' }}</div>
                                            </td>
                                            <td>
                                                @if($clockIn)
                                                    {{ $clockIn->format('M d, Y g:i A') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clockOut)
                                                    {{ $clockOut->format('M d, Y g:i A') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $durationText }}</td>
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
                                                    @if($person)
                                                        <a
                                                            href="{{ route('admin.users.view', $person->id) }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                        >
                                                            User
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                No attendance records found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $attendances->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('attendance-print-submit');
            const printForm = document.getElementById('attendance-print-form');
            const printLoader = document.getElementById('attendance-print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) chips.push(`Search: ${filters.search}`);
                if (filters.role_id) chips.push(`Role ID: ${filters.role_id}`);
                if (filters.status && filters.status !== 'all') chips.push(`Status: ${filters.status}`);
                if (filters.archived) chips.push('Archived records');
                if (filters.start || filters.end) chips.push(`Clock-in: ${filters.start || '—'} → ${filters.end || '—'}`);
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildRows(items) {
                return (items || []).map((item) => {
                    const role = item.role ? `<div class="muted">${item.role}</div>` : '';
                    const phone = item.phone ? `<div class="muted">${item.phone}</div>` : '';

                    return `
                        <tr>
                            <td>${item.id ?? '—'}</td>
                            <td>
                                <div class="fw">${item.name || '—'}</div>
                                ${role}
                            </td>
                            <td>
                                <div>${item.email || '—'}</div>
                                ${phone}
                            </td>
                            <td>${item.clock_in || '—'}</td>
                            <td>${item.clock_out || '—'}</td>
                            <td>${item.duration || '—'}</td>
                            <td>${item.status || '—'}</td>
                            <td>${item.archive || '—'}</td>
                        </tr>
                    `;
                }).join('');
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = payload.filters || {};
                const rows = buildRows(items);
                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>${payload.title || 'Attendance history'}</title>
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
                                .fw { font-weight: 700; }
                            </style>
                        </head>
                        <body>
                            <div class="sheet">
                                <div class="header">
                                    <div>
                                        <h1 class="title">${payload.title || 'Attendance history'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                        <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Clock-in</th>
                                            <th>Clock-out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Archive</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="8" style="text-align:center; padding:16px;">No attendances available for this view.</td></tr>'}
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
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('attendance-filter-form');
            if (!form) {
                return;
            }

            const rangeButtons = form.querySelectorAll('.attendance-range-chip');
            const startInput = document.getElementById('attendance-start-date');
            const endInput = document.getElementById('attendance-end-date');
            const statusInput = document.getElementById('attendance-status-filter');
            const statusSelect = document.getElementById('status');
            const statusButtons = form.querySelectorAll('.attendance-status-chip');

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
            }

            function setActiveStatus(status) {
                if (statusInput) statusInput.value = status;
                if (statusSelect) statusSelect.value = status;

                statusButtons.forEach((btn) => {
                    const isActive = btn.dataset.status === status;
                    btn.classList.toggle('btn-danger', isActive);
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

            // Ensure the chips reflect the active status on load
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
