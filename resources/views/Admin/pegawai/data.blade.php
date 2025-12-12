@extends('layout.admin')
@section('title', 'Data Pegawai')
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
                        <!-- Tombol Tambah Data -->
                        <a href="{{ route('admin.create') }}"class="btn btn-success">Tambah Data</a>

                        <!-- Form Pencarian dengan Ikon di dalam -->
                        <form action="{{ route('admin.data') }}" method="GET" class="search-form ms-auto">
                            <div class="search-input-container">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input type="text" name="search" class="form-control"
                                    placeholder="Cari NIK, Email, atau Nama"
                                    value="{{ request('search', $search ?? '') }}">
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="text-center">
                                    <th>NO</th>
                                    <th>FACE ID</th>
                                    <th>FOTO</th>
                                    <th>NIK</th>
                                    <th>NAMA</th>
                                    <th>EMAIL</th>
                                    <th>Jabatan</th>
                                    <th>No Telepon</th>
                                    <th>AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employee as $user)
                                    <tr class="text-center">
                                        <td>{{ ($employee->currentPage() - 1) * $employee->perPage() + $loop->iteration }}
                                        </td>
                                        <td><a href="{{ route('admin.faceid', $user->id) }}"
                                                class="btn btn-primary btn-sm rounded-circle rekam-wajah-btn"
                                                style="width:36px; height: 36px; position: relative;"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Rekam Wajah"
                                                data-embedding-exists="{{ $user->face_embedding ? 'true' : 'false' }}"
                                                data-user-name="{{ $user->nama }}">

                                                <i class="fas fa-camera"
                                                    style="position: absolute;  top: 50%; left: 50%; transform: translate(-50%, -50%); "></i>
                                            </a>
                                        </td>
                                        <td>
                                            @if ($user->foto)
                                                <img src="{{ route('storage.file', $user->foto) }}" alt="avatar"
                                                    class="imaged w64 rounded-circle"
                                                    style="width: 100px; height: 100px; object-fit: cover;">
                                            @else
                                                <img src="{{ asset('assets/image/profil-picture.png') }}" alt="avatar"
                                                    class="imaged w64 rounded-circle"
                                                    style="width: 100px; height: 100px; object-fit: cover;">
                                            @endif
                                        </td>
                                        <td>{{ $user->nik }}</td>
                                        <td>{{ $user->nama }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->jabatan }}</td>
                                        <td>{{ $user->no_hp }}</td>
                                        <td class="align-middle">
                                            <div
                                                class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">

                                                <a href="{{ route('admin.edit', $user->id) }}"
                                                    class="btn btn-sm btn-warning">EDIT</a>

                                                <form action="{{ route('admin.delete', $user->id) }}" method="POST"
                                                    class="mb-0">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                        HAPUS
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada data pegawai.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- Link Paginasi --}}
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
