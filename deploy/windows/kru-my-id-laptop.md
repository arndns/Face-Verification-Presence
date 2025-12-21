# Menggunakan kru.my.id dengan Laptop (Laragon) sebagai Server

Ikuti langkah ini agar `kru.my.id` menunjuk ke laptop dan bisa diakses dari jaringan luar/lokal.

## 1) DNS
- Di panel domain, buat A record `kru.my.id` ke IP publik laptop (cek di whatismyip.com). Jika IP publik dinamis, gunakan DDNS atau Cloudflare Tunnel sebagai alternatif.

## 2) Port Forwarding + Firewall
- Router: forward port 80 dan 443 ke IP LAN laptop (mis. 192.168.1.14).
- Windows Defender Firewall: buat inbound rule untuk port 80 dan 443 (untuk Apache/Nginx Laragon).

## 3) Sertifikat (HTTPS wajib untuk kamera/geo)
- Install mkcert (jika belum): `mkcert -install`.
- Buat sertifikat: `mkcert kru.my.id`.
- Pindahkan `.pem` & `-key.pem` ke `C:\laragon\etc\ssl\` (atau biarkan di folder keluaran mkcert, sesuaikan path di vhost).

## 4) VirtualHost Apache (contoh)
Simpan sebagai `C:\laragon\etc\apache2\sites-enabled\kru.my.id.conf`:
```
<VirtualHost *:80>
  ServerName kru.my.id
  Redirect permanent / https://kru.my.id/
</VirtualHost>

<VirtualHost *:443>
  ServerName kru.my.id
  DocumentRoot "C:/laragon/www/Fata Project/Face-Verification-Presence/public"
  SSLEngine on
  SSLCertificateFile "C:/laragon/etc/ssl/kru.my.id.pem"
  SSLCertificateKeyFile "C:/laragon/etc/ssl/kru.my.id-key.pem"
  <Directory "C:/laragon/www/Fata Project/Face-Verification-Presence/public">
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```
Untuk Nginx, buat server block setara (listen 80 redirect ke 443, root ke folder `public`, php-fpm upstream laragon).

## 5) Laragon
- Preferences > General: aktifkan **Auto virtual hosts** & **Start All Services**.
- Pastikan Apache/Nginx jalan, dan `Listen` di `httpd.conf`/`nginx.conf` memakai `0.0.0.0:80` dan `0.0.0.0:443`.

## 6) Laravel
- `.env` sudah diset `APP_URL=https://kru.my.id`. Jika ubah, jalankan `php artisan config:clear`.
- Pastikan aset sudah dibuild: `npm run build`.

## 7) Jalankan
- Restart Laragon (Apache/Nginx reload).
- Akses dari luar: `https://kru.my.id`. Jika pakai self-signed/mkcert, import sertifikat ke perangkat klien; atau gunakan Let’s Encrypt di server publik jika IP dan port sudah terbuka.
