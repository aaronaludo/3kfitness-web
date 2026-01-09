@extends('layouts.admin')
@section('title', 'Payroll Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 mt-2">
            <div>
                <h2 class="title mb-1">Payroll Report</h2>
                <p class="text-muted mb-0">Snapshot of payroll totals across all processed runs.</p>
            </div>
            <div class="text-end text-muted small">
                <div>Total runs: {{ $runsCount }}</div>
                <div>Last updated: {{ now()->format('M d, Y g:i A') }}</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="row g-3">
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Gross</div>
                            <h4 class="mb-0">₱{{ number_format($totals['gross'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Net</div>
                            <h4 class="mb-0 text-success">₱{{ number_format($totals['net'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small fw-semibold mb-1">SSS</div>
                            <h4 class="mb-0">₱{{ number_format($totals['sss'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small fw-semibold mb-1">PhilHealth</div>
                            <h4 class="mb-0">₱{{ number_format($totals['philhealth'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small fw-semibold mb-1">Pag-IBIG</div>
                            <h4 class="mb-0">₱{{ number_format($totals['pagibig'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body">
                            <div class="text-muted text-uppercase small fw-semibold mb-1">3K Fitness App Cut</div>
                            <h4 class="mb-0">₱{{ number_format($totals['app_cut'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(($runsCount ?? 0) === 0)
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body text-center text-muted py-5">
                        <i class="fa-solid fa-clipboard-list fa-2x mb-3"></i>
                        <h5 class="fw-semibold mb-1">No payroll runs yet</h5>
                        <p class="mb-0">Process payroll to see gross, net, and deduction totals here.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
