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
                @endif
                <div class="table-container">
                    <h4 class="mb-4">Data Pegawai</h4>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <!-- Tombol Tambah Data -->
                        <a href="{{ route('admin.create') }}"class="btn btn-success">Tambah Data</a>

                        <!-- Form Pencarian dengan Ikon di dalam -->
                        <form action="#" method="GET" class="search-form ms-auto">
                            <div class="search-input-container">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input type="text" name="search" class="form-control" placeholder="Cari Data Pegawai">
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>NIK</th>
                                    <th>NAMA</th>
                                    <th>EMAIL</th>
                                    <th>Jabatan</th>
                                    <th>No Telepon</th>
                                    <th>AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                        <td>{{ $user->nik }}</td>
                                        <td>{{ $user->nama }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->jabatan }}</td>
                                        <td>{{ $user->no_tilpun }}</td>
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
                                    <div class="alert alert-danger">
                                        Data Post belum Tersedia.
                                    </div>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- Link Paginasi --}}
                    <div class="d-flex justify-content-center mt-3">
                        {!! $users->appends(request()->query())->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
