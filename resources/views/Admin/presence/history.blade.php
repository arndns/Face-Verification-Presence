@extends('layout.admin')
@section('title', 'Riwayat Presensi')
@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-history text-primary"></i> Riwayat Presensi Pegawai</h2>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.presence.history') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Pegawai</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Nama atau NIK" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
                @if(request()->hasAny(['search', 'date_from', 'date_to']))
                    <div class="mt-3">
                        <a href="{{ route('admin.presence.history') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Reset Filter
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Data Table --}}
        <div class="card shadow-sm">
            <div class="card-body">
                @if($presences->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>NIK</th>
                                    <th>Nama Pegawai</th>
                                    <th>Jabatan</th>
                                    <th>Waktu Masuk</th>
                                    <th>Waktu Pulang</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($presences as $index => $presence)
                                    <tr>
                                        <td>{{ $presences->firstItem() + $index }}</td>
                                        <td><strong>{{ $presence->nik }}</strong></td>
                                        <td>{{ $presence->nama }}</td>
                                        <td><span class="badge bg-info">{{ $presence->jabatan }}</span></td>
                                        <td>
                                            @if($presence->waktu_masuk)
                                                <i class="fas fa-clock text-success"></i>
                                                {{ \Carbon\Carbon::parse($presence->waktu_masuk)->format('d/m/Y H:i') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($presence->waktu_pulang)
                                                <i class="fas fa-clock text-danger"></i>
                                                {{ \Carbon\Carbon::parse($presence->waktu_pulang)->format('d/m/Y H:i') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($presence->waktu_masuk && $presence->waktu_pulang)
                                                <span class="badge bg-success">Selesai</span>
                                            @elseif($presence->waktu_masuk)
                                                <span class="badge bg-warning">Sedang Berlangsung</span>
                                            @else
                                                <span class="badge bg-secondary">Belum Masuk</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $presences->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tidak ada data presensi yang ditemukan.</p>
                        @if(request()->hasAny(['search', 'date_from', 'date_to']))
                            <a href="{{ route('admin.presence.history') }}" class="btn btn-sm btn-primary">
                                Lihat Semua Data
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
