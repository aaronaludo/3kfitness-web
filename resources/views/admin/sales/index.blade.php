@extends('layouts.admin')
@section('title', 'Sales')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $filters = $filters ?? [];
                $filterLabels = $filterLabels ?? [];
                $conversionRates = $conversionRates ?? ['approval' => 0, 'rejection' => 0];
                $filterStart = request('start_date', optional($start)->toDateString());
                $filterEnd = request('end_date', optional($end)->toDateString());
                $staffOrder = $filters['staff_sales_order'] ?? null;
                $trainerOrder = $filters['trainer_sales_order'] ?? null;
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
                        'staff' => $filterLabels['staff'] ?? null,
                        'trainer' => $filterLabels['trainer'] ?? null,
                        'member' => $filterLabels['member'] ?? null,
                        'membership' => $filterLabels['membership'] ?? null,
                        'staff_order' => $staffOrder,
                        'trainer_order' => $trainerOrder,
                    ],
                    'totals' => [
                        'sales' => $totalSales,
                        'revenue' => number_format((float) ($totalRevenue ?? 0), 2),
                        'revenue_period' => optional($start)->format('M d, Y') . ' → ' . optional($end)->format('M d, Y'),
                        'finance' => [
                            'revenue_total' => number_format((float) ($revenueTotal ?? 0), 2),
                            'revenue_components' => [
                                'membership' => number_format((float) ($totalRevenue ?? 0), 2),
                                'app_cut' => number_format((float) ($payrollTotals['app_cut'] ?? 0), 2),
                            ],
                            'cost_total' => number_format((float) ($costTotal ?? 0), 2),
                            'cost_components' => [
                                'staff' => number_format((float) ($payrollTotals['staff_net'] ?? 0), 2),
                                'trainer' => number_format((float) ($payrollTotals['trainer_net'] ?? 0), 2),
                            ],
                            'profit_total' => number_format((float) ($profitTotal ?? 0), 2),
                            'period' => ($payrollTotals['period_label'] ?? '') ?: (optional($start)->format('M d, Y') . ' → ' . optional($end)->format('M d, Y')),
                        ],
                        'status' => [
                            'approved' => $statusTallies['approved'] ?? 0,
                            'pending' => $statusTallies['pending'] ?? 0,
                            'rejected' => $statusTallies['rejected'] ?? 0,
                        ],
                        'conversion' => [
                            'approval' => number_format((float) ($conversionRates['approval'] ?? 0), 1),
                            'rejection' => number_format((float) ($conversionRates['rejection'] ?? 0), 1),
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
                    'pie' => [
                        'labels' => $pieLabels ?? [],
                        'values' => $pieValues ?? [],
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
                        <p class="text-muted small mb-3">Filter by date, staff, trainer, membership plan, or member to focus profit and payroll slices.</p>
                        <form action="{{ route('admin.sales.index') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-lg-3">
                                <label for="start-date" class="form-label small text-muted mb-1">Start date</label>
                                <input type="date" id="start-date" name="start_date" class="form-control" value="{{ request('start_date', optional($start)->toDateString()) }}" />
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="end-date" class="form-label small text-muted mb-1">End date</label>
                                <input type="date" id="end-date" name="end_date" class="form-control" value="{{ request('end_date', optional($end)->toDateString()) }}" />
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="membership-id" class="form-label small text-muted mb-1">Membership plan</label>
                                <select id="membership-id" name="membership_id" class="form-select">
                                    <option value="">All plans</option>
                                    @foreach($membershipOptions ?? [] as $option)
                                        <option value="{{ $option['id'] }}" {{ (string) request('membership_id') === (string) $option['id'] ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="member-id" class="form-label small text-muted mb-1">Member sales</label>
                                <select id="member-id" name="member_id" class="form-select">
                                    <option value="">All members</option>
                                    @foreach($memberOptions ?? [] as $option)
                                        <option value="{{ $option['id'] }}" {{ (string) request('member_id') === (string) $option['id'] ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="staff-id" class="form-label small text-muted mb-1">Staff</label>
                                <select id="staff-id" name="staff_id" class="form-select">
                                    <option value="">All staff</option>
                                    @foreach($staffOptions ?? [] as $option)
                                        <option value="{{ $option['id'] }}" {{ (string) request('staff_id') === (string) $option['id'] ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="trainer-id" class="form-label small text-muted mb-1">Trainer</label>
                                <select id="trainer-id" name="trainer_id" class="form-select">
                                    <option value="">All trainers</option>
                                    @foreach($trainerOptions ?? [] as $option)
                                        <option value="{{ $option['id'] }}" {{ (string) request('trainer_id') === (string) $option['id'] ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="staff-sales-order" class="form-label small text-muted mb-1">Staff sales order</label>
                                <select id="staff-sales-order" name="staff_sales_order" class="form-select">
                                    <option value="">All staff</option>
                                    <option value="most" {{ request('staff_sales_order') === 'most' ? 'selected' : '' }}>Most sales first</option>
                                    <option value="least" {{ request('staff_sales_order') === 'least' ? 'selected' : '' }}>Least sales first</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label for="trainer-sales-order" class="form-label small text-muted mb-1">Trainer sales order</label>
                                <select id="trainer-sales-order" name="trainer_sales_order" class="form-select">
                                    <option value="">All trainers</option>
                                    <option value="most" {{ request('trainer_sales_order') === 'most' ? 'selected' : '' }}>Most sales first</option>
                                    <option value="least" {{ request('trainer_sales_order') === 'least' ? 'selected' : '' }}>Least sales first</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-3 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-danger mt-auto flex-fill"><i class="fa-solid fa-magnifying-glass me-2"></i>Apply</button>
                                <a href="{{ route('admin.sales.index') }}" class="btn btn-light mt-auto flex-fill">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3 align-items-stretch">
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Membership revenue (approved)</div>
                                <div class="h4 mb-2">{{ $currency }} {{ number_format((float) ($totalRevenue ?? 0), 2) }}</div>
                                <small class="text-muted mt-auto">Period: {{ optional($start)->format('M d, Y') }} → {{ optional($end)->format('M d, Y') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Total sales (count)</div>
                                <div class="h4 mb-2">{{ $totalSales }}</div>
                                <small class="text-muted mt-auto">Approved payments in window</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Total Payouts (Net)</div>
                                <div class="h4 mb-2">{{ $currency }} {{ number_format((float) ($payrollTotals['net'] ?? 0), 2) }}</div>
                                <small class="text-muted mt-auto">Runs: {{ $payrollTotals['run_count'] ?? 0 }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small mb-2">New Memberships (period)</div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-success">Approved: {{ $statusTallies['approved'] ?? 0 }}</span>
                                    <span class="badge bg-warning text-dark">Pending: {{ $statusTallies['pending'] ?? 0 }}</span>
                                    <span class="badge bg-danger">Rejected: {{ $statusTallies['rejected'] ?? 0 }}</span>
                                </div>
                                <div class="d-flex gap-3 flex-wrap align-items-center mt-2">
                                    <span class="text-success small fw-semibold">Approval rate: {{ number_format((float) ($conversionRates['approval'] ?? 0), 1) }}%</span>
                                    <span class="text-danger small fw-semibold">Rejection rate: {{ number_format((float) ($conversionRates['rejection'] ?? 0), 1) }}%</span>
                                </div>
                                <small class="text-muted mt-auto">Same date window as filters</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="row g-3 align-items-stretch">
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Gross Revenue</div>
                                <div class="h4 mb-2">{{ $currency }} {{ number_format((float) ($revenueTotal ?? 0), 2) }}</div>
                                <small class="text-muted">Membership: {{ $currency }} {{ number_format((float) ($totalRevenue ?? 0), 2) }}</small>
                                <small class="text-muted mb-2">App cut: {{ $currency }} {{ number_format((float) ($payrollTotals['app_cut'] ?? 0), 2) }}</small>
                                <small class="text-muted mt-auto">Period: {{ ($payrollTotals['period_label'] ?? '') ?: (optional($start)->format('M d, Y') . ' → ' . optional($end)->format('M d, Y')) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Cost (staff + trainer payroll)</div>
                                <div class="h4 mb-2">{{ $currency }} {{ number_format((float) ($costTotal ?? 0), 2) }}</div>
                                <small class="text-muted">Staff: {{ $currency }} {{ number_format((float) ($payrollTotals['staff_net'] ?? 0), 2) }}</small>
                                <small class="text-muted mb-2">Trainer: {{ $currency }} {{ number_format((float) ($payrollTotals['trainer_net'] ?? 0), 2) }}</small>
                                <small class="text-muted mt-auto">Period: {{ $payrollTotals['period_label'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Profit (revenue − cost)</div>
                                <div class="h4 mb-2">{{ $currency }} {{ number_format((float) ($profitTotal ?? 0), 2) }}</div>
                                <small class="text-muted mt-auto">Period: {{ ($payrollTotals['period_label'] ?? '') ?: (optional($start)->format('M d, Y') . ' → ' . optional($end)->format('M d, Y')) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="fw-semibold">Revenue • Cost • Profit</div>
                            <small class="text-muted">Totals for the selected window</small>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-6">
                                <div class="d-flex justify-content-center">
                                    <canvas id="financePie" style="max-width: 360px; max-height: 360px;"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <ul class="list-unstyled mb-0 small text-muted">
                                    <li class="mb-1"><span class="badge bg-primary me-2">&nbsp;</span>Revenue: {{ $currency }} {{ number_format((float) ($revenueTotal ?? 0), 2) }}</li>
                                    <li class="mb-1"><span class="badge bg-danger me-2">&nbsp;</span>Cost: {{ $currency }} {{ number_format((float) ($costTotal ?? 0), 2) }}</li>
                                    <li><span class="badge bg-success me-2">&nbsp;</span>Profit: {{ $currency }} {{ number_format((float) ($profitTotal ?? 0), 2) }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="row g-3 align-items-stretch">
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Staff payroll (net)</div>
                                <div class="h5 mb-2">{{ $currency }} {{ number_format((float) ($payrollTotals['staff_net'] ?? 0), 2) }}</div>
                                <small class="text-muted mt-auto">Period: {{ $payrollTotals['period_label'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">Trainer payroll (net)</div>
                                <div class="h5 mb-2">{{ $currency }} {{ number_format((float) ($payrollTotals['trainer_net'] ?? 0), 2) }}</div>
                                <small class="text-muted mt-auto">Period: {{ $payrollTotals['period_label'] }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="text-muted small">3kfitness app cut</div>
                                <div class="h5 mb-2">{{ $currency }} {{ number_format((float) ($payrollTotals['app_cut'] ?? 0), 2) }}</div>
                                <small class="text-muted mt-auto">Runs: {{ $payrollTotals['run_count'] ?? 0 }}</small>
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
            const financePieCtx = document.getElementById('financePie');
            if (financePieCtx) {
                new Chart(financePieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Revenue', 'Cost', 'Profit'],
                        datasets: [{
                            data: @json([($revenueTotal ?? 0), ($costTotal ?? 0), ($profitTotal ?? 0)]),
                            backgroundColor: ['#0d6efd', '#dc3545', '#198754'],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                    },
                });
            }

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
                if (filters.membership) {
                    chips.push(`Plan: ${filters.membership}`);
                }
                if (filters.member) {
                    chips.push(`Member: ${filters.member}`);
                }
                if (filters.staff) {
                    chips.push(`Staff: ${filters.staff}`);
                }
                if (filters.trainer) {
                    chips.push(`Trainer: ${filters.trainer}`);
                }
                if (filters.staff_order) {
                    chips.push(`Staff rank: ${filters.staff_order === 'least' ? 'Least sales' : 'Most sales'}`);
                }
                if (filters.trainer_order) {
                    chips.push(`Trainer rank: ${filters.trainer_order === 'least' ? 'Least sales' : 'Most sales'}`);
                }
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function renderPrintWindow(payload) {
                const filters = payload.filters || {};
                const totals = payload.totals || {};
                const currency = payload.currency || '';
                const payroll = payload.payroll || {};
                const finance = totals.finance || {};
                const chart = payload.chart || {};
                const chartLabels = chart.labels || [];
                const chartStaff = chart.staff || [];
                const chartTrainer = chart.trainer || [];
                const chartAppCut = chart.app_cut || [];
                const piePayload = payload.pie || {};
                const pieLabels = (piePayload.labels && piePayload.labels.length)
                    ? piePayload.labels
                    : ['Staff payroll (net)', 'Trainer payroll (net)', '3kfitness app cut'];
                const pieValues = Array.isArray(piePayload.values) ? piePayload.values : [];
                const financePieLabels = ['Revenue', 'Cost', 'Profit'];
                const financePieValues = [
                    finance.revenue_total,
                    finance.cost_total,
                    finance.profit_total,
                ];

                function toNumber(val) {
                    if (typeof val === 'number') return val;
                    if (typeof val === 'string') {
                        const parsed = Number(val.replace(/,/g, ''));
                        return Number.isFinite(parsed) ? parsed : 0;
                    }
                    return 0;
                }

                function sumSeries(values) {
                    return (values || []).reduce((sum, val) => sum + toNumber(val), 0);
                }

                const conversion = totals.conversion || {};
                const approvalRate = toNumber(conversion.approval || 0);
                const rejectionRate = toNumber(conversion.rejection || 0);

                function buildPieChart(labels, values, palette) {
                    const colors = palette && palette.length
                        ? palette
                        : ['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1'];

                    const slices = (labels || []).map((label, idx) => ({
                        label: label || `Slice ${idx + 1}`,
                        value: toNumber(values[idx]),
                        color: colors[idx % colors.length],
                    })).filter((slice) => slice.value > 0);

                    const total = slices.reduce((sum, slice) => sum + slice.value, 0);
                    if (!total) {
                        return '<div class="muted" style="font-size:12px;">No data available</div>';
                    }

                    const radius = 80;
                    const circumference = 2 * Math.PI * radius;
                    let offset = 0;

                    const arcs = slices.map((slice) => {
                        const length = (slice.value / total) * circumference;
                        const arc = `
                            <circle
                                class="pie-slice"
                                r="${radius}"
                                cx="100"
                                cy="100"
                                stroke="${slice.color}"
                                stroke-dasharray="${length} ${circumference - length}"
                                stroke-dashoffset="-${offset}"
                            ></circle>
                        `;
                        offset += length;
                        return arc;
                    }).join('');

                    const legend = slices.map((slice) => `
                        <div class="legend-row">
                            <span class="legend-swatch" style="background:${slice.color};"></span>
                            <span>${slice.label}: ${currency} ${slice.value.toFixed(2)}</span>
                        </div>
                    `).join('');

                    return `
                        <div class="pie-wrapper">
                            <svg class="pie-svg" viewBox="0 0 200 200" role="img" aria-label="Pie chart">
                                <circle class="pie-ring" r="${radius}" cx="100" cy="100"></circle>
                                ${arcs}
                            </svg>
                            <div class="legend">${legend}</div>
                        </div>
                    `;
                }

                const pieValuesToUse = pieValues.some((val) => toNumber(val) > 0)
                    ? pieValues
                    : [
                        sumSeries(chartStaff) || toNumber(payroll.staff_net),
                        sumSeries(chartTrainer) || toNumber(payroll.trainer_net),
                        sumSeries(chartAppCut) || toNumber(payroll.app_cut),
                    ];

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
                                .pie-wrapper { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
                                .pie-svg { width: 180px; height: 180px; transform: rotate(-90deg); }
                                .pie-ring { fill: none; stroke: #e5e7eb; stroke-width: 28; }
                                .pie-slice { fill: none; stroke-width: 28; stroke-linecap: butt; }
                                .legend { display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: #374151; }
                                .legend-row { display: flex; align-items: center; gap: 8px; }
                                .legend-swatch { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }
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
                                        <span class="label">Total sales</span>
                                        <div class="value">${totals.sales ?? 0}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Membership revenue (approved)</span>
                                        <div class="value">${currency} ${totals.revenue || '0.00'}</div>
                                        <div class="muted">Period: ${totals.revenue_period || finance.period || '—'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Gross Revenue</span>
                                        <div class="value">${currency} ${finance.revenue_total || '0.00'}</div>
                                        <div class="muted">Membership: ${currency} ${finance.revenue_components?.membership || '0.00'} • App cut: ${currency} ${finance.revenue_components?.app_cut || '0.00'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Cost (staff + trainer payroll)</span>
                                        <div class="value">${currency} ${finance.cost_total || '0.00'}</div>
                                        <div class="muted">Staff: ${currency} ${finance.cost_components?.staff || '0.00'} • Trainer: ${currency} ${finance.cost_components?.trainer || '0.00'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Profit (revenue − cost)</span>
                                        <div class="value">${currency} ${finance.profit_total || '0.00'}</div>
                                        <div class="muted">Period: ${finance.period || '—'}</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Membership status (period)</span>
                                        <div class="d-flex" style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <span class="badge approved">Approved: ${totals.status?.approved ?? 0}</span>
                                            <span class="badge pending">Pending: ${totals.status?.pending ?? 0}</span>
                                            <span class="badge rejected">Rejected: ${totals.status?.rejected ?? 0}</span>
                                        </div>
                                        <div class="muted" style="margin-top:8px;">Approval rate: ${approvalRate.toFixed(1)}% • Rejection rate: ${rejectionRate.toFixed(1)}%</div>
                                    </div>
                                    <div class="card">
                                        <span class="label">Total Payouts (Net)</span>
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
                                    <div class="chart-title">Revenue • Cost • Profit</div>
                                    ${buildPieChart(financePieLabels, financePieValues, ['#0d6efd', '#dc3545', '#198754'])}
                                </div>
                                <div class="chart-block">
                                    <div class="chart-title">Finished payroll mix</div>
                                    ${buildPieChart(pieLabels, pieValuesToUse, ['#0d6efd', '#198754', '#dc3545'])}
                                </div>
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
