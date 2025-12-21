@extends('layout.admin')
@section('title', 'Manajemen Shift')
@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h4 class="mb-0">Daftar Shift</h4>
                        <a href="{{ route('shifts.create') }}" class="btn btn-primary">Tambah Shift</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Shift</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th class="text-center" style="width: 20%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shifts as $shift)
                                    <tr>
                                        <td>{{ ($shifts->currentPage() - 1) * $shifts->perPage() + $loop->iteration }}</td>
                                        <td>{{ $shift->nama_shift }}</td>
                                        <td>{{ $shift->jam_masuk }}</td>
                                        <td>{{ $shift->jam_pulang }}</td>
                                        <td>
                                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                                                <a href="{{ route('shifts.edit', $shift) }}"
                                                    class="btn btn-sm btn-warning text-white" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('shifts.destroy', $shift) }}" method="POST"
                                                    onsubmit="return confirm('Hapus shift ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" type="submit" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada data shift.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $shifts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
