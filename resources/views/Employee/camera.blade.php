@extends('layout.employee')
@section('title', 'Camera')
@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">
            <a href="javascript:;" class="headerButton goBack text-light" onclick="confirmExit()">
                <!-- Font Awesome back icon -->
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">
            Attendance Employee
        </div>
        <div class="right" style="width: 24px;">

        </div>
    </div>
@endsection
@section('content')
    <div class="p-4 camera-page">
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
            
            <div class="w-100 mb-3 text-center">
                <div id="status-indicator" class="p-3 rounded-3 shadow-sm bg-white border">
                    <div class="spinner-border text-primary mb-2 d-none" role="status" id="loading-spinner"></div>
                    <h5 id="status-title" class="mb-1 fw-bold text-dark">Menunggu Wajah...</h5>
                    <p id="status-message" class="mb-0 text-muted small">Posisikan wajah Anda di dalam bingkai.</p>
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



        .camera-page {
            padding-bottom: 90px;
        }
    </style>


@endsection
@section('script')
    <script>
        // --- Variabel Global ---
        const EMPLOYEE_LOCATION = @json($employeeLocationPayload);
        const GEOLOCATION_OPTIONS = {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 20000
        };
        const FACE_VERIFICATION_THRESHOLD = 0.38; // Threshold Euclidean Distance
        const FACE_VERIFICATION_MARGIN = 0.03; // toleransi rata-rata
        const VERIFICATION_SAMPLES = 5;
        const REQUIRED_MATCHES = 4;
        const FRAME_DELAY_MS = 120;
        const VIDEO_MIRRORED = true;
        const STATUS_POLL_INTERVAL_MS = 60000;
        let referenceEmbeddings = []; // Diisi saat init() dari API (multi-orientasi)
        let isVerifying = false;
        let recentSuccess = false;
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
        const geoState = {
            latitude: null,
            longitude: null,
            accuracy: null,
        };
        const locationValidation = {
            ready: false,
            isInsideRadius: false,
            distanceMeters: null,
        };
        // Map variables removed
        let locationNotConfiguredModalShown = false;
        let outsideRadiusModalShown = false;
        let missingLocationModalShown = false;
        let locationAlertDismissed = false;
        let locationAlertLastType = null;
        let locationAlertLastMessage = null;

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
                        window.location.href = '{{ route('employee.index') }}';
                    }
                });
            } else {
                if (confirm('Keluar dari halaman presensi?')) {
                    window.location.href = '{{ route('employee.index') }}';
                }
            }
        }

        function initializeGeolocation() {
            const isSecure = window.isSecureContext || ['localhost', '127.0.0.1'].includes(location.hostname);
            if (!isSecure) {
                const insecureMessage =
                    'Browser memblokir geolokasi karena halaman tidak diakses via HTTPS/localhost. Buka lewat https atau localhost agar izin lokasi muncul.';
                updateLocationAlert(insecureMessage, 'warning');
                return;
            }

            if (!navigator.geolocation) {
                updateLocationAlert('Perangkat Anda tidak mendukung geolocation.', 'danger');
                return;
            }

            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, GEOLOCATION_OPTIONS);
            navigator.geolocation.watchPosition(successCallback, errorCallback, GEOLOCATION_OPTIONS);
        }

        function successCallback(position) {
            geoState.latitude = position.coords.latitude;
            geoState.longitude = position.coords.longitude;
            geoState.accuracy = position.coords.accuracy ?? null;

            if (lokasiInput) {
                lokasiInput.value = `${geoState.latitude},${geoState.longitude}`;
            }

            missingLocationModalShown = false;

            validateEmployeeLocation();
            updateLocationAlert();
        }

        function errorCallback(error) {
            console.warn('Tidak dapat membaca lokasi pengguna:', error);
            geoState.latitude = null;
            geoState.longitude = null;
            geoState.accuracy = null;

            if (lokasiInput) {
                lokasiInput.value = '';
            }

            locationValidation.ready = false;
            locationValidation.isInsideRadius = false;
            locationValidation.distanceMeters = null;

            let message = 'Berikan izin lokasi agar presensi dapat diverifikasi.';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = "Perizinan lokasi ditolak. Izin diperlukan untuk presensi.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = "Informasi lokasi tidak tersedia.";
                    break;
                case error.TIMEOUT:
                    message = "Permintaan lokasi melebihi batas waktu.";
                    break;
                default:
                    message = "Terjadi kesalahan saat membaca lokasi perangkat.";
                    break;
            }

            updateLocationAlert(message, 'danger');
            showLocationRequirementModal(message);
            updateStatusIndicator('Lokasi Error', message, 'danger');
        }

 

        document.addEventListener("DOMContentLoaded", () => {
            lokasiInput = document.getElementById('location');
            locationAlertElement = document.getElementById('locationAlertWrapper');
            locationAlertTextElement = document.getElementById('locationValidationStatusText');

            if (!EMPLOYEE_LOCATION) {
                ensureLocationConfigured();
            } else {
                updateLocationAlert();
            }

            initializeGeolocation();

            monitorPresenceStatus({ showReminders: true });
            if (!presenceStatusInterval) {
                presenceStatusInterval = setInterval(() => {
                    monitorPresenceStatus({
                        showReminders: true
                    });
                }, STATUS_POLL_INTERVAL_MS);
            }

            init();
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

            // Notifikasi radius hanya melalui modal; sembunyikan alert jika lokasi sudah terbaca.
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

        async function init() {
            await ensureFaceAPI(); // 1. Muat library Face-API

            Swal.fire({
                title: 'Memuat Kamera',
                text: 'Menyiapkan model verifikasi dan mengakses kamera Anda...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {

                await loadFaceModels();
                await getReferenceEmbedding();


                Swal.update({
                    text: 'Mengakses kamera...'
                });
                await startCamera();
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
            return new Promise((ok, err) => {
                const s = document.createElement("script");
                s.src = "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js";
                s.onload = ok;
                s.onerror = err;
                document.head.appendChild(s);
            });
        }

        async function loadFaceModels() {
            const MODEL_URL = '/models';
            await faceapi.nets.mtcnn.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        }

        async function getReferenceEmbedding() {
            try {
                console.log('Mengambil data referensi dari server...');
                const response = await fetch(`/api/employee/embedding`);

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Gagal mengambil data referensi');
                }

                const data = await response.json();
                const rawEmbeddings = Array.isArray(data.embeddings) ? data.embeddings : [];

                if (!rawEmbeddings.length && data.descriptor) {
                    rawEmbeddings.push({
                        orientation: data.orientation || 'front',
                        descriptor: data.descriptor,
                    });
                }

                if (!rawEmbeddings.length) {
                    throw new Error('Data embedding wajah belum tersedia. Hubungi admin.');
                }

                referenceEmbeddings = rawEmbeddings.map((item, index) => ({
                    orientation: item.orientation || 'front',
                    descriptor: normalizeDescriptor(item.descriptor, index),
                }));

                console.log(`Data referensi berhasil dimuat (${referenceEmbeddings.length} orientasi)`);

            } catch (err) {
                console.error('Error mengambil embedding:', err);
                console.error('Stack trace:', err.stack);
                throw new Error(err.message || 'Data referensi wajah Anda tidak ditemukan.');
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

        function findBestReferenceDistance(descriptor) {
            if (!referenceEmbeddings.length) {
                return { distance: Infinity, orientation: null };
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
                        };
                    }

                    return best;
                },
                { distance: Infinity, orientation: null }
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
                Swal.close();
                const video = wrap.querySelector("video");
                if (!video) {
                    console.error('Element video tidak ditemukan');
                    return;
                }

                forceVideoFill(video);
                const overlay = ensureOverlay(wrap);
                const sync = () => syncCanvasToBox(overlay, wrap);
                sync();
                new ResizeObserver(sync).observe(wrap);

                const displayName =
                    @json(optional(optional($user)->employee)->nama ?? (optional(optional($user)->employee)->nik ?? 'Tidak dikenali'));
                if (video.readyState >= 2) {
                    runDetect(video, overlay, wrap, displayName);
                } else {
                    video.addEventListener("loadedmetadata", () => runDetect(video, overlay, wrap,
                    displayName), {
                        once: true
                    });
                }
            });

            Webcam.on("error", (err) => {
                console.error('Webcam error:', err);
                Swal.fire('Kamera Error', `Gagal mengakses kamera: ${err.message}. Harap izinkan akses kamera.`,
                    'error');
            });

            Webcam.attach(wrap);
        }

        function runDetect(video, canvas, box, displayName) {
            const ctx = canvas.getContext('2d');
            const opts = new faceapi.MtcnnOptions({
                minFaceSize: 100
            });
            const dpr = window.devicePixelRatio || 1;
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;

            async function loop() {
                if (video.readyState < 2 || isVerifying || !referenceEmbeddings.length || recentSuccess) {
                    requestAnimationFrame(loop);
                    return;
                }

                // Cek status presensi sebelum melakukan deteksi
                const actionMode = getCurrentActionMode();
                if (actionMode === 'on_leave' || actionMode === 'done' || actionMode === 'waiting') {
                     updateStatusIndicatorForActionMode(actionMode);
                     requestAnimationFrame(loop);
                     return;
                }

                // Cek lokasi
                if (!EMPLOYEE_LOCATION || !locationValidation.ready || !locationValidation.isInsideRadius) {
                     updateStatusIndicatorForLocation();
                     requestAnimationFrame(loop);
                     return;
                }

                const drawSize = {
                    width: box.clientWidth,
                    height: box.clientHeight
                };

                try {
                    const detections = await faceapi
                        .detectAllFaces(video, opts)
                        .withFaceLandmarks()
                        .withFaceDescriptors();
                    const resizedResults = faceapi.resizeResults(detections, drawSize);

                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '12px "Inter", sans-serif';
                    let recognizedFaces = 0;
                    
                    resizedResults.forEach((result) => {
                        const box = result.detection.box;
                        const drawX = box.x;
                        ctx.beginPath();
                        ctx.lineWidth = 2;
                        ctx.strokeStyle = '#38bdf8';
                        ctx.rect(drawX, box.y, box.width, box.height);
                        ctx.stroke();

                        let label = 'Tidak dikenali';
                        let isMatch = false;
                        if (result.descriptor && referenceEmbeddings.length) {
                            const bestMatch = findBestReferenceDistance(result.descriptor);
                            if (bestMatch.distance < FACE_VERIFICATION_THRESHOLD) {
                                label = displayName;
                                recognizedFaces++;
                                isMatch = true;
                            }
                        }

                        const padding = 4;
                        const textWidth = ctx.measureText(label).width;
                        const textHeight = 14;
                        const rectWidth = textWidth + padding * 2;
                        const rectHeight = textHeight + padding;
                        const rectX = drawX - padding;
                        const rectY = box.y - textHeight - padding;
                        const textY = box.y - 6;

                        ctx.fillStyle = isMatch ? 'rgba(14, 165, 233, 0.8)' : 'rgba(239, 68, 68, 0.8)';
                        ctx.strokeStyle = isMatch ? '#38bdf8' : '#ef4444';

                        ctx.save();
                        if (VIDEO_MIRRORED) {
                            ctx.scale(-1, 1);
                            ctx.fillRect(-(rectX + rectWidth), rectY, rectWidth, rectHeight);
                            ctx.fillStyle = 'white';
                            ctx.fillText(label, -(rectX + rectWidth) + padding, textY);
                        } else {
                            ctx.fillRect(rectX, rectY, rectWidth, rectHeight);
                            ctx.fillStyle = 'white';
                            ctx.fillText(label, rectX + padding, textY);
                        }
                        ctx.restore();
                    });

                    // Logika Auto-Presensi
                    if (recognizedFaces === 1 && resizedResults.length === 1) {
                        updateStatusIndicator('Memverifikasi...', 'Tahan posisi wajah Anda...', 'primary', true);
                        await performVerification(video, csrfToken);
                    } else if (resizedResults.length > 1) {
                        updateStatusIndicator('Terlalu Banyak Wajah', 'Pastikan hanya satu wajah di kamera.', 'warning');
                    } else {
                        updateStatusIndicator('Menunggu Wajah...', 'Posisikan wajah Anda di dalam bingkai.', 'secondary');
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

            const mtcnnOptions = new faceapi.MtcnnOptions({ minFaceSize: 100 });

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

                const distanceResults = collectedDescriptors.map(descriptor => findBestReferenceDistance(descriptor));
                const distances = distanceResults.map(result => result.distance);
                const avgDistance = distances.reduce((sum, d) => sum + d, 0) / distances.length;
                const matchCount = distances.filter(d => d < FACE_VERIFICATION_THRESHOLD).length;

                if (matchCount >= REQUIRED_MATCHES && avgDistance < FACE_VERIFICATION_THRESHOLD + FACE_VERIFICATION_MARGIN) {
                    updateStatusIndicator('Verifikasi Berhasil!', 'Mengirim data presensi...', 'success', true);
                    const snapshotData = takeSnapshot(video);
                    await sendPresenceRequest(csrfToken, snapshotData);
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

            const employeeId = button.getAttribute('data-user-id');
            const csrfToken = csrfTokenMeta.getAttribute('content');

            // Validasi employeeId
            if (!employeeId) {
                console.error("❌ Employee ID tidak ditemukan di tombol");
                button.disabled = true;
                const buttonText = document.getElementById('button-text');
                if (buttonText) buttonText.innerText = "Error: ID Tidak Ditemukan";
                return;
            }

            console.log('✅ Setup tombol absensi berhasil untuk employee:', employeeId);
            applyButtonIdleState();

            button.addEventListener('click', async () => {
                const actionMode = getCurrentActionMode();
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
                    Swal.fire('Menunggu Lokasi', 'Sistem masih membaca lokasi perangkat Anda.', 'info');
                    resetButton();
                    return;
                }

                if (!locationValidation.isInsideRadius) {
                    promptOutsideRadiusModal(locationValidation.distanceMeters || 0, Number(EMPLOYEE_LOCATION.radius || 0));
                    resetButton();
                    return;
                }

                if (geoState.latitude === null || geoState.longitude === null) {
                    Swal.fire('Lokasi Tidak Tersedia', 'Koordinat perangkat belum tersedia.', 'info');
                    resetButton();
                    return;
                }

                if (isVerifying) {
                    console.log('�?� Sedang memverifikasi, abaikan klik');
                    return;
                }

                isVerifying = true; // Kunci proses
                button.disabled = true;

                console.log('�Y"? Memulai proses verifikasi...');

                Swal.fire({
                    title: 'Mencoba melakukan presensi...',
                    text: 'Harap tetap di posisi yang sama sementara sistem memverifikasi wajah Anda.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                    showConfirmButton: false,
                });

                const mtcnnOptions = new faceapi.MtcnnOptions({
                    minFaceSize: 100
                });

                try {
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

                    const distanceResults = collectedDescriptors.map(descriptor => findBestReferenceDistance(descriptor));
                    const distances = distanceResults.map(result => result.distance);
                    const minDistance = Math.min(...distances);
                    const avgDistance = distances.reduce((sum, d) => sum + d, 0) / distances.length;
                    const matchCount = distances.filter(d => d < FACE_VERIFICATION_THRESHOLD).length;

                    console.log(`📏 Sampel jarak: ${distances.map(d => d.toFixed(4)).join(', ')}`);
                    console.log(
                        `✅ Minimum: ${minDistance.toFixed(4)}, Rata-rata: ${avgDistance.toFixed(4)}, Cocok: ${matchCount}`
                    );

                    if (matchCount >= REQUIRED_MATCHES && avgDistance < FACE_VERIFICATION_THRESHOLD +
                        FACE_VERIFICATION_MARGIN) {
                        console.log('✅ Verifikasi berhasil! Wajah cocok.');
                        Swal.update({
                            title: 'Verifikasi Berhasil!',
                            text: 'Mengambil snapshot...'
                        });

                        const snapshotData = takeSnapshot(video);
                        await sendPresenceRequest(csrfToken, snapshotData);

                    } else {
                        console.log(
                            `❌ Verifikasi gagal! Rata-rata jarak terlalu besar: ${avgDistance.toFixed(4)}`);
                        Swal.fire({
                            icon: 'error',
                            title: 'Bukan Pemilik Akun',
                            text: 'Wajah yang terdeteksi tidak cocok dengan data pemilik akun ini.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            timer: 2200,
                            timerProgressBar: true,
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


        function takeSnapshot(videoElement) {
            console.log('📷 Mengambil snapshot...');
            const canvas = document.createElement('canvas');
            canvas.width = videoElement.videoWidth;
            canvas.height = videoElement.videoHeight;
            const ctx = canvas.getContext('2d');


            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);


            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            console.log('✅ Snapshot berhasil diambil');
            return dataUrl;
        }

        async function sendPresenceRequest(csrfToken, snapshotData) {
            console.log('📸 Memulai proses pengiriman presensi dengan foto...');
            if (!EMPLOYEE_LOCATION) {
                Swal.fire('Lokasi Tidak Tersedia', 'Lokasi presensi belum ditetapkan. Hubungi admin.', 'error');
                resetButton();
                return;
            }

            if (!locationValidation.ready || !locationValidation.isInsideRadius) {
                Swal.fire('Di Luar Radius', 'Anda berada di luar radius lokasi atau lokasi belum terbaca.', 'error');
                resetButton();
                return;
            }

            if (geoState.latitude === null || geoState.longitude === null) {
                Swal.fire('Lokasi Tidak Terbaca', 'Aktifkan GPS untuk melanjutkan.', 'error');
                resetButton();
                return;
            }

            if (!csrfToken) {
                Swal.fire('Error', 'CSRF token tidak ditemukan. Refresh halaman.', 'error');
                resetButton();
                return;
            }

            Swal.update({
                title: 'Mengirim Data Presensi...'
            });

            try {
                const response = await fetch('/employee/presence/store', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        snapshot: snapshotData,
                        coordinates: {
                            latitude: geoState.latitude,
                            longitude: geoState.longitude,
                            accuracy: geoState.accuracy,
                        },
                    }),
                });
                const data = await response.json();

                if (!response.ok) {
                    console.error('🚨 Server error:', data);
                    const errorMessage = data.error || data.message;
                    throw new Error(errorMessage || `Server error (${response.status})`);
                }

                                console.log('✅ Presensi berhasil!');
                const recordedTime = data.recorded_at || data.waktu_masuk || data.waktu_pulang;
                const statusLabel = data.status_kehadiran || 'Tepat Waktu';
                const isLate = statusLabel === 'Terlambat';
                const shiftInfo = data.shift || {};
                const actionType = data.action || 'clock_in';
                const modalDetails = [
                    `<p><strong>Status:</strong> ${statusLabel}</p>`,
                    `<p><strong>Jam Shift:</strong> ${shiftInfo.jam_masuk || '-'}</p>`,
                    `<p><strong>Jam Presensi:</strong> ${recordedTime || '-'}</p>`
                ];
                if (isLate) {
                    modalDetails.push(`
                        <div class="alert alert-warning mt-2" role="alert">
                            Anda tercatat terlambat. Segera informasikan kepada atasan jika diperlukan.
                        </div>
                    `);
                }
                const radiusLimit = EMPLOYEE_LOCATION ? Number(EMPLOYEE_LOCATION.radius || 0) : null;
                if (locationValidation.distanceMeters !== null && radiusLimit !== null) {
                    const distanceText = `${locationValidation.distanceMeters.toFixed(1)} m`;
                    const radiusStatusText = locationValidation.isInsideRadius ? 'Dalam Radius' : 'Di Luar Radius';
                    modalDetails.push(`<p><strong>Jarak ke Lokasi:</strong> ${distanceText}</p>`);
                    modalDetails.push(`<p><strong>Radius Ditentukan:</strong> ${radiusLimit} m</p>`);
                    modalDetails.push(`<p><strong>Status Radius:</strong> ${radiusStatusText}</p>`);
                    if (!locationValidation.isInsideRadius && radiusLimit > 0 && locationValidation.distanceMeters > radiusLimit) {
                        modalDetails.push(`
                            <div class="alert alert-danger mt-2" role="alert">
                                Sistem mendeteksi Anda di luar radius lokasi. Dekati titik kantor untuk presensi berikutnya.
                            </div>
                        `);
                    }
                }
                Swal.fire({
                    icon: isLate ? 'warning' : 'success',
                    title: actionType === 'clock_out'
                        ? 'Presensi Pulang Tercatat'
                        : (isLate ? 'Presensi Tercatat (Terlambat)' : 'Presensi Masuk Tercatat'),
                    html: `<div class="text-start">${modalDetails.join('')}</div>`,
                    allowOutsideClick: false,
                    showConfirmButton: true,
                    confirmButtonText: 'Ok'
                });
                if (actionType === 'clock_out') {
                    presenceState.hasCheckedIn = true;
                    presenceState.hasCheckedOut = true;
                    presenceState.canCheckOut = false;
                    presenceState.lastClockOut = recordedTime || null;
                    hasShownCheckoutReminder = true;
                } else {
                    presenceState.hasCheckedIn = true;
                    presenceState.lastClockIn = recordedTime || null;
                    presenceState.canCheckOut = false;
                    hasShownCheckInReminder = true;
                }
                updateStatusIndicator('Berhasil!', `Presensi tercatat (${recordedTime || '-'}).`, 'success');
                recentSuccess = true;
                setTimeout(() => { recentSuccess = false; }, 15000);
                isVerifying = false;
                monitorPresenceStatus({
                    showReminders: false
                });
                return data;
            } catch (err) {
                console.error('?? Terjadi kesalahan:', err);
                Swal.fire('Error', err.message || 'Tidak dapat terhubung ke server.', 'error');
                updateStatusIndicator('Error', 'Gagal mengirim data.', 'danger');
                isVerifying = false; // Reset lock on error
                monitorPresenceStatus({
                    showReminders: false
                });
            }

        }





        async function monitorPresenceStatus(options = {}) {
            const { showReminders = true } = options;
            try {
                const response = await fetch('/employee/presence/status', {
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
    </script>


@endsection
