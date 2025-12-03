@extends('layouts.admin')
@section('title', 'Sales')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $filterStart = request('start_date', optional($start)->toDateString());
                $filterEnd = request('end_date', optional($end)->toDateString());
                $seriesItems = collect($labels)->zip($series ?? [])->map(function ($pair) use ($currency) {
                    return [
                        'label' => $pair[0] ?? '',
                        'value' => isset($pair[1]) ? number_format((float) $pair[1], 2) : '0.00',
                        'currency' => $currency,
                    ];
                });
                $pieItems = collect($pieLabels)->zip($pieValues ?? [])->map(function ($pair) use ($currency) {
                    return [
                        'label' => $pair[0] ?? '',
                        'value' => isset($pair[1]) ? number_format((float) $pair[1], 2) : '0.00',
                        'currency' => $currency,
                    ];
                });
                $printPayload = [
                    'title' => 'Sales overview',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'start' => $filterStart,
                        'end' => $filterEnd,
                    ],
                    'totals' => [
                        'revenue' => number_format((float) $totalRevenue, 2),
                        'sales' => $totalSales,
                        'status' => [
                            'approved' => $statusTallies['approved'] ?? 0,
                            'pending' => $statusTallies['pending'] ?? 0,
                            'rejected' => $statusTallies['rejected'] ?? 0,
                        ],
                    ],
                    'series' => $seriesItems,
                    'pie' => $pieItems,
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
                                <div class="text-muted small">Total Revenue</div>
                                <div class="h4 mb-0">{{ $currency }} {{ number_format((float) $totalRevenue, 2) }}</div>
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
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Revenue over time</div>
                                </div>
                                <canvas id="salesLine"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Revenue by membership</div>
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
                        labels: @json($labels),
                        datasets: [{
                            label: 'Revenue ({{ $currency }})',
                            data: @json($series),
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.3,
                            fill: true,
                        }]
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
                        labels: @json($pieLabels),
                        datasets: [{
                            label: 'Revenue Share',
                            data: @json($pieValues),
                            backgroundColor: [
                                '#dc3545', '#0d6efd', '#198754', '#ffc107', '#20c997', '#6f42c1', '#6c757d', '#fd7e14'
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
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildSeriesRows(items, currency) {
                return items.map((item) => `
                    <tr>
                        <td>${item.label || '—'}</td>
                        <td>${item.value ? currency + ' ' + item.value : currency + ' 0.00'}</td>
                    </tr>
                `).join('');
            }

            function buildPieRows(items, currency) {
                return items.map((item) => `
                    <tr>
                        <td>${item.label || '—'}</td>
                        <td>${item.value ? currency + ' ' + item.value : currency + ' 0.00'}</td>
                    </tr>
                `).join('');
            }

            function renderPrintWindow(payload) {
                const filters = payload.filters || {};
                const totals = payload.totals || {};
                const series = payload.series || [];
                const pie = payload.pie || [];
                const currency = payload.currency || '';
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
                                table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
                                th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
                                th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
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
                                        <span class="label">Total revenue</span>
                                        <div class="value">${currency} ${totals.revenue || '0.00'}</div>
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
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2">Revenue over time</th>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${buildSeriesRows(series, currency) || '<tr><td colspan="2" style="text-align:center; padding:16px;">No data.</td></tr>'}
                                    </tbody>
                                </table>
                                <table>
                                    <thead>
                                        <tr>
                                            <th colspan="2">Revenue by membership</th>
                                        </tr>
                                        <tr>
                                            <th>Membership</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${buildPieRows(pie, currency) || '<tr><td colspan="2" style="text-align:center; padding:16px;">No data.</td></tr>'}
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
