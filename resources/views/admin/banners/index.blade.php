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
                                    <label for="background_image" class="col-sm-12 col-lg-2 col-form-label">Background Image: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center flex-column justify-content-center">
                                        <img src="{{ asset($data->background_image ?? "" ) }}" alt="{{ $data->title ?? "" }}" style="width: 200px;"/><br/>
                                        <input type="file" class="form-control" id="background_image" name="background_image"/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="title" class="col-sm-12 col-lg-2 col-form-label">Title: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="title" name="title" value="{{ $data->title ?? "" }}" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="description" class="col-sm-12 col-lg-2 col-form-label">Description: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $data->description ?? "" }}</textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="button_text" class="col-sm-12 col-lg-2 col-form-label">Button Text: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="button_text" name="button_text" value="{{ $data->button_text ?? "" }}" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="pricing_text" class="col-sm-12 col-lg-2 col-form-label">Pricing Text: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="pricing_text" name="pricing_text" value="{{ $data->pricing_text ?? "" }}" required/>
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
