@extends('layouts.admin')
@section('title', 'Banners')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Banners</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.banners.update') }}" method="POST" enctype="multipart/form-data" id="main-form">
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
                                <div class="mb-3 row">
                                    <label for="background_image" class="col-sm-12 col-lg-2 col-form-label">Background Image:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="d-flex align-items-start flex-column">
                                            @if (!empty($data->background_image))
                                                <img src="{{ asset($data->background_image) }}" alt="Banner background" class="img-thumbnail mb-2" style="width: 240px; max-width: 100%;" />
                                            @endif
                                            <input type="file" class="form-control" id="background_image" name="background_image" accept="image/*" />
                                            <small class="text-muted mt-1">Recommended size: 1200x600 or larger.</small>
                                        </div>
                                    </div>
                                </div>
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
                                <input type="hidden" name="button_text" value="{{ old('button_text', $data->button_text ?? '') }}"/>
                                <input type="hidden" name="pricing_text" value="{{ old('pricing_text', $data->pricing_text ?? '') }}"/>
                                <input type="hidden" name="tag_icon" value="{{ old('tag_icon', $data->tag_icon ?? '') }}"/>
                                <input type="hidden" name="tag_text" value="{{ old('tag_text', $data->tag_text ?? '') }}"/>
                                <input type="hidden" name="schedule_button_icon" value="{{ old('schedule_button_icon', $data->schedule_button_icon ?? '') }}"/>
                                <input type="hidden" name="schedule_button_text" value="{{ old('schedule_button_text', $data->schedule_button_text ?? '') }}"/>
                                <input type="hidden" name="footnote_prefix" value="{{ old('footnote_prefix', $data->footnote_prefix ?? '') }}"/>
                                <input type="hidden" name="footnote_price" value="{{ old('footnote_price', $data->footnote_price ?? '') }}"/>
                                <input type="hidden" name="footnote_suffix" value="{{ old('footnote_suffix', $data->footnote_suffix ?? '') }}"/>
                                <input type="hidden" name="stat_one_icon" value="{{ old('stat_one_icon', $data->stat_one_icon ?? '') }}"/>
                                <input type="hidden" name="stat_one_value" value="{{ old('stat_one_value', $data->stat_one_value ?? '') }}"/>
                                <input type="hidden" name="stat_one_label" value="{{ old('stat_one_label', $data->stat_one_label ?? '') }}"/>
                                <input type="hidden" name="stat_two_icon" value="{{ old('stat_two_icon', $data->stat_two_icon ?? '') }}"/>
                                <input type="hidden" name="stat_two_value" value="{{ old('stat_two_value', $data->stat_two_value ?? '') }}"/>
                                <input type="hidden" name="stat_two_label" value="{{ old('stat_two_label', $data->stat_two_label ?? '') }}"/>
                                <input type="hidden" name="stat_three_icon" value="{{ old('stat_three_icon', $data->stat_three_icon ?? '') }}"/>
                                <input type="hidden" name="stat_three_value" value="{{ old('stat_three_value', $data->stat_three_value ?? '') }}"/>
                                <input type="hidden" name="stat_three_label" value="{{ old('stat_three_label', $data->stat_three_label ?? '') }}"/>
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
