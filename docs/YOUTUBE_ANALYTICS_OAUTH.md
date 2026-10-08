# Panduan Otorisasi YouTube Analytics API (Exact Subscriber Growth)

Dokumen ini menjelaskan langkah-langkah setup Google OAuth 2.0 untuk mengaktifkan **YouTube Analytics API** guna memperoleh data pertumbuhan subscriber secara exact (`subscribersGained` & `subscribersLost`).

---

## 1. Mengapa YouTube Analytics API Diperlukan?

- **YouTube Data API v3 (`channels.list`)**:
  Field `statistics.subscriberCount` bersifat **dibulatkan (rounded)** oleh YouTube (misal: 27.600, 27.700, 27.900). Selisih snapshot antar hari hanya akan menghasilkan angka kelipatan ratusan (misal: +100, +200, +300), bukan angka riil.
- **YouTube Analytics API (`reports.query`)**:
  Menyediakan metrik exact:
  - `subscribersGained`: Jumlah subscriber baru yang subscribe pada periode tersebut.
  - `subscribersLost`: Jumlah subscriber yang unsubscribe pada periode tersebut.
  - **Net Growth**: `subscribersGained - subscribersLost` (misal: `350 - 38 = +312`).

---

## 2. Langkah Setup di Google Cloud Console

1. Buka [Google Cloud Console](https://console.cloud.google.com/).
2. Buat project baru atau pilih project yang sudah ada (misal: `TVRI-Analytics`).
3. Buka menu **APIs & Services** > **Library**:
   - Cari dan aktifkan **YouTube Analytics API**.
   - (Pastikan juga **YouTube Data API v3** sudah aktif untuk sync konten).
4. Buka menu **APIs & Services** > **OAuth consent screen**:
   - Pilih User Type: **External** (atau Internal jika menggunakan Google Workspace organisasi).
   - Isi Nama Aplikasi: `TVRI Analytics`.
   - Tambahkan Scope:
     `https://www.googleapis.com/auth/yt-analytics.readonly`
   - Pada bagian **Test users**, tambahkan alamat email Google / akun YouTube yang mengelola channel TVRI Aceh.
5. Buka menu **APIs & Services** > **Credentials**:
   - Klik **Create Credentials** > **OAuth client ID**.
   - Application type: **Web application**.
   - Name: `TVRI Analytics Web Client`.
   - **Authorized redirect URIs**:
     Tambahkan URL callback aplikasi Anda, contoh:
     - `http://localhost:8000/youtube/callback` (untuk local dev `artisan serve`)
     - `http://tvri-analytics.test/youtube/callback` (untuk local dev Laragon virtual host)
     - `https://analytics.tvri.co.id/youtube/callback` (untuk production)
   - Klik **Create**, lalu salin **Client ID** dan **Client Secret**.

---

## 3. Konfigurasi di File `.env`

Tambahkan variabel berikut ke file `.env` di server/komputer Anda:

```env
# YouTube Data API v3 (Public API Key untuk sync konten & snapshot channel)
YOUTUBE_API_KEY=AIzaSy...
YOUTUBE_CHANNEL_ID=UCS6QvcOIR7pzOhUAQaYK1Ig

# YouTube Analytics API (OAuth 2.0 untuk Exact Subscriber Growth)
YOUTUBE_CLIENT_ID=xxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
YOUTUBE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx
YOUTUBE_REDIRECT_URI="${APP_URL}/youtube/callback"
```

---

## 4. Melakukan Otorisasi (Mendapatkan Token)

Tersedia 2 metode otorisasi:

### Metode A: Otorisasi via Web Dashboard (Direkomendasikan)
1. Pastikan variabel `YOUTUBE_CLIENT_ID`, `YOUTUBE_CLIENT_SECRET`, dan `YOUTUBE_REDIRECT_URI` sudah ada di `.env`.
2. Login ke aplikasi TVRI Analytics sebagai admin.
3. Akses URL:
   `http://localhost:8000/youtube/connect`
4. Anda akan dialihkan ke halaman Google Consent Screen.
5. Pilih akun Google pemilik/pengelola channel YouTube TVRI Aceh.
6. Klik **Izinkan / Allow** untuk memberikan izin baca analitik channel (`yt-analytics.readonly`).
7. Google akan me-redirect kembali ke `/youtube/callback`, dan refresh token otomatis tersimpan terenkripsi di database (`social_accounts`).

---

### Metode B: Menggunakan Refresh Token Permanen di `.env`
Jika server tidak mengizinkan redirect web browser (misal environment headless/server tertutup):
1. Buka [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground/).
2. Di pojok kanan atas, klik ikon gerigi ⚙️:
   - Centang **Use your own OAuth credentials**.
   - Masukkan **OAuth Client ID** dan **OAuth Client secret** Anda.
3. Pada panel kiri (Step 1), masukkan scope:
   `https://www.googleapis.com/auth/yt-analytics.readonly`
4. Klik **Authorize APIs** dan login dengan akun channel YouTube TVRI.
5. Pada Step 2, klik **Exchange authorization code for tokens**.
6. Salin nilai **Refresh token**, lalu tempel di `.env`:
   ```env
   YOUTUBE_REFRESH_TOKEN=1//04xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
7. Aplikasi akan otomatis menggunakan refresh token tersebut untuk me-refresh access token secara transparan.

---

## 5. Keamanan Kredensial & Fallback

- **Perlindungan Token**:
  - `client_secret`, `access_token`, dan `refresh_token` **TIDAK PERNAH** ditampilkan pada UI atau berkas log.
  - Access token di-cache di Laravel Cache dengan masa berlaku otomatis (TTL).
  - Jika token disimpan di database, kolom `access_token` pada `social_accounts` otomatis dienkripsi dengan enkripsi AES-256 Laravel (`Crypt::encryptString`).
- **Backward Compatibility (Graceful Fallback)**:
  - Jika otorisasi belum dilakukan atau API Google sedang mengalami gangguan jaringan, sistem **TIDAK AKAN ERROR** dan **TIDAK MENAMPILKAN DATA PALSU**.
  - Dashboard secara otomatis melakukan fallback aman ke selisih snapshot historis `ChannelStatsDaily::getFollowersGained($startDate, $endDate)`.
  - Snapshot total subscriber `channel_stats_daily.subscriber_count` tetap terjaga sebagai snapshot harian resmi.
