@extends('layout.admin')
@section('title', 'Dashboard')
@section('content')
    <!-- Kartu Statistik -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-total">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Total Pegawai</h5>
                        <p class="card-text fs-4 fw-bold">{{ $totalEmployees }}</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-present">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Hadir Hari Ini</h5>
                        <p class="card-text fs-4 fw-bold">{{ $presenceToday }}</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-permit">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Izin / Sakit</h5>
                        <p class="card-text fs-4 fw-bold">{{ $permitsToday }}</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-absent">
                <div class="card-body">
                    <div>
                        <h5 class="card-title">Belum Hadir</h5>
                        <p class="card-text fs-4 fw-bold">{{ max(0, $totalEmployees - $presenceToday - $permitsToday) }}</p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                </div>
            </div>
        </div>
    </div>
@endsection
