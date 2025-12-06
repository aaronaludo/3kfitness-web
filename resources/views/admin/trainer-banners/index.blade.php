@extends('layouts.admin')
@section('title', 'Trainer Banners')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Trainer Banners</h2></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.trainer-banners.update') }}" method="POST" enctype="multipart/form-data" id="main-form">
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
                                <input type="hidden" name="id" value="{{ $data->id ?? 0 }}" />
                                {{-- <div class="mb-3 row">
                                    <label for="background_image" class="col-sm-12 col-lg-2 col-form-label">Background Image: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center flex-column justify-content-center">
                                        <img src="{{ asset($data->background_image ?? '' ) }}" alt="{{ $data->title ?? '' }}" style="width: 200px;"/><br/>
                                        <input type="file" class="form-control" id="background_image" name="background_image"/>
                                    </div>
                                </div>    --}}
                                <div class="mb-3 row">
                                    <label for="title" class="col-sm-12 col-lg-2 col-form-label">Hero Title: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $data->title ?? '') }}" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="description" class="col-sm-12 col-lg-2 col-form-label">Hero Subtitle: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $data->description ?? '') }}</textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="button_text" class="col-sm-12 col-lg-2 col-form-label">Primary CTA Text: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="button_text" name="button_text" value="{{ old('button_text', $data->button_text ?? '') }}" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="pricing_text" class="col-sm-12 col-lg-2 col-form-label">Pricing Line (legacy): </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="pricing_text" name="pricing_text" value="{{ old('pricing_text', $data->pricing_text ?? '') }}" required/>
                                    </div>
                                </div>

                                <hr class="my-4"/>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-3">Tag &amp; Schedule Pill</h5>
                                </div>
                                <div class="mb-3 row">
                                    <label for="tag_icon" class="col-sm-12 col-lg-2 col-form-label">Tag Icon:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="tag_icon" name="tag_icon" placeholder="e.g. emoji-events" value="{{ old('tag_icon', $data->tag_icon ?? '') }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="tag_text" class="col-sm-12 col-lg-2 col-form-label">Tag Text:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="tag_text" name="tag_text" placeholder="Coach momentum" value="{{ old('tag_text', $data->tag_text ?? '') }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="schedule_button_icon" class="col-sm-12 col-lg-2 col-form-label">Schedule Icon:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="schedule_button_icon" name="schedule_button_icon" placeholder="calendar-today" value="{{ old('schedule_button_icon', $data->schedule_button_icon ?? '') }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="schedule_button_text" class="col-sm-12 col-lg-2 col-form-label">Schedule Text:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="schedule_button_text" name="schedule_button_text" placeholder="View schedule" value="{{ old('schedule_button_text', $data->schedule_button_text ?? '') }}"/>
                                    </div>
                                </div>

                                <hr class="my-4"/>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-3">Footnote</h5>
                                </div>
                                <div class="mb-3 row">
                                    <label for="footnote_prefix" class="col-sm-12 col-lg-2 col-form-label">Prefix:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="footnote_prefix" name="footnote_prefix" placeholder="This week's focus" value="{{ old('footnote_prefix', $data->footnote_prefix ?? '') }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="footnote_price" class="col-sm-12 col-lg-2 col-form-label">Price:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="footnote_price" name="footnote_price" placeholder="90%" value="{{ old('footnote_price', $data->footnote_price ?? '') }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="footnote_suffix" class="col-sm-12 col-lg-2 col-form-label">Suffix:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="footnote_suffix" name="footnote_suffix" placeholder="attendance goal" value="{{ old('footnote_suffix', $data->footnote_suffix ?? '') }}"/>
                                    </div>
                                </div>

                                <hr class="my-4"/>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-3">Stats Cards</h5>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Stat 1:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="row g-3">
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_one_icon" placeholder="Icon e.g. groups" value="{{ old('stat_one_icon', $data->stat_one_icon ?? '') }}"/>
                                            </div>
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_one_value" placeholder="Value e.g. 3 check-ins" value="{{ old('stat_one_value', $data->stat_one_value ?? '') }}"/>
                                            </div>
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_one_label" placeholder="Label e.g. Celebrate member wins" value="{{ old('stat_one_label', $data->stat_one_label ?? '') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Stat 2:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="row g-3">
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_two_icon" placeholder="Icon e.g. schedule" value="{{ old('stat_two_icon', $data->stat_two_icon ?? '') }}"/>
                                            </div>
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_two_value" placeholder="Value e.g. Plan ahead" value="{{ old('stat_two_value', $data->stat_two_value ?? '') }}"/>
                                            </div>
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_two_label" placeholder="Label e.g. Outline tomorrow's sessions" value="{{ old('stat_two_label', $data->stat_two_label ?? '') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4 row">
                                    <label class="col-sm-12 col-lg-2 col-form-label">Stat 3:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="row g-3">
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_three_icon" placeholder="Icon e.g. emoji-events" value="{{ old('stat_three_icon', $data->stat_three_icon ?? '') }}"/>
                                            </div>
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_three_value" placeholder="Value e.g. Lead boldly" value="{{ old('stat_three_value', $data->stat_three_value ?? '') }}"/>
                                            </div>
                                            <div class="col-lg-4 col-sm-12">
                                                <input type="text" class="form-control" name="stat_three_label" placeholder="Label e.g. Bring your best energy" value="{{ old('stat_three_label', $data->stat_three_label ?? '') }}"/>
                                            </div>
                                        </div>
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

    <script>
        document.getElementById('main-form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('submitButton');
            const loader = document.getElementById('loader');

            // Disable the button and show loader
            submitButton.disabled = true;
            loader.classList.remove('d-none');
        });
    </script>
@endsection
