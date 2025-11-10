@extends('layout.admin')
@section('title', 'Data Wajah')
@section('content')


    <div class="container mt-5">

        <div class="row g-4 justify-content-center">

            <div class="col-12 col-md-6">
                <div class="camera-open"
                    style="position:relative;width:100%;max-width:720px;aspect-ratio:720/520;background:#000;overflow:hidden;border-radius:8px;">
                </div>
            </div>

            <div class="col-12 col-md-6">

                <input type="hidden" id="employee-id" value="{{ $employee->id }}">

                <div class="card shadow-sm border-0 rounded-3 mb-3">

                    <div class="card-body p-4">

                        <h2 class="h3 text-center mb-3 fw-semibold">Pendaftaran Wajah Karyawan</h2>
                        <p class="text-muted text-center mb-3">Sesuaikan posisi wajah anda</p>

                        <hr class="my-3">

                        <div class="mb-2">
                            <label class="form-label text-muted small mb-0">NIK</label>
                            <p class="h5 mb-0">{{ $employee->nik ?? 'N/A' }}</p>
                        </div>
                        <hr class="my-3">
                        <div>
                            <label class="form-label text-muted small mb-0">NAMA</label>
                            <p class="h5 mb-0">{{ $employee->nama ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" id="rekamwajah">
                        <i class="fas fa-camera me-2"></i>
                        Rekam Wajah
                    </button>

                    <a href="{{ route('admin.data') }}" class="btn btn-outline-danger btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Kembali
                    </a>
                </div>
            </div>

            
        </div>
        
    </div>
    <a href="{{ route('admin.data') }}" id="admin-data-route" class="d-none"></a>





@endsection

@section('style')
    <style>
        #warning-box {
            padding: 40px 20px;
            text-align: center;
            border: 2px dashed #e74c3c;
            background: #fbeae8;
            border-radius: 8px;
        }

        #warning-box i {
            font-size: 3em;
            color: #e74c3c;
        }

        #warning-box p {
            font-size: 1.2em;
            font-weight: bold;
            color: #c0392b;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const rekamButton = document.getElementById('rekamwajah');
            if (!rekamButton) {
                console.log("Mode registrasi tidak aktif (wajah mungkin sudah terdaftar).");
                return;
            }
            init();
        });

        async function init() {
            await ensureFaceAPI();

            Swal.fire({
                title: 'Memuat kamera',
                text: 'Ini mungkin perlu waktu lebih lama. Harap tunggu...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            await loadFaceModels();
            Swal.close();
            startCamera();
            setupEnrollmentButton('rekamwajah', '.camera-open video');
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
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/models';
            await faceapi.nets.mtcnn.loadFromUri('/models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
        }

        function startCamera() {
            const wrap = document.querySelector(".camera-open");
            if (!wrap) return;
            wrap.querySelector("span")?.remove();

            Webcam.set({
                image_format: "jpeg",
                jpeg_quality: 90,
                flip_horiz: true
            });

            Webcam.on("live", () => {
                const video = wrap.querySelector("video");
                if (!video) return;

                forceVideoFill(video);
                const overlay = ensureOverlay(wrap);
                const sync = () => syncCanvasToBox(overlay, wrap);
                sync();
                new ResizeObserver(sync).observe(wrap);

                if (video.readyState >= 2) runDetect(video, overlay, wrap);
                else video.addEventListener("loadedmetadata", () => runDetect(video, overlay, wrap), {
                    once: true
                });
            });

            Webcam.attach(".camera-open");
        }

        function runDetect(video, canvas, box) {
            const ctx = canvas.getContext('2d');

            const opts = new faceapi.MtcnnOptions();

            const dpr = window.devicePixelRatio || 1;
            const rekamButton = document.getElementById('rekamwajah');

            async function loop() {
                if (video.readyState < 2) {
                    requestAnimationFrame(loop);
                    return;
                }

                const drawSize = {
                    width: box.clientWidth,
                    height: box.clientHeight
                };

                const detections = await faceapi.detectAllFaces(video, opts);
                const resizedResults = faceapi.resizeResults(detections, drawSize);

                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                faceapi.draw.drawDetections(canvas, resizedResults);

                if (rekamButton) {
                    if (resizedResults.length === 1) {
                        rekamButton.disabled = false;
                        rekamButton.innerText = "Rekam Wajah";
                    } else if (resizedResults.length > 1) {
                        rekamButton.disabled = true;
                        rekamButton.innerText = "Terlalu Banyak Wajah";
                    } else {
                        rekamButton.disabled = true;
                        rekamButton.innerText = "Wajah Tidak Terdeteksi";
                    }
                }

                requestAnimationFrame(loop);
            }
            loop();
        }

        function setupEnrollmentButton(buttonId, videoSelector) {

            const button = document.getElementById(buttonId);
            const video = document.querySelector(videoSelector);
            const employeeIdInput = document.getElementById('employee-id');
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

            if (!button || !video || !employeeIdInput || !csrfTokenMeta) {
                console.error("Elemen penting tidak ditemukan!");
                console.error("Tombol 'rekamwajah'?", button);
                console.error("Video?", video);
                console.error("Input 'employee-id'?", employeeIdInput);
                console.error("Meta 'csrf-token'?", csrfTokenMeta);

                Swal.fire(
                    'Error Kritis',
                    'Elemen halaman penting (ID Karyawan atau Token) tidak ditemukan. Halaman tidak bisa berfungsi.',
                    'error'
                );
                return;
            }

            const employeeId = employeeIdInput.value;
            const csrfToken = csrfTokenMeta.getAttribute('content');

            button.addEventListener('click', async () => {
                Swal.fire({
                    title: 'Memproses Wajah...',
                    text: 'Mendeteksi dan mengekstrak data. Harap tunggu...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const opts = new faceapi.MtcnnOptions();

                const detection = await faceapi.detectSingleFace(video, opts)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (!detection) {
                    Swal.fire('Gagal',
                        'Wajah tidak terdeteksi dengan jelas. Posisikan wajah Anda lurus ke kamera dan coba lagi.',
                        'error');
                    return;
                }

                const descriptor = detection.descriptor;

                await saveDescriptorToAPI(employeeId, descriptor, csrfToken);
            });
        }

        async function saveDescriptorToAPI(employeeId, descriptor, csrfToken) {
            try {
                const response = await fetch('/api/save-embedding', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        descriptor: Array.from(descriptor)
                    })
                });

                const data = await response.json();

                // Dapatkan URL redirect DARI AWAL
                const redirectElement = document.getElementById('admin-data-route');
                const redirectUrl = redirectElement ? redirectElement.href : null;

                if (!redirectUrl) {
                    console.error("Fatal Error: Elemen 'admin-data-route' tidak ditemukan!");
                    Swal.fire('Error Kritis', 'URL Redirect admin.data tidak ditemukan.', 'error');
                    return;
                }

                if (response.ok && data.success) {
                    sessionStorage.setItem('showSuccessModal', data.message || 'Data wajah berhasil disimpan!');

                    // 2. Langsung redirect
                    window.location.href = redirectUrl;

                } else {
                    // Gagal dari server (bukan error koneksi)
                    Swal.fire({
                        title: 'Gagal!',
                        text: data.message || 'Gagal menyimpan data.',
                        icon: 'error',
                        confirmButtonText: 'Coba Lagi'
                    });
                }

            } catch (error) {
                console.error("Error saving descriptor:", error);

                // --- INI PERUBAHANNYA (BAGIAN KONEKSI ERROR) ---
                const redirectElement = document.getElementById('admin-data-route');
                const redirectUrl = redirectElement ? redirectElement.href : null;

                if (redirectUrl) {
                    // 1. Simpan pesan PERINGATAN ke sessionStorage
                    sessionStorage.setItem('showWarningModal',
                        'Koneksi terputus saat mengirim. Data Anda MUNGKIN sudah tersimpan. Harap periksa daftar.');

                    // 2. Langsung redirect
                    window.location.href = redirectUrl;
                } else {
                    // Fallback jika redirect URL tidak ada
                    Swal.fire({
                        title: 'Koneksi Terputus!',
                        text: 'Kami tidak dapat menerima konfirmasi. Harap refresh manual.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
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
