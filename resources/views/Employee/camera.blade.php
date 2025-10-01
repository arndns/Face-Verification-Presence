@extends('layout.employee')
@section('title', 'Camera')
@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">

            <a href="javascript:;" class="headerButton goBack text-light">
                <!-- Font Awesome back icon -->
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">
            Attandance Employee
        </div>
        <div class="right" style="width: 24px;">

        </div>
    </div>
@endsection
@section('content')
    <div class="p-4 ">
        <div class="w-100 mb-4">
            <input type="hidden" id="location">
            <div class="camera-capture text-muted">
                <span>Memuat Kamera...</span>
            </div>
        </div>
        <div class="w-100 mb-4">
            <button id="takeattandance"
                class="w-100 btn btn-primary btn-lg fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-3">
                <i class="fa-solid fa-camera fa-lg"></i>
                <span id="button-text">Presensi Masuk</span>
            </button>
        </div>
        <div class="w-100 mb-4">
            <button id="takeattandance"
                class="w-100 btn btn-danger btn-lg fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-3">
                <i class="fa-solid fa-camera fa-lg"></i>
                <span id="button-text">Presensi Pulang</span>
            </button>
        </div>
    </div>
@endsection
