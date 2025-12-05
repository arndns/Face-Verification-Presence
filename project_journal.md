# Project Journal

## Deployment System
### Overview
The project uses a PM2-based deployment system located in the `deploy/` directory. It is designed for Linux servers (Ubuntu/Debian/CentOS).

### Key Files
- **`deploy/linux/deploy.sh`**: Full deployment script.
  - Auto-installs system requirements (Node.js, PHP, Composer, PM2, Git).
  - Installs dependencies (npm & composer).
  - Builds frontend assets.
  - Optimizes Laravel caches.
  - Configures and starts the app with PM2.
  - Dynamically generates `ecosystem.config.cjs` with correct paths.
- **`deploy/linux/quick-update.sh`**: For minor updates.
  - Rebuilds frontend assets.
  - Clears config cache.
  - Restarts PM2 process.
- **`deploy/linux/ecosystem.config.cjs`**: PM2 configuration file (generated/overwritten by scripts).
- **`deploy/linux/PM2-DEPLOYMENT-GUIDE.md`**: Detailed documentation for the deployment process.

### Deployment Workflow
1.  **Full Deploy**: Run `./deploy.sh` from the `deploy/linux` directory. Use for initial setup or major updates.
2.  **Quick Update**: Run `./quick-update.sh` from the `deploy/linux` directory. Use for code changes that don't affect dependencies.

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
    - `deploy/windows/local-run.bat`: Full setup (install dependencies, migrate, build, serve).
    - `deploy/windows/local-quick.bat`: Quick run (build frontend, serve only).

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

### Dashboard Features
- **Jam Server Real-time**: Menampilkan waktu server yang update setiap detik
- **Peta Lokasi**: 
  - Muncul setelah klik tombol "Periksa Lokasi Saya"
  - Menampilkan marker lokasi kantor (biru) dan lokasi pegawai (hijau)
  - Radius area kantor ditampilkan dalam circle
  - Auto zoom untuk menampilkan kedua marker
  - Menggunakan Leaflet.js dengan OpenStreetMap

## 2025-12-02 - Allow Multiple Clock-Outs
- **Objective**: Allow employees to clock out multiple times (e.g., if they stay late after initial clock-out).
- **Changes**:
  - **Backend**: Modified `EmployeeController.php`.
    - Removed the check in `presence` method that blocked clock-out if `waktu_pulang` was already set.
    - Updated `presenceStatus` method to set `canCheckOut` to true if it is past shift end, even if `hasCheckedOut` is already true.
  - **Frontend**: Modified `camera.blade.php`.
    - Updated `getCurrentActionMode` to prioritize `check_out` mode over `done` mode if `canCheckOut` is true.
    - Added a `recentSuccess` flag with a 15-second cooldown to prevent immediate re-clocking out loops when the user stays on the camera page.

## 2025-12-02 - Add Map to Admin Location Edit
- **Objective**: Provide a visual map interface for admins to set office location coordinates.
- **Changes**:
  - **Frontend**: Modified `resources/views/Admin/lokasi/CRUD/update.blade.php`.
    - Integrated Leaflet.js map.
    - Added a draggable marker to set Latitude and Longitude.
    - Added a circle overlay to visualize the radius.
    - Implemented two-way binding between the map marker and the input fields.
    - Added logic to auto-detect user location if coordinates are empty.

## 2025-12-02 - Unify Admin Presence History
- **Objective**: Match the Admin's presence history view with the Employee's view by including approved leave/permit records.
- **Changes**:
  - **Backend**: Modified `AdminController.php`.
    - Updated `presenceHistory` to fetch both `Presence` and `Permit` records.
    - Merged and sorted the data by date.
    - Implemented manual pagination (`LengthAwarePaginator`) for the merged collection.
  - **Frontend**: Modified `resources/views/Admin/presence/history.blade.php`.
    - Updated the table to display "Izin/Cuti" status correctly alongside regular attendance.

## 2025-12-02 - Change Locale to Indonesian
- **Objective**: Ensure date and time formats are displayed in Indonesian (e.g., "Senin, 02 Des 2025").
- **Changes**:
  - **Config**: Modified `config/app.php` to set `'locale'` to `'id'`.
  - **Action**: Cleared configuration cache to apply changes.

## 2025-12-02 - Fix Locale Issue
- **Objective**: Force application to use Indonesian locale as `.env` was overriding config.
- **Changes**:
  - **Environment**: Updated `.env` file to set `APP_LOCALE=id`.
  - **Provider**: Added `\Carbon\Carbon::setLocale('id')` in `AppServiceProvider` to explicitly enforce Carbon locale.
  - **Action**: Cleared configuration cache.

## 2025-12-02 - Fix Date Formatting in Views
- **Objective**: Ensure dates are displayed in Indonesian format (e.g., "03 Des 2025") in views.
- **Changes**:
  - **Frontend**: Modified `resources/views/Admin/permit/index.blade.php` and `resources/views/Admin/presence/history.blade.php`.
    - Replaced `format()` with `translatedFormat()` to utilize the localized date names.
  - **Frontend**: Modified `resources/views/Employee/permit/history.blade.php`.
    - Replaced `format()` with `translatedFormat()` for localized date display in employee leave history.

## 2025-12-05 - Disable Presence Buttons Outside Radius
- **Objective**: Prevent users from attempting to clock in/out when outside the designated office radius.
- **Changes**:
  - **Frontend**: Modified `resources/views/Employee/index.blade.php` and `resources/views/layout/employee.blade.php`.
    - Added IDs to the "Mulai Presensi" button and the bottom navigation "Presensi" button.
    - Updated the `checkLocation` JavaScript function to dynamically disable these buttons if the calculated distance exceeds the office radius.
    - Added visual feedback (opacity, cursor change, text update) when buttons are disabled.
