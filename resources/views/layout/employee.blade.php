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


    @vite(['resource/js/app.js', 'resources/sass/app.scss', 'resources\css\employee.css', 'resources/js/employee.js'])
</head>

<body>

    {{-- loader content  --}}
    <div id="loader">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <!-- Konten Halaman (Akan berubah-ubah) -->
    <main id="main-content">
        @yield('content')
    </main>
    
    

    {{-- menu footer --}}
    <div class="app-bottom-menu">

        <a href="{{route('employee.index')}}" class="item active">
            <i class="fa-solid fa-home"></i>
            <strong>Home</strong>
        </a>
        <a href="#" class="item">
            <i class="fa-solid fa-wallet"></i>
            <strong>Pendapatan</strong>
        </a>
        
        <a href="{{route('employee.camera')}}" class="item camera-col">
            <i class="fa-solid fa-camera"></i>
            <strong>Camera</strong>
        </a>
        <a href="#" class="item">
            <i class="fa-solid fa-file-alt"></i>
            <strong>Docs</strong>
        </a>
        <a href="#"  class="item">
            <i class="fa-solid fa-users"></i>
            <strong>Profile</strong>
        </a>
    </div>

</body>



</html>

