<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>

    <!-- Favicon icon -->
    <link rel="icon" href="{{ asset('assets/image/nuansa-laras-icon.ico') }}" type="image/x-icon">
    <!-- Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
      <!-- amCharts 4 JS -->
    <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

    @vite(['resource/js/app.js', 'resources/sass/app.scss', 'resources\css\employee.css', 'resources/js/employee.js'])
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Navbar Atas -->
    <header class="navbar bg-white shadow-sm sticky-top z-3">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <!-- Ganti ikon dengan gambar logo -->
                <img src="{{ asset('assets/image/nuansa-laras.png') }}" alt="Logo UMKM"
                    style="height: 45px;" class="me-2">
            </a>
            <form action="{{ route('logout') }}" method="POST" id="logout-form">
                @csrf
                <a href="{{ route('logout') }}" class="btn btn-danger d-flex align-items-center"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>
                    <span>Keluar</span>
                </a>
            </form>
        </div>
    </header>


    <!-- Konten Halaman (Akan berubah-ubah) -->
    @yield('content')
        <!-- Navigasi Bawah (Terlihat di semua ukuran layar) -->
    <nav class="navbar fixed-bottom bg-white shadow-lg bottom-nav">
        <div class="container-fluid d-flex justify-content-around">
            <a href="{{route('employee.index')}}" class="text-center text-decoration-none text-muted small active">
                <i class="fa-solid fa-house fs-4"></i>
                <span class="d-block" style="font-size: 0.7rem;">Home</span>
            </a>
            <a href="#" class="text-center text-decoration-none text-muted small">
                <i class="fa-solid fa-file-lines fs-4"></i>
                <span class="d-block" style="font-size: 0.7rem;">Laporan</span>
            </a>
            <a href="#" class="text-center text-decoration-none text-muted small">
                <i class="fa-solid fa-calendar-plus fs-4"></i>
                <span class="d-block" style="font-size: 0.7rem;">Pengajuan</span>
            </a>
            <a href="{{route('employee.camera')}}" class="text-center text-decoration-none text-muted small">
                <i class="fa-solid fa-camera fs-4"></i>
                <span class="d-block" style="font-size: 0.7rem;">Kamera</span>
            </a>
            <a href="#" class="text-center text-decoration-none text-muted small">
                <i class="fa-solid fa-wallet fs-4"></i>
                <span class="d-block" style="font-size: 0.7rem;">Pendapatan</span>
            </a>
            <a href="#" class="text-center text-decoration-none text-muted small">
                <i class="fa-solid fa-user fs-4"></i>
                <span class="d-block" style="font-size: 0.7rem;">Profil</span>
            </a>
        </div>
    </nav>

   
</body>



</html>
