@extends('layouts.admin')
@section('title', 'Payment Receipt')

@section('styles')
    <style>
        .receipt-shell {
            max-width: 880px;
            margin: 0 auto;
        }

        .receipt-card {
            border: 1px solid #eef1f5;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08);
        }

        .receipt-hero {
            background: linear-gradient(120deg, #ff6b6b 0%, #ffa45c 50%, #ffd86f 100%);
            color: #fff;
            padding: 26px 30px;
        }

        .receipt-hero .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
        }

        .receipt-body {
            padding: 26px 30px 30px;
            background: #fff;
        }

        .receipt-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            background: #fff7ed;
            border: 1px dashed #f3c59b;
            color: #92400e;
            letter-spacing: 0.01em;
            box-shadow: none;
        }

        .receipt-pill::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
        }

        .receipt-detail-label {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.78rem;
            color: #8a94a6;
            font-weight: 700;
        }

        .receipt-table th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.06em;
            color: #8a94a6;
            border-bottom: 1px solid #e9edf3;
        }

        .receipt-table td {
            border-color: #e9edf3;
            vertical-align: middle;
        }

        .receipt-note {
            background: #f6f8fb;
            border: 1px dashed #d8deea;
            border-radius: 12px;
            padding: 14px 16px;
            color: #5b6471;
        }

        .no-print {
            display: inherit;
        }

        @media print {
            @page {
                margin: 10mm;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: #fff !important;
            }

            body * {
                visibility: hidden;
            }

            #receipt-print,
            #receipt-print * {
                visibility: visible;
            }

            #receipt-print {
                position: absolute;
                inset: 0;
                margin: 0;
                padding: 0 6mm;
                width: auto;
            }

            .no-print,
            header,
            nav,
            footer {
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2 no-print">
                <div>
                    <h2 class="title mb-0">Payment Receipt</h2>
                    <p class="text-muted mb-0">Show this receipt at the front desk.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-danger" onclick="window.print()"><i class="fa-solid fa-print"></i>&nbsp; Print</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.staff-account-management.membership-payments') }}">Back to Payments</a>
                </div>
            </div>

            <div class="col-xl-9 col-lg-10 mx-auto">
                <div id="receipt-print" class="receipt-shell">
                    <div class="receipt-card">
                        <div class="receipt-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="brand-mark shadow-sm">3K</div>
                                <div>
                                    <div class="fw-bold fs-5 mb-0">3K Fitness</div>
                                    <div class="small text-white-50">Walk-in payment receipt</div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-uppercase small fw-semibold text-white-50">Receipt #</div>
                                <div class="fw-bold fs-5">{{ $record->id }}</div>
                                <div class="small text-white-50">Issued {{ $createdAt->format('F j, Y g:i A') }}</div>
                            </div>
                        </div>

                        <div class="receipt-body">
                            <div class="row g-4 mb-3">
                                <div class="col-md-7">
                                    <div class="receipt-detail-label mb-1">Member</div>
                                    <div class="fw-semibold fs-5 mb-1">
                                        {{ optional($record->user)->first_name }} {{ optional($record->user)->last_name }}
                                    </div>
                                    <div class="text-muted small">{{ optional($record->user)->email }}</div>
                                    <div class="text-muted small">{{ optional($record->user)->phone_number }}</div>
                                </div>
                                <div class="col-md-5">
                                    <div class="receipt-detail-label mb-1">Membership</div>
                                    <div class="fw-semibold">{{ optional($record->membership)->name }}</div>
                                    <div class="mt-2 receipt-pill">
                                        <i class="fa-regular fa-calendar-check"></i>
                                        Valid until {{ $record->expiration_at ? \Carbon\Carbon::parse($record->expiration_at)->format('F j, Y') : '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive receipt-table">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-dark">
                                                {{ optional($record->membership)->name }}
                                            </td>
                                            <td class="text-end fw-semibold fs-6">
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
                                            <th class="text-uppercase text-muted">Total</th>
                                            <th class="text-end fs-5">{{ ($currency ?? 'PHP') }} {{ number_format((float) ($price ?? 0), 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-4 receipt-note">
                                Thank you for choosing 3K Fitness. Keep this receipt for your records and present it at the front desk
                                when requested.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
