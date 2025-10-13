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
                class="w-100 btn btn-primary btn-lg fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-3">
                <i class="fa-solid fa-camera fa-lg"></i>
                <span id="button-text">Presensi Masuk</span>
            </button>
        </div>
        <div class="w-100 mb-4">
            <button id="takeattandance"
                class="w-100 btn btn-danger btn-lg fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-3">
                <i class="fa-solid fa-camera fa-lg"></i>
                <span id="button-text">Presensi Pulang</span>
            </button>
        </div>
    </div>
    <div id="face-root" data-models="{{ asset('models') }}"></div>
@endsection
@section('style')
    <style>
        canvas {
            position: absolute;
        }
    </style>

@endsection
@section('script')
    <script>
        //fungsi untuk memuat kooridinat
        document.addEventListener("DOMContentLoaded", () => {
            const locationInput = document.getElementById("location");

            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        locationInput.value = `${latitude},${longitude}`;
                        console.log("Lokasi berhasil didapat:", locationInput.value);
                    },
                    (error) => {
                        console.warn("Gagal mendapatkan lokasi:", error.message);
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                locationInput.value = "Izin lokasi ditolak.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                locationInput.value = "Lokasi tidak tersedia.";
                                break;
                            case error.TIMEOUT:
                                locationInput.value = "Permintaan lokasi kadaluarsa.";
                                break;
                            default:
                                locationInput.value = "Terjadi kesalahan.";
                        }
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                locationInput.value = "Browser tidak mendukung geolokasi.";
            }
        });

        document.addEventListener("DOMContentLoaded", init);

        async function init() {
            await ensureFaceAPI();
            await loadFaceModels();
            startCamera();
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
            const base = "https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights";
            await Promise.all([
                faceapi.nets.mtcnn.loadFromUri(base),
                faceapi.nets.faceLandmark68Net.loadFromUri(base),
                faceapi.nets.faceExpressionNet.loadFromUri(base),
            ]);
        }

        /* =================== CAMERA + OVERLAY =================== */
        function startCamera() {
            const wrap = document.querySelector(".camera-capture");
            if (!wrap) return;
            wrap.querySelector("span")?.remove(); // hapus teks "Memuat Kamera..."

            // Pasang stream (tampilan responsif diatur lewat CSS & JS)
            Webcam.set({
                width: 720,
                height: 520,
                image_format: "jpeg",
                jpeg_quality: 90
            });

            Webcam.on("live", () => {
                const video = wrap.querySelector("video");
                if (!video) return;

                // Paksa video memenuhi kotak (override CSS !important jika ada)
                forceVideoFill(video);

                // Buat/ambil canvas overlay dan samakan mirror dengan video
                const overlay = ensureOverlay(wrap);

                // Sinkronkan ukuran canvas ke ukuran kontainer (retina aware)
                const sync = () => syncCanvasToBox(overlay, wrap);
                sync();
                new ResizeObserver(sync).observe(wrap);

                // Mulai deteksi setelah metadata tersedia
                if (video.readyState >= 2) runDetect(video, overlay, wrap);
                else video.addEventListener("loadedmetadata", () => runDetect(video, overlay, wrap), {
                    once: true
                });
            });

            Webcam.on("error", err => {
                console.error("Webcam error:", err);
                wrap.insertAdjacentHTML("beforeend",
                    "<span class='text-danger'>Tidak dapat mengakses kamera.</span>");
            });

            Webcam.attach(".camera-capture");
        }

        function forceVideoFill(video) {
            video.setAttribute("playsinline", "");
            video.style.setProperty('position', 'absolute', 'important');
            video.style.setProperty('top', '0', 'important');
            video.style.setProperty('left', '0', 'important');
            video.style.setProperty('width', '100%', 'important');
            video.style.setProperty('height', '100%', 'important');
            video.style.setProperty('object-fit', 'cover', 'important'); // sesuai CSS kamu
            video.style.setProperty('background', '#000', 'important');
            // Video kamu sudah dimirror via CSS .camera-capture video { transform: scaleX(-1) }
        }

        function ensureOverlay(wrap) {
            let c = wrap.querySelector("#overlay");
            if (!c) {
                c = document.createElement("canvas");
                c.id = "overlay";
                wrap.appendChild(c);
            }
            // Overlay ikut mirror supaya box tepat di wajah
            c.style.position = "absolute";
            c.style.top = 0;
            c.style.left = 0;
            c.style.width = "100%";
            c.style.height = "100%";
            c.style.pointerEvents = "none";
            c.style.zIndex = 2;
            c.style.transform = "scaleX(-1)";
            c.style.webkitTransform = "scaleX(-1)";
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
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0); // gambar pakai unit CSS pixel
        }

        /* =================== DETECTION LOOP =================== */
        function runDetect(video, canvas, box) {
            const ctx = canvas.getContext("2d");
            const opts = new faceapi.MtcnnOptions(); // default OK

            async function loop() {
                if (video.readyState >= 2) {
                    // Gunakan ukuran kontainer agar koordinat pas dengan tampilan
                    const drawSize = {
                        width: box.clientWidth,
                        height: box.clientHeight
                    };

                    const res = await faceapi
                        .detectAllFaces(video, opts)
                        .withFaceLandmarks()
                        .withFaceExpressions();

                    ctx.clearRect(0, 0, drawSize.width, drawSize.height);

                    if (res.length) {
                        const r = faceapi.resizeResults(res, drawSize);
                        faceapi.draw.drawDetections(canvas, r);
                        faceapi.draw.drawFaceLandmarks(canvas, r);
                        faceapi.draw.drawFaceExpressions(canvas, r, 0.05);
                    }
                }
                requestAnimationFrame(loop);
            }
            loop();
        }
    </script>
@endsection
