@extends('layouts.admin')
@section('title', 'Process Payroll')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div
                    class="card border-0 shadow-sm rounded-4 text-white"
                    style="background: linear-gradient(120deg, #111827 0%, #1f2937 60%, #ef4444 120%);"
                >
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-white text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Payroll Run</span>
                                <h2 class="fw-bold mb-2">Process payroll</h2>
                                <p class="text-white-50 mb-3">
                                    Review hours, deductions, and payouts for {{ $monthLabel }} before finalizing your payroll.
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.payrolls.index') }}" class="btn btn-light d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        Back to payroll list
                                    </a>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger text-white fw-semibold rounded-pill px-3 py-2">{{ $monthLabel }}</span>
                                <h3 class="display-6 fw-bold mb-1">₱{{ number_format($stats['projected_net'], 2) }}</h3>
                                <p class="text-white-50 mb-0">Projected payout for selected month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <form action="{{ route('admin.payrolls.process') }}" method="GET" class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label text-muted text-uppercase small mb-1">Search staff</label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input
                                        type="search"
                                        name="search"
                                        value="{{ $search }}"
                                        class="form-control rounded-pill ps-5"
                                        placeholder="Search by name or email"
                                    />
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-muted text-uppercase small mb-1">Payroll month</label>
                                <input
                                    type="month"
                                    name="month"
                                    value="{{ $month }}"
                                    class="form-control rounded-pill"
                                    max="{{ now()->format('Y-m') }}"
                                />
                            </div>
                            <div class="col-12 col-md-4 d-flex justify-content-md-end gap-2">
                                <a href="{{ route('admin.payrolls.process') }}" class="btn btn-outline-secondary rounded-pill px-4">Reset</a>
                                <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-rotate"></i>
                                    Update view
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-12 mb-3">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Staff in this run</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-user-group text-danger fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $stats['staff_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Pending clock-outs</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-hourglass-half text-warning fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $stats['pending_entries'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Total hours</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-clock-rotate-left text-primary fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ number_format($stats['total_hours'], 2) }} hrs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Net payout</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-peso-sign text-success fs-4"></i>
                                    <span class="fs-4 fw-bold">₱{{ number_format($stats['projected_net'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                @forelse ($summaries as $summary)
                    @php
                        $staff = $summary['staff'];
                        $collapseId = 'payroll-breakdown-' . $staff->id;
                    @endphp
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                <div>
                                    <h5 class="fw-semibold mb-1">{{ $staff->first_name }} {{ $staff->last_name }}</h5>
                                    <div class="text-muted small">{{ $staff->email }}</div>
                                    <span class="badge bg-light text-dark fw-semibold rounded-pill px-3 py-2 mt-2">
                                        ₱{{ number_format($staff->rate_per_hour ?? 0, 2) }} / hr
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="text-start">
                                        <div class="text-muted small text-uppercase">Hours</div>
                                        <div class="fw-bold fs-5">{{ number_format($summary['total_hours'], 2) }}</div>
                                    </div>
                                    <div class="text-start">
                                        <div class="text-muted small text-uppercase">Gross</div>
                                        <div class="fw-bold fs-5">₱{{ number_format($summary['gross_pay'], 2) }}</div>
                                    </div>
                                    <div class="text-start">
                                        <div class="text-muted small text-uppercase">Net</div>
                                        <div class="fw-bold fs-5 text-success">₱{{ number_format($summary['net_pay'], 2) }}</div>
                                    </div>
                                    <div>
                                        <span class="badge {{ $summary['pending_entries'] ? 'bg-warning text-dark' : 'bg-success' }} rounded-pill px-3 py-2">
                                            {{ $summary['pending_entries'] ? $summary['pending_entries'] . ' pending entries' : 'Ready to finalize' }}
                                        </span>
                                    </div>
                                    @php
                                        $printEntries = $summary['entries']->map(function ($entry) {
                                            return [
                                                'id' => $entry['id'],
                                                'clockin' => $entry['clockin_at'] ? $entry['clockin_at']->format('M d, Y g:i A') : '—',
                                                'clockout' => $entry['clockout_at'] ? $entry['clockout_at']->format('M d, Y g:i A') : '—',
                                                'hours' => $entry['hours'],
                                                'amount' => $entry['amount'],
                                                'status' => $entry['status'],
                                            ];
                                        })->values();

                                        $payslipData = [
                                            'name' => $staff->first_name . ' ' . $staff->last_name,
                                            'email' => $staff->email,
                                            'rate' => $staff->rate_per_hour ?? 0,
                                            'gross' => $summary['gross_pay'],
                                            'net' => $summary['net_pay'],
                                            'deductions' => $summary['deductions'],
                                            'month' => $monthLabel,
                                            'entries' => $printEntries,
                                        ];

                                        $payslipJson = json_encode($payslipData);
                                    @endphp
                                    <button
                                        type="button"
                                        class="btn btn-danger rounded-pill px-3 d-flex align-items-center gap-2"
                                        data-payslip='{{ $payslipJson }}'
                                    >
                                        <i class="fa-solid fa-file-pdf"></i>
                                        Print payslip
                                    </button>
                                    <button
                                        class="btn btn-outline-primary rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                        aria-controls="{{ $collapseId }}"
                                    >
                                        Review details
                                    </button>
                                </div>
                            </div>

                            <div class="collapse {{ $loop->first ? 'show' : '' }} mt-3" id="{{ $collapseId }}">
                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <div class="border rounded-4 p-3 h-100 bg-light">
                                            <h6 class="fw-semibold mb-3">Payroll summary</h6>
                                            <ul class="list-unstyled small mb-0">
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>Gross pay</span>
                                                    <span>₱{{ number_format($summary['gross_pay'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>SSS</span>
                                                    <span>₱{{ number_format($summary['deductions']['sss'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>PhilHealth</span>
                                                    <span>₱{{ number_format($summary['deductions']['philhealth'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>Pag-IBIG</span>
                                                    <span>₱{{ number_format($summary['deductions']['pagibig'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between fw-semibold pt-2 border-top">
                                                    <span>Net pay</span>
                                                    <span>₱{{ number_format($summary['net_pay'], 2) }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-8">
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col">Entry ID</th>
                                                        <th scope="col">Clock in</th>
                                                        <th scope="col">Clock out</th>
                                                        <th scope="col">Hours</th>
                                                        <th scope="col">Amount</th>
                                                        <th scope="col">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($summary['entries'] as $entry)
                                                        <tr>
                                                            <td class="text-muted">#{{ $entry['id'] }}</td>
                                                            <td>
                                                                {{ $entry['clockin_at'] ? $entry['clockin_at']->format('M d, Y g:i A') : '—' }}
                                                            </td>
                                                            <td>
                                                                {{ $entry['clockout_at'] ? $entry['clockout_at']->format('M d, Y g:i A') : '—' }}
                                                            </td>
                                                            <td>{{ $entry['hours'] ? number_format($entry['hours'], 2) . ' hrs' : 'Pending' }}</td>
                                                            <td>
                                                                {{ $entry['amount'] ? '₱' . number_format($entry['amount'], 2) : '—' }}
                                                            </td>
                                                            <td>
                                                                @if ($entry['status'] === 'complete')
                                                                    <span class="badge bg-success-subtle text-success fw-semibold rounded-pill px-3 py-2">Complete</span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning fw-semibold rounded-pill px-3 py-2">Awaiting clock-out</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted py-3">
                                                                No payroll entries available for this staff in {{ $monthLabel }}.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body text-center py-5">
                            <h5 class="fw-semibold mb-2">No payroll data found</h5>
                            <p class="text-muted mb-3">Try selecting a different month or adjusting your search filters.</p>
                            <a href="{{ route('admin.payrolls.index') }}" class="btn btn-danger rounded-pill px-4">Go back to payroll list</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttons = document.querySelectorAll('[data-payslip]');

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                let data = {};
                try {
                    data = JSON.parse(btn.dataset.payslip || '{}');
                } catch (e) {
                    console.error('Invalid payslip data', e);
                    return;
                }

                const entries = data.entries || [];
                const style = `
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111827; }
                        .payslip { max-width: 800px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 24px; border-radius: 12px; }
                        .header { text-align: center; margin-bottom: 24px; }
                        .header h1 { margin: 0 0 8px; }
                        .muted { color: #6b7280; font-size: 13px; }
                        .section { margin-bottom: 20px; }
                        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
                        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
                        th { background: #f3f4f6; }
                        .totals { background: #fef2f2; }
                        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; }
                        .badge-success { background: #dcfce7; color: #166534; }
                        .badge-warning { background: #fef9c3; color: #854d0e; }
                    </style>
                `;

                const rows = entries.map((entry) => {
                    const status = entry.status === 'complete'
                        ? '<span class="badge badge-success">Complete</span>'
                        : '<span class="badge badge-warning">Pending</span>';

                    return `
                        <tr>
                            <td>#${entry.id ?? '—'}</td>
                            <td>${entry.clockin ?? '—'}</td>
                            <td>${entry.clockout ?? '—'}</td>
                            <td>${Number(entry.hours || 0).toFixed(2)} hrs</td>
                            <td>₱${Number(entry.amount || 0).toFixed(2)}</td>
                            <td>${status}</td>
                        </tr>
                    `;
                }).join('');

                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>Payslip - ${data.name || ''}</title>
                            ${style}
                        </head>
                        <body>
                            <div class="payslip">
                                <div class="header">
                                    <h1>Payroll Payslip</h1>
                                    <div class="muted">3kfitness Gym • ${data.month || ''}</div>
                                </div>
                                <div class="section grid">
                                    <div><strong>Employee:</strong> ${data.name || '—'}</div>
                                    <div><strong>Email:</strong> ${data.email || '—'}</div>
                                    <div><strong>Hourly rate:</strong> ₱${Number(data.rate || 0).toFixed(2)}</div>
                                    <div><strong>Period:</strong> ${data.month || '—'}</div>
                                </div>
                                <div class="section">
                                    <strong>Entries</strong>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Entry</th>
                                                <th>Clock in</th>
                                                <th>Clock out</th>
                                                <th>Hours</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${rows || '<tr><td colspan="6" style="text-align:center;">No entries</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                                <div class="section">
                                    <strong>Summary</strong>
                                    <table class="totals">
                                        <tbody>
                                            <tr><td>Gross pay</td><td>₱${Number(data.gross || 0).toFixed(2)}</td></tr>
                                            <tr><td>SSS</td><td>₱${Number(data.deductions?.sss || 0).toFixed(2)}</td></tr>
                                            <tr><td>PhilHealth</td><td>₱${Number(data.deductions?.philhealth || 0).toFixed(2)}</td></tr>
                                            <tr><td>Pag-IBIG</td><td>₱${Number(data.deductions?.pagibig || 0).toFixed(2)}</td></tr>
                                            <tr><th>Net pay</th><th>₱${Number(data.net || 0).toFixed(2)}</th></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <script>window.print();<\/script>
                        </body>
                    </html>
                `;

                const printWindow = window.open('', '_blank', 'width=900,height=1200');
                if (!printWindow) return;
                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
            });
        });
    });
</script>
@endsection
