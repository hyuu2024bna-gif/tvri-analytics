<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Test suite: Konten Aktif — pertambahan konten dalam periode (newContentInPeriod).
 *
 * Requirements yang diuji:
 *  1. Jumlah konten baru dalam 7 hari benar.
 *  2. Jumlah konten baru dalam 15 hari dan 30 hari benar.
 *  3. Custom range memakai tanggal awal dan akhir yang benar.
 *  4. Filter platform bekerja dengan benar.
 *  5. Konten di luar rentang tidak ikut dihitung.
 *  6. tanggal_upload NULL tidak ikut dihitung.
 *  7. Nilai utama Konten Aktif ($totalVideo) tidak berubah.
 *  8. Fitur Likes, Komentar, Views, Followers, dan Subscribers tidak rusak.
 */
class DashboardNewContentGrowthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Platform $youtube;
    protected Platform $tiktok;
    protected Platform $instagram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user      = User::factory()->create(['email' => 'growth_test@tvri.co.id']);
        $this->youtube   = Platform::firstOrCreate(['slug' => 'youtube'],   ['nama' => 'YouTube']);
        $this->tiktok    = Platform::firstOrCreate(['slug' => 'tiktok'],    ['nama' => 'TikTok']);
        $this->instagram = Platform::firstOrCreate(['slug' => 'instagram'], ['nama' => 'Instagram']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    private function makeContent(Platform $platform, ?string $tanggalUpload, string $externalId): Content
    {
        return Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => $externalId,
            'judul'               => "Content {$externalId}",
            'url'                 => "https://example.com/{$externalId}",
            'thumbnail_url'       => null,
            'status'              => 'aktif',
            'tanggal_upload'      => $tanggalUpload,
        ]);
    }

    private function makeSnapshot(Content $content, string $tanggal): void
    {
        ContentStatsDaily::create([
            'content_id' => $content->id,
            'tanggal'    => $tanggal,
            'views'      => 100,
            'likes'      => 10,
            'comments'   => 1,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 1: 7-day window
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_content_count_is_correct_for_7_day_period(): void
    {
        $today        = Carbon::today()->toDateString();
        $threeDaysAgo = Carbon::today()->subDays(3)->toDateString();
        $tenDaysAgo   = Carbon::today()->subDays(10)->toDateString();

        $inside  = $this->makeContent($this->youtube, $threeDaysAgo, 'YT_7D_INSIDE');
        $outside = $this->makeContent($this->youtube, $tenDaysAgo,   'YT_7D_OUTSIDE');
        $this->makeSnapshot($inside,  $today);
        $this->makeSnapshot($outside, $today);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 2a: 15-day window
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_content_count_is_correct_for_15_day_period(): void
    {
        $today         = Carbon::today()->toDateString();
        $tenDaysAgo    = Carbon::today()->subDays(10)->toDateString();
        $twentyDaysAgo = Carbon::today()->subDays(20)->toDateString();

        $inside  = $this->makeContent($this->youtube, $tenDaysAgo,    'YT_15D_IN');
        $outside = $this->makeContent($this->youtube, $twentyDaysAgo, 'YT_15D_OUT');
        $this->makeSnapshot($inside,  $today);
        $this->makeSnapshot($outside, $today);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '15']));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 2b: 30-day window
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_content_count_is_correct_for_30_day_period(): void
    {
        $today         = Carbon::today()->toDateString();
        $twentyDaysAgo = Carbon::today()->subDays(20)->toDateString();
        $fortyDaysAgo  = Carbon::today()->subDays(40)->toDateString();

        $inside  = $this->makeContent($this->youtube, $twentyDaysAgo, 'YT_30D_IN');
        $outside = $this->makeContent($this->youtube, $fortyDaysAgo,  'YT_30D_OUT');
        $this->makeSnapshot($inside,  $today);
        $this->makeSnapshot($outside, $today);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '30']));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 3: Custom range
    // ─────────────────────────────────────────────────────────────────────────

    public function test_custom_range_uses_correct_start_and_end_dates(): void
    {
        $today     = Carbon::today()->toDateString();
        $startDate = Carbon::today()->subDays(10)->toDateString();
        $endDate   = Carbon::today()->subDays(3)->toDateString();

        $onStart = $this->makeContent($this->youtube, $startDate, 'YT_CUST_START');
        $onEnd   = $this->makeContent($this->youtube, $endDate,   'YT_CUST_END');
        $before  = $this->makeContent($this->youtube, Carbon::today()->subDays(11)->toDateString(), 'YT_CUST_BEFORE');
        $after   = $this->makeContent($this->youtube, Carbon::today()->subDays(2)->toDateString(),  'YT_CUST_AFTER');

        foreach ([$onStart, $onEnd, $before, $after] as $c) {
            $this->makeSnapshot($c, $today);
        }

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', [
                'platform'   => 'youtube',
                'period'     => 'custom',
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ]));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 4: Platform filter
    // ─────────────────────────────────────────────────────────────────────────

    public function test_platform_filter_isolates_content_correctly(): void
    {
        $today = Carbon::today()->toDateString();

        $ytContent = $this->makeContent($this->youtube, $today, 'YT_PLAT_FILTER');
        $ttContent = $this->makeContent($this->tiktok,  $today, 'TT_PLAT_FILTER');
        $this->makeSnapshot($ytContent, $today);
        $this->makeSnapshot($ttContent, $today);

        $ytResponse = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));
        $ytResponse->assertOk();
        $ytResponse->assertViewHas('newContentInPeriod', 1);

        $ttResponse = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'tiktok', 'period' => '7']));
        $ttResponse->assertOk();
        $ttResponse->assertViewHas('newContentInPeriod', 1);

        $allResponse = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'all', 'period' => '7']));
        $allResponse->assertOk();
        $allResponse->assertViewHas('newContentInPeriod', 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 5: Content outside range excluded
    // ─────────────────────────────────────────────────────────────────────────

    public function test_content_outside_period_range_is_excluded(): void
    {
        $today   = Carbon::today()->toDateString();
        $oldDate = Carbon::today()->subDays(60)->toDateString();

        $old = $this->makeContent($this->youtube, $oldDate, 'YT_OLD_CONTENT');
        $this->makeSnapshot($old, $today);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 0);
        // Zero-state label must still be rendered (not the old static platform text)
        $response->assertSee('0 konten baru');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 6: NULL tanggal_upload excluded
    // ─────────────────────────────────────────────────────────────────────────

    public function test_null_tanggal_upload_is_not_counted(): void
    {
        $today  = Carbon::today()->toDateString();
        $noDate = $this->makeContent($this->youtube, null, 'YT_NULL_DATE');
        $this->makeSnapshot($noDate, $today);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 0);
        $response->assertViewHas('totalVideo', 1); // still counted in main total
        $response->assertSee('0 konten baru');     // zero-state label rendered
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 9: Zero-state shows neutral label with correct period label
    // ─────────────────────────────────────────────────────────────────────────

    public function test_zero_new_content_shows_neutral_label_with_period(): void
    {
        // No content at all → newContentInPeriod = 0
        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        $response->assertViewHas('newContentInPeriod', 0);

        // Must show the neutral zero-state text
        $response->assertSee('0 konten baru');

        // Must NOT show the emerald growth badge marker
        $response->assertDontSee('▲ +');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 7: totalVideo (main value) is period-agnostic
    // ─────────────────────────────────────────────────────────────────────────

    public function test_total_video_main_value_is_not_affected_by_period(): void
    {
        $today   = Carbon::today()->toDateString();
        $oldDate = Carbon::today()->subDays(90)->toDateString();

        $old1 = $this->makeContent($this->youtube, $oldDate, 'YT_OLD_1');
        $old2 = $this->makeContent($this->youtube, $oldDate, 'YT_OLD_2');
        $new1 = $this->makeContent($this->youtube, $today,   'YT_NEW_1');
        foreach ([$old1, $old2, $new1] as $c) {
            $this->makeSnapshot($c, $today);
        }

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        $response->assertViewHas('totalVideo', 3);        // all 3 active
        $response->assertViewHas('newContentInPeriod', 1); // only today
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 8: Existing KPI metrics unbroken
    // ─────────────────────────────────────────────────────────────────────────

    public function test_existing_kpi_metrics_are_not_broken(): void
    {
        $today        = Carbon::today()->toDateString();
        $sevenDaysAgo = Carbon::today()->subDays(6)->toDateString();

        $content = $this->makeContent($this->youtube, $sevenDaysAgo, 'YT_KPI_CHECK');

        ContentStatsDaily::create([
            'content_id' => $content->id,
            'tanggal'    => $sevenDaysAgo,
            'views'      => 1000,
            'likes'      => 50,
            'comments'   => 5,
        ]);

        ContentStatsDaily::create([
            'content_id' => $content->id,
            'tanggal'    => $today,
            'views'      => 1500,
            'likes'      => 70,
            'comments'   => 8,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        $response->assertViewHas('totalViewsTerkini',   1500);
        $response->assertViewHas('totalViewsBertambah', 500);
        $response->assertViewHas('totalLikes',          70);
        $response->assertViewHas('totalLikesGrowth',    20);
        $response->assertViewHas('totalComments',       8);
        $response->assertViewHas('totalCommentsGrowth', 3);
        $response->assertViewHas('totalVideo',          1);
        $response->assertViewHas('newContentInPeriod',  1);
    }
}
