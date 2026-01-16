@extends('layouts.admin')
@section('title', 'Payroll Cash Release')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payroll Cash Release</h2>
                    <p class="text-muted mb-0">Track released payroll cash and print release logs.</p>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <form action="#" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $searchTerm }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="role" value="{{ $roleFilter }}">
                        <button
                            type="submit"
                            class="btn btn-danger d-flex align-items-center gap-2"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of cash releases"
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
                                <h4 class="fw-semibold mb-1">Payroll Release</h4>
                                <p class="text-muted mb-0">Filter released payrolls by month, year, staff, or role.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $runs->total() }} results</span>
                                <span class="d-block text-muted small">Page {{ $runs->currentPage() }} of {{ $runs->lastPage() }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.payrolls.cash-release') }}" method="GET" class="mt-4">
                            <div class="d-flex flex-wrap align-items-end gap-3">
                                <div class="flex-grow-1" style="min-width: 240px;">
                                    <label class="form-label text-muted small mb-1" for="cash_release_search">Search</label>
                                    <div class="position-relative">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <input
                                            type="search"
                                            class="form-control rounded-pill ps-5"
                                            name="search"
                                            id="cash_release_search"
                                            placeholder="Search staff, code, or period"
                                            value="{{ $searchTerm }}"
                                            aria-label="Search released payrolls"
                                        />
                                    </div>
                                </div>

                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 180px;">
                                    <label class="form-label text-muted small mb-1" for="cash_release_month">Month</label>
                                    <select class="form-select rounded-pill" name="month" id="cash_release_month">
                                        <option value="">All months</option>
                                        @foreach($monthOptions as $option)
                                            <option value="{{ $option['value'] }}" {{ $selectedMonth === $option['value'] ? 'selected' : '' }}>
                                                {{ $option['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 150px;">
                                    <label class="form-label text-muted small mb-1" for="cash_release_year">Year</label>
                                    <select class="form-select rounded-pill" name="year" id="cash_release_year">
                                        <option value="">All years</option>
                                        @foreach($yearOptions as $yearOption)
                                            <option value="{{ $yearOption }}" {{ (string) $selectedYear === (string) $yearOption ? 'selected' : '' }}>
                                                {{ $yearOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-grow-1 flex-lg-grow-0">
                                    <label class="form-label text-muted small mb-1 d-block">Role</label>
                                    <div class="btn-group" role="group" aria-label="Payroll role filter">
                                        <input type="radio" class="btn-check" name="role" id="role_all" value="all" {{ $roleFilter === 'all' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="role_all">All</label>

                                        <input type="radio" class="btn-check" name="role" id="role_trainer" value="trainer" {{ $roleFilter === 'trainer' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="role_trainer">Trainer</label>

                                        <input type="radio" class="btn-check" name="role" id="role_staff" value="staff" {{ $roleFilter === 'staff' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary" for="role_staff">Staff</label>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 ms-auto">
                                    <a href="{{ route('admin.payrolls.cash-release') }}" class="btn btn-link text-decoration-none text-muted px-0">
                                        Reset
                                    </a>
                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
                                        <th scope="col">Period</th>
                                        <th scope="col">Net Pay</th>
                                        <th scope="col">Processed Date</th>
                                        <th scope="col">Released Date</th>
                                        <th scope="col">Released By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($runs as $run)
                                        @php
                                            $staff = $run->user;
                                            $releasedBy = $run->releasedByUser;
                                            $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'N/A';
                                            $email = optional($staff)->email ?? 'N/A';
                                            $processedAt = $run->processed_at
                                                ? $run->processed_at->format('M d, Y g:i A')
                                                : ($run->created_at?->format('M d, Y g:i A') ?? 'N/A');
                                            $releasedAt = $run->released_at
                                                ? $run->released_at->format('M d, Y g:i A')
                                                : 'N/A';
                                            $releasedByName = $releasedBy
                                                ? trim(($releasedBy->first_name ?? '') . ' ' . ($releasedBy->last_name ?? ''))
                                                : 'N/A';
                                            $rowNumber = ($runs->firstItem() ?? 1) + $loop->index;
                                        @endphp
                                        <tr>
                                            <td>{{ $rowNumber }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $name !== '' ? $name : 'N/A' }}</div>
                                                <span class="text-muted small">{{ $email }}</span>
                                            </td>
                                            <td>{{ $run->period_month ?? 'N/A' }}</td>
                                            <td class="text-success fw-semibold">{{ $currencySymbol }}{{ number_format((float) ($run->net_pay ?? 0), 2) }}</td>
                                            <td>{{ $processedAt }}</td>
                                            <td>{{ $releasedAt }}</td>
                                            <td>{{ $releasedByName !== '' ? $releasedByName : 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No released payrolls found. Adjust your filters or check back later.
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('print-submit-button');
            const printLoader = document.getElementById('print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) {
                    chips.push({ label: 'Search', value: filters.search });
                }
                if (filters.month || filters.year) {
                    const monthName = filters.month
                        ? new Date(2000, Number(filters.month) - 1, 1).toLocaleString('en-US', { month: 'long' })
                        : 'All months';
                    const yearLabel = filters.year || 'All years';
                    chips.push({ label: 'Period', value: `${monthName} ${yearLabel}`.trim() });
                }
                if (filters.role && filters.role !== 'all') {
                    chips.push({ label: 'Role', value: filters.role === 'trainer' ? 'Trainer only' : 'Staff only' });
                }
                return chips;
            }

            function buildRows(items, currencySymbol) {
                return (items || []).map((item, index) => ([
                    index + 1,
                    `<div class="fw">${item.name || 'N/A'}</div><div class="muted">${item.email || ''}</div>`,
                    item.period || 'N/A',
                    `${currencySymbol}${item.net || '0.00'}`,
                    item.processed_at || 'N/A',
                    item.released_at || 'N/A',
                    item.released_by || 'N/A',
                ]));
            }

            function renderPrintWindow(payload) {
                const rawItems = payload && payload.items ? payload.items : [];
                const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                const currencySymbol = payload.currency_symbol || '{{ $currencySymbol }}';
                const headers = [
                    '#',
                    'Staff',
                    'Period',
                    'Net Pay',
                    'Processed Date',
                    'Released Date',
                    'Released By',
                ];
                const rows = buildRows(items, currencySymbol);
                const filterChips = buildFilters(payload.filters || {});

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
        });
    </script>
@endsection
