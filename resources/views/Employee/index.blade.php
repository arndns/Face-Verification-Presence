<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Presensi Pegawai</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Leaflet CSS (for map) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <style>
        /* Blue to Orange Gradient Color Scheme */
        :root {
            --custom-primary: #0D6EFD;
            /* Bootstrap Blue */
            --custom-secondary: #FF8C00;
            /* Dark Orange */
            --sidebar-width: 260px;
        }

        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
            /* Prevent horizontal scroll */
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            background: linear-gradient(180deg, var(--custom-primary) 0%, var(--custom-secondary) 100%);
            transition: left 0.3s ease-in-out;
        }

        .sidebar.collapsed {
            left: calc(-1 * var(--sidebar-width));
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: bold;
        }

        .sidebar .nav-link i {
            margin-right: 1rem;
            width: 20px;
            /* To align text */
        }

        .sidebar-header {
            padding: 1rem 1.5rem;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 0;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
            height: 100vh;
            overflow-y: auto;
            /* Make main content scrollable */
        }

        .main-content.collapsed {
            margin-left: 0;
            width: 100%;
        }

        /* UPDATED: Topbar for mobile and desktop toggle */
        .topbar {
            display: flex !important;
            padding: 1rem;
            /* Adjusted padding to match sidebar header */
            align-items: center;
            position: sticky;
            /* Make topbar sticky */
            top: 0;
            z-index: 1021;
            /* Below sidebar, above content */
        }

        .action-card {
            border: none;
            background: linear-gradient(135deg, var(--custom-primary) 0%, var(--custom-secondary) 100%);
            color: white;
        }

        .action-card #live-clock {
            font-size: 3.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .action-card #current-date {
            color: rgba(255, 255, 255, 0.8);
        }

        .action-card #status-badge {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-action {
            padding: 15px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        #video-preview {
            width: 100%;
            max-width: 320px;
            border-radius: 8px;
            transform: scaleX(-1);
        }

        #photo-canvas {
            display: none;
        }

        #map-preview {
            height: 200px;
            width: 100%;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .achievement-badge {
            text-align: center;
        }

        .achievement-badge i {
            font-size: 2.5rem;
            padding: 10px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #adb5bd;
            transition: all 0.3s ease;
        }

        .achievement-badge.unlocked i {
            background-color: #FFC107;
            color: white;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.7);
        }

        .achievement-badge small {
            font-size: 0.75rem;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-0 shadow">
        <!-- Sidebar Header with Close Button -->
        <div class="sidebar-header d-flex justify-content-between align-items-center">
            <a class="sidebar-brand d-flex align-items-center text-decoration-none" href="#">
                <img src="{{ asset('assets/image/nuansa-laras.png') }}" alt="Logo Perusahaan" style="height: 55px;">
            </a>
            <button class="btn text-white sidebar-toggle">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#"><i
                        class="fa-solid fa-table-cells-large"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fa-solid fa-user"></i> Profil Saya</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fa-solid fa-calendar-days"></i> Riwayat</a>
            </li>
        </ul>
        <div class="p-3">
            <form action="{{ route('logout') }}" method="POST" id="logout-form">
                @csrf
                <a class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    href="{{ route('logout') }}"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </form>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content">
        <!-- Topbar with Toggle Button -->
        <nav class="navbar bg-light shadow-sm topbar">
            <div class="container-fluid">
                <button class="btn btn-primary sidebar-toggle" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="navbar-brand mb-0 h1 d-none d-sm-block">Dashboard Pegawai</span>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container-fluid p-4">
            <!-- Profile Row (Enlarged and Centered) -->
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <img src="https://placehold.co/150x150/EFEFEF/333333?text=Foto" class="rounded-circle mb-3"
                                style="width: 120px; height: 120px; object-fit: cover; border: 4px solid var(--custom-primary);">
                            <div>
                                <h5 class="card-title mb-1" id="dynamic-greeting">Selamat Sore,</h5>
                                <p class="card-text fs-4 fw-bold text-dark mb-2">Budi Santoso</p>
                                <span
                                    class="badge bg-primary-subtle text-primary-emphasis rounded-pill fs-6 px-3 py-2">Manajer
                                    Pemasaran</span>
                                <p class="card-text text-muted mt-2 mb-0">NIK: 3271234567890001</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Action Card (Clock & Buttons) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card action-card text-center shadow-lg">
                        <div class="card-body p-4 p-md-5">
                            <p class="mb-2" id="current-date">Kamis, 7 Agustus 2025</p>
                            <h1 id="live-clock">17:30:00</h1>
                            <p class="mb-4">Status:
                                <span class="badge fs-6 rounded-pill" id="status-badge">Sudah Absen Masuk</span>
                            </p>
                            <div class="d-grid gap-2 col-md-6 col-lg-4 mx-auto">
                                <button class="btn btn-light btn-action d-none" id="btn-clock-in" data-bs-toggle="modal"
                                    data-bs-target="#selfieModal">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Absen Masuk
                                </button>
                                <button class="btn btn-light btn-action" id="btn-clock-out" data-bs-toggle="modal"
                                    data-bs-target="#selfieModal">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Absen Pulang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart, Achievements, and Other Info Row -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fa-solid fa-chart-pie me-2"></i> Ringkasan Bulanan</h5>
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <canvas id="attendanceChart" style="max-height: 220px;"></canvas>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fa-solid fa-trophy me-2"></i> Prestasi & Lencana</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4 achievement-badge unlocked" title="Kehadiran Sempurna Bulan Ini">
                                    <i class="fa-solid fa-award"></i>
                                    <small>Sempurna</small>
                                </div>
                                <div class="col-4 achievement-badge unlocked" title="Tepat Waktu 5x Berturut-turut">
                                    <i class="fa-solid fa-bolt"></i>
                                    <small>Anti Telat</small>
                                </div>
                                <div class="col-4 achievement-badge" title="Rajin Lembur">
                                    <i class="fa-solid fa-star"></i>
                                    <small>Pekerja Keras</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fa-solid fa-paper-plane me-2"></i> Pengajuan</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary"><i class="fa-solid fa-calendar-plus me-2"></i>
                                    Ajukan Cuti</button>
                                <button class="btn btn-outline-secondary"><i
                                        class="fa-solid fa-clock-rotate-left me-2"></i> Lapor Lembur</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Selfie & Confirmation -->
    <div class="modal fade" id="selfieModal" tabindex="-1" aria-labelledby="selfieModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selfieModalLabel">Konfirmasi Presensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted">Posisikan wajah Anda dan pastikan lokasi akurat.</p>
                    <video id="video-preview" autoplay playsinline></video>
                    <canvas id="photo-canvas"></canvas>
                    <div id="map-preview" class="bg-light d-flex align-items-center justify-content-center">
                        <div id="location-info">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">Mendeteksi lokasi GPS...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="submit-attendance" disabled>
                        <i class="fa-solid fa-camera me-2"></i> Kirim Presensi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <!-- Leaflet JS (for map) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // DOM Elements
            const clockElement = document.getElementById('live-clock');
            const dateElement = document.getElementById('current-date');
            const greetingElement = document.getElementById('dynamic-greeting');
            const selfieModal = new bootstrap.Modal(document.getElementById('selfieModal'));
            const videoElement = document.getElementById('video-preview');
            const canvasElement = document.getElementById('photo-canvas');
            const submitButton = document.getElementById('submit-attendance');
            const modalElement = document.getElementById('selfieModal');
            const mapContainer = document.getElementById('map-preview');
            const locationInfo = document.getElementById('location-info');

            let cameraStream;
            let map;

            // --- Dynamic Greeting ---
            function updateGreeting() {
                const hour = new Date().getHours();
                if (hour < 11) {
                    greetingElement.textContent = 'Selamat Pagi,';
                } else if (hour < 15) {
                    greetingElement.textContent = 'Selamat Siang,';
                } else if (hour < 19) {
                    greetingElement.textContent = 'Selamat Sore,';
                } else {
                    greetingElement.textContent = 'Selamat Malam,';
                }
            }

            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                }).replace(/\./g, ':');
                const dateString = now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                clockElement.textContent = timeString;
                dateElement.textContent = dateString;
            }
            setInterval(updateClock, 1000);
            updateClock();
            updateGreeting(); // Call greeting function on load

            function showMap(lat, lon) {
                locationInfo.style.display = 'none';
                mapContainer.style.display = 'block';

                if (map) {
                    map.setView([lat, lon], 17);
                } else {
                    map = L.map('map-preview').setView([lat, lon], 17);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);
                }

                L.marker([lat, lon]).addTo(map)
                    .bindPopup('Lokasi Anda saat ini.')
                    .openPopup();
            }

            modalElement.addEventListener('show.bs.modal', async () => {
                submitButton.disabled = true;
                locationInfo.style.display = 'flex';
                if (map) {
                    map.remove();
                    map = null;
                }

                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user'
                        },
                        audio: false
                    });
                    videoElement.srcObject = cameraStream;
                } catch (err) {
                    console.error("Error accessing camera: ", err);
                    alert('Tidak dapat mengakses kamera. Pastikan Anda memberikan izin pada browser.');
                    selfieModal.hide();
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const {
                            latitude,
                            longitude
                        } = position.coords;
                        showMap(latitude, longitude);
                        submitButton.dataset.latitude = latitude;
                        submitButton.dataset.longitude = longitude;
                        submitButton.disabled = false;
                    },
                    (error) => {
                        console.error("Error getting location: ", error);
                        locationInfo.innerHTML =
                            `<i class="fa-solid fa-circle-xmark text-danger fs-3"></i><p class="mt-2 mb-0">Gagal mendapatkan lokasi.</p>`;
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });

            modalElement.addEventListener('hidden.bs.modal', () => {
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                }
                if (map) {
                    map.remove();
                    map = null;
                }
            });

            submitButton.addEventListener('click', () => {
                const context = canvasElement.getContext('2d');
                canvasElement.width = videoElement.videoWidth;
                canvasElement.height = videoElement.videoHeight;
                context.translate(canvasElement.width, 0);
                context.scale(-1, 1);
                context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

                const photoDataUrl = canvasElement.toDataURL('image/jpeg');
                const latitude = submitButton.dataset.latitude;
                const longitude = submitButton.dataset.longitude;

                console.log("Submitting data to server...");
                console.log("Photo (Data URL):", photoDataUrl.substring(0, 40) + '...');
                console.log("Latitude:", latitude);
                console.log("Longitude:", longitude);

                alert('Presensi berhasil direkam! (Ini adalah simulasi frontend)');
                selfieModal.hide();
                updateUIAfterAttendance();
            });

            function updateUIAfterAttendance() {
                const clockInBtn = document.getElementById('btn-clock-in');
                const clockOutBtn = document.getElementById('btn-clock-out');
                const statusBadge = document.getElementById('status-badge');

                if (!clockInBtn.classList.contains('d-none')) {
                    clockInBtn.classList.add('d-none');
                    clockOutBtn.classList.remove('d-none');
                    statusBadge.textContent = 'Sudah Absen Masuk';
                } else {
                    clockOutBtn.disabled = true;
                    clockOutBtn.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i> Selesai Hari Ini';
                    statusBadge.textContent = 'Sudah Absen Pulang';
                }
            }

            // --- SCRIPT FOR ATTENDANCE CHART ---
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            const attendanceChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit'],
                    datasets: [{
                        label: 'Hari',
                        data: [22, 3, 1, 0], // Sample data
                        backgroundColor: [
                            'rgba(13, 110, 253, 0.8)', // Blue (Hadir)
                            'rgba(255, 193, 7, 0.8)', // Warning (Terlambat)
                            'rgba(13, 202, 240, 0.8)', // Info (Izin)
                            'rgba(220, 53, 69, 0.8)' // Danger (Sakit)
                        ],
                        borderColor: '#f8f9fa',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: false,
                        }
                    }
                }
            });

            // --- UPDATED AND FIXED: SIDEBAR TOGGLE SCRIPT ---
            const sidebarToggles = document.querySelectorAll('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            // Unified function to toggle the sidebar
            const toggleSidebar = () => {
                sidebar.classList.toggle('collapsed');

                // On desktop, we also toggle the main content margin
                if (window.innerWidth >= 992) {
                    mainContent.classList.toggle('collapsed');
                }
            };

            // Attach the same click event to all toggle buttons
            if (sidebarToggles.length > 0) {
                sidebarToggles.forEach(toggle => {
                    toggle.addEventListener('click', toggleSidebar);
                });
            }

            // Function to set the correct initial state based on screen size
            const setInitialLayout = () => {
                if (window.innerWidth < 992) {
                    // On mobile, ensure sidebar starts hidden
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('collapsed');
                } else {
                    // On desktop, ensure sidebar starts open
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('collapsed');
                }
            };

            // Set the layout on initial load and on window resize
            setInitialLayout();
            window.addEventListener('resize', setInitialLayout);

        });
    </script>
</body>

</html>
