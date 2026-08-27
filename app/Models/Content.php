<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'platform_id',
        'content_id_external',
        'judul',
        'url',
        'thumbnail_url',
        'tanggal_upload',
        'status',
    ];

    protected $casts = [
        'tanggal_upload' => 'date',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function statsDaily()
    {
        return $this->hasMany(ContentStatsDaily::class);
    }

    public function manualInputs()
    {
        return $this->hasMany(ManualInput::class);
    }
}
