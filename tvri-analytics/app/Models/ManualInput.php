<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualInput extends Model
{
    protected $fillable = [
        'content_id',
        'tanggal',
        'views',
        'diinput_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
