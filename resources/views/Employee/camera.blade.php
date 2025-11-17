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
        <div class="w-100 mb-4">
            <input type="hidden" id="location" placeholder="Menunggu lokasi...">
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
        {{-- <div class="w-100 mb-4">
            <div id="map"></div>
        </div>
    </div> --}}

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
            let referenceDescriptor; // Diisi saat init() dari API
            let isVerifying = false; // Mencegah klik ganda

            /**
             * ========================================================================
             * FUNGSI INISIALISASI
             * ========================================================================
             */
            document.addEventListener("DOMContentLoaded", () => {
                const attendanceButton = document.getElementById('takeattandance');
                if (!attendanceButton) {
                    console.log("Mode absensi tidak aktif.");
                    return;
                }
                // Jalankan inisialisasi
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
                    // 2. Muat Model AI
                    await loadFaceModels();

                    // 3. Ambil Data Wajah Referensi (Logika baru dari controller Anda)
                    await getReferenceEmbedding();

                    // 4. Nyalakan Kamera
                    Swal.update({
                        text: 'Mengakses kamera...'
                    });
                    startCamera(); // startCamera akan memanggil setupAttendanceButton

                    // Swal.close() akan dipanggil oleh startCamera() saat kamera 'live'
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Inisialisasi Gagal',
                        text: error.message, // Menampilkan error (misal: "Data referensi tidak ditemukan")
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
                const MODEL_URL = '/models'; // Pastikan path ini benar
                await faceapi.nets.mtcnn.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            }

            /**
             * DIPERBAIKI: Mengambil data embedding referensi dari controller faceMatcher
             * dengan penanganan error yang lebih baik
             */
            async function getReferenceEmbedding() {
                try {
                    console.log('🔄 Mengambil data referensi dari server...');
                    const response = await fetch(`/api/employee/embedding`);

                    if (!response.ok) {
                        const err = await response.json();
                        throw new Error(err.error || 'Gagal mengambil data referensi');
                    }

                    const data = await response.json();

                    // DEBUG: Lihat struktur data
                    console.log('📦 Raw data dari server:', data);
                    console.log('📊 Type descriptor:', typeof data.descriptor);
                    console.log('📝 Descriptor value:', data.descriptor);

                    // PERBAIKAN: Tangani berbagai format descriptor
                    let descriptorArray;

                    if (typeof data.descriptor === 'string') {
                        // Jika masih string JSON, parse dulu
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
                        // Jika sudah array, langsung pakai
                        console.log('✅ Descriptor sudah berupa array');
                        descriptorArray = data.descriptor;
                    } else if (typeof data.descriptor === 'object' && data.descriptor !== null) {
                        // Jika object, konversi ke array
                        console.log('🔧 Descriptor adalah object, konversi ke array...');
                        descriptorArray = Object.values(data.descriptor);
                    } else {
                        console.error('❌ Format descriptor tidak dikenali:', data.descriptor);
                        throw new Error('Format descriptor tidak dikenali');
                    }

                    // Validasi panjang array (harus 128 untuk face-api.js)
                    if (!descriptorArray || !Array.isArray(descriptorArray)) {
                        throw new Error('Descriptor bukan array yang valid');
                    }

                    if (descriptorArray.length !== 128) {
                        console.error(`❌ Panjang descriptor salah: ${descriptorArray.length} (seharusnya 128)`);
                        throw new Error(`Descriptor tidak valid (panjang: ${descriptorArray.length}, seharusnya 128)`);
                    }

                    // Konversi ke Float32Array yang dibutuhkan oleh face-api.js
                    referenceDescriptor = new Float32Array(descriptorArray);

                    console.log('✅ Data referensi berhasil dimuat (128 float values)');
                    console.log('📊 Sample values:', Array.from(referenceDescriptor.slice(0, 5)));

                } catch (err) {
                    console.error('❌ Error mengambil embedding:', err);
                    console.error('❌ Stack trace:', err.stack);
                    // Hentikan alur jika referensi gagal dimuat
                    throw new Error(err.message || 'Data referensi wajah Anda tidak ditemukan.');
                }
            }

            /**
             * ========================================================================
             * FUNGSI KAMERA & DETEKSI (Struktur dari skrip lama)
             * ========================================================================
             */
            function startCamera() {
                const wrap = document.querySelector(".camera-capture");
                if (!wrap) {
                    console.error('❌ Element .camera-capture tidak ditemukan');
                    return;
                }
                wrap.querySelector("span")?.remove(); // Hapus teks "Memuat Kamera..."

                Webcam.set({
                    image_format: "jpeg",
                    jpeg_quality: 90,
                    flip_horiz: true
                });

                Webcam.on("live", () => {
                    console.log('✅ Kamera live');
                    Swal.close(); // Kamera siap, tutup loading
                    const video = wrap.querySelector("video");
                    if (!video) {
                        console.error('❌ Element video tidak ditemukan');
                        return;
                    }

                    forceVideoFill(video);
                    const overlay = ensureOverlay(wrap);
                    const sync = () => syncCanvasToBox(overlay, wrap);
                    sync();
                    new ResizeObserver(sync).observe(wrap);

                    // Jalankan deteksi visual (untuk UX)
                    const displayName =
                        @json(optional(optional($user)->employee)->nama ?? (optional(optional($user)->employee)->nik ?? 'Tidak dikenali'));
                    if (video.readyState >= 2) {
                        runDetect(video, overlay, wrap, displayName);
                    } else {
                        video.addEventListener("loadedmetadata", () => runDetect(video, overlay, wrap, displayName), {
                            once: true
                        });
                    }

                    // Siapkan tombol HANYA setelah kamera live dan referensi siap
                    setupAttendanceButton('takeattandance', '.camera-capture video');
                });

                Webcam.on("error", (err) => {
                    console.error('❌ Webcam error:', err);
                    Swal.fire('Kamera Error', `Gagal mengakses kamera: ${err.message}. Harap izinkan akses kamera.`,
                        'error');
                });

                Webcam.attach(".camera-capture");
            }

            /**
             * Fungsi ini hanya untuk UX (User Experience)
             * Menampilkan kotak deteksi dan mengaktifkan/menonaktifkan tombol.
             * Perbandingan berat (Euclidean) TIDAK dilakukan di sini.
             */
            function runDetect(video, canvas, box, displayName) {
                const ctx = canvas.getContext('2d');
                const opts = new faceapi.MtcnnOptions({
                    minFaceSize: 100
                }); // Opsi deteksi cepat
                const dpr = window.devicePixelRatio || 1;
                const attendanceButton = document.getElementById('takeattandance');
                const buttonText = document.getElementById('button-text');

                async function loop() {
                    // Jeda deteksi jika sedang memverifikasi ATAU jika data referensi belum siap
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

                        if (attendanceButton && buttonText && !attendanceButton.classList.contains('btn-success')) {
                            if (recognizedFaces === 1 && resizedResults.length === 1) {
                                attendanceButton.disabled = false;
                                buttonText.innerText = "Absen Sekarang";
                            } else if (resizedResults.length > 1) {
                                attendanceButton.disabled = true;
                                buttonText.innerText = "Terlalu Banyak Wajah";
                            } else {
                                attendanceButton.disabled = true;
                                buttonText.innerText = "Wajah Tidak Terdeteksi";
                            }
                        }
                    } catch (detectError) {
                        console.error('❌ Error saat deteksi wajah:', detectError);
                    }

                    requestAnimationFrame(loop);
                }
                loop();
            }

            function setupAttendanceButton(buttonId, videoSelector) {
                const button = document.getElementById(buttonId);
                const video = document.querySelector(videoSelector);
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

                // Validasi elemen-elemen penting
                if (!button) {
                    console.error("❌ Tombol tidak ditemukan:", buttonId);
                    return;
                }
                if (!video) {
                    console.error("❌ Video tidak ditemukan:", videoSelector);
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

                button.addEventListener('click', async () => {
                    if (isVerifying) {
                        console.log('⏳ Sedang memverifikasi, abaikan klik');
                        return;
                    }

                    isVerifying = true; // Kunci proses
                    button.disabled = true;

                    console.log('🔍 Memulai proses verifikasi...');

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

                        const distances = collectedDescriptors.map(descriptor =>
                            faceapi.euclideanDistance(descriptor, referenceDescriptor));
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
                            await sendClockInRequest(csrfToken, snapshotData);

                        } else {
                            // GAGAL: Wajah tidak cocok
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

            /**
             * DIPERBAIKI: Mengambil snapshot dari elemen video Webcam.js
             */
            function takeSnapshot(videoElement) {
                console.log('📷 Mengambil snapshot...');
                const canvas = document.createElement('canvas');
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;
                const ctx = canvas.getContext('2d');

                // Balikkan gambar secara horizontal (mirror)
                // Ini penting karena Webcam.js (flip_horiz: true) membalik tampilannya
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                // Kembalikan sebagai data URL Base64
                const dataUrl = canvas.toDataURL('image/jpeg', 0.9); // Kualitas 90%
                console.log('✅ Snapshot berhasil diambil');
                return dataUrl;
            }

            async function sendClockInRequest(csrfToken, snapshotData) {
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
                    Swal.fire({
                        icon: 'success',
                        title: data.status === 'clock_out' ? 'Presensi Pulang Berhasil!' :
                            'Presensi Masuk Berhasil!',
                        text: `Jam tercatat: ${data.recorded_at || data.waktu_masuk || data.waktu_pulang}`,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    const button = document.getElementById('takeattandance');
                    const buttonText = document.getElementById('button-text');
                    if (buttonText) {
                        const timeText = data.recorded_at || data.waktu_masuk || data.waktu_pulang || '-';
                        buttonText.innerText = `Berhasil! (${timeText})`;
                    }
                    if (button) {
                        button.disabled = true;
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');
                    }
                    isVerifying = false;

                    return data;
                } catch (err) {
                    console.error('?? Terjadi kesalahan:', err);
                    Swal.fire('Error', err.message || 'Tidak dapat terhubung ke server.', 'error');
                    resetButton();
                }

            }

            function resetButton() {
                console.log('🔄 Reset tombol ke status awal');
                isVerifying = false;
                const button = document.getElementById('takeattandance');
                const buttonText = document.getElementById('button-text');
                if (button) button.disabled = false;
                // Teks akan otomatis di-update oleh runDetect()
                if (buttonText) buttonText.innerText = 'Wajah Tidak Terdeteksi';
            }

            // --- FUNGSI UTILITAS (Tidak berubah, untuk Webcam.js) ---
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
