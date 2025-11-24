@extends('layout.employee')
@section('title', 'Profil Saya')

@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">
            <a href="{{ route('employee.index') }}" class="headerButton goBack text-light">
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">
            Profil Saya
        </div>
        <div class="right" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="container py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <div class="avatar-section mb-3">
                    <img src="{{ $employee?->foto ? route('storage.file', $employee->foto) : asset('assets/image/profil-picture.png') }}" 
                         alt="Avatar" class="imaged w100 rounded-circle border border-3 border-white shadow-sm"
                         style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <h3 class="mb-1">{{ $employee->nama ?? $user->username }}</h3>
                <p class="text-muted mb-0">{{ $employee->jabatan ?? 'Pegawai' }}</p>
                <div class="mt-2">
                    <span class="badge bg-primary">{{ $employee->nik ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-user-circle me-2"></i>Informasi Pribadi</h5>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item">
                    <small class="text-muted d-block">Nama Lengkap</small>
                    <span class="fw-medium">{{ $employee->nama ?? '-' }}</span>
                </div>
                <div class="list-group-item">
                    <small class="text-muted d-block">NIK</small>
                    <span class="fw-medium">{{ $employee->nik ?? '-' }}</span>
                </div>
                <div class="list-group-item">
                    <small class="text-muted d-block">Jabatan</small>
                    <span class="fw-medium">{{ $employee->jabatan ?? '-' }}</span>
                </div>
                <div class="list-group-item">
                    <small class="text-muted d-block">No. HP</small>
                    <span class="fw-medium">{{ $employee->no_hp ?? '-' }}</span>
                </div>
                <div class="list-group-item">
                    <small class="text-muted d-block">Alamat</small>
                    <span class="fw-medium">{{ $employee->alamat ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Ubah Password</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('employee.update.password') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small text-muted">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Konfirmasi Password Baru</label>
                        <input type="password" name="new_password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i> Simpan Password
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-2">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100 btn-lg">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted small">
            Versi Aplikasi 1.0.0
        </div>
    </div>
@endsection
