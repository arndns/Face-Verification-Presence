@extends('layout.employee')
@section('title', 'Camera')
@section('header')
    <div class="appHeader text-light p-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="left">

            <a href="javascript:;" class="headerButton goBack text-light">
                <!-- Font Awesome back icon -->
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
        </div>
        <div class="pageTitle h5 mb-0">
            Attandance Employee
        </div>
        <div class="right" style="width: 24px;">

        </div>
    </div>
@endsection
@section('content')
    <div class="p-4 ">
        <div class="w-100 mb-4" data-camera-wrapper>
            <input type="hidden" id="location" placeholder="Menunggu lokasi..." readonly>
            <!-- Wadah tidak akan melebihi lebar .w-100 -->
            <div class="camera-capture text-muted"
                style="position:relative;width:100%;max-width:720px;aspect-ratio:720/520;background:#000;overflow:hidden;border-radius:8px;">
                <span style="position:absolute;inset:auto auto 8px 8px;z-index:3;color:#fff;opacity:.8;">Memuat
                    Kamera...</span>
            </div>
        </div>
        <div class="w-100 mb-4">
            <button id="takeattandance"
                class="w-100 btn btn-primary btn-lg fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-3"
                data-user-id="{{ $user->employee->id }}" disabled> <i class="fa-solid fa-camera fa-lg"></i>
                <span id="button-text">Wajah Tidak Terdeteksi</span>
            </button>
        </div>
        {{-- <div class="w-100 mb-4">
            <button id="takeattandance"
                class="w-100 btn btn-danger btn-lg fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-3">
                <i class="fa-solid fa-camera fa-lg"></i>
                <span id="button-text">Presensi Pulang</span>
            </button>
        </div> --}}
        <div class="w-100 mb-4">
            <div id="map"></div>
        </div>
    </div>

@endsection

@section('style')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        canvas {
            position: absolute;
        }

        #map {
            height: 200px;
        }
    </style>



@endsection


@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // --- Variabel Global ---
        const FACE_VERIFICATION_THRESHOLD = 0.38; // Threshold Euclidean Distance
        const FACE_VERIFICATION_MARGIN = 0.03; // toleransi rata-rata
        const VERIFICATION_SAMPLES = 5;
        const REQUIRED_MATCHES = 4;
        const FRAME_DELAY_MS = 120;
        const VIDEO_MIRRORED = true;
        const STATUS_POLL_INTERVAL_MS = 60000;
        let referenceDescriptor; // Diisi saat init() dari API
        let isVerifying = false;
        const presenceState = {
            hasCheckedIn: false,
            hasCheckedOut: false,
            canCheckOut: false,
            lastClockIn: null,
            lastClockOut: null,
        };
        let hasShownCheckInReminder = false;
        let hasShownCheckoutReminder = false;
        let presenceStatusInterval = null;

        var lokasi = document.getElementById('location');
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
        }

        function successCallback(position) {
            lokasi.value = position.coords.latitude + ',' + position.coords.longitude;
            var map = L.map('map').setView([position.coords.latitude, position.coords.longitude], 15);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            var maker = L.marker([position.coords.latitude, position.coords.longitude]).addTo(map);
            var circle = L.circle([position.coords.latitude, position.coords.longitude], {
                color: 'red',
                fillColor: '#f03',
                fillOpacity: 0.5,
                radius: 20
            }).addTo(map);
        }

        function errorCallback(error) {
            console.warn('Tidak dapat membaca lokasi pengguna:', error);
            lokasi.value = '';
            Swal.fire({
                icon: 'warning',
                title: 'Akses Lokasi Diperlukan',
                text: 'Berikan izin lokasi agar presensi dapat diverifikasi.',
            });
        }



        document.addEventListener("DOMContentLoaded", () => {
            const attendanceButton = document.getElementById('takeattandance');
            if (!attendanceButton) {
                console.log("Mode absensi tidak aktif.");
                return;
            }

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
                console.log('🔄 Mengambil data referensi dari server...');
                const response = await fetch(`/api/employee/embedding`);

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Gagal mengambil data referensi');
                }

                const data = await response.json();
                console.log('📦 Raw data dari server:', data);
                console.log('📊 Type descriptor:', typeof data.descriptor);
                console.log('📝 Descriptor value:', data.descriptor);

                let descriptorArray;

                if (typeof data.descriptor === 'string') {
                    console.log('🔧 Descriptor adalah string, melakukan parse...');
                    try {
                        descriptorArray = JSON.parse(data.descriptor);
                        console.log('✅ Parse berhasil, hasil:', descriptorArray);
                    } catch (parseError) {
                        console.error('❌ Error parsing descriptor string:', parseError);
                        console.error('❌ Descriptor content:', data.descriptor);
                        console.error('❌ Karakter di posisi error:', data.descriptor.substring(0, 50));
                        throw new Error('Format data descriptor tidak valid (JSON parse error)');
                    }
                } else if (Array.isArray(data.descriptor)) {
                    console.log('✅ Descriptor sudah berupa array');
                    descriptorArray = data.descriptor;
                } else if (typeof data.descriptor === 'object' && data.descriptor !== null) {
                    console.log('🔧 Descriptor adalah object, konversi ke array...');
                    descriptorArray = Object.values(data.descriptor);
                } else {
                    console.error('❌ Format descriptor tidak dikenali:', data.descriptor);
                    throw new Error('Format descriptor tidak dikenali');
                }

                if (!descriptorArray || !Array.isArray(descriptorArray)) {
                    throw new Error('Descriptor bukan array yang valid');
                }

                if (descriptorArray.length !== 128) {
                    console.error(`❌ Panjang descriptor salah: ${descriptorArray.length} (seharusnya 128)`);
                    throw new Error(`Descriptor tidak valid (panjang: ${descriptorArray.length}, seharusnya 128)`);
                }

                referenceDescriptor = new Float32Array(descriptorArray);

                console.log('✅ Data referensi berhasil dimuat (128 float values)');
                console.log('📊 Sample values:', Array.from(referenceDescriptor.slice(0, 5)));

            } catch (err) {
                console.error('❌ Error mengambil embedding:', err);
                console.error('❌ Stack trace:', err.stack);
                throw new Error(err.message || 'Data referensi wajah Anda tidak ditemukan.');
            }
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
                image_format: "jpeg",
                jpeg_quality: 90,
                flip_horiz: true
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


                setupAttendanceButton('takeattandance', video);
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
            const attendanceButton = document.getElementById('takeattandance');
            const buttonText = document.getElementById('button-text');

            async function loop() {
                if (video.readyState < 2 || isVerifying || !referenceDescriptor) {
                    requestAnimationFrame(loop);
                    return;
                }

                const drawSize = {
                    width: box.clientWidth,
                    height: box.clientHeight
                };

                try {
                    // Hanya deteksi (cepat), tidak perlu landmark/descriptor di loop ini
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
                        if (result.descriptor && referenceDescriptor) {
                            const distance = faceapi.euclideanDistance(result.descriptor, referenceDescriptor);
                            if (distance < FACE_VERIFICATION_THRESHOLD) {
                                label = displayName;
                                recognizedFaces++;
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

                        ctx.fillStyle = 'rgba(14, 165, 233, 0.8)';
                        ctx.strokeStyle = '#38bdf8';

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

                    if (attendanceButton && buttonText) {
                        const actionMode = getCurrentActionMode();
                        if (actionMode === 'done') {
                            attendanceButton.disabled = true;
                            buttonText.innerText = 'Presensi Hari Ini Selesai';
                        } else if (actionMode === 'waiting') {
                            attendanceButton.disabled = true;
                            buttonText.innerText = 'Menunggu Jam Pulang';
                        } else if (actionMode === 'check_out' || actionMode === 'check_in') {
                            if (recognizedFaces === 1 && resizedResults.length === 1) {
                                attendanceButton.disabled = false;
                                buttonText.innerText = actionMode === 'check_out' ? 'Presensi Pulang' : 'Presensi Masuk';
                            } else if (resizedResults.length > 1) {
                                attendanceButton.disabled = true;
                                buttonText.innerText = 'Terlalu Banyak Wajah';
                            } else {
                                attendanceButton.disabled = true;
                                buttonText.innerText = 'Wajah Tidak Terdeteksi';
                            }
                        } else {
                            attendanceButton.disabled = true;
                        }
                    }
                } catch (detectError) {
                    console.error('❌ Error saat deteksi wajah:', detectError);
                }

                requestAnimationFrame(loop);
            }
            loop();
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
            if (!referenceDescriptor) {
                console.error("❌ Data referensi belum dimuat");
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

                    const distances = collectedDescriptors.map(descriptor => faceapi.euclideanDistance(
                        descriptor, referenceDescriptor));
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

            if (!csrfToken) {
                Swal.fire('Error', 'CSRF token tidak ditemukan. Refresh halaman.', 'error');
                resetButton();
                return;
            }

            Swal.update({
                title: 'Mengirim Data Presensi...'
            });

            try {
                const response = await fetch('/presence/store', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        snapshot: snapshotData,
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
                Swal.fire({
                    icon: isLate ? 'warning' : 'success',
                    title: actionType === 'clock_out' ? 'Presensi Pulang Tercatat' : (isLate ? 'Presensi Tercatat (Terlambat)' : 'Presensi Masuk Tercatat'),
                    html: `
                        <div class="text-start">
                            <p><strong>Status:</strong> ${statusLabel}</p>
                            <p><strong>Jam Shift:</strong> ${shiftInfo.jam_masuk || '-'}</p>
                            <p><strong>Jam Presensi:</strong> ${recordedTime || '-'}</p>
                        </div>
                    `,
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
                const button = document.getElementById('takeattandance');
                const buttonText = document.getElementById('button-text');
                if (buttonText) {
                    const timeText = recordedTime || '-';
                    buttonText.innerText = `Berhasil! (${timeText})`;
                }
                isVerifying = false;
                applyButtonIdleState();
                monitorPresenceStatus({
                    showReminders: false
                });
                return data;
            } catch (err) {
                console.error('?? Terjadi kesalahan:', err);
                Swal.fire('Error', err.message || 'Tidak dapat terhubung ke server.', 'error');
                resetButton();
                monitorPresenceStatus({
                    showReminders: false
                });
            }

        }

        function resetButton() {
            console.log('🔄 Reset tombol ke status awal');
            isVerifying = false;
            applyButtonIdleState();
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

            applyButtonIdleState();

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
            if (presenceState.hasCheckedOut) {
                return 'done';
            }
            if (!presenceState.hasCheckedIn) {
                return 'check_in';
            }
            if (presenceState.canCheckOut) {
                return 'check_out';
            }
            return 'waiting';
        }

        function applyButtonIdleState() {
            const button = document.getElementById('takeattandance');
            const buttonText = document.getElementById('button-text');
            const actionMode = getCurrentActionMode();
            if (!button || !buttonText) {
                return;
            }

            button.disabled = true;
            button.classList.remove('btn-success', 'btn-warning', 'btn-primary');

            if (actionMode === 'done') {
                button.classList.add('btn-success');
                buttonText.innerText = `Sudah Presensi (${presenceState.lastClockOut || '-'})`;
                return;
            }

            if (actionMode === 'waiting') {
                button.classList.add('btn-warning');
                buttonText.innerText = 'Menunggu Jam Pulang';
                return;
            }

            button.classList.add('btn-primary');
            if (actionMode === 'check_out') {
                buttonText.innerText = 'Presensi Pulang';
            } else {
                buttonText.innerText = 'Wajah Tidak Terdeteksi';
            }
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
