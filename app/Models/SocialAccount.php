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
        'page_id',
        'connected_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Mutator: Enkripsi access_token sebelum disimpan ke database.
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Accessor: Dekripsi access_token saat dibaca, fallback aman jika data lama belum terenkripsi.
     */
    public function getAccessTokenAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
