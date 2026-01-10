@extends('layouts.admin')
@section('title', 'Payroll Report')

@section('styles')
<style>
    .report-summary {
        --border: #e7e7ea;
        --muted: #6b7280;
    }
    .summary-card {
        --accent: #d63e4b;
        --accent-soft: #fff5f5;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.06);
        min-height: 100%;
    }
    .summary-card__title { font-weight: 700; color: #1f2933; }
    .summary-card__subtitle { color: var(--muted); font-size: 0.9rem; }
    .summary-amount { font-size: 1.8rem; font-weight: 800; color: #1f2933; }
    .pill-soft {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        background: #fff5f5;
        color: #c53030;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .pill-ghost {
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--muted);
        background: #fff;
        font-weight: 700;
    }
    .focus-chip.btn-dark {
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
    }
    .table-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .table-meta .pill-soft { background: #f4f5f7; color: #111827; border-color: #e5e7eb; }
    .payroll-table th { text-transform: uppercase; letter-spacing: 0.02em; font-size: 0.8rem; }
    .payroll-table td .muted { color: #6b7280; font-size: 0.9rem; }
    .deduction-list { color: #6b7280; font-size: 0.85rem; margin: 0; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        @php
            $focus = request('focus', 'trainer');
            $search = trim(request('search', ''));
            $startDateInput = request('start_date');
            $endDateInput = request('end_date');
            $datePreset = request('date_preset');
            if (!$datePreset && ($startDateInput || $endDateInput)) {
                $datePreset = 'custom';
            }
            $datePreset = $datePreset ?: 'all_time';

            $today = \Carbon\Carbon::now();
            $resolveDateRange = function ($preset) use ($today) {
                $start = $today->copy()->startOfYear();
                $end = $today->copy()->endOfDay();

                switch ($preset) {
                    case 'today':
                        $start = $today->copy()->startOfDay();
                        $end = $today->copy()->endOfDay();
                        break;
                    case 'yesterday':
                        $start = $today->copy()->subDay()->startOfDay();
                        $end = $today->copy()->subDay()->endOfDay();
                        break;
                    case 'last_7':
                        $start = $today->copy()->subDays(6)->startOfDay();
                        $end = $today->copy()->endOfDay();
                        break;
                    case 'last_30':
                        $start = $today->copy()->subDays(29)->startOfDay();
                        $end = $today->copy()->endOfDay();
                        break;
                    case 'this_week':
                        $start = $today->copy()->startOfWeek();
                        $end = $today->copy()->endOfWeek();
                        break;
                    case 'last_week':
                        $start = $today->copy()->subWeek()->startOfWeek();
                        $end = $today->copy()->subWeek()->endOfWeek();
                        break;
                    case 'this_month':
                        $start = $today->copy()->startOfMonth();
                        $end = $today->copy()->endOfMonth();
                        break;
                    case 'last_month':
                        $lastMonth = $today->copy()->subMonth();
                        $start = $lastMonth->copy()->startOfMonth();
                        $end = $lastMonth->copy()->endOfMonth();
                        break;
                    case 'this_quarter':
                        $start = $today->copy()->firstOfQuarter()->startOfDay();
                        $end = $today->copy()->lastOfQuarter()->endOfDay();
                        break;
                    case 'last_quarter':
                        $lastQuarter = $today->copy()->subQuarter();
                        $start = $lastQuarter->copy()->firstOfQuarter()->startOfDay();
                        $end = $lastQuarter->copy()->lastOfQuarter()->endOfDay();
                        break;
                    case 'this_year':
                        $start = $today->copy()->startOfYear();
                        $end = $today->copy()->endOfDay();
                        break;
                    case 'last_year':
                        $lastYear = $today->copy()->subYear();
                        $start = $lastYear->copy()->startOfYear();
                        $end = $lastYear->copy()->endOfYear();
                        break;
                    case 'all_time':
                        $start = \Carbon\Carbon::create(2000, 1, 1, 0, 0, 0);
                        $end = $today->copy()->endOfDay();
                        break;
                    case 'custom':
                    default:
                        return [null, null];
                }

                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            };

            if ($datePreset !== 'custom') {
                [$startDateInput, $endDateInput] = $resolveDateRange($datePreset);
            }

            $startDate = $startDateInput ? \Carbon\Carbon::parse($startDateInput)->startOfDay() : null;
            $endDate = $endDateInput ? \Carbon\Carbon::parse($endDateInput)->endOfDay() : null;
            $presetLabels = [
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'last_7' => 'Last 7 Days',
                'last_30' => 'Last 30 Days',
                'this_week' => 'This Week',
                'last_week' => 'Last Week',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_quarter' => 'This Quarter',
                'last_quarter' => 'Last Quarter',
                'this_year' => 'This Year',
                'last_year' => 'Last Year',
                'all_time' => 'All Time',
            ];
            $dateRangeLabel = $datePreset === 'custom'
                ? trim(($startDateInput ?: '—') . ' → ' . ($endDateInput ?: '—'))
                : (($presetLabels[$datePreset] ?? 'All Time') . ($startDateInput && $endDateInput ? ' (' . $startDateInput . ' → ' . $endDateInput . ')' : ''));
            $perPage = 10;
            $runsQuery = \App\Models\PayrollRun::with(['user.role'])
                ->orderByDesc('processed_at')
                ->orderByDesc('id');

            $focusLabel = 'All payroll runs';
            if ($focus === 'trainer') {
                $focusLabel = 'Trainer payroll runs';
                $runsQuery->whereHas('user.role', function ($q) {
                    $q->where('name', 'like', '%trainer%');
                });
            } elseif ($focus === 'staff') {
                $focusLabel = 'Staff payroll runs';
                $runsQuery->where(function ($q) {
                    $q->whereHas('user.role', function ($role) {
                        $role->where('name', 'not like', '%trainer%');
                    })->orWhereDoesntHave('user.role');
                });
            }

            if ($search !== '') {
                $like = '%' . $search . '%';
                $integerSearch = ctype_digit($search) ? (int) $search : null;
                $parsedDate = null;
                try {
                    $parsedDate = \Carbon\Carbon::parse($search)->toDateString();
                } catch (\Throwable $th) {
                    $parsedDate = null;
                }

                $runsQuery->where(function ($query) use ($like, $integerSearch, $parsedDate) {
                    $query->where('period_month', 'like', $like)
                        ->orWhere('total_hours', 'like', $like)
                        ->orWhere('gross_pay', 'like', $like)
                        ->orWhere('deduction_sss', 'like', $like)
                        ->orWhere('deduction_philhealth', 'like', $like)
                        ->orWhere('deduction_pagibig', 'like', $like)
                        ->orWhere('deduction_app_cut', 'like', $like)
                        ->orWhere('net_pay', 'like', $like)
                        ->orWhere('processed_at', 'like', $like)
                        ->orWhere('created_at', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery
                                ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('user_code', 'like', $like)
                                ->orWhereHas('role', function ($roleQuery) use ($like) {
                                    $roleQuery->where('name', 'like', $like);
                                });
                        });

                    if (!is_null($integerSearch)) {
                        $query->orWhere('id', $integerSearch)
                            ->orWhere('user_id', $integerSearch);
                    }

                    if ($parsedDate) {
                        $query->orWhereDate('processed_at', $parsedDate)
                            ->orWhereDate('created_at', $parsedDate);
                    }
                });
            }

            if ($startDate || $endDate) {
                $runsQuery->where(function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        if ($startDate) {
                            $q->where('processed_at', '>=', $startDate);
                        }
                        if ($endDate) {
                            $q->where('processed_at', '<=', $endDate);
                        }
                    })
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->whereNull('processed_at');
                        if ($startDate) {
                            $q->where('created_at', '>=', $startDate);
                        }
                        if ($endDate) {
                            $q->where('created_at', '<=', $endDate);
                        }
                    });
                });
            }

            $runsQueryForAll = clone $runsQuery;
            $filteredRuns = $runsQuery->paginate($perPage)->appends(request()->query());
            $filteredTotal = $filteredRuns->total();
            $filteredCollection = $runsQueryForAll->get();
            $currencySymbol = '₱';
            $filteredTotals = [
                'gross' => round($filteredCollection->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
                'net' => round($filteredCollection->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
                'sss' => round($filteredCollection->sum(fn ($run) => (float) ($run->deduction_sss ?? 0)), 2),
                'philhealth' => round($filteredCollection->sum(fn ($run) => (float) ($run->deduction_philhealth ?? 0)), 2),
                'pagibig' => round($filteredCollection->sum(fn ($run) => (float) ($run->deduction_pagibig ?? 0)), 2),
                'app_cut' => round($filteredCollection->sum(fn ($run) => (float) ($run->deduction_app_cut ?? 0)), 2),
            ];
            $pageTotals = [
                'gross' => round(collect($filteredRuns->items() ?? [])->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
                'net' => round(collect($filteredRuns->items() ?? [])->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
                'sss' => round(collect($filteredRuns->items() ?? [])->sum(fn ($run) => (float) ($run->deduction_sss ?? 0)), 2),
                'philhealth' => round(collect($filteredRuns->items() ?? [])->sum(fn ($run) => (float) ($run->deduction_philhealth ?? 0)), 2),
                'pagibig' => round(collect($filteredRuns->items() ?? [])->sum(fn ($run) => (float) ($run->deduction_pagibig ?? 0)), 2),
                'app_cut' => round(collect($filteredRuns->items() ?? [])->sum(fn ($run) => (float) ($run->deduction_app_cut ?? 0)), 2),
            ];

            $mapRun = function ($run) {
                $staff = $run->user;
                $name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
                $periodLabel = $run->period_month
                    ? \Carbon\Carbon::parse($run->period_month . '-01')->format('M Y')
                    : '—';

                return [
                    'id' => $run->id,
                    'name' => $name !== '' ? $name : '—',
                    'email' => $staff->email ?? '—',
                    'user_code' => $staff->user_code ?? '—',
                    'role' => optional($staff->role)->name ?? '—',
                    'period' => $periodLabel,
                    'hours' => number_format((float) ($run->total_hours ?? 0), 2),
                    'gross' => number_format((float) ($run->gross_pay ?? 0), 2),
                    'sss' => number_format((float) ($run->deduction_sss ?? 0), 2),
                    'philhealth' => number_format((float) ($run->deduction_philhealth ?? 0), 2),
                    'pagibig' => number_format((float) ($run->deduction_pagibig ?? 0), 2),
                    'app_cut' => number_format((float) ($run->deduction_app_cut ?? 0), 2),
                    'net' => number_format((float) ($run->net_pay ?? 0), 2),
                    'processed_at' => $run->processed_at
                        ? $run->processed_at->format('M d, Y g:i A')
                        : ($run->created_at?->format('M d, Y g:i A') ?? '—'),
                ];
            };

            $printRuns = collect($filteredRuns->items() ?? [])->map($mapRun)->values();
            $printAllRuns = collect($filteredCollection ?? [])->map($mapRun)->values();

            $printPayload = [
                'title' => 'Payroll report',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $search,
                    'focus' => $focus,
                    'focus_label' => $focusLabel,
                    'date_preset' => $datePreset,
                    'date_range' => $dateRangeLabel,
                    'start_date' => $startDateInput,
                    'end_date' => $endDateInput,
                ],
                'currency_symbol' => $currencySymbol,
                'totals' => $pageTotals,
                'count' => $printRuns->count(),
                'items' => $printRuns,
            ];

            $printAllPayload = [
                'title' => 'Payroll report (all pages)',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $search,
                    'focus' => $focus,
                    'focus_label' => $focusLabel,
                    'date_preset' => $datePreset,
                    'date_range' => $dateRangeLabel,
                    'start_date' => $startDateInput,
                    'end_date' => $endDateInput,
                    'scope' => 'all',
                ],
                'currency_symbol' => $currencySymbol,
                'totals' => $filteredTotals,
                'count' => $printAllRuns->count(),
                'items' => $printAllRuns,
            ];
        @endphp
        <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 mt-2">
            <div>
                <h2 class="title mb-1">Payroll Report</h2>
                <p class="text-muted mb-0">Snapshot of payroll totals across all processed runs.</p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-3 h-100">
                <div class="text-end text-muted small">
                    <div>Total runs: {{ $runsCount }}</div>
                    <div>Last updated: {{ now()->format('M d, Y g:i A') }}</div>
                </div>
                <form action="#" method="POST" id="print-form">
                    @csrf
                    <button
                        type="submit"
                        class="btn btn-danger d-flex align-items-center gap-2"
                        id="print-submit-button"
                        data-print='@json($printPayload)'
                        data-print-all='@json($printAllPayload)'
                        aria-label="Open printable/PDF view of payroll report"
                    >
                        <i class="fa-solid fa-print"></i>
                        <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        Print
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12 mb-3 report-summary">
            <div class="row g-3">
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="summary-card">
                        <div class="summary-card__title h6 mb-1">Gross</div>
                        <div class="summary-card__subtitle mb-2">Total gross pay</div>
                        <div class="summary-amount">₱{{ number_format($filteredTotals['gross'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="summary-card">
                        <div class="summary-card__title h6 mb-1">Net</div>
                        <div class="summary-card__subtitle mb-2">Payout after deductions</div>
                        <div class="summary-amount text-success">₱{{ number_format($filteredTotals['net'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="summary-card">
                        <div class="summary-card__title h6 mb-1">SSS</div>
                        <div class="summary-card__subtitle mb-2">Government deduction</div>
                        <div class="summary-amount">₱{{ number_format($filteredTotals['sss'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="summary-card">
                        <div class="summary-card__title h6 mb-1">PhilHealth</div>
                        <div class="summary-card__subtitle mb-2">Health contribution</div>
                        <div class="summary-amount">₱{{ number_format($filteredTotals['philhealth'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="summary-card">
                        <div class="summary-card__title h6 mb-1">Pag-IBIG</div>
                        <div class="summary-card__subtitle mb-2">Savings deduction</div>
                        <div class="summary-amount">₱{{ number_format($filteredTotals['pagibig'] ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="summary-card">
                        <div class="summary-card__title h6 mb-1">3K Fitness App Cut</div>
                        <div class="summary-card__subtitle mb-2">Platform share</div>
                        <div class="summary-amount">₱{{ number_format($filteredTotals['app_cut'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if(($runsCount ?? 0) === 0)
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body text-center text-muted py-5">
                        <i class="fa-solid fa-clipboard-list fa-2x mb-3"></i>
                        <h5 class="fw-semibold mb-1">No payroll runs yet</h5>
                        <p class="mb-0">Process payroll to see gross, net, and deduction totals here.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                                <h4 class="fw-semibold mb-1">Payroll report filters</h4>
                                <p class="text-muted mb-0">Toggle between trainer and staff runs or search across IDs, names, roles, periods, totals, and dates.</p>
                            </div>
                            <div class="text-end">
                                <span class="pill-soft d-inline-block mb-1">{{ $focusLabel }}</span>
                                <div class="text-muted small">Showing {{ $filteredTotal }} record{{ $filteredTotal === 1 ? '' : 's' }}</div>
                            </div>
                        </div>
                        <form action="{{ route('admin.payrolls.report') }}" method="GET" class="mt-4" id="payroll-report-filter-form">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1">
                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                        <label class="form-label text-muted small mb-1" for="search">Search</label>
                                        <div class="position-relative">
                                            <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                            <input
                                                type="search"
                                                class="form-control rounded-pill ps-5"
                                                name="search"
                                                id="search"
                                                value="{{ $search }}"
                                                placeholder="ID, name, email, code, role, period, totals, date"
                                                aria-label="Search payroll runs"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 220px;">
                                        <label class="form-label text-muted small mb-1" for="date_preset">Date range</label>
                                        <select id="date_preset" name="date_preset" class="form-select rounded-pill">
                                            <option value="today" {{ ($datePreset ?? 'all_time') === 'today' ? 'selected' : '' }}>Today</option>
                                            <option value="yesterday" {{ ($datePreset ?? 'all_time') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                            <option value="last_7" {{ ($datePreset ?? 'all_time') === 'last_7' ? 'selected' : '' }}>Last 7 Days</option>
                                            <option value="last_30" {{ ($datePreset ?? 'all_time') === 'last_30' ? 'selected' : '' }}>Last 30 Days</option>
                                            <option value="this_week" {{ ($datePreset ?? 'all_time') === 'this_week' ? 'selected' : '' }}>This Week</option>
                                            <option value="last_week" {{ ($datePreset ?? 'all_time') === 'last_week' ? 'selected' : '' }}>Last Week</option>
                                            <option value="this_month" {{ ($datePreset ?? 'all_time') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                            <option value="last_month" {{ ($datePreset ?? 'all_time') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                            <option value="this_quarter" {{ ($datePreset ?? 'all_time') === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                                            <option value="last_quarter" {{ ($datePreset ?? 'all_time') === 'last_quarter' ? 'selected' : '' }}>Last Quarter</option>
                                            <option value="this_year" {{ ($datePreset ?? 'all_time') === 'this_year' ? 'selected' : '' }}>This Year</option>
                                            <option value="last_year" {{ ($datePreset ?? 'all_time') === 'last_year' ? 'selected' : '' }}>Last Year</option>
                                            <option value="all_time" {{ ($datePreset ?? 'all_time') === 'all_time' ? 'selected' : '' }}>All Time</option>
                                            <option value="custom" {{ ($datePreset ?? 'all_time') === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                                        </select>
                                    </div>

                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                        <label class="form-label text-muted small mb-1 d-block">Payroll type</label>
                                        <div class="btn-group" role="group" aria-label="Payroll focus">
                                            <button type="button" class="btn btn-outline-secondary focus-chip {{ $focus === 'trainer' ? 'btn-dark text-white' : '' }}" data-focus="trainer">
                                                <i class="fa-solid fa-ranking-star me-1"></i>Trainer runs
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary focus-chip {{ $focus === 'staff' ? 'btn-dark text-white' : '' }}" data-focus="staff">
                                                <i class="fa-solid fa-user-gear me-1"></i>Staff runs
                                            </button>
                                        </div>
                                        <input type="hidden" name="focus" id="payroll-focus" value="{{ $focus }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="{{ route('admin.payrolls.report') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-filter"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="{{ ($datePreset ?? 'all_time') === 'custom' ? '' : 'd-none' }} w-100" id="custom-date-range">
                                <label class="form-label text-muted text-uppercase small d-block mb-2">Custom date range</label>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label small text-muted mb-1" for="start_date">Start date</label>
                                        <input
                                            type="date"
                                            name="start_date"
                                            id="start_date"
                                            class="form-control rounded-3"
                                            value="{{ $startDateInput }}"
                                            aria-label="Start date"
                                        >
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label small text-muted mb-1" for="end_date">End date</label>
                                        <input
                                            type="date"
                                            name="end_date"
                                            id="end_date"
                                            class="form-control rounded-3"
                                            value="{{ $endDateInput }}"
                                            aria-label="End date"
                                        >
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">Matches processed date, falls back to created date if missing.</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                            <div>
                                <h5 class="fw-semibold mb-1">Payroll runs</h5>
                                <p class="text-muted small mb-2">Styled to match the Sales Report table.</p>
                                <div class="table-meta">
                                    <span class="pill-soft">{{ $focusLabel }}</span>
                                    <span class="pill-soft">Total runs: {{ $filteredTotal }}</span>
                                    <span class="pill-soft">Date: {{ $dateRangeLabel }}</span>
                                </div>
                            </div>
                            <div class="text-muted small text-end">
                                <div>Gross total: ₱{{ number_format($filteredTotals['gross'] ?? 0, 2) }}</div>
                                <div>Net total: ₱{{ number_format($filteredTotals['net'] ?? 0, 2) }}</div>
                            </div>
                        </div>

                        @if($filteredRuns->count() === 0)
                            <div class="text-center text-muted py-5">
                                <i class="fa-regular fa-file-lines fa-2x mb-3"></i>
                                <h6 class="fw-semibold mb-1">No payroll runs match this filter</h6>
                                <p class="mb-0">Adjust the search or payroll type to see results.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle payroll-table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Staff</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Period</th>
                                            <th scope="col">Hours</th>
                                            <th scope="col">Gross</th>
                                            <th scope="col">Deductions</th>
                                            <th scope="col">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($filteredRuns as $run)
                                            @php
                                                $staff = $run->user;
                                                $name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
                                                $email = $staff->email ?? '—';
                                                $code = $staff->user_code ?? '—';
                                                $roleName = optional($staff->role)->name ?? '—';
                                                $periodLabel = $run->period_month
                                                    ? \Carbon\Carbon::parse($run->period_month . '-01')->format('M Y')
                                                    : '—';
                                                $deductionTotal = ($run->deduction_sss ?? 0) + ($run->deduction_philhealth ?? 0) + ($run->deduction_pagibig ?? 0) + ($run->deduction_app_cut ?? 0);
                                            @endphp
                                            <tr>
                                                <td class="text-muted">#{{ $run->id }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $name !== '' ? $name : '—' }}</div>
                                                    <div class="muted">{{ $email }}</div>
                                                    <div class="muted">Code: {{ $code }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">{{ $roleName !== '' ? $roleName : '—' }}</span>
                                                </td>
                                                <td>{{ $periodLabel }}</td>
                                                <td>{{ number_format((float) ($run->total_hours ?? 0), 2) }}</td>
                                                <td>₱{{ number_format((float) ($run->gross_pay ?? 0), 2) }}</td>
                                                <td>
                                                    <div class="fw-semibold">₱{{ number_format((float) $deductionTotal, 2) }}</div>
                                                    <ul class="deduction-list list-unstyled mb-0">
                                                        <li>SSS: ₱{{ number_format((float) ($run->deduction_sss ?? 0), 2) }}</li>
                                                        <li>PhilHealth: ₱{{ number_format((float) ($run->deduction_philhealth ?? 0), 2) }}</li>
                                                        <li>Pag-IBIG: ₱{{ number_format((float) ($run->deduction_pagibig ?? 0), 2) }}</li>
                                                        <li>App cut: ₱{{ number_format((float) ($run->deduction_app_cut ?? 0), 2) }}</li>
                                                    </ul>
                                                </td>
                                                <td class="fw-semibold text-success">₱{{ number_format((float) ($run->net_pay ?? 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $filteredRuns->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const printButton = document.getElementById('print-submit-button');
        const printLoader = document.getElementById('print-loader');
        const datePresetSelect = document.getElementById('date_preset');
        const customDateRange = document.getElementById('custom-date-range');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const focusButtons = document.querySelectorAll('.focus-chip');
        const focusField = document.getElementById('payroll-focus');

        function buildFilters(filters) {
            const chips = [];
            if (filters.focus_label || filters.focus) {
                chips.push({ label: 'Type', value: filters.focus_label || filters.focus });
            }
            if (filters.search) chips.push({ label: 'Search', value: filters.search });
            if (filters.date_range) {
                chips.push({ label: 'Date', value: filters.date_range });
            } else if (filters.start_date || filters.end_date) {
                chips.push({ label: 'Date', value: `${filters.start_date || '—'} → ${filters.end_date || '—'}` });
            }
            return chips;
        }

        function buildTotalsRow(totals, currencySymbol) {
            if (!totals) return null;
            const fmtMoney = (value) => `${currencySymbol}${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            return [
                '',
                '<strong>Totals</strong>',
                '',
                '',
                '',
                fmtMoney(totals.gross),
                fmtMoney(totals.sss),
                fmtMoney(totals.philhealth),
                fmtMoney(totals.pagibig),
                fmtMoney(totals.app_cut),
                `<span class="text-success fw-semibold">${fmtMoney(totals.net)}</span>`,
            ];
        }

        function buildTotalsChips(totals, currencySymbol) {
            if (!totals) return [];
            const fmt = (value) => `${currencySymbol}${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            return [
                { label: 'Gross', value: fmt(totals.gross) },
                { label: 'Net', value: fmt(totals.net) },
                { label: 'SSS', value: fmt(totals.sss) },
                { label: 'PhilHealth', value: fmt(totals.philhealth) },
                { label: 'Pag-IBIG', value: fmt(totals.pagibig) },
                { label: '3k Fitness App Cut', value: fmt(totals.app_cut) },
            ];
        }

        function buildRows(items, totals, currencySymbol) {
            const rows = (items || []).map((item) => ([
                item.id ?? '—',
                `<div class="fw">${item.name || '—'}</div><div class="muted">${item.email || ''}</div><div class="muted">Code: ${item.user_code || '—'}</div>`,
                item.role || '—',
                item.period || '—',
                `${item.hours || '0.00'} hrs`,
                `${currencySymbol}${item.gross || '0.00'}`,
                `${currencySymbol}${item.sss || '0.00'}`,
                `${currencySymbol}${item.philhealth || '0.00'}`,
                `${currencySymbol}${item.pagibig || '0.00'}`,
                `${currencySymbol}${item.app_cut || '0.00'}`,
                `<span class="text-success fw-semibold">${currencySymbol}${item.net || '0.00'}</span>`,
            ]));

            const totalsRow = buildTotalsRow(totals, currencySymbol);
            if (totalsRow) rows.push(totalsRow);
            return rows;
        }

        function renderPrintWindow(payload) {
            const rawItems = payload && payload.items ? payload.items : [];
            const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
            const filters = buildFilters(payload.filters || {});
            const currencySymbol = payload.currency_symbol || '₱';
            const headers = [
                '#',
                'Staff',
                'Role',
                'Period',
                'Hours',
                'Gross',
                'SSS',
                'PhilHealth',
                'Pag-IBIG',
                'App cut',
                'Net',
            ];
            const rows = buildRows(items, payload.totals, currencySymbol);
            const totalsChips = buildTotalsChips(payload.totals, currencySymbol);
            const filterChips = filters.concat(totalsChips);

            return window.PrintPreview
                ? PrintPreview.tryOpen(payload, headers, rows, filterChips)
                : false;
        }

        if (printButton) {
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

        const formatDateInput = (date) => {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const startOfWeek = (date) => {
            const current = new Date(date);
            current.setHours(0, 0, 0, 0);
            const day = current.getDay();
            const diff = (day + 6) % 7; // Monday
            current.setDate(current.getDate() - diff);
            return current;
        };

        const computeDateRange = (preset) => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const result = { start: '', end: '' };

            switch (preset) {
                case 'today': {
                    result.start = formatDateInput(today);
                    result.end = formatDateInput(today);
                    break;
                }
                case 'yesterday': {
                    const y = new Date(today);
                    y.setDate(y.getDate() - 1);
                    result.start = formatDateInput(y);
                    result.end = formatDateInput(y);
                    break;
                }
                case 'last_7': {
                    const start = new Date(today);
                    start.setDate(start.getDate() - 6);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(today);
                    break;
                }
                case 'last_30': {
                    const start = new Date(today);
                    start.setDate(start.getDate() - 29);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(today);
                    break;
                }
                case 'this_week': {
                    const start = startOfWeek(today);
                    const end = new Date(start);
                    end.setDate(end.getDate() + 6);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'last_week': {
                    const start = startOfWeek(today);
                    start.setDate(start.getDate() - 7);
                    const end = new Date(start);
                    end.setDate(end.getDate() + 6);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'this_month': {
                    const start = new Date(today.getFullYear(), today.getMonth(), 1);
                    const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'last_month': {
                    const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const end = new Date(today.getFullYear(), today.getMonth(), 0);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'this_quarter': {
                    const quarterStartMonth = Math.floor(today.getMonth() / 3) * 3;
                    const start = new Date(today.getFullYear(), quarterStartMonth, 1);
                    const end = new Date(today.getFullYear(), quarterStartMonth + 3, 0);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'last_quarter': {
                    const quarterStartMonth = Math.floor(today.getMonth() / 3) * 3 - 3;
                    const normalizedMonth = ((quarterStartMonth % 12) + 12) % 12;
                    const year = quarterStartMonth < 0 ? today.getFullYear() - 1 : today.getFullYear();
                    const start = new Date(year, normalizedMonth, 1);
                    const end = new Date(year, normalizedMonth + 3, 0);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'this_year': {
                    const start = new Date(today.getFullYear(), 0, 1);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(today);
                    break;
                }
                case 'last_year': {
                    const start = new Date(today.getFullYear() - 1, 0, 1);
                    const end = new Date(today.getFullYear() - 1, 11, 31);
                    result.start = formatDateInput(start);
                    result.end = formatDateInput(end);
                    break;
                }
                case 'all_time': {
                    result.start = '2000-01-01';
                    result.end = formatDateInput(today);
                    break;
                }
                default: {
                    result.start = startDateInput?.value || '';
                    result.end = endDateInput?.value || '';
                }
            }

            return result;
        };

        const applyDatePreset = (preset, preserveCustom = false) => {
            const isCustom = preset === 'custom';
            if (customDateRange) {
                customDateRange.classList.toggle('d-none', !isCustom);
            }
            if (isCustom && preserveCustom) {
                return;
            }
            const range = computeDateRange(preset);
            if (startDateInput && range.start) {
                startDateInput.value = range.start;
            }
            if (endDateInput && range.end) {
                endDateInput.value = range.end;
            }
        };

        if (datePresetSelect) {
            applyDatePreset(datePresetSelect.value || 'custom', true);
            datePresetSelect.addEventListener('change', () => {
                applyDatePreset(datePresetSelect.value || 'custom');
            });
        }

        focusButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const selected = this.dataset.focus || 'trainer';
                focusButtons.forEach(function (b) {
                    b.classList.remove('btn-dark', 'text-white');
                });
                this.classList.add('btn-dark', 'text-white');
                if (focusField) {
                    focusField.value = selected;
                }
            });
        });
    });
</script>
@endsection
