@extends('layouts.admin')
@section('title', 'Staff View')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $data->loadMissing(['role']);

        $profilePicture = $data->profile_picture
            ? asset($data->profile_picture)
            : asset('assets/images/profile-45x45.png');
        $fullName = trim(($data->first_name ?? '') . ' ' . ($data->last_name ?? ''));
        $roleName = $data->role->name ?? 'Staff';
        $rate = $data->rate_per_hour !== null ? number_format((float) $data->rate_per_hour, 2) : '—';
    @endphp
    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img src="{{ $profilePicture }}" alt="Staff photo" class="detail-avatar">
                <div class="flex-grow-1">
                    <div class="hero-label mb-1">Team member</div>
                    <h2 class="hero-title mb-1">{{ $fullName ?: 'Staff' }}</h2>
                    <div class="hero-subtitle">User code: {{ $data->user_code ?? '—' }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="detail-pill">
                            <i class="fa-solid fa-id-badge"></i>
                            <span>{{ $roleName }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-envelope"></i>
                            <span>{{ $data->email ?? 'No email' }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-phone"></i>
                            <span>{{ $data->phone_number ?? 'No phone' }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-solid fa-sack-dollar"></i></span>
                        Rate/hr: ₱{{ $rate }}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Profile details</h5>
                <span class="text-muted detail-meta">Updated {{ optional($data->updated_at)->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="table-responsive">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th scope="row">Name</th>
                            <td>{{ $fullName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">User code</th>
                            <td>{{ $data->user_code ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Role</th>
                            <td>{{ $roleName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Address</th>
                            <td>{{ $data->address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td>{{ $data->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Phone number</th>
                            <td>{{ $data->phone_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Rate per hour</th>
                            <td>{{ $rate }}</td>
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
