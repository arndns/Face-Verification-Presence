@extends('layout.admin')
@section('title', 'Data Pegawai')
@section('content')

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <h1 class="card-title fs-3 fw-bold mb-2 text-center">FORMULIR DATA PENGGUNA</h1>
                        <!-- Menampilkan Pesan ERROR Validasi dengan Alert Bootstrap -->
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong class="fw-bold">Oops! Terjadi kesalahan.</strong>
                                <span class="d-block">Mohon periksa kembali isian form Anda.</span>
                            </div>
                        @endif

                        <form action="{{ route('admin.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="foto" class="form-label "></label>
                                <div id="profileContainer" class="profile-pic-uploader">

                                    <img id="profileImage"
                                        src="{{ $employee->foto ? Storage::url($employee->foto) : 'https://placehold.co/100x100/E9ECEF/6C757D?text=Pilih+Foto' }}"
                                        alt="Foto {{ $employee->nama ?? 'Baru' }}" class="profile-pic-img">
                                    <div class="profile-pic-overlay">
                                        <span>Ubah Foto</span>
                                    </div>
                                    <input type="file" id="fileInput" name="foto" accept="image/*" class="hidden">
                                </div>


                                @error('foto')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @else
                                    <small class="text-muted text-center d-block mt-1">Klik gambar di atas untuk mengganti foto.</small>
                                @enderror
                            </div>

                            <!-- Field NIK -->
                            <div class="mb-3">
                                <label for="nik" class="form-label">NIK</label>
                                <input type="text" name="nik" id="nik"
                                    class="form-control @error('nik') is-invalid @enderror"
                                    value="{{ old('nik', $employee->nik) }}" placeholder="Masukkan NIK Anda">
                                @error('nik')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <!-- Field Nama -->
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" id="nama"
                                    class="form-control @error('nama') is-invalid @enderror"
                                    value="{{ old('nama', $employee->nama) }}" placeholder="Masukkan nama lengkap Anda">
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Field Jabatan -->
                            <div class="mb-3">
                                <label for="jabatan" class="form-label">Jabatan</label>
                                <input type="text" name="jabatan" id="jabatan"
                                    class="form-control @error('jabatan') is-invalid @enderror"
                                    value="{{ old('jabatan', $employee->jabatan) }}" placeholder="Masukkan jabatan">
                                @error('jabatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Field Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Alamat Email</label>
                                <input type="email" name="email" id="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $employee->email) }}" placeholder="Masukkan email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Field No Telepon -->
                            <div class="mb-3">
                                <label for="no_hp" class="form-label">Nomor Telepon</label>
                                <input type="tel" name="no_hp" id="no_hp"
                                    class="form-control @error('no_hp') is-invalid @enderror"
                                    value="{{ old('no_hp', $employee->no_hp) }}" placeholder="Masukkan nomor telepon">
                                @error('no_hp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">


                                <div class="mb-3">
                                    <label for="password">Password Baru</label>
                                    <input type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror">
                                    <small class="text-warning">Biarkan kosong jika tidak ingin mengubah password.</small>
                                    @error('password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                                    <input type="password" name="password_confirmation" class="form-control">
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="d-flex  gap-2 mt-4">
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

@section('style')
    <style>
       .hidden {
        display: none;
    }

    .profile-pic-uploader {
        position: relative;
        width: 300px;
        height: 300px;
        border-radius: 50%; 
        margin: 0 auto;     
        overflow: hidden;
        cursor: pointer;
        background-color: #f8f9fa; 
        display: flex;
        justify-content: center;
        align-items: center;
         box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }


    .profile-pic-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .profile-pic-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        font-family: Arial, sans-serif;
        font-weight: bold;
        font-size: 1rem;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 50%;
        font-size: 1.1rem;
    }

    .profile-pic-uploader:hover .profile-pic-overlay {
        opacity: 1;
    }
    </style>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileContainer = document.getElementById('profileContainer');
            const fileInput = document.getElementById('fileInput');
            const profileImage = document.getElementById('profileImage');

            if (profileContainer && fileInput && profileImage) {

                profileContainer.addEventListener('click', function() {
                    fileInput.click();
                });

                fileInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            profileImage.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
@endsection
