# Panduan Deployment Production TVRI Analytics

Dokumen ini berisi panduan resmi untuk melakukan deployment TVRI Analytics pada server production (cPanel / VPS / Cloud).

---

## 1. Konfigurasi Lingkungan (`.env`)

Pada server production, salin `.env.example` menjadi `.env` lalu sesuaikan konfigurasi berikut:

```env
APP_NAME="TVRI Analytics"
APP_ENV=production
APP_KEY=base64:... (generate via php artisan key:generate)
APP_DEBUG=false
APP_URL=https://analytics.tvriaceh.com (ganti sesuai domain production)
APP_TIMEZONE=Asia/Jakarta

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
```

> [!CRITICAL]
> **KEAMANAN PRODUCTION:**
> - Pastikan **`APP_ENV=production`** dan **`APP_DEBUG=false`**.
> - Jangan pernah mengaktifkan `APP_DEBUG=true` di server production karena dapat mengekspos credential database, API secrets, dan environment variables ke publik jika terjadi exception/error.

---

## 2. Fitur Production Tahap 1 (Platform Sync Flags)

Pada fase peluncuran Tahap 1, integrasi yang aktif adalah **YouTube** dan **TikTok**:

```env
# Platform Sync Schedules (Production Tahap 1)
YOUTUBE_SYNC_ENABLED=true
TIKTOK_SYNC_ENABLED=true
INSTAGRAM_SYNC_ENABLED=false
FACEBOOK_SYNC_ENABLED=false
```

---

## 3. Langkah-Langkah Deployment

### A. Ekstraksi dan Dependensi
1. Ekstrak arsip release `tvri-analytics-production-final.zip` ke direktori web server (misalnya direktori di luar `public_html`, lalu arahkan Document Root ke folder `public/`).
2. Jalankan instalasi dependensi PHP tanpa dev dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Aset CSS/JS sudah di-bundle sebelumnya di `public/build/` (tidak memerlukan `npm install` atau `npm run build` di server).

### B. Konfigurasi Database & Migrasi
1. Sesuaikan koneksi database MySQL/MariaDB pada berkas `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_db_tvri
   DB_USERNAME=user_db_tvri
   DB_PASSWORD=password_db_aman
   ```
2. Jalankan migrasi skema database:
   ```bash
   php artisan migrate --force
   ```
3. Jalankan seeding master platform (YouTube, TikTok, Instagram, Facebook):
   ```bash
   php artisan db:seed --class=PlatformSeeder --force
   ```

---

## 4. Pembuatan Akun Administrator Pertama

Untuk alasan keamanan, **Registrasi Publik (`/register`) dinonaktifkan secara permanen** pada aplikasi. Tidak ada pengguna publik yang dapat mendaftar sendiri.

Akun Administrator pertama dibuat secara manual dan aman oleh pengelola server melalui salah satu opsi berikut:

### Opsi 1: Melalui `php artisan tinker` (Interaktif & Aman)
```bash
php artisan tinker
```
Jalankan perintah berikut pada prompt Tinker dengan mengganti email dan password menggunakan kredensial rahasia yang Anda tentukan sendiri:
```php
\App\Models\User::create([
    'name' => 'Administrator TVRI',
    'email' => 'admin@domain-resmi-tvri.co.id',
    'password' => \Illuminate\Support\Facades\Hash::make('MASUKKAN_PASSWORD_KUAT_DAN_UNIK_DI_SINI'),
    'role' => 'admin',
    'email_verified_at' => now(),
]);
```

> [!WARNING]
> **KEBIJAKAN KATA SANDI:**
> - Administrator server **wajib** menentukan password yang unik, panjang (minimal 16 karakter), dan memiliki kombinasi huruf besar, kecil, angka, serta simbol.
> - **Jangan pernah** menggunakan password bawaan, password contoh, atau mencatat password di dalam berkas repositori/source code.
> - Password dimasukkan secara sadar dan mandiri oleh administrator langsung pada terminal server saat setup awal.

### Opsi 2: Menggunakan Database Client (phpMyAdmin / MySQL CLI)
Jika menggunakan SQL client, buat record pada tabel `users` dengan hash `bcrypt` yang di-generate mandiri pada kolom `password`, serta nilai `'admin'` pada kolom `role`.

---

## 5. Pengamanan Endpoint Diagnostik

Endpoint diagnostik berikut telah diamankan dengan middleware `auth` dan `admin`:
- `/tiktok/test`
- `/tiktok/test-videos`
- `/instagram/test`
- `/instagram/test-media`
- `/facebook/test`
- `/facebook/test-posts`
- `/diagnostic/meta`

Pengguna publik (guest) akan dialihkan ke halaman login, dan staf non-admin akan menerima respon `403 Forbidden`.

---

## 6. Setup Scheduler Cron Job

Agar sinkronisasi berkala data analitik berjalan otomatis, tambahkan Cron Job berikut pada cPanel:

```bash
* * * * * cd /path/to/tvri-analytics && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Optimasi Cache Production

Setelah `.env` dan database terkonfigurasi, jalankan optimasi cache:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
