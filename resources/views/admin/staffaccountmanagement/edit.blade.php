@extends('layouts.admin')
@section('title', 'Edit Staff')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Edit Staff</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            @php
                                $canEditAll = in_array(auth()->user()->role_id ?? null, [1, 4], true);
                            @endphp
                            <form action="{{ route('admin.staff-account-management.update', $data->id) }}" method="POST" enctype="multipart/form-data" id="main-form">
                                @csrf
                                @method('PUT')
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="mb-3 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Name:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <div class="row w-100 g-2">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $data->first_name) }}" required />
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $data->last_name) }}" required />
                                                </div>
                                            </div>
                                        @else
                                            <p class="form-control-plaintext mb-0" id="readonlyName">{{ $data->first_name }} {{ $data->last_name }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Email:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $data->email) }}" required />
                                        @else
                                            <p class="form-control-plaintext mb-0" id="readonlyEmail">{{ $data->email }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Rate per hour:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="number" class="form-control" id="rate_per_hour" name="rate_per_hour" step="0.01" min="0" value="{{ old('rate_per_hour', $data->rate_per_hour) }}" required />
                                        @else
                                            <p class="form-control-plaintext mb-0" id="readonlyRate">PHP {{ number_format((float) $data->rate_per_hour, 2) }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-12">
                                        @if(!$canEditAll)
                                            <div class="alert alert-info mb-0">
                                                Only address and phone number can be updated on this page.
                                            </div>
                                        @else
                                            <div class="alert alert-secondary mb-0">
                                                Admins and super admins can update all staff details. Leave the password blank to keep it unchanged.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="address" class="col-sm-12 col-lg-2 col-form-label">Address: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $data->address) }}" required/>
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
                                            placeholder="+639XXXXXXXXX"
                                            value="{{ old('phone_number', $data->phone_number) }}"
                                            required
                                        />
                                        <div class="invalid-feedback">
                                            Please enter a valid Philippine mobile number (e.g., +639123456789).
                                        </div>
                                    </div>
                                </div>
                                @if($canEditAll)
                                    <div class="mb-3 row">
                                        <label for="password" class="col-sm-12 col-lg-2 col-form-label">Password:</label>
                                        <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" />
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="password_confirmation" class="col-sm-12 col-lg-2 col-form-label">Password Confirmation:</label>
                                        <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
                                        </div>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-center mt-5 mb-4">
                                    <button class="btn btn-danger" type="submit" id="submitButton">
                                        <span id="loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="formConfirmModal" tabindex="-1" aria-labelledby="formConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="formConfirmModalLabel">Update staff info?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Confirm the contact details before saving.</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Name</span>
                            <span class="fw-semibold" id="confirmName">{{ $data->first_name }} {{ $data->last_name }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Email</span>
                            <span class="fw-semibold" id="confirmEmail">{{ $data->email }}</span>
                        </div>
                        @if($canEditAll)
                            <div class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Rate / Hour</span>
                                <span class="fw-semibold" id="confirmRate">PHP {{ number_format((float) $data->rate_per_hour, 2) }}</span>
                            </div>
                        @endif
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Phone</span>
                            <span class="fw-semibold" id="confirmPhone">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Address</span>
                            <span class="fw-semibold" id="confirmAddress">—</span>
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
        const phoneInput = document.getElementById('phone_number');
        const addressInput = document.getElementById('address');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const emailInput = document.getElementById('email');
        const rateInput = document.getElementById('rate_per_hour');
        const readonlyName = document.getElementById('readonlyName');
        const readonlyEmail = document.getElementById('readonlyEmail');
        const readonlyRate = document.getElementById('readonlyRate');
        const confirmPhone = document.getElementById('confirmPhone');
        const confirmAddress = document.getElementById('confirmAddress');
        const confirmName = document.getElementById('confirmName');
        const confirmEmail = document.getElementById('confirmEmail');
        const confirmRate = document.getElementById('confirmRate');
        const confirmModalEl = document.getElementById('formConfirmModal');
        const confirmModal = confirmModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(confirmModalEl) : null;
        const confirmActionButton = document.getElementById('confirmActionButton');
        const confirmActionLoader = document.getElementById('confirmActionLoader');
        let allowSubmit = false;

        const buildName = () => {
            const first = firstNameInput?.value?.trim();
            const last = lastNameInput?.value?.trim();
            if (first || last) {
                return `${first || ''} ${last || ''}`.trim();
            }
            return readonlyName?.textContent?.trim() || '—';
        };

        const getEmail = () => {
            return emailInput?.value?.trim() || readonlyEmail?.textContent?.trim() || '—';
        };

        const formatRate = () => {
            if (rateInput?.value) {
                const num = Number(rateInput.value);
                if (!Number.isNaN(num)) {
                    return `PHP ${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }
                return rateInput.value;
            }
            return readonlyRate?.textContent?.trim() || '—';
        };

        const populateConfirmation = () => {
            confirmPhone.textContent = phoneInput?.value?.trim() || '—';
            confirmAddress.textContent = addressInput?.value?.trim() || '—';
            if (confirmName) {
                confirmName.textContent = buildName();
            }
            if (confirmEmail) {
                confirmEmail.textContent = getEmail();
            }
            if (confirmRate) {
                confirmRate.textContent = formatRate();
            }
        };

        form?.addEventListener('submit', function(e) {
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
