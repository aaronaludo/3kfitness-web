@extends('layouts.admin')
@section('title', 'Admin View')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert" style="background: #b3ccf5;">
                    <h4 class="alert-heading color-kabarkadogs">Full name: <span class="fw-bold ms-2">{{ $user->first_name }} {{ $user->last_name }}</span></h4>
                    <p class="color-kabarkadogs">Address: <span class="fw-bold ms-2">{{ $user->address }}</span></p>
                    <p class="color-kabarkadogs">Phone number: <span class="fw-bold ms-2">{{ $user->phone_number }}</span></p>
                    <p class="color-kabarkadogs">Email: <span class="fw-bold ms-2">{{ $user->email }}</span></p>
                    <p class="color-kabarkadogs">Role: <span class="fw-bold ms-2">{{ $user->role->name }}</span></p>
                </div>
            </div>                    
        </div>
    </div>
@endsection