@extends('layouts.admin')
@section('title', 'Payroll History')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payroll History</h2>
                    <p class="text-muted mb-0">Review processed payroll runs for staff.</p>
                </div>
                <div class="d-flex align-items-center">
                    <a
                        href="{{ route('admin.payrolls.process') }}"
                        class="btn btn-primary d-flex align-items-center gap-2"
                    >
                        <i class="fa-solid fa-gears"></i>
                        Process payroll
                    </a>
                </div>
            </div>

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                                <h4 class="fw-semibold mb-1">Find a payroll run</h4>
                                <p class="text-muted mb-0">Search by staff or period month to locate processed runs.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $runs->total() }} results</span>
                                <span class="d-block text-muted small">Page {{ $runs->currentPage() }} of {{ $runs->lastPage() }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.payrolls.index') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label text-muted small mb-1" for="member_name">Staff</label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input
                                        type="search"
                                        class="form-control rounded-pill ps-5"
                                        name="member_name"
                                        id="member_name"
                                        placeholder="Search staff name or email"
                                        value="{{ request('member_name') }}"
                                        aria-label="Search staff name or email"
                                    />
                                </div>
                            </div>

                            <div class="col-12 col-md-4 col-lg-3">
                                <label class="form-label text-muted small mb-1" for="period_month">Period month</label>
                                <input
                                    type="month"
                                    class="form-control rounded-pill"
                                    name="period_month"
                                    id="period_month"
                                    value="{{ request('period_month') }}"
                                    aria-label="Filter by payroll month"
                                />
                            </div>

                            <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                                <a href="{{ route('admin.payrolls.index') }}" class="btn btn-link text-decoration-none text-muted px-0">
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

            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
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
                                        <th scope="col">Hours</th>
                                        <th scope="col">Gross</th>
                                        <th scope="col">Net</th>
                                        <th scope="col">Processed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($runs as $run)
                                        @php
                                            $staff = $run->user;
                                            $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                                            $periodLabel = $run->period_month ?? '—';
                                            $processedAt = $run->processed_at
                                                ? $run->processed_at->format('M d, Y g:i A')
                                                : ($run->created_at?->format('M d, Y g:i A') ?? '—');
                                        @endphp
                                        <tr>
                                            <td>{{ $run->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $name }}</div>
                                                <span class="text-muted small">{{ $staff->email ?? '—' }}</span>
                                            </td>
                                            <td>{{ $periodLabel }}</td>
                                            <td><span class="fw-semibold">{{ number_format((float) ($run->total_hours ?? 0), 2) }}</span> hrs</td>
                                            <td>₱{{ number_format((float) ($run->gross_pay ?? 0), 2) }}</td>
                                            <td class="text-success fw-semibold">₱{{ number_format((float) ($run->net_pay ?? 0), 2) }}</td>
                                            <td>{{ $processedAt }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No payroll runs found. Adjust your filters or check back later.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $runs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
