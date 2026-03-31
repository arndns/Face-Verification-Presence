@extends('layout.admin')
@section('title', 'Presensi Karyawan')
@section('content')
    <div class="container-fluid camera-page">
        <div class="row mb-3">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <h2 class="mb-0"><i class="fas fa-user-check text-primary me-2"></i> Presensi Karyawan</h2>
                <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        <div class="camera-shell mx-auto">
            <div class="w-100 mb-4" data-camera-wrapper>
                <input type="hidden" id="location" placeholder="Menunggu lokasi..." readonly>
                <!-- Wadah tidak akan melebihi lebar .w-100 -->
                <div class="camera-capture text-muted"
                    style="position:relative;width:100%;aspect-ratio:720/520;background:#000;overflow:hidden;border-radius:8px;">
                    <span style="position:absolute;inset:auto auto 8px 8px;z-index:3;color:#fff;opacity:.8;">Memuat
                        Kamera...</span>
                </div>
            </div>
            
            <div class="w-100">
                <button class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center gap-2"
                    id="attendance-action-btn">
                    <i class="fa-solid fa-fingerprint"></i>
                    <span id="button-text">Mulai Presensi</span>
                </button>
                <small class="text-muted d-block mt-2">
                    Tekan untuk memulai verifikasi presensi.
                </small>
            </div>

            <div class="w-100 mt-3 threshold-control">
                <label for="face-threshold-range" class="form-label small text-muted mb-1">Threshold Verifikasi</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range" id="face-threshold-range" min="0.2" max="0.6"
                        step="0.01" value="0.38">
                    <input type="number" class="form-control form-control-sm" id="face-threshold-input" min="0.2"
                        max="0.6" step="0.01" value="0.38">
                </div>
                <small class="text-muted d-block mt-1">Semakin kecil, semakin ketat.</small>
            </div>

            <div class="w-100 mt-4 presence-map-card">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Lokasi Presensi</h6>
                            @if ($presenceLocationPayload)
                                <span class="badge bg-primary">Radius {{ $presenceLocationPayload['radius'] ?? 0 }} m</span>
                            @endif
                        </div>
                        <div id="presence-map" class="presence-map rounded"></div>
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mt-3">
                            <div>
                                <div class="small mb-1 text-muted">Koordinat perangkat</div>
                                <div class="fw-semibold" id="location-status-text">Mencari lokasi...</div>
                                <div class="text-muted small" id="location-distance-text"></div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" id="refresh-location-btn">
                                <i class="fa-solid fa-location-crosshairs me-1"></i> Periksa Lokasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('style')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        canvas {
            position: absolute;
        }

        .camera-shell {
            max-width: 720px;
            width: 100%;
            margin: 0 auto;
        }

        .presence-map {
            width: 100%;
            min-height: 240px;
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
        }

        /* Matikan peta di halaman presensi (menggunakan koordinat dari dashboard) */
        .presence-map-card {
            display: none !important;
        }

        .camera-page {
            padding-bottom: 90px;
        }

        /* Nonaktifkan interaksi saat lokasi masih diproses */
        .location-loading-active .camera-page button,
        .location-loading-active .camera-page .btn,
        .location-loading-active .app-bottom-menu a,
        .location-loading-active [data-presence-nav] {
            pointer-events: none;
            opacity: 0.6;
        }
        /* Nonaktifkan interaksi jika geolokasi diblokir/diminta ulang */
        .presence-disabled .camera-page button,
        .presence-disabled .camera-page .btn,
        .presence-disabled .app-bottom-menu a,
        .presence-disabled [data-presence-nav] {
            pointer-events: none;
            opacity: 0.6;
        }

        /* Responsif untuk SweetAlert agar tidak menutupi tombol di mobile/desktop */
        .swal2-container {
            padding: 12px;
        }
        .swal2-popup {
            width: min(460px, 92vw);
            border-radius: 12px;
        }
        .swal2-actions {
            flex-wrap: wrap;
            gap: 8px;
        }
        .swal2-styled {
            min-width: 120px;
            flex: 1 1 auto;
        }
        @media (max-width: 576px) {
            .swal2-popup {
                padding: 18px 14px;
            }
            .swal2-title {
                font-size: 1.1rem;
            }
            .swal2-html-container {
                font-size: 0.95rem;
            }
        }

        /* Modal hasil presensi */
        .swal-presence-popup {
            width: min(520px, 94vw);
            border-radius: 16px;
            padding: 20px 18px;
        }
        .swal-presence-popup .swal2-title {
            font-size: 1.25rem;
        }
        .swal-presence-popup .swal2-html-container {
            text-align: left;
            font-size: 0.98rem;
            line-height: 1.4;
        }
        .swal-presence-confirm {
            min-width: 140px;
            padding: 10px 14px;
            font-weight: 700;
        }
        @media (max-width: 576px) {
            .swal-presence-popup {
                padding: 16px 14px;
            }
            .swal-presence-popup .swal2-title {
                font-size: 1.1rem;
            }
            .swal-presence-popup .swal2-html-container {
                font-size: 0.95rem;
            }
        }

        .threshold-control .form-range {
            flex: 1 1 auto;
        }
        .threshold-control .form-control {
            max-width: 96px;
        }

    </style>


@endsection
@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const EMPLOYEE_LOCATION = @json($presenceLocationPayload);
        const GEOLOCATION_OPTIONS_FAST = {
            enableHighAccuracy: false,
            maximumAge: 30000,
            timeout: 15000
        };
        const GEOLOCATION_OPTIONS_PRECISE = {
            enableHighAccuracy: true,
            maximumAge: 15000,
            timeout: 30000
        };
        const DEFAULT_FACE_THRESHOLD = 0.38;
        const FACE_VERIFICATION_MARGIN = 0.03;
        const FACE_THRESHOLD_MIN = 0.2;
        const FACE_THRESHOLD_MAX = 0.6;
        let faceVerificationThreshold = DEFAULT_FACE_THRESHOLD;
        const getFaceMatchLimit = () => faceVerificationThreshold + FACE_VERIFICATION_MARGIN;
        const VERIFICATION_SAMPLES = 5;
        const REQUIRED_MATCHES = 3;
        const FRAME_DELAY_MS = 120;
        const VIDEO_MIRRORED = false;
        const NAME_MIRRORED = true;
        const STATUS_POLL_INTERVAL_MS = 60000;
        const MTCNN_OPTIONS = {
            minFaceSize: 60,
            scoreThresholds: [0.5, 0.6, 0.7],
            scaleFactor: 0.8
        };
        const FACE_MODEL_BASE_URL = `${window.location.origin}/models`;
        const MAX_ACCEPTABLE_ACCURACY = 100; // meters
        const MAX_JUMP_METERS = 200; // ignore sudden jumps
        const GEO_SMOOTH_HISTORY = 5;
        const GEO_MAX_POSITION_AGE_MS = 15000;
        const GEO_MIN_MOVEMENT_METERS = 12;
        const GEO_ACCURACY_IMPROVEMENT_MARGIN = 5;
        const GEO_STABLE_ACCURACY = 35;
        const MAP_DISABLED = true; // jangan tampilkan peta di halaman presensi
        const USE_DASHBOARD_GPS = false; // ambil lokasi langsung dari perangkat admin
        let referenceEmbeddings = []; // Diisi saat init() dari API (multi-orientasi)
        let activeEmployee = null;
        let activeEmployeeId = null;
        let thresholdRangeEl = null;
        let thresholdInputEl = null;
        let isVerifying = false;
        let recentSuccess = false;
        let attendanceButtonInitialized = false;
        let faceApiScriptPromise = null;
        let faceModelPromise = null;
        let recognitionModelPromise = null;
        const presenceState = {
            hasCheckedIn: false,
            hasCheckedOut: false,
            canCheckOut: false,
            lastClockIn: null,
            lastClockOut: null,
            isOnLeave: false,
            leaveInfo: null,
        };
        const LEAVE_LABELS = {
            sakit: 'Sakit',
            izin: 'Izin',
            cuti_tahunan: 'Cuti Tahunan',
        };
        let hasShownCheckInReminder = false;
        let hasShownCheckoutReminder = false;
        let presenceStatusInterval = null;
        let lokasiInput = null;
        let locationAlertElement = null;
        let locationAlertTextElement = null;
        let locationStatusTextEl = null;
        let locationDistanceTextEl = null;
        let refreshLocationBtn = null;
        const LOCATION_CACHE_MAX_AGE_MS = 5 * 60 * 1000; // 5 menit
        let presenceMap = null;
        let officeMarker = null;
        let userMarker = null;
        let radiusCircle = null;
        let lastDetectAt = 0;
        const DETECTION_INTERVAL_MS = 220; // turunkan beban CPU pada perangkat lemah
        const geoState = {
            latitude: null,
            longitude: null,
            accuracy: null,
        };
        let lastStableFix = null;
        const geoHistory = [];
        const locationValidation = {
            ready: false,
            isInsideRadius: false,
            distanceMeters: null,
        };
        let locationLoading = true;
        const detectionState = {
            status: 'idle', // idle | ready | unknown | multiple | no_face
            lastChangedAt: null,
        };
        function setLocationLoadingState(isLoading, message = null) {
            locationLoading = isLoading;
            document.body.classList.toggle('location-loading-active', Boolean(isLoading));
            if (message && locationStatusTextEl) {
                locationStatusTextEl.textContent = message;
                if (locationDistanceTextEl) locationDistanceTextEl.textContent = '';
            }
        }
        // Map variables removed
        let locationNotConfiguredModalShown = false;
        let outsideRadiusModalShown = false;
        let missingLocationModalShown = false;
        let locationAlertDismissed = false;
        let locationAlertLastType = null;
        let locationAlertLastMessage = null;
        let geoWatchId = null;
        let geoRetryTimer = null;

        function getAttendanceButtonElements() {
            return {
                button: document.getElementById('attendance-action-btn'),
                text: document.getElementById('button-text'),
            };
        }

        function setDetectionState(status) {
            if (!status || detectionState.status === status) {
                return;
            }
            detectionState.status = status;
            detectionState.lastChangedAt = Date.now();
            applyButtonIdleState();
        }

        function setButtonLoadingState(message = 'Memverifikasi...') {
            const { button, text } = getAttendanceButtonElements();
            if (button) button.disabled = true;
            if (text) text.textContent = message;
        }

        function applyButtonIdleState() {
            const { button, text } = getAttendanceButtonElements();
            if (!button || !text) return;

            if (isVerifying) {
                text.textContent = text.textContent || 'Memverifikasi...';
                button.disabled = true;
                return;
            }

            let label = 'Mulai Presensi';
            let disabled = false;
            const mode = getCurrentActionMode();

            if (mode === 'idle') {
                label = 'Arahkan Wajah';
            } else if (mode === 'check_out') {
                label = 'Presensi Pulang';
            } else if (mode === 'check_in') {
                label = 'Presensi Masuk';
            } else if (mode === 'done') {
                label = 'Presensi Selesai';
                disabled = true;
            } else if (mode === 'waiting') {
                label = 'Menunggu Jam Pulang';
                disabled = true;
            } else if (mode === 'on_leave') {
                label = 'Sedang Izin/Cuti';
                disabled = true;
            }

            if (!EMPLOYEE_LOCATION) {
                label = 'Lokasi belum diatur';
                disabled = true;
            } else if (!locationValidation.ready) {
                label = 'Mengambil lokasi...';
                disabled = true;
            }

            const locationReady = Boolean(EMPLOYEE_LOCATION && locationValidation.ready);
            if (locationReady && detectionState.status === 'ready') {
                label = 'Siap Presensi';
                disabled = false;
            } else if (!disabled) {
                if (detectionState.status === 'multiple') {
                    label = 'Terlalu Banyak Wajah';
                    disabled = true;
                } else if (detectionState.status === 'unknown') {
                    label = 'Wajah Tidak Dikenali';
                    disabled = true;
                } else if (detectionState.status === 'no_face') {
                    label = 'Cari Wajah';
                    disabled = true;
                }
            }

            text.textContent = label;
            button.disabled = disabled || isVerifying;
        }

        function resetButton(customText = null) {
            const { button, text } = getAttendanceButtonElements();
            isVerifying = false;
            if (button) button.disabled = false;
            if (text && customText) text.textContent = customText;
            applyButtonIdleState();
        }

        function clampThresholdValue(value) {
            const parsed = Number.parseFloat(value);
            if (!Number.isFinite(parsed)) return null;
            return Math.min(FACE_THRESHOLD_MAX, Math.max(FACE_THRESHOLD_MIN, parsed));
        }

        function applyThresholdValue(value) {
            const clamped = clampThresholdValue(value);
            if (clamped === null) return;
            faceVerificationThreshold = clamped;
            const formatted = clamped.toFixed(2);
            if (thresholdRangeEl) thresholdRangeEl.value = formatted;
            if (thresholdInputEl) thresholdInputEl.value = formatted;
        }

        function initThresholdControls() {
            thresholdRangeEl = document.getElementById('face-threshold-range');
            thresholdInputEl = document.getElementById('face-threshold-input');
            if (!thresholdRangeEl && !thresholdInputEl) return;

            const initial = thresholdInputEl?.value || thresholdRangeEl?.value || DEFAULT_FACE_THRESHOLD;
            applyThresholdValue(initial);

            if (thresholdRangeEl) {
                thresholdRangeEl.addEventListener('input', (event) => {
                    applyThresholdValue(event.target.value);
                });
            }
            if (thresholdInputEl) {
                thresholdInputEl.addEventListener('input', (event) => {
                    applyThresholdValue(event.target.value);
                });
                thresholdInputEl.addEventListener('change', (event) => {
                    applyThresholdValue(event.target.value);
                });
            }
        }

        function resetPresenceState() {
            presenceState.hasCheckedIn = false;
            presenceState.hasCheckedOut = false;
            presenceState.canCheckOut = false;
            presenceState.lastClockIn = null;
            presenceState.lastClockOut = null;
            presenceState.isOnLeave = false;
            presenceState.leaveInfo = null;
            hasShownCheckInReminder = false;
            hasShownCheckoutReminder = false;
        }

        function setActiveEmployee(match) {
            const nextId = match?.employeeId ?? null;
            if (!nextId) {
                if (activeEmployeeId !== null) {
                    activeEmployeeId = null;
                    activeEmployee = null;
                    resetPresenceState();
                    applyButtonIdleState();
                }
                return;
            }

            if (activeEmployeeId === nextId) {
                return;
            }

            activeEmployeeId = nextId;
            activeEmployee = {
                id: match.employeeId,
                name: match.employeeName || match.employeeNik || null,
                nik: match.employeeNik || null,
            };
            resetPresenceState();
            monitorPresenceStatus({ showReminders: true, employeeId: activeEmployeeId });
            applyButtonIdleState();
        }

        function confirmExit() {
            if (window.Swal && typeof Swal.fire === 'function') {
                Swal.fire({
                    title: 'Keluar dari Halaman Presensi?',
                    text: 'Proses presensi akan dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Keluar',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route('admin.index') }}';
                    }
                });
            } else {
                if (confirm('Keluar dari halaman presensi?')) {
                    window.location.href = '{{ route('admin.index') }}';
                }
            }
        }

        function initializeGeolocation() {
            if (USE_DASHBOARD_GPS) {
                if (locationValidation.ready && geoState.latitude !== null && geoState.longitude !== null) {
                    setLocationLoadingState(false);
                } else {
                    setLocationLoadingState(true, 'Menunggu data lokasi dari dashboard...');
                }
                return;
            }
            if (geoRetryTimer) {
                clearTimeout(geoRetryTimer);
                geoRetryTimer = null;
            }
            const isSecure = window.isSecureContext || ['localhost', '127.0.0.1'].includes(location.hostname);
            if (!isSecure) {
                const insecureMessage =
                    'Browser memblokir geolokasi karena halaman tidak diakses via HTTPS/localhost. Buka lewat https atau localhost agar izin lokasi muncul.';
                updateLocationAlert(insecureMessage, 'warning');
                // Tetap coba meminta lokasi; beberapa browser masih mengizinkan di HTTP (mobile/local)
            }

            if (!navigator.geolocation) {
                updateLocationAlert('Perangkat Anda tidak mendukung geolocation.', 'danger');
                setLocationLoadingState(true, 'Perangkat tidak mendukung geolokasi.');
                return;
            }

            if (geoWatchId !== null) {
                navigator.geolocation.clearWatch(geoWatchId);
                geoWatchId = null;
            }

            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, GEOLOCATION_OPTIONS_FAST);
            geoWatchId = navigator.geolocation.watchPosition(successCallback, errorCallback, GEOLOCATION_OPTIONS_PRECISE);
        }

        function wireLocationButton() {
            if (!refreshLocationBtn) return;
            if (USE_DASHBOARD_GPS) {
                refreshLocationBtn.classList.add('d-none');
                return;
            }
            const restore = () => {
                refreshLocationBtn.disabled = false;
                refreshLocationBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Periksa Lokasi';
            };
            refreshLocationBtn.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    updateLocationStatusUI('Perangkat tidak mendukung geolokasi', true);
                    return;
                }
                refreshLocationBtn.disabled = true;
                refreshLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memeriksa...';
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        successCallback(pos);
                        restore();
                    },
                    (err) => {
                        errorCallback(err);
                        restore();
                    },
                    GEOLOCATION_OPTIONS_FAST
                );
            });
        }

        function successCallback(position) {
            applySmoothedLocation(position);

            // Sinkronkan dengan cache lokasi (dipakai juga oleh dashboard)
            try {
                sessionStorage.setItem('employeeLastGeo', JSON.stringify({
                    latitude: geoState.latitude,
                    longitude: geoState.longitude,
                    accuracy: geoState.accuracy ?? null,
                    ts: Date.now()
                }));
                sessionStorage.removeItem('employeeGeoBlocked');
                document.body.classList.remove('presence-disabled');
            } catch (e) {
                console.warn('Gagal menyimpan cache lokasi:', e);
            }

            missingLocationModalShown = false;

            validateEmployeeLocation();
            updateLocationAlert();
            updatePresenceMapWithUser();
            updateLocationStatusUI();
            if (locationValidation.ready && geoState.latitude !== null && geoState.longitude !== null) {
                setLocationLoadingState(false);
                applyButtonIdleState();
            }
        }

        function errorCallback(error) {
            console.warn('Tidak dapat membaca lokasi pengguna:', error);
            const code = (error && typeof error.code !== 'undefined') ? error.code : null;
            const hasFallback = lastStableFix || (geoState.latitude !== null && geoState.longitude !== null);
            if (!hasFallback) {
                geoState.latitude = null;
                geoState.longitude = null;
                geoState.accuracy = null;
                lastStableFix = null;
                geoHistory.length = 0;
                try {
                    sessionStorage.removeItem('employeeLastGeo');
                } catch (e) {
                    // abaikan
                }

                if (lokasiInput) {
                    lokasiInput.value = '';
                }

                locationValidation.ready = false;
                locationValidation.isInsideRadius = false;
                locationValidation.distanceMeters = null;
            }

            let message = 'Berikan izin lokasi agar presensi dapat diverifikasi.';
            if (code === (error?.PERMISSION_DENIED)) {
                message = "Perizinan lokasi ditolak. Izin diperlukan untuk presensi.";
            } else if (code === (error?.POSITION_UNAVAILABLE)) {
                message = "Informasi lokasi tidak tersedia.";
            } else if (code === (error?.TIMEOUT)) {
                message = "Permintaan lokasi melebihi batas waktu.";
            } else if (code !== null) {
                message = "Terjadi kesalahan saat membaca lokasi perangkat.";
            }

            if (hasFallback) {
                const statusMsg = code === (error?.TIMEOUT)
                    ? 'GPS lambat/timeout, gunakan lokasi terakhir dan coba ulang.'
                    : 'GPS terbaru tidak tersedia, gunakan lokasi terakhir.';
                updateStatusIndicator('Menggunakan lokasi sebelumnya', statusMsg, 'warning');
                validateEmployeeLocation();
                updateLocationAlert(message, code === (error?.TIMEOUT) ? 'warning' : 'danger');
                updateLocationStatusUI();
                updatePresenceMapWithUser();
                if (locationValidation.ready && geoState.latitude !== null && geoState.longitude !== null) {
                    setLocationLoadingState(false);
                    applyButtonIdleState();
                }
            } else {
                updateLocationAlert(message, 'danger');
                showLocationRequirementModal(message);
                updateStatusIndicator(code === (error?.TIMEOUT) ? 'Lokasi Timeout' : 'Lokasi Error', message, 'danger');
                updateLocationStatusUI(message, true);
                setLocationLoadingState(true, message);
            }

            if (code === (error?.PERMISSION_DENIED)) {
                try {
                    sessionStorage.setItem('employeeGeoBlocked', '1');
                } catch (e) {
                    // ignore
                }
                document.body.classList.add('presence-disabled');
                return;
            }
            if (!geoRetryTimer) {
                geoRetryTimer = setTimeout(() => {
                    geoRetryTimer = null;
                    initializeGeolocation();
                }, code === (error?.TIMEOUT) ? 3000 : 5000);
            }
        }

 

        document.addEventListener("DOMContentLoaded", () => {
            lokasiInput = document.getElementById('location');
            locationAlertElement = document.getElementById('locationAlertWrapper');
            locationAlertTextElement = document.getElementById('locationValidationStatusText');
            locationStatusTextEl = document.getElementById('location-status-text');
            locationDistanceTextEl = document.getElementById('location-distance-text');
            refreshLocationBtn = document.getElementById('refresh-location-btn');
            initThresholdControls();
            setLocationLoadingState(true, 'Mengambil lokasi perangkat...');

            if (!EMPLOYEE_LOCATION) {
                ensureLocationConfigured();
            } else {
                updateLocationAlert();
            }

            initPresenceMap();
            loadCachedGeoLocation();
            initializeGeolocation();
            wireLocationButton();

            if (!presenceStatusInterval) {
                presenceStatusInterval = setInterval(() => {
                    if (activeEmployeeId) {
                        monitorPresenceStatus({
                            showReminders: true,
                            employeeId: activeEmployeeId
                        });
                    }
                }, STATUS_POLL_INTERVAL_MS);
            }

            init();
            applyButtonIdleState();
        });



        function validateEmployeeLocation() {
            if (!EMPLOYEE_LOCATION) {
                locationValidation.ready = false;
                locationValidation.isInsideRadius = false;
                locationValidation.distanceMeters = null;
                ensureLocationConfigured();
                applyButtonIdleState();
                return;
            }

            if (EMPLOYEE_LOCATION.latitude === null || EMPLOYEE_LOCATION.longitude === null) {
                locationValidation.ready = false;
                locationValidation.isInsideRadius = false;
                locationValidation.distanceMeters = null;
                ensureLocationConfigured();
                applyButtonIdleState();
                return;
            }

            if (geoState.latitude === null || geoState.longitude === null) {
                locationValidation.ready = false;
                locationValidation.isInsideRadius = false;
                locationValidation.distanceMeters = null;
                applyButtonIdleState();
                return;
            }

            const distance = calculateDistanceMeters(
                geoState.latitude,
                geoState.longitude,
                Number(EMPLOYEE_LOCATION.latitude),
                Number(EMPLOYEE_LOCATION.longitude)
            );

            locationValidation.ready = true;
            locationValidation.distanceMeters = distance;
            const radiusLimit = Number(EMPLOYEE_LOCATION.radius || 0);
            locationValidation.isInsideRadius = radiusLimit <= 0 || distance <= radiusLimit;

            if (!locationValidation.isInsideRadius) {
                promptOutsideRadiusModal(distance, radiusLimit);
            } else {
                outsideRadiusModalShown = false;
            }

            updateStatusIndicatorForLocation();
            applyButtonIdleState();
            updatePresenceMapWithUser();
            updateLocationStatusUI();
        }

        function updateLocationAlert(message = null, status = 'info') {
            if (!locationAlertElement) {
                return;
            }

            if (!EMPLOYEE_LOCATION) {
                setAlertState(locationAlertElement, 'warning',
                    'Lokasi presensi belum ditetapkan. Hubungi admin untuk bantuan.');
                return;
            }

            if (message) {
                setAlertState(locationAlertElement, status, message);
                return;
            }

            if (!locationValidation.ready) {
                setAlertState(locationAlertElement, 'info',
                    `Radius lokasi: ${EMPLOYEE_LOCATION.radius || 0} m. Menunggu lokasi perangkat...`);
                return;
            }

            hideLocationAlert();
        }

        function setAlertState(element, type, message) {
            if (!element || !locationAlertTextElement) {
                return;
            }

            const finalMessage = typeof message === 'string' ? message : String(message ?? '');
            if (locationAlertDismissed && locationAlertLastType === type && locationAlertLastMessage === finalMessage) {
                return;
            }

            locationAlertLastType = type;
            locationAlertLastMessage = finalMessage;

            const baseClass = 'small mb-3 d-flex align-items-start justify-content-between gap-2';
            element.className = `alert alert-${type} ${baseClass}`;
            element.classList.remove('d-none');
            locationAlertDismissed = false;
            locationAlertTextElement.innerHTML = finalMessage;
        }

        function hideLocationAlert() {
            if (!locationAlertElement) {
                return;
            }
            locationAlertElement.classList.add('d-none');
            locationAlertLastType = null;
            locationAlertLastMessage = null;
        }

        function dismissLocationAlert() {
            if (!locationAlertElement) {
                return;
            }
            locationAlertDismissed = true;
            locationAlertElement.classList.add('d-none');
        }

        function updateLocationStatusUI(errorMessage = null, isError = false) {
            if (!locationStatusTextEl || !locationDistanceTextEl) return;

            if (errorMessage) {
                locationStatusTextEl.textContent = errorMessage;
                locationDistanceTextEl.textContent = '';
                return;
            }

            if (geoState.latitude === null || geoState.longitude === null) {
                locationStatusTextEl.textContent = 'Mencari lokasi...';
                locationDistanceTextEl.textContent = '';
                return;
            }

            locationStatusTextEl.textContent = `${geoState.latitude.toFixed(5)}, ${geoState.longitude.toFixed(5)}`;

            if (locationValidation.ready && locationValidation.distanceMeters !== null) {
                const radiusLimit = Number(EMPLOYEE_LOCATION?.radius || 0);
                const insideText = locationValidation.isInsideRadius ? 'Dalam radius' : 'Di luar radius';
                locationDistanceTextEl.textContent =
                    `${locationValidation.distanceMeters.toFixed(1)} m dari kantor (${insideText}${radiusLimit ? `, batas ${radiusLimit} m` : ''})`;
            } else {
                locationDistanceTextEl.textContent = '';
            }
        }

        function calculateDistanceMeters(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }

        function applySmoothedLocation(position) {
            const lat = Number(position.coords.latitude);
            const lon = Number(position.coords.longitude);
            const accuracy = Number(position.coords.accuracy ?? 9999);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                return;
            }

            const now = Date.now();
            const posAge = position.timestamp ? now - Number(position.timestamp) : 0;
            const last = lastStableFix || geoHistory[geoHistory.length - 1] || null;

            if (posAge > GEO_MAX_POSITION_AGE_MS && last) {
                console.log('Mengabaikan titik lokasi lama, menunggu pembaruan yang lebih baru');
                geoState.latitude = last.latitude;
                geoState.longitude = last.longitude;
                geoState.accuracy = last.accuracy ?? accuracy;
                return;
            }

            if (accuracy > MAX_ACCEPTABLE_ACCURACY && last) {
                // terlalu tidak akurat, abaikan bila kita sudah punya titik
                updateLocationStatusUI('Akurasi GPS rendah, memakai lokasi sebelumnya.', true);
                geoState.latitude = last.latitude;
                geoState.longitude = last.longitude;
                geoState.accuracy = last.accuracy ?? accuracy;
                return;
            }

            if (last) {
                const jump = calculateDistanceMeters(lat, lon, last.latitude, last.longitude);
                const notBetterAccuracy = accuracy >= (last.accuracy ?? accuracy) - GEO_ACCURACY_IMPROVEMENT_MARGIN;

                // Abaikan jitter kecil bila akurasi tidak membaik
                if (jump < GEO_MIN_MOVEMENT_METERS && notBetterAccuracy) {
                    geoState.latitude = last.latitude;
                    geoState.longitude = last.longitude;
                    geoState.accuracy = Math.min(last.accuracy ?? accuracy, accuracy);
                    if (lokasiInput && Number.isFinite(geoState.latitude) && Number.isFinite(geoState.longitude)) {
                        lokasiInput.value = `${geoState.latitude},${geoState.longitude}`;
                    }
                    return;
                }

                if (jump > MAX_JUMP_METERS && accuracy >= (last.accuracy ?? accuracy)) {
                    // lompat besar dengan akurasi tidak lebih baik, abaikan
                    return;
                }
            }

            geoHistory.push({ latitude: lat, longitude: lon, accuracy });
            if (geoHistory.length > GEO_SMOOTH_HISTORY) geoHistory.shift();

            // Weighted average dengan bobot kebalikan akurasi
            let sumLat = 0;
            let sumLon = 0;
            let sumW = 0;
            geoHistory.forEach((p) => {
                const w = 1 / Math.max(p.accuracy || 1, 1);
                sumLat += p.latitude * w;
                sumLon += p.longitude * w;
                sumW += w;
            });
            if (sumW === 0) {
                geoState.latitude = lat;
                geoState.longitude = lon;
            } else {
                geoState.latitude = sumLat / sumW;
                geoState.longitude = sumLon / sumW;
            }
            geoState.accuracy = accuracy;

            if (!lastStableFix || accuracy + GEO_ACCURACY_IMPROVEMENT_MARGIN < (lastStableFix.accuracy ?? Infinity) || accuracy <= GEO_STABLE_ACCURACY) {
                lastStableFix = {
                    latitude: geoState.latitude,
                    longitude: geoState.longitude,
                    accuracy,
                    ts: now
                };
            }

            if (lokasiInput && Number.isFinite(geoState.latitude) && Number.isFinite(geoState.longitude)) {
                lokasiInput.value = `${geoState.latitude},${geoState.longitude}`;
            }
        }

        function promptOutsideRadiusModal(distance, radius) {
            if (outsideRadiusModalShown) {
                return;
            }
            outsideRadiusModalShown = true;
            Swal.fire({
                icon: 'warning',
                title: 'Di luar radius lokasi',
                html: `<div class="text-start">
                        <p>Anda berada ${distance.toFixed(1)} meter dari titik kantor.</p>
                        <p>Radius maksimum yang diizinkan adalah ${radius} meter.</p>
                    </div>`,
                confirmButtonText: 'Mengerti'
            });
        }

        function showLocationRequirementModal(message) {
            if (missingLocationModalShown) {
                return;
            }
            missingLocationModalShown = true;
            Swal.fire({
                icon: 'warning',
                title: 'Akses Lokasi Diperlukan',
                text: message,
                confirmButtonText: 'Mengerti'
            }).then(() => {
                missingLocationModalShown = false;
            });
        }

        function ensureLocationConfigured() {
            if (locationNotConfiguredModalShown || EMPLOYEE_LOCATION) {
                return;
            }

            locationNotConfiguredModalShown = true;
            Swal.fire({
                icon: 'info',
                title: 'Lokasi Presensi Belum Tersedia',
                text: 'Admin belum menetapkan lokasi presensi untuk akun Anda. Presensi tidak dapat dilanjutkan.',
                confirmButtonText: 'Mengerti'
            });
        }

        function getOfficeLatLng() {
            if (!EMPLOYEE_LOCATION) return { lat: null, lng: null };
            const lat = Number(EMPLOYEE_LOCATION.latitude);
            const lng = Number(EMPLOYEE_LOCATION.longitude);
            return {
                lat: isNaN(lat) ? null : lat,
                lng: isNaN(lng) ? null : lng
            };
        }

        function initPresenceMap() {
            if (MAP_DISABLED) return;
            if (presenceMap || !EMPLOYEE_LOCATION) return;
            if (typeof L === 'undefined') return;
            const mapEl = document.getElementById('presence-map');
            const { lat, lng } = getOfficeLatLng();
            if (!mapEl || lat === null || lng === null) return;

            presenceMap = L.map(mapEl).setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(presenceMap);

            officeMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'custom-office-marker',
                    html: '<div style="background:#2563eb;color:#fff;padding:6px 10px;border-radius:10px;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.25);">Kantor</div>',
                    iconSize: [60, 32],
                    iconAnchor: [30, 32]
                })
            }).addTo(presenceMap);

            const radiusVal = Number(EMPLOYEE_LOCATION.radius || 0);
            if (!isNaN(radiusVal) && radiusVal > 0) {
                radiusCircle = L.circle([lat, lng], {
                    color: '#2563eb',
                    fillColor: '#60a5fa',
                    fillOpacity: 0.25,
                    radius: radiusVal
                }).addTo(presenceMap);
            }
        }

        function updatePresenceMapWithUser() {
            if (MAP_DISABLED) return;
            initPresenceMap();
            if (!presenceMap) return;
            const { latitude, longitude } = geoState;
            const { lat: officeLat, lng: officeLng } = getOfficeLatLng();
            if (latitude === null || longitude === null) return;

            if (!userMarker) {
                userMarker = L.marker([latitude, longitude], {
                    icon: L.divIcon({
                        className: 'custom-user-marker',
                        html: '<div style="background:#10b981;color:#fff;padding:6px 10px;border-radius:10px;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.25);">Anda</div>',
                        iconSize: [60, 32],
                        iconAnchor: [30, 32]
                    })
                }).addTo(presenceMap);
            } else {
                userMarker.setLatLng([latitude, longitude]);
            }

            if (officeLat !== null && officeLng !== null) {
                const bounds = L.latLngBounds([
                    [latitude, longitude],
                    [officeLat, officeLng]
                ]);
                presenceMap.fitBounds(bounds, { padding: [40, 40] });
            } else {
                presenceMap.setView([latitude, longitude], 16);
            }
        }

        function loadCachedGeoLocation() {
            try {
                const raw = sessionStorage.getItem('employeeLastGeo');
                if (!raw) return;
                const cached = JSON.parse(raw);
                if (!cached || typeof cached !== 'object') return;
                const age = Date.now() - Number(cached.ts || 0);
                if (isNaN(age) || age > LOCATION_CACHE_MAX_AGE_MS) return;

                geoState.latitude = Number(cached.latitude ?? null);
                geoState.longitude = Number(cached.longitude ?? null);
                geoState.accuracy = cached.accuracy != null ? Number(cached.accuracy) : null;
                if (isFinite(geoState.latitude) && isFinite(geoState.longitude)) {
                    geoHistory.push({
                        latitude: geoState.latitude,
                        longitude: geoState.longitude,
                        accuracy: geoState.accuracy ?? MAX_ACCEPTABLE_ACCURACY
                    });
                    if (geoHistory.length > GEO_SMOOTH_HISTORY) geoHistory.shift();
                    lastStableFix = {
                        latitude: geoState.latitude,
                        longitude: geoState.longitude,
                        accuracy: geoState.accuracy ?? MAX_ACCEPTABLE_ACCURACY,
                        ts: Date.now() - age
                    };
                    if (lokasiInput) {
                        lokasiInput.value = `${geoState.latitude},${geoState.longitude}`;
                    }
                    validateEmployeeLocation();
                    updatePresenceMapWithUser();
                    updateLocationStatusUI();
                    if (locationValidation.ready) {
                        setLocationLoadingState(false);
                        applyButtonIdleState();
                    }
                } else if (USE_DASHBOARD_GPS) {
                    setLocationLoadingState(true, 'Lokasi dashboard belum tersedia, buka dashboard untuk memperbarui GPS.');
                }
            } catch (e) {
                console.warn('Gagal memuat cache lokasi:', e);
            }
        }

        async function init() {
            try {
                await ensureFaceAPI();
            } catch (e) {
                console.error('FaceAPI gagal dimuat:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memuat FaceAPI',
                    text: e?.message || 'Library face-api.min.js tidak bisa diunduh. Periksa koneksi atau pastikan CDN diperbolehkan.',
                });
                return;
            }
            try {
                const makeDescriptor = (val) => new Array(128).fill(val);
                const testDist = faceapi.euclideanDistance(makeDescriptor(0.12), makeDescriptor(0.14));
                console.log('FaceAPI descriptor test distance (~0.226 expected):', testDist);
            } catch (e) {
                console.warn('FaceAPI distance test gagal:', e);
            }

            // Tampilkan loading, tapi biarkan kamera mulai
            Swal.fire({
                title: 'Memuat Sistem',
                text: 'Menyiapkan kamera dan model kecerdasan buatan...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                
                const cameraPromise = startCamera(); 
                const modelsPromise = Promise.all([loadDetectionModels(), loadRecognitionModel()]);
                const embeddingPromise = getReferenceEmbedding();

                
                await Promise.all([cameraPromise, modelsPromise, embeddingPromise]);

                Swal.close();
                tryInitAttendanceButton(document.querySelector('.camera-capture video'));
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Inisialisasi Gagal',
                    text: error.message,
                });
                console.error(error);
            }
        }

        function ensureFaceAPI() {
            if (window.faceapi) return Promise.resolve();
            if (faceApiScriptPromise) return faceApiScriptPromise;

            const CDN_FALLBACK = "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js";
            const assetPath = "/assets/js/face-api.min.js";
            const bladeUrl = "{{ asset('assets/js/face-api.min.js') }}";
            const originUrl = `${window.location.origin}${assetPath}`;
            const normalizeProtocol = (url) => {
                if (!url) return null;
                if (window.location.protocol === 'https:' && url.startsWith('http://')) {
                    return url.replace('http://', 'https://');
                }
                return url;
            };
            const candidates = Array.from(
                new Set([bladeUrl, originUrl, CDN_FALLBACK].map(normalizeProtocol).filter(Boolean))
            );

            faceApiScriptPromise = new Promise((resolve, reject) => {
                const tryLoad = (idx = 0) => {
                    if (idx >= candidates.length) {
                        reject(new Error('FaceAPI tidak bisa dimuat (lokal & CDN gagal).'));
                        return;
                    }
                    const url = candidates[idx];
                    const s = document.createElement("script");
                    s.src = url;
                    s.onload = resolve;
                    s.onerror = (event) => {
                        console.warn('Gagal memuat face-api dari', url, event);
                        s.remove();
                        tryLoad(idx + 1);
                    };
                    document.head.appendChild(s);
                };
                tryLoad();
            });
            return faceApiScriptPromise;
        }

        function getMtcnnOptions() {
            return new faceapi.MtcnnOptions(MTCNN_OPTIONS);
        }

        async function loadDetectionModels() {
            if (faceapi.nets?.mtcnn?.isLoaded && faceapi.nets?.faceLandmark68Net?.isLoaded) {
                return;
            }
            if (faceModelPromise) return faceModelPromise;
            faceModelPromise = Promise.all([
                faceapi.loadMtcnnModel(FACE_MODEL_BASE_URL),
                faceapi.loadFaceLandmarkModel(FACE_MODEL_BASE_URL),
            ]);
            return faceModelPromise;
        }

        async function loadRecognitionModel() {
            if (faceapi.nets?.faceRecognitionNet?.isLoaded) {
                return;
            }
            if (recognitionModelPromise) return recognitionModelPromise;
            recognitionModelPromise = faceapi.loadFaceRecognitionModel(FACE_MODEL_BASE_URL);
            return recognitionModelPromise;
        }

        async function getReferenceEmbedding() {
            try {
                console.log('Mengambil data referensi dari server...');
                const response = await fetch(`/admin/presence/embeddings`);

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Gagal mengambil data referensi');
                }

                const data = await response.json();
                const rawEmbeddings = Array.isArray(data.embeddings) ? data.embeddings : [];

                if (!rawEmbeddings.length) {
                    throw new Error('Data wajah belum tersedia. Pastikan wajah karyawan sudah direkam.');
                }

                referenceEmbeddings = rawEmbeddings.map((item, index) => ({
                    employeeId: item.employee_id,
                    employeeName: item.employee_name,
                    employeeNik: item.employee_nik,
                    orientation: item.orientation || 'front',
                    descriptor: normalizeDescriptor(item.descriptor, index),
                }));

                console.log(`Data referensi berhasil dimuat (${referenceEmbeddings.length} orientasi)`);

            } catch (err) {
                console.error('Error mengambil embedding:', err);
                console.error('Stack trace:', err.stack);
                throw new Error(err.message || 'Data referensi wajah tidak ditemukan.');
            }
        }

        function normalizeDescriptor(descriptorInput, index = 0) {
            let descriptorArray;

            if (typeof descriptorInput === 'string') {
                try {
                    descriptorArray = JSON.parse(descriptorInput);
                } catch (parseError) {
                    throw new Error('Format data descriptor tidak valid (JSON parse error)');
                }
            } else if (Array.isArray(descriptorInput)) {
                descriptorArray = descriptorInput;
            } else if (typeof descriptorInput === 'object' && descriptorInput !== null) {
                descriptorArray = Object.values(descriptorInput);
            } else {
                throw new Error('Format descriptor tidak dikenali');
            }

            if (!descriptorArray || !Array.isArray(descriptorArray)) {
                throw new Error('Descriptor bukan array yang valid');
            }

            if (descriptorArray.length !== 128) {
                throw new Error(
                    `Descriptor tidak valid (orientasi ke-${index + 1}, panjang: ${descriptorArray.length}, seharusnya 128)`
                );
            }

            return new Float32Array(descriptorArray);
        }

        function findBestReferenceMatch(descriptor) {
            if (!referenceEmbeddings.length) {
                return {
                    distance: Infinity,
                    orientation: null,
                    employeeId: null,
                    employeeName: null,
                    employeeNik: null,
                };
            }

            return referenceEmbeddings.reduce(
                (best, current) => {
                    if (!current.descriptor) {
                        return best;
                    }

                    const distance = faceapi.euclideanDistance(descriptor, current.descriptor);
                    if (distance < best.distance) {
                        return {
                            distance,
                            orientation: current.orientation || 'front',
                            employeeId: current.employeeId,
                            employeeName: current.employeeName,
                            employeeNik: current.employeeNik,
                        };
                    }

                    return best;
                },
                {
                    distance: Infinity,
                    orientation: null,
                    employeeId: null,
                    employeeName: null,
                    employeeNik: null,
                }
            );
        }

        function waitForElement(selector, timeout = 5000) {
            return new Promise((resolve, reject) => {
                const existing = document.querySelector(selector);
                if (existing) {
                    resolve(existing);
                    return;
                }

                let timerId;
                const observer = new MutationObserver(() => {
                    const element = document.querySelector(selector);
                    if (element) {
                        observer.disconnect();
                        if (timerId) clearTimeout(timerId);
                        resolve(element);
                    }
                });

                observer.observe(document.body || document.documentElement, {
                    childList: true,
                    subtree: true
                });

                if (timeout) {
                    timerId = setTimeout(() => {
                        observer.disconnect();
                        reject(new Error(`Element ${selector} tidak ditemukan`));
                    }, timeout);
                }
            });
        }


        function createCameraContainer() {
            const holder = document.querySelector("[data-camera-wrapper]") || document.getElementById('main-content');
            if (!holder) {
                console.warn('Tempat untuk membuat fallback camera-capture tidak ditemukan');
                return null;
            }

            const fallback = document.createElement("div");
            fallback.className = "camera-capture text-muted";
            fallback.style.cssText =
                "position:relative;width:100%;max-width:720px;aspect-ratio:720/520;background:#000;overflow:hidden;border-radius:8px;";

            const statusText = document.createElement("span");
            statusText.textContent = "Memuat Kamera...";
            statusText.style.cssText =
                "position:absolute;inset:auto auto 8px 8px;z-index:3;color:#fff;opacity:.8;";
            fallback.appendChild(statusText);

            if (typeof holder.prepend === "function") {
                holder.prepend(fallback);
            } else {
                holder.insertBefore(fallback, holder.firstChild || null);
            }

            console.warn('Membuat fallback elemen .camera-capture secara dinamis.');
            return fallback;
        }

        function informCriticalCameraError(message) {
            if (window.Swal && typeof Swal.fire === "function") {
                return Swal.fire({
                    icon: "error",
                    title: "Kamera Error",
                    text: message,
                    allowOutsideClick: false,
                });
            }
            alert(message);
            return Promise.resolve();
        }

        async function startCamera() {
            let wrap = document.querySelector(".camera-capture");
            if (!wrap) {
                console.warn('Container .camera-capture belum siap, menunggu DOM update...');
                try {
                    wrap = await waitForElement(".camera-capture", 5000);
                } catch (err) {
                    console.error('Element .camera-capture tidak ditemukan setelah penantian', err);
                }
            }

            if (!wrap) {
                wrap = createCameraContainer();
            }

            if (!wrap) {
                await informCriticalCameraError(
                'Komponen kamera tidak ditemukan di halaman. Muat ulang aplikasi Anda.');
                return;
            }
            wrap.querySelector("span")?.remove(); // Hapus teks "Memuat Kamera..."

            Webcam.set({
                width: 640,
                height: 480,
                image_format: "jpeg",
                jpeg_quality: 90,
                flip_horiz: true,
                constraints: {
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: "user"
                    },
                    audio: false
                }
            });

            Webcam.on("live", () => {
                console.log('Kamera live');
                // Swal.close(); // Jangan close dulu, tunggu model siap di init()
                const video = wrap.querySelector("video");
                if (!video) {
                    console.error('Element video tidak ditemukan');
                    return;
                }

                const setupAndRun = async () => {
                    try {
                        await loadDetectionModels(); // pastikan model deteksi terunduh sebelum deteksi
                    } catch (e) {
                        console.error('Gagal memuat model face-api:', e);
                        Swal.fire('Error', 'Model deteksi wajah gagal dimuat. Muat ulang halaman.', 'error');
                        return;
                    }

                    forceVideoFill(video);
                    const overlay = ensureOverlay(wrap);
                    const sync = () => syncCanvasToBox(overlay, wrap);
                    sync();
                    new ResizeObserver(sync).observe(wrap);
                    window.addEventListener('resize', sync);
                    window.addEventListener('orientationchange', sync);

                    tryInitAttendanceButton(video);
                    const runWithReady = () => runDetect(video, overlay, wrap);
                    if (video.readyState >= 2) {
                        runWithReady();
                    } else {
                        video.addEventListener("loadedmetadata", runWithReady, { once: true });
                    }
                };

                setupAndRun();
            });

            Webcam.on("error", (err) => {
                console.error('Webcam error:', err);
                Swal.fire('Kamera Error', `Gagal mengakses kamera: ${err.message}. Harap izinkan akses kamera.`,
                    'error');
            });

            Webcam.attach(wrap);
        }

        function runDetect(video, canvas, box) {
            const ctx = canvas.getContext('2d');
            const opts = getMtcnnOptions();
            const dpr = window.devicePixelRatio || 1;

            async function loop() {
                if (video.readyState < 2 || isVerifying || recentSuccess) {
                    requestAnimationFrame(loop);
                    return;
                }
                const now = performance.now();
                if (now - lastDetectAt < DETECTION_INTERVAL_MS) {
                    requestAnimationFrame(loop);
                    return;
                }
                lastDetectAt = now;
                if (!faceapi?.nets?.mtcnn?.isLoaded || !faceapi?.nets?.faceLandmark68Net?.isLoaded) {
                    // Model belum siap, tunggu frame berikutnya
                    requestAnimationFrame(loop);
                    return;
                }

                const drawSize = (() => {
                    const rect = video.getBoundingClientRect();
                    const w = Math.round(rect.width || video.clientWidth || box.clientWidth);
                    const h = Math.round(rect.height || video.clientHeight || box.clientHeight);
                    return { width: w, height: h };
                })();

                try {
                    // Pastikan kanvas tetap mengikuti ukuran container (tampilan responsif)
                    const expectedW = Math.round(box.clientWidth);
                    const expectedH = Math.round(box.clientHeight);
                    const currentW = Math.round(canvas.style.width ? parseFloat(canvas.style.width) : 0);
                    const currentH = Math.round(canvas.style.height ? parseFloat(canvas.style.height) : 0);
                    if (expectedW !== currentW || expectedH !== currentH) {
                        syncCanvasToBox(canvas, box);
                    }

                    const detections = await faceapi
                        .detectAllFaces(video, opts)
                        .withFaceLandmarks();

                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    const overlayMirrored = VIDEO_MIRRORED === true; // samakan orientasi overlay dengan tampilan video yang sudah mirror

                    detections.forEach((result) => {
                        const box = projectDetectionBox(
                            result.detection.box,
                            drawSize.width,
                            drawSize.height,
                            video.videoWidth,
                            video.videoHeight,
                            overlayMirrored
                        );
                        if (!box) return;
                        const drawX = box.x;
                        ctx.beginPath();
                        ctx.lineWidth = 2;
                        ctx.strokeStyle = '#38bdf8';
                        ctx.rect(drawX, box.y, box.width, box.height);
                        ctx.stroke();
                    });

                    if (detections.length === 1) {
                        setDetectionState('ready');
                        updateStatusIndicator('Wajah Terdeteksi', 'Tekan tombol presensi untuk verifikasi.', 'primary');
                    } else if (detections.length > 1) {
                        setDetectionState('multiple');
                        updateStatusIndicator('Terlalu Banyak Wajah', 'Pastikan hanya satu wajah di kamera.', 'warning');
                    } else {
                        setDetectionState('no_face');
                        updateStatusIndicator('Menunggu Wajah...', 'Posisikan wajah Anda di dalam bingkai.', 'secondary');
                    }
                    if (detections.length !== 1) {
                        setActiveEmployee(null);
                    }

                } catch (detectError) {
                    console.error('❌ Error saat deteksi wajah:', detectError);
                }

                requestAnimationFrame(loop);
            }
            loop();
        }

        async function performVerification(video, csrfToken) {
            if (isVerifying) return;
            isVerifying = true;

            await loadRecognitionModel();
            const mtcnnOptions = getMtcnnOptions();

            try {
                const collectedDescriptors = [];
                let attempt = 0;
                
                while (collectedDescriptors.length < VERIFICATION_SAMPLES && attempt < VERIFICATION_SAMPLES + 2) {
                    const detection = await faceapi.detectSingleFace(video, mtcnnOptions)
                        .withFaceLandmarks()
                        .withFaceDescriptor();
                    if (detection) {
                        collectedDescriptors.push(detection.descriptor);
                    }
                    attempt++;
                    await new Promise(resolve => setTimeout(resolve, FRAME_DELAY_MS));
                }

                if (!collectedDescriptors.length) {
                    updateStatusIndicator('Gagal Verifikasi', 'Wajah tidak stabil. Coba lagi.', 'danger');
                    isVerifying = false;
                    return;
                }

                const distanceResults = collectedDescriptors.map(descriptor => findBestReferenceMatch(descriptor));
                const distances = distanceResults.map(result => result.distance);
                const avgDistance = distances.reduce((sum, d) => sum + d, 0) / distances.length;
                const matchLimit = getFaceMatchLimit();
                const matchCount = distances.filter(d => d < matchLimit).length;

                if (matchCount >= REQUIRED_MATCHES && avgDistance < matchLimit) {
                    updateStatusIndicator('Verifikasi Berhasil!', 'Menampilkan hasil verifikasi...', 'success', true);
                    await showVerificationResult(null, {
                        threshold: faceVerificationThreshold,
                        matchLimit,
                        distance: avgDistance,
                        matchCount,
                        sampleCount: collectedDescriptors.length,
                    });
                } else {
                    updateStatusIndicator('Bukan Pemilik Akun', 'Wajah tidak cocok.', 'danger');
                    await new Promise(r => setTimeout(r, 2000)); // Delay agar user bisa baca pesan error
                    isVerifying = false;
                }

            } catch (err) {
                console.error('Verification error:', err);
                updateStatusIndicator('Error', 'Terjadi kesalahan verifikasi.', 'danger');
                isVerifying = false;
            }
        }

        function updateStatusIndicator(title, message, type = 'secondary', loading = false) {
            const titleEl = document.getElementById('status-title');
            const msgEl = document.getElementById('status-message');
            const spinner = document.getElementById('loading-spinner');
            const container = document.getElementById('status-indicator');

            // Fallback: jika kartu status dihilangkan, tampilkan info lewat tombol
            if (!titleEl && !msgEl && !spinner && !container) {
                const { button, text } = getAttendanceButtonElements();
                if (text && title) text.textContent = title;
                if (button && (loading || type === 'danger' || type === 'warning')) {
                    button.disabled = true;
                }
                return;
            }

            if (titleEl) titleEl.textContent = title;
            if (msgEl) msgEl.textContent = message;
            
            if (spinner) {
                if (loading) spinner.classList.remove('d-none');
                else spinner.classList.add('d-none');
            }

            if (container) {
                container.className = `p-3 rounded-3 shadow-sm border bg-white`;
                // Bisa tambahkan border color based on type jika mau
                if (type === 'danger') container.classList.add('border-danger');
                else if (type === 'success') container.classList.add('border-success');
                else if (type === 'warning') container.classList.add('border-warning');
                else container.classList.remove('border-danger', 'border-success', 'border-warning');
            }
        }

        function formatVerificationSummary(details) {
            if (!details) return '';
            const formatNumber = (val, digits = 4) => Number.isFinite(val) ? val.toFixed(digits) : '-';
            const thresholdText = Number.isFinite(details.threshold) ? details.threshold.toFixed(2) : '-';
            const limitText = Number.isFinite(details.matchLimit) ? details.matchLimit.toFixed(2) : '-';
            const distanceText = formatNumber(details.distance, 4);
            const matchCount = Number.isFinite(details.matchCount) ? details.matchCount : 0;
            const sampleCount = Number.isFinite(details.sampleCount) ? details.sampleCount : '-';
            return `
                <div class="mt-2 pt-2 border-top">
                    <div class="fw-semibold mb-1">Ringkasan Verifikasi</div>
                    <p class="mb-1"><strong>Threshold:</strong> ${thresholdText} (limit ${limitText})</p>
                    <p class="mb-1"><strong>Jarak Verifikasi:</strong> ${distanceText}</p>
                    <p class="mb-0"><strong>Cocok:</strong> ${matchCount} dari ${sampleCount}</p>
                </div>
            `;
        }

        function updateStatusIndicatorForActionMode(mode) {
             if (mode === 'on_leave') {
                 const leaveType = presenceState.leaveInfo?.leaveType;
                 const leaveText = leaveType ? (LEAVE_LABELS[leaveType] || leaveType) : 'Izin';
                 updateStatusIndicator(`Sedang ${leaveText}`, 'Anda tidak perlu presensi hari ini.', 'info');
             } else if (mode === 'done') {
                 updateStatusIndicator('Selesai', `Presensi hari ini sudah selesai (${presenceState.lastClockOut || '-'}).`, 'success');
             } else if (mode === 'waiting') {
                 updateStatusIndicator('Menunggu Jam Pulang', 'Belum waktunya presensi pulang.', 'warning');
             }
        }

        function updateStatusIndicatorForLocation() {
            if (!EMPLOYEE_LOCATION) {
                updateStatusIndicator('Lokasi Belum Diatur', 'Hubungi admin.', 'danger');
            } else if (!locationValidation.ready) {
                updateStatusIndicator('Mencari Lokasi...', 'Menunggu koordinat GPS...', 'secondary', true);
            } else if (!locationValidation.isInsideRadius) {
                updateStatusIndicator('Di Luar Radius', 'Anda berada di luar jangkauan kantor.', 'danger');
            }
        }

        function setupAttendanceButton(buttonId, videoTarget) {
            const button = document.getElementById(buttonId);
            const videoSourceInfo = typeof videoTarget === 'string' ? videoTarget : '[HTMLElement]';
            const video = typeof videoTarget === 'string' ? document.querySelector(videoTarget) : videoTarget;
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

            // Validasi elemen-elemen penting
            if (!button) {
                console.error("❌ Tombol tidak ditemukan:", buttonId);
                return;
            }
            if (!video) {
                console.error("❌ Video tidak ditemukan:", videoSourceInfo);
                button.disabled = true;
                const buttonText = document.getElementById('button-text');
                if (buttonText) buttonText.innerText = "Error: Video Tidak Ditemukan";
                return;
            }
            if (!csrfTokenMeta) {
                console.error("❌ CSRF token tidak ditemukan");
                button.disabled = true;
                const buttonText = document.getElementById('button-text');
                if (buttonText) buttonText.innerText = "Error: CSRF Token Hilang";
                return;
            }
            if (!referenceEmbeddings.length) {
                console.error("Data referensi belum dimuat");
                button.disabled = true;
                const buttonText = document.getElementById('button-text');
                if (buttonText) buttonText.innerText = "Error: Data Referensi Hilang";
                return;
            }

            const csrfToken = csrfTokenMeta.getAttribute('content');

            console.log('Setup tombol absensi berhasil untuk admin presence');
            applyButtonIdleState();

            button.addEventListener('click', async () => {
                const actionMode = activeEmployeeId ? getCurrentActionMode() : null;
                if (actionMode === 'on_leave') {
                    Swal.fire('Sedang Izin', 'Anda tidak perlu presensi karena izin sudah disetujui.', 'info');
                    applyButtonIdleState();
                    return;
                }
                if (actionMode === 'done') {
                    Swal.fire('Informasi', 'Anda sudah menyelesaikan presensi hari ini.', 'info');
                    applyButtonIdleState();
                    return;
                }

                if (actionMode === 'waiting') {
                    Swal.fire('Belum Waktu Pulang', 'Presensi pulang dapat dilakukan setelah jam pulang shift Anda.', 'info');
                    applyButtonIdleState();
                    return;
                }

                if (!EMPLOYEE_LOCATION) {
                    ensureLocationConfigured();
                    resetButton();
                    return;
                }

                if (!locationValidation.ready) {
                    Swal.fire('Menunggu Lokasi', 'Sistem sedang membaca koordinat perangkat Anda.', 'info');
                    resetButton();
                    return;
                }

                if (geoState.latitude === null || geoState.longitude === null) {
                    Swal.fire('Lokasi Tidak Tersedia', 'Koordinat perangkat belum tersedia.', 'info');
                    resetButton();
                    return;
                }

                if (isVerifying) {
                    console.log('Sedang memverifikasi, abaikan klik ganda');
                    return;
                }

                isVerifying = true; // Kunci proses
                button.disabled = true;
                setButtonLoadingState('Memverifikasi...');

                console.log('Memulai proses verifikasi...');

                Swal.fire({
                    title: 'Mencoba melakukan verifikasi...',
                    text: 'Harap tetap di posisi yang sama sementara sistem memverifikasi wajah Anda.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                    showConfirmButton: false,
                });

                const mtcnnOptions = getMtcnnOptions();

                try {
                    await loadRecognitionModel();
                    const collectedDescriptors = [];
                    let attempt = 0;
                    console.log('📸 Mengambil beberapa sampel wajah untuk verifikasi...');
                    while (collectedDescriptors.length < VERIFICATION_SAMPLES && attempt <
                        VERIFICATION_SAMPLES + 2) {
                        const detection = await faceapi.detectSingleFace(video, mtcnnOptions)
                            .withFaceLandmarks()
                            .withFaceDescriptor();
                        if (detection) {
                            collectedDescriptors.push(detection.descriptor);
                            console.log(`✅ Sampel ${collectedDescriptors.length} berhasil diambil.`);
                        } else {
                            console.log('⚠️ Sampel wajah tidak terbaca, mencoba lagi...');
                        }
                        attempt++;
                        await new Promise(resolve => setTimeout(resolve, FRAME_DELAY_MS));
                    }

                    if (!collectedDescriptors.length) {
                        Swal.fire('Verifikasi Gagal',
                            'Wajah tidak terdeteksi dengan stabil. Posisikan wajah lurus dan coba lagi.',
                            'error');
                        resetButton();
                        return;
                    }
                    const matchResults = collectedDescriptors.map(descriptor => findBestReferenceMatch(descriptor));
                    const matchLimit = getFaceMatchLimit();
                    const summaryBase = {
                        threshold: faceVerificationThreshold,
                        matchLimit,
                        sampleCount: collectedDescriptors.length,
                    };
                    const allDistances = matchResults
                        .map(result => result.distance)
                        .filter(distance => Number.isFinite(distance));
                    const avgDistance = allDistances.length
                        ? allDistances.reduce((sum, d) => sum + d, 0) / allDistances.length
                        : null;
                    const fallbackSummary = {
                        ...summaryBase,
                        distance: avgDistance,
                        matchCount: allDistances.filter(d => d < matchLimit).length,
                    };
                    const validMatches = matchResults.filter(result => result.employeeId && result.distance < matchLimit);

                    if (!validMatches.length) {
                        const summaryHtml = formatVerificationSummary(fallbackSummary);
                        Swal.fire({
                            icon: 'error',
                            title: 'Wajah Tidak Dikenali',
                            html: `<div class="text-start"><p class="mb-2">Pastikan wajah sudah terdaftar dan terlihat jelas.</p>${summaryHtml}</div>`,
                            allowOutsideClick: false,
                            showConfirmButton: true,
                            confirmButtonText: 'Ok',
                        });
                        resetButton();
                        return;
                    }

                    const groupedMatches = {};
                    validMatches.forEach((match) => {
                        if (!groupedMatches[match.employeeId]) {
                            groupedMatches[match.employeeId] = {
                                employeeId: match.employeeId,
                                sample: match,
                                distances: [],
                            };
                        }
                        groupedMatches[match.employeeId].distances.push(match.distance);
                    });

                    const rankedMatches = Object.values(groupedMatches)
                        .map((group) => {
                            const avg = group.distances.reduce((sum, d) => sum + d, 0) / group.distances.length;
                            return {
                                ...group,
                                avgDistance: avg,
                                matchCount: group.distances.length,
                                minDistance: Math.min(...group.distances),
                            };
                        })
                        .sort((a, b) => b.matchCount - a.matchCount || a.avgDistance - b.avgDistance);

                    const bestMatch = rankedMatches[0];
                    if (!bestMatch) {
                        Swal.fire('Verifikasi Gagal', 'Wajah tidak dapat diverifikasi.', 'error');
                        resetButton();
                        return;
                    }

                    console.log(`Sample distances: ${bestMatch.distances.map(d => d.toFixed(4)).join(', ')}`);
                    console.log(
                        `Min: ${bestMatch.minDistance.toFixed(4)}, Avg: ${bestMatch.avgDistance.toFixed(4)}, Matches: ${bestMatch.matchCount}`
                    );

                    const bestMatchSummary = {
                        ...summaryBase,
                        distance: bestMatch.avgDistance,
                        matchCount: bestMatch.matchCount,
                    };

                    if (bestMatch.matchCount >= REQUIRED_MATCHES && bestMatch.avgDistance < matchLimit) {
                        console.log('Verification success. Face matched.');
                        const verifiedSample = bestMatch.sample;
                        if (verifiedSample && verifiedSample.employeeId && activeEmployeeId !== verifiedSample.employeeId) {
                            setActiveEmployee(verifiedSample);
                        }
                        Swal.update({
                            title: 'Verifikasi Berhasil!',
                            text: 'Menampilkan hasil verifikasi...'
                        });

                        await showVerificationResult(verifiedSample, bestMatchSummary);

                    } else {
                        console.log(
                            `Verification failed. Avg distance too high: ${bestMatch.avgDistance.toFixed(4)}`
                        );
                        const summaryHtml = formatVerificationSummary(bestMatchSummary);
                        Swal.fire({
                            icon: 'error',
                            title: 'Wajah Tidak Dikenali',
                            html: `<div class="text-start"><p class="mb-2">Wajah yang terdeteksi tidak cocok dengan data karyawan.</p>${summaryHtml}</div>`,
                            allowOutsideClick: false,
                            showConfirmButton: true,
                            confirmButtonText: 'Ok',
                        });
                        resetButton();
                    }
                } catch (err) {
                    console.error('❌ Error saat deteksi:', err);
                    console.error('❌ Stack trace:', err.stack);
                    Swal.fire('Error', `Terjadi error saat verifikasi: ${err.message}`, 'error');
                    resetButton();
                }
            });
        }


        async function showVerificationResult(matchInfo = null, verificationSummary = null) {
            const distanceValue = verificationSummary?.distance;
            const distanceText = Number.isFinite(distanceValue) ? distanceValue.toFixed(4) : '-';
            const employeeName = matchInfo?.employeeName || matchInfo?.employeeNik || activeEmployee?.name || activeEmployee?.nik || null;
            const modalDetails = [];

            if (employeeName) {
                modalDetails.push(`<p class="mb-1"><strong>Karyawan:</strong> ${employeeName}</p>`);
            }
            modalDetails.push(`<p class="mb-0"><strong>Jarak Verifikasi:</strong> ${distanceText}</p>`);

            Swal.fire({
                icon: 'success',
                title: 'Verifikasi Berhasil!',
                html: `<div class="text-start">${modalDetails.join('')}</div>`,
                allowOutsideClick: false,
                showConfirmButton: true,
                confirmButtonText: 'Ok',
                customClass: {
                    popup: 'swal-presence-popup',
                    confirmButton: 'swal-presence-confirm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    recentSuccess = false;
                    applyButtonIdleState();
                }
            });

            updateStatusIndicator('Verifikasi Berhasil', `Jarak verifikasi: ${distanceText}`, 'success');
            recentSuccess = true;
            setTimeout(() => { recentSuccess = false; }, 15000);
            isVerifying = false;
            applyButtonIdleState();
        }



        async function monitorPresenceStatus(options = {}) {
            const { showReminders = true, employeeId = activeEmployeeId } = options;
            if (!employeeId) {
                return;
            }
            try {
                const response = await fetch(`/admin/presence/status?employee_id=${employeeId}`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-cache'
                });
                if (!response.ok) {
                    throw new Error('Gagal memuat status presensi');
                }
                const data = await response.json();
                updatePresenceUI(data, showReminders);
            } catch (error) {
                console.warn('Status presensi tidak tersedia:', error);
            }
        }

        function updatePresenceUI(data, showReminders = true) {
            if (!data) return;
            if (data.employee && activeEmployeeId === data.employee.id) {
                activeEmployee = {
                    id: data.employee.id,
                    name: data.employee.nama || data.employee.nik || null,
                    nik: data.employee.nik || null,
                };
                updateRecognizedEmployeeInfo();
            }

            presenceState.hasCheckedIn = Boolean(data.presence && data.presence.has_checked_in);
            presenceState.hasCheckedOut = Boolean(data.presence && data.presence.has_checked_out);
            presenceState.canCheckOut = Boolean(data.presence && data.presence.can_check_out);
            presenceState.lastClockIn = data.presence?.waktu_masuk || null;
            presenceState.lastClockOut = data.presence?.waktu_pulang || null;
            presenceState.isOnLeave = Boolean(data.presence && data.presence.is_on_leave);
            presenceState.leaveInfo = presenceState.isOnLeave ? {
                leaveType: data.presence?.leave_type || null,
                leaveStart: data.presence?.leave_start || null,
                leaveEnd: data.presence?.leave_end || null,
            } : null;

            if (presenceState.isOnLeave) {
                presenceState.hasCheckedIn = false;
                presenceState.hasCheckedOut = false;
                presenceState.canCheckOut = false;
                hasShownCheckInReminder = true;
                hasShownCheckoutReminder = true;
            }

            if (showReminders) {
                if (data.reminders?.should_check_in && !hasShownCheckInReminder) {
                    hasShownCheckInReminder = true;
                    showReminderModal('checkin', data.shift);
                }
                if (data.reminders?.should_check_out && !hasShownCheckoutReminder) {
                    hasShownCheckoutReminder = true;
                    showReminderModal('checkout', data.shift);
                }
            }

            applyButtonIdleState();
        }

        function showReminderModal(type, shiftInfo = {}) {
            const isCheckIn = type === 'checkin';
            const title = isCheckIn ? 'Segera Lakukan Presensi Masuk' : 'Presensi Hari Ini Belum Ditemukan';
            const description = isCheckIn
                ? 'Shift Anda telah dimulai. Segera lakukan presensi masuk agar tidak dianggap terlambat.'
                : 'Jam pulang shift telah tiba namun sistem belum menemukan presensi Anda hari ini.';

            Swal.fire({
                icon: 'info',
                title,
                html: `<div class="text-start">
                        <p>${description}</p>
                        <p><strong>Shift:</strong> ${shiftInfo?.nama_shift || '-'}</p>
                        <p><strong>Jam Masuk:</strong> ${shiftInfo?.jam_masuk || '-'}</p>
                        <p><strong>Jam Pulang:</strong> ${shiftInfo?.jam_pulang || '-'}</p>
                    </div>`,
                confirmButtonText: 'Mengerti'
            });
        }

        function getCurrentActionMode() {
            if (!activeEmployeeId) {
                return 'idle';
            }
            if (presenceState.isOnLeave) {
                return 'on_leave';
            }
            if (presenceState.canCheckOut) {
                return 'check_out';
            }
            if (presenceState.hasCheckedOut) {
                return 'done';
            }
            if (!presenceState.hasCheckedIn) {
                return 'check_in';
            }
            return 'waiting';
        }

        function tryInitAttendanceButton(videoEl) {
            if (attendanceButtonInitialized) return;
            if (!videoEl) return;
            if (!referenceEmbeddings.length) {
                setTimeout(() => tryInitAttendanceButton(videoEl), 300);
                return;
            }
            setupAttendanceButton('attendance-action-btn', videoEl);
            attendanceButtonInitialized = true;
            applyButtonIdleState();
        }



        function forceVideoFill(video) {
            video.setAttribute("playsinline", "");
            video.style.setProperty("position", "absolute", "important");
            video.style.setProperty("top", "0", "important");
            video.style.setProperty("left", "0", "important");
            video.style.setProperty("width", "100%", "important");
            video.style.setProperty("height", "100%", "important");
            video.style.setProperty("object-fit", "cover", "important");
            video.style.setProperty("background", "#000", "important");
        }

        function ensureOverlay(wrap) {
            let c = wrap.querySelector("#overlay");
            if (!c) {
                c = document.createElement("canvas");
                c.id = "overlay";
                wrap.appendChild(c);
            }
            Object.assign(c.style, {
                position: "absolute",
                top: 0,
                left: 0,
                width: "100%",
                height: "100%",
                pointerEvents: "none",
                zIndex: 2
            });
            return c;
        }

        function syncCanvasToBox(canvas, box) {
            const dpr = window.devicePixelRatio || 1;
            const w = Math.round(box.clientWidth);
            const h = Math.round(box.clientHeight);
            canvas.style.width = w + "px";
            canvas.style.height = h + "px";
            canvas.width = Math.max(1, w * dpr);
            canvas.height = Math.max(1, h * dpr);
            const ctx = canvas.getContext("2d");
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        function projectDetectionBox(detBox, containerW, containerH, videoW, videoH, mirrored = false) {
            if (!detBox || !containerW || !containerH || !videoW || !videoH) return null;

            // object-fit: cover mapping agar overlay sejajar di mobile maupun desktop
            const scale = Math.max(containerW / videoW, containerH / videoH);
            const displayW = videoW * scale;
            const displayH = videoH * scale;
            const offsetX = (displayW - containerW) / 2;
            const offsetY = (displayH - containerH) / 2;

            const xRaw = detBox.x * scale - offsetX;
            const yRaw = detBox.y * scale - offsetY;
            const width = detBox.width * scale;
            const height = detBox.height * scale;

            let x = xRaw;
            if (mirrored) {
                x = containerW - (xRaw + width);
            }

            // clamp supaya box tidak keluar area canvas
            return {
                x: Math.min(Math.max(0, x), containerW - width),
                y: Math.min(Math.max(0, yRaw), containerH - height),
                width,
                height
            };
        }
    </script>


@endsection






