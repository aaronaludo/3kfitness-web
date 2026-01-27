@extends('layouts.admin')
@section('title', 'Create Feedback')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div><h2 class="title">Add Feedback</h2></div>
                <div>
                    <a href="{{ route('admin.feedbacks.index') }}" class="btn btn-light">Back</a>
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
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">New</span>
                                <h4 class="fw-semibold mb-1">Create feedback entry</h4>
                                <p class="text-muted mb-0">Log a new feedback record or add one on behalf of a member.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.feedbacks.store') }}" class="mt-4">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Member ID (optional)</label>
                                    <input
                                        type="number"
                                        name="user_id"
                                        class="form-control rounded-3"
                                        value="{{ old('user_id') }}"
                                        placeholder="Enter member user ID"
                                    />
                                    <small class="text-muted">Leave blank to save as guest feedback.</small>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">Confirmation</label>
                                    <div class="form-check mt-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="admin_confirmation_status"
                                            id="admin_confirmation_status"
                                            value="1"
                                            {{ old('admin_confirmation_status') ? 'checked' : '' }}
                                        />
                                        <label class="form-check-label" for="admin_confirmation_status">Mark as confirmed</label>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Title</label>
                                    <input
                                        type="text"
                                        name="title"
                                        class="form-control rounded-3"
                                        value="{{ old('title') }}"
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
                                    >{{ old('description') }}</textarea>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.feedbacks.index') }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-danger">Save Feedback</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
