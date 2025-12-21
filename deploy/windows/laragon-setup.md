# Laragon Setup (Auto Start & Akses dari Device Lain)

Gunakan panduan ini jika proyek dijalankan di Laragon dan ingin otomatis aktif saat Laragon start, serta dapat diakses dari perangkat lain di jaringan yang sama.

## 1) Lokasi Proyek
- Pastikan folder ada di `C:\laragon\www\Face-Verification-Presence`.
- Laragon dengan Auto Virtual Hosts akan membuat domain `http://face-verification-presence.test` otomatis.

## 2) Hidupkan Otomatis
- Laragon > Menu > Preferences > General:
  - Centang **Auto virtual hosts**.
  - Centang **Start All Services** saat Laragon start.
- Pastikan Apache atau Nginx dipilih di **Services & Ports**.

## 3) Dengarkan di Semua Interface (agar bisa diakses dari LAN)
- Apache (`Menu > Apache > httpd.conf`): ubah `Listen 80` jadi `Listen 0.0.0.0:80` (tambahkan `Listen 0.0.0.0:443` jika pakai HTTPS).
- Nginx (`Menu > Nginx > nginx.conf`): set `listen 0.0.0.0:80;` (dan `listen 0.0.0.0:443 ssl;` jika HTTPS).

## 4) Virtual Host
- Apache contoh (letakkan di `C:\laragon\etc\apache2\sites-enabled\face-verification-presence.conf`):
  ```
  <VirtualHost *:80>
    ServerName face-verification-presence.test
    DocumentRoot "C:/laragon/www/Face-Verification-Presence/public"
    <Directory "C:/laragon/www/Face-Verification-Presence/public">
      AllowOverride All
      Require all granted
    </Directory>
  </VirtualHost>
  ```
- Nginx contoh (letakkan di `C:\laragon\etc\nginx\sites-enabled\face-verification-presence.conf`):
  ```
  server {
    listen 80;
    server_name face-verification-presence.test;
    root "C:/laragon/www/Face-Verification-Presence/public";
    index index.php index.html;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass php_upstream; }
  }
  ```

## 5) HTTPS untuk Kamera/Geolokasi
- Instal sertifikat lokal: `mkcert -install` lalu `mkcert face-verification-presence.test 192.168.x.x`.
- Apache VirtualHost 443:
  ```
  <VirtualHost *:443>
    ServerName face-verification-presence.test
    DocumentRoot "C:/laragon/www/Face-Verification-Presence/public"
    SSLEngine on
    SSLCertificateFile "C:/laragon/etc/ssl/face-verification-presence.test.pem"
    SSLCertificateKeyFile "C:/laragon/etc/ssl/face-verification-presence.test-key.pem"
    <Directory "C:/laragon/www/Face-Verification-Presence/public">
      AllowOverride All
      Require all granted
    </Directory>
  </VirtualHost>
  ```
- Nginx 443: pakai sertifikat yang sama di blok `listen 443 ssl;`.
- Import sertifikat ke device klien agar trusted, atau akses via IP + sertifikat untuk IP tersebut.

## 6) Hosts dan Firewall
- Laptop server: hosts biasanya otomatis berisi `127.0.0.1 face-verification-presence.test`. Device lain tambahkan `192.168.x.x face-verification-presence.test` (ganti IP laptop).
- Buka port 80/443 di Windows Defender Firewall (Inbound rule untuk Apache/Nginx atau langsung port).

## 7) .env
- Set `APP_URL=https://face-verification-presence.test` (atau `http://192.168.x.x` jika pakai IP).
- Setelah ubah: `php artisan config:clear` dari terminal Laragon.

## 8) Start & Akses
- Nyalakan Laragon; karena “Start All Services” aktif, Apache/Nginx + MySQL jalan otomatis.
- Akses: `https://face-verification-presence.test` (dengan hosts entry) atau `https://192.168.x.x` jika vhost/IP disiapkan.
