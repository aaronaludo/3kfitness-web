@extends('layouts.admin')
@section('title', 'Edit Feedback')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div><h2 class="title">Edit Feedback</h2></div>
                <div>
                    <a href="{{ route('admin.feedbacks.show', $feedback) }}" class="btn btn-light">Back</a>
                </div>
            </div>

            <div class="col-12 mt-3">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Update</span>
                                <h4 class="fw-semibold mb-1">Edit feedback entry</h4>
                                <p class="text-muted mb-0">Update feedback details, status, or the linked member.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Last updated {{ optional($feedback->updated_at)->format('M d, Y g:i A') }}</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.feedbacks.update', $feedback) }}" class="mt-4">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Member ID (optional)</label>
                                    <input
                                        type="number"
                                        name="user_id"
                                        class="form-control rounded-3"
                                        value="{{ old('user_id', $feedback->user_id) }}"
                                        placeholder="Enter member user ID"
                                    />
                                    <small class="text-muted">Leave blank to save as guest feedback.</small>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check mt-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="isadminread"
                                            id="isadminread"
                                            value="1"
                                            {{ old('isadminread', $feedback->isadminread) ? 'checked' : '' }}
                                        />
                                        <label class="form-check-label" for="isadminread">Mark as read</label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Title</label>
                                    <input
                                        type="text"
                                        name="title"
                                        class="form-control rounded-3"
                                        value="{{ old('title', $feedback->title) }}"
                                        maxlength="120"
                                        required
                                    />
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea
                                        name="description"
                                        class="form-control rounded-3"
                                        rows="6"
                                        maxlength="1000"
                                        required
                                    >{{ old('description', $feedback->description) }}</textarea>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.feedbacks.show', $feedback) }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-danger">Update Feedback</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
