@extends('layout.admin')
@section('title', 'Dashboard')
@section('content')
    <!-- Kartu Statistik -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Pegawai</h5>
                        <p class="card-text fs-4 fw-bold">20</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Tepat Waktu</h5>
                        <p class="card-text fs-4 fw-bold">1,250</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Terlambat</h5>
                        <p class="card-text fs-4 fw-bold">8,540</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Tidak Hadir</h5>
                        <p class="card-text fs-4 fw-bold">25</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-user-xmark"></i></div>
                </div>
            </div>
        </div>
    </div>
@endsection
