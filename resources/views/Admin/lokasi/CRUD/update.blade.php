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
                            <h1 class="card-title fs-3 fw-bold mb-2"> Data Lokasi</h1>
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

                            <!-- Peta Lokasi -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0"><i class="fas fa-map-marked-alt me-2 text-primary"></i>Pilih Lokasi di Peta</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="useCurrentLocation">
                                        <i class="fas fa-location-crosshairs me-1"></i> Gunakan Lokasi Saya
                                    </button>
                                </div>
                                <div id="map" style="height: 400px; width: 100%; border-radius: 8px; border: 1px solid #ced4da;"></div>
                                <small class="text-muted">Geser marker atau klik pada peta untuk menentukan lokasi.</small>
                            </div>

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
        // Script untuk Geolocation dasar (hanya mengisi jika kosong dan diminta)
        const locationStatus = document.getElementById('locationStatus');
        
        // Hapus event listener window load yang lama agar tidak konflik dengan map
        // window.addEventListener('load', ...); 
    </script>
@endsection

@section('style')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endsection

@section('script')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const radiusInput = document.getElementById('radius');
            
            // Default location (Jakarta) if no data
            let defaultLat = -6.2088;
            let defaultLng = 106.8456;
            let zoomLevel = 13;

            // Check if inputs have values
            if (latInput.value && lngInput.value) {
                defaultLat = parseFloat(latInput.value);
                defaultLng = parseFloat(lngInput.value);
                zoomLevel = 16;
            }

            // Initialize Map
            const map = L.map('map').setView([defaultLat, defaultLng], zoomLevel);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Marker
            let marker = L.marker([defaultLat, defaultLng], {
                draggable: true
            }).addTo(map);

            // Circle for radius
            let circle = L.circle([defaultLat, defaultLng], {
                color: 'blue',
                fillColor: '#3085d6',
                fillOpacity: 0.1,
                radius: parseFloat(radiusInput.value) || 0
            }).addTo(map);

            // Update inputs when marker is dragged
            marker.on('dragend', function(e) {
                const position = marker.getLatLng();
                updateInputs(position.lat, position.lng);
                updateCircle(position.lat, position.lng);
            });

            // Update marker when map is clicked
            map.on('click', function(e) {
                const position = e.latlng;
                marker.setLatLng(position);
                updateInputs(position.lat, position.lng);
                updateCircle(position.lat, position.lng);
            });

            // Update marker when inputs change manually
            latInput.addEventListener('change', updateMarkerFromInputs);
            lngInput.addEventListener('change', updateMarkerFromInputs);
            radiusInput.addEventListener('input', function() {
                const r = parseFloat(this.value) || 0;
                circle.setRadius(r);
            });

            function updateInputs(lat, lng) {
                latInput.value = lat;
                lngInput.value = lng;
            }

            function updateCircle(lat, lng) {
                circle.setLatLng([lat, lng]);
            }

            function updateMarkerFromInputs() {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    const newLatLng = new L.LatLng(lat, lng);
                    marker.setLatLng(newLatLng);
                    circle.setLatLng(newLatLng);
                    map.panTo(newLatLng);
                }
            }

            // If inputs were empty, try to get current location
            if (!latInput.value || !lngInput.value) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Only update if user hasn't input anything yet
                        if (!latInput.value && !lngInput.value) {
                            updateInputs(lat, lng);
                            const newLatLng = new L.LatLng(lat, lng);
                            marker.setLatLng(newLatLng);
                            circle.setLatLng(newLatLng);
                            map.setView(newLatLng, 16);
                        }
                    }, function(error) {
                        console.warn("Geolocation error:", error);
                    });
                }
            }

            // Handle "Gunakan Lokasi Saya" button
            const useLocationBtn = document.getElementById('useCurrentLocation');
            if (useLocationBtn) {
                useLocationBtn.addEventListener('click', function() {
                    if (navigator.geolocation) {
                        locationStatus.textContent = 'Mendapatkan lokasi...';
                        locationStatus.className = 'form-text mt-1 mb-3 text-info';
                        
                        navigator.geolocation.getCurrentPosition(function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            updateInputs(lat, lng);
                            const newLatLng = new L.LatLng(lat, lng);
                            marker.setLatLng(newLatLng);
                            circle.setLatLng(newLatLng);
                            map.setView(newLatLng, 18);
                            
                            locationStatus.textContent = 'Lokasi berhasil diperbarui ke posisi Anda saat ini.';
                            locationStatus.className = 'form-text mt-1 mb-3 text-success';
                        }, function(error) {
                            let msg = 'Gagal mendapatkan lokasi.';
                            switch(error.code) {
                                case error.PERMISSION_DENIED: msg = "Izin lokasi ditolak."; break;
                                case error.POSITION_UNAVAILABLE: msg = "Informasi lokasi tidak tersedia."; break;
                                case error.TIMEOUT: msg = "Waktu permintaan habis."; break;
                            }
                            locationStatus.textContent = msg;
                            locationStatus.className = 'form-text mt-1 mb-3 text-danger';
                        });
                    } else {
                        locationStatus.textContent = 'Browser tidak mendukung geolocation.';
                        locationStatus.className = 'form-text mt-1 mb-3 text-danger';
                    }
                });
            }
        });
    </script>

@endsection
