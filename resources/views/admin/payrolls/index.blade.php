@extends('layouts.admin')
@section('title', 'Payrolls')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payrolls</h2>
                </div>
                <div class="d-flex align-items-center">
                    <a
                        href="{{ route('admin.payrolls.process') }}"
                        class="btn btn-primary d-flex align-items-center gap-2 me-2"
                    >
                        <i class="fa-solid fa-gears"></i>
                        Process payroll
                    </a>
                    <button type="button" class="btn btn-danger d-flex align-items-center gap-2">
                        <i class="fa-solid fa-print"></i>
                        Print
                    </button>
                </div>
            </div>

            @php
                $searchTerm = request('member_name');
                $pageCollection = collect($data->items());
                $pageCompleted = $pageCollection->filter(fn ($item) => $item->clockout_at)->count();
                $pagePending = $pageCollection->filter(fn ($item) => !$item->clockout_at)->count();
                $pageTotalHours = $pageCollection->sum(function ($item) {
                    if (!$item->clockin_at || !$item->clockout_at) {
                        return 0;
                    }

                    $clockIn = \Carbon\Carbon::parse($item->clockin_at);
                    $clockOut = \Carbon\Carbon::parse($item->clockout_at);

                    return round($clockOut->diffInMinutes($clockIn) / 60, 2);
                });
                $pageTotalAmount = $pageCollection->sum(function ($item) {
                    if (!$item->clockin_at || !$item->clockout_at) {
                        return 0;
                    }

                    $clockIn = \Carbon\Carbon::parse($item->clockin_at);
                    $clockOut = \Carbon\Carbon::parse($item->clockout_at);
                    $hours = $clockOut->diffInMinutes($clockIn) / 60;
                    $rate = $item->user->rate_per_hour ?? 0;

                    return round($hours * $rate, 2);
                });
            @endphp

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Payroll activity</h4>
                                <p class="text-muted mb-0">Track staff clock-ins, verify completed timesheets, and review payouts in one place.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $data->total() }} results</span>
                                <span class="d-block text-muted small">Page {{ $data->currentPage() }} of {{ $data->lastPage() }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.payrolls.index') }}" method="GET" id="payroll-filter-form" class="mt-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 240px;">
                                    <div class="position-relative">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <input
                                            type="search"
                                            class="form-control rounded-pill ps-5"
                                            name="member_name"
                                            placeholder="Search staff name"
                                            value="{{ $searchTerm }}"
                                            aria-label="Search staff name"
                                        />
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($searchTerm)
                                        <a href="{{ route('admin.payrolls.index') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif
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
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="tile tile-primary h-100">
                            <div class="tile-heading">Completed entries (page)</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-circle-check"></i>
                                <h2 class="float-end mb-0">{{ $pageCompleted }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="tile tile-primary h-100">
                            <div class="tile-heading">Pending clock-outs (page)</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-hourglass-half"></i>
                                <h2 class="float-end mb-0">{{ $pagePending }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="tile tile-primary h-100">
                            <div class="tile-heading">Total payout (page)</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-peso-sign"></i>
                                <h2 class="float-end mb-0">₱{{ number_format($pageTotalAmount, 2) }}</h2>
                            </div>
                        </div>
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
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Staff</th>
                                        <th scope="col">Clock in</th>
                                        <th scope="col">Clock out</th>
                                        <th scope="col">Total hours</th>
                                        <th scope="col">Total amount</th>
                                        <th scope="col">Created</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $item)
                                        @php
                                            $clockIn = $item->clockin_at ? \Carbon\Carbon::parse($item->clockin_at) : null;
                                            $clockOut = $item->clockout_at ? \Carbon\Carbon::parse($item->clockout_at) : null;
                                            $hoursWorked = ($clockIn && $clockOut)
                                                ? round($clockOut->diffInMinutes($clockIn) / 60, 2)
                                                : null;
                                            $totalAmount = $hoursWorked ? $hoursWorked * ($item->user->rate_per_hour ?? 0) : null;
                                        @endphp
                                        <tr>
                                            <td class="text-muted">#{{ $item->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $item->user->first_name }} {{ $item->user->last_name }}</div>
                                                @if(!is_null($item->user->rate_per_hour))
                                                    <span class="text-muted small">₱{{ number_format($item->user->rate_per_hour, 2) }} / hour</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clockIn)
                                                    <span class="fw-semibold d-block">{{ $clockIn->format('M d, Y') }}</span>
                                                    <span class="text-muted small">{{ $clockIn->format('g:i A') }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clockOut)
                                                    <span class="fw-semibold d-block">{{ $clockOut->format('M d, Y') }}</span>
                                                    <span class="text-muted small">{{ $clockOut->format('g:i A') }}</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning fw-semibold rounded-pill px-3 py-1">Awaiting</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!is_null($hoursWorked))
                                                    <span class="fw-semibold d-block">{{ number_format($hoursWorked, 2) }} hrs</span>
                                                    <span class="badge bg-success-subtle text-success fw-semibold rounded-pill px-3 py-1">Complete</span>
                                                @else
                                                    <span class="text-muted">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!is_null($totalAmount))
                                                    <span class="fw-semibold d-block">₱{{ number_format($totalAmount, 2) }}</span>
                                                    <span class="text-muted small">Calculated from hours worked</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold d-block">{{ $item->created_at?->format('M d, Y') }}</span>
                                                <span class="text-muted small">{{ $item->created_at?->format('g:i A') }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="action-button">
                                                        <a href="{{ route('admin.payrolls.view', $item->id) }}" title="View">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                No payroll entries found. Adjust your filters or check back later.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $data->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
