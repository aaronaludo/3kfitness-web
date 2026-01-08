@extends('layouts.admin')
@section('title', 'Sales Profit Report')

@section('styles')
<style>
    /* Minimal summary cards */
    .report-summary {
        --border: #e7e7ea;
        --text: #1f2933;
        --muted: #6b7280;
    }
    .summary-card {
        --accent: #d63e4b;
        --accent-soft: #fff5f5;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.05);
        min-height: 100%;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .summary-card.cost {
        --accent: #c2395a;
        --accent-soft: #fff4f8;
    }
    .summary-card.profit {
        --accent: #2f7a48;
        --accent-soft: #eef7f1;
    }
    .summary-card__header {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
    }
    .summary-card__title {
        font-weight: 700;
        margin: 2px 0 2px;
        color: var(--text);
    }
    .summary-card__subtitle {
        color: var(--muted);
        font-size: 0.9rem;
    }
    .summary-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-soft);
        color: var(--accent);
        border: 1px solid var(--border);
    }
    .pill-soft {
        margin-bottom: 10px;
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        background: var(--accent-soft);
        color: var(--accent);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }
    .pill-ghost {
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--muted);
        background: #fff;
        font-weight: 600;
    }
    .summary-amount {
        font-size: 1.9rem;
        font-weight: 800;
        color: var(--text);
    }
    .summary-card__value {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }
    .summary-card__math {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        background: var(--accent-soft);
        border: 1px dashed rgba(0, 0, 0, 0.05);
        border-radius: 12px;
        padding: 10px;
    }
    .math-chip {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        min-width: 160px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.03);
    }
    .math-chip__icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-soft);
        color: var(--accent);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }
    .math-chip__label {
        font-weight: 700;
        color: var(--text);
        line-height: 1.2;
    }
    .math-chip__value {
        color: var(--muted);
        font-weight: 600;
    }
    .math-symbol {
        font-weight: 800;
        color: var(--accent);
    }
    .summary-card__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: var(--muted);
        gap: 10px;
    }
    .summary-card__link {
        color: var(--accent);
        text-decoration: none;
        font-weight: 700;
    }
    .summary-card__link i {
        font-size: 0.95rem;
    }
    @media (max-width: 575.98px) {
        .summary-card__value {
            flex-direction: column;
            align-items: flex-start;
        }
        .math-chip {
            min-width: auto;
            flex: 1 1 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    @php
        $printFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_revenue' => round((float) ($summary['total_revenue'] ?? 0), 2),
            'total_cost' => round((float) ($summary['cost'] ?? 0), 2),
            'profit' => round((float) ($summary['profit'] ?? 0), 2),
            'currency' => $summary['currency'] ?? 'PHP',
        ];
        $buildPrintRow = function ($payment) {
            return [
                'id' => $payment['id'] ?? '—',
                'member' => $payment['member'] ?? '—',
                'user_code' => $payment['user_code'] ?? '—',
                'membership' => $payment['membership'] ?? '—',
                'amount' => $payment['amount'] ?? '0.00',
                'currency' => $payment['currency'] ?? '',
                'created_at' => $payment['created_at'] ?? '—',
            ];
        };
        $printRows = collect($membershipPayments->items() ?? [])->map($buildPrintRow)->values();
        $printAllRows = collect($membershipPaymentsAll ?? [])->map($buildPrintRow)->values();
        $printPayload = [
            'title' => 'Sales detailed reports',
            'generated_at' => now()->format('M d, Y g:i A'),
            'filters' => array_merge($printFilters, ['scope' => 'current']),
            'count' => $printRows->count(),
            'items' => $printRows,
        ];
        $printAllPayload = [
            'title' => 'Sales detailed reports (all pages)',
            'generated_at' => now()->format('M d, Y g:i A'),
            'filters' => array_merge($printFilters, ['scope' => 'all']),
            'count' => $printAllRows->count(),
            'items' => $printAllRows,
        ];
    @endphp
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
            <div>
                <h2 class="title mb-0">Sales Profit Report</h2>
                <p class="text-muted mb-0">Revenue, cost, and profit with membership payments in your selected range.</p>
            </div>
            <form action="#" method="POST" id="print-form">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <button
                    type="submit"
                    class="btn btn-danger d-flex align-items-center gap-2"
                    id="print-submit-button"
                    data-print='@json($printPayload)'
                    data-print-all='@json($printAllPayload)'
                    aria-label="Open printable/PDF view of filtered payments"
                >
                    <i class="fa-solid fa-print"></i>
                    <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    Print
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 report-summary">
        <div class="col-12 col-lg-4">
            <div class="summary-card revenue">
                <div class="summary-card__header">
                    <div>
                        <span class="pill-soft">Revenue mix</span>
                        <div class="summary-card__title h5 mb-1">Total revenue</div>
                        <div class="summary-card__subtitle">Memberships + App cut</div>
                    </div>
                    <div class="summary-icon">
                        <i class="fa-solid fa-sack-dollar fa-fw fa-lg"></i>
                    </div>
                </div>
                <div class="summary-card__value">
                    <div class="summary-amount mb-0">{{ $summary['currency'] }} {{ number_format($summary['total_revenue'] ?? 0, 2) }}</div>
                    <span class="pill-ghost">All channels</span>
                </div>
                <div class="summary-card__math">
                    <div class="math-chip">
                        <span class="math-chip__icon">
                            <i class="fa-solid fa-wallet fa-fw fa-lg"></i>
                        </span>
                        <div>
                            <div class="math-chip__label">Memberships</div>
                            <div class="math-chip__value">{{ $summary['currency'] }} {{ number_format($summary['membership_revenue'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <span class="math-symbol">+</span>
                    <div class="math-chip">
                        <span class="math-chip__icon">
                            <i class="fa-solid fa-scissors fa-fw fa-lg"></i>
                        </span>
                        <div>
                            <div class="math-chip__label">App cut</div>
                            <div class="math-chip__value">{{ $summary['currency'] }} {{ number_format($summary['app_cut_revenue'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <span class="math-symbol">=</span>
                    <div class="math-chip">
                        <span class="math-chip__icon">
                            <i class="fa-solid fa-sack-dollar fa-fw fa-lg"></i>
                        </span>
                        <div>
                            <div class="math-chip__label">Total</div>
                            <div class="math-chip__value">{{ $summary['currency'] }} {{ number_format($summary['total_revenue'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="summary-card cost">
                <div class="summary-card__header">
                    <div>
                        <span class="pill-soft">Cost</span>
                        <div class="summary-card__title h5 mb-1">Total cost</div>
                        <div class="summary-card__subtitle">Payrolls and other payouts</div>
                    </div>
                    <div class="summary-icon">
                        <i class="fa-solid fa-hand-holding-dollar fa-fw fa-lg"></i>
                    </div>
                </div>
                <div class="summary-card__value">
                    <div class="summary-amount mb-0">{{ $summary['currency'] }} {{ number_format($summary['cost'] ?? 0, 2) }}</div>
                    <span class="pill-ghost">Payouts</span>
                </div>
                <div class="summary-card__footer">
                    <span class="summary-card__subtitle mb-0">Track all outgoing payouts</span>
                    <a class="summary-card__link d-inline-flex align-items-center gap-2" href="{{ route('admin.payrolls.index', ['processed_from' => $startDate, 'processed_to' => $endDate]) }}">
                        <i class="fa-solid fa-arrow-up-right-from-square fa-fw"></i>
                        <span>View payrolls</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="summary-card profit">
                <div class="summary-card__header">
                    <div>
                        <span class="pill-soft">Net profit</span>
                        <div class="summary-card__title h5 mb-1">Profit</div>
                        <div class="summary-card__subtitle">Total revenue - Cost</div>
                    </div>
                    <div class="summary-icon">
                        <i class="fa-solid fa-chart-line fa-fw fa-lg"></i>
                    </div>
                </div>
                <div class="summary-card__value">
                    <div class="summary-amount mb-0">{{ $summary['currency'] }} {{ number_format($summary['profit'] ?? 0, 2) }}</div>
                    <span class="pill-ghost">After costs</span>
                </div>
                <div class="summary-card__math">
                    <div class="math-chip">
                        <span class="math-chip__icon">
                            <i class="fa-solid fa-sack-dollar fa-fw fa-lg"></i>
                        </span>
                        <div>
                            <div class="math-chip__label">Total revenue</div>
                            <div class="math-chip__value">{{ $summary['currency'] }} {{ number_format($summary['total_revenue'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <span class="math-symbol">−</span>
                    <div class="math-chip">
                        <span class="math-chip__icon">
                            <i class="fa-solid fa-hand-holding-dollar fa-fw fa-lg"></i>
                        </span>
                        <div>
                            <div class="math-chip__label">Cost</div>
                            <div class="math-chip__value">{{ $summary['currency'] }} {{ number_format($summary['cost'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <span class="math-symbol">=</span>
                    <div class="math-chip">
                        <span class="math-chip__icon">
                            <i class="fa-solid fa-chart-line fa-fw fa-lg"></i>
                        </span>
                        <div>
                            <div class="math-chip__label">Profit</div>
                            <div class="math-chip__value">{{ $summary['currency'] }} {{ number_format($summary['profit'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="tile tile-primary h-100 mb-0">
                <div class="tile-heading">Memberships Revenue</div>
                <div class="tile-body">
                    <i class="fa-solid fa-wallet"></i>
                    <h2 class="float-end mb-0">{{ $summary['currency'] }} {{ number_format($summary['membership_revenue'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="tile tile-primary h-100 mb-0">
                <div class="tile-heading">App Cut Revenue</div>
                <div class="tile-body">
                    <i class="fa-solid fa-scissors"></i>
                    <h2 class="float-end mb-0">{{ $summary['currency'] }} {{ number_format($summary['app_cut_revenue'], 2) }}</h2>
                </div>
            </div>
        </div>
    </div> --}}

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                            <h4 class="fw-semibold mb-1">Profit Report</h4>
                            <p class="text-muted mb-0">Pick a date window to refresh revenue, cost, profit, and payment details.</p>
                        </div>
                        <div class="text-end">
                            @php
                                $tableCounts = [
                                    'payments' => $membershipPayments->total(),
                                    'members' => $memberSalesTable->total(),
                                    'trainers' => $trainerSalesOrderTable->total(),
                                    'staffs' => $staffSalesOrderTable->total(),
                                    'memberships' => $membershipPlanTable->total(),
                                ];
                                $filtersTableCount = $tableCounts[$tableScope ?? 'payments'] ?? $membershipPayments->total();
                            @endphp
                            <span class="d-block text-muted small">{{ $summary['period_label'] }}</span>
                            <span class="d-block text-muted small">Showing {{ $filtersTableCount }} record{{ $filtersTableCount === 1 ? '' : 's' }}</span>
                        </div>
                    </div>

                    <form action="{{ route('admin.sales.reports') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label class="form-label text-muted small mb-1" for="date_preset">Date range</label>
                            <select id="date_preset" name="date_preset" class="form-select rounded-pill">
                                <option value="today" {{ ($datePreset ?? 'last_30') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ ($datePreset ?? 'last_30') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="last_7" {{ ($datePreset ?? 'last_30') === 'last_7' ? 'selected' : '' }}>Last 7 Days</option>
                                <option value="last_30" {{ ($datePreset ?? 'last_30') === 'last_30' ? 'selected' : '' }}>Last 30 Days</option>
                                <option value="this_week" {{ ($datePreset ?? 'last_30') === 'this_week' ? 'selected' : '' }}>This Week</option>
                                <option value="last_week" {{ ($datePreset ?? 'last_30') === 'last_week' ? 'selected' : '' }}>Last Week</option>
                                <option value="this_month" {{ ($datePreset ?? 'last_30') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="last_month" {{ ($datePreset ?? 'last_30') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                <option value="this_quarter" {{ ($datePreset ?? 'last_30') === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                                <option value="last_quarter" {{ ($datePreset ?? 'last_30') === 'last_quarter' ? 'selected' : '' }}>Last Quarter</option>
                                <option value="this_year" {{ ($datePreset ?? 'last_30') === 'this_year' ? 'selected' : '' }}>This Year</option>
                                <option value="last_year" {{ ($datePreset ?? 'last_30') === 'last_year' ? 'selected' : '' }}>Last Year</option>
                                <option value="all_time" {{ ($datePreset ?? 'last_30') === 'all_time' ? 'selected' : '' }}>All Time</option>
                                <option value="custom" {{ ($datePreset ?? 'last_30') === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label text-muted small mb-1" for="table_scope">Table view</label>
                            <select id="table_scope" name="table_scope" class="form-select rounded-pill">
                                <option value="payments" {{ ($tableScope ?? 'payments') === 'payments' ? 'selected' : '' }}>Membership payments</option>
                                <option value="members" {{ ($tableScope ?? '') === 'members' ? 'selected' : '' }}>Members</option>
                                <option value="trainers" {{ ($tableScope ?? '') === 'trainers' ? 'selected' : '' }}>Trainers</option>
                                <option value="staffs" {{ ($tableScope ?? '') === 'staffs' ? 'selected' : '' }}>Staffs</option>
                                <option value="memberships" {{ ($tableScope ?? '') === 'memberships' ? 'selected' : '' }}>Memberships</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-4 d-flex gap-2 justify-content-lg-end">
                            <a href="{{ route('admin.sales.reports') }}" class="btn btn-link text-decoration-none text-muted px-0">
                                Reset
                            </a>
                            <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                Apply
                            </button>
                        </div>
                        <div class="col-12 {{ ($datePreset ?? '') === 'custom' ? '' : 'd-none' }}" id="custom-date-range">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small mb-1" for="start_date">Start date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control rounded-pill" value="{{ ($datePreset ?? '') === 'custom' ? $startDate : '' }}">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted small mb-1" for="end_date">End date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control rounded-pill" value="{{ ($datePreset ?? '') === 'custom' ? $endDate : '' }}">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            @php
                                $tableTitles = [
                                    'payments' => 'Membership payments',
                                    'members' => 'Members',
                                    'trainers' => 'Trainers',
                                    'staffs' => 'Staffs',
                                    'memberships' => 'Memberships',
                                ];
                                $currentTable = $tableTitles[$tableScope ?? 'payments'] ?? 'Membership payments';
                                $recordCounts = [
                                    'payments' => $membershipPayments->total(),
                                    'members' => $memberSalesTable->total(),
                                    'trainers' => $trainerSalesOrderTable->total(),
                                    'staffs' => $staffSalesOrderTable->total(),
                                    'memberships' => $membershipPlanTable->total(),
                            ];
                            $currentCount = $recordCounts[$tableScope ?? 'payments'] ?? $membershipPayments->total();
                        @endphp
                        <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small">{{ $currentTable }}</span>
                        <div class="text-muted small">Showing data for the selected view and date range</div>
                    </div>
                    <div class="text-muted small">
                        Showing {{ $currentCount }} record{{ $currentCount === 1 ? '' : 's' }}
                    </div>
                </div>
                    @if(($tableScope ?? 'payments') === 'memberships')
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Plan</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Sales</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($membershipPlanTable ?? [] as $planRow)
                                        <tr>
                                            <td>{{ $planRow['name'] ?? '—' }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($planRow['price'] ?? 0), 2) }}</td>
                                            <td class="text-end">{{ $planRow['sales'] ?? 0 }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($planRow['revenue'] ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No membership sales in this period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($membershipPlanTable instanceof \Illuminate\Pagination\AbstractPaginator)
                            <div class="mt-3">
                                {{ $membershipPlanTable->links() }}
                            </div>
                        @endif
                    @elseif(($tableScope ?? 'payments') === 'members')
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member</th>
                                        <th class="text-end">Sales</th>
                                        <th class="text-end">Total</th>
                                        <th>Last plan</th>
                                        <th class="text-end">Last sale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($memberSalesTable ?? [] as $memberRow)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $memberRow['name'] ?? '—' }}</div>
                                                <div class="text-muted small">Code: {{ $memberRow['user_code'] ?? '—' }}</div>
                                            </td>
                                            <td class="text-end">{{ $memberRow['sales'] ?? 0 }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($memberRow['total'] ?? 0), 2) }}</td>
                                            <td>{{ $memberRow['last_membership'] ?? '—' }}</td>
                                            <td class="text-end">{{ $memberRow['last_payment_at'] ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No member sales found for this window.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($memberSalesTable instanceof \Illuminate\Pagination\AbstractPaginator)
                            <div class="mt-3">
                                {{ $memberSalesTable->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    @elseif(($tableScope ?? 'payments') === 'staffs')
                        @php $staffPaymentModals = []; @endphp
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Staff</th>
                                        <th class="text-end">Runs</th>
                                        <th class="text-end">Gross</th>
                                        <th class="text-end">Net</th>
                                        <th class="text-end">Membership payments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($staffSalesOrderTable ?? [] as $staffRow)
                                        @php
                                            $mp = $staffRow['membership_payments'] ?? ['count' => 0, 'total' => 0, 'currency' => $summary['currency'] ?? 'PHP', 'items' => collect()];
                                            $mpItems = collect($mp['items'] ?? []);
                                            $mpId = 'staff-mp-report-' . ($staffRow['id'] ?? $loop->iteration);
                                            $staffPaymentModals[] = [
                                                'id' => $mpId,
                                                'name' => $staffRow['name'] ?? 'Staff',
                                                'currency' => $mp['currency'] ?? ($summary['currency'] ?? 'PHP'),
                                                'count' => $mp['count'] ?? 0,
                                                'total' => $mp['total'] ?? 0,
                                                'items' => $mpItems,
                                            ];
                                        @endphp
                                        <tr>
                                            <td class="text-muted">
                                                {{ $staffSalesOrderTable instanceof \Illuminate\Pagination\AbstractPaginator ? $staffSalesOrderTable->firstItem() + $loop->index : $loop->iteration }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $staffRow['name'] ?? '—' }}</div>
                                                <div class="text-muted small">Code: {{ $staffRow['user_code'] ?? '—' }}</div>
                                            </td>
                                            <td class="text-end">{{ $staffRow['run_count'] ?? 0 }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($staffRow['gross'] ?? 0), 2) }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($staffRow['net'] ?? 0), 2) }}</td>
                                            <td class="text-end">
                                                @if(($mp['count'] ?? 0) > 0)
                                                    <div>{{ $mp['currency'] ?? ($summary['currency'] ?? 'PHP') }} {{ number_format((float) ($mp['total'] ?? 0), 2) }}</div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#{{ $mpId }}">
                                                        View {{ $mp['count'] ?? 0 }}
                                                    </button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No staff payroll runs in this window.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($staffSalesOrderTable instanceof \Illuminate\Pagination\AbstractPaginator)
                            <div class="mt-3">
                                {{ $staffSalesOrderTable->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                        @foreach($staffPaymentModals as $modal)
                            @php $modalItems = collect($modal['items'] ?? []); @endphp
                            <div class="modal fade" id="{{ $modal['id'] ?? '' }}" tabindex="-1" aria-labelledby="{{ $modal['id'] ?? '' }}-label" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header">
                                            <div>
                                                <h5 class="modal-title fw-semibold mb-0" id="{{ $modal['id'] ?? '' }}-label">Membership payments approved by {{ $modal['name'] ?? 'Staff' }}</h5>
                                                <p class="text-muted small mb-0">Approved payments within the selected window</p>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-light text-dark">Count: {{ $modal['count'] ?? 0 }}</span>
                                                @if(($modal['count'] ?? 0) > 0)
                                                    <span class="badge bg-success-subtle text-success">Total: {{ $modal['currency'] ?? ($summary['currency'] ?? 'PHP') }} {{ number_format((float) ($modal['total'] ?? 0), 2) }}</span>
                                                @endif
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Member</th>
                                                            <th>Membership</th>
                                                            <th class="text-end">Amount</th>
                                                            <th class="text-end">Approved</th>
                                                            <th class="text-end">Expires</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($modalItems as $payment)
                                                            <tr>
                                                                <td class="text-muted">#{{ $payment['id'] }}</td>
                                                                <td>
                                                                    <div class="fw-semibold">{{ $payment['member_name'] ?? '—' }}</div>
                                                                    <div class="text-muted small">Code: {{ $payment['member_code'] ?? '—' }}</div>
                                                                </td>
                                                                <td>{{ $payment['membership'] ?? '—' }}</td>
                                                                <td class="text-end">{{ $payment['currency'] ?? ($summary['currency'] ?? 'PHP') }} {{ number_format((float) ($payment['price'] ?? 0), 2) }}</td>
                                                                <td class="text-end">{{ $payment['created_at'] ?? '—' }}</td>
                                                                <td class="text-end">{{ $payment['expiration_at'] ?? '—' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="text-center text-muted small">No approved membership payments for this staff.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @elseif(($tableScope ?? 'payments') === 'trainers')
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Trainer</th>
                                        <th class="text-end">Runs</th>
                                        <th class="text-end">Gross</th>
                                        <th class="text-end">Net</th>
                                        <th class="text-end">App cut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trainerSalesOrderTable ?? [] as $trainerRow)
                                        <tr>
                                            <td class="text-muted">
                                                {{ $trainerSalesOrderTable instanceof \Illuminate\Pagination\AbstractPaginator ? $trainerSalesOrderTable->firstItem() + $loop->index : $loop->iteration }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $trainerRow['name'] ?? '—' }}</div>
                                                <div class="text-muted small">Code: {{ $trainerRow['user_code'] ?? '—' }}</div>
                                            </td>
                                            <td class="text-end">{{ $trainerRow['run_count'] ?? 0 }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($trainerRow['gross'] ?? 0), 2) }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($trainerRow['net'] ?? 0), 2) }}</td>
                                            <td class="text-end">{{ $summary['currency'] ?? 'PHP' }} {{ number_format((float) ($trainerRow['app_cut'] ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No trainer payroll runs in this window.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($trainerSalesOrderTable instanceof \Illuminate\Pagination\AbstractPaginator)
                            <div class="mt-3">
                                {{ $trainerSalesOrderTable->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th>Membership</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Approved Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($membershipPayments as $payment)
                                        <tr>
                                            <td>{{ $payment['id'] }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $payment['member'] }}</div>
                                                <div class="text-muted small">{{ $payment['user_code'] }}</div>
                                            </td>
                                            <td>{{ $payment['membership'] }}</td>
                                            <td class="text-end">{{ $payment['currency'] }} {{ $payment['amount'] }}</td>
                                            <td class="text-end">{{ $payment['created_at'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No payments found for this range.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $membershipPayments->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const printButton = document.getElementById('print-submit-button');
        const printForm = document.getElementById('print-form');
        const printLoader = document.getElementById('print-loader');
        const datePresetSelect = document.getElementById('date_preset');
        const customDateRange = document.getElementById('custom-date-range');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        function buildFilters(filters) {
            const chips = [];
            const fmt = (value) => {
                const num = Number(value) || 0;
                const currency = filters.currency || '';
                return `${currency} ${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            };
            if (filters.start_date || filters.end_date) {
                chips.push({
                    label: 'Date range',
                    value: `${filters.start_date || 'Any'} → ${filters.end_date || 'Any'}`,
                });
            }
            if (filters.total_revenue !== undefined) {
                chips.push({ label: 'Total revenue', value: fmt(filters.total_revenue) });
            }
            if (filters.total_cost !== undefined) {
                chips.push({ label: 'Total cost', value: fmt(filters.total_cost) });
            }
            if (filters.profit !== undefined) {
                chips.push({ label: 'Profit', value: fmt(filters.profit) });
            }
            return chips;
        }

        function buildRows(items) {
            return (items || []).map((item) => ([
                item.id ?? '—',
                `<div class="fw">${item.member || '—'}</div><div class="muted">${item.user_code || ''}</div>`,
                item.membership || '—',
                `${item.currency || ''} ${item.amount || '0.00'}`,
                item.created_at || '—',
            ]));
        }

        function renderPrintWindow(payload) {
            const rawItems = payload && payload.items ? payload.items : [];
            const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
            const filters = buildFilters(payload.filters || {});
            const headers = ['#', 'Member', 'Membership', 'Amount', 'Date'];
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

                printButton.disabled = false;
                if (printLoader) printLoader.classList.add('d-none');

                if (!handled && printForm) {
                    printForm.submit();
                }
            });
        }

        if (datePresetSelect && customDateRange) {
            const toggleCustomDates = () => {
                const isCustom = datePresetSelect.value === 'custom';
                customDateRange.classList.toggle('d-none', !isCustom);
                if (startDateInput) startDateInput.disabled = !isCustom;
                if (endDateInput) endDateInput.disabled = !isCustom;
            };

            datePresetSelect.addEventListener('change', toggleCustomDates);
            toggleCustomDates();
        }
    });
</script>
@endsection
