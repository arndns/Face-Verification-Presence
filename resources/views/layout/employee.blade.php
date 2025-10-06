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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    @vite(['resources/scss/app.scss', 'resources/js/app.js', 'resources/js/employee.js', 'resources/css/employee.css', 'resources/css/app.css'])
</head>

<body>

    {{-- loader content  --}}
    <div id="loader">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    @yield('header')

    <!-- Konten Halaman (Akan berubah-ubah) -->
    <main id="main-content">
        @yield('content')
    </main>



    {{-- menu footer --}}
    <div class="app-bottom-menu">

        <a href="/employee" class="item {{ request()->is('employee') ? 'active' : '' }}">
            <i class="fa-solid fa-home"></i>
            <strong>Home</strong>
        </a>
        <a href="#" class="item">
            <i class="fa-solid fa-wallet"></i>
            <strong>Pendapatan</strong>
        </a>

        <a href="{{ route('employee.camera') }}" class="item camera-col">
            <i class="fa-solid fa-camera"></i>
            <strong>Camera</strong>
        </a>
        <a href="#" class="item">
            <i class="fa-solid fa-file-alt"></i>
            <strong>Docs</strong>
        </a>
        <a href="#" class="item">
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
