@extends('layouts.admin')
@section('title', 'View Membership Payment')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $data->loadMissing(['user.role', 'membership']);

        $payer = $data->user;
        $membership = $data->membership;
        $payerFirst = $payer?->first_name ?? '';
        $payerLast = $payer?->last_name ?? '';
        $payerName = trim($payerFirst . ' ' . $payerLast);
        $payerName = $payerName !== '' ? $payerName : ($payer?->email ?? 'Member');
        $payerEmail = $payer?->email ?? '—';
        $payerPhone = $payer?->phone_number ?? '—';
        $profilePicture = $payer?->profile_picture
            ? asset($payer->profile_picture)
            : asset('assets/images/profile-45x45.png');

        $statusMap = [
            0 => ['label' => 'Pending approval', 'class' => 'warning', 'icon' => 'fa-hourglass-half'],
            1 => ['label' => 'Approved', 'class' => 'success', 'icon' => 'fa-circle-check'],
            2 => ['label' => 'Rejected', 'class' => 'danger', 'icon' => 'fa-circle-xmark'],
        ];
        $status = $statusMap[(int) $data->isapproved] ?? $statusMap[0];

        $expiresAt = $data->expiration_at ? \Carbon\Carbon::parse($data->expiration_at) : null;
        $expiresText = $expiresAt ? $expiresAt->format('M d, Y g:i A') : '—';

        $amountText = $membership && $membership->price !== null
            ? ($membership->currency . ' ' . number_format((float) $membership->price, 2))
            : '—';

        $proof = $data->proof_of_payment && $data->proof_of_payment !== 'blank_for_now'
            ? asset($data->proof_of_payment)
            : null;
    @endphp

    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img src="{{ $profilePicture }}" alt="Member photo" class="detail-avatar sm">
                <div class="flex-grow-1">
                    <div class="hero-label mb-1">Membership payment</div>
                    <h2 class="hero-title mb-1">{{ $membership?->name ?? 'Membership' }}</h2>
                    <div class="hero-subtitle">Paid by {{ $payerName }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="detail-pill">
                            <i class="fa-solid fa-user"></i>
                            <span>{{ $payerName }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-envelope"></i>
                            <span>{{ $payerEmail }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-money-check-dollar"></i>
                            <span>{{ $amountText }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="detail-badge {{ $status['class'] }}">
                        <i class="fa-solid {{ $status['icon'] }}"></i>
                        {{ $status['label'] }}
                    </span>
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-solid fa-calendar-check"></i></span>
                        Expires {{ $expiresText }}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Payment details</h5>
                <span class="text-muted detail-meta">Payment #{{ $data->id }}</span>
            </div>
            <div class="table-responsive">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th scope="row">Member</th>
                            <td>{{ $payerName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td>{{ $payerEmail }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Phone</th>
                            <td>{{ $payerPhone }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Membership</th>
                            <td>{{ $membership?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Amount</th>
                            <td>{{ $amountText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td>{{ $status['label'] }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Expiration</th>
                            <td>{{ $expiresText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Proof of payment</th>
                            <td>
                                @if ($proof)
                                    <a href="{{ $proof }}" target="_blank" rel="noopener" class="fw-bold text-danger">
                                        View file
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>{{ optional($data->created_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Last Updated</th>
                            <td>{{ optional($data->updated_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
