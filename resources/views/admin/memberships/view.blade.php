@extends('layouts.admin')
@section('title', 'View Membership')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $durationParts = [];
        if (!empty($data->year)) {
            $durationParts[] = $data->year . ' year' . ($data->year > 1 ? 's' : '');
        }
        if (!empty($data->month)) {
            $durationParts[] = $data->month . ' month' . ($data->month > 1 ? 's' : '');
        }
        if (!empty($data->week)) {
            $durationParts[] = $data->week . ' week' . ($data->week > 1 ? 's' : '');
        }
        $durationText = $durationParts ? implode(' • ', $durationParts) : 'Flexible duration';

        $classLimit = $data->class_limit_per_month !== null
            ? $data->class_limit_per_month . ' classes / month'
            : 'Unlimited classes';
        $priceText = $data->price !== null ? number_format((float) $data->price, 2) : '—';
    @endphp
    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="hero-label mb-1">Membership</div>
            <h2 class="hero-title mb-2">{{ $data->name }}</h2>
            <div class="hero-subtitle mb-3">
                {!! nl2br(e($data->description)) !!}
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="detail-pill">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>{{ $data->currency }} {{ $priceText }}</span>
                </span>
                <span class="detail-pill">
                    <i class="fa-solid fa-bolt"></i>
                    <span>{{ $classLimit }}</span>
                </span>
                <span class="detail-pill">
                    <i class="fa-solid fa-calendar-alt"></i>
                    <span>{{ $durationText }}</span>
                </span>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Plan details</h5>
                <span class="text-muted detail-meta">
                    Updated {{ optional($data->updated_at)->format('M d, Y') ?? '—' }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th scope="row">Name</th>
                            <td>{{ $data->name }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Currency</th>
                            <td>{{ $data->currency }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Price</th>
                            <td>{{ $data->currency }} {{ $priceText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Classes / Month</th>
                            <td>{{ $classLimit }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Duration</th>
                            <td>{{ $durationText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Description</th>
                            <td>{!! nl2br(e($data->description)) !!}</td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>{{ optional($data->created_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Last Updated</th>
                            <td>{{ optional($data->updated_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
