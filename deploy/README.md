# Deploy & Run Guide

Panduan singkat dari clone repo sampai aplikasi jalan, untuk Windows (portable) dan Linux (PM2).

## 1) Clone Repo
```bash
git clone <repo-url>
cd Face-Verification-Presence
```

## 2) Siapkan .env
- Salin contoh: `cp .env.example .env`
- Untuk dev cepat, pakai SQLite (sudah diset di `.env` yang ada di repo ini). Jika ganti MySQL, isi kredensialnya lalu migrasi ulang.

## 3) Jalankan di Windows (portable, tanpa instal global)
Ada helper di `deploy/windows/`:
- **Full setup + migrate + build + serve**:
  ```cmd
  cd deploy\windows
  local-run.bat
  ```
- **Frontend build + serve** (backend sudah siap):
  ```cmd
  cd deploy\windows
  local-quick.bat
  ```
Catatan: Script memakai PHP/Composer/Node portable di folder proyek (`php.bat`, `composer.bat`, `node.bat`, `npm.bat`). Tidak perlu instal global.

## 4) Jalankan di Linux (PM2, production-style)
Ada helper di `deploy/linux/`:
- **Full deploy** (install deps, build, optimize, start PM2):
  ```bash
  cd deploy/linux
  ./deploy.sh
  ```
- **Quick update** (build + restart PM2):
  ```bash
  cd deploy/linux
  ./quick-update.sh
  ```
Script akan:
- Deteksi path project root otomatis.
- Cek/instal Node.js, npm, PHP (+extensions), Composer, PM2, Git.
- `npm install`, `npm run build`.
- `composer install --no-dev --optimize-autoloader`.
- Clear + cache config/route/view.
- Start PM2 process `face-verification-app` (command: `php artisan serve --host=0.0.0.0 --port=8000`).

## 5) Akses aplikasi
- Dev/Windows default: `http://127.0.0.1:8000`
- PM2/Linux default: `http://0.0.0.0:8000` (set `APP_URL` sesuai domain/IP di `.env`)

## 6) Credensial default (setelah seeding)
Jika butuh akun uji, jalankan `php artisan db:seed --class=UserSeeder` lalu pakai:
- Admin: `zfauzanadmin123@gmail.com` / `mugo2sukses`
- Pegawai: `1234567890` / `password123`

## 7) Troubleshooting ringkas
- Redirect loop: hapus cookie 127.0.0.1, pastikan sudah login sesuai role.
- PM2 status/log: `pm2 status`, `pm2 logs face-verification-app`.
- Build error: `rm -rf node_modules && npm install && npm run build`.
- Laravel error: `php artisan config:clear && php artisan cache:clear && php artisan view:clear`.
