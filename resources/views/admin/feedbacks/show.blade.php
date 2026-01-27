@extends('layouts.admin')
@section('title', 'Feedback Details')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div><h2 class="title">Feedback Details</h2></div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.feedbacks.index') }}" class="btn btn-light">Back</a>
                    <a href="{{ route('admin.feedbacks.edit', $feedback) }}" class="btn btn-danger">Edit</a>
                </div>
            </div>

            <div class="col-12 mt-3">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
            </div>

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        @php
                            $user = $feedback->user;
                            $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
                            $memberName = $fullName !== '' ? $fullName : ($user->name ?? $user->email ?? 'Guest');
                        @endphp
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Details</span>
                                <h4 class="fw-semibold mb-1">Feedback from {{ $memberName }}</h4>
                                <p class="text-muted mb-0">Submitted on {{ optional($feedback->created_at)->format('M d, Y g:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $feedback->admin_confirmation_status ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $feedback->admin_confirmation_status ? 'Confirmed' : 'Pending' }}
                                </span>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-lg-4 mb-3">
                                <div class="text-muted">Member</div>
                                <div class="fw-semibold">{{ $memberName }}</div>
                                @if($user && $user->email)
                                    <div class="text-muted small">{{ $user->email }}</div>
                                @endif
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="text-muted">Confirmation</div>
                                <div class="fw-semibold">{{ $feedback->admin_confirmation_status ? 'Confirmed' : 'Pending' }}</div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="text-muted">Last updated</div>
                                <div class="fw-semibold">{{ optional($feedback->updated_at)->format('M d, Y g:i A') }}</div>
                            </div>
                        </div>

                        <hr />

                        <div class="mb-3">
                            <div class="text-muted">Title</div>
                            <h4 class="fw-semibold">{{ $feedback->title }}</h4>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted">Message</div>
                            <p class="mb-0">{{ $feedback->description }}</p>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <form method="POST" action="{{ route('admin.feedbacks.destroy', $feedback) }}" onsubmit="return confirm('Delete this feedback?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
