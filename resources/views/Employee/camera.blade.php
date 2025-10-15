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

        function startCamera() {
            const wrap = document.querySelector(".camera-capture");
            if (!wrap) return;
            wrap.querySelector("span")?.remove();

            Webcam.set({
                width: 720,
                height: 520,
                image_format: "jpeg",
                jpeg_quality: 90
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

            Webcam.attach(".camera-capture");
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


        function runDetect(video, canvas, box) {
            const ctx = canvas.getContext('2d');
            const opts = new faceapi.MtcnnOptions();
            const dpr = window.devicePixelRatio || 1;

            
            const topExpression = (exp) => {
                let best = {
                    k: 'neutral',
                    v: 0
                };
                for (const k in exp)
                    if (exp[k] > best.v) best = {
                        k,
                        v: exp[k]
                    };
                return `${best.k} ${(best.v*100).toFixed(0)}%`;
            };

            async function loop() {
                if (video.readyState >= 2) {
                    const drawSize = {
                        width: box.clientWidth,
                        height: box.clientHeight
                    };

                    
                    const det = await faceapi
                        .detectAllFaces(video, opts)
                        .withFaceLandmarks()
                        .withFaceExpressions();

                    
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    
                    ctx.setTransform(-dpr, 0, 0, dpr, canvas.width, 0); 
                    const r = faceapi.resizeResults(det, drawSize);

                    
                    ctx.lineWidth = 2;
                    ctx.strokeStyle = '#4FC3F7';
                    for (const d of r) {
                        const b = d.detection.box;
                        ctx.strokeRect(b.x, b.y, b.width, b.height);
                    }
                    
                    faceapi.draw.drawFaceLandmarks(canvas, r);
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0); 
                    ctx.font = '14px sans-serif';
                    ctx.fillStyle = '#00E676';
                    ctx.strokeStyle = 'rgba(0,0,0,.55)';
                    ctx.lineWidth = 3;

                    for (const d of r) {
                        // --- Ekspresi (teks normal, tak dibalik) ---
                        const expText = topExpression(d.expressions);

                       
                        const score = (d.detection?.score ?? 0);
                        const mtcnnText = `score ${(score*100).toFixed(0)}%`;
                        const b = d.detection.box;
                        const mirroredX = drawSize.width - (b.x + b.width);
                        const tx = Math.max(4, mirroredX + 4);
                        const ty1 = Math.max(16, b.y - 8); 
                        const ty2 = Math.min(drawSize.height - 6, b.y + 16); 

                        ctx.strokeText(expText, tx, ty1);
                        ctx.fillText(expText, tx, ty1);

                        ctx.strokeText(mtcnnText, tx, ty2);
                        ctx.fillText(mtcnnText, tx, ty2);
                    }

                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                }
                requestAnimationFrame(loop);
            }
            loop();
        }
    </script>
@endsection
