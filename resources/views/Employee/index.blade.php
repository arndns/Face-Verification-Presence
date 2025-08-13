@extends('layout.employee')
@section('title', 'Dashboard')
@section('content')
    <!-- Bagian Profil -->
    <div class="profile-header text-white position-relative z-1">
        <div class="container p-4">
            <div class="d-flex align-items-center">
                <img id="user-profile-picture" src="https://placehold.co/80x80/FFFFFF/0D6EFD?text=B" alt="Avatar Pengguna"
                    class="rounded-circle border border-4 border-white-50">
                <div class="ms-3">
                    <h2 class="fw-bold mb-0">{{$user->nama}}</h2>
                    <p class="mb-0 fs-5 opacity-75">{{$user->jabatan}}</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Konten dengan Lengkungan -->
    <div class="content-card bg-white shadow-lg flex-grow-1 mt-4">
        <div class="container p-4">
            <!-- Fitur Absen -->
            <div class="row g-3">
                <div class="col-6">
                    <div class="card gradasigreen rounded-4 shadow">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-camera fs-1 me-3"></i>
                                <div>
                                    <h4 class="fw-bold mb-0">Masuk</h4>
                                    <span class="small">07:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card gradasired rounded-4 shadow">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-camera fs-1 me-3"></i>
                                <div>
                                    <h4 class="fw-bold mb-0">Pulang</h4>
                                    <span class="small">15:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <hr class="my-4">
            <div>
                <h5 class="fw-bold mb-3">Ringkasan Kehadiran Bulan Ini</h5>
                <div id="chartdiv"></div>
            </div>
        </div>
    </div>
@endsection
