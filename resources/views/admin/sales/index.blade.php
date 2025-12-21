@extends('layouts.admin')
@section('title', 'Sales')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $filterStart = request('start_date', optional($start)->toDateString());
                $filterEnd = request('end_date', optional($end)->toDateString());
                $payrollTotals = $payrollSummary ?? [
                    'app_cut' => 0,
                    'trainer_net' => 0,
                    'staff_net' => 0,
                    'gross' => 0,
                    'net' => 0,
                    'run_count' => 0,
                    'period_label' => '',
                ];
                $payrollRuns = collect($payrollDetails ?? [])->map(function ($run) use ($currency) {
                    return [
                        'id' => $run['id'] ?? '—',
                        'name' => $run['name'] ?? '—',
                        'email' => $run['email'] ?? '—',
                        'user_code' => $run['user_code'] ?? '—',
                        'role' => $run['role'] ?? '—',
                        'period' => $run['period'] ?? '—',
                        'processed_at' => $run['processed_at'] ?? '—',
                        'net' => isset($run['net']) ? $currency . ' ' . $run['net'] : $currency . ' 0.00',
                    ];
                });
                $printPayload = [
                    'title' => 'Sales overview',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'start' => $filterStart,
                        'end' => $filterEnd,
                        'payroll_period' => $payrollTotals['period_label'] ?? null,
                    ],
                    'totals' => [
                        'sales' => $totalSales,
                        'status' => [
                            'approved' => $statusTallies['approved'] ?? 0,
                            'pending' => $statusTallies['pending'] ?? 0,
                            'rejected' => $statusTallies['rejected'] ?? 0,
                        ],
                    ],
                    'payroll' => [
                        'staff_net' => number_format((float) ($payrollTotals['staff_net'] ?? 0), 2),
                        'trainer_net' => number_format((float) ($payrollTotals['trainer_net'] ?? 0), 2),
                        'app_cut' => number_format((float) ($payrollTotals['app_cut'] ?? 0), 2),
                        'gross' => number_format((float) ($payrollTotals['gross'] ?? 0), 2),
                        'net' => number_format((float) ($payrollTotals['net'] ?? 0), 2),
                        'run_count' => $payrollTotals['run_count'] ?? 0,
                        'period' => $payrollTotals['period_label'] ?? '',
                    ],
                    'chart' => [
                        'labels' => $payrollLabels ?? [],
                        'staff' => $payrollStaffSeries ?? [],
                        'trainer' => $payrollTrainerSeries ?? [],
                        'app_cut' => $payrollAppCutSeries ?? [],
                    ],
                    'payroll_runs' => $payrollRuns,
                    'currency' => $currency,
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Sales</h2>
                    <p class="text-muted mb-0">Revenue from approved, non-archived membership payments.</p>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <div class="nav nav-pills">
                        <a class="nav-link active" aria-current="page" href="{{ route('admin.sales.index') }}">Sales Overview</a>
                        <a class="nav-link" href="{{ route('admin.staff-account-management.membership-payments') }}">Membership Payments</a>
                    </div>
                    <button
                        class="btn btn-danger d-flex align-items-center gap-2"
                        type="button"
                        id="print-submit-button"
                        data-print='@json($printPayload)'
                        aria-label="Open printable/PDF view of sales"
                    >
                        <i class="fa-solid fa-print"></i>
                        <span id="print-loader" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Print
                    </button>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <form action="{{ route('admin.sales.index') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-sm-4">
                                <label for="start-date" class="form-label small text-muted mb-1">Start date</label>
                                <input type="date" id="start-date" name="start_date" class="form-control" value="{{ request('start_date', optional($start)->toDateString()) }}" />
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="end-date" class="form-label small text-muted mb-1">End date</label>
                                <input type="date" id="end-date" name="end_date" class="form-control" value="{{ request('end_date', optional($end)->toDateString()) }}" />
                            </div>
                            <div class="col-12 col-sm-4 d-flex gap-2">
                                <button type="submit" class="btn btn-danger mt-auto"><i class="fa-solid fa-magnifying-glass me-2"></i>Apply</button>
                                <a href="{{ route('admin.sales.index') }}" class="btn btn-light mt-auto">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Finished payroll net</div>
                                <div class="h4 mb-0">{{ $currency }} {{ number_format((float) ($payrollTotals['net'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Total Sales</div>
                                <div class="h4 mb-0">{{ $totalSales }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small mb-2">New Memberships (period)</div>
                                <div class="d-flex gap-3">
                                    <span class="badge bg-success">Approved: {{ $statusTallies['approved'] ?? 0 }}</span>
                                    <span class="badge bg-warning text-dark">Pending: {{ $statusTallies['pending'] ?? 0 }}</span>
                                    <span class="badge bg-danger">Rejected: {{ $statusTallies['rejected'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Staff payroll (net)</div>
                                <div class="h5 mb-0">{{ $currency }} {{ number_format((float) ($payrollTotals['staff_net'] ?? 0), 2) }}</div>
                                <small class="text-muted">Period: {{ $payrollTotals['period_label'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">Trainer payroll (net)</div>
                                <div class="h5 mb-0">{{ $currency }} {{ number_format((float) ($payrollTotals['trainer_net'] ?? 0), 2) }}</div>
                                <small class="text-muted">Period: {{ $payrollTotals['period_label'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted small">3kfitness app cut</div>
                                <div class="h5 mb-0">{{ $currency }} {{ number_format((float) ($payrollTotals['app_cut'] ?? 0), 2) }}</div>
                                <small class="text-muted">Runs: {{ $payrollTotals['run_count'] ?? 0 }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-2">Payroll figures are pulled from processed runs dated within the selected window.</p>
            </div>

            <div class="col-12 mt-3">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Finished payrolls over time</div>
                                </div>
                                <canvas id="salesLine"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Finished payroll mix</div>
                                </div>
                                <canvas id="salesPie"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lineCtx = document.getElementById('salesLine');
            if (lineCtx) {
                const lineChart = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: @json($payrollLabels ?? []),
                        datasets: [
                            {
                                label: 'Staff payroll (net, {{ $currency }})',
                                data: @json($payrollStaffSeries ?? []),
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Trainer payroll (net, {{ $currency }})',
                                data: @json($payrollTrainerSeries ?? []),
                                borderColor: '#198754',
                                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: '3kfitness app cut',
                                data: @json($payrollAppCutSeries ?? []),
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                tension: 0.3,
                                fill: true,
                            },
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            const pieCtx = document.getElementById('salesPie');
            if (pieCtx) {
                const pieChart = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($pieLabels ?? []),
                        datasets: [{
                            label: 'Payroll mix ({{ $currency }})',
                            data: @json($pieValues ?? []),
                            backgroundColor: [
                                '#0d6efd',
                                '#198754',
                                '#dc3545',
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            const printButton = document.getElementById('print-submit-button');
            const printLoader = document.getElementById('print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.start || filters.end) {
                    chips.push(`Date: ${filters.start || '—'} → ${filters.end || '—'}`);
                }
                if (filters.payroll_period) {
                    chips.push(`Payroll window: ${filters.payroll_period}`);
                }
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function renderPrintWindow(payload) {
                const filters = payload.filters || {};
                const totals = payload.totals || {};
                const currency = payload.currency || '';
                const payroll = payload.payroll || {};
                const payrollRuns = payload.payroll_runs || [];
                const chart = payload.chart || {};
                const chartLabels = chart.labels || [];
                const chartStaff = chart.staff || [];
                const chartTrainer = chart.trainer || [];
                const chartAppCut = chart.app_cut || [];

                const payrollRows = (payrollRuns || []).map((run) => `
                    <tr>
                        <td>${run.id ?? '—'}</td>
                        <td>
                            <div style="font-weight:600;">${run.name || '—'}</div>
                            <div class="muted">${run.email || ''}</div>
                            <div class="muted">${run.user_code || ''}</div>
                        </td>
                        <td>${run.role || '—'}</td>
                        <td>${run.period || '—'}</td>
                        <td>${run.processed_at || '—'}</td>
                        <td>${run.net || currency + ' 0.00'}</td>
                    </tr>
                `).join('');

                function buildMiniBars(labels, values, color) {
                    if (!values.length) {
                        return '<div class="muted" style="font-size:12px;">No data</div>';
                    }
                    const maxVal = Math.max(...values.map((v) => Number(v) || 0), 0.01);
                    const bars = values.map((val, idx) => {
                        const safeVal = Number(val) || 0;
                        const height = Math.max((safeVal / maxVal) * 60, 6);
                        const label = labels[idx] || '';
                        return `<div class="bar" title="${label}: ${currency} ${safeVal.toFixed(2)}" style="height:${height}px; background:${color};"></div>`;
                    }).join('');
                    return `<div class="bar-row">${bars}</div><div class="muted" style="font-size:11px; margin-top:4px;">${labels.join(' • ')}</div>`;
                }

                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>${payload.title || 'Sales overview'}</title>
                            <style>
                                :root { color-scheme: light; }
                                body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
                                .sheet { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; }
                                .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
                                .title { margin: 0; font-size: 22px; }
                                .muted { color: #6b7280; font-size: 12px; }
                                .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
                                .pill { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
                                .cards { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
                                .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #fff; }
                                .card .label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 6px; display: block; }
                                .card .value { font-size: 20px; font-weight: 700; }
                                .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
                                .badge.approved { background: #dcfce7; color: #166534; }
                                .badge.pending { background: #fef9c3; color: #854d0e; }
                                .badge.rejected { background: #fee2e2; color: #991b1b; }
                                table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px; }
                                th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
                                th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
                                .chart-block { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; margin-top: 16px; }
                                .chart-title { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
                                .bar-row { display: flex; gap: 4px; align-items: flex-end; min-height: 72px; }
                                .bar-row .bar { width: 14px; border-radius: 6px 6px 2px 2px; }
                            </style>
                        </head>
                        <body>
                            <div class="sheet">
                                <div class="header">
                                    <div>
                                        <h1 class="title">${payload.title || 'Sales overview'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <div class="cards">
                                    <div class="card">
                                        <span class="label">Finished payroll runs</span>
                                        <div class="value">${payroll.run_count ?? 0}</div>
                                        <div class="muted">Period: ${payroll.period || '—'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Total sales</span>
                                        <div class="value">${totals.sales ?? 0}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Membership status (period)</span>
                                        <div class="d-flex" style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <span class="badge approved">Approved: ${totals.status?.approved ?? 0}</span>
                                            <span class="badge pending">Pending: ${totals.status?.pending ?? 0}</span>
                                            <span class="badge rejected">Rejected: ${totals.status?.rejected ?? 0}</span>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Payroll totals</span>
                                        <div class="value">${currency} ${payroll.net || '0.00'} <span class="muted" style="font-size:12px; font-weight:500;">net</span></div>
                                        <div class="muted">Gross: ${currency} ${payroll.gross || '0.00'} • Runs: ${payroll.run_count ?? 0}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Staff payroll (net)</span>
                                        <div class="value">${currency} ${payroll.staff_net || '0.00'}</div>
                                        <div class="muted">Period: ${payroll.period || '—'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Trainer payroll (net)</span>
                                        <div class="value">${currency} ${payroll.trainer_net || '0.00'}</div>
                                        <div class="muted">Period: ${payroll.period || '—'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">3kfitness app cut</span>
                                        <div class="value">${currency} ${payroll.app_cut || '0.00'}</div>
                                        <div class="muted">Runs: ${payroll.run_count ?? 0}</div>
                                    </div>
                                </div>
                                <div class="chart-block">
                                    <div class="chart-title">Finished payrolls over time</div>
                                    <div class="muted" style="font-size:11px;">Staff (blue), Trainer (green), App cut (red)</div>
                                    ${buildMiniBars(chartLabels, chartStaff, '#0d6efd')}
                                    ${buildMiniBars(chartLabels, chartTrainer, '#198754')}
                                    ${buildMiniBars(chartLabels, chartAppCut, '#dc3545')}
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="6">Finished payroll runs</th>
                                        </tr>
                                        <tr>
                                            <th>#</th>
                                            <th>Staff/Trainer</th>
                                            <th>Role</th>
                                            <th>Period</th>
                                            <th>Processed</th>
                                            <th>Net pay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${payrollRows || '<tr><td colspan="6" style="text-align:center; padding:16px;">No payroll runs found in this window.</td></tr>'}
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

            if (printButton) {
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
                        window.print();
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
@endsection
