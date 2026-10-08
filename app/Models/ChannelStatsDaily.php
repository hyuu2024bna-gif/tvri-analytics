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

    /**
     * Hitung pertambahan subscriber YouTube dalam rentang tanggal tertentu.
     */
    public static function getFollowersGained(?string $startDate = null, ?string $endDate = null): ?int
    {
        $query = static::query();
        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }
        $snapshots = $query->orderBy('tanggal')->get();

        if ($snapshots->count() < 2) {
            return null;
        }

        $first = $snapshots->first();
        $last = $snapshots->last();

        if ($first->subscriber_count === null || $last->subscriber_count === null) {
            return null;
        }

        return (int) ($last->subscriber_count - $first->subscriber_count);
    }
}
