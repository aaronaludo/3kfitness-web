@extends('layouts.admin')
@section('title', 'View Payroll')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert alert-danger">
                    <p class="color-kabarkadogs">Member Name: <span class="fw-bold">{{ $data->user->first_name }} {{ $data->user->last_name }}</span></p>
                    <p class="color-kabarkadogs">Clock In Date: <span class="fw-bold">{{ $data->clockin_at }}</span></p>
                    <p class="color-kabarkadogs">Clock Out Date: <span class="fw-bold">{{ $data->clockout_at }}</span></p>
                    <p class="color-kabarkadogs">Total Hours: 
                        <span class="fw-bold">
                            {{ $data->clockout_at 
                                ? \Carbon\Carbon::parse($data->clockin_at)->diffInHours(\Carbon\Carbon::parse($data->clockout_at)) 
                                : 'Wait for clockout' }}
                        </span>
                    </p>
                    <p class="color-kabarkadogs">Created Date: <span class="fw-bold">{{ $data->created_at }}</span></p>
                </div>
            </div>                    
        </div>
    </div>
@endsection