# Panduan Operasional Scheduler & Sinkronisasi Data TVRI Analytics

Dokumen ini menjelaskan cara kerja, konfigurasi jadwal, serta instruksi menjalankan scheduler Laravel di berbagai environment (Development Local & Production).

---

## 1. Jadwal Sinkronisasi Otomatis

Seluruh task otomatis didefinisikan dalam berkas `routes/console.php`:

| Command | Jadwal (WIB) | Timezone | Lock Expiration | Log Output File |
| :--- | :---: | :---: | :---: | :--- |
| `youtube:sync` | **01:00** | `Asia/Jakarta` | `withoutOverlapping(60)` | `storage/logs/youtube-sync.log` |
| `instagram:sync` | **02:00** | `Asia/Jakarta` | `withoutOverlapping(60)` | `storage/logs/instagram-sync.log` |
| `facebook:sync` | **03:00** | `Asia/Jakarta` | `withoutOverlapping(60)` | `storage/logs/facebook-sync.log` |

### Penjelasan `withoutOverlapping(60)`
- Parameter `60` adalah **lock/mutex expiration dalam satuan MENIT** di cache store Laravel, **BUKAN execution timeout**.
- Tujuannya adalah jika suatu proses sync sebelumnya mengalami kegagalan/crash mendadak tanpa sempat melepaskan lock file, lock tersebut otomatis kedaluwarsa setelah 60 menit sehingga scheduler hari berikutnya tidak terblokir selamanya.

---

## 2. Menjalankan Scheduler di Lingkungan Development (Windows / Laragon)

Pada lingkungan Windows lokal, Laravel Scheduler **tidak berjalan otomatis** hanya dengan mendefinisikan `Schedule::command()`. Harus ada runner aktif.

### Opsi A: Menggunakan Artisan Worker (Direkomendasikan saat sesi development aktif)
Buka terminal baru di direktori root project, lalu jalankan:
```bash
php artisan schedule:work
```
Perintah ini akan terus berjalan di background terminal dan mengevaluasi seluruh jadwal cron setiap 1 menit.

### Opsi B: Menggunakan Windows Task Scheduler (Untuk otomasi lokal 24/7)
1. Buka **Task Scheduler** di Windows (`taskschd.msc`).
2. Klik **Create Task...**
   - **Name**: `TVRI Analytics Scheduler`
   - **Security Options**: Centang *Run whether user is logged on or not* / *Run with highest privileges*.
3. Pada tab **Triggers**:
   - New Trigger -> *Daily* -> Recur every 1 days.
   - Advanced settings: Centang *Repeat task every: 1 minute* for a duration of: *Indefinitely*.
4. Pada tab **Actions**:
   - Action: *Start a program*
   - Program/script: `D:\laragon\laragon\bin\php\php-8.x.x\php.exe` (sesuaikan path PHP Laragon)
   - Add arguments: `artisan schedule:run`
   - Start in: `D:\laragon\laragon\www\tvri-analytics`
5. Simpan dan aktifkan task.

---

## 3. Menjalankan Scheduler di Lingkungan Production (Linux Server / cPanel)

### Opsi A: Linux Server (Crontab)
Jalankan `crontab -e` pada server Linux, lalu tambahkan baris berikut:
```cron
* * * * * cd /path/to/tvri-analytics && php artisan schedule:run >> /dev/null 2>&1
```

### Opsi B: cPanel Cron Jobs
1. Masuk ke cPanel -> menu **Cron Jobs**.
2. Pilih Common Settings: **Once Per Minute (`* * * * *`)**.
3. Pada kolom Command, masukkan:
```bash
/usr/local/bin/php /home/username/public_html/tvri-analytics/artisan schedule:run >> /dev/null 2>&1
```
*(Sesuaikan path binary PHP dan direktori project di cPanel).*

---

## 4. Troubleshooting & Logging

Setiap eksekusi sinkronisasi mencatat progress ke berkas log:
- **YouTube sync log**: `storage/logs/youtube-sync.log`
- **Application log**: `storage/logs/laravel.log`

Untuk melihat daftar jadwal aktif:
```bash
php artisan schedule:list
```

Untuk menguji eksekusi manual satu command:
```bash
php artisan youtube:sync
```
