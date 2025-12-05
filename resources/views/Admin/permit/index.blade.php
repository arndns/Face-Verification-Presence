@extends('layout.admin')
@section('title', 'Manajemen Pengajuan Cuti')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Manajemen Pengajuan Cuti
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-body border-bottom py-3">
                        <div class="d-flex">
                            <div class="text-muted">
                                Filter:
                                <div class="mx-2 d-inline-block">
                                    <form action="{{ route('admin.permit.index') }}" method="GET" class="d-flex gap-2">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">Semua Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                        </select>
                                        <input type="text" name="search" class="form-control form-control-sm" 
                                               placeholder="Cari nama/NIK..." value="{{ request('search') }}">
                                        <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Karyawan</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Respon Admin</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permits as $permit)
                                <tr>
                                    <td>{{ $loop->iteration + $permits->firstItem() - 1 }}</td>
                                    <td>
                                        <div>{{ $permit->employee->nama }}</div>
                                        <div class="text-muted small">{{ $permit->employee->nik }}</div>
                                    </td>
                                    <td>
                                        @if($permit->leave_type == 'sakit')
                                            <span class="badge bg-danger text-white">Sakit</span>
                                        @elseif($permit->leave_type == 'izin')
                                            <span class="badge bg-warning text-white">Izin</span>
                                        @else
                                            <span class="badge bg-info text-white">Cuti Tahunan</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($permit->start_date)->translatedFormat('d M Y') }} - 
                                        {{ \Carbon\Carbon::parse($permit->end_date)->translatedFormat('d M Y') }}
                                        <div class="text-muted small">
                                            ({{ \Carbon\Carbon::parse($permit->start_date)->diffInDays($permit->end_date) + 1 }} hari)
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 150px;" 
                                              data-bs-toggle="tooltip" title="{{ $permit->reason }}">
                                            {{ $permit->reason }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 150px;" 
                                              data-bs-toggle="tooltip" title="{{ $permit->admin_note }}">
                                            {{ $permit->admin_note ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($permit->status == 'pending')
                                            <span class="badge bg-warning text-white">Pending</span>
                                        @elseif($permit->status == 'approved')
                                            <span class="badge bg-success text-white">Disetujui</span>
                                        @else
                                            <span class="badge bg-danger text-white">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($permit->status == 'pending')
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                                    data-bs-target="#modal-approve-{{ $permit->id }}">
                                                Setujui
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                    data-bs-target="#modal-reject-{{ $permit->id }}">
                                                Tolak
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" 
                                                    data-bs-target="#modal-detail-{{ $permit->id }}">
                                                Detail
                                            </button>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                    data-bs-target="#modal-edit-{{ $permit->id }}">
                                                Edit
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Modals akan di-render di sini atau di loop terpisah -->
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pengajuan cuti.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex align-items-center">
                        {{ $permits->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loop Modals -->
@foreach($permits as $permit)
    <!-- Modal Approve -->
    <div class="modal modal-blur fade" id="modal-approve-{{ $permit->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Setujui Pengajuan Cuti</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.permit.approve', $permit->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Pegawai:</strong> {{ $permit->employee->nama }}<br>
                            <strong>Jenis:</strong> {{ ucfirst($permit->leave_type) }}<br>
                            <strong>Periode:</strong> {{ \Carbon\Carbon::parse($permit->start_date)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($permit->end_date)->translatedFormat('d M Y') }}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin (Opsional)</label>
                            <textarea name="admin_note" class="form-control" rows="3" placeholder="Berikan catatan jika diperlukan..."></textarea>
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

    <!-- Modal Reject -->
    <div class="modal modal-blur fade" id="modal-reject-{{ $permit->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Tolak Pengajuan Cuti</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.permit.reject', $permit->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong>Pegawai:</strong> {{ $permit->employee->nama }}<br>
                            <strong>Jenis:</strong> {{ ucfirst($permit->leave_type) }}<br>
                            <strong>Periode:</strong> {{ \Carbon\Carbon::parse($permit->start_date)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($permit->end_date)->translatedFormat('d M Y') }}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="admin_note" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal modal-blur fade" id="modal-detail-{{ $permit->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-5">Nama Karyawan:</dt>
                        <dd class="col-7">{{ $permit->employee->nama }}</dd>
                        
                        <dt class="col-5">Jenis Cuti:</dt>
                        <dd class="col-7">{{ ucfirst($permit->leave_type) }}</dd>
                        
                        <dt class="col-5">Tanggal:</dt>
                        <dd class="col-7">
                            {{ \Carbon\Carbon::parse($permit->start_date)->translatedFormat('d M Y') }} - 
                            {{ \Carbon\Carbon::parse($permit->end_date)->translatedFormat('d M Y') }}
                        </dd>
                        
                        <dt class="col-5">Alasan:</dt>
                        <dd class="col-7">{{ $permit->reason }}</dd>
                        
                        <dt class="col-5">Status:</dt>
                        <dd class="col-7">
                            @if($permit->status == 'approved')
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </dd>
                        
                        <dt class="col-5">Catatan Admin:</dt>
                        <dd class="col-7">{{ $permit->admin_note ?? '-' }}</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal modal-blur fade" id="modal-edit-{{ $permit->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Status Pengajuan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.permit.update', $permit->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" {{ $permit->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ $permit->status == 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected" {{ $permit->status == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin</label>
                            <textarea name="admin_note" class="form-control" rows="3">{{ $permit->admin_note }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
