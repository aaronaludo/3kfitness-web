@extends('layouts.admin')
@section('title', 'Membership History')

@section('content')
    <div class="container-fluid">
        @php
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['membership_id'] ?? null) || (($filters['status'] ?? 'all') !== 'all') || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null);
            $statusLabels = [
                'all' => ['label' => 'All statuses', 'class' => 'bg-secondary'],
                'approved' => ['label' => 'Approved', 'class' => 'bg-success'],
                'pending' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger'],
            ];
            $activeStatus = $filters['status'] ?? 'all';
            $advancedFiltersOpen = ($filters['membership_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'all';

            $printSource = $payments;
            $printAllSource = $printAllPayments ?? collect();

            $mapPayment = function ($payment) {
                $member = $payment->user;
                $membership = $payment->membership;
                $purchasedAt = $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : null;
                $expiresAt = $payment->expiration_at ? \Carbon\Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : null;
                $statusMeta = [
                    0 => 'Pending',
                    1 => 'Approved',
                    2 => 'Rejected',
                ];

                return [
                    'id' => $payment->id,
                    'member' => $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : 'Unknown member',
                    'role' => $member && $member->role ? ($member->role->name ?? null) : null,
                    'email' => $member ? ($member->email ?? null) : null,
                    'phone' => $member ? ($member->phone_number ?? null) : null,
                    'membership' => $membership ? $membership->name : 'Membership unavailable',
                    'price' => $membership && isset($membership->price) ? number_format((float) $membership->price, 2) : null,
                    'status' => $statusMeta[$payment->isapproved] ?? 'Pending',
                    'purchased' => $purchasedAt,
                    'expires' => $expiresAt,
                ];
            };

            $printItems = collect($printSource->items() ?? [])->map($mapPayment)->values();
            $printAllItems = collect($printAllSource ?? [])->map($mapPayment)->values();

            $printFilters = [
                'search' => $filters['search'] ?? '',
                'membership_id' => $filters['membership_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'start' => $filters['start_date'] ?? null,
                'end' => $filters['end_date'] ?? null,
            ];

            $printPayload = [
                'title' => 'Membership history',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => $printFilters,
                'count' => $printItems->count(),
                'items' => $printItems,
            ];

            $printAllPayload = [
                'title' => 'Membership history (all pages)',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => array_merge($printFilters, ['scope' => 'all']),
                'count' => $printAllItems->count(),
                'items' => $printAllItems,
            ];
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Membership History</h2>
                    <p class="text-muted mb-0 small">Track past membership purchases, approvals, and expirations for members.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center h-100">
                    <form action="{{ route('admin.history.memberships.print') }}" method="POST" id="membership-history-print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="membership_id" value="{{ $filters['membership_id'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <button
                            type="submit"
                            class="btn btn-danger"
                            id="membership-history-print-submit"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered membership history"
                        >
                            <i class="fa-solid fa-print me-2"></i>Print
                            <span id="membership-history-print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                    <a href="{{ route('admin.staff-account-management.membership-payments') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-wallet me-2"></i>View payments
                    </a>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">History</span>
                                <h4 class="fw-semibold mb-1">Membership activity</h4>
                                <p class="text-muted mb-0">Search members, filter by membership plan or status, and review when each plan was purchased.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Records</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['total'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Unique members</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['members'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Memberships</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['memberships'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.history.memberships') }}" method="GET" id="membership-history-filter-form" class="mt-3">
                            <input type="hidden" name="status" id="membership-history-status-filter" value="{{ $activeStatus }}">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                @foreach ($statusLabels as $key => $label)
                                    @php
                                        $count = $statusTallies[$key] ?? null;
                                    @endphp
                                    <button
                                        type="button"
                                        class="btn btn-sm rounded-pill px-3 membership-history-status-chip {{ $activeStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                        data-status="{{ $key }}"
                                        aria-label="Filter memberships by {{ strtolower($label['label']) }}"
                                    >
                                        {{ $label['label'] }}
                                        @if(!is_null($count))
                                            <span class="badge bg-transparent {{ $activeStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $count }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                    <div class="position-relative">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input
                                            type="search"
                                            id="search"
                                            name="search"
                                            class="form-control rounded-pill ps-5"
                                            value="{{ $filters['search'] ?? '' }}"
                                            placeholder="Search member, membership, or ID"
                                            aria-label="Search membership history"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasFilters)
                                        <a href="{{ route('admin.history.memberships') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#membershipHistoryFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="membershipHistoryFiltersModal" tabindex="-1" aria-labelledby="membershipHistoryFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="membershipHistoryFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill membership-history-range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill membership-history-range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill membership-history-range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label for="membership_id" class="form-label text-muted text-uppercase small mb-1">Membership</label>
                                                    <select id="membership_id" name="membership_id" class="form-select rounded-3">
                                                        <option value="">All memberships</option>
                                                        @foreach ($membershipOptions as $membership)
                                                            <option
                                                                value="{{ $membership->id }}"
                                                                {{ (string) ($filters['membership_id'] ?? '') === (string) $membership->id ? 'selected' : '' }}
                                                            >
                                                                {{ $membership->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="status" class="form-label text-muted text-uppercase small mb-1">Status</label>
                                                    <select id="status" name="status_display" class="form-select rounded-3">
                                                        <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                                        <option value="approved" {{ ($filters['status'] ?? 'all') === 'approved' ? 'selected' : '' }}>Approved</option>
                                                        <option value="pending" {{ ($filters['status'] ?? 'all') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="rejected" {{ ($filters['status'] ?? 'all') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Purchased range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="membership-history-start-date" class="form-label small text-muted mb-1">From</label>
                                                            <input
                                                                type="date"
                                                                id="membership-history-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ $filters['start_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="membership-history-end-date" class="form-label small text-muted mb-1">To</label>
                                                            <input
                                                                type="date"
                                                                id="membership-history-end-date"
                                                                class="form-control rounded-3"
                                                                name="end_date"
                                                                value="{{ $filters['end_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fa-solid fa-magnifying-glass me-2"></i>Apply filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th>Contact</th>
                                        <th>Membership</th>
                                        <th>Status</th>
                                        <th>Purchased</th>
                                        <th>Expires</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments as $index => $payment)
                                        @php
                                            $member = $payment->user;
                                            $membership = $payment->membership;
                                            $purchasedAt = $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—';
                                            $expiresAt = $payment->expiration_at ? \Carbon\Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—';
                                            $fullName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '';
                                            $statusValue = $payment->isapproved;
                                            $statusMeta = [
                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                            ][$statusValue] ?? ['label' => 'Pending', 'class' => 'bg-warning text-dark'];
                                        @endphp
                                        <tr>
                                            <td>{{ $payment->id ?? '—' }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $fullName !== '' ? $fullName : 'Unknown member' }}</div>
                                                @if($member && $member->role)
                                                    <div class="text-muted small">{{ $member->role->name ?? '' }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $member ? ($member->email ?? '—') : '—' }}</div>
                                                <div class="text-muted small">{{ $member ? ($member->phone_number ?? '—') : '—' }}</div>
                                            </td>
                                            <td>
                                                @if($membership)
                                                    <div class="fw-semibold">{{ $membership->name }}</div>
                                                    <div class="text-muted small">
                                                        @php
                                                            $currency = $membership->currency ?? 'PHP';
                                                            $price = $membership->price ?? 0;
                                                        @endphp
                                                        {{ $currency }} {{ number_format((float) $price, 2) }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">Membership unavailable</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">
                                                    {{ $statusMeta['label'] }}
                                                </span>
                                            </td>
                                            <td>{{ $purchasedAt }}</td>
                                            <td>{{ $expiresAt }}</td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    @if($member)
                                                        <a
                                                            href="{{ route('admin.gym-management.members.view', $member->id) }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                        >
                                                            Member
                                                        </a>
                                                    @endif
                                                    @if($membership)
                                                        <a
                                                            href="{{ route('admin.staff-account-management.memberships.view', $membership->id) }}"
                                                            class="btn btn-outline-primary btn-sm"
                                                        >
                                                            Membership
                                                        </a>
                                                    @endif
                                                    <a
                                                        href="{{ route('admin.staff-account-management.membership-payments.receipt', $payment->id) }}"
                                                        class="btn btn-outline-success btn-sm"
                                                    >
                                                        Receipt
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                No membership history found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} records
                        </div>
                        <div class="ms-auto">
                            {{ $payments->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('membership-history-print-submit');
            const printForm = document.getElementById('membership-history-print-form');
            const printLoader = document.getElementById('membership-history-print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) chips.push({ label: 'Search', value: filters.search });
                if (filters.membership_id) chips.push({ label: 'Membership ID', value: filters.membership_id });
                if (filters.status && filters.status !== 'all') chips.push({ label: 'Status', value: filters.status });
                if (filters.start || filters.end) chips.push({ label: 'Purchased', value: `${filters.start || '—'} → ${filters.end || '—'}` });
                return chips;
            }

            function buildRows(items) {
                return (items || []).map((item) => {
                    const role = item.role ? `<div class="muted">${item.role}</div>` : '';
                    const phone = item.phone ? `<div class="muted">${item.phone}</div>` : '';
                    const price = item.price ? `PHP ${item.price}` : '—';

                    return [
                        item.id ?? '—',
                        `<div class="fw">${item.member || '—'}</div>${role}`,
                        `<div>${item.email || '—'}</div>${phone}`,
                        item.membership || '—',
                        price,
                        item.status || '—',
                        item.purchased || '—',
                        item.expires || '—',
                    ];
                });
            }

            function renderPrintWindow(payload) {
                const rawItems = payload && payload.items ? payload.items : [];
                const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                const filters = buildFilters(payload.filters || {});
                const headers = ['#', 'Member', 'Contact', 'Membership', 'Price', 'Status', 'Purchased', 'Expires'];
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

                    if (!handled) {
                        printForm.submit();
                    }

                    printButton.disabled = false;
                    if (printLoader) printLoader.classList.add('d-none');
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('membership-history-filter-form');
            if (!form) {
                return;
            }

            const rangeButtons = form.querySelectorAll('.membership-history-range-chip');
            const startInput = document.getElementById('membership-history-start-date');
            const endInput = document.getElementById('membership-history-end-date');
            const statusInput = document.getElementById('membership-history-status-filter');
            const statusSelect = document.getElementById('status');
            const statusButtons = form.querySelectorAll('.membership-history-status-chip');

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function applyRange(range) {
                const today = new Date();
                const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const start = new Date(end);

                if (range === 'last-week') {
                    start.setDate(start.getDate() - 7);
                } else if (range === 'last-month') {
                    start.setMonth(start.getMonth() - 1);
                } else if (range === 'last-year') {
                    start.setFullYear(start.getFullYear() - 1);
                }

                if (startInput) startInput.value = formatDate(start);
                if (endInput) endInput.value = formatDate(end);
            }

            function setActiveStatus(status) {
                if (statusInput) statusInput.value = status;
                if (statusSelect) statusSelect.value = status;

                statusButtons.forEach((btn) => {
                    const isActive = btn.dataset.status === status;
                    btn.classList.toggle('btn-dark', isActive);
                    btn.classList.toggle('btn-outline-secondary', !isActive);
                });
            }

            statusButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const selectedStatus = this.dataset.status || '';
                    setActiveStatus(selectedStatus);
                    form.submit();
                });
            });

            if (statusSelect) {
                statusSelect.addEventListener('change', function () {
                    setActiveStatus(this.value || '');
                });
            }

            if (statusInput && statusInput.value) {
                setActiveStatus(statusInput.value);
            }

            rangeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const range = this.dataset.range;
                    applyRange(range);
                });
            });
        });
    </script>
@endsection
