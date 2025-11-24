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
                        <img src="{{ $employeeProfile?->foto ? route('storage.file', $employeeProfile->foto) : asset('assets/image/profil-picture.png') }}"
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
                            <span class="tag badge-time">
                                <i class="fas fa-clock me-1"></i>
                                <span id="server-time">{{ now()->format('H:i:s') }}</span>
                            </span>
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

        <div class="action-strip card shadow-sm border-0 rounded-4 cta-card p-4">
            <div class="w-100">
                <a href="{{ route('employee.camera') }}" class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3"></path>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"></path>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"></path>
                        <path d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
                        <path d="M9 10v2"></path>
                        <path d="M15 10v2"></path>
                        <path d="M12 14c-1 0-1.5.5-1.5 1s.5 1 1.5 1 1.5-.5 1.5-1-.5-1-1.5-1z"></path> <!-- Nose approximation -->
                        <path d="M9 17c1.5 1 4.5 1 6 0"></path>
                    </svg>
                    Mulai Presensi
                </a>
            </div>
        </div>

        <div class="insight-grid">
            <div class="status-card location-card d-block">
                @if ($locationData)
                    <div class="d-flex gap-2 align-items-stretch mb-3">
                        <div class="location-status flex-grow-1 d-flex align-items-center mb-0" id="location-status">
                            Belum memeriksa lokasi perangkat.
                        </div>
                        <button class="btn btn-outline-primary btn-sm flex-shrink-0 d-flex align-items-center gap-2" id="check-location"
                            data-lat="{{ $locationData->latitude }}" data-lon="{{ $locationData->longitude }}"
                            data-radius="{{ $locationData->radius }}">
                            <i class="fa-solid fa-location-crosshairs"></i> Periksa Lokasi Saya
                        </button>
                    </div>
                    <div id="location-map" class="location-map" style="display: none;">
                        <div id="map-container"></div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> Lokasi kantor belum ditetapkan.
                    </div>
                    <button class="btn btn-outline-secondary btn-sm mt-3 w-100" disabled>Periksa Lokasi</button>
                @endif
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
    <script>
        // Real-time Server Clock
        (function() {
            const serverTimeEl = document.getElementById('server-time');
            if (!serverTimeEl) return;

            // Parse initial server time
            const initialTimeStr = serverTimeEl.textContent.trim();
            const [hours, minutes, seconds] = initialTimeStr.split(':').map(Number);
            
            let serverDate = new Date();
            serverDate.setHours(hours, minutes, seconds, 0);

            function updateClock() {
                serverDate.setSeconds(serverDate.getSeconds() + 1);
                
                const h = String(serverDate.getHours()).padStart(2, '0');
                const m = String(serverDate.getMinutes()).padStart(2, '0');
                const s = String(serverDate.getSeconds()).padStart(2, '0');
                
                serverTimeEl.textContent = `${h}:${m}:${s}`;
            }

            setInterval(updateClock, 1000);
        })();
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkBtn = document.getElementById('check-location');
            const statusText = document.getElementById('location-status');
            const mapContainer = document.getElementById('location-map');
            let map = null;
            let userMarker = null;
            let officeMarker = null;
            let radiusCircle = null;

            if (!checkBtn || !statusText) {
                return;
            }

            const officeLat = parseFloat(checkBtn.dataset.lat);
            const officeLon = parseFloat(checkBtn.dataset.lon);
            const officeRadius = parseFloat(checkBtn.dataset.radius || '0');

            function initMap() {
                if (map) return; // Map already initialized

                map = L.map('map-container').setView([officeLat, officeLon], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                // Office marker
                officeMarker = L.marker([officeLat, officeLon], {
                    icon: L.divIcon({
                        className: 'custom-office-marker',
                        html: '<div style="background: #2563eb; color: white; padding: 8px 12px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.3);"><i class="fas fa-building"></i> Kantor</div>',
                        iconSize: [80, 40],
                        iconAnchor: [40, 40]
                    })
                }).addTo(map);

                // Radius circle
                radiusCircle = L.circle([officeLat, officeLon], {
                    color: '#2563eb',
                    fillColor: '#60a5fa',
                    fillOpacity: 0.2,
                    radius: officeRadius
                }).addTo(map);
            }

            function checkLocation() {
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

                    // Show and initialize map
                    mapContainer.style.display = 'block';
                    initMap();

                    // Update or create user marker
                    if (userMarker) {
                        userMarker.setLatLng([latitude, longitude]);
                    } else {
                        userMarker = L.marker([latitude, longitude], {
                            icon: L.divIcon({
                                className: 'custom-user-marker',
                                html: '<div style="background: #10b981; color: white; padding: 8px 12px; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.3);"><i class="fas fa-user"></i> Anda</div>',
                                iconSize: [80, 40],
                                iconAnchor: [40, 40]
                            })
                        }).addTo(map);
                    }

                    // Fit map to show both markers
                    const bounds = L.latLngBounds([
                        [latitude, longitude],
                        [officeLat, officeLon]
                    ]);
                    map.fitBounds(bounds, { padding: [50, 50] });
                    
                    // Force map resize calculation after container becomes visible
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);

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
            }

            checkBtn.addEventListener('click', checkLocation);
            
            // Auto check on load
            checkLocation();
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
