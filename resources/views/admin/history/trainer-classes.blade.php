@extends('layouts.admin')
@section('title', 'Trainer Class History')

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
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['trainer_id'] ?? null) || ($activeStatus !== 'all') || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null);
            $advancedFiltersOpen = ($filters['trainer_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'all';

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
                    'enrollments' => $class->user_schedules_count ?? 0,
                    'rate' => $class->trainer_rate_per_hour !== null ? number_format((float) $class->trainer_rate_per_hour, 2) : null,
                    'start' => $start ? $start->format('M d, Y g:i A') : null,
                    'end' => $end ? $end->format('M d, Y g:i A') : null,
                    'status' => $statusMeta[$class->isadminapproved] ?? 'Pending',
                    'archive' => (int) ($class->is_archieve ?? 0) === 1 ? 'Archived' : 'Active',
                ];
            })->values();

            $printPayload = [
                'title' => 'Trainer class history',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'trainer_id' => $filters['trainer_id'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                ],
                'count' => $printItems->count(),
                'items' => $printItems,
            ];
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Trainer Class History</h2>
                    <p class="text-muted mb-0 small">Review completed classes, who led them, and how many members joined.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center h-100">
                    <form action="{{ route('admin.history.trainer-classes.print') }}" method="POST" id="trainer-class-print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="trainer_id" value="{{ $filters['trainer_id'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <button
                            type="submit"
                            class="btn btn-danger"
                            id="trainer-class-print-submit"
                            data-print='@json($printPayload)'
                            aria-label="Open printable/PDF view of filtered trainer classes"
                        >
                            <i class="fa-solid fa-print me-2"></i>Print
                            <span id="trainer-class-print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-calendar-days me-2"></i>View classes
                    </a>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">History</span>
                                <h4 class="fw-semibold mb-1">Completed trainer-led classes</h4>
                                <p class="text-muted mb-0">Filter by trainer, admin status, or completion window to find past sessions.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Classes</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['classes'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Trainers</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['trainers'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Enrollments</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['enrollments'] ?? 0) }}</div>
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
                                    class="btn btn-sm rounded-pill px-3 trainer-class-status-chip {{ $activeStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                    data-status="{{ $key }}"
                                    aria-label="Filter trainer classes by {{ strtolower($label['label']) }}"
                                >
                                    {{ $label['label'] }}
                                    @if(!is_null($count))
                                        <span class="badge bg-transparent {{ $activeStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $count }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <form action="{{ route('admin.history.trainer-classes') }}" method="GET" id="trainer-class-filter-form" class="mt-3">
                            <input type="hidden" name="status" id="trainer-class-status-filter" value="{{ $activeStatus }}">
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
                                            aria-label="Search trainer-led classes"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasFilters)
                                        <a href="{{ route('admin.history.trainer-classes') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#trainerClassFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="trainerClassFiltersModal" tabindex="-1" aria-labelledby="trainerClassFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="trainerClassFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill trainer-class-range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill trainer-class-range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill trainer-class-range-chip" data-range="last-year">Last year</button>
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
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Ended range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="trainer-class-start-date" class="form-label small text-muted mb-1">From</label>
                                                            <input
                                                                type="date"
                                                                id="trainer-class-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ $filters['start_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="trainer-class-end-date" class="form-label small text-muted mb-1">To</label>
                                                            <input
                                                                type="date"
                                                                id="trainer-class-end-date"
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
                                        <th>Members</th>
                                        <th>Trainer Rate</th>
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
                                            <td>{{ $class->user_schedules_count ?? 0 }}</td>
                                            <td>
                                                @if(!is_null($class->trainer_rate_per_hour))
                                                    PHP {{ number_format((float) $class->trainer_rate_per_hour, 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($start || $end)
                                                    <div>{{ $start ? $start->format('M d, Y g:i A') : '—' }}</div>
                                                    <div class="text-muted small">to {{ $end ? $end->format('M d, Y g:i A') : '—' }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">{{ $statusMeta['label'] }}</span>
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
                                                        Class
                                                    </a>
                                                    @if($trainer)
                                                        <a
                                                            href="{{ route('admin.trainer-management.view', $trainer->id) }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                        >
                                                            Trainer
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                No completed trainer classes found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $classes->firstItem() ?? 0 }} to {{ $classes->lastItem() ?? 0 }} of {{ $classes->total() }} records
                        </div>
                        <div class="ms-auto">
                            {{ $classes->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('trainer-class-print-submit');
            const printForm = document.getElementById('trainer-class-print-form');
            const printLoader = document.getElementById('trainer-class-print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) chips.push(`Search: ${filters.search}`);
                if (filters.trainer_id) chips.push(`Trainer ID: ${filters.trainer_id}`);
                if (filters.status && filters.status !== 'all') chips.push(`Status: ${filters.status}`);
                if (filters.start || filters.end) chips.push(`Ended: ${filters.start || '—'} → ${filters.end || '—'}`);
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildRows(items) {
                return (items || []).map((item) => {
                    const trainer = item.trainer ? `<div class="muted">${item.trainer}</div>` : '';
                    const rate = item.rate ? `PHP ${item.rate}` : '—';

                    return `
                        <tr>
                            <td>${item.id ?? '—'}</td>
                            <td>
                                <div class="fw">${item.name || '—'}</div>
                                <div class="muted">${item.code || ''}</div>
                            </td>
                            <td>${trainer || 'Not assigned'}</td>
                            <td>${item.enrollments ?? 0}</td>
                            <td>${rate}</td>
                            <td>
                                <div>${item.start || '—'}</div>
                                <div class="muted">${item.end ? 'to ' + item.end : ''}</div>
                            </td>
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
                            <title>${payload.title || 'Trainer class history'}</title>
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
                                        <h1 class="title">${payload.title || 'Trainer class history'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                        <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Class</th>
                                            <th>Trainer</th>
                                            <th>Enrollments</th>
                                            <th>Rate/hr</th>
                                            <th>Class Window</th>
                                            <th>Status</th>
                                            <th>Archive</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="8" style="text-align:center; padding:16px;">No trainer classes available for this view.</td></tr>'}
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
            const form = document.getElementById('trainer-class-filter-form');
            if (!form) {
                return;
            }

            const rangeButtons = form.querySelectorAll('.trainer-class-range-chip');
            const startInput = document.getElementById('trainer-class-start-date');
            const endInput = document.getElementById('trainer-class-end-date');
            const statusInput = document.getElementById('trainer-class-status-filter');
            const statusSelect = document.getElementById('status');
            const statusButtons = form.querySelectorAll('.trainer-class-status-chip');

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
