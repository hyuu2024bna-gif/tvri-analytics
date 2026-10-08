<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    protected $fillable = [
        'platform_id',
        'external_account_id',
        'username',
        'access_token',
        'refresh_token',
        'page_id',
        'connected_by',
        'expires_at',
        'refresh_expires_at',
    ];

    protected $casts = [
        'expires_at'         => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];

    /**
     * Kolom yang tidak boleh dikirim ke JSON/API ataupun debug dump.
     * Ini adalah lapisan keamanan tambahan di atas enkripsi.
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // =========================================================================
    // ACCESS TOKEN — Enkripsi / Dekripsi
    // =========================================================================

    /**
     * Mutator: Enkripsi access_token sebelum disimpan ke database.
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Accessor: Dekripsi access_token saat dibaca.
     * Fallback aman jika data lama belum terenkripsi (misal data testing awal).
     */
    public function getAccessTokenAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Fallback untuk data lama yang belum terenkripsi
            return $value;
        }
    }

    // =========================================================================
    // REFRESH TOKEN — Enkripsi / Dekripsi
    // =========================================================================

    /**
     * Mutator: Enkripsi refresh_token sebelum disimpan ke database.
     * Null-safe: jika TikTok tidak mengembalikan refresh_token, simpan NULL.
     */
    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Accessor: Dekripsi refresh_token saat dibaca dari database.
     * Fallback aman jika data lama belum terenkripsi.
     * Mengembalikan NULL jika tidak ada refresh_token (akun lama / platform lain).
     */
    public function getRefreshTokenAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Fallback untuk data lama yang tidak terenkripsi
            return $value;
        }
    }

    // =========================================================================
    // TOKEN HELPERS
    // =========================================================================

    /**
     * Apakah access_token sudah expired atau akan expired dalam $bufferMinutes menit ke depan?
     *
     * @param  int  $bufferMinutes  Margin waktu buffer (default 15 menit)
     */
    public function isAccessTokenExpiredOrExpiring(int $bufferMinutes = 15): bool
    {
        if (! $this->expires_at) {
            // Tidak ada info expiry — anggap tidak expired (backward compat)
            return false;
        }

        return $this->expires_at->subMinutes($bufferMinutes)->isPast();
    }

    /**
     * Apakah refresh_token tersedia dan belum expired?
     */
    public function hasValidRefreshToken(): bool
    {
        if (empty($this->refresh_token)) {
            return false;
        }

        if (! $this->refresh_expires_at) {
            // Tidak ada info expiry refresh_token — anggap masih valid
            return true;
        }

        return $this->refresh_expires_at->isFuture();
    }

    // =========================================================================
    // RELASI
    // =========================================================================

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
