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
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Membership History</h2>
                    <p class="text-muted mb-0 small">Track past membership purchases, approvals, and expirations for members.</p>
                </div>
                <div class="d-flex gap-2">
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

                        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                            @foreach ($statusLabels as $key => $label)
                                <span class="badge {{ $activeStatus === $key ? $label['class'] : 'bg-light text-dark' }} px-3 py-2">
                                    {{ $label['label'] }}
                                    @php
                                        $count = $statusTallies[$key] ?? null;
                                    @endphp
                                    @if(!is_null($count))
                                        <span class="ms-2 fw-semibold">{{ $count }}</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>

                        <form action="{{ route('admin.history.memberships') }}" method="GET" class="row g-3 align-items-end mt-3">
                            <div class="col-12 col-lg-3">
                                <label for="search" class="form-label text-muted small fw-semibold">Search member or membership</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="e.g. Jane Doe, Premium"
                                    >
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="membership_id" class="form-label text-muted small fw-semibold">Membership</label>
                                <select id="membership_id" name="membership_id" class="form-select">
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
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="status" class="form-label text-muted small fw-semibold">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="approved" {{ ($filters['status'] ?? 'all') === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="pending" {{ ($filters['status'] ?? 'all') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="rejected" {{ ($filters['status'] ?? 'all') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="start_date" class="form-label text-muted small fw-semibold">Purchased from</label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    class="form-control"
                                    value="{{ $filters['start_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="end_date" class="form-label text-muted small fw-semibold">Purchased to</label>
                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    class="form-control"
                                    value="{{ $filters['end_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                @if ($hasFilters)
                                    <a href="{{ route('admin.history.memberships') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                @endif
                                <button type="submit" class="btn btn-danger">
                                    Apply
                                </button>
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
                                            <td>{{ ($payments->firstItem() ?? 0) + $index }}</td>
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
@endsection
