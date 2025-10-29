@extends('layouts.admin')
@section('title', 'View Schedule')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert alert-danger">
                    <img src="{{ asset($data->image) }}" alt="{{ $data->name }}" class="img-thumbnail me-3 mb-3" style="width: 100px; height: auto;">
                    <p class="color-kabarkadogs">Name: <span class="fw-bold ms-2">{{ $data->name }}</span></p>
                </div>
            </div>                    
        </div>
    </div>
@endsection