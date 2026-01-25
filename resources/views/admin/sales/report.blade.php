@extends('layouts.admin')
@section('title', 'Sales Report')

@section('styles')
<style>
    /* Compact page shell */
    .report-shell {
        --card-radius: 14px;
        --card-padding: 1rem;
        --border: #e7e7ea;
        --text: #1f2933;
        --muted: #6b7280;
    }
    .report-shell .card {
        border-radius: var(--card-radius);
    }
    .report-shell .card-body {
        padding: var(--card-padding);
    }
    .report-shell h2.title {
        font-size: 1.35rem;
        margin-bottom: 0.15rem;
    }
    .report-shell h4.fw-semibold {
        font-size: 1.05rem;
    }
    .report-shell .badge {
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        letter-spacing: 0.01em;
    }
    .report-shell .form-control,
    .report-shell .form-select {
        padding: 0.55rem 0.75rem;
        font-size: 0.95rem;
        border-radius: 10px;
    }
    .report-shell .form-label {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    .report-shell .btn {
        padding: 0.48rem 0.9rem;
        border-radius: 10px;
    }
    .report-shell .btn.btn-danger,
    .report-shell .btn.btn-outline-secondary,
    .report-shell .btn.btn-link {
        box-shadow: none;
    }
    .report-shell .sales-month-filter {
        min-width: 180px;
    }
    .report-shell .details-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.48rem 0.9rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .report-shell .details-toggle-btn.is-active {
        border-color: rgba(220, 53, 69, 0.4);
        color: #b91c1c;
        background: rgba(220, 53, 69, 0.08);
    }
    .report-shell .table {
        font-size: 0.93rem;
    }
    .report-shell .table > :not(caption) > * > * {
        padding: 0.55rem 0.75rem;
    }
    .report-shell .table thead th {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
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
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.05);
        min-height: 100%;
        display: flex;
        flex-direction: column;
        gap: 10px;
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
        font-size: 1.05rem;
        line-height: 1.25;
    }
    .summary-card__subtitle {
        color: var(--muted);
        font-size: 0.86rem;
    }
    .summary-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-soft);
        color: var(--accent);
        border: 1px solid var(--border);
    }
    .pill-soft {
        margin-bottom: 8px;
        display: inline-block;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        background: var(--accent-soft);
        color: var(--accent);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }
    .pill-ghost {
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--muted);
        background: #fff;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .summary-amount {
        font-size: 1.55rem;
        font-weight: 800;
        color: var(--text);
    }
    .summary-card__value {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .summary-card__math {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        background: var(--accent-soft);
        border: 1px dashed rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        padding: 8px;
    }
    .math-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        min-width: 140px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.02);
    }
    .math-chip__icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
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
        font-size: 0.95rem;
    }
    .math-chip__value {
        color: var(--muted);
        font-weight: 600;
        font-size: 0.85rem;
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
        border-radius: 10px;
        padding: 8px 10px;
    }
    .filter-menu__panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 30;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.12);
        min-width: 560px;
        border: 1px solid rgba(0,0,0,0.08);
        display: none;
    }
    .filter-menu__panel.show {
        display: grid;
        grid-template-columns: 150px 180px 1fr;
        grid-template-rows: auto;
        grid-auto-rows: auto;
        align-items: start;
        gap: 0;
    }
    .filter-menu__panel.show.has-memberships {
        grid-template-columns: 150px 180px 190px 1fr;
        min-width: 720px;
    }
    .filter-menu__list {
        list-style: none;
        margin: 0;
        padding: 6px;
        border-right: 1px solid rgba(0,0,0,0.06);
        grid-column: 1;
        grid-row: 1;
    }
    .filter-menu__item {
        width: 100%;
        text-align: left;
        border: none;
        background: transparent;
        padding: 8px 10px;
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
        padding: 8px;
        grid-column: 2;
        grid-row: 1;
        display: grid;
        gap: 8px;
    }
    .filter-menu__sub--memberships {
        grid-column: 2;
    }
    .filter-menu__panel.show.has-memberships .filter-menu__sub--memberships {
        grid-column: 3;
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
        padding: 10px 10px;
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
        max-height: 360px;
        overflow-y: auto;
        background: #f8fafc;
        border-left: 1px solid rgba(0,0,0,0.06);
        border-radius: 0 14px 14px 0;
        padding: 10px;
        display: grid;
        gap: 10px;
    }
    .filter-menu__panel.show.has-memberships .filter-menu__sub--dates {
        grid-column: 4;
    }
    .filter-menu__custom-range {
        grid-column: 1 / -1;
        grid-row: 2;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 12px;
        padding: 12px;
        border-top: 1px solid rgba(0,0,0,0.06);
        background: #f8fafc;
        border-radius: 0 0 14px 14px;
    }
    .filter-menu__custom-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .filter-menu__custom-range .form-label {
        font-size: 0.82rem;
        color: #475569;
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
    .filter-menu__panel.show.has-memberships {
        min-width: 0;
        grid-template-columns: 1fr;
    }
    .filter-menu__list {
        border-right: none;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .filter-menu__sub {
        grid-column: 1;
        grid-row: auto;
    }
    .filter-menu__panel.show.has-memberships .filter-menu__sub--memberships {
        grid-column: 1;
    }
    .filter-menu__sub--dates {
        grid-column: 1;
        grid-row: auto;
        border-left: none;
        border-top: 1px solid rgba(0,0,0,0.06);
        border-radius: 0 0 14px 14px;
    }
    .filter-menu__panel.show.has-memberships .filter-menu__sub--dates {
        grid-column: 1;
    }
    .filter-menu__custom-range {
        grid-column: 1;
        grid-row: auto;
        grid-template-columns: 1fr;
        border-radius: 0 0 14px 14px;
    }
    }
</style>
@endsection

@section('content')
<div class="container-fluid report-shell">
    @php
        $printCollectionCurrent = $focusRows instanceof \Illuminate\Pagination\AbstractPaginator
            ? collect($focusRows->items())
            : collect($focusRows ?? []);
        $printCollectionAll = $focusRowsAll instanceof \Illuminate\Pagination\AbstractPaginator
            ? collect($focusRowsAll->items())
            : collect($focusRowsAll ?? $printCollectionCurrent);
        $hasFilterPreset = !empty(request()->except(['page']));

        $mapPrintRow = function ($row) use ($focus, $currency) {
            $label = $row['label'] ?? '—';

            if ($focus === 'trainer') {
                return [
                    'label' => $label,
                    'value' => round((float) ($row['app_cut'] ?? 0), 2),
                    'currency' => $currency,
                    'last_sale' => $row['last_sale'] ?? '—',
                ];
            }

            if ($focus === 'staff') {
                return [
                    'label' => $label,
                    'value' => round((float) ($row['membership_payments']['total'] ?? 0), 2),
                    'count' => (int) ($row['membership_payments']['count'] ?? 0),
                    'currency' => $row['membership_payments']['currency'] ?? $currency,
                ];
            }

            return [
                'label' => $label,
                'type' => $row['type'] ?? '—',
                'sales' => (int) ($row['sales'] ?? 0),
                'revenue' => round((float) ($row['revenue'] ?? 0), 2),
                'last_sale' => $row['last_sale'] ?? '—',
            ];
        };

        $printItems = $printCollectionCurrent->values()->map($mapPrintRow);
        $printItemsAll = $printCollectionAll->values()->map($mapPrintRow);

        if (!$hasFilterPreset) {
            $printItems = collect();
            $printItemsAll = collect();
        }

        $includeAllSummary = !$hasFilterPreset;
        $summaryForPrint = [
            'membership_revenue' => ($includeAllSummary || $focus !== 'trainer')
                ? round((float) ($summary['membership_revenue'] ?? 0), 2)
                : null,
            'total_sales_count' => ($includeAllSummary || $focus !== 'trainer')
                ? (int) ($summary['total_sales_count'] ?? 0)
                : null,
            'class_commission' => ($includeAllSummary || $focus === 'trainer')
                ? round((float) ($summary['class_commission'] ?? 0), 2)
                : null,
        ];
        $filtersForPrint = $hasFilterPreset
            ? [
                'search' => $searchTerm,
                'focus' => ucfirst($focus),
                'order' => $order,
                'membership' => $selectedMembershipLabel ?? 'All Memberships',
                'date' => $datePresetLabel ?? 'Custom Date Range',
                'range' => $rangeLabel,
            ]
            : [];
        $printUser = auth()->user();
        $printUserName = $printUser
            ? trim(($printUser->first_name ?? '') . ' ' . ($printUser->last_name ?? ''))
            : '';
        if ($printUser && $printUserName === '') {
            $printUserName = $printUser->name ?? $printUser->email ?? '';
        }
        $printUserRole = $printUser ? optional($printUser->role)->name : null;
        $printGeneratedBy = $printUserName !== '' ? $printUserName : '—';
        if ($printUserRole) {
            $printGeneratedBy .= " ({$printUserRole})";
        }
        $printMeta = [
            'generated_by' => $printGeneratedBy,
        ];
        if (!$hasFilterPreset) {
            $printMeta['hide_table'] = true;
        }

        $printPayload = [
            'title' => 'Sales report',
            'generated_at' => now()->format('M d, Y g:i A'),
            'focus' => $focus,
            'order' => $order,
            'currency' => $currency,
            'summary' => $summaryForPrint,
            'filters' => $filtersForPrint,
            'meta' => $printMeta,
            'count' => $printItems->count(),
            'items' => $printItems,
        ];
        $printAllPayload = array_merge($printPayload, [
            'title' => 'Sales report (all pages)',
            'filters' => array_merge($printPayload['filters'], ['scope' => 'all']),
            'count' => $printItemsAll->count(),
            'items' => $printItemsAll,
        ]);
        $filterPresetEmpty = !$hasFilterPreset;
        $showMembershipTile = $filterPresetEmpty || $focus !== 'trainer';
        $showTotalSalesTile = $filterPresetEmpty || $focus !== 'trainer';
        $showClassCommissionTile = $filterPresetEmpty || $focus === 'trainer';
        $tileCount = ($showMembershipTile ? 1 : 0) + ($showTotalSalesTile ? 1 : 0) + ($showClassCommissionTile ? 1 : 0);
        $tileColClass = $tileCount === 1 ? 'col-12' : ($tileCount === 2 ? 'col-12 col-lg-6' : 'col-12 col-lg-4');
        $baseMonth = now()->startOfMonth();
        $monthFilterOptions = collect(range(0, 36))
            ->map(function ($offset) use ($baseMonth) {
                $month = $baseMonth->copy()->subMonths($offset);
                return [
                    'value' => $month->format('Y-m'),
                    'label' => $month->format('F Y'),
                    'start' => $month->copy()->startOfMonth()->format('Y-m-d'),
                    'end' => $month->copy()->endOfMonth()->format('Y-m-d'),
                ];
            })
            ->sortByDesc('start')
            ->values();
        $monthFilterSelection = null;
        if (!empty($datePreset) && $datePreset === 'this_month') {
            $monthFilterSelection = now()->format('Y-m');
        } elseif (!empty($datePreset) && $datePreset === 'last_month') {
            $monthFilterSelection = now()->subMonth()->format('Y-m');
        } elseif (!empty($startDate) && !empty($endDate)) {
            try {
                $startCarbon = \Carbon\Carbon::parse($startDate);
                $endCarbon = \Carbon\Carbon::parse($endDate);
                if (
                    $startCarbon->isSameDay($startCarbon->copy()->startOfMonth()) &&
                    $endCarbon->isSameDay($endCarbon->copy()->endOfMonth()) &&
                    $startCarbon->isSameMonth($endCarbon)
                ) {
                    $monthFilterSelection = $startCarbon->format('Y-m');
                }
            } catch (\Exception $e) {
                $monthFilterSelection = null;
            }
        }
    @endphp
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 mt-2">
            <div>
                <h2 class="title mb-0">Sales Reports</h2>
                <p class="text-muted mb-0">Choose a filter preset to load results. Default date preset is the current year.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select sales-month-filter" id="sales-month-filter" aria-label="Filter by month">
                    <option value="">Filter month</option>
                    @foreach ($monthFilterOptions as $option)
                        <option
                            value="{{ $option['value'] }}"
                            data-start="{{ $option['start'] }}"
                            data-end="{{ $option['end'] }}"
                            {{ $monthFilterSelection === $option['value'] ? 'selected' : '' }}
                        >
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                <button
                    type="button"
                    class="btn btn-danger d-flex align-items-center gap-2"
                    id="sales-print-btn"
                    data-print='@json($printPayload)'
                    data-print-all='@json($printAllPayload)'
                >
                    <i class="fa-solid fa-print"></i>
                    <span id="sales-print-loader" class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
                    Print
                </button>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3 report-summary">
        @if($showMembershipTile)
            <div class="{{ $tileColClass }}">
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
        @if($showClassCommissionTile)
            <div class="{{ $tileColClass }}">
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
        @if($showTotalSalesTile)
            <div class="{{ $tileColClass }}">
                <div class="summary-card profit">
                    <div class="summary-card__header">
                        <div>
                            <span class="pill-soft">Total</span>
                            <div class="summary-card__title h5 mb-1">Total sales count</div>
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
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                <div>
                    <span class="badge bg-light text-dark fw-semibold px-2 py-1 rounded-pill text-uppercase small mb-2">Filters</span>
                    <h4 class="fw-semibold mb-1">Sales report filters</h4>
                    <p class="text-muted mb-0">Adjust focus, membership, and date range. Default is current year.</p>
                </div>
            </div>
            <form action="{{ route('admin.sales.report') }}" method="GET" class="row g-2 align-items-end" id="sales-report-form">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Search for all</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass input-icon"></i>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control search-input"
                            placeholder="Members, trainers, staff, codes, memberships, dates"
                            value="{{ $searchTerm }}"
                        />
                    </div>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Filter preset</label>
                    <div class="filter-menu">
                        <button type="button" class="btn btn-outline-secondary w-100 filter-menu__toggle" id="filter-menu-toggle">
                            <span id="filter-menu-label">
                                @if($hasFilterPreset)
                                    @if(in_array($focus, ['member','trainer','staff']))
                                        {{ ucfirst($focus) }} — {{ $order === 'least' ? 'Least Sales' : 'Most Sales' }}
                                        @if(in_array($focus, ['member','staff']) && !empty($selectedMembershipId))
                                            | Memberships — {{ $selectedMembershipLabel ?? 'All Memberships' }}
                                        @endif
                                        | Date — {{ $datePresetLabel ?? 'Custom Date Range' }}
                                    @elseif($focus === 'membership')
                                        Memberships — {{ $selectedMembershipLabel ?? 'All Memberships' }} | Date — {{ $datePresetLabel ?? 'Custom Date Range' }}
                                    @else
                                        Date — {{ $datePresetLabel ?? 'Custom Date Range' }}
                                    @endif
                                @else
                                    Filter preset
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
                            <ul class="filter-menu__sub filter-menu__sub--memberships scrollable {{ in_array($focus, ['member','staff','membership']) ? '' : 'd-none' }}" id="sub-memberships">
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
                            <div class="filter-menu__custom-range {{ $datePreset === 'custom' ? '' : 'd-none' }}" id="custom-date-row">
                                <div class="filter-menu__custom-field">
                                    <label for="start-date" class="form-label">Start date</label>
                                    <input type="date" id="start-date" name="start_date" class="form-control" value="{{ $datePreset === 'custom' ? ($startDate ?? '') : '' }}">
                                </div>
                                <div class="filter-menu__custom-field">
                                    <label for="start-time" class="form-label">Start time</label>
                                    <input type="time" id="start-time" name="start_time" class="form-control" value="{{ $datePreset === 'custom' ? ($startTime ?? '') : '' }}">
                                </div>
                                <div class="filter-menu__custom-field">
                                    <label for="end-date" class="form-label">End date</label>
                                    <input type="date" id="end-date" name="end_date" class="form-control" value="{{ $datePreset === 'custom' ? ($endDate ?? '') : '' }}">
                                </div>
                                <div class="filter-menu__custom-field">
                                    <label for="end-time" class="form-label">End time</label>
                                    <input type="time" id="end-time" name="end_time" class="form-control" value="{{ $datePreset === 'custom' ? ($endTime ?? '') : '' }}">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="focus" id="focus-field" value="{{ $focus }}">
                        <input type="hidden" name="order" id="order-field" value="{{ $order ?? 'most' }}">
                        <input type="hidden" name="membership_id" id="membership-field" value="{{ $selectedMembershipId }}">
                        <input type="hidden" name="date_preset" id="date-preset-field" value="{{ $datePreset }}">
                    </div>
                </div>
                <div class="col-12 col-lg-auto d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.sales.report') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                    <button type="button" class="details-toggle-btn" id="sales-details-toggle" aria-pressed="true">
                        <i class="fa-solid fa-eye-slash"></i>
                        Hide Details
                    </button>
                    <button type="submit" class="btn btn-danger px-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-filter"></i>
                        Apply
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

    @if($hasFilterPreset)
    <div class="card shadow-sm border-0 rounded-4 mb-4 report-detail-section">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <h5 class="fw-semibold mb-1">Results</h5>
                    <p class="text-muted small mb-2">Styled to match the Sales Profit Report table.</p>
                    <span class="badge bg-light text-dark fw-semibold px-2 py-1 rounded-pill text-uppercase small">{{ ucfirst($focus) }}</span>
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
                                    <span class="badge bg-light text-dark fw-semibold px-2 py-1 rounded-pill text-uppercase small">
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
    @else
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4 text-center text-muted">
            <div class="mb-3">
                <i class="fa-solid fa-filter-circle-xmark fa-2x"></i>
            </div>
            <h5 class="fw-semibold mb-2">No filters applied</h5>
            <p class="mb-0">Choose a filter preset to load results.</p>
        </div>
    </div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var printBtn = document.getElementById('sales-print-btn');
        var loader = document.getElementById('sales-print-loader');
        if (!printBtn) return;

        var rawPayload = printBtn.dataset.print;
        var rawAllPayload = printBtn.dataset.printAll;
        var detailsStorageKey = 'salesReportShowDetails';

        var parsePayload = function (raw) {
            try {
                return raw ? JSON.parse(raw) : null;
            } catch (err) {
                return null;
            }
        };

        var buildFilterChips = function (filters) {
            var chips = [];
            if (!filters) return chips;
            if (filters.search) chips.push({ label: 'Search', value: filters.search });
            if (filters.focus) {
                var focusValue = filters.focus;
                if (filters.order) {
                    focusValue += ' (' + filters.order + ')';
                }
                chips.push({ label: 'Focus', value: focusValue });
            }
            if (filters.membership) chips.push({ label: 'Membership', value: filters.membership });
            if (filters.date) chips.push({ label: 'Date', value: filters.date });
            if (filters.range) chips.push({ label: 'Range', value: filters.range });
            return chips;
        };

        var buildRows = function (payload) {
            var focus = payload.focus || 'member';
            var currency = payload.currency || '';
            var summary = payload.summary || {};
            var meta = payload.meta || {};
            var items = Array.isArray(payload.items) ? payload.items : [];
            var headers = [];
            var rows = [];

            if (meta.hide_table) {
                return { headers: [], rows: [] };
            }

            if (focus === 'trainer') {
                headers = ['#', 'Trainer', 'Class Commission (' + currency + ')', 'Last Sale'];
                rows = items.map(function (item, idx) {
                    return [
                        idx + 1,
                        item.label || '—',
                        currency + ' ' + Number(item.value || 0).toFixed(2),
                        item.last_sale || '—',
                    ];
                });
            } else if (focus === 'staff') {
                headers = ['#', 'Staff', 'Membership Payments (' + currency + ')', 'Count'];
                rows = items.map(function (item, idx) {
                    var curr = item.currency || currency;
                    return [
                        idx + 1,
                        item.label || '—',
                        curr + ' ' + Number(item.value || 0).toFixed(2),
                        (item.count ?? 0).toString(),
                    ];
                });
            } else {
                headers = ['Focus', 'Type', 'Sales', 'Revenue (' + currency + ')', 'Last Sale'];
                rows = items.map(function (item) {
                    return [
                        item.label || '—',
                        item.type || '—',
                        (item.sales ?? 0).toString(),
                        currency + ' ' + Number(item.revenue || 0).toFixed(2),
                        item.last_sale || '—',
                    ];
                });

                var hasSalesTotal = summary.total_sales_count !== undefined && summary.total_sales_count !== null;
                var hasRevenueTotal = summary.membership_revenue !== undefined && summary.membership_revenue !== null;

                if (hasSalesTotal || hasRevenueTotal) {
                    var revenueLabel = currency ? 'Total Revenue (' + currency + ')' : 'Total Revenue';
                    var revenueTotal = Number(summary.membership_revenue || 0);
                    var salesTotal = Number(summary.total_sales_count || 0);

                    rows.push([
                        'Totals',
                        revenueLabel,
                        salesTotal.toString(),
                        (currency ? currency + ' ' : '') + revenueTotal.toFixed(2),
                        '—',
                    ]);
                }
            }

            return { headers: headers, rows: rows };
        };

        printBtn.addEventListener('click', async function (event) {
            event.preventDefault();
            if (loader) loader.classList.remove('d-none');
            printBtn.disabled = true;

            var payload = parsePayload(rawPayload);
            var allPayload = parsePayload(rawAllPayload);
            var handled = false;

            var scope = 'current';
            if (window.PrintPreview && typeof window.PrintPreview.chooseScope === 'function') {
                scope = await window.PrintPreview.chooseScope();
                if (!scope) {
                    printBtn.disabled = false;
                    if (loader) loader.classList.add('d-none');
                    return;
                }
            }

            var payloadToUse = scope === 'all' && allPayload ? allPayload : payload;
            var detailsVisible = true;
            try {
                detailsVisible = localStorage.getItem(detailsStorageKey) !== '0';
            } catch (err) {
                detailsVisible = true;
            }

            if (payloadToUse && !detailsVisible) {
                payloadToUse = JSON.parse(JSON.stringify(payloadToUse));
                payloadToUse.meta = payloadToUse.meta || {};
                payloadToUse.meta.hide_table = true;
                payloadToUse.items = [];
                payloadToUse.count = 0;
            }

            if (payloadToUse && window.PrintPreview && typeof window.PrintPreview.tryOpen === 'function') {
                var chips = buildFilterChips(payloadToUse.filters || {});
                var built = buildRows(payloadToUse);
                handled = window.PrintPreview.tryOpen(payloadToUse, built.headers, built.rows, chips);
            }

            if (!handled) {
                window.print();
            }

            printBtn.disabled = false;
            if (loader) loader.classList.add('d-none');
        });
    });
</script>
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
        var monthFilter = document.getElementById('sales-month-filter');
        var salesForm = document.getElementById('sales-report-form');
        var detailsToggle = document.getElementById('sales-details-toggle');
        var detailSections = document.querySelectorAll('.report-detail-section');
        var detailsStorageKey = 'salesReportShowDetails';

        if (!toggle || !panel || !focusInput || !orderInput || !membershipInput || !datePresetInput) return;

        var membershipLabels = @json(($membershipOptions ?? collect())->pluck('name', 'id'));
        var datePresetLabels = @json($datePresetLabels ?? []);
        var hasUserFilters = @json($hasFilterPreset);

        var currentFocus = focusInput.value || 'member';
        var currentOrder = orderInput.value || 'most';
        var currentMembership = membershipInput.value || '';
        var currentDatePreset = datePresetInput.value || 'this_year';

        var focusSupportsMembership = function (focusValue) {
            return ['member', 'staff', 'membership'].indexOf(focusValue) !== -1;
        };

        var focusStacksMembership = function (focusValue) {
            return ['member', 'staff'].indexOf(focusValue) !== -1;
        };

        var getMembershipName = function () {
            return currentMembership && membershipLabels[currentMembership] ? membershipLabels[currentMembership] : 'All Memberships';
        };

        var updateLabel = function () {
            if (!hasUserFilters) {
                labelEl.textContent = 'Filter preset';
                return;
            }
            var text = '';
            var dateLabel = datePresetLabels[currentDatePreset] || 'Custom Date Range';
            if (['member', 'trainer', 'staff'].indexOf(currentFocus) !== -1) {
                text = currentFocus.charAt(0).toUpperCase() + currentFocus.slice(1) + ' — ' + (currentOrder === 'least' ? 'Least Sales' : 'Most Sales');
                if (focusStacksMembership(currentFocus) && currentMembership) {
                    text += ' | Memberships — ' + getMembershipName();
                }
                text += ' | Date — ' + dateLabel;
            } else if (currentFocus === 'membership') {
                text = 'Memberships — ' + getMembershipName() + ' | Date — ' + dateLabel;
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
            subOrders.classList.toggle('d-none', !hasOrder);
            subMemberships.classList.toggle('d-none', !focusSupportsMembership(focus));
            panel.classList.toggle('has-memberships', focusStacksMembership(focus));
        };

        var setFocus = function (focus, hasOrder, markDirty) {
            currentFocus = focus;
            focusInput.value = focus;
            itemButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-focus') === focus);
            });
            showSub(focus, hasOrder);

            if (!focusSupportsMembership(focus)) {
                currentMembership = '';
                membershipInput.value = '';
                membershipButtons.forEach(function (btn) {
                    var isActive = btn.getAttribute('data-membership-id') === '';
                    btn.classList.toggle('active', isActive);
                });
            }

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
            if (markDirty) {
                hasUserFilters = true;
            }
            updateLabel();
        };

        var setOrder = function (order, markDirty) {
            if (['member', 'trainer', 'staff'].indexOf(currentFocus) === -1) return;
            currentOrder = order;
            orderInput.value = order;
            orderButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-order') === order;
                btn.classList.toggle('active', isActive);
            });
            if (markDirty) {
                hasUserFilters = true;
            }
            updateLabel();
        };

        var setMembership = function (membershipId, markDirty) {
            currentMembership = membershipId;
            membershipInput.value = membershipId;
            membershipButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-membership-id') === membershipId;
                btn.classList.toggle('active', isActive);
            });
            if (markDirty) {
                hasUserFilters = true;
            }
            updateLabel();
        };

        var setDatePreset = function (preset, markDirty) {
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
            if (markDirty) {
                hasUserFilters = true;
            }
            updateLabel();
        };

        toggle.addEventListener('click', function () {
            panel.classList.toggle('show');
        });

        itemButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var focus = btn.getAttribute('data-focus');
                var hasOrder = btn.getAttribute('data-has-order') === 'true';
                setFocus(focus, hasOrder, true);
            });
        });

        orderButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setOrder(btn.getAttribute('data-order'), true);
            });
        });

        membershipButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setMembership(btn.getAttribute('data-membership-id') || '', true);
            });
        });

        dateButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setDatePreset(btn.getAttribute('data-date-preset'), true);
            });
        });

        if (monthFilter) {
            monthFilter.addEventListener('change', function () {
                var selected = monthFilter.options[monthFilter.selectedIndex];
                var startValue = selected ? selected.getAttribute('data-start') : null;
                var endValue = selected ? selected.getAttribute('data-end') : null;
                if (!startValue || !endValue) {
                    return;
                }
                if (startDateInput) startDateInput.value = startValue;
                if (endDateInput) endDateInput.value = endValue;
                if (startTimeInput) startTimeInput.value = '';
                if (endTimeInput) endTimeInput.value = '';
                setDatePreset('custom', true);
                if (salesForm) {
                    salesForm.submit();
                }
            });
        }

        if (salesForm) {
            salesForm.addEventListener('submit', function () {
                localStorage.setItem(detailsStorageKey, '1');
            });
        }

        document.addEventListener('click', function (event) {
            if (!panel.contains(event.target) && !toggle.contains(event.target)) {
                closePanel();
            }
        });

        if (detailsToggle && detailSections.length) {
            var savedState = localStorage.getItem(detailsStorageKey);
            var isVisible = savedState !== '0';

            var setDetailsVisibility = function (visible) {
                isVisible = visible;
                detailSections.forEach(function (section) {
                    section.classList.toggle('d-none', !visible);
                });
                detailsToggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
                detailsToggle.classList.toggle('is-active', !visible);
                detailsToggle.innerHTML = visible
                    ? '<i class="fa-solid fa-eye-slash"></i> Hide Details'
                    : '<i class="fa-solid fa-eye"></i> Show Details';
                localStorage.setItem(detailsStorageKey, visible ? '1' : '0');
            };

            setDetailsVisibility(isVisible);
            detailsToggle.addEventListener('click', function () {
                setDetailsVisibility(!isVisible);
            });
        }

        // Initialize state
        setFocus(currentFocus, ['member', 'trainer', 'staff'].indexOf(currentFocus) !== -1, false);
        toggleCustomDateInputs(currentDatePreset);
        updateLabel();
    });
</script>
@endsection
