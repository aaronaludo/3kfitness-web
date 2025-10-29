@extends('layouts.admin')
@section('title', 'Ride History View')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert" style="background: #b3ccf5;">
                    <h4 class="alert-heading color-kabarkadogs">Passenger name: <span class="fw-bold ms-2">{{ $history->passenger->first_name }} {{ $history->passenger->last_name }}</span></h4>
                    <h4 class="alert-heading color-kabarkadogs">Driver name: <span class="fw-bold ms-2">{{ $history->driver->first_name }} {{ $history->driver->last_name }}</span></h4>
                    <h4 class="alert-heading color-kabarkadogs">Date: <span class="fw-bold ms-2">{{ \Carbon\Carbon::parse($history->created_at)->format('m/d/Y') }}</span></h4>
                    {{-- <p class="color-kabarkadogs">Address: <span class="fw-bold ms-2">{{ $user->address }}</span></p> --}}
                </div>
            </div>                    
        </div>
    </div>
@endsection