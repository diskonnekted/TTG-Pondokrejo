# Panduan Deployment ke Nginx + MySQL (VPS/Shared Hosting)

Panduan ini akan membantu Anda memindahkan website "TTG Pondokrejo" dari Vercel ke server sendiri (VPS atau Hosting dengan akses Nginx & MySQL).

## Persiapan Server

Pastikan server Anda sudah terinstall:
1.  **Nginx** (Web Server)
2.  **PHP 8.0+** (dengan ekstensi `php-fpm`, `php-mysql`, `php-pdo`, `php-mbstring`)
3.  **MySQL / MariaDB** (Database)

## Langkah 1: Siapkan Database MySQL

1.  Login ke MySQL/phpMyAdmin.
2.  Buat database baru, misal: `ttg_db`.
3.  Import struktur tabel dari file `database/schema_mysql.sql`.
    *   Jika menggunakan CLI:
        ```bash
        mysql -u username -p ttg_db < database/schema_mysql.sql
        ```
4.  (Opsional) Import data awal (seeding) dengan menjalankan script PHP nanti.

## Langkah 2: Upload Kode Website

### Opsi A: Menggunakan Git (Disarankan untuk VPS)
1.  Masuk ke folder web root (biasanya `/var/www/`):
    ```bash
    cd /var/www/
    ```
2.  Clone repository:
    ```bash
    git clone https://github.com/diskonnekted/TTG-Pondokrejo.git public_html
    ```
3.  Set permission agar web server bisa baca/tulis (penting untuk upload gambar):
    ```bash
    chown -R www-data:www-data /var/www/public_html
    chmod -R 755 /var/www/public_html
    chmod -R 777 /var/www/public_html/public/uploads
    ```

### Opsi B: Menggunakan GitHub Actions (Otomatis Deploy)
Saya sudah menyiapkan file workflow di `.github/workflows/deploy.yml`. Agar otomatis deploy setiap kali Anda push ke GitHub:

1.  Buka repository GitHub Anda -> **Settings** -> **Secrets and variables** -> **Actions**.
2.  Tambahkan **New repository secret**:
    *   `SERVER_HOST`: IP Address VPS Anda (misal: 103.x.x.x).
    *   `SERVER_USER`: Username SSH (misal: root).
    *   `SERVER_PASSWORD`: Password SSH Anda (atau gunakan `SERVER_KEY` untuk private key).
    *   `SERVER_PORT`: Port SSH (biasanya 22).
3.  Setiap kali Anda push ke branch `main`, GitHub akan otomatis mengupload file terbaru ke server.

### Opsi C: Upload Manual (File Manager/SFTP)
1.  Upload semua file project ke folder `public_html` di server Anda.
2.  Pastikan struktur foldernya benar. Root domain Anda harus mengarah ke folder `public`.
    *   *Catatan: Jika hosting Anda memaksa root folder adalah `public_html` dan tidak bisa diubah, pindahkan isi folder `public/*` ke `public_html/` dan folder `includes/` serta `database/` sejajar dengan `public_html`.*

## Langkah 3: Konfigurasi Nginx

Buat file konfigurasi server block Nginx (misal: `/etc/nginx/sites-available/ttg_pondokrejo`).
Contoh konfigurasi ada di file `nginx.conf.example`.

Poin penting:
*   Set `root` ke folder `/path/to/project/public`.
*   Pastikan PHP-FPM socket path sesuai dengan versi PHP Anda (misal `php8.2-fpm.sock`).

Jangan lupa restart Nginx:
```bash
sudo nginx -t
sudo systemctl restart nginx
```

## Langkah 4: Koneksi Database

Karena kita menggunakan MySQL, kita perlu mengatur kredensial database melalui **Environment Variables** atau edit langsung file `includes/config.php`.

### Cara Edit Langsung (Paling Mudah)
Buka file `includes/config.php` dan sesuaikan bagian ini (atau tambahkan hardcode jika perlu, meski tidak disarankan untuk keamanan):

Anda bisa menset Environment Variable di konfigurasi PHP-FPM (`/etc/php/8.x/fpm/pool.d/www.conf`) atau di Nginx:

**Contoh setting di Nginx (`fastcgi_param`):**
```nginx
fastcgi_param DB_HOST "localhost";
fastcgi_param DB_NAME "ttg_db";
fastcgi_param DB_USER "root";
fastcgi_param DB_PASS "password_anda";
```

Atau, jika ingin simpel, edit `includes/config.php` dan ganti baris `getenv(...)` dengan nilai langsung:
```php
$mysqlHost = 'localhost';
$mysqlName = 'ttg_db';
$mysqlUser = 'user_db';
$mysqlPass = 'pass_db';
```

## Langkah 5: Cek Website

Buka domain Anda.
1.  Coba login ke Admin (default: `admin` / `admin123`).
2.  Coba upload gambar baru.
3.  Cek apakah data tersimpan di MySQL.

Selamat! Website Anda sekarang berjalan di server sendiri tanpa batasan Vercel.
