@extends('layouts.admin')
@section('title', 'View Member')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert alert-danger">
                    <h4 class="alert-heading color-kabarkadogs mb-3">Full name: <span class="fw-bold ms-2">{{ $gym_member->first_name }} {{ $gym_member->last_name }}</span></h4>
                    <p class="color-kabarkadogs">Address: <span class="fw-bold ms-2">{{ $gym_member->address }}</span></p>
                    <p class="color-kabarkadogs">Phone number: <span class="fw-bold ms-2">{{ $gym_member->phone_number }}</span></p>
                    <p class="color-kabarkadogs">Email: <span class="fw-bold ms-2">{{ $gym_member->email }}</span></p>
                    <p class="color-kabarkadogs">Role: <span class="fw-bold ms-2">{{ $gym_member->role->name }}</span></p>
                </div>
            </div>                    
        </div>
    </div>
@endsection