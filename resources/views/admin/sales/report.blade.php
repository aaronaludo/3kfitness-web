@extends('layouts.admin')
@section('title', 'Sales Reports')

@section('styles')
<style>
    .report-hero {
        background: linear-gradient(135deg, #0f4c75 0%, #1b6ca8 45%, #3282b8 100%);
        color: #f5fbff;
        border-radius: 20px;
        padding: 24px 26px;
        box-shadow: 0 18px 45px rgba(15, 76, 117, 0.25);
        position: relative;
        overflow: hidden;
    }
    .report-hero::after {
        content: "";
        position: absolute;
        inset: 10% -10% -40% 50%;
        background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 55%);
        transform: rotate(-8deg);
    }
    .report-hero h2 {
        font-weight: 800;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .report-hero .eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 700;
        font-size: 0.8rem;
        opacity: 0.85;
    }
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.15);
        color: #f5fbff;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .pill i {
        font-size: 0.9rem;
    }
    .summary-card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
        padding: 18px;
        box-shadow: 0 14px 38px rgba(0, 0, 0, 0.05);
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .summary-card .eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 700;
        font-size: 0.82rem;
        color: #5b6470;
    }
    .summary-value {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 6px 0;
        color: #1f2a37;
    }
    .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 10px;
        background: rgba(15, 76, 117, 0.06);
        color: #0f4c75;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .filter-card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
        box-shadow: 0 10px 28px rgba(0,0,0,0.06);
    }
    .form-label {
        font-weight: 700;
        color: #4b5563;
        font-size: 0.9rem;
    }
    .input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
    }
    .search-input {
        padding-left: 38px;
    }
    .table thead th {
        border: none;
        color: #5b6470;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 0.78rem;
    }
    .table tbody td {
        vertical-align: middle;
    }
    .badge-soft {
        background: rgba(12, 108, 178, 0.08);
        color: #0c6cb2;
        border-radius: 12px;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .empty-state {
        padding: 24px;
        text-align: center;
        color: #6b7280;
    }
    .note-box {
        border-radius: 14px;
        border: 1px dashed rgba(12, 108, 178, 0.25);
        background: rgba(12, 108, 178, 0.05);
        padding: 12px 14px;
        color: #0f4c75;
        font-weight: 600;
    }
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
        min-width: 260px;
        border: 1px solid rgba(0,0,0,0.08);
        display: none;
    }
    .filter-menu__panel.show {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .filter-menu__list {
        list-style: none;
        margin: 0;
        padding: 8px;
        border-right: 1px solid rgba(0,0,0,0.06);
    }
    .filter-menu__item {
        width: 100%;
        text-align: left;
        border: none;
        background: transparent;
        padding: 10px 10px;
        border-radius: 10px;
        font-weight: 700;
        color: #1f2a37;
    }
    .filter-menu__item.active, .filter-menu__item:hover {
        background: rgba(15, 76, 117, 0.08);
        color: #0f4c75;
    }
    .filter-menu__sub {
        list-style: none;
        margin: 0;
        padding: 8px;
    }
    .filter-menu__sub li {
        margin-bottom: 6px;
    }
    .filter-menu__sub button {
        width: 100%;
        border: none;
        background: rgba(15, 76, 117, 0.06);
        color: #0f4c75;
        border-radius: 10px;
        padding: 8px 10px;
        font-weight: 700;
    }
    .filter-menu__sub button.active {
        background: #0f4c75;
        color: #fff;
    }
    .filter-menu__sub.disabled {
        opacity: 0.45;
        pointer-events: none;
    }
    .filter-menu__sub.scrollable {
        max-height: calc(6 * 52px); /* show up to ~6 options before scrolling */
        overflow-y: auto;
    }
    .filter-menu__sub--dates {
        grid-column: 1 / -1;
        border-top: 1px solid rgba(0,0,0,0.06);
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
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="report-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8 position-relative" style="z-index: 1;">
                <p class="eyebrow mb-1">Sales Overview</p>
                <h2>Sales Reports</h2>
                <p class="mb-3">Default view loads the current year. Adjust dates to see past years or custom ranges.</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="pill"><i class="fa-solid fa-calendar"></i> Year {{ $rangeYear }}</span>
                    <span class="pill"><i class="fa-solid fa-clock"></i> Range {{ $rangeLabel }}</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end text-start position-relative" style="z-index: 1;">
                <div class="note-box">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    Edit the dates any time; by default we pull {{ $rangeYear }} year-to-date results.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="summary-card h-100">
                <p class="eyebrow mb-1">Total Membership Sales</p>
                <div class="summary-value">{{ $currency }} {{ number_format($summary['membership_revenue'] ?? 0, 2) }}</div>
                <div class="summary-chip">
                    <i class="fa-solid fa-user-group"></i>
                    {{ $summary['membership_count'] ?? 0 }} sales
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card h-100">
                <p class="eyebrow mb-1">Class Commission</p>
                <div class="summary-value">{{ $currency }} {{ number_format($summary['class_commission'] ?? 0, 2) }}</div>
                <div class="summary-chip">
                    <i class="fa-solid fa-ranking-star"></i>
                    Trainer commissions for the period
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card h-100">
                <p class="eyebrow mb-1">Total Sales</p>
                <div class="summary-value">{{ $currency }} {{ number_format($summary['total_sales'] ?? 0, 2) }}</div>
                <div class="summary-chip">
                    <i class="fa-solid fa-layer-group"></i>
                    Memberships + commissions
                </div>
            </div>
        </div>
    </div>

    <div class="filter-card mb-4 p-4">
        <div class="card-body">
            <form action="{{ route('admin.sales.report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
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
                    <label class="form-label">Filter dropdown</label>
                    <div class="filter-menu">
                        <button type="button" class="btn btn-outline-secondary filter-menu__toggle" id="filter-menu-toggle">
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
                <div class="col-12 {{ $datePreset === 'custom' ? '' : 'd-none' }}" id="custom-date-row">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label for="start-date" class="form-label">Start date</label>
                            <input type="date" id="start-date" name="start_date" class="form-control" value="{{ $datePreset === 'custom' ? ($startDate ?? '') : '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="start-time" class="form-label">Start time</label>
                            <input type="time" id="start-time" name="start_time" class="form-control" value="{{ $datePreset === 'custom' ? ($startTime ?? '') : '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="end-date" class="form-label">End date</label>
                            <input type="date" id="end-date" name="end_date" class="form-control" value="{{ $datePreset === 'custom' ? ($endDate ?? '') : '' }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="end-time" class="form-label">End time</label>
                            <input type="time" id="end-time" name="end_time" class="form-control" value="{{ $datePreset === 'custom' ? ($endTime ?? '') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-auto">
                    <button type="submit" class="btn btn-primary w-100 w-lg-auto">
                        <i class="fa-solid fa-filter me-2"></i>Apply filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden p-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">Results</h5>
                        <small class="text-muted">Sorted by {{ $order === 'least' ? 'lowest' : 'highest' }} revenue.</small>
                    </div>
                    <div class="badge-soft">{{ $currency }} currency</div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
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
                                    <td>{{ $row['label'] }}</td>
                                    <td><span class="badge-soft">{{ $row['type'] }}</span></td>
                                    <td class="text-center fw-bold">{{ $row['sales'] }}</td>
                                    <td class="text-end fw-bold">{{ $currency }} {{ number_format($row['revenue'], 2) }}</td>
                                    <td class="text-end text-muted">{{ $row['last_sale'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fa-regular fa-circle-question me-2"></i>No data for this view. Try widening your date range or clearing filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
