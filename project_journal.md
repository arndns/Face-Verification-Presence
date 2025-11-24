# Project Journal

## Deployment System
### Overview
The project uses a PM2-based deployment system located in the `deploy/` directory. It is designed for Linux servers (Ubuntu/Debian/CentOS).

### Key Files
- **`deploy/deploy.sh`**: Full deployment script.
  - Auto-installs system requirements (Node.js, PHP, Composer, PM2, Git).
  - Installs dependencies (npm & composer).
  - Builds frontend assets.
  - Optimizes Laravel caches.
  - Configures and starts the app with PM2.
  - Dynamically generates `ecosystem.config.cjs` with correct paths.
- **`deploy/quick-update.sh`**: For minor updates.
  - Rebuilds frontend assets.
  - Clears config cache.
  - Restarts PM2 process.
- **`deploy/ecosystem.config.cjs`**: PM2 configuration file (generated/overwritten by scripts).
- **`deploy/PM2-DEPLOYMENT-GUIDE.md`**: Detailed documentation for the deployment process.

### Deployment Workflow
1.  **Full Deploy**: Run `./deploy.sh` from the `deploy` directory. Use for initial setup or major updates.
2.  **Quick Update**: Run `./quick-update.sh` from the `deploy` directory. Use for code changes that don't affect dependencies.

### Server Requirements
- Linux OS
- Node.js v18+
- PHP v8.2+
- Composer
- PM2
- Linux OS

## Local Development Setup (Windows - Portable)
### Current Status
- **Environment**: Configured `.env` for SQLite.
- **Database**: `database/database.sqlite` exists and migrated.
- **Dependencies**:
  - `npm`: Installed (v10.9.3).
  - `php`: **Portable** (v8.2.29) in `bin/php`.
  - `composer`: **Portable** in `bin/php`.
- **Helper Scripts**:
  - `php.bat`: Wrapper for portable PHP.
  - `composer.bat`: Wrapper for portable Composer.

### How to Run
1.  **Start Server**:
    ```bash
    ./php.bat artisan serve
    ```
2.  **Watch Frontend** (Optional, for development):
    ```bash
    npm run dev
    ```
3.  **Quick Deploy Scripts**:
    - `deploy/local-run.bat`: Full setup (install dependencies, migrate, build, serve).
    - `deploy/local-quick.bat`: Quick run (build frontend, serve only).

### Portable PHP Setup
- **Location**: `bin/php/` (auto-created by `setup_portable.ps1`).
- **Contents**: PHP 8.2.29, Composer, configured extensions (SQLite, curl, etc.).
- **Purpose**: Run Laravel without system-wide PHP installation.
- **Gitignore**: Excluded from Git (`bin/`, `php.bat`, `composer.bat`, `setup_portable.ps1`).

### Default Login Credentials
Setelah menjalankan `php artisan db:seed`, terdapat 2 user default:

**Admin:**
- Username: `nuansa@mail.com`
- Password: `password123`
- Role: `admin` (default)

**Employee:**
- Username: `1234567890` (NIK)
- Password: `password123`
- Role: `employee`

## Admin Features
### Riwayat Presensi
- **Route**: `/admin/presence/history`
- **Fitur**:
  - Menampilkan seluruh data presensi pegawai (waktu masuk & pulang)
  - Filter berdasarkan nama/NIK pegawai
  - Filter berdasarkan rentang tanggal
  - Pagination (15 data per halaman)
  - Status badge (Selesai, Sedang Berlangsung, Belum Masuk)

### Pengajuan Cuti
- **Route**: `/admin/leave`
- **Fitur**:
  - Melihat semua pengajuan cuti dari seluruh pegawai
  - Filter berdasarkan nama/NIK pegawai
  - Filter berdasarkan status (pending, approved, rejected)
  - **Approve/Reject** pengajuan cuti dengan catatan admin
  - Modal konfirmasi untuk setiap aksi
  - Lihat detail pengajuan yang sudah diproses
  - Pagination (15 data per halaman)

## Employee Features
### Pengajuan Cuti
- **Route**: `/employee/leave/create`
- **Menu**: Bottom navigation "Ajukan Cuti" (menggantikan "Pendapatan")
- **Fitur**:
  - Form pengajuan cuti dengan pilihan jenis: Sakit, Izin, Cuti Tahunan
  - Validasi tanggal (tidak bisa pilih tanggal lampau)
  - Input alasan cuti (max 500 karakter)
  - Auto-sync tanggal selesai dengan tanggal mulai
- **Riwayat Cuti** (`/employee/leave/history`):
  - Melihat semua pengajuan cuti (pending, approved, rejected)
  - Badge status warna-warni
  - Catatan admin (jika ada)
  - Pagination
