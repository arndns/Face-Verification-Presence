@extends('layout.admin')
@section('title', 'Tambah Shift')
@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Tambah Shift Baru</h4>
                        <form action="{{ route('shifts.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama Shift</label>
                                <input type="text" name="nama_shift" class="form-control @error('nama_shift') is-invalid @enderror"
                                    value="{{ old('nama_shift') }}" required>
                                @error('nama_shift')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jam Masuk</label>
                                <input type="time" name="jam_masuk" class="form-control @error('jam_masuk') is-invalid @enderror"
                                    value="{{ old('jam_masuk') }}" required>
                                @error('jam_masuk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jam Pulang</label>
                                <input type="time" name="jam_pulang" class="form-control @error('jam_pulang') is-invalid @enderror"
                                    value="{{ old('jam_pulang') }}" required>
                                @error('jam_pulang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('shifts.index') }}" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
