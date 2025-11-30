@extends('layouts.admin')
@section('title', 'Create Class')

@section('content')
    <style>
        .schedule-card {
            background: radial-gradient(circle at 10% 20%, rgba(255, 230, 235, 0.9), #fff),
                        linear-gradient(145deg, #fff, #f7f7fb);
        }
        .day-chip {
            border: 1px solid #dcdfe6;
            background: #fff;
            color: #2f2f38;
            padding: 0.55rem 0.9rem;
            border-radius: 999px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .day-chip.active {
            background: linear-gradient(120deg, #dc3545, #ff7f73);
            color: #fff;
            border-color: #dc3545;
            box-shadow: 0 8px 18px rgba(220, 53, 69, 0.18);
        }
        .selected-days-panel {
            background: #fff;
            border: 1px solid #ececf3;
            border-radius: 1rem;
            padding: 1rem;
            min-height: 280px;
        }
        .day-card {
            border: 1px solid #e9e9f2;
            border-radius: 0.9rem;
            background: #fafbff;
        }
        .calendar-shell {
            border: 1px solid #ececf3;
            border-radius: 1rem;
            background: #fff;
            padding: 1rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.45rem;
        }
        .calendar-cell {
            position: relative;
            border-radius: 0.65rem;
            border: 1px dashed #e6e8f0;
            min-height: 68px;
            padding: 0.35rem 0.45rem;
            background: #fafbff;
            transition: all 0.18s ease;
            color: #2f2f38;
        }
        .calendar-cell.in-range {
            border-color: #d4d7e5;
            background: #f5f7ff;
        }
        .calendar-cell.has-class {
            border: 1px solid #dc3545;
            background: linear-gradient(160deg, rgba(220, 53, 69, 0.12), rgba(255, 200, 200, 0.35));
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.15);
        }
        .calendar-cell .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #dc3545;
            display: inline-block;
            margin-right: 6px;
        }
        .calendar-cell .date-number {
            font-weight: 700;
        }
        .calendar-cell.is-today {
            border: 1px solid #6c757d;
        }
        .calendar-weekday {
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 0.78rem;
            color: #8a8ea2;
            text-align: center;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff4f4;
            color: #d9213c;
            border: 1px solid rgba(220, 53, 69, 0.35);
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
        }
    </style>
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
                                <div class="mb-4 row">
                                    <div class="col-12">
                                        <div class="schedule-card p-4 rounded-4 border shadow-sm">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                                <div>
                                                    <div class="text-uppercase text-muted small fw-semibold">Class cadence</div>
                                                    <h5 class="mb-0">Recurring days with end date</h5>
                                                </div>
                                                <div class="pill">
                                                    <i class="bi bi-calendar-week"></i> Calendar style scheduling
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-lg-6 col-sm-12">
                                                    <label for="series_start_date" class="form-label">Series Start Date <span class="required">*</span></label>
                                                    <input type="date" class="form-control" id="series_start_date" required>
                                                    <small class="text-muted">First day the class can run.</small>
                                                </div>
                                                <div class="col-lg-6 col-sm-12">
                                                    <label for="series_end_date" class="form-label">Series End Date <span class="required">*</span></label>
                                                    <input type="date" class="form-control" id="series_end_date" required>
                                                    <small class="text-muted">Classes stop repeating after this date.</small>
                                                </div>
                                                <div class="col-lg-6 col-sm-12">
                                                    <label for="class_start_time" class="form-label">Class Start Time <span class="required">*</span></label>
                                                    <input type="time" class="form-control" id="class_start_time" name="class_start_time" required>
                                                </div>
                                                <div class="col-lg-6 col-sm-12">
                                                    <label for="class_end_time" class="form-label">Class End Time <span class="required">*</span></label>
                                                    <input type="time" class="form-control" id="class_end_time" name="class_end_time" required>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label d-flex align-items-center gap-2">Days of the week <span class="required">*</span></label>
                                                <div class="d-flex flex-wrap gap-2" id="dayButtons">
                                                    <button type="button" class="day-chip" data-day="sun">Sun</button>
                                                    <button type="button" class="day-chip" data-day="mon">Mon</button>
                                                    <button type="button" class="day-chip" data-day="tue">Tue</button>
                                                    <button type="button" class="day-chip" data-day="wed">Wed</button>
                                                    <button type="button" class="day-chip" data-day="thu">Thu</button>
                                                    <button type="button" class="day-chip" data-day="fri">Fri</button>
                                                    <button type="button" class="day-chip" data-day="sat">Sat</button>
                                                </div>
                                                <small class="text-muted d-block mt-1">Select multiple days (ex: Monday, Tuesday, Saturday). Cancel or reschedule any day without leaving the page.</small>
                                            </div>
                                            <div class="row g-3 mt-3">
                                                <div class="col-lg-5">
                                                    <div class="selected-days-panel h-100">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <span class="fw-semibold">Selected days</span>
                                                            <small class="text-muted">Cancel or reschedule</small>
                                                        </div>
                                                        <div id="selectedDaysList" class="row g-2"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="calendar-shell h-100">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <div>
                                                                <div class="text-uppercase text-muted small fw-semibold">Calendar preview</div>
                                                                <div class="fw-semibold" id="calendarMonthLabel">—</div>
                                                                <small class="text-muted" id="calendarRangeLabel"></small>
                                                            </div>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="prevMonthBtn"><i class="bi bi-chevron-left"></i></button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="nextMonthBtn"><i class="bi bi-chevron-right"></i></button>
                                                            </div>
                                                        </div>
                                                        <div class="calendar-grid text-center mb-2">
                                                            <div class="calendar-weekday">Sun</div>
                                                            <div class="calendar-weekday">Mon</div>
                                                            <div class="calendar-weekday">Tue</div>
                                                            <div class="calendar-weekday">Wed</div>
                                                            <div class="calendar-weekday">Thu</div>
                                                            <div class="calendar-weekday">Fri</div>
                                                            <div class="calendar-weekday">Sat</div>
                                                        </div>
                                                        <div class="calendar-grid" id="calendarGrid"></div>
                                                        <div class="d-flex gap-3 align-items-center mt-3">
                                                            <div><span class="dot"></span> <small class="text-muted">Class occurs</small></div>
                                                            <div><span class="badge bg-light text-dark border">Range</span> <small class="text-muted">Within start/end</small></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" id="class_start_date" name="class_start_date" />
                                            <input type="hidden" id="class_end_date" name="class_end_date" />
                                            <input type="hidden" id="recurring_days" name="recurring_days" />
                                            <input type="hidden" id="series_start_date_hidden" name="series_start_date" />
                                            <input type="hidden" id="series_end_date_hidden" name="series_end_date" />
                                        </div>
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
        const seriesStartInput = document.getElementById('series_start_date');
        const seriesEndInput = document.getElementById('series_end_date');
        const startTimeInput = document.getElementById('class_start_time');
        const endTimeInput = document.getElementById('class_end_time');
        const hiddenStartInput = document.getElementById('class_start_date');
        const hiddenEndInput = document.getElementById('class_end_date');
        const recurringInput = document.getElementById('recurring_days');
        const hiddenSeriesStartInput = document.getElementById('series_start_date_hidden');
        const hiddenSeriesEndInput = document.getElementById('series_end_date_hidden');
        const dayButtonsContainer = document.getElementById('dayButtons');
        const selectedDaysList = document.getElementById('selectedDaysList');
        const calendarGrid = document.getElementById('calendarGrid');
        const calendarMonthLabel = document.getElementById('calendarMonthLabel');
        const calendarRangeLabel = document.getElementById('calendarRangeLabel');
        const prevMonthBtn = document.getElementById('prevMonthBtn');
        const nextMonthBtn = document.getElementById('nextMonthBtn');
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
        const daysMeta = [
            { key: 'sun', label: 'Sun', long: 'Sunday', index: 0 },
            { key: 'mon', label: 'Mon', long: 'Monday', index: 1 },
            { key: 'tue', label: 'Tue', long: 'Tuesday', index: 2 },
            { key: 'wed', label: 'Wed', long: 'Wednesday', index: 3 },
            { key: 'thu', label: 'Thu', long: 'Thursday', index: 4 },
            { key: 'fri', label: 'Fri', long: 'Friday', index: 5 },
            { key: 'sat', label: 'Sat', long: 'Saturday', index: 6 },
        ];
        const dayIndexMap = daysMeta.reduce((acc, day) => ({ ...acc, [day.key]: day.index }), {});
        const dayLabelMap = daysMeta.reduce((acc, day) => ({ ...acc, [day.key]: day.long }), {});
        let selectedDays = new Set();
        let calendarCursor = new Date();
        let allowSubmit = false;

        const toLocalIsoString = (date) => {
            return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        };

        const dateOnlyValue = (date) => toLocalIsoString(date).slice(0, 10);

        const enforceMinDate = () => {
            const today = new Date();
            const todayVal = dateOnlyValue(today);
            if (seriesStartInput && !seriesStartInput.value) {
                seriesStartInput.value = todayVal;
            }
            if (seriesEndInput && !seriesEndInput.value) {
                seriesEndInput.value = todayVal;
            }
            if (seriesStartInput) {
                seriesStartInput.min = todayVal;
            }
            if (seriesEndInput) {
                seriesEndInput.min = seriesStartInput?.value || todayVal;
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

        const formatDate = (value) => {
            if (!value) return '—';
            const parsed = new Date(value);
            return isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString(undefined, { dateStyle: 'medium' });
        };

        const formatTime = (value) => {
            if (!value) return '—';
            const [hours, minutes] = value.split(':');
            const parsed = new Date();
            parsed.setHours(hours || 0, minutes || 0, 0, 0);
            return parsed.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
        };

        const populateConfirmation = () => {
            confirmName.textContent = nameInput?.value?.trim() || '—';
            confirmTrainer.textContent = trainerSelect?.options[trainerSelect.selectedIndex]?.text || '—';
            confirmSlots.textContent = slotsInput?.value || '—';
            const daysReadable = Array.from(selectedDays)
                .sort((a, b) => dayIndexMap[a] - dayIndexMap[b])
                .map((d) => dayLabelMap[d])
                .join(', ') || '—';
            confirmStart.textContent = `${formatDate(seriesStartInput?.value)} → ${formatDate(seriesEndInput?.value)}`;
            confirmEnd.textContent = `${daysReadable} · ${formatTime(startTimeInput?.value)} - ${formatTime(endTimeInput?.value)}`;
        };

        const renderDayButtons = () => {
            dayButtonsContainer?.querySelectorAll('[data-day]')?.forEach((btn) => {
                const key = btn.dataset.day;
                btn.classList.toggle('active', selectedDays.has(key));
            });
        };

        const buildRescheduleOptions = (current) => {
            return daysMeta.map((day) => {
                const disabled = selectedDays.has(day.key) && day.key !== current ? 'disabled' : '';
                const label = selectedDays.has(day.key) && day.key !== current ? `${day.long} (already added)` : day.long;
                const selected = day.key === current ? 'selected' : '';
                return `<option value="${day.key}" ${disabled} ${selected}>${label}</option>`;
            }).join('');
        };

        const renderSelectedDays = () => {
            if (!selectedDaysList) return;
            selectedDaysList.innerHTML = '';
            if (!selectedDays.size) {
                selectedDaysList.innerHTML = '<div class="col-12 text-muted small">No days selected yet.</div>';
                return;
            }
            Array.from(selectedDays)
                .sort((a, b) => dayIndexMap[a] - dayIndexMap[b])
                .forEach((dayKey) => {
                    const col = document.createElement('div');
                    col.className = 'col-12';
                    col.innerHTML = `
                        <div class="day-card p-3 d-flex flex-column gap-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-semibold">${dayLabelMap[dayKey]}</div>
                                    <small class="text-muted">Repeats weekly</small>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary resched-btn" data-day="${dayKey}">Reschedule</button>
                                    <button type="button" class="btn btn-sm btn-link text-danger cancel-day" data-day="${dayKey}" title="Remove this day">Cancel</button>
                                </div>
                            </div>
                            <div class="resched-row d-none" data-resched="${dayKey}">
                                <label class="form-label small text-muted mb-1">Move ${dayLabelMap[dayKey]} to</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm resched-select" data-day="${dayKey}">
                                        ${buildRescheduleOptions(dayKey)}
                                    </select>
                                    <button type="button" class="btn btn-sm btn-danger apply-resched" data-day="${dayKey}">Move</button>
                                </div>
                            </div>
                        </div>
                    `;
                    selectedDaysList.appendChild(col);
                });
        };

        const toggleDaySelection = (dayKey) => {
            if (selectedDays.has(dayKey)) {
                selectedDays.delete(dayKey);
            } else {
                selectedDays.add(dayKey);
            }
            renderDayButtons();
            renderSelectedDays();
            updateHiddenFields();
            renderCalendar();
        };

        dayButtonsContainer?.addEventListener('click', (event) => {
            const btn = event.target.closest('[data-day]');
            if (!btn) return;
            toggleDaySelection(btn.dataset.day);
        });

        selectedDaysList?.addEventListener('click', (event) => {
            const cancelBtn = event.target.closest('.cancel-day');
            const reschedBtn = event.target.closest('.resched-btn');
            const applyReschedBtn = event.target.closest('.apply-resched');

            if (cancelBtn) {
                const day = cancelBtn.dataset.day;
                selectedDays.delete(day);
                renderDayButtons();
                renderSelectedDays();
                updateHiddenFields();
                renderCalendar();
            }

            if (reschedBtn) {
                const day = reschedBtn.dataset.day;
                const row = selectedDaysList.querySelector(`[data-resched="${day}"]`);
                row?.classList.toggle('d-none');
            }

            if (applyReschedBtn) {
                const day = applyReschedBtn.dataset.day;
                const select = selectedDaysList.querySelector(`select[data-day="${day}"]`);
                const target = select?.value;
                if (target && target !== day) {
                    selectedDays.delete(day);
                    selectedDays.add(target);
                }
                renderDayButtons();
                renderSelectedDays();
                updateHiddenFields();
                renderCalendar();
            }
        });

        const getFirstOccurrenceDate = () => {
            if (!seriesStartInput?.value || !selectedDays.size) return null;
            const startDate = new Date(seriesStartInput.value + 'T00:00:00');
            const maxLookAhead = 14;
            const selectedIndexes = Array.from(selectedDays).map((d) => dayIndexMap[d]);
            const candidate = new Date(startDate);
            for (let i = 0; i < maxLookAhead; i++) {
                if (selectedIndexes.includes(candidate.getDay())) {
                    return candidate;
                }
                candidate.setDate(candidate.getDate() + 1);
            }
            return null;
        };

        const updateHiddenFields = () => {
            if (recurringInput) {
                recurringInput.value = JSON.stringify(Array.from(selectedDays));
            }
            if (hiddenSeriesStartInput) {
                hiddenSeriesStartInput.value = seriesStartInput?.value || '';
            }
            if (hiddenSeriesEndInput) {
                hiddenSeriesEndInput.value = seriesEndInput?.value || '';
            }
            const firstOccurrence = getFirstOccurrenceDate();
            if (firstOccurrence && startTimeInput?.value) {
                const [sh, sm] = startTimeInput.value.split(':');
                const startDateTime = new Date(firstOccurrence);
                startDateTime.setHours(sh || 0, sm || 0, 0, 0);
                hiddenStartInput.value = toLocalIsoString(startDateTime);

                const endDateTime = new Date(firstOccurrence);
                if (endTimeInput?.value) {
                    const [eh, em] = endTimeInput.value.split(':');
                    endDateTime.setHours(eh || 0, em || 0, 0, 0);
                }
                hiddenEndInput.value = toLocalIsoString(endDateTime);
            } else {
                hiddenStartInput.value = '';
                hiddenEndInput.value = '';
            }
        };

        const renderCalendar = () => {
            if (!calendarGrid) return;
            const year = calendarCursor.getFullYear();
            const month = calendarCursor.getMonth();
            const startOfMonth = new Date(year, month, 1);
            const endOfMonth = new Date(year, month + 1, 0);
            const startDayOffset = startOfMonth.getDay();
            const daysInMonth = endOfMonth.getDate();
            const totalCells = Math.ceil((startDayOffset + daysInMonth) / 7) * 7;

            const seriesStart = seriesStartInput?.value ? new Date(seriesStartInput.value + 'T00:00:00') : null;
            const seriesEnd = seriesEndInput?.value ? new Date(seriesEndInput.value + 'T00:00:00') : null;
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            calendarGrid.innerHTML = '';
            calendarMonthLabel.textContent = startOfMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
            calendarRangeLabel.textContent = seriesStart && seriesEnd
                ? `${formatDate(seriesStart)} → ${formatDate(seriesEnd)}`
                : 'Pick a start and end date';

            for (let cell = 0; cell < totalCells; cell++) {
                const dayNumber = cell - startDayOffset + 1;
                const cellEl = document.createElement('div');
                if (dayNumber < 1 || dayNumber > daysInMonth) {
                    cellEl.className = 'calendar-cell';
                    cellEl.innerHTML = '&nbsp;';
                    calendarGrid.appendChild(cellEl);
                    continue;
                }
                const currentDate = new Date(year, month, dayNumber);
                const inRange = seriesStart && seriesEnd && currentDate >= seriesStart && currentDate <= seriesEnd;
                const hasClass = inRange && selectedDays.has(daysMeta[currentDate.getDay()].key);

                cellEl.className = 'calendar-cell';
                if (inRange) cellEl.classList.add('in-range');
                if (hasClass) cellEl.classList.add('has-class');
                if (currentDate.getTime() === today.getTime()) cellEl.classList.add('is-today');

                cellEl.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="date-number">${dayNumber}</span>
                        ${hasClass ? '<span class="dot" title="Class occurs"></span>' : ''}
                    </div>
                    <div class="small text-muted">${daysMeta[currentDate.getDay()].label}</div>
                `;
                calendarGrid.appendChild(cellEl);
            }
        };

        prevMonthBtn?.addEventListener('click', () => {
            calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() - 1, 1);
            renderCalendar();
        });

        nextMonthBtn?.addEventListener('click', () => {
            calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() + 1, 1);
            renderCalendar();
        });

        seriesStartInput?.addEventListener('change', () => {
            if (seriesEndInput && seriesEndInput.value < seriesStartInput.value) {
                seriesEndInput.value = seriesStartInput.value;
            }
            enforceMinDate();
            calendarCursor = new Date(seriesStartInput.value + 'T00:00:00');
            updateHiddenFields();
            renderCalendar();
        });

        seriesEndInput?.addEventListener('change', () => {
            enforceMinDate();
            updateHiddenFields();
            renderCalendar();
        });

        startTimeInput?.addEventListener('change', updateHiddenFields);
        endTimeInput?.addEventListener('change', updateHiddenFields);

        const initializeDefaults = () => {
            const todayKey = daysMeta[new Date().getDay()].key;
            selectedDays.add(todayKey);
            renderDayButtons();
            renderSelectedDays();
            updateHiddenFields();
            renderCalendar();
        };
        initializeDefaults();

        form.addEventListener('submit', function(e) {
            const errors = [];
            const now = new Date();
            now.setHours(0, 0, 0, 0);

            const seriesStart = seriesStartInput?.value ? new Date(seriesStartInput.value + 'T00:00:00') : null;
            const seriesEnd = seriesEndInput?.value ? new Date(seriesEndInput.value + 'T00:00:00') : null;

            if (!selectedDays.size) {
                errors.push('Pick at least one day for the class.');
            }
            if (!seriesStart) {
                errors.push('Series start date is required.');
            } else if (seriesStart < now) {
                errors.push('Series start date cannot be in the past.');
            }
            if (!seriesEnd) {
                errors.push('Series end date is required.');
            }
            if (seriesStart && seriesEnd && seriesEnd < seriesStart) {
                errors.push('Series end date must be on or after the start date.');
            }
            if (!startTimeInput?.value || !endTimeInput?.value) {
                errors.push('Start time and end time are required.');
            } else if (startTimeInput.value >= endTimeInput.value) {
                errors.push('End time must be after the start time.');
            }

            const firstOccurrence = getFirstOccurrenceDate();
            if (!firstOccurrence) {
                errors.push('Make sure the start date lines up with at least one selected day.');
            }

            updateHiddenFields();

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
