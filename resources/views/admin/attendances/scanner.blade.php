@extends('layouts.admin')
@section('styles')
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('title', 'Qr Scanner')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Qr Scanner</h2></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="container">
                                <div id="scanner-container">
                                    <video id="scanner" playsinline></video>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scannerModalLabel">Scanned Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalContent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const scannerContainer = document.getElementById('scanner-container');
    const scanner = new Instascan.Scanner({ video: document.getElementById('scanner') });

    scanner.addListener('scan', function (content) {
        // console.log(content);
        // alert(content);
        sendScannedData(content);
    });

    Instascan.Camera.getCameras().then(function (cameras) {
        if (cameras.length > 0) {
            scanner.start(cameras[0]);
        } else {
            console.error('No cameras found.');
        }
    }).catch(function (error) {
        console.error(error);
    });

    function sendScannedData(content) {
        const csrfToken = document.querySelector("meta[name='csrf-token']").getAttribute('content');

        fetch("{{ route('admin.staff-account-management.attendances.scanner2.fetch') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ result: content })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalContent').textContent = data.data;
            const scannerModal = new bootstrap.Modal(document.getElementById('scannerModal'));
            scannerModal.show();   
        })
        .catch(error => {
            console.error(error);
        });
    }
</script>
@endsection