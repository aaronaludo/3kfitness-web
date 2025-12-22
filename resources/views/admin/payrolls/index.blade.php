@extends('layouts.admin')
@section('title', 'Payroll History')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $searchTerm = request('member_name');
                $searchColumn = request('search_column');
                $periodMonth = request('period_month');
                $processedFrom = request('processed_from');
                $processedTo = request('processed_to');
                $printSource = $runs;
                $printAllSource = $printAllRuns ?? collect();
                $mapRun = function ($run) {
                    $staff = $run->user;
                    $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                    $email = optional($staff)->email ?? '—';
                    $userCode = optional($staff)->user_code;
                    $periodLabel = $run->period_month ?? '—';
                    $processedAt = $run->processed_at
                        ? $run->processed_at->format('M d, Y g:i A')
                        : ($run->created_at?->format('M d, Y g:i A') ?? '—');
                    $processedSessions = collect($run->processed_session_series ?? []);
                    $processedSessionCount = $processedSessions->sum(function ($item) {
                        return collect($item['sessions'] ?? [])->count();
                    });

                    return [
                        'id' => $run->id,
                        'name' => $name !== '' ? $name : '—',
                        'email' => $email,
                        'user_code' => $userCode,
                        'period' => $periodLabel,
                        'hours' => number_format((float) ($run->total_hours ?? 0), 2),
                        'gross' => number_format((float) ($run->gross_pay ?? 0), 2),
                        'net' => number_format((float) ($run->net_pay ?? 0), 2),
                        'processed_at' => $processedAt,
                        'processed_sessions' => $processedSessionCount,
                    ];
                };

                $printRuns = collect($printSource->items() ?? [])->map($mapRun)->values();
                $printAllRuns = collect($printAllSource ?? [])->map($mapRun)->values();

                $printPayload = [
                    'title' => 'Payroll history',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'member_name' => $searchTerm,
                        'search_column' => $searchColumn,
                        'period_month' => $periodMonth,
                        'processed_from' => $processedFrom,
                        'processed_to' => $processedTo,
                    ],
                    'count' => $printRuns->count(),
                    'items' => $printRuns,
                ];

                $printAllPayload = [
                    'title' => 'Payroll history (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'member_name' => $searchTerm,
                        'search_column' => $searchColumn,
                        'period_month' => $periodMonth,
                        'processed_from' => $processedFrom,
                        'processed_to' => $processedTo,
                        'scope' => 'all',
                    ],
                    'count' => $printAllRuns->count(),
                    'items' => $printAllRuns,
                ];
            @endphp

            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payroll History</h2>
                    <p class="text-muted mb-0">Review processed payroll runs for staff.</p>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <a
                        href="{{ route('admin.payrolls.process') }}"
                        class="btn btn-primary d-flex align-items-center gap-2"
                    >
                        <i class="fa-solid fa-gears"></i>
                        Process payroll
                    </a>
                    <form action="#" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="member_name" value="{{ $searchTerm }}">
                        <input type="hidden" name="search_column" value="{{ $searchColumn }}">
                        <input type="hidden" name="period_month" value="{{ $periodMonth }}">
                        <input type="hidden" name="processed_from" value="{{ $processedFrom }}">
                        <input type="hidden" name="processed_to" value="{{ $processedTo }}">
                        <button
                            type="submit"
                            class="btn btn-danger d-flex align-items-center gap-2"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered payrolls"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                                <h4 class="fw-semibold mb-1">Find a payroll run</h4>
                                <p class="text-muted mb-0">Search by staff, period month, or processed date window to locate completed runs.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $runs->total() }} results</span>
                                <span class="d-block text-muted small">Page {{ $runs->currentPage() }} of {{ $runs->lastPage() }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.payrolls.index') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label text-muted small mb-1" for="member_name">Staff</label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input
                                        type="search"
                                        class="form-control rounded-pill ps-5"
                                        name="member_name"
                                        id="member_name"
                                        placeholder="Search staff or payroll"
                                        value="{{ $searchTerm }}"
                                        aria-label="Search staff or payroll"
                                    />
                                </div>
                            </div>

                            <div class="col-12 col-md-4 col-lg-3">
                                <label class="form-label text-muted small mb-1" for="search_column">Search by</label>
                                <select
                                    class="form-select rounded-3"
                                    name="search_column"
                                    id="search_column"
                                    aria-label="Choose which payroll field to search"
                                >
                                    <option value="" disabled {{ $searchColumn ? '' : 'selected' }}>Select option</option>
                                    <option value="id" {{ $searchColumn === 'id' ? 'selected' : '' }}>#</option>
                                    <option value="name" {{ $searchColumn === 'name' ? 'selected' : '' }}>Name</option>
                                    <option value="email" {{ $searchColumn === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="user_code" {{ $searchColumn === 'user_code' ? 'selected' : '' }}>User Code</option>
                                    <option value="period_month" {{ $searchColumn === 'period_month' ? 'selected' : '' }}>Period Month</option>
                                    <option value="processed_at" {{ $searchColumn === 'processed_at' ? 'selected' : '' }}>Processed Date</option>
                                    <option value="created_at" {{ $searchColumn === 'created_at' ? 'selected' : '' }}>Created Date</option>
                                    <option value="updated_at" {{ $searchColumn === 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4 col-lg-3">
                                <label class="form-label text-muted small mb-1" for="period_month">Period month</label>
                                <input
                                    type="month"
                                    class="form-control rounded-pill"
                                    name="period_month"
                                    id="period_month"
                                    value="{{ $periodMonth }}"
                                    aria-label="Filter by payroll month"
                                />
                            </div>

                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label text-muted small mb-1" for="processed_from">Processed date</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input
                                            type="date"
                                            class="form-control rounded-3"
                                            name="processed_from"
                                            id="processed_from"
                                            value="{{ $processedFrom }}"
                                            aria-label="Filter payrolls processed from date"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <input
                                            type="date"
                                            class="form-control rounded-3"
                                            name="processed_to"
                                            id="processed_to"
                                            value="{{ $processedTo }}"
                                            aria-label="Filter payrolls processed to date"
                                        />
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">Matches processed date, falls back to created date if missing.</small>
                            </div>

                            <div class="col-12 col-md-4 col-lg-2 d-flex gap-2 justify-content-md-end">
                                <a href="{{ route('admin.payrolls.index') }}" class="btn btn-link text-decoration-none text-muted px-0">
                                    Reset
                                </a>
                                <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    Apply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Staff</th>
                                        <th scope="col">User Code</th>
                                        <th scope="col">Period</th>
                                        <th scope="col">Hours</th>
                                        <th scope="col">Gross</th>
                                        <th scope="col">Net</th>
                                        <th scope="col">Processed Date</th>
                                        <th scope="col">Processed Sessions</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($runs as $run)
                                        @php
                                            $staff = $run->user;
                                            $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                                            $periodLabel = $run->period_month ?? '—';
                                            $processedAt = $run->processed_at
                                                ? $run->processed_at->format('M d, Y g:i A')
                                                : ($run->created_at?->format('M d, Y g:i A') ?? '—');
                                            $rate = ($run->total_hours ?? 0) > 0
                                                ? round((float) $run->gross_pay / max((float) $run->total_hours, 0.01), 2)
                                                : null;
                                            $isTrainer = optional($staff)->role_id === 5;
                                            $payslipDetail = $payslipDetails[$run->id] ?? ['entries' => [], 'assignments' => []];
                                            $processedSeries = collect($run->processed_session_series ?? []);
                                            $processedSessionCount = $processedSeries->sum(function ($item) {
                                                return collect($item['sessions'] ?? [])->count();
                                            });
                                            $payslipData = [
                                                'type' => $isTrainer ? 'trainer' : 'staff',
                                                'name' => $name,
                                                'email' => optional($staff)->email ?? '—',
                                                'month' => $periodLabel,
                                                'gross' => (float) ($run->gross_pay ?? 0),
                                                'net' => (float) ($run->net_pay ?? 0),
                                                'rate' => $rate,
                                                'hours' => (float) ($run->total_hours ?? 0),
                                                'deductions' => [
                                                    'sss' => (float) ($run->deduction_sss ?? 0),
                                                    'philhealth' => (float) ($run->deduction_philhealth ?? 0),
                                                    'pagibig' => (float) ($run->deduction_pagibig ?? 0),
                                                    'app_cut' => (float) ($run->deduction_app_cut ?? 0),
                                                ],
                                                'entries' => $payslipDetail['entries'] ?? [],
                                                'assignments' => $payslipDetail['assignments'] ?? [],
                                            ];
                                        @endphp
                                        <tr>
                                            <td>{{ $run->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $name }}</div>
                                                <span class="text-muted small">{{ optional($staff)->email ?? '—' }}</span>
                                            </td>
                                            <td><span class="text-muted small">{{ optional($staff)->user_code ?? '—' }}</span></td>
                                            <td>{{ $periodLabel }}</td>
                                            <td><span class="fw-semibold">{{ number_format((float) ($run->total_hours ?? 0), 2) }}</span> hrs</td>
                                            <td>₱{{ number_format((float) ($run->gross_pay ?? 0), 2) }}</td>
                                            <td class="text-success fw-semibold">₱{{ number_format((float) ($run->net_pay ?? 0), 2) }}</td>
                                            <td>{{ $processedAt }}</td>
                                            <td>
                                                @if($processedSessionCount > 0)
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-sm processed-series-btn"
                                                        data-series='@json($processedSeries->values())'
                                                        data-run-name="{{ $name }}"
                                                        data-period="{{ $periodLabel }}"
                                                    >
                                                        View ({{ $processedSessionCount }})
                                                    </button>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($staff)
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm payslip-btn"
                                                        data-payslip='@json($payslipData)'
                                                    >
                                                        <i class="fa-solid fa-file-pdf me-1"></i>
                                                        Print payslip
                                                    </button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                No payroll runs found. Adjust your filters or check back later.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $runs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="processedSeriesModal" tabindex="-1" aria-labelledby="processedSeriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="processedSeriesModalLabel">Processed sessions</h5>
                        <span class="text-muted small" id="processedSeriesSubtitle"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="processed-series-body" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('print-submit-button');
            const printForm = document.getElementById('print-form');
            const printLoader = document.getElementById('print-loader');

            function buildFilters(filters) {
                const formatDate = (value) => {
                    if (!value) return null;
                    const parsed = new Date(value);
                    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
                };
                const chips = [];
                if (filters.member_name) {
                    chips.push({
                        label: 'Search',
                        value: `${filters.member_name}${filters.search_column ? ` (${filters.search_column})` : ''}`,
                    });
                }
                if (filters.period_month) chips.push({ label: 'Period', value: filters.period_month });
                if (filters.processed_from || filters.processed_to) {
                    const from = formatDate(filters.processed_from) || 'Any';
                    const to = formatDate(filters.processed_to) || 'Any';
                    chips.push({ label: 'Processed', value: `${from} → ${to}` });
                }
                return chips;
            }

            function buildRows(items) {
                return (items || []).map((item) => ([
                    item.id ?? '—',
                    `<div class="fw">${item.name || '—'}</div><div class="muted">${item.email || ''}</div>`,
                    item.user_code || '—',
                    item.period || '—',
                    `${item.hours || '0.00'} hrs`,
                    `₱${item.gross || '0.00'}`,
                    `<span class="text-success fw-semibold">₱${item.net || '0.00'}</span>`,
                    item.processed_at || '—',
                    (item.processed_sessions ?? 0).toString(),
                ]));
            }

            function renderPrintWindow(payload) {
                const rawItems = payload && payload.items ? payload.items : [];
                const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                const filters = buildFilters(payload.filters || {});
                const headers = ['#', 'Staff', 'User Code', 'Period', 'Hours', 'Gross', 'Net', 'Processed', 'Processed Sessions'];
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
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                        return;
                    }

                    printButton.disabled = false;
                    if (printLoader) printLoader.classList.add('d-none');
                });
            }

            // Payslip preview/print (matches process page)
            const payslipButtons = document.querySelectorAll('.payslip-btn');
            payslipButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    let data = {};
                    try {
                        data = JSON.parse(btn.dataset.payslip || '{}');
                    } catch (e) {
                        console.error('Invalid payslip data', e);
                        return;
                    }

                    const entries = Array.isArray(data.entries) ? data.entries : [];
                    const assignments = Array.isArray(data.assignments) ? data.assignments : [];
                    const isTrainer = data.type === 'trainer';
                    const style = `
                        <style>
                            body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111827; }
                            .payslip { max-width: 800px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 24px; border-radius: 12px; }
                            .header { text-align: center; margin-bottom: 24px; }
                            .header h1 { margin: 0 0 8px; }
                            .muted { color: #6b7280; font-size: 13px; }
                            .section { margin-bottom: 20px; }
                            .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
                            th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
                            th { background: #f3f4f6; }
                            .totals { background: #fef2f2; }
                            .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; }
                            .badge-success { background: #dcfce7; color: #166534; }
                            .badge-warning { background: #fef9c3; color: #854d0e; }
                        </style>
                    `;

                    const rows = entries.map((entry) => {
                        const status = entry.status === 'complete'
                            ? '<span class="badge badge-success">Complete</span>'
                            : '<span class="badge badge-warning">Pending</span>';

                        return `
                            <tr>
                                <td>#${entry.id ?? '—'}</td>
                                <td>${entry.clockin ?? '—'}</td>
                                <td>${entry.clockout ?? '—'}</td>
                                <td>${Number(entry.hours || 0).toFixed(2)} hrs</td>
                                <td>₱${Number(entry.amount || 0).toFixed(2)}</td>
                                <td>${status}</td>
                            </tr>
                        `;
                    }).join('');
                    const assignmentRows = assignments.map((assignment) => `
                            <tr>
                                <td>
                                    ${assignment.title || '—'}
                                    ${assignment.code ? `<div class="muted">${assignment.code}</div>` : ''}
                                    ${assignment.recurrence ? `<div class="muted">Recurring: ${assignment.recurrence}</div>` : ''}
                                </td>
                                <td>${assignment.date || '—'}</td>
                                <td>${assignment.time || '—'}</td>
                                <td>
                                    ${Array.isArray(assignment.attendance) && assignment.attendance.length
                                        ? assignment.attendance.map((slot) => `<div>${slot}</div>`).join('')
                                        : '<span class="muted">No attendance</span>'}
                                </td>
                                <td>${Number(assignment.hours || 0).toFixed(2)} hrs</td>
                                <td>₱${Number(assignment.salary || 0).toFixed(2)}</td>
                            </tr>
                        `).join('');
                    const infoFields = [
                        `<div><strong>${isTrainer ? 'Trainer' : 'Employee'}:</strong> ${data.name || '—'}</div>`,
                        `<div><strong>Email:</strong> ${data.email || '—'}</div>`,
                        `<div><strong>Period:</strong> ${data.month || '—'}</div>`,
                    ];
                    if (!isTrainer) {
                        infoFields.push(`<div><strong>Hourly rate:</strong> ₱${Number(data.rate || 0).toFixed(2)}</div>`);
                        infoFields.push(`<div><strong>Total hours:</strong> ${Number(data.hours || 0).toFixed(2)} hrs</div>`);
                    }

                    const detailSection = isTrainer
                        ? `
                            <div class="section">
                                <strong>Assignments with attendance</strong>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Class/Schedule</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Attendance</th>
                                            <th>Hours</th>
                                            <th>Pay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${assignmentRows || '<tr><td colspan="6" style="text-align:center;">No assignments with attendance for this period.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        `
                        : `
                            <div class="section">
                                <strong>Entries</strong>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Entry</th>
                                            <th>Clock in</th>
                                            <th>Clock out</th>
                                            <th>Hours</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="6" style="text-align:center;">No entries</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        `;

                    const html = `
                        <!doctype html>
                        <html>
                            <head>
                                <title>Payslip - ${data.name || ''}</title>
                                ${style}
                            </head>
                            <body>
                                <div class="payslip">
                                    <div class="header">
                                        <h1>Payroll Payslip</h1>
                                        <div class="muted">3kfitness Gym • ${data.month || ''}</div>
                                    </div>
                                    <div class="section grid">
                                        ${infoFields.join('')}
                                    </div>
                                    ${detailSection}
                                    <div class="section">
                                        <strong>Summary</strong>
                                        <table class="totals">
                                            <tbody>
                                                <tr><td>Gross pay</td><td>₱${Number(data.gross || 0).toFixed(2)}</td></tr>
                                                <tr><td>SSS</td><td>₱${Number(data.deductions?.sss || 0).toFixed(2)}</td></tr>
                                                <tr><td>PhilHealth</td><td>₱${Number(data.deductions?.philhealth || 0).toFixed(2)}</td></tr>
                                                <tr><td>Pag-IBIG</td><td>₱${Number(data.deductions?.pagibig || 0).toFixed(2)}</td></tr>
                                                <tr><td>3kfitness app cut</td><td>₱${Number(data.deductions?.app_cut || 0).toFixed(2)}</td></tr>
                                                <tr><th>Net pay</th><th>₱${Number(data.net || 0).toFixed(2)}</th></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <script>window.print();<\/script>
                            </body>
                        </html>
                    `;

                    const printWindow = window.open('', '_blank', 'width=900,height=1200');
                    if (!printWindow) return;
                    printWindow.document.open();
                    printWindow.document.write(html);
                    printWindow.document.close();
                });
            });

            const processedSeriesModal = document.getElementById('processedSeriesModal');
            const processedSeriesBody = document.getElementById('processed-series-body');
            const processedSeriesSubtitle = document.getElementById('processedSeriesSubtitle');
            const processedSeriesTitle = document.getElementById('processedSeriesModalLabel');

            function renderProcessedSeries(series, runName, period) {
                if (processedSeriesSubtitle) {
                    processedSeriesSubtitle.textContent = `${runName || '—'} • ${period || '—'}`;
                }
                if (processedSeriesBody) {
                    processedSeriesBody.innerHTML = '';
                    if (!series.length) {
                        const empty = document.createElement('div');
                        empty.className = 'text-muted text-center';
                        empty.textContent = 'No processed sessions recorded for this run.';
                        processedSeriesBody.appendChild(empty);
                        return;
                    }

                    series.forEach((item) => {
                        const sessions = Array.isArray(item.sessions) ? item.sessions : [];
                        const sessionList = sessions.length
                            ? sessions.map((session) => {
                                const badge = document.createElement('span');
                                badge.className = 'badge bg-success-subtle text-success border border-success-subtle';
                                badge.textContent = session.status || 'Completed';

                                const row = document.createElement('div');
                                row.className = 'd-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-1';
                                row.innerHTML = `
                                    <div>
                                        <div class="fw-semibold">${session.label || session.date || '—'}</div>
                                        ${session.day ? `<div class="text-muted small">Day ${session.day}</div>` : ''}
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        ${badge.outerHTML}
                                    </div>
                                `;
                                return row.outerHTML;
                            }).join('')
                            : '<div class="text-muted small">No session dates saved.</div>';

                        const card = document.createElement('div');
                        card.className = 'border rounded-4 p-3';
                        card.innerHTML = `
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <div class="fw-semibold">${item.schedule_name || 'Class schedule'}</div>
                                    ${item.class_code ? `<div class="text-muted small">Code: ${item.class_code}</div>` : ''}
                                    ${item.time_range ? `<div class="text-muted small">Time: ${item.time_range}</div>` : ''}
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">₱${Number(item.payroll_salary || 0).toFixed(2)}</div>
                                    <div class="text-muted small">${Number(item.payroll_hours || 0).toFixed(2)} hrs</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                ${sessionList}
                            </div>
                        `;
                        processedSeriesBody.appendChild(card);
                    });
                }

                if (processedSeriesModal && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(processedSeriesModal);
                    modal.show();
                }
            }

            document.querySelectorAll('.processed-series-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    let series = [];
                    try {
                        series = JSON.parse(btn.dataset.series || '[]');
                    } catch (e) {
                        series = [];
                    }
                    const runName = btn.dataset.runName || '';
                    const period = btn.dataset.period || '';
                    if (processedSeriesTitle) {
                        processedSeriesTitle.textContent = 'Processed sessions';
                    }
                    renderProcessedSeries(Array.isArray(series) ? series : [], runName, period);
                });
            });
        });
    </script>
@endsection
