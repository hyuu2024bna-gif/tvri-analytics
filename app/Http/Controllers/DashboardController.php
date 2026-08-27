<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $platformSlug = $request->query('platform', 'youtube');
        $platform = Platform::where('slug', $platformSlug)->first();

        if (! $platform) {
            abort(404, 'Platform tidak ditemukan.');
        }

        $period = $request->query('period', '7'); // today, 7, 15, 30, all, custom
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');
        [$startDate, $endDate, $periodLabel] = $this->resolvePeriod($period, $customStart, $customEnd);

        $statsInRange = DB::table('content_stats_daily as csd')
            ->join('contents', 'contents.id', '=', 'csd.content_id')
            ->where('contents.status', 'aktif')
            ->where('contents.platform_id', $platform->id)
            ->when($startDate, fn ($q) => $q->where('csd.tanggal', '>=', $startDate))
            ->where('csd.tanggal', '<=', $endDate)
            ->select(
                'csd.content_id', 'csd.tanggal', 'csd.views', 'csd.likes', 'csd.comments',
                'contents.judul', 'contents.url', 'contents.thumbnail_url'
            )
            ->orderBy('csd.tanggal')
            ->get()
            ->groupBy('content_id');

        $rows = $statsInRange->map(function ($snapshots) {
            $first = $snapshots->first();
            $last = $snapshots->last();

            return (object) [
                'content_id'       => $first->content_id,
                'judul'            => $last->judul,
                'url'              => $last->url,
                'thumbnail_url'    => $last->thumbnail_url,
                'views_terkini'    => $last->views,
                'views_bertambah'  => max(0, $last->views - $first->views),
                'likes_terkini'    => $last->likes,
                'comments_terkini' => $last->comments,
                'tanggal_terakhir' => $last->tanggal,
            ];
        })->values();

        $totalVideo = $rows->count();
        $totalViewsTerkini = $rows->sum('views_terkini');
        $totalViewsBertambah = $rows->sum('views_bertambah');
        $totalLikes = $rows->sum('likes_terkini');
        $latestSnapshotDate = $rows->max('tanggal_terakhir');

        $top10 = $totalViewsBertambah > 0
            ? $rows->sortByDesc('views_bertambah')->take(10)->values()
            : $rows->sortByDesc('views_terkini')->take(10)->values();

        $trend = DB::table('content_stats_daily as csd')
            ->join('contents', 'contents.id', '=', 'csd.content_id')
            ->where('contents.platform_id', $platform->id)
            ->select('csd.tanggal', DB::raw('SUM(csd.views) as total_views'))
            ->when($startDate, fn ($q) => $q->where('csd.tanggal', '>=', $startDate))
            ->where('csd.tanggal', '<=', $endDate)
            ->groupBy('csd.tanggal')
            ->orderBy('csd.tanggal')
            ->get();

        $availablePlatforms = Platform::whereHas('contents', function ($q) {
            $q->where('status', 'aktif');
        })->orderBy('nama')->get();

        return view('dashboard', [
            'platform' => $platform,
            'availablePlatforms' => $availablePlatforms,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'customStart' => $customStart,
            'customEnd' => $customEnd,
            'totalVideo' => $totalVideo,
            'totalViewsTerkini' => $totalViewsTerkini,
            'totalViewsBertambah' => $totalViewsBertambah,
            'totalLikes' => $totalLikes,
            'latestSnapshotDate' => $latestSnapshotDate,
            'top10' => $top10,
            'trendLabels' => $trend->pluck('tanggal'),
            'trendData' => $trend->pluck('total_views'),
        ]);
    }

    private function resolvePeriod(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        $today = Carbon::today();

        if ($period === 'custom' && $customStart && $customEnd) {
            return [$customStart, $customEnd, "{$customStart} s/d {$customEnd}"];
        }

        return match ($period) {
            'today' => [$today->toDateString(), $today->toDateString(), 'Hari Ini'],
            '15'    => [$today->copy()->subDays(14)->toDateString(), $today->toDateString(), '15 Hari Terakhir'],
            '30'    => [$today->copy()->subDays(29)->toDateString(), $today->toDateString(), '30 Hari Terakhir'],
            'all'   => [null, $today->toDateString(), 'Semua Waktu'],
            default => [$today->copy()->subDays(6)->toDateString(), $today->toDateString(), '7 Hari Terakhir'],
        };
    }
}
