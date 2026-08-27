<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentStatsDaily extends Model
{
    protected $table = 'content_stats_daily';

    protected $fillable = [
        'content_id',
        'tanggal',
        'views',
        'likes',
        'comments',
        'diinput_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function inputOleh()
    {
        return $this->belongsTo(User::class, 'diinput_oleh');
    }
}
