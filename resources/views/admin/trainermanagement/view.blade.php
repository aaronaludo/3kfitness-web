@extends('layouts.admin')
@section('title', 'Trainer Management - View')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $trainer->loadMissing(['role'])->loadCount('trainerSchedules');

        $profilePicture = $trainer->profile_picture
            ? asset($trainer->profile_picture)
            : asset('assets/images/profile-45x45.png');
        $fullName = trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
        $roleName = $trainer->role->name ?? 'Trainer';
    @endphp
    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img src="{{ $profilePicture }}" alt="Trainer photo" class="detail-avatar">
                <div class="flex-grow-1">
                    <div class="hero-label mb-1">Trainer</div>
                    <h2 class="hero-title mb-1">{{ $fullName ?: 'Trainer' }}</h2>
                    <div class="hero-subtitle">User code: {{ $trainer->user_code ?? '—' }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="detail-pill">
                            <i class="fa-solid fa-id-badge"></i>
                            <span>{{ $roleName }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-envelope"></i>
                            <span>{{ $trainer->email ?? 'No email' }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-phone"></i>
                            <span>{{ $trainer->phone_number ?? 'No phone' }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-solid fa-dumbbell"></i></span>
                        Classes assigned: {{ $trainer->trainer_schedules_count ?? 0 }}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Profile details</h5>
                <span class="text-muted detail-meta">Updated {{ optional($trainer->updated_at)->format('M d, Y') ?? '—' }}</span>
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
                            <td>{{ $trainer->user_code ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Role</th>
                            <td>{{ $roleName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Address</th>
                            <td>{{ $trainer->address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td>{{ $trainer->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Phone number</th>
                            <td>{{ $trainer->phone_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Classes assigned</th>
                            <td>{{ $trainer->trainer_schedules_count ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>{{ optional($trainer->created_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Last Updated</th>
                            <td>{{ optional($trainer->updated_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
