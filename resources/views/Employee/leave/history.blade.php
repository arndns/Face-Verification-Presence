@extends('layout.employee')
@section('title', 'Riwayat Cuti')

@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">
            <a href="{{ route('employee.index') }}" class="headerButton goBack text-light">
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">
            Riwayat Cuti
        </div>
        <div class="right" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="container py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="mb-3">
            <a href="{{ route('employee.leave.create') }}" class="btn btn-primary w-100">
                <i class="fas fa-plus"></i> Ajukan Cuti Baru
            </a>
        </div>

        @if($leaves->count() > 0)
            @foreach($leaves as $leave)
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    @if($leave->leave_type == 'sakit')
                                        <i class="fas fa-briefcase-medical text-danger"></i> Sakit
                                    @elseif($leave->leave_type == 'izin')
                                        <i class="fas fa-hand-paper text-warning"></i> Izin
                                    @else
                                        <i class="fas fa-umbrella-beach text-info"></i> Cuti Tahunan
                                    @endif
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i>
                                    {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                    - 
                                    {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                    ({{ \Carbon\Carbon::parse($leave->start_date)->diffInDays($leave->end_date) + 1 }} hari)
                                </small>
                            </div>
                            <div>
                                @if($leave->status == 'pending')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                                @elseif($leave->status == 'approved')
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Disetujui</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Ditolak</span>
                                @endif
                            </div>
                        </div>

                        <p class="mb-2"><strong>Alasan:</strong> {{ $leave->reason }}</p>

                        @if($leave->admin_note)
                            <div class="alert alert-light mb-0 py-2">
                                <small><strong>Catatan Admin:</strong> {{ $leave->admin_note }}</small>
                            </div>
                        @endif

                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-clock"></i> Diajukan: {{ $leave->created_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
            @endforeach

            <div class="mt-3" style="margin-bottom: 100px;">
                {{ $leaves->links() }}
            </div>
        @else
            <div class="card shadow-sm text-center py-5">
                <div class="card-body">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">Belum ada pengajuan cuti</p>
                    <a href="{{ route('employee.leave.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajukan Cuti Pertama
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
