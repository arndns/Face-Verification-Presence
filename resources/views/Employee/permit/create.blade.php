@extends('layout.employee')
@section('title', 'Ajukan Cuti')

@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">
            <a href="{{ route('employee.index') }}" class="headerButton goBack text-light">
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">
            Ajukan Cuti
        </div>
        <div class="right" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-body">
                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <form action="{{ route('employee.permit.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                        <select class="form-select @error('leave_type') is-invalid @enderror" id="leave_type" name="leave_type" required>
                            <option value="">Pilih Jenis Cuti</option>
                            <option value="sakit" {{ old('leave_type') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="izin" {{ old('leave_type') == 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="cuti_tahunan" {{ old('leave_type') == 'cuti_tahunan' ? 'selected' : '' }}>Cuti Tahunan</option>
                        </select>
                        @error('leave_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @php
                        $minPermitDate = date('Y-m-d', strtotime('+1 day'));
                    @endphp
                    @php
                        $today = date('Y-m-d');
                        $initialMin = old('leave_type') === 'sakit' ? $today : $minPermitDate;
                    @endphp
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" value="{{ old('start_date') }}" 
                               min="{{ $initialMin }}"
                               data-today="{{ $today }}"
                               data-tomorrow="{{ $minPermitDate }}"
                               required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="end_date" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" value="{{ old('end_date') }}" 
                               min="{{ $minPermitDate }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Alasan Cuti <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" 
                                  id="reason" name="reason" rows="4" 
                                  placeholder="Jelaskan alasan pengajuan cuti Anda..." 
                                  maxlength="500" required>{{ old('reason') }}</textarea>
                        <small class="text-muted">Maksimal 500 karakter</small>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('employee.permit.history') }}" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-history"></i> Riwayat Cuti
                        </a>
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Informasi:</strong>
            <ul class="mb-0 mt-2">
                <li>Pengajuan cuti akan diverifikasi oleh admin.</li>
                <li>Anda akan mendapat notifikasi setelah pengajuan diproses.</li>
                <li>Pastikan mengisi data dengan lengkap dan jelas.</li>
            </ul>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // Aturan tanggal:
        // - Sakit: boleh H (hari ini), min = today
        // - Selain sakit: minimal H-1 sebelum mulai (tidak boleh hari ini), min = besok
        (function() {
            const leaveSelect = document.getElementById('leave_type');
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            if (!leaveSelect || !startInput || !endInput) return;

            const minToday = startInput.dataset.today;
            const minTomorrow = startInput.dataset.tomorrow;

            const enforceEndMin = () => {
                const minDate = startInput.value || startInput.min;
                endInput.min = minDate;
                if (endInput.value && endInput.value < minDate) {
                    endInput.value = minDate;
                }
            };

            const applyLeaveMin = () => {
                const isSick = leaveSelect.value === 'sakit';
                const minDate = isSick ? minToday : minTomorrow;
                startInput.min = minDate;
                if (startInput.value && startInput.value < minDate) {
                    startInput.value = minDate;
                }
                enforceEndMin();
            };

            leaveSelect.addEventListener('change', applyLeaveMin);
            startInput.addEventListener('change', enforceEndMin);
            applyLeaveMin();
        })();
    </script>
@endsection
