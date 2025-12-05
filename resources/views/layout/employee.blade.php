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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    @vite([ 'resources/js/employee.js', 'resources/css/employee.css', 'resources/css/app.css'])
</head>

<body class="employee-app">

    {{-- loader content  --}}
    <div id="loader">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div class="app-shell">
        @yield('header')

        <!-- Konten Halaman (Akan berubah-ubah) -->
        <main id="main-content" class="app-main">
            @yield('content')
        </main>
    </div>

    @yield('script')

    
    @yield('style')


    {{-- menu footer --}}
    <div class="app-bottom-menu">

        <a href="/employee/dashboard" class="item {{ request()->is('employee') ? 'active' : '' }}">
            <i class="fa-solid fa-home"></i>
            <strong>Home</strong>
        </a>
        <a href="{{ route('employee.permit.history') }}" class="item">
            <i class="fa-solid fa-calendar-days"></i>
            <strong>Ajukan Cuti</strong>
        </a>

        @if($approvedPermitToday)
            <div class="item camera-col" style="opacity: 0.5; cursor: not-allowed;">
                <div class="d-flex justify-content-center align-items-center" style="background-color: #9ca3af;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3"></path>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"></path>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"></path>
                        <path d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
                        <path d="M9 10v2"></path>
                        <path d="M15 10v2"></path>
                        <path d="M12 14c-1 0-1.5.5-1.5 1s.5 1 1.5 1 1.5-.5 1.5-1-.5-1-1.5-1z"></path>
                        <path d="M9 17c1.5 1 4.5 1 6 0"></path>
                    </svg>
                </div>
                <strong>Izin/Cuti</strong>
            </div>
        @else
            <a href="{{ route('employee.camera') }}" class="item camera-col" id="bottom-nav-presence">
                <div class="d-flex justify-content-center align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3"></path>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"></path>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"></path>
                        <path d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
                        <path d="M9 10v2"></path>
                        <path d="M15 10v2"></path>
                        <path d="M12 14c-1 0-1.5.5-1.5 1s.5 1 1.5 1 1.5-.5 1.5-1-.5-1-1.5-1z"></path>
                        <path d="M9 17c1.5 1 4.5 1 6 0"></path>
                    </svg>
                </div>
                <strong>Presensi</strong>
            </a>
        @endif
        <a href="{{route('employee.presence.history')}}" class="item">
            <i class="fa-solid fa-history"></i>
            <strong>History</strong>
        </a>
        <a href="{{ route('employee.profile') }}" class="item">
            <i class="fa-solid fa-users"></i>
            <strong>Profile</strong>
        </a>
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
                                Terjadi Kesalahan
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Pesan error Anda akan ditampilkan di sini --}}
                            {{ session('error') }}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                
            </script>
        @endif

</body>



</html>
