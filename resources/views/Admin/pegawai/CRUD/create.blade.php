@extends('layout.admin')
@section('title', 'Data Pegawai')
@section('content')
    <!-- Kontainer Utama -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <h1 class="card-title fs-3 fw-bold mb-2">Formulir Pendaftaran Pegawai</h1>
                        
                        <!-- Menampilkan Pesan ERROR Validasi dengan Alert Bootstrap -->
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong class="fw-bold">Oops! Terjadi kesalahan.</strong>
                                <span class="d-block">Mohon periksa kembali isian form Anda.</span>
                            </div>
                        @endif

                        <!-- Ganti 'users.store' dengan route Anda jika berbeda -->
                        <form action="{{ route('admin.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf <!-- Token Keamanan Laravel -->

                    <!-- Field Foto -->
                    <div class = "form-group mb-3" >
                            <label for="foto" class="form-label">Foto</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-camera"></i></span>
                                <input type="file" class="form-control @error('foto') is-invalid @enderror"
                                    id="foto" name="foto" accept="image/*">
                            </div>
                            <small class="form-text text-muted">Opsional. Unggah file JPG/JPEG maksimal 2 MB.</small>
                            @error('foto')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                    </div>

                    <!-- Field NIK -->
                    <div class="mb-3">
                        <label for="nik" class="form-label">NIK</label>
                        <input type="text" name="nik" id="nik"
                            class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik') }}"
                            placeholder="Masukkan NIK Anda">
                        <small class="form-text text-muted">Wajib diisi dan harus berbeda dari pegawai lain.</small>
                        @error('nik')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Field Nama -->
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" id="nama"
                            class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}"
                            placeholder="Masukkan nama lengkap Anda">
                        <small class="form-text text-muted">Wajib diisi sesuai identitas.</small>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Field Jabatan -->
                    <div class="mb-3">
                        <label for="jabatan" class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan"
                            class="form-control @error('jabatan') is-invalid @enderror" value="{{ old('jabatan') }}"
                            placeholder="Masukkan jabatan">
                        <small class="form-text text-muted">Wajib diisi sesuai posisi pekerjaan.</small>
                        @error('jabatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    <!-- Field Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" name="email" id="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            placeholder="Masukkan email">
                        <small class="form-text text-muted">Wajib diisi dengan email aktif dan belum dipakai pegawai lain.</small>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Field No Telepon -->
                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor Telepon</label>
                        <input type="tel" name="no_hp" id="no_hp"
                            class="form-control @error('no_hp') is-invalid @enderror" value="{{ old('no_hp') }}"
                            placeholder="Masukkan nomor telepon">
                        <small class="form-text text-muted">Opsional. Maksimal 15 digit tanpa spasi atau simbol.</small>
                        @error('no_hp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Field Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password"
                            class="form-control @error('password') is-invalid @enderror" placeholder="Masukkan password">
                        <small class="form-text text-muted">Minimal 8 karakter.</small>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Field Password -->
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                            placeholder="Konfirmasi password" required>
                        <small class="form-text text-muted">Pastikan sama dengan password di atas.</small>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <!-- Ganti 'users.index' dengan route Anda jika berbeda -->
                        <a href="{{ route('admin.data') }}" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    </div>

@endsection
