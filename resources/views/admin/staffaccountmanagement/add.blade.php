@extends('layouts.admin')
@section('title', 'Add Staff')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Add Staff</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.staff-account-management.store') }}" method="POST" enctype="multipart/form-data" id="main-form">
                                @csrf
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                @php
                                    $employmentType = old('employment_type', 'salaried');
                                    $employmentType = in_array($employmentType, ['salaried', 'contractor'], true)
                                        ? $employmentType
                                        : 'salaried';
                                    $allowSystemLogin = old('allow_system_login', '1');
                                    $includeStatutory = old('include_statutory_info', '1');
                                @endphp
                                <input type="hidden" name="employment_type" id="employment_type" value="{{ $employmentType }}">

                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body p-4">
                                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                            <div>
                                                <h5 class="fw-semibold mb-1">Personal Information</h5>
                                                <p class="text-muted small mb-0">Add staff details, payroll settings, and access.</p>
                                            </div>
                                        </div>
                                        <hr class="my-4">

                                        <div class="d-flex align-items-center flex-wrap gap-3">
                                            <img
                                                id="profilePreview"
                                                src="{{ asset('assets/images/profile-45x45.png') }}"
                                                alt="Profile preview"
                                                class="rounded-circle border"
                                                width="100"
                                                height="100"
                                                data-default="{{ asset('assets/images/profile-45x45.png') }}"
                                                data-existing=""
                                                data-has-existing="0"
                                            />
                                            <div class="flex-grow-1" style="max-width: 360px;">
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

                                        <div class="mt-4 border rounded-3 p-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <h6 class="fw-semibold mb-0">Personal Information</h6>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label fw-semibold">Employment Type <span class="required">*</span></label>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input employment-type-option"
                                                            type="radio"
                                                            name="employment_type_personal"
                                                            id="employmentTypeSalaried"
                                                            value="salaried"
                                                            {{ $employmentType === 'salaried' ? 'checked' : '' }}
                                                        />
                                                        <label class="form-check-label" for="employmentTypeSalaried">Salaried (Basic Pay)</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input employment-type-option"
                                                            type="radio"
                                                            name="employment_type_personal"
                                                            id="employmentTypeContractor"
                                                            value="contractor"
                                                            {{ $employmentType === 'contractor' ? 'checked' : '' }}
                                                        />
                                                        <label class="form-check-label" for="employmentTypeContractor">Contractor / Freelancer</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-3 mt-1">
                                                <div class="col-md-6">
                                                    <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name') }}" required />
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name') }}" required />
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="phone_number" class="form-label">Phone Number <span class="required">*</span></label>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="phone_number"
                                                        name="phone_number"
                                                        pattern="^\+639\d{9}$"
                                                        placeholder="+639XXXXXXXXX"
                                                        value="{{ old('phone_number') }}"
                                                        required
                                                    />
                                                    <div class="invalid-feedback">
                                                        Please enter a valid Philippine mobile number (e.g., +639123456789).
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="email" class="form-label">Email <span class="required">*</span></label>
                                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required />
                                                </div>
                                                <div class="col-12">
                                                    <label for="address" class="form-label">Address <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" required />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion mt-4" id="staffAddAccordion">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="payrollInfoHeading">
                                                    <button
                                                        class="accordion-button"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#payrollInfoCollapse"
                                                        aria-expanded="true"
                                                        aria-controls="payrollInfoCollapse"
                                                    >
                                                        Payroll Information
                                                    </button>
                                                </h2>
                                                <div
                                                    id="payrollInfoCollapse"
                                                    class="accordion-collapse collapse show"
                                                    aria-labelledby="payrollInfoHeading"
                                                    data-bs-parent="#staffAddAccordion"
                                                >
                                                    <div class="accordion-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Employment Type <span class="required">*</span></label>
                                                            <div class="d-flex flex-wrap gap-3">
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input employment-type-option"
                                                                        type="radio"
                                                                        name="employment_type_payroll"
                                                                        id="employmentTypeSalariedPayroll"
                                                                        value="salaried"
                                                                        {{ $employmentType === 'salaried' ? 'checked' : '' }}
                                                                    />
                                                                    <label class="form-check-label" for="employmentTypeSalariedPayroll">Salaried (Basic Pay)</label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input employment-type-option"
                                                                        type="radio"
                                                                        name="employment_type_payroll"
                                                                        id="employmentTypeContractorPayroll"
                                                                        value="contractor"
                                                                        {{ $employmentType === 'contractor' ? 'checked' : '' }}
                                                                    />
                                                                    <label class="form-check-label" for="employmentTypeContractorPayroll">Contractor / Freelancer</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label for="per_month_salary" class="form-label">Per Month Salary <span class="required">*</span></label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">₱</span>
                                                                    <input
                                                                        type="number"
                                                                        class="form-control"
                                                                        id="per_month_salary"
                                                                        name="per_month_salary"
                                                                        step="0.01"
                                                                        min="0"
                                                                        value="{{ old('per_month_salary') }}"
                                                                        required
                                                                    />
                                                                </div>
                                                                <small class="text-muted d-block mt-2" id="computedHourlyRate">Computed hourly rate: —</small>
                                                                <small class="text-muted d-block">Uses expected hours per week (defaults to 40 if blank).</small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="expected_hours_per_week" class="form-label">Expected Hours per Week (optional)</label>
                                                                <input
                                                                    type="number"
                                                                    class="form-control"
                                                                    id="expected_hours_per_week"
                                                                    name="expected_hours_per_week"
                                                                    step="0.25"
                                                                    min="0"
                                                                    value="{{ old('expected_hours_per_week') }}"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="statutoryInfoHeading">
                                                    <button
                                                        class="accordion-button collapsed"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#statutoryInfoCollapse"
                                                        aria-expanded="false"
                                                        aria-controls="statutoryInfoCollapse"
                                                    >
                                                        Statutory Information
                                                    </button>
                                                </h2>
                                                <div
                                                    id="statutoryInfoCollapse"
                                                    class="accordion-collapse collapse"
                                                    aria-labelledby="statutoryInfoHeading"
                                                    data-bs-parent="#staffAddAccordion"
                                                >
                                                    <div class="accordion-body">
                                                        <div class="mb-3" id="statutoryOptionWrapper">
                                                            <label class="form-label fw-semibold">Include statutory information?</label>
                                                            <div class="d-flex flex-wrap gap-3">
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input"
                                                                        type="radio"
                                                                        name="include_statutory_info"
                                                                        id="includeStatutoryYes"
                                                                        value="1"
                                                                        {{ $includeStatutory === '1' ? 'checked' : '' }}
                                                                    />
                                                                    <label class="form-check-label" for="includeStatutoryYes">Yes</label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input
                                                                        class="form-check-input"
                                                                        type="radio"
                                                                        name="include_statutory_info"
                                                                        id="includeStatutoryNo"
                                                                        value="0"
                                                                        {{ $includeStatutory === '0' ? 'checked' : '' }}
                                                                    />
                                                                    <label class="form-check-label" for="includeStatutoryNo">No</label>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted d-block mt-1">Optional for contractor/freelancer.</small>
                                                        </div>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label for="tin_number" class="form-label">TIN Number</label>
                                                                <input type="text" class="form-control" id="tin_number" name="tin_number" value="{{ old('tin_number') }}" />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="sss_number" class="form-label">SSS Number</label>
                                                                <input type="text" class="form-control" id="sss_number" name="sss_number" value="{{ old('sss_number') }}" />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="philhealth_number" class="form-label">PhilHealth Number</label>
                                                                <input type="text" class="form-control" id="philhealth_number" name="philhealth_number" value="{{ old('philhealth_number') }}" />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="pagibig_number" class="form-label">Pag-IBIG Number</label>
                                                                <input type="text" class="form-control" id="pagibig_number" name="pagibig_number" value="{{ old('pagibig_number') }}" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 border rounded-3 p-3">
                                            <h6 class="fw-semibold mb-3">System Access</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="password" class="form-label">Password <span class="required">*</span></label>
                                                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required />
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="password_confirmation" class="form-label">Confirm Password <span class="required">*</span></label>
                                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required />
                                                </div>
                                            </div>
                                            <div class="form-check mt-3">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    id="allow_system_login"
                                                    name="allow_system_login"
                                                    value="1"
                                                    {{ $allowSystemLogin ? 'checked' : '' }}
                                                />
                                                <label class="form-check-label fw-semibold" for="allow_system_login">Allow system login</label>
                                                <div class="text-muted small ms-4">
                                                    Used for staff portal access only (attendance, schedule, payslips).
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-center gap-3 p-4 pt-0">
                                        <a class="btn btn-outline-secondary" href="{{ route('admin.staff-account-management.index') }}">Cancel</a>
                                        <button class="btn btn-danger" type="submit" id="submitButton">
                                            <span id="loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                            Submit
                                        </button>
                                    </div>
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
                    <h5 class="modal-title fw-semibold" id="formConfirmModalLabel">Create staff account?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Confirm the details before adding this staff member.</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Name</span>
                            <span class="fw-semibold" id="confirmName">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Email</span>
                            <span class="fw-semibold" id="confirmEmail">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Phone</span>
                            <span class="fw-semibold" id="confirmPhone">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Employment Type</span>
                            <span class="fw-semibold" id="confirmEmploymentType">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Per Month Salary</span>
                            <span class="fw-semibold" id="confirmRate">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Expected Hours</span>
                            <span class="fw-semibold" id="confirmExpectedHours">—</span>
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
                        Yes, create
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
        const monthlySalaryInput = document.getElementById('per_month_salary');
        const expectedHoursInput = document.getElementById('expected_hours_per_week');
        const addressInput = document.getElementById('address');
        const employmentTypeHidden = document.getElementById('employment_type');
        const employmentTypeOptions = document.querySelectorAll('.employment-type-option');
        const allowSystemLoginToggle = document.getElementById('allow_system_login');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const computedHourlyRate = document.getElementById('computedHourlyRate');
        const statutoryOptionWrapper = document.getElementById('statutoryOptionWrapper');
        const includeStatutoryYes = document.getElementById('includeStatutoryYes');
        const includeStatutoryNo = document.getElementById('includeStatutoryNo');
        const statutoryInputs = [
            document.getElementById('tin_number'),
            document.getElementById('sss_number'),
            document.getElementById('philhealth_number'),
            document.getElementById('pagibig_number')
        ].filter(Boolean);
        const confirmName = document.getElementById('confirmName');
        const confirmEmail = document.getElementById('confirmEmail');
        const confirmPhone = document.getElementById('confirmPhone');
        const confirmEmploymentType = document.getElementById('confirmEmploymentType');
        const confirmRate = document.getElementById('confirmRate');
        const confirmExpectedHours = document.getElementById('confirmExpectedHours');
        const confirmAddress = document.getElementById('confirmAddress');
        const confirmModalEl = document.getElementById('formConfirmModal');
        const confirmModal = confirmModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(confirmModalEl) : null;
        const confirmActionButton = document.getElementById('confirmActionButton');
        const confirmActionLoader = document.getElementById('confirmActionLoader');
        const profileInput = document.getElementById('profile_picture');
        const profilePreview = document.getElementById('profilePreview');
        const removeProfileButton = document.getElementById('removeProfileButton');
        const removeProfileInput = document.getElementById('remove_profile_picture');
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
                }
                profilePreview.src = profilePreview.dataset.hasExisting === '1' ? existingImage : defaultImage;
                removeProfileInput.value = 0;
                setRemoveButtonState();
            });
        }

        const employmentLabels = {
            salaried: 'Salaried (Basic Pay)',
            contractor: 'Contractor / Freelancer'
        };

        const setStatutoryEnabled = (enabled) => {
            statutoryInputs.forEach((input) => {
                input.disabled = !enabled;
                if (!enabled) {
                    input.value = '';
                }
            });
        };

        const shouldIncludeStatutory = () => {
            if (includeStatutoryYes?.checked) return true;
            if (includeStatutoryNo?.checked) return false;
            return true;
        };

        const updateStatutoryFields = () => {
            const employmentType = employmentTypeHidden?.value || 'salaried';
            const isContractor = employmentType === 'contractor';

            if (statutoryOptionWrapper) {
                statutoryOptionWrapper.classList.toggle('d-none', !isContractor);
            }

            if (isContractor) {
                if (includeStatutoryYes && includeStatutoryNo && !includeStatutoryYes.checked && !includeStatutoryNo.checked) {
                    includeStatutoryNo.checked = true;
                }
                setStatutoryEnabled(shouldIncludeStatutory());
            } else {
                if (includeStatutoryYes) includeStatutoryYes.checked = true;
                if (includeStatutoryNo) includeStatutoryNo.checked = false;
                setStatutoryEnabled(true);
            }
        };

        const syncEmploymentType = (value) => {
            if (!employmentTypeHidden) {
                return;
            }
            const nextValue = value || employmentTypeHidden.value || 'salaried';
            employmentTypeHidden.value = nextValue;
            employmentTypeOptions.forEach((option) => {
                option.checked = option.value === nextValue;
            });
            updateStatutoryFields();
        };

        if (employmentTypeOptions.length && employmentTypeHidden) {
            syncEmploymentType(employmentTypeHidden.value);
            employmentTypeOptions.forEach((option) => {
                option.addEventListener('change', () => {
                    if (option.checked) {
                        syncEmploymentType(option.value);
                    }
                });
            });
        }

        includeStatutoryYes?.addEventListener('change', updateStatutoryFields);
        includeStatutoryNo?.addEventListener('change', updateStatutoryFields);

        const updateSystemAccess = () => {
            const allowLogin = !!allowSystemLoginToggle?.checked;
            if (passwordInput) {
                passwordInput.disabled = !allowLogin;
                passwordInput.required = allowLogin;
                if (!allowLogin) {
                    passwordInput.value = '';
                }
            }
            if (passwordConfirmationInput) {
                passwordConfirmationInput.disabled = !allowLogin;
                passwordConfirmationInput.required = allowLogin;
                if (!allowLogin) {
                    passwordConfirmationInput.value = '';
                }
            }
        };

        allowSystemLoginToggle?.addEventListener('change', updateSystemAccess);
        updateSystemAccess();

        const buildName = () => {
            const first = firstNameInput?.value?.trim() || '';
            const last = lastNameInput?.value?.trim() || '';
            return (first + ' ' + last).trim() || '—';
        };

        const formatCurrency = (value) => {
            if (value === '' || value === null || value === undefined) return '—';
            const num = Number(value);
            if (Number.isNaN(num)) return value;
            return `₱${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        const formatEmploymentType = (value) => employmentLabels[value] || '—';

        const formatExpectedHours = (value) => {
            if (value === '' || value === null || value === undefined) return '—';
            const num = Number(value);
            if (Number.isNaN(num)) return value;
            return `${num} hrs / week`;
        };

        const WEEKS_PER_MONTH = 52 / 12;
        const DEFAULT_HOURS_PER_WEEK = 40;

        const computeHourlyRate = () => {
            if (!monthlySalaryInput) {
                return null;
            }
            const monthlySalary = Number(monthlySalaryInput.value);
            if (Number.isNaN(monthlySalary) || monthlySalary <= 0) {
                return null;
            }
            const rawHours = expectedHoursInput ? Number(expectedHoursInput.value) : NaN;
            const hoursPerWeek = !Number.isNaN(rawHours) && rawHours > 0 ? rawHours : DEFAULT_HOURS_PER_WEEK;
            return monthlySalary / (hoursPerWeek * WEEKS_PER_MONTH);
        };

        const updateComputedHourlyRate = () => {
            if (!computedHourlyRate) {
                return;
            }
            const rate = computeHourlyRate();
            if (!rate) {
                computedHourlyRate.textContent = 'Computed hourly rate: —';
                return;
            }
            computedHourlyRate.textContent = `Computed hourly rate: ₱${rate.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        monthlySalaryInput?.addEventListener('input', updateComputedHourlyRate);
        expectedHoursInput?.addEventListener('input', updateComputedHourlyRate);
        updateComputedHourlyRate();

        const populateConfirmation = () => {
            confirmName.textContent = buildName();
            confirmEmail.textContent = emailInput?.value?.trim() || '—';
            confirmPhone.textContent = phoneInput?.value?.trim() || '—';
            if (confirmEmploymentType) {
                confirmEmploymentType.textContent = formatEmploymentType(employmentTypeHidden?.value);
            }
            confirmRate.textContent = formatCurrency(monthlySalaryInput?.value);
            if (confirmExpectedHours) {
                confirmExpectedHours.textContent = formatExpectedHours(expectedHoursInput?.value);
            }
            confirmAddress.textContent = addressInput?.value?.trim() || '—';
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
