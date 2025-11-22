# PM2 Deployment Guide - Face Verification Presence

Guide sederhana untuk menggunakan PM2 dan deployment scripts aplikasi Laravel Anda.

## 🚀 Quick Start

### 2 Script yang Anda Butuhkan:

**Full Deployment (untuk server baru atau update besar):**
```bash
cd deploy
./deploy.sh
```

**Quick Update (untuk perubahan kode kecil):**
```bash
cd deploy
./quick-update.sh
```

## 📁 Folder Structure:

```
Face-Verification-Presence/
├── deploy/                          # 📦 Deployment folder
│   ├── deploy.sh                    # 🚀 Full deployment script
│   ├── quick-update.sh              # ⚡ Quick update script
│   ├── ecosystem.config.cjs         # ⚙️ PM2 configuration
│   └── PM2-DEPLOYMENT-GUIDE.md    # 📚 Documentation ini
├── app/                           # Laravel application
├── public/                        # Public files
├── storage/                       # Storage & logs
├── artisan                        # Laravel artisan
└── ...                           # Other Laravel files
```

## 📝 Environment Configuration (.env)

### 🔧 **3 Bagian Configuration:**

#### **📦 BAGIAN 1: Production Configuration (AKTIF)**
```env
APP_ENV=production
APP_DEBUG=false  # SECURITY FIX: Always false in production!
APP_URL=https://azisfata.my.id
DB_CONNECTION=sqlite
DB_DATABASE=/home/fata/Face-Verification-Presence/database/database.sqlite
LOG_LEVEL=error
CACHE_STORE=file
SESSION_DRIVER=file
```

#### **📝 BAGIAN 2: Development Configuration (DISABLED)**
```env
# APP_ENV=local
# APP_DEBUG=true
# APP_URL=http://127.0.0.1:8000
# LOG_LEVEL=debug
# CACHE_STORE=database
# SESSION_DRIVER=database
```

#### **🗄️ BAGIAN 3: Database Switching Options**
```env
# Production MySQL (masa depan)
# DB_CONNECTION=mysql
# DB_DATABASE=face_verification_prod

# Development MySQL
# DB_CONNECTION=mysql
# DB_DATABASE=face_verification_dev
```

### 🔄 **Cara Switch Environment:**

**Production (Current):**
- Bagian 1 aktif, Bagian 2 disabled
- SQLite production sudah aktif

**Development:**
1. Comment Bagian 1 (tambah #)
2. Uncomment Bagian 2 (hapus #)
3. Pilih database option di Bagian 3

**MySQL Migration:**
1. Comment SQLite configuration
2. Uncomment MySQL option yang diinginkan

## 🆕 Setup Server Baru

Script `deploy.sh` sekarang memiliki **auto-install requirements** dan **dynamic path configuration** yang akan memeriksa dan menginstall:

### ✅ Yang Dicek & Di-install Otomatis:
- **Node.js** (v18+ recommended)
- **npm** (package manager Node.js)
- **PHP** dengan extensions: `php-cli php-fpm php-mysql php-xml php-zip php-mbstring php-curl php-bcmath php-gd php-json php-tokenizer`
- **Composer** (dependency manager PHP)
- **PM2** (process manager)
- **Git** (version control)

### 🔄 Dynamic Path Configuration:
**✅ FIX CRITICAL:** `ecosystem.config.cjs` sekarang **OTOMATIS** menyesuaikan path!
- Script akan **auto-update** working directory ke project root
- **Tidak perlu edit manual** di server baru
- Works di **semua server** dengan path berbeda

### 📋 Requirements Sistem:

#### 🖥️ **Sistem Operasi:**
- **Linux** (Ubuntu/Debian/CentOS/semua distro Linux)
- Script menggunakan `apt-get`, `curl`, `bash` commands

#### 📦 **Software Requirements:**
- **Node.js**: v18+ (warning jika < v18)
- **PHP**: v8.2+ (sesuai composer.json)
- **Memory**: 1GB+ untuk PM2 auto-restart
- **Storage**: Space untuk `node_modules` dan `vendor`
- **Internet**: Untuk download dependencies

#### ⚠️ **Important Notes:**
- ❌ **Tidak support Windows** (script menggunakan Linux commands)
- ❌ **Tidak support macOS** (berbeda package manager)
- ✅ **Hanya untuk Linux server** (Ubuntu, Debian, CentOS, dll)

### 🚀 Cara Deploy di Server Baru:

**1. Clone repository:**
```bash
git clone https://github.com/arndns/Face-Verification-Presence.git
cd Face-Verification-Presence
```

**2. Setup environment:**
```bash
# .env sudah dikonfigurasi dengan 3 bagian:
# - BAGIAN 1: Production Configuration (AKTIF)
# - BAGIAN 2: Development Configuration (DISABLED)
# - BAGIAN 3: Database Switching Options

# Edit jika perlu customization:
nano .env
```

**3. Run deployment (auto-install semua requirements):**
```bash
cd deploy
./deploy.sh
```

**Script akan otomatis:**
- ✅ Cek dan install Node.js, npm, PHP, Composer, PM2, Git
- ✅ Install dependencies (npm install, composer install)
- ✅ Build frontend assets (npm run build)
- ✅ Clear & optimize Laravel caches
- ✅ Start aplikasi dengan PM2
- ✅ Setup PM2 auto-start on boot

## 🛠️ PM2 Configuration

- **Nama**: `face-verification-app`
- **Script**: `php artisan serve --host=0.0.0.0 --port=8000`
- **Auto-restart**: ✅ Enabled
- **Memory Limit**: 1GB
- **Logs**: `storage/logs/`
- **Config Location**: `deploy/ecosystem.config.cjs`

## 📝 Script Details

### `deploy.sh` - Full Deployment
Melakukan:
1. ✅ Auto-detect project root (parent directory)
2. ✅ npm install + npm run build
3. ✅ composer install --no-dev
4. ✅ Clear & optimize Laravel caches
5. ✅ Restart PM2 + setup startup
6. ✅ Generate ecosystem config di deploy/

**Gunakan untuk:**
- Pertama kali deployment
- Update dependencies
- Update konfigurasi besar

### `quick-update.sh` - Quick Update
Melakukan:
1. ✅ Auto-detect project root
2. ✅ npm run build
3. ✅ php artisan config:clear
4. ✅ Restart PM2

**Gunakan untuk:**
- Perubahan kode kecil
- Quick fixes
- Update fitur tanpa dependency changes

## 🔧 PM2 Commands

```bash
# Cek status
pm2 status

# Lihat logs
pm2 logs face-verification-app

# Restart manual
pm2 restart face-verification-app

# Stop
pm2 stop face-verification-app
```

## 🔄 Workflow yang Direkomendasikan

### Untuk Development:
```bash
# 1. Pull changes manual
git pull origin main

# 2. Quick update untuk perubahan kecil
cd deploy && ./quick-update.sh

# 3. Full update untuk perubahan besar
cd deploy && ./deploy.sh
```

### Manual Process (jika script gagal):
```bash
git pull origin main
npm run build
php artisan config:clear
pm2 restart face-verification-app
```

## 🐛 Troubleshooting

### Application tidak jalan:
```bash
pm2 status
pm2 logs face-verification-app
```

### Build error:
```bash
npm cache clean --force
rm -rf node_modules
npm install
npm run build
```

### Laravel error:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
tail -f storage/logs/laravel.log
```

### Script location error:
```bash
# Pastikan Anda berada di folder deploy
pwd  # harus menunjukkan .../Face-Verification-Presence/deploy
ls ../artisan  # harus menunjukkan file artisan
```

---

**Application URL**: http://0.0.0.0:8000  
**PM2 Process Name**: `face-verification-app`  
**Deployment Scripts**: di folder `deploy/`
