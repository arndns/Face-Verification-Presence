@extends('layout.employee')
@section('title', 'Dashboard')

@php
    use Carbon\Carbon;
    $employeeProfile = $employee;
    $todayStatus = $todayPresence
        ? ($todayPresence->waktu_pulang ? 'Presensi Selesai' : 'Presensi Berjalan')
        : 'Belum Presensi';
    $todayIn = optional($todayPresence?->waktu_masuk)->format('H:i') ?? '--:--';
    $todayOut = optional($todayPresence?->waktu_pulang)->format('H:i') ?? '--:--';
    $lastPresence = $recentPresences->first();
    $presencesWithDate = $recentPresences->filter(fn($presence) => $presence->waktu_masuk || $presence->waktu_pulang);
    $groupedPresences = $presencesWithDate->groupBy(function ($presence) {
        $date = $presence->waktu_masuk ?? $presence->waktu_pulang;
        return $date ? $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d') : 'unknown';
    });
    $weekKeys = $groupedPresences->keys()->sort()->values();
    $currentWeekKey = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    if (!$weekKeys->contains($currentWeekKey)) {
        $currentWeekKey = $weekKeys->last();
    }
@endphp

@section('content')
    <div class="page active" id="home-page">
        <div class="user-section">
            <div class="user-detail">
                <div class="user-identity">
                    <div class="avatar">
                        <img src="{{ $employeeProfile?->foto ? \Illuminate\Support\Facades\Storage::url($employeeProfile->foto) : asset('assets/image/profil-picture.png') }}"
                            alt="avatar" class="imaged w64 rounded-circle">
                    </div>
                    <div class="user-info">
                        <p class="eyebrow text-white-50 mb-1">Selamat datang kembali</p>
                        <h2 id="user-name">{{ $employeeProfile->nama ?? $user->username }}</h2>
                        <span id="user-role">{{ $employeeProfile->jabatan ?? '-' }}</span>
                        <div class="user-tags">
                            <span class="tag badge-face {{ $faceRegistered ? 'active' : '' }}">
                                <i class="fa-solid fa-face-smile me-1"></i>
                                {{ $faceRegistered ? 'Face ID Aktif' : 'Belum Rekam Wajah' }}
                            </span>
                            @if ($locationData)
                                <span class="tag badge-location">
                                    <i class="fa-solid fa-map-location-dot me-1"></i>{{ $locationData->kota }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="logout-button">
                    <form action="{{ route('logout') }}" method="POST" id="logout-form">
                        @csrf
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                                class="fa-solid fa-sign-out-alt"></i></a>
                    </form>
                </div>
            </div>
            <div class="user-status">
                <div>
                    <small class="text-uppercase text-white-50">Status hari ini</small>
                    <h3 class="mb-1">{{ $todayStatus }}</h3>
                    <p class="mb-0 text-white-75">
                        Gunakan verifikasi wajah dan pastikan berada dalam zona lokasi kantor sebelum absen.
                    </p>
                    @if ($lastPresence && !$todayPresence)
                        <p class="text-white-75 small mb-0 mt-2">
                            Terakhir tercatat: {{ optional($lastPresence->waktu_masuk)->translatedFormat('d M Y H:i') }}
                        </p>
                    @endif
                </div>
                <div class="user-status__times">
                    <div class="time-chip">
                        <span class="label">Masuk</span>
                        <strong>{{ $todayPresence ? $todayIn : 'Belum ada presensi' }}</strong>
                        <small class="{{ $todayPresence ? 'text-success' : 'text-light' }}">
                            {{ $todayPresence ? 'Terekam via pengenalan wajah' : 'Menunggu hadir' }}
                        </small>
                    </div>
                    <div class="time-chip">
                        <span class="label">Pulang</span>
                        <strong>{{ $todayPresence && $todayPresence->waktu_pulang ? $todayOut : '-' }}</strong>
                        <small
                            class="{{ $todayPresence && $todayPresence->waktu_pulang ? 'text-success' : 'text-warning' }}">
                            {{ $todayPresence && $todayPresence->waktu_pulang ? 'Sudah tercatat' : 'Siapkan verifikasi' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-strip card shadow-sm border-0 rounded-4 cta-card">
            <div class="action-strip__info">
                <small class="text-uppercase text-muted">Mulai presensi</small>
                <h3 class="mb-1">{{ $faceRegistered ? 'Siap melakukan verifikasi wajah' : 'Aktifkan Face ID Anda' }}</h3>
                <p class="mb-0 text-muted">Buka kamera untuk absen, sistem otomatis cek Face ID dan lokasi kantor.</p>
            </div>
            <div class="action-strip__cta">
                <a href="{{ route('employee.camera') }}" class="btn btn-primary btn-lg w-100 w-md-auto">
                    <i class="fa-solid fa-camera me-2"></i> Buka Kamera
                </a>
                <span class="cta-note">Wajah harus terdeteksi jelas sebelum absen.</span>
            </div>
        </div>

        <div class="insight-grid">
            <div class="status-card location-card">
                <div class="status-card__icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="w-100">
                    <h4>Zona Lokasi</h4>
                    @if ($locationData)
                        <p class="mb-2">
                            {{ $locationData->kota }} &middot; radius {{ $locationData->radius }} m<br>
                            <span class="text-muted">{{ $locationData->alamat }}</span>
                        </p>
                        <div class="location-status" id="location-status">
                            Belum memeriksa lokasi perangkat.
                        </div>
                        <button class="btn btn-outline-primary btn-sm mt-3 w-100" id="check-location"
                            data-lat="{{ $locationData->latitude }}" data-lon="{{ $locationData->longitude }}"
                            data-radius="{{ $locationData->radius }}">
                            <i class="fa-solid fa-location-crosshairs me-1"></i> Periksa Lokasi Saya
                        </button>
                    @else
                        <p class="mb-0">Lokasi kantor belum ditetapkan.</p>
                        <button class="btn btn-outline-secondary btn-sm mt-3 w-100" disabled>Periksa Lokasi</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="history-card card shadow-sm border-0 rounded-4 mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h5 class="mb-0">Riwayat Presensi</h5>
                        <small class="text-muted">Ditampilkan per minggu (tanggal | waktu masuk | waktu pulang)</small>
                    </div>
                    @if ($groupedPresences->isNotEmpty())
                        @php
                            $labelCurrentStart = $currentWeekKey ? Carbon::parse($currentWeekKey) : null;
                            $labelCurrentEnd = $labelCurrentStart ? $labelCurrentStart->copy()->endOfWeek() : null;
                        @endphp
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-primary btn-sm" data-week-nav="prev">Minggu sebelumnya</button>
                            <div class="week-label" id="week-label">
                                {{ $labelCurrentStart ? $labelCurrentStart->translatedFormat('d M') . ' - ' . $labelCurrentEnd->translatedFormat('d M') : '-' }}
                            </div>
                            <button class="btn btn-outline-primary btn-sm" data-week-nav="next">Minggu berikutnya</button>
                        </div>
                    @endif
                </div>

                @if ($groupedPresences->isNotEmpty())
                    @foreach ($weekKeys as $weekStart)
                        @php
                            $startDate = Carbon::parse($weekStart);
                            $endDate = $startDate->copy()->endOfWeek();
                            $rows = $groupedPresences->get($weekStart, collect())->sortBy('waktu_masuk');
                        @endphp
                        <div class="{{ $weekStart === $currentWeekKey ? '' : 'd-none' }}" data-week="{{ $weekStart }}" data-week-label="{{ $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M') }}">
                            <table class="table table-sm history-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Tanggal</th>
                                        <th scope="col">Waktu Masuk</th>
                                        <th scope="col">Waktu Pulang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $presence)
                                        <tr>
                                            <td>{{ optional($presence->waktu_masuk ?? $presence->waktu_pulang)->translatedFormat('d M Y') ?? '-' }}</td>
                                            <td>{{ optional($presence->waktu_masuk)->format('H:i') ?? '-' }}</td>
                                            <td>{{ optional($presence->waktu_pulang)->format('H:i') ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state text-center py-4">
                        <i class="fa-regular fa-circle-question fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 text-muted">Belum ada data presensi. Mulai dengan menekan tombol kamera.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkBtn = document.getElementById('check-location');
            const statusText = document.getElementById('location-status');

            if (!checkBtn || !statusText) {
                return;
            }

            const officeLat = parseFloat(checkBtn.dataset.lat);
            const officeLon = parseFloat(checkBtn.dataset.lon);
            const officeRadius = parseFloat(checkBtn.dataset.radius || '0');

            checkBtn.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    statusText.textContent = 'Browser Anda tidak mendukung geolocation.';
                    statusText.classList.add('text-danger');
                    return;
                }

                statusText.textContent = 'Mengambil koordinat perangkat...';
                statusText.classList.remove('text-danger', 'text-success');

                navigator.geolocation.getCurrentPosition((position) => {
                    const {
                        latitude,
                        longitude
                    } = position.coords;
                    const distance = calculateDistance(latitude, longitude, officeLat, officeLon);

                    statusText.textContent = `Posisi Anda ${distance.toFixed(0)} m dari kantor.`;
                    if (distance <= officeRadius) {
                        statusText.classList.add('text-success');
                        statusText.classList.remove('text-danger');
                    } else {
                        statusText.classList.add('text-danger');
                        statusText.classList.remove('text-success');
                    }
                }, (error) => {
                    let message = 'Gagal mendapatkan lokasi.';
                    if (error.code === error.PERMISSION_DENIED) {
                        message = 'Izin lokasi ditolak. Aktifkan GPS untuk memanfaatkan deteksi lokasi.';
                    }
                    statusText.textContent = message;
                    statusText.classList.add('text-danger');
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const weekOrder = @json($weekKeys);
            let activeWeek = "{{ $currentWeekKey ?? '' }}";
            const labelEl = document.getElementById('week-label');
            const prevBtn = document.querySelector('[data-week-nav="prev"]');
            const nextBtn = document.querySelector('[data-week-nav="next"]');
            const weekBlocks = Array.from(document.querySelectorAll('[data-week]'));

            if (!weekOrder.length) return;

            const setWeek = (weekKey) => {
                activeWeek = weekKey;
                weekBlocks.forEach((block) => {
                    block.classList.toggle('d-none', block.dataset.week !== weekKey);
                });
                const activeBlock = weekBlocks.find((block) => block.dataset.week === weekKey);
                if (activeBlock && labelEl) {
                    labelEl.textContent = activeBlock.dataset.weekLabel || '';
                }
                const idx = weekOrder.indexOf(weekKey);
                if (prevBtn) prevBtn.disabled = idx <= 0;
                if (nextBtn) nextBtn.disabled = idx === weekOrder.length - 1 || idx === -1;
            };

            prevBtn?.addEventListener('click', () => {
                const idx = weekOrder.indexOf(activeWeek);
                if (idx > 0) setWeek(weekOrder[idx - 1]);
            });

            nextBtn?.addEventListener('click', () => {
                const idx = weekOrder.indexOf(activeWeek);
                if (idx < weekOrder.length - 1) setWeek(weekOrder[idx + 1]);
            });

            setWeek(activeWeek || weekOrder[weekOrder.length - 1]);
        });

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const toRad = (value) => value * Math.PI / 180;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }
        @if ($presenceReminder['should_show'] ?? false)
            (function() {
                const shiftInfo = {
                    nama_shift: "{{ $presenceReminder['shift_name'] ?? '-' }}",
                    jam_masuk: "{{ $presenceReminder['shift_start'] ?? '-' }}",
                    jam_pulang: "{{ $presenceReminder['shift_end'] ?? '-' }}",
                    current_time: "{{ $presenceReminder['current_time'] ?? '--:--' }}"
                };

                Swal.fire({
                    icon: 'info',
                    title: 'Segera Lakukan Presensi Masuk',
                    html: `<div class="text-start">
                            <p>Shift <strong>${shiftInfo.nama_shift}</strong> sudah dimulai.</p>
                            <p><strong>Jam Masuk:</strong> ${shiftInfo.jam_masuk}</p>
                            <p><strong>Waktu Saat Ini:</strong> ${shiftInfo.current_time}</p>
                            <p class="mb-1"><strong>Jam Pulang:</strong> ${shiftInfo.jam_pulang}</p>
                            <p class="text-muted mb-0">Segera lakukan presensi agar tidak dianggap terlambat.</p>
                        </div>`,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-camera me-1"></i> Buka Kamera',
                    cancelButtonText: 'Nanti',
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('employee.camera') }}";
                    }
                });
            })();
        @endif
    </script>
@endsection
