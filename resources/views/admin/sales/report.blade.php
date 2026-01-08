@extends('layouts.admin')
@section('title', 'Sales Report')

@section('styles')
<style>
    /* Minimal summary cards (copied from sales detailed reports) */
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
    /* Filter menu (kept from original) */
    .filter-menu {
        position: relative;
    }
    .filter-menu__toggle {
        width: 100%;
        justify-content: space-between;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 12px;
        padding: 10px 12px;
    }
    .filter-menu__panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 30;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.12);
        min-width: 680px;
        border: 1px solid rgba(0,0,0,0.08);
        display: none;
    }
    .filter-menu__panel.show {
        display: grid;
        grid-template-columns: 180px 220px 1fr;
        grid-template-rows: 1fr;
        align-items: start;
        gap: 0;
    }
    .filter-menu__list {
        list-style: none;
        margin: 0;
        padding: 8px;
        border-right: 1px solid rgba(0,0,0,0.06);
        grid-column: 1;
        grid-row: 1;
    }
    .filter-menu__item {
        width: 100%;
        text-align: left;
        border: none;
        background: transparent;
        padding: 10px 12px;
        border-radius: 12px;
        font-weight: 700;
        color: #1f2a37;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .filter-menu__item.active, .filter-menu__item:hover {
        background: #e9f0f7;
        color: #0f4c75;
    }
    .filter-menu__sub {
        list-style: none;
        margin: 0;
        padding: 10px;
        grid-column: 2;
        grid-row: 1;
        display: grid;
        gap: 8px;
    }
    .filter-menu__sub li {
        margin-bottom: 6px;
    }
    .filter-menu__sub button {
        width: 100%;
        border: 1px solid rgba(0,0,0,0.05);
        background: #edf2f7;
        color: #0f4c75;
        border-radius: 12px;
        padding: 12px 12px;
        font-weight: 800;
        text-align: center;
        box-shadow: 0 6px 12px rgba(0,0,0,0.05);
    }
    .filter-menu__sub button.active {
        background: #0f4c75;
        color: #fff;
        box-shadow: 0 10px 20px rgba(15, 76, 117, 0.25);
    }
    .filter-menu__sub.disabled {
        opacity: 0.45;
        pointer-events: none;
    }
    .filter-menu__sub.scrollable {
        max-height: calc(7 * 48px);
        overflow-y: auto;
    }
    .filter-menu__sub--dates {
        grid-column: 3;
        grid-row: 1;
        max-height: 420px;
        overflow-y: auto;
        background: #f8fafc;
        border-left: 1px solid rgba(0,0,0,0.06);
        border-radius: 0 14px 14px 0;
        padding: 12px;
        display: grid;
        gap: 10px;
    }
    @media (max-width: 575.98px) {
        .report-hero {
            padding: 18px;
        }
        .summary-value {
            font-size: 1.5rem;
        }
    .filter-menu__panel.show {
        grid-template-columns: 1fr;
        width: 100%;
    }
    .filter-menu__list {
        border-right: none;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .filter-menu__sub {
        grid-column: 1;
    }
    .filter-menu__sub--dates {
        grid-column: 1;
        grid-row: 3;
        border-left: none;
        border-top: 1px solid rgba(0,0,0,0.06);
        border-radius: 0 0 14px 14px;
    }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
            <div>
                <h2 class="title mb-0">Sales Reports</h2>
                <p class="text-muted mb-0">Default view loads Member — Most Sales for the last 30 days. Adjust filters to change focus or date ranges.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="pill-soft">Preset {{ $datePresetLabel ?? 'All Time' }}</span>
                <span class="pill-soft">Range {{ $rangeLabel }}</span>
            </div>
        </div>
    </div>

    @php
        $isTrainerFocus = $focus === 'trainer';
    @endphp
    <div class="row g-3 mb-4 report-summary">
        @if(!$isTrainerFocus)
            <div class="col-12 col-lg-6">
                <div class="summary-card">
                    <div class="summary-card__header">
                        <div>
                            <span class="pill-soft">Memberships</span>
                            <div class="summary-card__title h5 mb-1">Membership revenue</div>
                            <div class="summary-card__subtitle">Total membership sales</div>
                        </div>
                        <div class="summary-icon">
                            <i class="fa-solid fa-wallet fa-fw fa-lg"></i>
                        </div>
                    </div>
                    <div class="summary-card__value">
                        <div class="summary-amount mb-0">{{ $currency }} {{ number_format($summary['membership_revenue'] ?? 0, 2) }}</div>
                        <span class="pill-ghost">{{ $summary['membership_count'] ?? 0 }} sales</span>
                    </div>
                </div>
            </div>
        @endif
        @if($isTrainerFocus)
            <div class="col-12">
                <div class="summary-card cost">
                    <div class="summary-card__header">
                        <div>
                            <span class="pill-soft">Commission</span>
                            <div class="summary-card__title h5 mb-1">Class commission</div>
                            <div class="summary-card__subtitle">Trainer commissions for the period</div>
                        </div>
                        <div class="summary-icon">
                            <i class="fa-solid fa-ranking-star fa-fw fa-lg"></i>
                        </div>
                    </div>
                    <div class="summary-card__value">
                        <div class="summary-amount mb-0">{{ $currency }} {{ number_format($summary['class_commission'] ?? 0, 2) }}</div>
                        <span class="pill-ghost">Included in totals</span>
                    </div>
                </div>
            </div>
        @endif
        @if(!$isTrainerFocus)
            <div class="col-12 col-lg-6">
                <div class="summary-card profit">
                    <div class="summary-card__header">
                        <div>
                            <span class="pill-soft">Total</span>
                            <div class="summary-card__title h5 mb-1">Total sales</div>
                            <div class="summary-card__subtitle">Count of sales in this view</div>
                        </div>
                        <div class="summary-icon">
                            <i class="fa-solid fa-layer-group fa-fw fa-lg"></i>
                        </div>
                    </div>
                    <div class="summary-card__value">
                        <div class="summary-amount mb-0">{{ number_format((float) ($summary['total_sales_count'] ?? 0)) }}</div>
                        <span class="pill-ghost">Sales</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                    <h4 class="fw-semibold mb-1">Sales report filters</h4>
                    <p class="text-muted mb-0">Adjust focus, membership, and date range. Default is current year.</p>
                </div>
                <div class="text-end">
                    <span class="d-block text-muted small">Year {{ $rangeYear }}</span>
                    <span class="d-block text-muted small">Range {{ $rangeLabel }}</span>
                </div>
            </div>

            <form action="{{ route('admin.sales.report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Search for all</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass input-icon"></i>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control search-input"
                            placeholder="Members, trainers, staff, memberships"
                            value="{{ $searchTerm }}"
                        />
                    </div>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Filter preset</label>
                    <div class="filter-menu">
                        <button type="button" class="btn btn-outline-secondary w-100 filter-menu__toggle" id="filter-menu-toggle">
                            <span id="filter-menu-label">
                                @if(in_array($focus, ['member','trainer','staff']))
                                    {{ ucfirst($focus) }} — {{ $order === 'least' ? 'Least Sales' : 'Most Sales' }} | Date — {{ $datePresetLabel ?? 'Custom Date Range' }}
                                @elseif($focus === 'membership')
                                    Memberships — {{ $selectedMembershipLabel ?? 'All Memberships' }} | Date — {{ $datePresetLabel ?? 'Custom Date Range' }}
                                @else
                                    Date — {{ $datePresetLabel ?? 'Custom Date Range' }}
                                @endif
                            </span>
                            <i class="fa-solid fa-chevron-down small"></i>
                        </button>
                        <div class="filter-menu__panel" id="filter-menu-panel">
                            <ul class="filter-menu__list">
                                <li><button type="button" class="filter-menu__item {{ $focus === 'member' ? 'active' : '' }}" data-focus="member" data-has-order="true">Member</button></li>
                                <li><button type="button" class="filter-menu__item {{ $focus === 'trainer' ? 'active' : '' }}" data-focus="trainer" data-has-order="true">Trainer</button></li>
                                <li><button type="button" class="filter-menu__item {{ $focus === 'staff' ? 'active' : '' }}" data-focus="staff" data-has-order="true">Staff</button></li>
                                <li><button type="button" class="filter-menu__item {{ $focus === 'membership' ? 'active' : '' }}" data-focus="membership" data-has-order="false">Memberships</button></li>
                                <li><button type="button" class="filter-menu__item {{ $focus === 'date' ? 'active' : '' }}" data-focus="date" data-has-order="false">Date</button></li>
                            </ul>
                            <ul class="filter-menu__sub {{ in_array($focus, ['member','trainer','staff']) ? '' : 'd-none' }}" id="sub-orders">
                                <li><button type="button" class="{{ $order === 'least' ? '' : 'active' }}" data-order="most">Most Sales</button></li>
                                <li><button type="button" class="{{ $order === 'least' ? 'active' : '' }}" data-order="least">Least Sales</button></li>
                            </ul>
                            <ul class="filter-menu__sub scrollable {{ $focus === 'membership' ? '' : 'd-none' }}" id="sub-memberships">
                                <li><button type="button" class="{{ empty($selectedMembershipId) ? 'active' : '' }}" data-membership-id="">All Memberships</button></li>
                                @foreach($membershipOptions ?? [] as $option)
                                    <li>
                                        <button
                                            type="button"
                                            class="{{ (string) $selectedMembershipId === (string) $option->id ? 'active' : '' }}"
                                            data-membership-id="{{ $option->id }}"
                                            data-membership-label="{{ $option->name }}"
                                        >
                                            {{ $option->name }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <ul class="filter-menu__sub scrollable filter-menu__sub--dates" id="sub-dates">
                                <li><button type="button" class="{{ $datePreset === 'today' ? 'active' : '' }}" data-date-preset="today" data-date-label="Today">Today</button></li>
                                <li><button type="button" class="{{ $datePreset === 'yesterday' ? 'active' : '' }}" data-date-preset="yesterday" data-date-label="Yesterday">Yesterday</button></li>
                                <li><button type="button" class="{{ $datePreset === 'last_7' ? 'active' : '' }}" data-date-preset="last_7" data-date-label="Last 7 Days">Last 7 Days</button></li>
                                <li><button type="button" class="{{ $datePreset === 'last_30' ? 'active' : '' }}" data-date-preset="last_30" data-date-label="Last 30 Days">Last 30 Days</button></li>
                                <li><button type="button" class="{{ $datePreset === 'this_week' ? 'active' : '' }}" data-date-preset="this_week" data-date-label="This Week">This Week</button></li>
                                <li><button type="button" class="{{ $datePreset === 'last_week' ? 'active' : '' }}" data-date-preset="last_week" data-date-label="Last Week">Last Week</button></li>
                                <li><button type="button" class="{{ $datePreset === 'this_month' ? 'active' : '' }}" data-date-preset="this_month" data-date-label="This Month">This Month</button></li>
                                <li><button type="button" class="{{ $datePreset === 'last_month' ? 'active' : '' }}" data-date-preset="last_month" data-date-label="Last Month">Last Month</button></li>
                                <li><button type="button" class="{{ $datePreset === 'this_quarter' ? 'active' : '' }}" data-date-preset="this_quarter" data-date-label="This Quarter">This Quarter</button></li>
                                <li><button type="button" class="{{ $datePreset === 'last_quarter' ? 'active' : '' }}" data-date-preset="last_quarter" data-date-label="Last Quarter">Last Quarter</button></li>
                                <li><button type="button" class="{{ $datePreset === 'this_year' ? 'active' : '' }}" data-date-preset="this_year" data-date-label="This Year">This Year</button></li>
                                <li><button type="button" class="{{ $datePreset === 'last_year' ? 'active' : '' }}" data-date-preset="last_year" data-date-label="Last Year">Last Year</button></li>
                                <li><button type="button" class="{{ $datePreset === 'all_time' ? 'active' : '' }}" data-date-preset="all_time" data-date-label="All Time">All Time</button></li>
                                <li><button type="button" class="{{ $datePreset === 'custom' ? 'active' : '' }}" data-date-preset="custom" data-date-label="Custom Date Range">Custom Date Range</button></li>
                            </ul>
                        </div>
                        <input type="hidden" name="focus" id="focus-field" value="{{ $focus }}">
                        <input type="hidden" name="order" id="order-field" value="{{ $order ?? 'most' }}">
                        <input type="hidden" name="membership_id" id="membership-field" value="{{ $selectedMembershipId }}">
                        <input type="hidden" name="date_preset" id="date-preset-field" value="{{ $datePreset }}">
                    </div>
                </div>
                <div class="col-12 col-lg-4 {{ $datePreset === 'custom' ? '' : 'd-none' }}" id="custom-date-row">
                    <div class="row g-3">
                        <div class="col-6 col-md-6">
                            <label for="start-date" class="form-label">Start date</label>
                            <input type="date" id="start-date" name="start_date" class="form-control" value="{{ $datePreset === 'custom' ? ($startDate ?? '') : '' }}">
                        </div>
                        <div class="col-6 col-md-6">
                            <label for="start-time" class="form-label">Start time</label>
                            <input type="time" id="start-time" name="start_time" class="form-control" value="{{ $datePreset === 'custom' ? ($startTime ?? '') : '' }}">
                        </div>
                        <div class="col-6 col-md-6">
                            <label for="end-date" class="form-label">End date</label>
                            <input type="date" id="end-date" name="end_date" class="form-control" value="{{ $datePreset === 'custom' ? ($endDate ?? '') : '' }}">
                        </div>
                        <div class="col-6 col-md-6">
                            <label for="end-time" class="form-label">End time</label>
                            <input type="time" id="end-time" name="end_time" class="form-control" value="{{ $datePreset === 'custom' ? ($endTime ?? '') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-auto d-flex gap-3">
                    <a href="{{ route('admin.sales.report') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                    <button type="submit" class="btn btn-primary w-100 w-lg-auto">
                        <i class="fa-solid fa-filter me-2"></i>Apply filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $rowsTotal = $focusRows instanceof \Illuminate\Pagination\AbstractPaginator
            ? $focusRows->total()
            : (is_countable($focusRows) ? count($focusRows) : 0);
    @endphp

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="fw-semibold mb-1">Results</h5>
                    <p class="text-muted small mb-2">Styled to match the Sales Profit Report table.</p>
                    <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small">{{ ucfirst($focus) }}</span>
            </div>
            <div class="text-muted small text-end">
                <div><i class="fa-solid fa-coins me-1"></i>{{ $currency }} currency</div>
                <div>Showing {{ $rowsTotal }} record{{ $rowsTotal === 1 ? '' : 's' }}</div>
            </div>
        </div>
        @if($focus === 'trainer')
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted">#</th>
                            <th>Trainer</th>
                            <th class="text-end">Class Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($focusRows as $row)
                            @php
                                $indexBase = $focusRows instanceof \Illuminate\Pagination\AbstractPaginator ? $focusRows->firstItem() : 1;
                                $rowNumber = ($indexBase ?? 1) + $loop->index;
                                $label = $row['label'] ?? '—';
                                $labelParts = [];
                                if (preg_match('/^(.*)\\s*\\(([^)]+)\\)$/', $label, $matches)) {
                                    $labelParts = [$matches[1] ?? $label, $matches[2] ?? null];
                                }
                                $name = $labelParts[0] ?? $label;
                                $code = $labelParts[1] ?? null;
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $rowNumber }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $name }}</div>
                                    @if($code)
                                        <div class="text-muted small">Code: {{ $code }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $currency }} {{ number_format((float) ($row['app_cut'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No data for this view. Try widening your date range or clearing filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($focus === 'staff')
            @php $staffPaymentModals = []; @endphp
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted">#</th>
                            <th>Staff</th>
                            <th class="text-end">Membership payments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($focusRows as $row)
                            @php
                                $indexBase = $focusRows instanceof \Illuminate\Pagination\AbstractPaginator ? $focusRows->firstItem() : 1;
                                $rowNumber = ($indexBase ?? 1) + $loop->index;
                                $label = $row['label'] ?? '—';
                                $labelParts = [];
                                if (preg_match('/^(.*)\\s*\\(([^)]+)\\)$/', $label, $matches)) {
                                    $labelParts = [$matches[1] ?? $label, $matches[2] ?? null];
                                }
                                $name = $labelParts[0] ?? $label;
                                $code = $labelParts[1] ?? null;
                                $mp = $row['membership_payments'] ?? ['count' => 0, 'total' => 0, 'currency' => $currency, 'items' => collect()];
                                $mpItems = collect($mp['items'] ?? []);
                                $mpId = 'staff-mp-report-' . $rowNumber;
                                $staffPaymentModals[] = [
                                    'id' => $mpId,
                                    'name' => $name,
                                    'currency' => $mp['currency'] ?? $currency,
                                    'count' => $mp['count'] ?? 0,
                                    'total' => $mp['total'] ?? 0,
                                    'items' => $mpItems,
                                ];
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $rowNumber }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $name }}</div>
                                    @if($code)
                                        <div class="text-muted small">Code: {{ $code }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(($mp['count'] ?? 0) > 0)
                                        <div class="fw-semibold">{{ $mp['currency'] ?? $currency }} {{ number_format((float) ($mp['total'] ?? 0), 2) }}</div>
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
                                <td colspan="6" class="text-center text-muted">
                                    No data for this view. Try widening your date range or clearing filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($focusRows instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="mt-3">
                    {{ $focusRows->withQueryString()->links('pagination::bootstrap-5') }}
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
                                        <span class="badge bg-success-subtle text-success">Total: {{ $modal['currency'] ?? $currency }} {{ number_format((float) ($modal['total'] ?? 0), 2) }}</span>
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
                                                    <td class="text-end">{{ $payment['currency'] ?? $currency }} {{ number_format((float) ($payment['price'] ?? 0), 2) }}</td>
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
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Focus</th>
                            <th>Type</th>
                            <th class="text-center">Sales</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Last Sale</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($focusRows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small">
                                        {{ $row['type'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-center fw-semibold">{{ $row['sales'] ?? 0 }}</td>
                                <td class="text-end fw-semibold">{{ $currency }} {{ number_format((float) ($row['revenue'] ?? 0), 2) }}</td>
                                <td class="text-end text-muted">{{ $row['last_sale'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No data for this view. Try widening your date range or clearing filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
        @if($focusRows instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="mt-3">
                {{ $focusRows->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('filter-menu-toggle');
        var panel = document.getElementById('filter-menu-panel');
        var labelEl = document.getElementById('filter-menu-label');
        var focusInput = document.getElementById('focus-field');
        var orderInput = document.getElementById('order-field');
        var membershipInput = document.getElementById('membership-field');
        var datePresetInput = document.getElementById('date-preset-field');
        var startDateInput = document.getElementById('start-date');
        var endDateInput = document.getElementById('end-date');
        var startTimeInput = document.getElementById('start-time');
        var endTimeInput = document.getElementById('end-time');
        var customDateRow = document.getElementById('custom-date-row');
        var itemButtons = Array.from(document.querySelectorAll('.filter-menu__item'));
        var orderButtons = Array.from(document.querySelectorAll('#sub-orders button'));
        var membershipButtons = Array.from(document.querySelectorAll('#sub-memberships button'));
        var dateButtons = Array.from(document.querySelectorAll('#sub-dates button'));
        var subOrders = document.getElementById('sub-orders');
        var subMemberships = document.getElementById('sub-memberships');

        if (!toggle || !panel || !focusInput || !orderInput || !membershipInput || !datePresetInput) return;

        var membershipLabels = @json(($membershipOptions ?? collect())->pluck('name', 'id'));
        var datePresetLabels = @json($datePresetLabels ?? []);

        var currentFocus = focusInput.value || 'member';
        var currentOrder = orderInput.value || 'most';
        var currentMembership = membershipInput.value || '';
        var currentDatePreset = datePresetInput.value || 'this_year';

        var updateLabel = function () {
            var text = '';
            var dateLabel = datePresetLabels[currentDatePreset] || 'Custom Date Range';
            if (['member', 'trainer', 'staff'].indexOf(currentFocus) !== -1) {
                text = currentFocus.charAt(0).toUpperCase() + currentFocus.slice(1) + ' — ' + (currentOrder === 'least' ? 'Least Sales' : 'Most Sales') + ' | Date — ' + dateLabel;
            } else if (currentFocus === 'membership') {
                var membershipName = currentMembership && membershipLabels[currentMembership] ? membershipLabels[currentMembership] : 'All Memberships';
                text = 'Memberships — ' + membershipName + ' | Date — ' + dateLabel;
            } else {
                text = 'Date — ' + dateLabel;
            }
            labelEl.textContent = text;
        };

        var toggleCustomDateInputs = function (preset) {
            if (!customDateRow) return;
            if (preset === 'custom') {
                customDateRow.classList.remove('d-none');
            } else {
                customDateRow.classList.add('d-none');
            }
        };

        var clearCustomInputs = function () {
            if (startDateInput) startDateInput.value = '';
            if (endDateInput) endDateInput.value = '';
            if (startTimeInput) startTimeInput.value = '';
            if (endTimeInput) endTimeInput.value = '';
        };

        var closePanel = function () {
            panel.classList.remove('show');
        };

        var showSub = function (focus, hasOrder) {
            subOrders.classList.add('d-none');
            subMemberships.classList.add('d-none');

            if (hasOrder) {
                subOrders.classList.remove('d-none');
            } else if (focus === 'membership') {
                subMemberships.classList.remove('d-none');
            }
        };

        var setFocus = function (focus, hasOrder) {
            currentFocus = focus;
            focusInput.value = focus;
            itemButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-focus') === focus);
            });
            showSub(focus, hasOrder);

            if (!hasOrder) {
                orderInput.value = '';
            } else {
                if (!currentOrder) {
                    currentOrder = 'most';
                }
                orderInput.value = currentOrder;
            }

            orderButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-order') === currentOrder;
                btn.classList.toggle('active', isActive);
            });

            // If moving to membership or date, keep previous selections but refresh labels
            updateLabel();
        };

        var setOrder = function (order) {
            if (['member', 'trainer', 'staff'].indexOf(currentFocus) === -1) return;
            currentOrder = order;
            orderInput.value = order;
            orderButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-order') === order;
                btn.classList.toggle('active', isActive);
            });
            updateLabel();
        };

        var setMembership = function (membershipId) {
            currentMembership = membershipId;
            membershipInput.value = membershipId;
            membershipButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-membership-id') === membershipId;
                btn.classList.toggle('active', isActive);
            });
            updateLabel();
        };

        var setDatePreset = function (preset) {
            currentDatePreset = preset;
            datePresetInput.value = preset;
            dateButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-date-preset') === preset;
                btn.classList.toggle('active', isActive);
            });
            // Clear manual dates when using preset other than custom
            if (preset !== 'custom') {
                clearCustomInputs();
            }
            toggleCustomDateInputs(preset);
            updateLabel();
        };

        toggle.addEventListener('click', function () {
            panel.classList.toggle('show');
        });

        itemButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var focus = btn.getAttribute('data-focus');
                var hasOrder = btn.getAttribute('data-has-order') === 'true';
                setFocus(focus, hasOrder);
            });
        });

        orderButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setOrder(btn.getAttribute('data-order'));
            });
        });

        membershipButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setMembership(btn.getAttribute('data-membership-id') || '');
            });
        });

        dateButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setDatePreset(btn.getAttribute('data-date-preset'));
            });
        });

        document.addEventListener('click', function (event) {
            if (!panel.contains(event.target) && !toggle.contains(event.target)) {
                closePanel();
            }
        });

        // Initialize state
        setFocus(currentFocus, ['member', 'trainer', 'staff'].indexOf(currentFocus) !== -1);
        toggleCustomDateInputs(currentDatePreset);
        updateLabel();
    });
</script>
@endsection
