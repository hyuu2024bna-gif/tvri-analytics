# Panduan Operasional Scheduler & Sinkronisasi Data TVRI Analytics

Dokumen ini menjelaskan cara kerja, konfigurasi jadwal, serta instruksi lengkap menjalankan scheduler Laravel di berbagai environment (khususnya Windows/Laragon untuk development dan Linux/cPanel untuk production).

---

## ⚠️ PERINGATAN PENTING: KESALAHPAHAMAN UMUM DI LARAGON / WINDOWS

> **Laravel Scheduler TIDAK berjalan otomatis hanya karena Apache, MySQL, atau Laragon berstatus "Started" ("Start All").**

### Mengapa?
- **Laragon** hanya bertugas menjalankan service web server (Apache/Nginx) untuk melayani request HTTP dan database server (MySQL) untuk query data.
- **Laravel Task Scheduler** adalah subsistem berbasis command-line (`php artisan schedule:run`) yang dirancang untuk dieksekusi **setiap satu menit** oleh scheduler di tingkat Sistem Operasi.
- Jika Sistem Operasi (Windows Task Scheduler / Cron di Linux) atau process runner (`php artisan schedule:work`) tidak aktif, maka event sinkronisasi pada `routes/console.php` (seperti `youtube:sync` jam 01:00 WIB) **TIDAK AKAN PERNAH DIJALANKAN**, sehingga snapshot harian akan kosong/bolong.

---

## 1. Jadwal Sinkronisasi Otomatis

Seluruh task otomatis didefinisikan dalam berkas `routes/console.php`:

| Command | Jadwal (WIB) | Timezone | Status Production Tahap 1 | Mutex / Lock Expiration | Log Output File |
| :--- | :---: | :---: | :---: | :---: | :--- |
| `youtube:sync` | **01:00** | `Asia/Jakarta` | **Aktif** (`YOUTUBE_SYNC_ENABLED=true`) | `withoutOverlapping(60)` | `storage/logs/youtube-sync.log` |
| `instagram:sync` | **02:00** | `Asia/Jakarta` | *Nonaktif Sementara* (`INSTAGRAM_SYNC_ENABLED=false`) | `withoutOverlapping(60)` | `storage/logs/instagram-sync.log` |
| `facebook:sync` | **03:00** | `Asia/Jakarta` | *Nonaktif Sementara* (`FACEBOOK_SYNC_ENABLED=false`) | `withoutOverlapping(60)` | `storage/logs/facebook-sync.log` |
| `tiktok:sync` | **04:00** | `Asia/Jakarta` | **Aktif** (`TIKTOK_SYNC_ENABLED=true`) | `withoutOverlapping(60)` | `storage/logs/tiktok-sync.log` |

> **Catatan Production Tahap 1:**
> - **Aktif:** YouTube (`01:00 WIB`) dan TikTok (`04:00 WIB`).
> - **Nonaktif:** Instagram dan Facebook dinonaktifkan sementara dan dapat diaktifkan kembali melalui environment variable kapan saja tanpa mengubah codebase.

### Penjelasan Parameter `withoutOverlapping(60)`
- Parameter `60` adalah **lock/mutex expiration dalam satuan MENIT** pada cache store Laravel, **BUKAN execution timeout**.
- Jika proses sync hari sebelumnya mati mendadak (misal: server crash, listrik padam, proses di-kill) tanpa sempat melepas file lock mutex, lock tersebut akan kedaluwarsa secara otomatis setelah 60 menit sehingga scheduler hari berikutnya tidak terblokir permanen.

---

## 2. Menjalankan Scheduler di Lingkungan Development (Windows / Laragon)

Pilih salah satu metode di bawah ini sesuai kebutuhan kerja Anda:

### Opsi A: Menggunakan Artisan Worker (Paling Mudah Saat Sesi Kerja/Coding)
Buka terminal baru di direktori root project TVRI Analytics, lalu jalankan:
```bash
php artisan schedule:work
```
- Perintah ini akan berjalan terus-menerus di jendela terminal.
- Setiap menit, worker ini memanggil runner scheduler secara internal dan mengeksekusi command tepat pada jadwalnya (misal `youtube:sync` pada 01:00 WIB).
- *Catatan*: Worker ini akan berhenti jika terminal ditutup atau laptop dimatikan/sleep.

---

### Opsi B: Otomatisasi Permanen Menggunakan Windows Task Scheduler (24/7)
Agar scheduler tetap berjalan otomatis di latar belakang tanpa harus membuka terminal manual setiap hari, manfaatkan **Windows Task Scheduler**:

#### Cara 1: Setup via GUI Windows Task Scheduler (`taskschd.msc`)
1. Tekan tombol `Win + R`, ketik `taskschd.msc`, lalu tekan **Enter**.
2. Pada panel kanan, klik **Create Task...** (Bukan *Create Basic Task*).
3. **Tab General**:
   - **Name**: `TVRI Analytics Scheduler`
   - **Description**: `Menjalankan php artisan schedule:run setiap 1 menit untuk TVRI Analytics`
   - Pilih opsi: **Run whether user is logged on or not** (Jalankan meskipun user tidak sedang login).
   - Centang: **Run with highest privileges**.
4. **Tab Triggers**:
   - Klik **New...**
   - Begin the task: **On a schedule**.
   - Settings: Pilih **Daily**, Recur every: `1` days.
   - Pada bagian **Advanced settings**:
     - Centang **Repeat task every**: pilih `1 minute`.
     - For a duration of: pilih **Indefinitely** (selamanya).
     - Centang **Enabled**.
   - Klik **OK**.
5. **Tab Actions**:
   - Klik **New...**
   - Action: **Start a program**.
   - **Program/script**: Arahkan ke binary PHP Laragon, contoh:
     `D:\laragon\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe`
     *(Cek versi PHP aktif Anda di folder `D:\laragon\laragon\bin\php\`)*
   - **Add arguments**: `artisan schedule:run`
   - **Start in**: Masukkan path folder project Anda:
     `D:\laragon\laragon\www\tvri-analytics`
   - Klik **OK**.
6. **Tab Settings**:
   - Centang **Allow task to be run on demand**.
   - Centang **Run task as soon as possible after a scheduled start is missed** (jika laptop sempat sleep/mati, task langsung dieksekusi saat hidup kembali).
7. Klik **OK** dan masukkan password akun Windows jika diminta.

#### Cara 2: Setup Cepat via PowerShell (Administrator)
Buka PowerShell sebagai Administrator dan sesuaikan path PHP:
```powershell
$phpPath = "D:\laragon\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
$projectPath = "D:\laragon\laragon\www\tvri-analytics"

$action = New-ScheduledTaskAction -Execute $phpPath -Argument "artisan schedule:run" -WorkingDirectory $projectPath
$trigger = New-ScheduledTaskTrigger -Daily -At 00:00
$trigger.RepetitionInterval = (New-TimeSpan -Minutes 1)
$trigger.RepetitionDuration = [System.TimeSpan]::MaxValue
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "TVRI-Analytics-Scheduler" -Action $action -Trigger $trigger -Settings $settings -Description "Laravel Scheduler for TVRI Analytics"
```

---

## 3. Menjalankan Scheduler di Lingkungan Production (Linux / cPanel)

### Opsi A: Linux Server (Cron Daemon)
Buka crontab dengan perintah:
```bash
crontab -e
```
Tambahkan baris berikut di bagian akhir file:
```cron
* * * * * cd /var/www/tvri-analytics && php artisan schedule:run >> /dev/null 2>&1
```

### Opsi B: cPanel Cron Jobs
1. Masuk ke cPanel -> menu **Cron Jobs** (Tugas Cron).
2. Pada bagian *Add New Cron Job*:
   - Common Settings: **Once Per Minute (`* * * * *`)**.
   - Command:
     ```bash
     /usr/local/bin/php /home/username/public_html/tvri-analytics/artisan schedule:run >> /dev/null 2>&1
     ```
     *(Ganti `/usr/local/bin/php` dan `/home/username/public_html/tvri-analytics` sesuai path server cPanel Anda).*
3. Klik **Add New Cron Job**.

---

## 4. Monitoring, Log, & Verifikasi

### Melihat Daftar Jadwal Aktif
Jalankan perintah berikut di terminal project:
```bash
php artisan schedule:list
```
Output yang diharapkan (Production Tahap 1: YouTube & TikTok aktif):
```text
 0 18 * * * php artisan youtube:sync ........ Next Due: 01:00 WIB (Asia/Jakarta)
 0 21 * * * php artisan tiktok:sync ......... Next Due: 04:00 WIB (Asia/Jakarta)
```
*(Catatan: `0 18 * * *` UTC sama persis dengan `01:00 WIB` / UTC+7, dan `0 21 * * *` UTC sama persis dengan `04:00 WIB` / UTC+7).*

### Memeriksa Berkas Log
- Log output command YouTube:
  `storage/logs/youtube-sync.log`
- Log output command TikTok:
  `storage/logs/tiktok-sync.log`
- Log aplikasi Laravel (mencatat `[SYNC START]`, `[SYNC SUCCESS]`, dan `[SYNC FAILED]`):
  `storage/logs/laravel.log`

### Menguji Eksekusi Manual
Jika ingin menguji satu kali sinkronisasi manual di terminal:
```bash
php artisan youtube:sync
php artisan tiktok:sync
```
Command ini aman dijalankan berulang karena memiliki mekanisme **Idempotent Snapshot** (jika snapshot untuk tanggal hari ini sudah ada, data tidak akan menduplikasi baris, serta melindungi field valid agar tidak ditimpa).
