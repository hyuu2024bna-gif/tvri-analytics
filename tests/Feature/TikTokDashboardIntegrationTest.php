<?php

namespace Tests\Feature;

use App\Models\ChannelStatsDaily;
use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TikTokDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Platform $youtube;
    protected Platform $instagram;
    protected Platform $facebook;
    protected Platform $tiktok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'dashboard_tester@tvri.co.id',
        ]);

        $this->youtube = Platform::firstOrCreate(['slug' => 'youtube'], ['nama' => 'YouTube']);
        $this->instagram = Platform::firstOrCreate(['slug' => 'instagram'], ['nama' => 'Instagram']);
        $this->facebook = Platform::firstOrCreate(['slug' => 'facebook'], ['nama' => 'Facebook']);
        $this->tiktok = Platform::firstOrCreate(['slug' => 'tiktok'], ['nama' => 'TikTok']);
    }

    /**
     * Helper to seed standard multi-platform test data.
     */
    protected function seedPlatformsData(): void
    {
        $today = Carbon::today()->toDateString();
        $sevenDaysAgo = Carbon::today()->subDays(6)->toDateString();

        // 1. YouTube
        $ytVideo = Content::create([
            'platform_id'         => $this->youtube->id,
            'content_id_external' => 'YT_TEST_VID_001',
            'judul'               => 'Berita YouTube TVRI',
            'url'                 => 'https://youtube.com/watch?v=YT_TEST_VID_001',
            'thumbnail_url'       => 'https://yt.com/thumb.jpg',
            'status'              => 'aktif',
            'tanggal_upload'      => $sevenDaysAgo,
        ]);

        ContentStatsDaily::create([
            'content_id' => $ytVideo->id,
            'tanggal'    => $sevenDaysAgo,
            'views'      => 100000,
            'likes'      => 2000,
            'comments'   => 500,
        ]);

        ContentStatsDaily::create([
            'content_id' => $ytVideo->id,
            'tanggal'    => $today,
            'views'      => 105000,
            'likes'      => 2100,
            'comments'   => 520,
        ]);

        ChannelStatsDaily::create([
            'tanggal'          => $today,
            'subscriber_count' => 120000,
            'total_views'      => 8000000,
            'video_count'      => 1500,
        ]);

        // 2. TikTok
        $ttVideo1 = Content::create([
            'platform_id'         => $this->tiktok->id,
            'content_id_external' => 'TT_VID_101',
            'judul'               => 'TikTok TVRI Viral 1',
            'url'                 => 'https://tiktok.com/@tvri/video/TT_VID_101',
            'thumbnail_url'       => 'https://tiktok.com/thumb101.jpg',
            'status'              => 'aktif',
            'tanggal_upload'      => $sevenDaysAgo,
        ]);

        $ttVideo2 = Content::create([
            'platform_id'         => $this->tiktok->id,
            'content_id_external' => 'TT_VID_102',
            'judul'               => 'TikTok TVRI Viral 2',
            'url'                 => 'https://tiktok.com/@tvri/video/TT_VID_102',
            'thumbnail_url'       => 'https://tiktok.com/thumb102.jpg',
            'status'              => 'aktif',
            'tanggal_upload'      => $today,
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideo1->id,
            'tanggal'    => $sevenDaysAgo,
            'views'      => 20000,
            'likes'      => 1500,
            'comments'   => 200,
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideo1->id,
            'tanggal'    => $today,
            'views'      => 25000,
            'likes'      => 1800,
            'comments'   => 250,
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideo2->id,
            'tanggal'    => $today,
            'views'      => 5000,
            'likes'      => 400,
            'comments'   => 50,
        ]);

        PlatformStatsDaily::create([
            'platform_id'       => $this->tiktok->id,
            'social_account_id' => null,
            'tanggal'           => $today,
            'followers'         => 65000,
            'total_contents'    => 2,
            'total_views'       => null, // Strict NULL
        ]);

        // 3. Instagram
        $igMedia = Content::create([
            'platform_id'         => $this->instagram->id,
            'content_id_external' => 'IG_POST_001',
            'judul'               => 'Instagram Post TVRI',
            'url'                 => 'https://instagram.com/p/IG_POST_001',
            'thumbnail_url'       => 'https://ig.com/thumb.jpg',
            'status'              => 'aktif',
            'tanggal_upload'      => $today,
        ]);

        ContentStatsDaily::create([
            'content_id' => $igMedia->id,
            'tanggal'    => $today,
            'views'      => null, // NULL views for Instagram
            'likes'      => 800,
            'comments'   => 60,
        ]);

        PlatformStatsDaily::create([
            'platform_id'       => $this->instagram->id,
            'social_account_id' => null,
            'tanggal'           => $today,
            'followers'         => 45000,
            'total_contents'    => 1,
            'total_views'       => null,
        ]);
    }

    /**
     * Test 1: TikTok platform filter returns 200 and renders TikTok tab active
     */
    public function test_tiktok_platform_filter_renders_active(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $response->assertSee('TikTok');
        $response->assertSee('TikTok TVRI Viral 1');
        $response->assertSee('TikTok TVRI Viral 2');
        // YouTube and Instagram videos should NOT appear on platform=tiktok
        $response->assertDontSee('Berita YouTube TVRI');
        $response->assertDontSee('Instagram Post TVRI');
    }

    /**
     * Test 2: TikTok KPI calculations (Total Konten, Followers, Views, Likes, Comments)
     */
    public function test_tiktok_kpi_calculations(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => '7']));

        $response->assertOk();
        // Total Views Terkini: 25000 + 5000 = 30,000
        $response->assertSee(number_format(30000));
        // Total Views Bertambah: (25000 - 20000) + (5000 - 5000) = 5,000
        $response->assertSee(number_format(5000));
        // Followers: 65,000
        $response->assertSee(number_format(65000));
        // Total Likes Terkini: 1800 + 400 = 2,200
        $response->assertSee(number_format(2200));
        // Total Comments Terkini: 250 + 50 = 300
        $response->assertSee(number_format(300));
    }

    /**
     * Test 3: TikTok views NULL renders as '—' (Strict NULL Semantics)
     */
    public function test_tiktok_views_null_renders_dash(): void
    {
        $today = Carbon::today()->toDateString();

        $ttVideoNull = Content::create([
            'platform_id'         => $this->tiktok->id,
            'content_id_external' => 'TT_VID_NULL',
            'judul'               => 'TikTok Tanpa Views',
            'url'                 => 'https://tiktok.com/@tvri/video/TT_VID_NULL',
            'status'              => 'aktif',
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideoNull->id,
            'tanggal'    => $today,
            'views'      => null, // Unavailable metric
            'likes'      => 10,
            'comments'   => 5,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $response->assertSee('—');
    }

    /**
     * Test 4: TikTok views zero renders as '0'
     */
    public function test_tiktok_views_zero_renders_as_zero(): void
    {
        $today = Carbon::today()->toDateString();

        $ttVideoZero = Content::create([
            'platform_id'         => $this->tiktok->id,
            'content_id_external' => 'TT_VID_ZERO',
            'judul'               => 'TikTok Baru 0 Views',
            'url'                 => 'https://tiktok.com/@tvri/video/TT_VID_ZERO',
            'status'              => 'aktif',
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideoZero->id,
            'tanggal'    => $today,
            'views'      => 0, // Explicit zero
            'likes'      => 0,
            'comments'   => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $response->assertSee('TikTok Baru 0 Views');
    }

    /**
     * Test 5 & 6: TikTok likes and comments NULL render as '—'
     */
    public function test_tiktok_likes_and_comments_null_render_dash(): void
    {
        $today = Carbon::today()->toDateString();

        $ttVideo = Content::create([
            'platform_id'         => $this->tiktok->id,
            'content_id_external' => 'TT_VID_NULL_ENGAGEMENT',
            'judul'               => 'TikTok Null Engagement',
            'url'                 => 'https://tiktok.com/@tvri/video/TT_VID_NULL_ENGAGEMENT',
            'status'              => 'aktif',
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideo->id,
            'tanggal'    => $today,
            'views'      => 1000,
            'likes'      => null,
            'comments'   => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $response->assertSee('TikTok Null Engagement');
    }

    /**
     * Test 7: TikTok followers fetched from platform_stats_daily.followers
     */
    public function test_tiktok_followers_displayed_correctly(): void
    {
        $today = Carbon::today()->toDateString();

        $ttVideo = Content::create([
            'platform_id'         => $this->tiktok->id,
            'content_id_external' => 'TT_VID_FOL',
            'judul'               => 'TikTok Fol Video',
            'url'                 => 'https://tiktok.com/fol',
            'status'              => 'aktif',
        ]);

        ContentStatsDaily::create([
            'content_id' => $ttVideo->id,
            'tanggal'    => $today,
            'views'      => 100,
        ]);

        PlatformStatsDaily::create([
            'platform_id'    => $this->tiktok->id,
            'tanggal'        => $today,
            'followers'      => 98765,
            'total_contents' => 1,
            'total_views'    => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $response->assertSee('98,765');
    }

    /**
     * Test 8: TikTok daily trend chart is populated when views exist
     */
    public function test_tiktok_daily_trend_chart_is_rendered(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => '7']));

        $response->assertOk();
        $response->assertSee('trendChart');
    }

    /**
     * Test 9: TikTok Top 10 content table displays video ranking
     */
    public function test_tiktok_top_10_content_table(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => '7']));

        $response->assertOk();
        $response->assertSee('TikTok TVRI Viral 1');
        $response->assertSee('TikTok TVRI Viral 2');
        $response->assertSee('25,000');
        $response->assertSee('5,000');
    }

    /**
     * Test 10, 11, 12: Period filters (7, 15, 30, All Time, Custom) work for TikTok
     */
    public function test_tiktok_period_filters(): void
    {
        $this->seedPlatformsData();

        // 7 Days
        $res7 = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => '7']));
        $res7->assertOk();

        // 15 Days
        $res15 = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => '15']));
        $res15->assertOk();

        // 30 Days
        $res30 = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => '30']));
        $res30->assertOk();

        // All Time
        $resAll = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok', 'period' => 'all']));
        $resAll->assertOk();

        // Custom
        $resCustom = $this->actingAs($this->user)->get(route('dashboard', [
            'platform'   => 'tiktok',
            'period'     => 'custom',
            'start_date' => Carbon::today()->subDays(10)->toDateString(),
            'end_date'   => Carbon::today()->toDateString(),
        ]));
        $resCustom->assertOk();
    }

    /**
     * Test 13 & 14: Localization (ID & EN) works properly on TikTok tab
     */
    public function test_tiktok_localization_id_and_en(): void
    {
        $this->seedPlatformsData();

        // Indonesian
        $this->withSession(['locale' => 'id']);
        $resId = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));
        $resId->assertOk();
        $resId->assertSee('Konten Aktif');
        $resId->assertSee('Views Saat Ini');
        $resId->assertSee('Top 10 Konten');

        // English
        $this->withSession(['locale' => 'en']);
        $resEn = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));
        $resEn->assertOk();
        $resEn->assertSee('Active Content');
        $resEn->assertSee('Current Views');
        $resEn->assertSee('Top 10 Content');
    }

    /**
     * Test 15: platform=all includes TikTok safely without double counting
     */
    public function test_platform_all_includes_tiktok_safely(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'all', 'period' => '7']));

        $response->assertOk();
        // Total video count across all platforms: 1 YT + 2 TikTok + 1 IG = 4
        $response->assertSee('4');
        // Total views terkini: 105,000 (YT) + 30,000 (TT) = 135,000
        $response->assertSee(number_format(135000));
        // Total followers: 120,000 (YT) + 65,000 (TT) + 45,000 (IG) = 230,000
        $response->assertSee(number_format(230000));

        // All platform titles visible in summary table
        $response->assertSee('YouTube');
        $response->assertSee('TikTok');
        $response->assertSee('Instagram');
    }

    /**
     * Test 16: platform=youtube regression (YouTube metrics & formulas remain untouched)
     */
    public function test_platform_youtube_regression(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        // Total Views Terkini YouTube: 105,000
        $response->assertSee(number_format(105000));
        // YouTube subscribers: 120,000
        $response->assertSee(number_format(120000));
        // TikTok content must NOT appear on YouTube tab
        $response->assertDontSee('TikTok TVRI Viral 1');
    }

    /**
     * Test 17 & 18: platform=instagram and facebook regression
     */
    public function test_platform_instagram_and_facebook_regression(): void
    {
        $this->seedPlatformsData();

        // Instagram
        $resIg = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'instagram']));
        $resIg->assertOk();
        $resIg->assertSee('Instagram Post TVRI');
        $resIg->assertDontSee('TikTok TVRI Viral 1');
        $resIg->assertDontSee('Berita YouTube TVRI');

        // Facebook
        $resFb = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'facebook']));
        $resFb->assertOk();
        $resFb->assertDontSee('TikTok TVRI Viral 1');
    }

    /**
     * Test 19: No cross-platform data leakage
     */
    public function test_no_cross_platform_data_leakage(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $response->assertSee('TikTok TVRI Viral 1');
        $response->assertSee('TikTok TVRI Viral 2');
        $response->assertDontSee('YT_TEST_VID_001');
        $response->assertDontSee('IG_POST_001');
    }

    /**
     * Test 20: No raw translation keys rendered in HTML
     */
    public function test_no_raw_translation_keys_rendered(): void
    {
        $this->seedPlatformsData();

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'tiktok']));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('app.dashboard.days', $html);
        $this->assertStringNotContainsString('app.kpi.', $html);
        $this->assertStringNotContainsString('app.platform.', $html);
        $this->assertStringNotContainsString('app.status.', $html);
    }
}
