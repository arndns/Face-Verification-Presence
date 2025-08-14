# Face Verification Presence

Sebuah aplikasi web yang dibangun dengan Laravel untuk manajemen kehadiran atau presensi menggunakan teknologi verifikasi wajah.

## Tentang Proyek

Aplikasi ini memungkinkan pengguna untuk melakukan absensi dengan memindai wajah mereka. Sistem akan memverifikasi dan mencatat kehadiran secara otomatis. Proyek ini bertujuan untuk menyediakan solusi presensi yang modern, cepat, dan aman.

## Fitur Utama

*   **Registrasi Pengguna:** Pendaftaran akun untuk pengguna baru.
*   **Pengambilan Data Wajah:** Proses untuk merekam dan menyimpan data wajah pengguna.
*   **Verifikasi Wajah:** Pencocokan wajah secara real-time untuk absensi.
*   **Pencatatan Kehadiran:** Log atau riwayat kehadiran yang tercatat otomatis.
*   **Dasbor Pengguna:** Tampilan riwayat dan status kehadiran masing-masing pengguna.

## Teknologi yang Digunakan

*   **Backend:** PHP 8.2+, Laravel 12
*   **Database:** (Dapat disesuaikan) MySQL, PostgreSQL, SQLite

## Panduan Instalasi

Berikut adalah langkah-langkah untuk menjalankan proyek ini di lingkungan lokal Anda.

**1. Clone Repository**
```bash
git clone [URL_REPOSITORY_ANDA]
cd Face-Verification-Presence
```

**2. Instal Dependensi**
Pastikan Anda memiliki Composer terinstal.
```bash
composer install
```

**3. Konfigurasi Lingkungan**
Salin file `.env.example` menjadi `.env` dan sesuaikan koneksi database Anda.
```bash
cp .env.example .env
```
Setelah itu, generate kunci aplikasi Laravel.
```bash
php artisan key:generate
```

**4. Migrasi Database**
Jalankan migrasi untuk membuat tabel-tabel yang dibutuhkan.
```bash
php artisan migrate
```

**5. Jalankan Aplikasi**
Jalankan server development Laravel.
```bash
php artisan serve
```
Aplikasi sekarang akan berjalan di `http://127.0.0.1:8000`.

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).