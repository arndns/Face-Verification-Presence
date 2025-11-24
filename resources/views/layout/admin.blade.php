<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSRF Token untuk AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon icon -->
    <link rel="icon" href="{{ asset('assets/image/nuansa-laras-icon.ico') }}" type="image/x-icon">
    <!-- Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])

    <title>@yield('title', 'Dashboard')</title>
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
                <li><a href="{{ route('admin.index') }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="{{ route('admin.data') }}"><i class="fas fa-users"></i> Data Pegawai</a></li>
                <li><a href="{{ route('location.index') }}"><i class="fas fa-map-location-dot"></i> Lokasi Kantor</a></li>
                <li><a href="{{ route('shifts.index') }}"><i class="fas fa-business-time"></i> Pengaturan Shift</a></li>
                <li><a href="{{ route('admin.permit.index') }}"><i class="fas fa-calendar-check"></i> Pengajuan Cuti</a></li>
                <li><a href="{{ route('admin.presence.history') }}"><i class="fas fa-history"></i> Riwayat Presensi</a></li>
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

        <!-- Konten Utama -->
        <div class="content-wrapper">
            <div id="admin-toggle-bar">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" id="sidebarCollapse" class="navbar-toggle-btn">
                        <img src="{{ asset('assets/image/nuansa-laras-icon.ico') }}" alt="Menu"
                            style="width: 40px; height: 40px; border-radius: 8px;">
                    </button>
                </div>
            </div>
            <div id="content">
                <!-- Area Konten Halaman Anda -->
                <main class="main-content-area">
                    @yield('content')
                </main>
                @yield('script')

                @yield('style')
            </div>
        </div>
        <div class="sidebar-backdrop"></div>
    </div>
    @if (session('error'))
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="errorModalLabel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16" style="margin-right: 8px;">
                                <path
                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                            Mohon Maaf
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{ session('error') }}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <script></script>
    @endif

</body>

</html>
