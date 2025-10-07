<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
    <link rel="icon" href="{{ asset('assets/image/nuansa-laras-icon.ico') }}" type="image/x-icon">
    <title>Login</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js', 'resources/css/login.css', 'resources/js/login.js', 'resources/css/app.css'])
</head>

<body>
    <div class="container">

        <div class="row login-container">
            
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <div class="card login-card">
                    <div class="card-body p-4 p-sm-5">

                        <!-- Logo dan Judul -->
                        <div class="text-center mb-4">
                            <img src="{{ asset('assets/image/nuansa-laras.png') }}" alt="Logo UMKM"
                                class="img-fluid d-block mx-auto mb-3" style="max-height: 90px;">
                            <h3 class="mb-1 fw-bold">Selamat Datang Kembali</h3>
                            <p class="text-muted">Silakan masukkan NIK dan password</p>
                        </div>
                        <!-- Form Login -->
                        <form id="loginForm" class="needs-validation" novalidate action="{{ route('login') }}"
                            method="POST">
                            @csrf
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Oops! Terjadi Kesalahan:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            <!-- Input Email -->
                            <div class="form-floating mb-3">
                                <input type="username"
                                    class="form-control {{ $errors->has('username') ? 'error' : '' }}" type="text"
                                    id="username" name="username" value="{{ old('username') }}"
                                    placeholder="Masukkan NIK" autocomplete="username" required>
                                <label for="username"><i class="fa-solid  fa-envelope me-2"></i>NIK</label>
                            </div>

                            <!-- Input Password dengan Tombol Toggle -->
                            <div class="input-group mb-3">
                                <div class="form-floating flex-grow-1">
                                    <input type="password"
                                        class="form-control {{ $errors->has('password') ? 'error' : '' }}"
                                        id="password" placeholder="Password" name="password" required
                                        placeholder="Masukkan password" autocomplete="current-password">
                                    <label for="password"><i class="fa-solid fa-lock me-2"></i>Password</label>
                                </div>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                    aria-label="Tampilkan atau sembunyikan password">
                                    <i class="fa-solid fa-eye-slash"></i>
                                </button>
                            </div>
                            <!-- Tombol Submit -->
                            <div class="d-grid  mb-4">
                                <button class="btn btn-primary btn-lg fw-bold" type="submit">Masuk</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>




</body>

</html>
