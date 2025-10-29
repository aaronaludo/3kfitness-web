@extends('layouts.admin')
@section('title', 'Feedbacks')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Feedbacks</h2></div>
                {{-- <div class="d-flex align-items-center"><a class="btn btn-danger" href="#"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add</a></div> --}}
            </div>
            <div class="col-lg-12 mb-20">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="input-group mb-3 mb-lg-0">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" class="form-control" placeholder="Search by Name" />
                            </div>
                        </div>
                        <div class="col-lg-2"><button class="btn btn-danger w-100">Search</button></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Is Read</th>
                                        <th>Created Date</th>
                                        <th>Updated Date</th>
                                    </thead>
                                    <tbody>
                                        @foreach($feedbacks as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->title }}</td>
                                                <td>{{ $item->description }}</td>
                                                <td>{{ $item->isadminread ? 'Yes' : 'No'}}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="#" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
