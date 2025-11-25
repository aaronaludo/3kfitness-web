@extends('layouts.admin')
@section('title', 'Create Class')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Create Class</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.gym-management.schedules.store') }}" method="POST" enctype="multipart/form-data" id="main-form">
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
                                <div class="mb-3 row">
                                    <label for="image" class="col-sm-12 col-lg-2 col-form-label">Image: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="file" class="form-control" id="image" name="image"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="name" class="col-sm-12 col-lg-2 col-form-label">Name: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="name" name="name" required/>
                                    </div>
                                </div>
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="class_code" class="col-sm-12 col-lg-2 col-form-label">Class Code: <span class="required">*</span></label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="text" class="form-control" id="class_code" name="class_code" required/>-->
                                <!--    </div>-->
                                <!--</div>-->
                                <div class="mb-3 row">
                                    <label for="slots" class="col-sm-12 col-lg-2 col-form-label">Slots: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="number" class="form-control" id="slots" name="slots" min="1" required/>
                                    </div>
                                </div>
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="image" class="col-sm-12 col-lg-2 col-form-label">Image: <span class="required">*</span></label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="file" class="form-control" id="image" name="image"/>-->
                                <!--    </div>-->
                                <!--</div>-->
                                <div class="mb-3 row">
                                    <label for="class_start_date" class="col-sm-12 col-lg-2 col-form-label">
                                        Class Start Date & Time: <span class="required">*</span>
                                    </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="datetime-local" class="form-control" id="class_start_date" name="class_start_date" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="class_end_date" class="col-sm-12 col-lg-2 col-form-label">
                                        Class End Date & Time: <span class="required">*</span>
                                    </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="datetime-local" class="form-control" id="class_end_date" name="class_end_date" required/>
                                    </div>
                                </div>
                                {{-- <div class="mb-3 row">
                                    <label for="isenabled" class="col-sm-12 col-lg-2 col-form-label">Status: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <select class="form-control" id="isenabled" name="isenabled" required>
                                            <option value="1">Enable</option>
                                            <option value="0">Disabled</option>
                                        </select>
                                    </div>
                                </div>       --}}
                                <div class="mb-3 row">
                                    <label for="trainer_id" class="col-sm-12 col-lg-2 col-form-label">Trainer: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <select class="form-control" id="trainer_id" name="trainer_id" required>
                                            <option value="0" {{ old('trainer_id', '0') == '0' ? 'selected' : '' }}>No Trainer for Now</option>
                                            @foreach($trainers as $trainer)
                                                <option value="{{ $trainer->id }}" {{ old('trainer_id') == $trainer->id ? 'selected' : '' }}>
                                                    {{ $trainer->first_name .' '. $trainer->last_name  }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>    
                                <div class="mb-3 row">
                                    <label for="trainer_rate_per_hour" class="col-sm-12 col-lg-2 col-form-label">
                                        Trainer Rate per Hour:
                                    </label>
                                    <div class="col-lg-10 col-sm-12">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control"
                                            id="trainer_rate_per_hour"
                                            name="trainer_rate_per_hour"
                                            value="{{ old('trainer_rate_per_hour') }}"
                                            placeholder="Enter rate in pesos"
                                        />
                                        <small class="text-muted">Required when a trainer is assigned.</small>
                                        @error('trainer_rate_per_hour')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
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
                    <h5 class="modal-title fw-semibold" id="formConfirmModalLabel">Create class?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">We will create a new class with the details below.</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Name</span>
                            <span class="fw-semibold" id="confirmName">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Trainer</span>
                            <span class="fw-semibold" id="confirmTrainer">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Slots</span>
                            <span class="fw-semibold" id="confirmSlots">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Start</span>
                            <span class="fw-semibold" id="confirmStart">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">End</span>
                            <span class="fw-semibold" id="confirmEnd">—</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Review again</button>
                    <button type="button" class="btn btn-danger" id="confirmActionButton">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="confirmActionLoader" role="status" aria-hidden="true"></span>
                        Yes, create it
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('main-form');
        const submitButton = document.getElementById('submitButton');
        const loader = document.getElementById('loader');
        const startInput = document.getElementById('class_start_date');
        const endInput = document.getElementById('class_end_date');
        const trainerSelect = document.getElementById('trainer_id');
        const rateInput = document.getElementById('trainer_rate_per_hour');
        const nameInput = document.getElementById('name');
        const slotsInput = document.getElementById('slots');
        const confirmName = document.getElementById('confirmName');
        const confirmTrainer = document.getElementById('confirmTrainer');
        const confirmSlots = document.getElementById('confirmSlots');
        const confirmStart = document.getElementById('confirmStart');
        const confirmEnd = document.getElementById('confirmEnd');
        const confirmModalEl = document.getElementById('formConfirmModal');
        const confirmModal = confirmModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(confirmModalEl) : null;
        const confirmActionButton = document.getElementById('confirmActionButton');
        const confirmActionLoader = document.getElementById('confirmActionLoader');
        let allowSubmit = false;

        const toLocalIsoString = (date) => {
            return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        };

        const enforceMinDate = () => {
            const now = new Date();
            const nowIso = toLocalIsoString(now);
            if (startInput) {
                startInput.min = nowIso;
            }
            if (endInput) {
                endInput.min = nowIso;
            }
        };

        enforceMinDate();

        const toggleRateInput = () => {
            if (!trainerSelect || !rateInput) {
                return;
            }

            const noTrainer = trainerSelect.value === '0';
            rateInput.disabled = noTrainer;

            if (noTrainer) {
                rateInput.value = '';
            }
        };

        trainerSelect?.addEventListener('change', toggleRateInput);
        toggleRateInput();

        const formatDateTime = (value) => {
            if (!value) return '—';
            const parsed = new Date(value);
            return isNaN(parsed.getTime()) ? value : parsed.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        };

        const populateConfirmation = () => {
            confirmName.textContent = nameInput?.value?.trim() || '—';
            confirmTrainer.textContent = trainerSelect?.options[trainerSelect.selectedIndex]?.text || '—';
            confirmSlots.textContent = slotsInput?.value || '—';
            confirmStart.textContent = formatDateTime(startInput?.value);
            confirmEnd.textContent = formatDateTime(endInput?.value);
        };

        form.addEventListener('submit', function(e) {
            const errors = [];
            const now = new Date();
            const startDate = startInput && startInput.value ? new Date(startInput.value) : null;
            const endDate = endInput && endInput.value ? new Date(endInput.value) : null;

            if (startDate && startDate < now) {
                errors.push('Class start date cannot be in the past.');
            }
            if (endDate && endDate < now) {
                errors.push('Class end date cannot be in the past.');
            }
            if (startDate && endDate && endDate <= startDate) {
                errors.push('Class end date must be after the start date.');
            }

            if (errors.length) {
                e.preventDefault();
                alert(errors.join('\n'));
                submitButton.disabled = false;
                loader.classList.add('d-none');
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
