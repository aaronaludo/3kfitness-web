@extends('layouts.admin')
@section('title', 'Sales Detailed Reports')

@section('content')
<div class="container-fluid">
    @php
        $printFilters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
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
                <h2 class="title mb-0">Sales Detailed Reports</h2>
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

        <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="tile tile-primary h-100 mb-0">
                <div class="tile-heading">Memberships Revenue</div>
                <div class="tile-body">
                    <i class="fa-solid fa-wallet"></i>
                    <h2 class="float-end mb-0">{{ $summary['currency'] }} {{ number_format($summary['revenue'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="tile tile-primary h-100 mb-0">
                <div class="tile-heading">Cost</div>
                <div class="tile-body">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <h2 class="float-end mb-0">{{ $summary['currency'] }} {{ number_format($summary['cost'], 2) }}</h2>
                </div>
                 <div class="tile-footer"><a href="{{ route('admin.payrolls.index', ['processed_from' => $startDate, 'processed_to' => $endDate]) }}">Go to payrolls...</a></div>
                {{-- <div class="tile-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="text-muted small mb-0">Payroll payouts within range</span>
                    <a href="{{ route('admin.payrolls.index', ['processed_from' => $startDate, 'processed_to' => $endDate]) }}">
                        View payrolls...
                    </a>
                </div> --}}
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="tile tile-primary h-100 mb-0">
                <div class="tile-heading">Profit</div>
                <div class="tile-body">
                    <i class="fa-solid fa-chart-line"></i>
                    <h2 class="float-end mb-0">{{ $summary['currency'] }} {{ number_format($summary['profit'], 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                        <div>
                            <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                            <h4 class="fw-semibold mb-1">Detailed reports</h4>
                            <p class="text-muted mb-0">Pick a date window to refresh revenue, cost, profit, and payment details.</p>
                        </div>
                        <div class="text-end">
                            <span class="d-block text-muted small">{{ $summary['period_label'] }}</span>
                            <span class="d-block text-muted small">Showing {{ $membershipPayments->total() }} payments</span>
                        </div>
                    </div>

                    <form action="{{ route('admin.sales.reports') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-muted small mb-1" for="start_date">Start date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control rounded-pill" value="{{ $startDate }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-muted small mb-1" for="end_date">End date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control rounded-pill" value="{{ $endDate }}">
                        </div>
                        <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end">
                            <a href="{{ route('admin.sales.reports') }}" class="btn btn-link text-decoration-none text-muted px-0">
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
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small">Membership payments</span>
                            <div class="text-muted small">Approved payments in the selected date range</div>
                        </div>
                        <div class="text-muted small">
                            Showing {{ $membershipPayments->total() }} record{{ $membershipPayments->total() === 1 ? '' : 's' }}
                        </div>
                    </div>
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

        function buildFilters(filters) {
            const chips = [];
            if (filters.start_date || filters.end_date) {
                chips.push({
                    label: 'Date range',
                    value: `${filters.start_date || 'Any'} → ${filters.end_date || 'Any'}`,
                });
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
    });
</script>
@endsection
