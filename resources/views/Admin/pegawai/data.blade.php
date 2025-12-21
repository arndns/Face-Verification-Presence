@extends('layout.admin')
@section('title', 'Data Pegawai')
@section('style')
    <style>
        .btn-no-hover:hover,
        .btn-no-hover:focus,
        .btn-no-hover:active {
            color: var(--bs-primary) !important;
            background-color: transparent !important;
            border-color: var(--bs-primary) !important;
            text-decoration: none !important;
            box-shadow: none !important;
        }
        .btn-link-no-hover,
        .btn-link-no-hover:hover,
        .btn-link-no-hover:focus,
        .btn-link-no-hover:active {
            color: #6c757d !important;
            background-color: transparent !important;
            text-decoration: none !important;
            box-shadow: none !important;
        }
    </style>
@endsection
@section('content')
    <!-- Tabel Data -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <!-- Menampilkan Pesan SUKSES dengan Alert Bootstrap -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Berhasil!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @elseif (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Peringatan!</strong> {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @elseif (session('danger') || session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-x-circle-fill me-2"></i>
                        <strong>Gagal!</strong> {{ session('danger') ?? session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="table-container">
                    <h4 class="mb-4">Data Pegawai</h4>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <a href="{{ route('admin.create') }}" class="btn btn-primary">Tambah Data</a>
                        <form action="{{ route('admin.data') }}" method="GET" class="search-form ms-auto d-flex align-items-center gap-2">
                            <div class="search-input-container position-relative">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input type="text" name="search" class="form-control"
                                    placeholder="Cari NIK atau Nama"
                                    value="{{ request('search', $search ?? '') }}"
                                    autocomplete="off">
                                <button type="button" class="btn btn-sm btn-link text-muted position-absolute end-0 top-50 translate-middle-y px-2 d-none btn-link-no-hover"
                                    aria-label="Bersihkan pencarian"
                                    data-clear-search>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1 btn-primary-no-hover">
                                <i class="fa-solid fa-search"></i>
                                <span>Cari</span>
                            </button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="text-center">
                                    <th style="width: 15%;">Foto</th>
                                    <th style="width: 15%;">Face ID</th>
                                    <th style="width: 25%;">NIK</th>
                                    <th style="width: 30%;" class="text-start">Nama</th>
                                    <th style="width: 15%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employee as $user)
                                    @php
                                        $hasFace = $user->faceEmbeddings && $user->faceEmbeddings->isNotEmpty();
                                    @endphp
                                    <tr class="text-center">
                                        <td>
                                            <div class="d-flex justify-content-center align-items-center">
                                                @if ($user->foto)
                                                    <img src="{{ route('storage.file', $user->foto) }}" alt="avatar"
                                                        class="rounded-circle border"
                                                        style="width: 96px; height: 96px; object-fit: cover;">
                                                @else
                                                    <img src="{{ asset('assets/image/profil-picture.png') }}" alt="avatar"
                                                        class="rounded-circle border"
                                                        style="width: 96px; height: 96px; object-fit: cover;">
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <a href="{{ route('admin.faceid', $user->id) }}"
                                                    class="btn btn-sm rounded-circle d-inline-flex align-items-center justify-content-center {{ $hasFace ? 'btn-success' : 'btn-primary' }}"
                                                    title="{{ $hasFace ? 'Data wajah sudah ada' : 'Daftarkan Wajah' }}"
                                                    style="width: 44px; height: 44px;"
                                                    data-face-button
                                                    data-has-face="{{ $hasFace ? '1' : '0' }}"
                                                    data-employee-name="{{ $user->nama ?? 'Pegawai' }}"
                                                    data-list-url="{{ route('admin.data') }}">
                                                    <i class="fas fa-camera"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="fw-semibold">{{ $user->nik ?? '-' }}</td>
                                        <td class="text-start">
                                            <div class="fw-semibold">{{ $user->nama ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                                                <a href="{{ route('admin.edit', $user->id) }}" class="btn btn-sm btn-warning text-white" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('admin.delete', $user->id) }}" method="POST" class="mb-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');"
                                                        title="Hapus Pegawai">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada data pegawai.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        {!! $employee->appends(request()->query())->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.querySelector('input[name="search"]');
            const clearBtn = document.querySelector('[data-clear-search]');

            function toggleClear() {
                if (!clearBtn || !searchInput) return;
                clearBtn.classList.toggle('d-none', !searchInput.value);
            }

            clearBtn?.addEventListener('click', () => {
                if (!searchInput) return;
                searchInput.value = '';
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                window.location.href = url.toString();
            });

            searchInput?.addEventListener('input', toggleClear);
            toggleClear();

            // Cegah membuka halaman perekaman jika data wajah sudah ada (gunakan flash warning via redirect)
            const faceButtons = document.querySelectorAll('[data-face-button]');
            faceButtons.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    const hasFace = btn.dataset.hasFace === '1';
                    if (hasFace) {
                        e.preventDefault();
                        const listUrl = btn.dataset.listUrl;
                        const name = btn.dataset.employeeName || '';
                        if (listUrl) {
                            const url = new URL(listUrl, window.location.origin);
                            url.searchParams.set('face_warning', '1');
                                        if (name) {
                                            url.searchParams.set('employee_name', name);
                                        }
                                        window.location.href = url.toString();
                                    }
                                }
                            });
                        });

            const successMessage = sessionStorage.getItem('showSuccessModal');
            if (successMessage) {
                Swal.fire({
                    title: 'Sukses!',
                    text: successMessage,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                sessionStorage.removeItem('showSuccessModal');
                return; 
            }
            const warningMessage = sessionStorage.getItem('showWarningModal');
            if (warningMessage) {
                Swal.fire({
                    title: 'Status Tidak Diketahui',
                    text: warningMessage,
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                sessionStorage.removeItem('showWarningModal');
            }
        });
    </script>
@endsection
