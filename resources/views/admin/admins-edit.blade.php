@extends('layouts.admin')
@section('title', 'Edit Admin')

@section('content')
    @php
        $user->loadMissing(['role', 'status']);
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $updateRouteAvailable = Route::has('admin.admins.update');
        $updateAction = $updateRouteAvailable ? route('admin.admins.update', $user->id) : '#';
        $currentProfilePicture = $user->profile_picture ?? null;
        $currentProfilePictureUrl = $currentProfilePicture ? asset($currentProfilePicture) : asset('assets/images/profile-45x45.png');
    @endphp
    <div class="container-fluid">
        <div class="row gy-4">
            <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="title mb-1">Edit admin</h2>
                    <p class="text-muted mb-0">Update contact details or credentials for {{ $fullName ?: 'this admin' }}.</p>
                </div>
            </div>
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <form action="{{ $updateAction }}" method="POST" id="main-form" novalidate enctype="multipart/form-data">
                            @csrf
                            @if ($updateRouteAvailable)
                                @method('PUT')
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            <div class="alert alert-secondary">
                                Leave the password fields blank to keep the current password unchanged.
                            </div>
                            @unless($updateRouteAvailable)
                                <div class="alert alert-warning">
                                    The update route has not been configured yet. Enable it to save changes from this page.
                                </div>
                            @endunless
                            <div class="mb-3 row">
                                <label for="profile_picture" class="col-sm-12 col-lg-2 col-form-label">Profile Picture:</label>
                                <div class="col-lg-10 col-sm-12">
                                    <div class="d-flex align-items-center flex-wrap gap-3">
                                        <img
                                            id="profilePreview"
                                            src="{{ $currentProfilePictureUrl }}"
                                            alt="Profile preview"
                                            class="rounded-circle border"
                                            width="100"
                                            height="100"
                                            data-default="{{ asset('assets/images/profile-45x45.png') }}"
                                            data-existing="{{ $currentProfilePicture ? asset($currentProfilePicture) : '' }}"
                                            data-has-existing="{{ $currentProfilePicture ? '1' : '0' }}"
                                        />
                                        <div class="flex-grow-1" style="max-width: 320px;">
                                            <input
                                                type="file"
                                                class="form-control mb-2"
                                                id="profile_picture"
                                                name="profile_picture"
                                                accept="image/*"
                                            />
                                            <input type="hidden" name="remove_profile_picture" id="remove_profile_picture" value="0">
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-danger btn-sm" id="removeProfileButton">
                                                    Remove Photo
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                Accepted formats: JPG, PNG, GIF up to 2MB.
                                            </small>
                                        </div>
                                    </div>
                                    @error('profile_picture')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="first_name" class="col-sm-12 col-lg-2 col-form-label">First name: <span class="required">*</span></label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="last_name" class="col-sm-12 col-lg-2 col-form-label">Last name: <span class="required">*</span></label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="address" class="col-sm-12 col-lg-2 col-form-label">Address: <span class="required">*</span></label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $user->address) }}" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="phone_number" class="col-sm-12 col-lg-2 col-form-label">
                                    Phone number: <span class="required">*</span>
                                </label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="phone_number"
                                        name="phone_number"
                                        pattern="^\+639\d{9}$"
                                        placeholder="+639XXXXXXXXX"
                                        value="{{ old('phone_number', $user->phone_number) }}"
                                        required
                                    />
                                    <div class="invalid-feedback">
                                        Please enter a valid Philippine mobile number (e.g., +639123456789).
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="email" class="col-sm-12 col-lg-2 col-form-label">Email: <span class="required">*</span></label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="password" class="col-sm-12 col-lg-2 col-form-label">Password:</label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input type="password" class="form-control" id="password" name="password" />
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="password_confirmation" class="col-sm-12 col-lg-2 col-form-label">Password Confirmation:</label>
                                <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" />
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-5 mb-4">
                                <button class="btn btn-danger" type="submit" id="submitButton" {{ $updateRouteAvailable ? '' : 'disabled' }}>
                                    <span id="loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="formConfirmModal" tabindex="-1" aria-labelledby="formConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="formConfirmModalLabel">Update admin account?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Confirm the details before saving this admin.</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Name</span>
                            <span class="fw-semibold" id="confirmName">{{ $fullName ?: '—' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Email</span>
                            <span class="fw-semibold" id="confirmEmail">{{ $user->email }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Phone</span>
                            <span class="fw-semibold" id="confirmPhone">{{ $user->phone_number ?? '—' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Address</span>
                            <span class="fw-semibold" id="confirmAddress">{{ $user->address ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Review again</button>
                    <button type="button" class="btn btn-danger" id="confirmActionButton">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="confirmActionLoader" role="status" aria-hidden="true"></span>
                        Yes, save changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('main-form');
        const submitButton = document.getElementById('submitButton');
        const loader = document.getElementById('loader');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone_number');
        const addressInput = document.getElementById('address');
        const confirmName = document.getElementById('confirmName');
        const confirmEmail = document.getElementById('confirmEmail');
        const confirmPhone = document.getElementById('confirmPhone');
        const confirmAddress = document.getElementById('confirmAddress');
        const confirmModalEl = document.getElementById('formConfirmModal');
        const confirmModal = confirmModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(confirmModalEl) : null;
        const confirmActionButton = document.getElementById('confirmActionButton');
        const confirmActionLoader = document.getElementById('confirmActionLoader');
        const profileInput = document.getElementById('profile_picture');
        const profilePreview = document.getElementById('profilePreview');
        const removeProfileButton = document.getElementById('removeProfileButton');
        const removeProfileInput = document.getElementById('remove_profile_picture');
        const updateEnabled = {{ $updateRouteAvailable ? 'true' : 'false' }};
        let allowSubmit = false;

        if (profileInput && profilePreview && removeProfileButton && removeProfileInput) {
            const defaultImage = profilePreview.dataset.default;
            const existingImage = profilePreview.dataset.existing || defaultImage;

            const setRemoveButtonState = () => {
                const hasExisting = profilePreview.dataset.hasExisting === '1';
                const hasNewFile = profileInput.files.length > 0;
                removeProfileButton.disabled = !hasExisting && !hasNewFile;
            };

            setRemoveButtonState();

            profileInput.addEventListener('change', (event) => {
                const [file] = event.target.files;

                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        profilePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    removeProfileInput.value = 0;
                } else {
                    profilePreview.src = profilePreview.dataset.hasExisting === '1' ? existingImage : defaultImage;
                    removeProfileInput.value = 0;
                }

                setRemoveButtonState();
            });

            removeProfileButton.addEventListener('click', () => {
                if (profileInput.files.length) {
                    profileInput.value = '';
                    profilePreview.src = profilePreview.dataset.hasExisting === '1' ? existingImage : defaultImage;
                    removeProfileInput.value = 0;
                } else if (profilePreview.dataset.hasExisting === '1') {
                    profilePreview.src = defaultImage;
                    profilePreview.dataset.hasExisting = '0';
                    removeProfileInput.value = 1;
                }

                setRemoveButtonState();
            });
        }

        const buildName = () => {
            const first = firstNameInput?.value?.trim() || '';
            const last = lastNameInput?.value?.trim() || '';
            return (first + ' ' + last).trim() || '—';
        };

        const populateConfirmation = () => {
            confirmName.textContent = buildName();
            confirmEmail.textContent = emailInput?.value?.trim() || '—';
            confirmPhone.textContent = phoneInput?.value?.trim() || '—';
            confirmAddress.textContent = addressInput?.value?.trim() || '—';
        };

        form?.addEventListener('submit', function(e) {
            if (!updateEnabled) {
                e.preventDefault();
                return;
            }
            if (!form.checkValidity()) {
                return;
            }
            if (!allowSubmit) {
                e.preventDefault();
                populateConfirmation();
                if (confirmModal) {
                    confirmModal.show();
                } else {
                    allowSubmit = true;
                    submitButton.disabled = true;
                    loader.classList.remove('d-none');
                    form.submit();
                }
            } else {
                submitButton.disabled = true;
                loader.classList.remove('d-none');
            }
        });

        confirmActionButton?.addEventListener('click', function () {
            if (!updateEnabled) {
                return;
            }
            allowSubmit = true;
            submitButton.disabled = true;
            confirmActionButton.disabled = true;
            confirmActionLoader.classList.remove('d-none');
            loader.classList.remove('d-none');
            confirmModal?.hide();
            form.submit();
        });
    </script>
@endsection
