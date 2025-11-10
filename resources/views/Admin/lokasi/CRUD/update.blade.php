@extends('layout.admin')
@section('title', 'Data Lokasi')
@section('content')
    <!-- Kontainer Utama -->
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-map-marked-alt fa-3x text-primary mb-3"></i>
                            <h1 class="card-title fs-3 fw-bold mb-2">Pendaftaran Data Lokasi</h1>
                            <p class="card-subtitle text-muted">Silakan isi informasi di bawah ini untuk menambahkan data
                                lokasi baru.</p>
                        </div>
                        <form action="{{ route('location.update', $location->id)  }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <!-- Field Kota -->
                            <div class="mb-3">
                                <label for="kota" class="form-label fw-semibold"><i
                                        class="fas fa-city me-2 text-primary"></i>Kota</label>
                                <input type="text" name="kota" id="kota"
                                    class="form-control @error('kota') is-invalid @enderror" placeholder="Contoh: Jakarta"
                                    value="{{ old('kota',$location->kota) }}">
                                @error('kota')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Field Alamat -->
                            <div class="mb-3">
                                <label for="alamat" class="form-label fw-semibold"><i
                                        class="fas fa-map-location-dot me-2 text-primary"></i>Alamat</label>
                                <textarea name="alamat" id="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3"
                                    placeholder="Masukkan alamat lengkap kantor">{{ old('alamat', $location->alamat) }}</textarea>
                                @error('alamat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status Lokasi dari GPS -->
                            <div id="locationStatus" class="form-text mt-1 mb-3"></div>

                            <div class="row">
                                <!-- Field Latitude -->
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label fw-semibold"><i
                                            class="fas fa-location-arrow me-2 text-primary"></i>Latitude</label>
                                    <input type="text" name="latitude" id="latitude"
                                        class="form-control @error('latitude') is-invalid @enderror"
                                        placeholder="Mendapatkan..." value="{{ old('latitude', $location->latitude) }}" >
                                    @error('latitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Field Longitude -->
                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label fw-semibold"><i
                                            class="fas fa-location-arrow me-2 text-primary"></i>Longitude</label>
                                    <input type="text" name="longitude" id="longitude"
                                        class="form-control @error('longitude') is-invalid @enderror"
                                        placeholder="Mendapatkan..." value="{{ old('longitude', $location->longitude) }}" >
                                    @error('longitude')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Field Radius -->
                            <div class="mb-4">
                                <label for="radius" class="form-label fw-semibold"><i
                                        class="fas fa-bullseye me-2 text-primary"></i>Radius (meter)</label>
                                <input type="number" name="radius" id="radius"
                                    class="form-control @error('radius') is-invalid @enderror" placeholder="Contoh: 500"
                                    value="{{ old('radius', $location->radius) }}">
                                @error('radius')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('location.index') }}" class="btn btn-secondary"><i
                                        class="fas fa-arrow-left me-2"></i>Kembali</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan
                                    Lokasi</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const locationStatus = document.getElementById('locationStatus');

        // Fungsi ini akan dipanggil saat halaman dimuat
        window.addEventListener('load', () => {
            if (navigator.geolocation) {
                locationStatus.textContent = 'Mendapatkan lokasi...';
                locationStatus.classList.remove('text-danger', 'text-success');
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                locationStatus.textContent = 'Geolocation tidak didukung oleh browser ini.';
                locationStatus.classList.add('text-danger');
            }
        });

        function showPosition(position) {
            latitudeInput.value = position.coords.latitude;
            longitudeInput.value = position.coords.longitude;
            locationStatus.textContent = 'Lokasi berhasil didapatkan!';
            locationStatus.classList.add('text-success');
            locationStatus.classList.remove('text-danger');
        }

        function showError(error) {
            let message = '';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = "Perizinan Lokasi Bermasalah.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = "Informasi lokasi tidak tersedia.";
                    break;
                case error.TIMEOUT:
                    message = "Permintaan untuk mendapatkan lokasi pengguna timeout.";
                    break;
                case error.UNKNOWN_ERROR:
                    message = "Terjadi kesalahan yang tidak diketahui.";
                    break;
            }
            locationStatus.textContent = message;
            locationStatus.classList.add('text-danger');
            locationStatus.classList.remove('text-success');
        }
    </script>

@endsection
