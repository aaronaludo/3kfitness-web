@extends('layouts.admin')
@section('title', 'Admin View')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $user->loadMissing(['role', 'status']);
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $roleName = optional($user->role)->name ?? 'Admin';
        $statusName = optional($user->status)->name ?? '—';
        $statusClass = match (strtolower($statusName)) {
            'active', 'enabled' => 'success',
            'pending' => 'warning',
            'inactive', 'disabled' => 'danger',
            default => 'neutral',
        };
        $profilePicture = $user->profile_picture ? asset($user->profile_picture) : asset('assets/images/profile-45x45.png');
    @endphp
    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img src="{{ $profilePicture }}" alt="Admin photo" class="detail-avatar">
                <div class="flex-grow-1">
                    <div class="hero-label mb-1">Admin account</div>
                    <h2 class="hero-title mb-1">{{ $fullName ?: 'Admin' }}</h2>
                    <div class="hero-subtitle">User code: {{ $user->user_code ?? '—' }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="detail-pill">
                            <i class="fa-solid fa-id-badge"></i>
                            <span>{{ $roleName }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-envelope"></i>
                            <span>{{ $user->email ?? 'No email' }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-phone"></i>
                            <span>{{ $user->phone_number ?? 'No phone' }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-solid fa-user-shield"></i></span>
                        Admin privileges
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Profile details</h5>
                <span class="text-muted detail-meta">Updated {{ optional($user->updated_at)->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="table-responsive">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th scope="row">Name</th>
                            <td>{{ $fullName ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">User code</th>
                            <td>{{ $user->user_code ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Role</th>
                            <td>{{ $roleName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td>
                                <span class="detail-badge {{ $statusClass }}">{{ $statusName }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td>{{ $user->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Phone number</th>
                            <td>{{ $user->phone_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Address</th>
                            <td>{{ $user->address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>{{ optional($user->created_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Last Updated</th>
                            <td>{{ optional($user->updated_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Created By</th>
                            <td>{{ $user->created_by ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
