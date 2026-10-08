<?php

namespace Tests\Feature;

use App\Models\ChannelStatsDaily;
use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Services\YoutubeService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YoutubeSyncReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.youtube.channel_id', 'UC_TEST_CHANNEL_123');
        Config::set('services.youtube.key', 'TEST_API_KEY');

        Platform::create([
            'nama' => 'YouTube',
            'slug' => 'youtube',
            'icon' => 'youtube',
            'color' => '#FF0000',
            'is_active' => true,
        ]);
    }

    /**
     * Skenario A: HTTP Success Penuh.
     */
    public function test_youtube_sync_success(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*part=contentDetails*' => Http::response([
                'items' => [
                    ['contentDetails' => ['relatedPlaylists' => ['uploads' => 'UU_PLAYLIST_123']]]
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    ['contentDetails' => ['videoId' => 'VID_001']],
                    ['contentDetails' => ['videoId' => 'VID_002']],
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'VID_001',
                        'snippet' => ['title' => 'Video 1', 'thumbnails' => ['medium' => ['url' => 'http://thumb1.jpg']], 'publishedAt' => '2026-09-01T00:00:00Z'],
                        'statistics' => ['viewCount' => '100', 'likeCount' => '10', 'commentCount' => '2'],
                    ],
                    [
                        'id' => 'VID_002',
                        'snippet' => ['title' => 'Video 2', 'thumbnails' => ['medium' => ['url' => 'http://thumb2.jpg']], 'publishedAt' => '2026-09-02T00:00:00Z'],
                        'statistics' => ['viewCount' => '200', 'likeCount' => '20', 'commentCount' => '4'],
                    ],
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/channels*part=statistics*' => Http::response([
                'items' => [
                    ['statistics' => ['subscriberCount' => '28000', 'viewCount' => '9000000', 'videoCount' => '2']]
                ]
            ], 200),
        ]);

        $this->artisan('youtube:sync')
            ->expectsOutputToContain('[SYNC START]')
            ->expectsOutputToContain('[SYNC SUCCESS]')
            ->assertSuccessful();

        $this->assertDatabaseHas('contents', ['content_id_external' => 'VID_001', 'judul' => 'Video 1']);
        $this->assertDatabaseHas('contents', ['content_id_external' => 'VID_002', 'judul' => 'Video 2']);
        $this->assertDatabaseCount('content_stats_daily', 2);
        $this->assertDatabaseCount('channel_stats_daily', 1);
        $this->assertDatabaseHas('channel_stats_daily', ['subscriber_count' => 28000]);
    }

    /**
     * Skenario B: Timeout pertama -> Retry -> Success.
     */
    public function test_youtube_sync_retry_on_transient_timeout(): void
    {
        $videoAttempts = 0;

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*part=contentDetails*' => Http::response([
                'items' => [
                    ['contentDetails' => ['relatedPlaylists' => ['uploads' => 'UU_PLAYLIST_123']]]
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    ['contentDetails' => ['videoId' => 'VID_001']],
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/videos*' => function ($request) use (&$videoAttempts) {
                $videoAttempts++;
                if ($videoAttempts === 1) {
                    throw new ConnectException('cURL error 28: Connection timed out', new Request('GET', $request->url()));
                }
                return Http::response([
                    'items' => [
                        [
                            'id' => 'VID_001',
                            'snippet' => ['title' => 'Video 1', 'thumbnails' => ['medium' => ['url' => 'http://thumb1.jpg']], 'publishedAt' => '2026-09-01T00:00:00Z'],
                            'statistics' => ['viewCount' => '100', 'likeCount' => '10', 'commentCount' => '2'],
                        ]
                    ]
                ], 200);
            },

            'https://www.googleapis.com/youtube/v3/channels*part=statistics*' => Http::response([
                'items' => [
                    ['statistics' => ['subscriberCount' => '28000', 'viewCount' => '9000000', 'videoCount' => '1']]
                ]
            ], 200),
        ]);

        $this->artisan('youtube:sync')
            ->expectsOutputToContain('[SYNC SUCCESS]')
            ->assertSuccessful();

        $this->assertEquals(2, $videoAttempts, 'Request videos should have succeeded on attempt 2 via retry');
    }

    /**
     * Skenario C: Timeout seluruh retry -> FAILED (Exit code failure).
     */
    public function test_youtube_sync_fails_when_all_retries_exhausted(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*part=contentDetails*' => function ($request) {
                throw new ConnectException('cURL error 28: Connection timed out', new Request('GET', $request->url()));
            },
        ]);

        $this->artisan('youtube:sync')
            ->expectsOutputToContain('[SYNC FAILED]')
            ->assertFailed();
    }

    /**
     * Skenario D: Same-day snapshot sudah ada -> SKIP (Immutable snapshot).
     */
    public function test_same_day_snapshot_is_immutable(): void
    {
        $today = Carbon::today()->toDateString();
        $platform = Platform::where('slug', 'youtube')->first();

        $content = Content::create([
            'platform_id' => $platform->id,
            'content_id_external' => 'VID_001',
            'judul' => 'Video Original',
            'url' => 'https://www.youtube.com/watch?v=VID_001',
            'thumbnail_url' => 'http://orig.jpg',
            'tanggal_upload' => '2026-09-01',
            'status' => 'aktif',
        ]);

        // Existing historical snapshot for today
        ContentStatsDaily::create([
            'content_id' => $content->id,
            'tanggal' => $today,
            'views' => 1000,
            'likes' => 50,
            'comments' => 10,
        ]);

        ChannelStatsDaily::create([
            'tanggal' => $today,
            'subscriber_count' => 25000,
            'total_views' => 8000000,
            'video_count' => 1,
        ]);

        // Mock API returns updated higher stats
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*part=contentDetails*' => Http::response([
                'items' => [
                    ['contentDetails' => ['relatedPlaylists' => ['uploads' => 'UU_PLAYLIST_123']]]
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    ['contentDetails' => ['videoId' => 'VID_001']],
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'VID_001',
                        'snippet' => ['title' => 'Video Original Updated Title', 'thumbnails' => ['medium' => ['url' => 'http://orig2.jpg']], 'publishedAt' => '2026-09-01T00:00:00Z'],
                        'statistics' => ['viewCount' => '9999', 'likeCount' => '999', 'commentCount' => '99'],
                    ],
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/channels*part=statistics*' => Http::response([
                'items' => [
                    ['statistics' => ['subscriberCount' => '30000', 'viewCount' => '9500000', 'videoCount' => '1']]
                ]
            ], 200),
        ]);

        $this->artisan('youtube:sync')
            ->expectsOutputToContain('Snapshot immutable/skip: 1')
            ->assertSuccessful();

        // Metadata video ter-update
        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'judul' => 'Video Original Updated Title',
        ]);

        // Snapshot content hari ini TIDAK dioverwrite (tetap 1000 views)
        $this->assertDatabaseHas('content_stats_daily', [
            'content_id' => $content->id,
            'views' => 1000,
            'likes' => 50,
        ]);

        // Snapshot channel hari ini TIDAK dioverwrite (tetap 25000 subscribers)
        $this->assertDatabaseHas('channel_stats_daily', [
            'subscriber_count' => 25000,
            'total_views' => 8000000,
        ]);

    }

    /**
     * Skenario E: Channel snapshot TIDAK dibuat jika video chunk sync gagal.
     */
    public function test_channel_snapshot_not_created_if_video_sync_fails(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*part=contentDetails*' => Http::response([
                'items' => [
                    ['contentDetails' => ['relatedPlaylists' => ['uploads' => 'UU_PLAYLIST_123']]]
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    ['contentDetails' => ['videoId' => 'VID_001']],
                ]
            ], 200),

            'https://www.googleapis.com/youtube/v3/videos*' => function ($request) {
                // Semua retry gagal
                throw new ConnectException('cURL error 28: Operation timed out', new Request('GET', $request->url()));
            },

            'https://www.googleapis.com/youtube/v3/channels*part=statistics*' => Http::response([
                'items' => [
                    ['statistics' => ['subscriberCount' => '30000', 'viewCount' => '9500000', 'videoCount' => '1']]
                ]
            ], 200),
        ]);

        $this->artisan('youtube:sync')
            ->expectsOutputToContain('[SYNC FAILED]')
            ->assertFailed();

        // Pastikan TIDAK ADA snapshot channel yang tercipta
        $this->assertDatabaseCount('channel_stats_daily', 0);
    }

    /**
     * Skenario F: Scheduler Registration.
     * Memastikan youtube:sync terdaftar di scheduler dengan spesifikasi:
     * - expression: 0 1 * * * (dailyAt 01:00)
     * - timezone: Asia/Jakarta
     * - withoutOverlapping: true
     * - lock expiration: 60 menit
     */
    public function test_youtube_sync_scheduler_registration(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $youtubeEvent = $events->first(function ($event) {
            return str_contains($event->command, 'youtube:sync');
        });

        $this->assertNotNull($youtubeEvent, 'Command youtube:sync harus terdaftar di scheduler');
        $this->assertEquals('0 1 * * *', $youtubeEvent->expression, 'Jadwal harus 01:00 (0 1 * * *)');

        $tz = $youtubeEvent->timezone instanceof \DateTimeZone
            ? $youtubeEvent->timezone->getName()
            : (string) $youtubeEvent->timezone;
        $this->assertEquals('Asia/Jakarta', $tz, 'Timezone scheduler harus Asia/Jakarta');

        $this->assertTrue($youtubeEvent->withoutOverlapping, 'withoutOverlapping harus aktif');
        $this->assertEquals(60, $youtubeEvent->expiresAt, 'Lock expiration harus 60 menit');
    }
}

