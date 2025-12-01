# PM2 Deployment Guide - Face Verification Presence

Panduan singkat menjalankan aplikasi ini di server Linux dengan PM2.

## Quick Start

- Full deployment (server baru / update besar):
  ```bash
  cd deploy/linux
  ./deploy.sh
  ```
- Quick update (perubahan kecil):
  ```bash
  cd deploy/linux
  ./quick-update.sh
  ```

## Folder Structure

```
Face-Verification-Presence/
├─ deploy/                       # Deployment helpers
│  ├─ linux/                    # PM2/production scripts & docs
│  │  ├─ deploy.sh              # Full deployment
│  │  ├─ quick-update.sh        # Quick update/restart
│  │  ├─ ecosystem.config.cjs   # PM2 config (generated/overwritten)
│  │  └─ PM2-DEPLOYMENT-GUIDE.md
│  └─ windows/                  # Windows local helpers
│     ├─ local-run.bat          # Setup + migrate + build + serve
│     └─ quick-run.bat        # Build + serve (backend ready)
├─ app/                         # Laravel application
├─ public/                      # Public files
├─ storage/                     # Storage & logs
├─ artisan                      # Laravel artisan
└─ ...                          # Other Laravel files
```

## .env Ringkas (contoh prod)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
DB_CONNECTION=sqlite
DB_DATABASE=/home/ubuntu/Face-Verification-Presence/database/database.sqlite
LOG_LEVEL=error
CACHE_STORE=file
SESSION_DRIVER=file
```

Ganti `APP_URL` dan `DB_*` sesuai server Anda. Untuk MySQL, ubah `DB_CONNECTION` dan cred-nya, lalu migrasi ulang.

## Apa yang Dilakukan Script

`deploy.sh` (full):
- Deteksi project root dari posisi script.
- Cek/instal Node.js, npm, PHP (plus extensions), Composer, PM2, Git.
- `npm install` + `npm run build`.
- `composer install --no-dev --optimize-autoloader`.
- Clear & cache config/route/view.
- Generate `deploy/linux/ecosystem.config.cjs`.
- Start app dengan PM2 (`face-verification-app`) dan set startup.

`quick-update.sh`:
- Deteksi project root.
- `npm run build`.
- `php artisan config:clear`.
- Regenerate `deploy/linux/ecosystem.config.cjs`.
- Restart PM2 process `face-verification-app`.

## PM2 Config (generated)

- Process name: `face-verification-app`
- Command: `php artisan serve --host=0.0.0.0 --port=8000`
- Logs: `storage/logs/pm2-*.log`
- Config path: `deploy/linux/ecosystem.config.cjs`

## Troubleshooting

- Cek status/logs:
  ```bash
  pm2 status
  pm2 logs face-verification-app
  ```
- Jika build error:
  ```bash
  npm cache clean --force
  rm -rf node_modules
  npm install
  npm run build
  ```
- Jika Laravel error:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  tail -f storage/logs/laravel.log
  ```
- Pastikan berada di `deploy/linux`:
  ```bash
  pwd  # .../Face-Verification-Presence/deploy/linux
  ls ../../artisan  # harus ada
  ```
