@extends('layout.employee')
@section('title', 'history')
@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">
            <a href="{{ route('employee.index') }}" class="headerButton goBack text-light">
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">Riwayat Presensi</div>
        <div class="right" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="p-3 pt-4">

        @if ($histories->isEmpty())
            <div class="text-center py-5 border rounded-3 bg-white shadow-sm">
                <i class="fa-regular fa-face-sad-tear fa-2x mb-2 text-muted"></i>
                <p class="mb-0 text-muted">Belum ada riwayat presensi untuk akun ini.</p>
            </div>
        @else
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body d-flex flex-wrap gap-3 align-items-end">
                    <div>
                        <label class="form-label mb-1">Dari tanggal</label>
                        <input type="date" id="filter-from-date" class="form-control form-control-sm"
                            value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div>
                        <label class="form-label mb-1">Sampai tanggal</label>
                        <input type="date" id="filter-to-date" class="form-control form-control-sm"
                            value="{{ $filters['to'] ?? '' }}">
                    </div>
                </div>
            </div>

            <div class="list-group shadow-sm" id="history-list">
                @foreach ($histories as $presence)
                    <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between history-row"
                        data-date="{{ $presence['date_iso'] }}">
                        <div>
                            <div class="fw-semibold">{{ $presence['formatted_date'] }}</div>
                            <div class="text-muted small">Masuk: {{ $presence['masuk'] }} &middot; Pulang:
                                {{ $presence['pulang'] }}</div>
                        </div>
                        <span class="badge bg-{{ $presence['status_badge'] }} text-uppercase">
                            {{ $presence['status_label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3" style="margin-bottom: 80px;">
                {{ $histories->appends(request()->query())->links() }}
            </div>
            <div class="text-center py-4 text-muted d-none" id="history-empty">
                Data belum ada untuk rentang ini.
            </div>
        @endif
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fromDateInput = document.getElementById('filter-from-date');
            const toDateInput = document.getElementById('filter-to-date');
            const rows = Array.from(document.querySelectorAll('.history-row'));
            const emptyState = document.getElementById('history-empty');

            function applyFilters() {
                const fromDateVal = fromDateInput?.value || '';
                const toDateVal = toDateInput?.value || '';
                let visibleCount = 0;

                rows.forEach(row => {
                    const dateStr = row.dataset.date;
                    if (!dateStr) {
                        row.classList.add('d-none');
                        return;
                    }

                    const matchFrom = !fromDateVal || dateStr >= fromDateVal;
                    const matchTo = !toDateVal || dateStr <= toDateVal;

                    const show = matchFrom && matchTo;
                    row.classList.toggle('d-none', !show);
                    if (show) visibleCount++;
                });

                if (emptyState) {
                    emptyState.classList.toggle('d-none', visibleCount > 0);
                    if (visibleCount === 0) {
                        emptyState.textContent = 'Belum ada data presensi untuk pilihan ini.';
                    }
                }
            }

            fromDateInput?.addEventListener('change', applyFilters);
            toDateInput?.addEventListener('change', applyFilters);
            applyFilters();
        });
    </script>
@endsection
