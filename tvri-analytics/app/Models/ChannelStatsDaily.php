<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelStatsDaily extends Model
{
    protected $table = 'channel_stats_daily';

    protected $fillable = [
        'tanggal',
        'subscriber_count',
        'total_views',
        'video_count',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
