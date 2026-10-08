# Panduan Konfigurasi Public URL & TikTok Developer Portal
**Project:** KMB TVRI Analytics (TVRI Stasiun Aceh)  
**Tujuan:** Mempersiapkan perpindahan konfigurasi dari local development (`tvri-analytics.test`) ke domain publik produksi (`DOMAIN-PUBLIK-AKTUAL`) tanpa hardcode URL dan tanpa merusak environment lokal.

---

## 1. Arsitektur URL Environment

Aplikasi KMB TVRI Analytics menggunakan helper standar Laravel (`route()`, `url()`, dan `config('app.url')`) untuk menghasilkan seluruh URL internal dan endpoint OAuth callback. Tidak ada domain yang di-hardcode dalam controller, service, maupun file Blade.

### LOCAL ENVIRONMENT (Saat Ini)
```env
APP_URL=https://tvri-analytics.test

# Konfigurasi TikTok OAuth Lokal
TIKTOK_REDIRECT_URI=https://tvri-analytics.test/tiktok/callback
```

### PRODUCTION ENVIRONMENT (Saat Siap Deployment)
```env
APP_URL=https://DOMAIN-PUBLIK-AKTUAL

# Konfigurasi TikTok OAuth Production
TIKTOK_REDIRECT_URI=https://DOMAIN-PUBLIK-AKTUAL/tiktok/callback
```

> **CATATAN PENTING:**  
> Jangan mengganti `DOMAIN-PUBLIK-AKTUAL` dengan domain contoh atau domain fiktif. Gantilah secara langsung dengan domain publik resmi yang telah dialokasikan untuk TVRI Stasiun Aceh ketika server produksi sudah aktif.

---

## 2. Pemetaan URL Legal & OAuth

Berdasarkan arsitektur konfigurasi di atas, seluruh endpoint publik dan OAuth akan terpetakan secara otomatis:

| Komponen | Local URL | Production URL |
| :--- | :--- | :--- |
| **Terms of Service** | `https://tvri-analytics.test/terms` | `https://DOMAIN-PUBLIK-AKTUAL/terms` |
| **Privacy Policy** | `https://tvri-analytics.test/privacy` | `https://DOMAIN-PUBLIK-AKTUAL/privacy` |
| **TikTok OAuth Callback** | `https://tvri-analytics.test/tiktok/callback` | `https://DOMAIN-PUBLIK-AKTUAL/tiktok/callback` |
| **TikTok OAuth Connect** | `https://tvri-analytics.test/tiktok/connect` | `https://DOMAIN-PUBLIK-AKTUAL/tiktok/connect` |

---

## 3. Checklist Konfigurasi TikTok Developer Portal

Ketika domain produksi resmi (`DOMAIN-PUBLIK-AKTUAL`) sudah tersedia dan diarahkan ke server aplikasi, lakukan pembaruan konfigurasi pada **TikTok for Developers Portal**:

1. **Production `APP_URL` di Server:**
   Pastikan environment file `.env` di server produksi telah diset:
   ```env
   APP_URL=https://DOMAIN-PUBLIK-AKTUAL
   TIKTOK_REDIRECT_URI=https://DOMAIN-PUBLIK-AKTUAL/tiktok/callback
   ```

2. **Web / Desktop URL TikTok App:**
   Pada pengaturan aplikasi TikTok Developer (bagian Basic Settings / App Details):
   - Masukkan Website / Desktop URL:
     ```
     https://DOMAIN-PUBLIK-AKTUAL
     ```

3. **Login Kit Redirect URI:**
   Pada konfigurasi produk **Login Kit for Web**:
   - Tambahkan Redirect URI produksi:
     ```
     https://DOMAIN-PUBLIK-AKTUAL/tiktok/callback
     ```
   *(Untuk keperluan testing local sandbox, URL `https://tvri-analytics.test/tiktok/callback` tetap dapat dipertahankan di daftar Redirect URI jika portal mengizinkan multiple URI).*

4. **Terms of Service URL:**
   Pada App Settings / Compliance section:
   - Masukkan URL resmi Terms of Service:
     ```
     https://DOMAIN-PUBLIK-AKTUAL/terms
     ```

5. **Privacy Policy URL:**
   Pada App Settings / Compliance section:
   - Masukkan URL resmi Privacy Policy:
     ```
     https://DOMAIN-PUBLIK-AKTUAL/privacy
     ```

---

## 4. Keamanan dan Batasan

- **Enkripsi Token:** Access token TikTok disimpan dalam basis data secara terenkripsi (`encrypted`).
- **Skema Data:** Aplikasi saat ini tidak menyimpan refresh token TikTok (karena tabel `social_accounts` belum memiliki kolom `refresh_token`).
- **Data Credential:** Jangan pernah mempublikasikan `TIKTOK_CLIENT_KEY` atau `TIKTOK_CLIENT_SECRET` pada repositori publik atau dokumentasi publik.
- **Review TikTok:** Jangan melakukan submit TikTok App Review sebelum domain publik aktif dengan sertifikat SSL valid (HTTPS) dan dapat diakses publik.
