@extends('layout.employee')
@section('title', 'Dashboard')

@php
    $employeeProfile = $employee;
    $todayStatus = $todayPresence
        ? ($todayPresence->waktu_pulang ? 'Presensi Selesai' : 'Presensi Berjalan')
        : 'Belum Presensi';
    $todayIn = optional($todayPresence?->waktu_masuk)->format('H:i') ?? '--:--';
    $todayOut = optional($todayPresence?->waktu_pulang)->format('H:i') ?? '--:--';
    $lastPresence = $recentPresences->first();
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
        </div>

        <div class="action-strip card shadow-sm border-0 rounded-4">
            <div class="action-strip__info">
                <small>Status Hari Ini</small>
                <h3 class="mb-1">{{ $todayStatus }}</h3>
                <p class="mb-0 text-muted">
                    Jam masuk: <strong>{{ $todayPresence ? $todayIn : 'Belum ada presensi' }}</strong>
                </p>
                <p class="mb-0 text-muted">
                    Jam pulang: <strong>{{ $todayPresence && $todayPresence->waktu_pulang ? $todayOut : '-' }}</strong>
                </p>
                @if ($lastPresence && !$todayPresence)
                    <p class="text-muted small mb-0">
                        Terakhir tercatat: {{ optional($lastPresence->waktu_masuk)->translatedFormat('d M Y H:i') }}
                    </p>
                @endif
            </div>
            <div class="action-strip__cta">
                <a href="{{ route('employee.camera') }}" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-camera me-2"></i> Buka Kamera
                </a>
                <span class="cta-note">Verifikasi wajah wajib sebelum presensi.</span>
            </div>
        </div>

        <div class="presence-stats">
            <div class="presence-card presence-card--in">
                <div class="presence-card__icon">
                    <i class="fa-solid fa-circle-arrow-down"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">Presensi Masuk</p>
                    <h4 class="mb-0">{{ $todayIn }}</h4>
                    <small class="{{ $todayPresence ? 'text-success' : 'text-muted' }}">
                        {{ $todayPresence ? 'Terekam via face recognition' : 'Belum tercatat' }}
                    </small>
                </div>
            </div>
            <div class="presence-card presence-card--out">
                <div class="presence-card__icon">
                    <i class="fa-solid fa-circle-arrow-up"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">Presensi Pulang</p>
                    <h4 class="mb-0">{{ $todayOut }}</h4>
                    <small
                        class="{{ $todayPresence && $todayPresence->waktu_pulang ? 'text-success' : 'text-warning' }}">
                        {{ $todayPresence && $todayPresence->waktu_pulang ? 'Sudah tercatat' : 'Menunggu verifikasi' }}
                    </small>
                </div>
            </div>
        </div>

        <div class="status-grid">
            <div class="status-card face-card">
                <div class="status-card__icon">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <div>
                    <h4>Face Recognition</h4>
                    <p>
                        {{ $faceRegistered
                            ? 'Data wajah Anda siap dipakai untuk presensi. Gunakan pencahayaan cukup agar deteksi akurat.'
                            : 'Belum ada data wajah. Hubungi admin untuk melakukan perekaman Face ID.' }}
                    </p>
                    @if ($employeeProfile?->faceEmbeddings)
                        <small class="text-muted">Terakhir diperbarui:
                            {{ optional($employeeProfile->faceEmbeddings->updated_at)->diffForHumans() }}</small>
                    @endif
                </div>
            </div>

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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Riwayat Presensi</h5>
                        <small class="text-muted">Menggunakan face recognition & geo-fence</small>
                    </div>
                </div>

                @if ($recentPresences->isNotEmpty())
                    <ul class="history-list list-unstyled mb-0">
                        @foreach ($recentPresences as $presence)
                            <li class="history-item">
                                <div>
                                    <strong>{{ optional($presence->waktu_masuk)->translatedFormat('d M Y') }}</strong>
                                    <div class="text-muted small">
                                        Masuk: {{ optional($presence->waktu_masuk)->format('H:i') ?? '-' }}
                                        @if ($presence->waktu_pulang)
                                            &middot; Pulang: {{ $presence->waktu_pulang->format('H:i') }}
                                        @endif
                                    </div>
                                </div>
                                <span
                                    class="history-pill {{ $presence->waktu_pulang ? 'pill-done' : 'pill-active' }}">{{ $presence->waktu_pulang ? 'Lengkap' : 'Masuk' }}</span>
                            </li>
                        @endforeach
                    </ul>
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
                    current_time: "{{ $presenceReminder['current_time'] ?? '--:--' }}",
                    timezone: "{{ $presenceReminder['timezone'] ?? config('app.timezone') }}",
                };

                Swal.fire({
                    icon: 'info',
                    title: 'Segera Lakukan Presensi Masuk',
                    html: `<div class="text-start">
                            <p>Shift <strong>${shiftInfo.nama_shift}</strong> sudah dimulai.</p>
                            <p><strong>Jam Masuk:</strong> ${shiftInfo.jam_masuk}</p>
                            <p><strong>Waktu Saat Ini:</strong> ${shiftInfo.current_time} (${shiftInfo.timezone})</p>
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
