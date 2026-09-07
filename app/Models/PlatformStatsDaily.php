<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PlatformStatsDaily extends Model
{
    protected $table = 'platform_stats_daily';

    protected $fillable = [
        'platform_id',
        'social_account_id',
        'tanggal',
        'followers',
        'total_contents',
        'total_views',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'followers' => 'integer',
        'total_contents' => 'integer',
        'total_views' => 'integer',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Hitung pertambahan followers dalam rentang periode tertentu.
     * Formula: Snapshot terbaru - Snapshot paling awal yang relevan dalam periode.
     *
     * @param int $platformId
     * @param int|string|null $daysOrStart Rentang hari (misal 7) atau tanggal awal (YYYY-MM-DD)
     * @param string|null $endDate Tanggal akhir (YYYY-MM-DD)
     * @return int|null Mengembalikan null jika snapshot histori kurang dari 2 hari atau data tidak tersedia
     */
    public static function getFollowersGained(int $platformId, $daysOrStart = 7, ?string $endDate = null): ?int
    {
        if (is_int($daysOrStart)) {
            $startDate = Carbon::today()->subDays($daysOrStart)->toDateString();
            $endDate = Carbon::today()->toDateString();
        } else {
            $startDate = $daysOrStart;
            $endDate = $endDate ?? Carbon::today()->toDateString();
        }

        $query = static::where('platform_id', $platformId);
        if ($startDate) {
            $query->where('tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('tanggal', '<=', $endDate);
        }

        $snapshots = $query->orderBy('tanggal')->get();

        if ($snapshots->count() < 2) {
            return null; // Belum cukup data histori untuk menghitung gain
        }

        $first = $snapshots->first();
        $last = $snapshots->last();

        if ($first->followers === null || $last->followers === null) {
            return null;
        }

        return (int) ($last->followers - $first->followers);
    }
}
