<?php

namespace App\Http\Controllers;

use App\Models\ChannelStatsDaily;
use App\Models\Content;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Services\YoutubeAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected ?YoutubeAnalyticsService $youtubeAnalytics = null
    ) {
        $this->youtubeAnalytics = $youtubeAnalytics ?? app(YoutubeAnalyticsService::class);
    }

    public function index(Request $request)
    {
        $platformSlug = $request->query('platform', 'all');
        $isAll = ($platformSlug === 'all');
        $currentPlatform = null;

        if (! $isAll) {
            $currentPlatform = Platform::where('slug', $platformSlug)->first();
            if (! $currentPlatform) {
                abort(404, 'Platform tidak ditemukan.');
            }
        }

        $period = $request->query('period', '7'); // today, 7, 15, 30, all, custom
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');
        [$startDate, $endDate, $periodLabel] = $this->resolvePeriod($period, $customStart, $customEnd);

        // Ambil snapshot harian dari database dalam rentang periode
        $statsQuery = DB::table('content_stats_daily as csd')
            ->join('contents', 'contents.id', '=', 'csd.content_id')
            ->join('platforms', 'platforms.id', '=', 'contents.platform_id')
            ->where('contents.status', 'aktif')
            ->when(! $isAll, fn ($q) => $q->where('contents.platform_id', $currentPlatform->id))
            ->when($startDate, fn ($q) => $q->whereDate('csd.tanggal', '>=', $startDate))
            ->whereDate('csd.tanggal', '<=', $endDate)
            ->select(
                'csd.content_id', 'csd.tanggal', 'csd.views', 'csd.likes', 'csd.comments',
                'contents.judul', 'contents.url', 'contents.thumbnail_url', 'contents.platform_id',
                'platforms.slug as platform_slug', 'platforms.nama as platform_nama'
            )
            ->orderBy('csd.tanggal');

        $statsInRange = $statsQuery->get()->groupBy('content_id');

        $rows = $statsInRange->map(function ($snapshots) {
            $first = $snapshots->first();
            $last = $snapshots->last();

            $viewsTerkini = $last->views !== null ? (int) $last->views : null;
            $viewsBertambah = ($last->views === null || $first->views === null)
                ? null
                : max(0, (int) $last->views - (int) $first->views);

            $likesTerkini = $last->likes !== null ? (int) $last->likes : null;
            $commentsTerkini = $last->comments !== null ? (int) $last->comments : null;

            return (object) [
                'content_id'       => $first->content_id,
                'platform_id'      => $last->platform_id,
                'platform_slug'    => $last->platform_slug,
                'platform_nama'    => $last->platform_nama,
                'judul'            => $last->judul,
                'url'              => $last->url,
                'thumbnail_url'    => $last->thumbnail_url,
                'views_terkini'    => $viewsTerkini,
                'views_bertambah'  => $viewsBertambah,
                'likes_terkini'    => $likesTerkini,
                'comments_terkini' => $commentsTerkini,
                'tanggal_terakhir' => $last->tanggal,
            ];
        })->values();

        // 1. Total Konten Aktif
        $totalContentQuery = Content::where('status', 'aktif');
        if (! $isAll) {
            $totalContentQuery->where('platform_id', $currentPlatform->id);
        }
        $totalVideo = $totalContentQuery->count();

        // 2. Total Views Terkini (Strict NULL Semantics: abaikan NULL, jangan jadikan 0 palsu)
        $hasViews = $rows->contains(fn ($r) => $r->views_terkini !== null);
        $totalViewsTerkini = $hasViews ? $rows->whereNotNull('views_terkini')->sum('views_terkini') : null;

        // 3. Total Views Bertambah (dalam periode yang dipilih)
        $hasViewsBertambah = $rows->contains(fn ($r) => $r->views_bertambah !== null);
        $totalViewsBertambah = $hasViewsBertambah ? $rows->whereNotNull('views_bertambah')->sum('views_bertambah') : null;

        // 4. Total Likes Terkini
        $hasLikes = $rows->contains(fn ($r) => $r->likes_terkini !== null);
        $totalLikes = $hasLikes ? $rows->whereNotNull('likes_terkini')->sum('likes_terkini') : null;

        // 5. Total Comments Terkini
        $hasComments = $rows->contains(fn ($r) => $r->comments_terkini !== null);
        $totalComments = $hasComments ? $rows->whereNotNull('comments_terkini')->sum('comments_terkini') : null;

        // 6. Followers / Subscribers
        $availablePlatforms = Platform::whereHas('contents', function ($q) {
            $q->where('status', 'aktif');
        })->orderBy('id')->get();

        $platformFollowers = [];
        $platformFollowersGrowth = [];
        $platformFollowersLatestDate = [];
        $totalFollowers = 0;
        $totalFollowersGrowth = 0;
        $hasAnyFollowers = false;
        $hasAnyFollowersGrowth = false;

        foreach ($availablePlatforms as $p) {
            $fol = null;
            $folGrowth = null;
            $folDate = null;

            if ($p->slug === 'youtube') {
                $ch = ChannelStatsDaily::latest('tanggal')->first();
                $fol = $ch?->subscriber_count;

                // Coba ambil exact growth dari YouTube Analytics API (subscribersGained - subscribersLost)
                $analyticsGrowth = $this->youtubeAnalytics->getSubscriberGrowth($startDate, $endDate);
                if ($analyticsGrowth !== null) {
                    $folGrowth = $analyticsGrowth['net'];
                } else {
                    // Fallback aman ke selisih snapshot historis jika Analytics API belum diotorisasi / gagal
                    $folGrowth = ChannelStatsDaily::getFollowersGained($startDate, $endDate);
                }

                $folDate = $ch?->tanggal ? Carbon::parse($ch->tanggal)->toDateString() : null;
            } else {
                $ps = PlatformStatsDaily::where('platform_id', $p->id)->latest('tanggal')->first();
                $fol = $ps?->followers;
                $folGrowth = PlatformStatsDaily::getFollowersGained($p->id, $startDate, $endDate);
                $folDate = $ps?->tanggal ? Carbon::parse($ps->tanggal)->toDateString() : null;
            }

            $platformFollowers[$p->id] = $fol;
            $platformFollowersGrowth[$p->id] = $folGrowth;
            $platformFollowersLatestDate[$p->id] = $folDate;

            if ($fol !== null) {
                $totalFollowers += $fol;
                $hasAnyFollowers = true;
            }

            if ($folGrowth !== null) {
                $totalFollowersGrowth += $folGrowth;
                $hasAnyFollowersGrowth = true;
            }
        }

        $currentFollowers = null;
        $followersGrowth = null;
        $latestFollowersDate = null;

        if (! $isAll) {
            $currentFollowers = $platformFollowers[$currentPlatform->id] ?? null;
            $followersGrowth = $platformFollowersGrowth[$currentPlatform->id] ?? null;
            $latestFollowersDate = $platformFollowersLatestDate[$currentPlatform->id] ?? null;
        } else {
            $followersGrowth = $hasAnyFollowersGrowth ? $totalFollowersGrowth : null;
            $validDates = array_filter($platformFollowersLatestDate);
            $latestFollowersDate = ! empty($validDates) ? max($validDates) : null;
        }

        $latestSnapshotDate = $rows->max('tanggal_terakhir');

        // 7. Ringkasan Performa Per Platform (khusus untuk mode platform=all)
        $platformSummaries = [];
        if ($isAll) {
            foreach ($availablePlatforms as $p) {
                $pActiveCount = Content::where('platform_id', $p->id)->where('status', 'aktif')->count();
                $pRows = $rows->where('platform_id', $p->id);

                $pHasViews = $pRows->contains(fn ($r) => $r->views_terkini !== null);
                $pHasViewsBertambah = $pRows->contains(fn ($r) => $r->views_bertambah !== null);
                $pHasLikes = $pRows->contains(fn ($r) => $r->likes_terkini !== null);
                $pHasComments = $pRows->contains(fn ($r) => $r->comments_terkini !== null);

                $platformSummaries[] = [
                    'platform'         => $p,
                    'active_contents'  => $pActiveCount,
                    'followers'        => $platformFollowers[$p->id] ?? null,
                    'followers_growth' => $platformFollowersGrowth[$p->id] ?? null,
                    'views_terkini'    => $pHasViews ? $pRows->whereNotNull('views_terkini')->sum('views_terkini') : null,
                    'views_bertambah'  => $pHasViewsBertambah ? $pRows->whereNotNull('views_bertambah')->sum('views_bertambah') : null,
                    'likes_terkini'    => $pHasLikes ? $pRows->whereNotNull('likes_terkini')->sum('likes_terkini') : null,
                    'comments_terkini' => $pHasComments ? $pRows->whereNotNull('comments_terkini')->sum('comments_terkini') : null,
                ];
            }
        }

        // 8. Sorting Top 10 Konten Lintas Platform
        if ($totalViewsBertambah !== null && $totalViewsBertambah > 0) {
            $sorted = $rows->sort(function ($a, $b) {
                $vbA = $a->views_bertambah ?? -1;
                $vbB = $b->views_bertambah ?? -1;
                if ($vbA !== $vbB) {
                    return $vbB <=> $vbA;
                }
                return ($b->likes_terkini ?? -1) <=> ($a->likes_terkini ?? -1);
            });
        } elseif ($hasViews) {
            $sorted = $rows->sort(function ($a, $b) {
                $vtA = $a->views_terkini ?? -1;
                $vtB = $b->views_terkini ?? -1;
                if ($vtA !== $vtB) {
                    return $vtB <=> $vtA;
                }
                return ($b->likes_terkini ?? -1) <=> ($a->likes_terkini ?? -1);
            });
        } else {
            $sorted = $rows->sort(function ($a, $b) {
                $lA = $a->likes_terkini ?? -1;
                $lB = $b->likes_terkini ?? -1;
                if ($lA !== $lB) {
                    return $lB <=> $lA;
                }
                return ($b->comments_terkini ?? -1) <=> ($a->comments_terkini ?? -1);
            });
        }

        $top10 = $sorted->take(10)->values();

        // 9. Data Tren Views Harian
        $trendQuery = DB::table('content_stats_daily as csd')
            ->join('contents', 'contents.id', '=', 'csd.content_id')
            ->where('contents.status', 'aktif')
            ->when(! $isAll, fn ($q) => $q->where('contents.platform_id', $currentPlatform->id))
            ->select('csd.tanggal', DB::raw('SUM(csd.views) as total_views'))
            ->when($startDate, fn ($q) => $q->whereDate('csd.tanggal', '>=', $startDate))
            ->whereDate('csd.tanggal', '<=', $endDate)
            ->groupBy('csd.tanggal')
            ->orderBy('csd.tanggal');

        $trend = $trendQuery->get();
        $hasTrendViews = $trend->some(fn ($t) => $t->total_views !== null);

        return view('dashboard', [
            'isAll'               => $isAll,
            'currentPlatform'     => $currentPlatform,
            'availablePlatforms'  => $availablePlatforms,
            'platformSlug'        => $platformSlug,
            'period'              => $period,
            'periodLabel'         => $periodLabel,
            'customStart'         => $customStart,
            'customEnd'           => $customEnd,
            'totalVideo'          => $totalVideo,
            'totalViewsTerkini'   => $totalViewsTerkini,
            'totalViewsBertambah' => $totalViewsBertambah,
            'totalLikes'          => $totalLikes,
            'totalComments'       => $totalComments,
            'currentFollowers'    => $currentFollowers,
            'totalFollowers'      => $hasAnyFollowers ? $totalFollowers : null,
            'followersGrowth'     => $followersGrowth,
            'latestFollowersDate' => $latestFollowersDate,
            'latestSnapshotDate'  => $latestSnapshotDate,
            'platformSummaries'   => $platformSummaries,
            'top10'               => $top10,
            'hasTrendViews'       => $hasTrendViews,
            'trendLabels'         => $trend->pluck('tanggal'),
            'trendData'           => $trend->map(fn ($t) => $t->total_views !== null ? (int) $t->total_views : null),
        ]);
    }

    private function resolvePeriod(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        $today = Carbon::today();

        if ($period === 'custom' && $customStart && $customEnd) {
            $toText = __('app.dashboard.to');
            return [$customStart, $customEnd, "{$customStart} {$toText} {$customEnd}"];
        }

        return match ($period) {
            'today' => [$today->toDateString(), $today->toDateString(), __('app.dashboard.today')],
            '15'    => [$today->copy()->subDays(14)->toDateString(), $today->toDateString(), __('app.dashboard.last_15_days')],
            '30'    => [$today->copy()->subDays(29)->toDateString(), $today->toDateString(), __('app.dashboard.last_30_days')],
            'all'   => [null, $today->toDateString(), __('app.dashboard.all_time')],
            default => [$today->copy()->subDays(6)->toDateString(), $today->toDateString(), __('app.dashboard.last_7_days')],
        };
    }
}
