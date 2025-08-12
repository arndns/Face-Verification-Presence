<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon icon -->
    <link rel="icon" href="{{ asset('assets/image/nuansa-laras-icon.ico') }}" type="image/x-icon">
    <!-- Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">

    <title>Owner</title>
    @vite(['resources/css/admin.css', 'resource/js/app.js', 'resources/sass/app.scss', 'resources/js/admin.js' ])
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="d-flex align-items-center text-decoration-none text-white">
                    <img src="{{ asset('assets/image/nuansa-laras-icon.ico') }}" alt="Logo" height="40"
                        class="rounded">
                    <h5 class="ms-2 mb-0 fa-fs">Owner</h5>
                </a>
                <button type="button" id="sidebarClose" class="btn-close btn-close-white ms-auto"
                    aria-label="Close"></button>
            </div>

            <ul class="list-unstyled components">
                <li class="active"><a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-box-open"></i> Produk</a></li>
                <li><a href="#"><i class="fas fa-shopping-cart"></i> Pesanan</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Pelanggan</a></li>
                <li><a href="#"><i class="fas fa-chart-pie"></i> Laporan</a></li>
                <li><a href="#"><i class="fas fa-cogs"></i> Pengaturan</a></li>
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
                <!-- Kartu Statistik -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Pegawai</h5>
                                    <p class="card-text fs-4 fw-bold">20</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Tepat Waktu</h5>
                                    <p class="card-text fs-4 fw-bold">1,250</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card bg-danger text-white">
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Terlambat</h5>
                                    <p class="card-text fs-4 fw-bold">8,540</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Tidak Hadir</h5>
                                    <p class="card-text fs-4 fw-bold">25</p>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-xmark"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="row">
                    <div class="col-12">
                        <div class="table-container">
                            <h4 class="mb-4">Pesanan Terbaru</h4>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID Pesanan</th>
                                            <th>Pelanggan</th>
                                            <th>Produk</th>
                                            <th>Total Harga</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">#INV12345</th>
                                            <td>Budi Santoso</td>
                                            <td>Laptop Pro X</td>
                                            <td>Rp 15.000.000</td>
                                            <td><span class="badge bg-success">Selesai</span></td>
                                            <td><button class="btn btn-sm btn-outline-primary"><i
                                                        class="fas fa-eye"></i></button></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">#INV12346</th>
                                            <td>Citra Lestari</td>
                                            <td>Smartphone G-11</td>
                                            <td>Rp 5.250.000</td>
                                            <td><span class="badge bg-info">Dikirim</span></td>
                                            <td><button class="btn btn-sm btn-outline-primary"><i
                                                        class="fas fa-eye"></i></button></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">#INV12347</th>
                                            <td>Ahmad Yani</td>
                                            <td>Headphone Bass</td>
                                            <td>Rp 850.000</td>
                                            <td><span class="badge bg-warning text-dark">Tertunda</span></td>
                                            <td><button class="btn btn-sm btn-outline-primary"><i
                                                        class="fas fa-eye"></i></button></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">#INV12348</th>
                                            <td>Dewi Anggraini</td>
                                            <td>Keyboard Mekanikal</td>
                                            <td>Rp 1.200.000</td>
                                            <td><span class="badge bg-danger">Dibatalkan</span></td>
                                            <td><button class="btn btn-sm btn-outline-primary"><i
                                                        class="fas fa-eye"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
          <div class="sidebar-backdrop"></div>
    </div>

</body>

</html>
