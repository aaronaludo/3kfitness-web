@extends('layouts.admin')
@section('title', 'Sales')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Sales</h2>
                    <p class="text-muted mb-0">Revenue from approved, non-archived membership payments.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.staff-account-management.membership-payments') }}">Membership Payments</a>
                </div>
            </div>

            <div class="col-12 mb-3">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <form action="{{ route('admin.sales.index') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-sm-4">
                                <label for="start-date" class="form-label small text-muted mb-1">Start date</label>
                                <input type="date" id="start-date" name="start_date" class="form-control" value="{{ request('start_date', optional($start)->toDateString()) }}" />
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="end-date" class="form-label small text-muted mb-1">End date</label>
                                <input type="date" id="end-date" name="end_date" class="form-control" value="{{ request('end_date', optional($end)->toDateString()) }}" />
                            </div>
                            <div class="col-12 col-sm-4 d-flex gap-2">
                                <button type="submit" class="btn btn-danger mt-auto"><i class="fa-solid fa-magnifying-glass me-2"></i>Apply</button>
                                <a href="{{ route('admin.sales.index') }}" class="btn btn-light mt-auto">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Total Revenue</div>
                                <div class="h4 mb-0">{{ $currency }} {{ number_format((float) $totalRevenue, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small">Total Sales</div>
                                <div class="h4 mb-0">{{ $totalSales }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="text-muted small mb-2">New Memberships (period)</div>
                                <div class="d-flex gap-3">
                                    <span class="badge bg-success">Approved: {{ $statusTallies['approved'] ?? 0 }}</span>
                                    <span class="badge bg-warning text-dark">Pending: {{ $statusTallies['pending'] ?? 0 }}</span>
                                    <span class="badge bg-danger">Rejected: {{ $statusTallies['rejected'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Revenue over time</div>
                                </div>
                                <canvas id="salesLine"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Revenue by membership</div>
                                </div>
                                <canvas id="salesPie"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lineCtx = document.getElementById('salesLine');
            if (lineCtx) {
                const lineChart = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: 'Revenue ({{ $currency }})',
                            data: @json($series),
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.3,
                            fill: true,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            const pieCtx = document.getElementById('salesPie');
            if (pieCtx) {
                const pieChart = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($pieLabels),
                        datasets: [{
                            label: 'Revenue Share',
                            data: @json($pieValues),
                            backgroundColor: [
                                '#dc3545', '#0d6efd', '#198754', '#ffc107', '#20c997', '#6f42c1', '#6c757d', '#fd7e14'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection


