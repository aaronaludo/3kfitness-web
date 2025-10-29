@extends('layouts.admin')
@section('title', 'Edit Motivational Video')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Edit Motivational Video</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.motivational-videos.update', $data->id) }}" method="POST" enctype="multipart/form-data" id="main-form">
                                @csrf
                                @METHOD('PUT')
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
                                    <label for="title" class="col-sm-12 col-lg-2 col-form-label">Title: <span class="required">*</span></label>
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
                                    <label for="video" class="col-sm-12 col-lg-2 col-form-label">Video: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex flex-column justify-content-center">
                                        <video width="320" height="240" controls class="mb-3">
                                            <source src="{{ asset($data->video ?? '') }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                        <input type="file" class="form-control" id="vide" name="video"/>
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
