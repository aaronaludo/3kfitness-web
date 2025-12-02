@extends('layouts.admin')
@section('title', 'Enrollment History')

@section('content')
    <div class="container-fluid">
        @php
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['class_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null);
            $advancedFiltersOpen = ($filters['class_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null);

            $printItems = collect($enrollments->items())->map(function ($enrollment) {
                $member = $enrollment->user;
                $class = $enrollment->schedule;
                $trainer = optional($class)->user;

                $start = $class && $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                $end = $class && $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                $joinedAt = $enrollment->created_at ? $enrollment->created_at->format('M d, Y g:i A') : null;
                $memberName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : 'Unknown member';

                return [
                    'id' => $class ? $class->id : ($enrollment->schedule_id ?? null),
                    'member' => $memberName,
                    'role' => $member && $member->role ? ($member->role->name ?? null) : null,
                    'email' => $member->email ?? null,
                    'phone' => $member->phone_number ?? null,
                    'class_name' => $class->name ?? null,
                    'class_code' => $class->class_code ?? null,
                    'trainer' => $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : 'Not assigned',
                    'joined' => $joinedAt,
                    'start' => $start ? $start->format('M d, Y g:i A') : null,
                    'end' => $end ? $end->format('M d, Y g:i A') : null,
                ];
            })->values();

            $printPayload = [
                'title' => 'Enrollment history',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'class_id' => $filters['class_id'] ?? null,
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
                    <h2 class="title mb-1">Enrollment History</h2>
                    <p class="text-muted mb-0 small">Past member enrollments for classes that have already finished.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center h-100">
                    <form action="{{ route('admin.history.class-enrollments.print') }}" method="POST" id="enrollment-print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="class_id" value="{{ $filters['class_id'] ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <button
                            type="submit"
                            class="btn btn-danger"
                            id="enrollment-print-submit"
                            data-print='@json($printPayload)'
                            aria-label="Open printable/PDF view of filtered enrollments"
                        >
                            <i class="fa-solid fa-print me-2"></i>Print
                            <span id="enrollment-print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
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
                                <h4 class="fw-semibold mb-1">Completed class enrollments</h4>
                                <p class="text-muted mb-0">Search members who joined completed classes, filter by class, and review when they enrolled.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Enrollments</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['total'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Unique members</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['members'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Classes</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['classes'] ?? 0) }}</div>
                                </div>
                                </div>
                            </div>

                        <form action="{{ route('admin.history.class-enrollments') }}" method="GET" id="enrollment-filter-form" class="mt-3">
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
                                            placeholder="Search member, class, or code"
                                            aria-label="Search member, class, or code"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasFilters)
                                        <a href="{{ route('admin.history.class-enrollments') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#enrollmentFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="enrollmentFiltersModal" tabindex="-1" aria-labelledby="enrollmentFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="enrollmentFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill enrollment-range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill enrollment-range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill enrollment-range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label for="class_id" class="form-label text-muted text-uppercase small mb-1">Class</label>
                                                    <select id="class_id" name="class_id" class="form-select rounded-3">
                                                        <option value="">All completed classes</option>
                                                        @foreach ($classOptions as $class)
                                                            <option
                                                                value="{{ $class->id }}"
                                                                {{ (string) ($filters['class_id'] ?? '') === (string) $class->id ? 'selected' : '' }}
                                                            >
                                                                {{ $class->name }} ({{ $class->class_code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Joined range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="enroll-start-date" class="form-label small text-muted mb-1">From</label>
                                                            <input
                                                                type="date"
                                                                id="enroll-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ $filters['start_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="enroll-end-date" class="form-label small text-muted mb-1">To</label>
                                                            <input
                                                                type="date"
                                                                id="enroll-end-date"
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
                                        <th>Member</th>
                                        <th>Contact</th>
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>Joined</th>
                                        <th>Class Window</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($enrollments as $index => $enrollment)
                                        @php
                                            $member = $enrollment->user;
                                            $class = $enrollment->schedule;
                                            $trainer = optional($class)->user;
                                            $start = $class && $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                                            $end = $class && $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                                            $joinedAt = $enrollment->created_at ? $enrollment->created_at->format('M d, Y g:i A') : '—';
                                            $fullName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '';
                                            $displayId = $class ? $class->id : ($enrollment->schedule_id ?? '—');
                                        @endphp
                                        <tr>
                                            <td>{{ $displayId }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $fullName !== '' ? $fullName : 'Unknown member' }}</div>
                                                @if($member && $member->role)
                                                    <div class="text-muted small">{{ $member->role->name ?? '' }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $member ? ($member->email ?? '—') : '—' }}</div>
                                                <div class="text-muted small">{{ $member ? ($member->phone_number ?? '—') : '—' }}</div>
                                            </td>
                                            <td>
                                                @if($class)
                                                    <div class="fw-semibold">{{ $class->name }}</div>
                                                    <div class="text-muted small">{{ $class->class_code }}</div>
                                                @else
                                                    <span class="text-muted">Class unavailable</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($trainer)
                                                    {{ trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) }}
                                                @else
                                                    <span class="text-muted">Not assigned</span>
                                                @endif
                                            </td>
                                            <td>{{ $joinedAt }}</td>
                                            <td>
                                                @if($start || $end)
                                                    <div>{{ $start ? $start->format('M d, Y g:i A') : '—' }}</div>
                                                    <div class="text-muted small">to {{ $end ? $end->format('M d, Y g:i A') : '—' }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    @if($member)
                                                        <a
                                                            href="{{ route('admin.gym-management.members.view', $member->id) }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                        >
                                                            Member
                                                        </a>
                                                    @endif
                                                    @if($class)
                                                        <a
                                                            href="{{ route('admin.gym-management.schedules.view', $class->id) }}"
                                                            class="btn btn-outline-primary btn-sm"
                                                        >
                                                            Class
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                No past enrollments found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $enrollments->firstItem() ?? 0 }} to {{ $enrollments->lastItem() ?? 0 }} of {{ $enrollments->total() }} enrollments
                        </div>
                        <div class="ms-auto">
                            {{ $enrollments->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('enrollment-print-submit');
            const printForm = document.getElementById('enrollment-print-form');
            const printLoader = document.getElementById('enrollment-print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) chips.push(`Search: ${filters.search}`);
                if (filters.class_id) chips.push(`Class ID: ${filters.class_id}`);
                if (filters.start || filters.end) chips.push(`Joined: ${filters.start || '—'} → ${filters.end || '—'}`);
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildRows(items) {
                return (items || []).map((item) => {
                    const role = item.role ? `<div class="muted">${item.role}</div>` : '';
                    const phone = item.phone ? `<div class="muted">${item.phone}</div>` : '';
                    const classCode = item.class_code ? `<div class="muted">${item.class_code}</div>` : '';
                    const range = item.start || item.end
                        ? `<div>${item.start || '—'}</div><div class="muted">${item.end ? 'to ' + item.end : ''}</div>`
                        : '<div class="muted">—</div>';

                    return `
                        <tr>
                            <td>${item.id ?? '—'}</td>
                            <td>
                                <div class="fw">${item.member || '—'}</div>
                                ${role}
                            </td>
                            <td>
                                <div>${item.email || '—'}</div>
                                ${phone}
                            </td>
                            <td>
                                <div class="fw">${item.class_name || '—'}</div>
                                ${classCode}
                            </td>
                            <td>${item.trainer || 'Not assigned'}</td>
                            <td>${item.joined || '—'}</td>
                            <td>${range}</td>
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
                            <title>${payload.title || 'Enrollment history'}</title>
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
                                        <h1 class="title">${payload.title || 'Enrollment history'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                        <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Member</th>
                                            <th>Contact</th>
                                            <th>Class</th>
                                            <th>Trainer</th>
                                            <th>Joined</th>
                                            <th>Class Window</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="7" style="text-align:center; padding:16px;">No enrollments available for this view.</td></tr>'}
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
            const form = document.getElementById('enrollment-filter-form');
            if (!form) {
                return;
            }

            const rangeButtons = form.querySelectorAll('.enrollment-range-chip');
            const startInput = document.getElementById('enroll-start-date');
            const endInput = document.getElementById('enroll-end-date');

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

            rangeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const range = this.dataset.range;
                    applyRange(range);
                });
            });
        });
    </script>
@endsection
