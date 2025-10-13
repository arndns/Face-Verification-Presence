@extends('layout.admin')
@section('title', 'Lokasi')
@section('content')
    <!-- Tabel Data -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <!-- Menampilkan Pesan SUKSES dengan Alert Bootstrap -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button
                            type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                @elseif(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button"
                            class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                @elseif(session('info'))
                    <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button"
                            class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                @endif
                <div class="table-container">
                    <h4 class="mb-4">Daftar Lokasi</h4>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <!-- Tombol Tambah Data -->
                        <a href="{{ route('location.create') }}"class="btn btn-success">Tambah Data</a>

                        <!-- Form Pencarian dengan Ikon di dalam -->
                        <form action="#" method="GET" class="search-form ms-auto">
                            <div class="search-input-container">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input type="text" name="search" class="form-control" placeholder="Cari Data Lokasi">
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>KOTA</th>
                                    <th>ALAMAT</th>
                                    <th>LATITUDE</th>
                                    <th>LONGTITUDE</th>
                                    <th>RADIUS</th>
                                    <th>AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($locations as $location)
                                    <tr>
                                        <td>{{ ($locations->currentPage() - 1) * $locations->perPage() + $loop->iteration }}
                                        </td>
                                        <td>{{ $location->kota }}</td>
                                        <td>{{ $location->alamat }}</td>
                                        <td>{{ $location->latitude }}</td>
                                        <td>{{ $location->longitude }}</td>
                                        <td>{{ $location->radius }}</td>
                                        <td class="align-middle">
                                            <div
                                                class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">

                                                <a href="{{ route('location.edit', $location->id) }}"
                                                    class="btn btn-sm btn-warning">EDIT</a>

                                                <form action="{{route('location.delete', $location->id)}}" method="POST" class="mb-0">
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
                                        Data Lokasi belum tersedia
                                    </div>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- Link Paginasi --}}
                    <div class="d-flex justify-content-center mt-3">
                        {!! $locations->appends(request()->query())->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
