@extends('layout.admin')
@section('title', 'Data Wajah')
@section('content')


    <div class="container mt-5">

        @if (session('warning'))
            <div class="alert alert-warning">
                {{ session('warning') }}
            </div>
        @endif

        <div class="row g-4 justify-content-center">

            <div class="col-12 col-md-6">
                <div class="camera-open"
                    style="position:relative;width:100%;max-width:720px;aspect-ratio:720/520;background:#000;overflow:hidden;border-radius:8px;">
                    <div id="capture-loading" class="capture-loading d-none">
                        <div class="spinner-border text-light" role="status" style="width: 2.5rem; height: 2.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2 fw-semibold" id="capture-loading-text">Memproses...</div>
                    </div>
                    <div class="distance-guide">
                        <div class="guide-rect" id="guide-rect"></div>
                        <div class="guide-text">Posisikan wajah di dalam kotak (jarak +/- 30-60 cm)</div>
                        <div id="overlay-instruction" class="guide-subtext"></div>
                    </div>
                    <div id="orientation-hint" class="guide-hint"></div>
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
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-0">NAMA</label>
                            <p class="h5 mb-0">{{ $employee->nama ?? 'N/A' }}</p>
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1">Pilih Arah Rekam</label>
                            <select id="orientation" class="form-select">
                                <option value="front">Depan</option>
                                <option value="left">Miring Kiri</option>
                                <option value="right">Miring Kanan</option>
                                <option value="up">Menengadah (Atas)</option>
                                <option value="down">Menunduk (Bawah)</option>
                            </select>
                            <small class="text-muted">Arah yang sudah tersimpan akan disembunyikan dari pilihan.</small>
                        </div>

                    <div id="saved-orientations-wrapper" class="alert alert-info small {{ empty($existingEmbeddings) || $existingEmbeddings->isEmpty() ? 'd-none' : '' }}">
                        <div class="fw-semibold mb-1">Data yang sudah tersimpan:</div>
                        <div id="saved-orientations" class="d-flex flex-wrap gap-2">
                            @foreach ($existingEmbeddings ?? [] as $embedding)
                                <span class="badge bg-success text-uppercase">
                                    {{ $embedding->orientation ?? 'front' }}
                                </span>
                            @endforeach
                        </div>
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

        .distance-guide {
            position: absolute;
            inset: 0;
            pointer-events: none;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            z-index: 12;
        }

        .distance-guide .guide-rect {
            width: 55%;
            height: 55%;
            border: 2px dashed rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(1px);
        }

        .distance-guide .guide-text {
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .distance-guide .guide-subtext {
            padding: 6px 10px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.45);
            color: #e5e7eb;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .guide-hint {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            pointer-events: none;
            z-index: 13;
        }

        .guide-rect.ideal {
            border-color: rgba(52, 211, 153, 0.9);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4);
        }

        .capture-loading {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            z-index: 10;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>

    <script>
        // Konfigurasi deteksi wajah (MTCNN)
        const MTCNN_OPTIONS = {
            minFaceSize: 80,            // abaikan wajah yang terlalu kecil
            scoreThresholds: [0.6, 0.7, 0.7], // penyaring skor tiap stage
            scaleFactor: 0.8,           // sedikit lebih kasar supaya lebih cepat
        };

        document.addEventListener("DOMContentLoaded", () => {

            const rekamButton = document.getElementById('rekamwajah');
            if (!rekamButton) {
                console.log("Mode registrasi tidak aktif (wajah mungkin sudah terdaftar).");
                return;
            }
            init();
        });

        async function init() {
            const overlayInstruction = document.getElementById('overlay-instruction');
            if (overlayInstruction) overlayInstruction.innerText = 'Memuat model wajah...';
            const hint = document.getElementById('orientation-hint');
            if (hint) hint.innerText = 'Tunggu, model sedang dimuat';

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
                s.src = "{{ asset('assets/js/face-api.min.js') }}";
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

        function getMtcnnOptions() {
            return new faceapi.MtcnnOptions(MTCNN_OPTIONS);
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

            const opts = getMtcnnOptions();

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
            const orientationSelect = document.getElementById('orientation');
            const existingOrientations = new Set(
                (@json(($existingEmbeddings ?? collect())->pluck('orientation')) || []).map(o => (o || '').toLowerCase())
            );
            const ALL_ORIENTATIONS = ['front', 'left', 'right', 'up', 'down'];
            const adminDataUrl = document.getElementById('admin-data-route')?.href || null;
            const savedWrapper = document.getElementById('saved-orientations-wrapper');
            const savedList = document.getElementById('saved-orientations');
            const updateOrientationOptions = () => {
                const options = Array.from(orientationSelect.options || []);
                options.forEach(opt => {
                    const val = (opt.value || '').toLowerCase();
                    opt.hidden = existingOrientations.has(val);
                });
                // Pastikan value selalu ke opsi pertama yang tidak tersembunyi
                const firstVisible = options.find(opt => !opt.hidden);
                if (firstVisible) {
                    orientationSelect.value = firstVisible.value;
                }
            };

            if (!button || !video || !employeeIdInput || !csrfTokenMeta || !orientationSelect) {
                Swal.fire(
                    'Error Kritis',
                    'Elemen halaman penting (ID Karyawan atau Token) tidak ditemukan. Halaman tidak bisa berfungsi.',
                    'error'
                );
                return;
            }

            const employeeId = employeeIdInput.value;
            const csrfToken = csrfTokenMeta.getAttribute('content');

            const isAllComplete = () => ALL_ORIENTATIONS.every(o => existingOrientations.has(o));
            const renderSavedOrientations = () => {
                if (!savedWrapper || !savedList) return;
                savedList.innerHTML = '';
                existingOrientations.forEach((ori) => {
                    const span = document.createElement('span');
                    span.className = 'badge bg-success text-uppercase';
                    span.textContent = ori || 'front';
                    savedList.appendChild(span);
                });
                savedWrapper.classList.toggle('d-none', existingOrientations.size === 0);
                updateOrientationOptions();
            };
            renderSavedOrientations();

            if (isAllComplete()) {
                button.disabled = true;
                button.innerText = 'Semua arah sudah tersimpan';
                Swal.fire({
                    icon: 'success',
                    title: 'Semua arah lengkap',
                    text: 'Data wajah lengkap. Mengalihkan ke halaman data pegawai...',
                    timer: 1200,
                    showConfirmButton: false,
                }).then(() => {
                    if (adminDataUrl) {
                        window.location.href = adminDataUrl;
                    }
                });
                return;
            }

            button.addEventListener('click', async () => {
                const orientation = (orientationSelect.value || 'front').toLowerCase();
                const overlayInstruction = document.getElementById('overlay-instruction');
                const hint = document.getElementById('orientation-hint');
                const message = `Arahkan wajah: ${orientation}`;
                if (overlayInstruction) overlayInstruction.innerText = message;
                if (hint) hint.innerText = message;

                button.disabled = true;
                button.innerText = 'Memproses...';
                setLoading(true, 'Mengambil wajah...');

                try {
                    const detection = await faceapi.detectSingleFace(video, getMtcnnOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (!detection) {
                        await Swal.fire('Gagal', 'Wajah tidak terdeteksi dengan jelas. Ulangi lagi.', 'error');
                        return;
                    }

                    const descriptor = detection.descriptor;
                    await saveDescriptorToAPI(employeeId, descriptor, csrfToken, orientation, {
                        skipRedirect: false,
                        existingSet: existingOrientations,
                        allOrientations: ALL_ORIENTATIONS,
                        adminDataUrl,
                        buttonRef: button,
                        onSaved: renderSavedOrientations,
                    });
                } catch (err) {
                    console.error(err);
                    await Swal.fire('Error', err.message || 'Gagal memproses wajah.', 'error');
                } finally {
                    setLoading(false);
                    button.disabled = false;
                    button.innerText = 'Rekam Wajah';
                }
            });
        }

        async function captureQuickDescriptor(video, opts) {
            const detection = await faceapi.detectSingleFace(video, opts)
                .withFaceLandmarks()
                .withFaceDescriptor();
            return detection ? detection.descriptor : null;
        }

        function waitMs(ms) {
            return new Promise(res => setTimeout(res, ms));
        }

        function setLoading(isLoading, message = '') {
            const overlay = document.getElementById('capture-loading');
            const text = document.getElementById('capture-loading-text');
            if (!overlay) return;
            overlay.classList.toggle('d-none', !isLoading);
            if (text && message) {
                text.innerText = message;
            }
        }

        async function saveDescriptorToAPI(employeeId, descriptor, csrfToken, orientation, options = {}) {
            const {
                skipRedirect = false,
                existingSet = null,
                allOrientations = null,
                adminDataUrl = null,
                buttonRef = null,
                onSaved = null,
            } = options;
            const normalizeOri = (o) => (o || '').toLowerCase();
            const targetOrientation = normalizeOri(orientation);
            try {
                const response = await fetch('/api/save-embedding', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        descriptor: Array.from(descriptor),
                        orientation: orientation || 'front',
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (existingSet && orientation) {
                        existingSet.add(targetOrientation);
                    }
                    if (typeof onSaved === 'function') {
                        onSaved();
                    }

                    const savedOrientation = (data.orientation || targetOrientation || 'front').toUpperCase();
                    const successMessage = data.message
                        ? `${data.message} (${savedOrientation})`
                        : `Data wajah (${savedOrientation}) berhasil disimpan!`;

                    const allCompleted = allOrientations
                        ? allOrientations.every((o) => existingSet?.has(normalizeOri(o)))
                        : false;

                    if (!skipRedirect && allCompleted && adminDataUrl) {
                        if (buttonRef) {
                            buttonRef.disabled = true;
                            buttonRef.innerText = 'Semua arah tersimpan';
                        }
                        sessionStorage.setItem('showSuccessModal', successMessage || 'Semua arah tersimpan');
                        window.location.href = adminDataUrl;
                    } else {
                        await Swal.fire('Berhasil', successMessage, 'success');
                    }
                    return { success: true, data };
                }

                Swal.fire({
                    title: 'Gagal!',
                    text: data.message || 'Gagal menyimpan data.',
                    icon: 'error',
                    confirmButtonText: 'Coba Lagi'
                });
                return { success: false, data };

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
