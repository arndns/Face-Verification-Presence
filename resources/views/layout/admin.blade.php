<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon icon -->
    <link rel="icon" href="{{ asset('assets/image/nuansa-laras-icon.ico') }}" type="image/x-icon">
    <!-- Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
    <script src="{{asset('assets/js/bootstrap.bundle.min.js')}}"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>


    <title>@yield('title','Dashboard')ADMIN</title>
    @vite(['resources/css/admin.css', 'resource/js/app.js', 'resources/sass/app.scss', 'resources/js/admin.js'])
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="d-flex align-items-center text-decoration-none text-white">
                    <img src="{{ asset('assets/image/nuansa-laras-icon.ico') }}" alt="Logo" height="40"
                        class="rounded">
                    <h5 class="ms-2 mb-0 fa-fs">ADMIN PANEL</h5>
                </a>
                <button type="button" id="sidebarClose" class="btn-close btn-close-white ms-auto"
                    aria-label="Close"></button>
            </div>

            <ul class="list-unstyled components">
                <li><a href="{{route('admin.index')}}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="{{ route('admin.data') }}"><i class="fas fa-users"></i> Data Pegawai</a></li>
                <li><a href="#"><i class="fas fa-chart-pie"></i> Riwayat Presensi</a></li>
            </ul>

            <ul class="list-unstyled CTAs">
                <li>
                    <form id="logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="logout bg-danger text-white text-center d-block m-3 rounded p-2 text-decoration-none">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Konten Utama (termasuk Navbar) -->
        <div id="content">
            <!-- Navbar yang kini berfungsi sebagai pembatas -->
            <nav class="navbar navbar-expand-lg main-navbar">
                <div class="container-fluid p-0">
                    <button type="button" id="sidebarCollapse" class="btn btn-light rounded-circle p-2">
                        <img src="{{ asset('assets/image/nuansa-laras-icon.ico') }}" alt="Menu"
                            style="width: 28px; height: 28px; border-radius: 4px;">
                    </button>
                </div>
            </nav>
            


            <!-- Area Konten Halaman Anda -->
            <main class="main-content-area">
                @yield('content')
            </main>
        </div>
        <div class="sidebar-backdrop"></div>
    </div>

</body>

</html>
