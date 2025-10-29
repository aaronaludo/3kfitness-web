@extends('layouts.admin')
@section('title', 'View Membership')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert alert-danger">
                    <p class="color-kabarkadogs">Name: <span class="fw-bold">{{ $data->name }}</span></p>
                    <p class="color-kabarkadogs">Currency: <span class="fw-bold">{{ $data->currency }}</span></p>
                    <p class="color-kabarkadogs">Price: <span class="fw-bold">{{ $data->price }}</span></p>
                    <p class="color-kabarkadogs">Created Date: <span class="fw-bold">{{ $data->created_at }}</span></p>
                    <p class="color-kabarkadogs">Updated Date: <span class="fw-bold">{{ $data->updated_at }}</span></p>
                </div>
            </div>                    
        </div>
    </div>
@endsection