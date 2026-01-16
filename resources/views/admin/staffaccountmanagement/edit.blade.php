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
                                $profilePicture = $data->profile_picture ?? null;
                                $profilePictureUrl = $profilePicture ? asset($profilePicture) : asset('assets/images/profile-45x45.png');
                                $employmentType = old('employment_type', $data->employment_type ?? 'salaried');
                                $employmentType = in_array($employmentType, ['salaried', 'contractor'], true)
                                    ? $employmentType
                                    : 'salaried';
                                $employmentTypeLabel = match ($employmentType ?? '') {
                                    'salaried' => 'Salaried (Basic Pay)',
                                    'contractor' => 'Contractor / Freelancer',
                                    default => '—',
                                };
                                $expectedHoursLabel = $data->expected_hours_per_week !== null && $data->expected_hours_per_week !== ''
                                    ? number_format((float) $data->expected_hours_per_week, 2) . ' hrs/week'
                                    : '—';
                                $hoursForMonthly = $data->expected_hours_per_week !== null && $data->expected_hours_per_week !== ''
                                    ? (float) $data->expected_hours_per_week
                                    : 40;
                                $perMonthSalary = $data->rate_per_hour !== null
                                    ? round((float) $data->rate_per_hour * $hoursForMonthly * (52 / 12), 2)
                                    : null;
                                $perMonthSalaryValue = old('per_month_salary', $perMonthSalary ?? '');
                                $perMonthSalaryLabel = $perMonthSalary !== null
                                    ? 'PHP ' . number_format((float) $perMonthSalary, 2)
                                    : '—';
                                $allowSystemLogin = old('allow_system_login', (int) ($data->allow_system_login ?? 1));
                                $hasStatutory = !empty($data->tin_number)
                                    || !empty($data->sss_number)
                                    || !empty($data->philhealth_number)
                                    || !empty($data->pagibig_number);
                                $includeStatutory = old('include_statutory_info', $hasStatutory ? '1' : '0');
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
                                    <label for="profile_picture" class="col-sm-12 col-lg-2 col-form-label">Profile Picture:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="d-flex align-items-center flex-wrap gap-3">
                                            <img
                                                id="profilePreview"
                                                src="{{ $profilePictureUrl }}"
                                                alt="Profile preview"
                                                class="rounded-circle border"
                                                width="100"
                                                height="100"
                                                data-default="{{ asset('assets/images/profile-45x45.png') }}"
                                                data-existing="{{ $profilePicture ? asset($profilePicture) : '' }}"
                                                data-has-existing="{{ $profilePicture ? '1' : '0' }}"
                                            />
                                            @if($canEditAll)
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
                                            @else
                                                <p class="mb-0 text-muted small">Profile photo updates are restricted.</p>
                                                <input type="hidden" name="remove_profile_picture" id="remove_profile_picture" value="0">
                                            @endif
                                        </div>
                                        @error('profile_picture')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
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
                                    <label class="col-sm-12 col-lg-2 col-form-label">Employment type:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <div class="d-flex flex-wrap gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="employment_type" id="employmentTypeSalaried" value="salaried" {{ $employmentType === 'salaried' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="employmentTypeSalaried">Salaried (Basic Pay)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="employment_type" id="employmentTypeContractor" value="contractor" {{ $employmentType === 'contractor' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="employmentTypeContractor">Contractor / Freelancer</label>
                                                </div>
                                            </div>
                                        @else
                                            <p class="form-control-plaintext mb-0">{{ $employmentTypeLabel }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Per Month Salary:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <div class="w-100">
                                                <input type="number" class="form-control" id="per_month_salary" name="per_month_salary" step="0.01" min="0" value="{{ $perMonthSalaryValue }}" required />
                                                <small class="text-muted d-block mt-2" id="computedHourlyRate">Computed hourly rate: —</small>
                                                <small class="text-muted d-block">Uses expected hours per week (defaults to 40 if blank).</small>
                                            </div>
                                        @else
                                            <p class="form-control-plaintext mb-0" id="readonlyRate">{{ $perMonthSalaryLabel }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="expected_hours_per_week" class="col-sm-12 col-lg-2 col-form-label">Expected hours/week:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="number" class="form-control" id="expected_hours_per_week" name="expected_hours_per_week" step="0.25" min="0" value="{{ old('expected_hours_per_week', $data->expected_hours_per_week) }}" />
                                        @else
                                            <p class="form-control-plaintext mb-0">{{ $expectedHoursLabel }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-12">
                                        <h6 class="fw-semibold mb-1">Statutory Information</h6>
                                        <p class="text-muted small mb-0">Optional IDs for payroll compliance.</p>
                                    </div>
                                </div>
                                @if($canEditAll)
                                    <div class="mb-3 row" id="statutoryOptionWrapper">
                                        <label class="col-sm-12 col-lg-2 col-form-label">Include statutory info?</label>
                                        <div class="col-lg-10 col-sm-12 d-flex align-items-center">
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
                                            <div class="text-muted small ms-3">Optional for contractor/freelancer.</div>
                                        </div>
                                    </div>
                                @endif
                                <div class="mb-3 row">
                                    <label for="tin_number" class="col-sm-12 col-lg-2 col-form-label">TIN number:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="text" class="form-control" id="tin_number" name="tin_number" value="{{ old('tin_number', $data->tin_number) }}" />
                                        @else
                                            <p class="form-control-plaintext mb-0">{{ $data->tin_number ?? '—' }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="sss_number" class="col-sm-12 col-lg-2 col-form-label">SSS number:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="text" class="form-control" id="sss_number" name="sss_number" value="{{ old('sss_number', $data->sss_number) }}" />
                                        @else
                                            <p class="form-control-plaintext mb-0">{{ $data->sss_number ?? '—' }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="philhealth_number" class="col-sm-12 col-lg-2 col-form-label">PhilHealth number:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="text" class="form-control" id="philhealth_number" name="philhealth_number" value="{{ old('philhealth_number', $data->philhealth_number) }}" />
                                        @else
                                            <p class="form-control-plaintext mb-0">{{ $data->philhealth_number ?? '—' }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="pagibig_number" class="col-sm-12 col-lg-2 col-form-label">Pag-IBIG number:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        @if($canEditAll)
                                            <input type="text" class="form-control" id="pagibig_number" name="pagibig_number" value="{{ old('pagibig_number', $data->pagibig_number) }}" />
                                        @else
                                            <p class="form-control-plaintext mb-0">{{ $data->pagibig_number ?? '—' }}</p>
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
                                        <label class="col-sm-12 col-lg-2 col-form-label">System access:</label>
                                        <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                            <input type="hidden" name="allow_system_login" value="0">
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    id="allow_system_login"
                                                    name="allow_system_login"
                                                    value="1"
                                                    {{ $allowSystemLogin ? 'checked' : '' }}
                                                />
                                                <label class="form-check-label fw-semibold" for="allow_system_login">Allow system login</label>
                                                <div class="text-muted small">
                                                    Used for staff portal access only (attendance, schedule, payslips).
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                <span class="text-muted">Employment Type</span>
                                <span class="fw-semibold" id="confirmEmploymentType">—</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Per Month Salary</span>
                                <span class="fw-semibold" id="confirmRate">{{ $perMonthSalaryLabel }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Expected Hours</span>
                                <span class="fw-semibold" id="confirmExpectedHours">—</span>
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
        const monthlySalaryInput = document.getElementById('per_month_salary');
        const expectedHoursInput = document.getElementById('expected_hours_per_week');
        const employmentTypeInputs = document.querySelectorAll('input[name="employment_type"]');
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
        const readonlyName = document.getElementById('readonlyName');
        const readonlyEmail = document.getElementById('readonlyEmail');
        const readonlyRate = document.getElementById('readonlyRate');
        const confirmPhone = document.getElementById('confirmPhone');
        const confirmAddress = document.getElementById('confirmAddress');
        const confirmName = document.getElementById('confirmName');
        const confirmEmail = document.getElementById('confirmEmail');
        const confirmEmploymentType = document.getElementById('confirmEmploymentType');
        const confirmRate = document.getElementById('confirmRate');
        const confirmExpectedHours = document.getElementById('confirmExpectedHours');
        const confirmModalEl = document.getElementById('formConfirmModal');
        const confirmModal = confirmModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(confirmModalEl) : null;
        const confirmActionButton = document.getElementById('confirmActionButton');
        const confirmActionLoader = document.getElementById('confirmActionLoader');
        const profileInput = document.getElementById('profile_picture');
        const profilePreview = document.getElementById('profilePreview');
        const removeProfileButton = document.getElementById('removeProfileButton');
        const removeProfileInput = document.getElementById('remove_profile_picture');
        let allowSubmit = false;

        if (profilePreview && removeProfileInput && profileInput && removeProfileButton) {
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
            const employmentType = getEmploymentTypeValue() || 'salaried';
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

        const getEmploymentTypeValue = () => {
            if (!employmentTypeInputs.length) {
                return '';
            }
            const selected = Array.from(employmentTypeInputs).find((input) => input.checked);
            return selected ? selected.value : '';
        };

        const formatEmploymentType = () => {
            const value = getEmploymentTypeValue();
            return employmentLabels[value] || '—';
        };

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

        const updateSystemAccess = () => {
            if (!allowSystemLoginToggle) {
                return;
            }
            const allowLogin = !!allowSystemLoginToggle.checked;
            if (passwordInput) {
                passwordInput.disabled = !allowLogin;
            }
            if (passwordConfirmationInput) {
                passwordConfirmationInput.disabled = !allowLogin;
            }
        };

        allowSystemLoginToggle?.addEventListener('change', updateSystemAccess);
        updateSystemAccess();
        updateStatutoryFields();

        employmentTypeInputs.forEach((input) => {
            input.addEventListener('change', updateStatutoryFields);
        });

        includeStatutoryYes?.addEventListener('change', updateStatutoryFields);
        includeStatutoryNo?.addEventListener('change', updateStatutoryFields);

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
            if (monthlySalaryInput && monthlySalaryInput.value !== '') {
                const num = Number(monthlySalaryInput.value);
                if (!Number.isNaN(num)) {
                    return `PHP ${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }
                return monthlySalaryInput.value;
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
            if (confirmEmploymentType) {
                confirmEmploymentType.textContent = formatEmploymentType();
            }
            if (confirmRate) {
                confirmRate.textContent = formatRate();
            }
            if (confirmExpectedHours) {
                confirmExpectedHours.textContent = formatExpectedHours(expectedHoursInput?.value);
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
