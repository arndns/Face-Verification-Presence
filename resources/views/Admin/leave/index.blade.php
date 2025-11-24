@extends('layout.admin')
@section('title', 'Pengajuan Cuti')
@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="fas fa-calendar-check text-primary"></i> Pengajuan Cuti Pegawai</h2>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filter Section --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.leave.index') }}" class="row g-3">
                    <div class="col-md-5">
                        <label for="search" class="form-label">Cari Pegawai</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Nama atau NIK" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
                @if(request()->hasAny(['search', 'status']))
                    <div class="mt-3">
                        <a href="{{ route('admin.leave.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Reset Filter
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Data Table --}}
        <div class="card shadow-sm">
            <div class="card-body">
                @if($leaves->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Pegawai</th>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th>Durasi</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaves as $index => $leave)
                                    <tr>
                                        <td>{{ $leaves->firstItem() + $index }}</td>
                                        <td>
                                            <strong>{{ $leave->employee->nama }}</strong><br>
                                            <small class="text-muted">NIK: {{ $leave->employee->nik }}</small>
                                        </td>
                                        <td>
                                            @if($leave->leave_type == 'sakit')
                                                <span class="badge bg-danger">Sakit</span>
                                            @elseif($leave->leave_type == 'izin')
                                                <span class="badge bg-warning text-dark">Izin</span>
                                            @else
                                                <span class="badge bg-info">Cuti Tahunan</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>
                                                {{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }}<br>
                                                s/d {{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($leave->start_date)->diffInDays($leave->end_date) + 1 }} hari
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($leave->reason, 50) }}</small>
                                        </td>
                                        <td>
                                            @if($leave->status == 'pending')
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @elseif($leave->status == 'approved')
                                                <span class="badge bg-success">Disetujui</span>
                                            @else
                                                <span class="badge bg-danger">Ditolak</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($leave->status == 'pending')
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#approveModal{{ $leave->id }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rejectModal{{ $leave->id }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#detailModal{{ $leave->id }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Approve Modal --}}
                                    <div class="modal fade" id="approveModal{{ $leave->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title">Setujui Pengajuan Cuti</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.leave.approve', $leave->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Pegawai:</strong> {{ $leave->employee->nama }}</p>
                                                        <p><strong>Jenis:</strong> {{ ucfirst($leave->leave_type) }}</p>
                                                        <p><strong>Periode:</strong> 
                                                            {{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }} - 
                                                            {{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}
                                                        </p>
                                                        <div class="mb-3">
                                                            <label for="admin_note" class="form-label">Catatan Admin (Opsional)</label>
                                                            <textarea class="form-control" name="admin_note" rows="3" 
                                                                      placeholder="Berikan catatan jika diperlukan..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-success">Setujui</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Reject Modal --}}
                                    <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Tolak Pengajuan Cuti</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.leave.reject', $leave->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <p><strong>Pegawai:</strong> {{ $leave->employee->nama }}</p>
                                                        <p><strong>Jenis:</strong> {{ ucfirst($leave->leave_type) }}</p>
                                                        <div class="mb-3">
                                                            <label for="admin_note" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" name="admin_note" rows="3" 
                                                                      placeholder="Jelaskan alasan penolakan..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Tolak</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Detail Modal --}}
                                    <div class="modal fade" id="detailModal{{ $leave->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-info text-white">
                                                    <h5 class="modal-title">Detail Pengajuan Cuti</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Pegawai:</strong> {{ $leave->employee->nama }}</p>
                                                    <p><strong>NIK:</strong> {{ $leave->employee->nik }}</p>
                                                    <p><strong>Jenis:</strong> {{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}</p>
                                                    <p><strong>Periode:</strong> 
                                                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d/m/Y') }} - 
                                                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d/m/Y') }}
                                                        ({{ \Carbon\Carbon::parse($leave->start_date)->diffInDays($leave->end_date) + 1 }} hari)
                                                    </p>
                                                    <p><strong>Alasan:</strong> {{ $leave->reason }}</p>
                                                    <p><strong>Status:</strong> 
                                                        @if($leave->status == 'approved')
                                                            <span class="badge bg-success">Disetujui</span>
                                                        @else
                                                            <span class="badge bg-danger">Ditolak</span>
                                                        @endif
                                                    </p>
                                                    @if($leave->admin_note)
                                                        <div class="alert alert-light">
                                                            <strong>Catatan Admin:</strong><br>
                                                            {{ $leave->admin_note }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $leaves->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Tidak ada pengajuan cuti yang ditemukan.</p>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.leave.index') }}" class="btn btn-sm btn-primary">
                                Lihat Semua Data
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
