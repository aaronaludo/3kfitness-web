@extends('layouts.admin')
@section('title', 'Payment Receipt')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payment Receipt</h2>
                    <p class="text-muted mb-0">Show this receipt at the front desk.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-danger" onclick="window.print()"><i class="fa-solid fa-print"></i>&nbsp; Print</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.staff-account-management.user-memberships') }}">Back to Payments</a>
                </div>
            </div>

            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-4">
                            <div>
                                <div class="fw-bold">3K Fitness</div>
                                <div class="text-muted small">Walk-in Payment Receipt</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Receipt #</div>
                                <div class="fw-bold">{{ $record->id }}</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="text-muted small">Member</div>
                                <div class="fw-semibold">{{ optional($record->user)->first_name }} {{ optional($record->user)->last_name }}</div>
                                <div class="small">{{ optional($record->user)->email }}</div>
                                <div class="small">{{ optional($record->user)->phone_number }}</div>
                            </div>
                            <div class="col-12 col-md-6 text-md-end">
                                <div class="text-muted small">Date</div>
                                <div class="fw-semibold">{{ $createdAt->format('F j, Y g:i A') }}</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            {{ optional($record->membership)->name }}
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $currency = optional($record->membership)->currency ?: 'PHP';
                                                $price = optional($record->membership)->price ?: 0;
                                            @endphp
                                            {{ $currency }} {{ number_format((float) $price, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ ($currency ?? 'PHP') }} {{ number_format((float) ($price ?? 0), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4 small text-muted">
                            Thank you for your purchase. Membership valid until: {{ $record->expiration_at }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


