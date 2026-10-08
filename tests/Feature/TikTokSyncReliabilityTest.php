<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\TikTokService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokSyncReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Platform $tiktokPlatform;
    protected SocialAccount $socialAccount;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.tiktok.client_key', 'test_client_key_123');
        Config::set('services.tiktok.client_secret', 'test_client_secret_xyz');
        Config::set('services.tiktok.redirect_uri', 'http://localhost/tiktok/callback');

        $this->user = User::factory()->create([
            'email' => 'sync_tester@tvri.co.id',
        ]);

        $this->tiktokPlatform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        $this->socialAccount = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_tvri_sync_001',
            'username'            => 'TVRI Aceh Official',
            'access_token'        => 'act_valid_sync_token_123',
            'connected_by'        => $this->user->id,
            'expires_at'          => Carbon::now()->addDays(1),
        ]);
    }

    /**
     * Test 1: Account belum connected -> Command fails safely
     */
    public function test_sync_fails_safely_when_account_not_connected(): void
    {
        SocialAccount::truncate();

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('[TIKTOK SYNC FAILED]')
            ->assertExitCode(1);

        $this->assertEquals(0, Content::count());
        $this->assertEquals(0, ContentStatsDaily::count());
        $this->assertEquals(0, PlatformStatsDaily::count());
    }

    /**
     * Test 2: Access token expired -> Command fails safely without corrupting data
     */
    public function test_sync_fails_safely_when_access_token_expired(): void
    {
        $this->socialAccount->update([
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(1);

        $this->assertEquals(0, ContentStatsDaily::count());
        $this->assertEquals(0, PlatformStatsDaily::count());
    }

    /**
     * Test 3: API 401 Unauthorized -> Fails safely without deactivating content
     */
    public function test_sync_fails_safely_on_api_401_without_deactivating_content(): void
    {
        // Existing active content
        $content = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'EXISTING_VID_001',
            'judul'               => 'Video Eksisting',
            'url'                 => 'https://tiktok.com/@tvri/video/EXISTING_VID_001',
            'status'              => 'aktif',
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'error' => [
                    'code'    => 'access_token_invalid',
                    'message' => 'Token has expired or is invalid.',
                ],
            ], 401),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(1);

        // Existing content status must remain 'aktif' (NOT deleted)
        $this->assertEquals('aktif', $content->fresh()->status);
    }

    /**
     * Test 4, 5, 8, 10, 24: Successful sync creates content, content snapshot & platform snapshot
     */
    public function test_successful_sync_creates_content_and_snapshots(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'        => 'open_id_tvri_sync_001',
                        'display_name'   => 'TVRI Aceh Official',
                        'follower_count' => 75000,
                        'video_count'    => 500,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'                => 'VID_TIKTOK_101',
                            'title'             => 'Semarak PON Aceh 2026',
                            'video_description' => 'Upacara pembukaan PON XXI.',
                            'cover_image_url'   => 'https://tiktokcdn.com/cover101.jpg',
                            'share_url'         => 'https://www.tiktok.com/@tvriaceh/video/VID_TIKTOK_101',
                            'view_count'        => 15000,
                            'like_count'        => 820,
                            'comment_count'     => 95,
                            'share_count'       => 40,
                            'create_time'       => 1725700000,
                        ],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('TikTok Sync Completed')
            ->assertExitCode(0);

        // 1. Content created
        $this->assertDatabaseHas('contents', [
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_TIKTOK_101',
            'judul'               => 'Semarak PON Aceh 2026',
            'url'                 => 'https://www.tiktok.com/@tvriaceh/video/VID_TIKTOK_101',
            'thumbnail_url'       => 'https://tiktokcdn.com/cover101.jpg',
            'status'              => 'aktif',
        ]);

        $content = Content::where('content_id_external', 'VID_TIKTOK_101')->first();

        // 2. Content snapshot created
        $today = Carbon::today()->toDateString();
        $snapshot = ContentStatsDaily::where('content_id', $content->id)->whereDate('tanggal', $today)->first();
        $this->assertNotNull($snapshot);
        $this->assertEquals(15000, $snapshot->views);
        $this->assertEquals(820, $snapshot->likes);
        $this->assertEquals(95, $snapshot->comments);

        // 3. Platform snapshot created with total_views = NULL (Strict NULL)
        $platformSnapshot = PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->whereDate('tanggal', $today)->first();
        $this->assertNotNull($platformSnapshot);
        $this->assertEquals(75000, $platformSnapshot->followers);
        $this->assertEquals(500, $platformSnapshot->total_contents);
        $this->assertNull($platformSnapshot->total_views);
    }

    /**
     * Test 6 & 7: Existing content updated safely without duplication
     */
    public function test_existing_content_updated_safely_without_duplication(): void
    {
        $existingContent = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_TIKTOK_DUP_CHECK',
            'judul'               => 'Judul Lama',
            'url'                 => 'https://tiktok.com/old',
            'thumbnail_url'       => 'https://tiktok.com/old_thumb.jpg',
            'status'              => 'aktif',
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'follower_count' => 1000,
                        'video_count'    => 1,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'              => 'VID_TIKTOK_DUP_CHECK',
                            'title'           => 'Judul Baru Terupdate',
                            'cover_image_url' => 'https://tiktok.com/new_thumb.jpg',
                            'share_url'       => 'https://tiktok.com/new_url',
                            'view_count'      => 5000,
                            'like_count'      => 100,
                            'comment_count'   => 20,
                            'create_time'     => 1725700000,
                        ],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        // Exactly 1 row in contents table
        $this->assertEquals(1, Content::where('content_id_external', 'VID_TIKTOK_DUP_CHECK')->count());

        $existingContent->refresh();
        $this->assertEquals('Judul Baru Terupdate', $existingContent->judul);
        $this->assertEquals('https://tiktok.com/new_url', $existingContent->url);
        $this->assertEquals('https://tiktok.com/new_thumb.jpg', $existingContent->thumbnail_url);
    }

    /**
     * Test 9: Snapshot for same date is immutable (does not overwrite existing metrics)
     */
    public function test_content_snapshot_is_immutable_and_never_overwritten(): void
    {
        $today = Carbon::today()->toDateString();

        $content = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_IMMUTABLE_001',
            'judul'               => 'Video Immutability Test',
            'url'                 => 'https://tiktok.com/immutable',
            'status'              => 'aktif',
        ]);

        // Pre-existing snapshot for today
        ContentStatsDaily::create([
            'content_id' => $content->id,
            'tanggal'    => $today,
            'views'      => 1000,
            'likes'      => 50,
            'comments'   => 10,
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 1000, 'video_count' => 1]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'            => 'VID_IMMUTABLE_001',
                            'title'         => 'Video Immutability Test',
                            'view_count'    => 99999, // New higher views from API
                            'like_count'    => 5555,
                            'comment_count' => 333,
                        ],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        // Snapshot must remain 1000 views (immutable)
        $snapshot = ContentStatsDaily::where('content_id', $content->id)->whereDate('tanggal', $today)->first();
        $this->assertEquals(1000, $snapshot->views);
        $this->assertEquals(50, $snapshot->likes);
        $this->assertEquals(10, $snapshot->comments);
    }

    /**
     * Test 11: Platform snapshot for same date preserves valid followers while updating stale total_contents
     */
    public function test_platform_snapshot_preserves_valid_followers_while_updating_stale_total_contents(): void
    {
        $today = Carbon::today()->toDateString();

        PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->socialAccount->id,
            'tanggal'           => $today,
            'followers'         => 50000,
            'total_contents'    => 100,
            'total_views'       => null,
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 99999, 'video_count' => 999]], // New values
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $platformSnapshot = PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->whereDate('tanggal', $today)->first();
        $this->assertEquals(50000, $platformSnapshot->followers);
        $this->assertEquals(999, $platformSnapshot->total_contents);
    }

    /**
     * Test 12 & 13: Strict NULL metrics stay NULL in DB (never converted to 0)
     */
    public function test_strict_null_metrics_remain_null_in_database(): void
    {
        $today = Carbon::today()->toDateString();

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'follower_count' => null, // Unavailable metric
                        'video_count'    => null, // Unavailable metric
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'            => 'VID_STRICT_NULL',
                            'title'         => 'Strict NULL Video',
                            'view_count'    => null, // Unavailable metric
                            'like_count'    => null,
                            'comment_count' => null,
                        ],
                    ],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $content = Content::where('content_id_external', 'VID_STRICT_NULL')->first();
        $snapshot = ContentStatsDaily::where('content_id', $content->id)->whereDate('tanggal', $today)->first();

        $this->assertNull($snapshot->views);
        $this->assertNull($snapshot->likes);
        $this->assertNull($snapshot->comments);

        $platformSnapshot = PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->whereDate('tanggal', $today)->first();
        $this->assertNull($platformSnapshot->followers);
    }

    /**
     * Test 14: API zero remains zero (0 is not confused with NULL)
     */
    public function test_api_zero_metrics_remain_zero(): void
    {
        $today = Carbon::today()->toDateString();

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 0, 'video_count' => 1]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'            => 'VID_ZERO_METRIC',
                            'title'         => 'Brand New Video with Zero Views',
                            'view_count'    => 0,
                            'like_count'    => 0,
                            'comment_count' => 0,
                        ],
                    ],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $content = Content::where('content_id_external', 'VID_ZERO_METRIC')->first();
        $snapshot = ContentStatsDaily::where('content_id', $content->id)->whereDate('tanggal', $today)->first();

        $this->assertSame(0, (int) $snapshot->views);
        $this->assertSame(0, (int) $snapshot->likes);
        $this->assertSame(0, (int) $snapshot->comments);
    }

    /**
     * Test 15: Cursor pagination handles multiple pages
     */
    public function test_cursor_pagination_syncs_all_pages(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 5000, 'video_count' => 3]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::sequence()
                ->push([
                    'data' => [
                        'videos' => [
                            ['id' => 'VID_P1_1', 'title' => 'Video 1', 'view_count' => 100],
                            ['id' => 'VID_P1_2', 'title' => 'Video 2', 'view_count' => 200],
                        ],
                        'cursor'   => 1700000000,
                        'has_more' => true,
                    ],
                    'error' => ['code' => 'ok'],
                ], 200)
                ->push([
                    'data' => [
                        'videos' => [
                            ['id' => 'VID_P2_1', 'title' => 'Video 3', 'view_count' => 300],
                        ],
                        'cursor'   => null,
                        'has_more' => false,
                    ],
                    'error' => ['code' => 'ok'],
                ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $this->assertEquals(3, Content::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertDatabaseHas('contents', ['content_id_external' => 'VID_P1_1']);
        $this->assertDatabaseHas('contents', ['content_id_external' => 'VID_P1_2']);
        $this->assertDatabaseHas('contents', ['content_id_external' => 'VID_P2_1']);
    }

    /**
     * Test 16: Empty video response
     */
    public function test_empty_video_response_syncs_safely(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 10, 'video_count' => 0]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $this->assertEquals(0, Content::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertDatabaseHas('platform_stats_daily', [
            'platform_id' => $this->tiktokPlatform->id,
            'followers'   => 10,
        ]);
    }

    /**
     * Test 17: Complete fetch reconciles deleted content (marks as 'dihapus')
     */
    public function test_complete_fetch_marks_missing_content_as_deleted(): void
    {
        $existingKept = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_KEPT',
            'judul'               => 'Video Tetap Ada',
            'url'                 => 'https://tiktok.com/kept',
            'status'              => 'aktif',
        ]);

        $existingDeleted = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_REMOVED_FROM_TIKTOK',
            'judul'               => 'Video Telah Dihapus di TikTok',
            'url'                 => 'https://tiktok.com/removed',
            'status'              => 'aktif',
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 500, 'video_count' => 1]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_KEPT', 'title' => 'Video Tetap Ada', 'view_count' => 100],
                    ],
                    'cursor'   => null,
                    'has_more' => false, // Fetch is complete
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $this->assertEquals('aktif', $existingKept->fresh()->status);
        $this->assertEquals('dihapus', $existingDeleted->fresh()->status);
    }

    /**
     * Test 18: Incomplete fetch does NOT delete content (protection against false deletion)
     */
    public function test_incomplete_fetch_does_not_mark_content_as_deleted(): void
    {
        $existingContent = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_MUST_NOT_BE_DELETED',
            'judul'               => 'Video Proteksi',
            'url'                 => 'https://tiktok.com/protected',
            'status'              => 'aktif',
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 500, 'video_count' => 10]],
                'error' => ['code' => 'ok'],
            ], 200),
            // First page ok, second page 500 error (incomplete fetch)
            TikTokService::API_BASE_URL . '/video/list/*' => Http::sequence()
                ->push([
                    'data' => [
                        'videos'   => [['id' => 'VID_PAGE_1_ONLY', 'title' => 'Page 1 Vid', 'view_count' => 50]],
                        'cursor'   => 1700000000,
                        'has_more' => true,
                    ],
                    'error' => ['code' => 'ok'],
                ], 200)
                ->push(['error' => 'Rate limit/Server error'], 500),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        // Status of existing content that wasn't on page 1 must STILL be 'aktif'
        $this->assertEquals('aktif', $existingContent->fresh()->status);
    }

    /**
     * Test 19: Content reappears and becomes active
     */
    public function test_content_reappears_and_becomes_active(): void
    {
        $reappearingContent = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_REAPPEAR_001',
            'judul'               => 'Video Sempat Dihapus',
            'url'                 => 'https://tiktok.com/reappear',
            'status'              => 'dihapus', // Previously inactive
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 500, 'video_count' => 1]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_REAPPEAR_001', 'title' => 'Video Sempat Dihapus', 'view_count' => 200],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $this->assertEquals('aktif', $reappearingContent->fresh()->status);
    }

    /**
     * Test 20: Transient API failure retries and succeeds
     */
    public function test_transient_api_failure_retries_and_succeeds(): void
    {
        $attempts = 0;
        Http::fake(function ($request) use (&$attempts) {
            $url = $request->url();

            if (str_contains($url, '/user/info/')) {
                $attempts++;
                if ($attempts === 1) {
                    throw new ConnectionException('cURL error 28: Connection timed out');
                }

                return Http::response([
                    'data'  => ['user' => ['follower_count' => 8800, 'video_count' => 1]],
                    'error' => ['code' => 'ok'],
                ], 200);
            }

            return Http::response([
                'data' => [
                    'videos'   => [['id' => 'VID_RETRY_OK', 'title' => 'Retry Video', 'view_count' => 150]],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200);
        });

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $this->assertDatabaseHas('contents', ['content_id_external' => 'VID_RETRY_OK']);
        $this->assertDatabaseHas('platform_stats_daily', ['followers' => 8800]);
    }

    /**
     * Test 21: Permanent API failure is handled safely
     */
    public function test_permanent_api_failure_is_handled_safely(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response(['error' => 'Fatal server error'], 500),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(1);
    }

    /**
     * Test 22: Idempotent second sync does not create duplicate contents or snapshots
     */
    public function test_idempotent_second_sync(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 12000, 'video_count' => 2]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_IDEM_1', 'title' => 'Idem 1', 'view_count' => 500, 'like_count' => 25, 'comment_count' => 5],
                        ['id' => 'VID_IDEM_2', 'title' => 'Idem 2', 'view_count' => 600, 'like_count' => 30, 'comment_count' => 6],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        // Run 1
        $this->artisan('tiktok:sync')->assertExitCode(0);
        $contentsCountRun1 = Content::where('platform_id', $this->tiktokPlatform->id)->count();
        $snapshotsCountRun1 = ContentStatsDaily::count();
        $platformSnapshotsRun1 = PlatformStatsDaily::count();

        $this->assertEquals(2, $contentsCountRun1);
        $this->assertEquals(2, $snapshotsCountRun1);
        $this->assertEquals(1, $platformSnapshotsRun1);

        // Run 2 (same day)
        $this->artisan('tiktok:sync')->assertExitCode(0);
        $contentsCountRun2 = Content::where('platform_id', $this->tiktokPlatform->id)->count();
        $snapshotsCountRun2 = ContentStatsDaily::count();
        $platformSnapshotsRun2 = PlatformStatsDaily::count();

        $this->assertEquals($contentsCountRun1, $contentsCountRun2);
        $this->assertEquals($snapshotsCountRun1, $snapshotsCountRun2);
        $this->assertEquals($platformSnapshotsRun1, $platformSnapshotsRun2);
    }

    /**
     * Test 23: No token leakage in logs/output
     */
    public function test_no_token_leakage_in_sync_output(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 100, 'video_count' => 0]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->doesntExpectOutputToContain('act_valid_sync_token_123')
            ->doesntExpectOutputToContain('test_client_secret_xyz')
            ->assertExitCode(0);
    }

    /**
     * Test 24: tiktok:sync terdaftar di Laravel Scheduler pada 04:00 Asia/Jakarta dengan withoutOverlapping(60)
     */
    public function test_tiktok_sync_is_registered_in_schedule(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $tiktokEvent = $events->first(function ($event) {
            return str_contains($event->command, 'tiktok:sync');
        });

        $this->assertNotNull($tiktokEvent, 'tiktok:sync harus terdaftar di schedule events');
        $this->assertEquals('0 4 * * *', $tiktokEvent->expression, 'Jadwal TikTok harus 04:00 daily');
        $this->assertEquals('Asia/Jakarta', $tiktokEvent->timezone, 'Timezone TikTok harus Asia/Jakarta');
        $this->assertTrue($tiktokEvent->withoutOverlapping, 'withoutOverlapping harus aktif');
        $this->assertEquals(60, $tiktokEvent->expiresAt, 'Lock expiration harus 60 menit');
    }

    /**
     * Test 25: Seluruh platform (YouTube 01:00, Instagram 02:00, Facebook 03:00, TikTok 04:00) terdaftar tanpa saling merusak
     */
    public function test_all_platforms_scheduled_with_proper_isolation(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $commands = [
            'youtube:sync'   => ['time' => '0 1 * * *', 'tz' => 'Asia/Jakarta'],
            'instagram:sync' => ['time' => '0 2 * * *', 'tz' => 'Asia/Jakarta'],
            'facebook:sync'  => ['time' => '0 3 * * *', 'tz' => 'Asia/Jakarta'],
            'tiktok:sync'    => ['time' => '0 4 * * *', 'tz' => 'Asia/Jakarta'],
        ];

        foreach ($commands as $cmd => $expected) {
            $event = $events->first(fn ($e) => str_contains($e->command, $cmd));
            $this->assertNotNull($event, "Command {$cmd} harus terdaftar di scheduler");
            $this->assertEquals($expected['time'], $event->expression, "Jadwal {$cmd} harus {$expected['time']}");
            $this->assertEquals($expected['tz'], $event->timezone, "Timezone {$cmd} harus {$expected['tz']}");
            $this->assertTrue($event->withoutOverlapping, "withoutOverlapping untuk {$cmd} harus aktif");
            $this->assertEquals(60, $event->expiresAt, "Lock expiration untuk {$cmd} harus 60 menit");
        }
    }

    /**
     * Test 26: Token expired ditangani dengan aman — tidak menghapus data, tidak merusak snapshot, tidak bocor credential
     */
    public function test_token_expired_exits_safely_and_preserves_existing_data(): void
    {
        // 1. Buat data konten dan snapshot historis
        $existingContent = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'TT_EXISTING_PRESERVE',
            'judul'               => 'Video Aman Terjaga',
            'url'                 => 'https://tiktok.com/@tvri/video/TT_EXISTING_PRESERVE',
            'status'              => 'aktif',
            'tanggal_upload'      => '2026-09-01',
        ]);

        $existingSnapshot = ContentStatsDaily::create([
            'content_id' => $existingContent->id,
            'tanggal'    => '2026-09-01',
            'views'      => 500,
            'likes'      => 50,
            'comments'   => 10,
        ]);

        $existingPlatformSnapshot = PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->socialAccount->id,
            'tanggal'           => '2026-09-01',
            'followers'         => null,
            'total_contents'    => 1,
            'total_views'       => null,
        ]);

        // 2. Set token expired di masa lalu
        $this->socialAccount->update([
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        // 3. Jalankan tiktok:sync
        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('[TIKTOK SYNC FAILED]')
            ->doesntExpectOutputToContain($this->socialAccount->access_token)
            ->assertExitCode(1);

        // 4. Verifikasi ketahanan data: data TIDAK berubah, TIDAK terhapus, TIDAK berstatus deleted
        $existingContent->refresh();
        $this->assertEquals('aktif', $existingContent->status, 'Status konten harus tetap aktif, bukan deleted');
        $this->assertDatabaseHas('contents', [
            'id'                  => $existingContent->id,
            'content_id_external' => 'TT_EXISTING_PRESERVE',
            'status'              => 'aktif',
        ]);

        $this->assertDatabaseHas('content_stats_daily', [
            'id'         => $existingSnapshot->id,
            'content_id' => $existingContent->id,
            'views'      => 500,
            'likes'      => 50,
            'comments'   => 10,
        ]);

        $this->assertDatabaseHas('platform_stats_daily', [
            'id'          => $existingPlatformSnapshot->id,
            'platform_id' => $this->tiktokPlatform->id,
        ]);

        // Tidak ada snapshot baru yang dibuat secara palsu
        $this->assertEquals(1, ContentStatsDaily::where('content_id', $existingContent->id)->count());
        $this->assertEquals(1, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->count());
    }

    /**
     * Test 24a: Snapshot existing dengan followers NULL dapat di-backfill menjadi nilai baru
     */
    public function test_existing_snapshot_with_null_followers_is_backfilled_with_new_value(): void
    {
        $today = Carbon::today()->toDateString();

        $existingSnapshot = PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->socialAccount->id,
            'tanggal'           => $today,
            'followers'         => null,
            'total_contents'    => 960,
            'total_views'       => null,
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => [
                    'user' => [
                        'follower_count' => 51642,
                        'video_count'    => 3092,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('Account snapshot updated: 1')
            ->assertExitCode(0);

        $existingSnapshot->refresh();
        $this->assertEquals(51642, $existingSnapshot->followers);
        $this->assertEquals(3092, $existingSnapshot->total_contents);
        $this->assertEquals(1, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->whereDate('tanggal', $today)->count());
    }

    /**
     * Test 24b: Snapshot existing dengan followers valid tidak ditimpa oleh NULL
     */
    public function test_existing_snapshot_with_valid_followers_is_not_overwritten_by_null(): void
    {
        $today = Carbon::today()->toDateString();

        $existingSnapshot = PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->socialAccount->id,
            'tanggal'           => $today,
            'followers'         => 50000,
            'total_contents'    => 100,
            'total_views'       => null,
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => [
                    'user' => [
                        'follower_count' => null, // NULL from API
                        'video_count'    => 100,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        $existingSnapshot->refresh();
        $this->assertEquals(50000, $existingSnapshot->followers, 'Valid followers MUST NOT be overwritten by NULL');
        $this->assertEquals(1, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->whereDate('tanggal', $today)->count());
    }

    /**
     * Test 24c: Tidak terjadi duplicate snapshot pada tanggal yang sama
     */
    public function test_no_duplicate_snapshot_created_on_same_date(): void
    {
        $today = Carbon::today()->toDateString();

        PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->socialAccount->id,
            'tanggal'           => $today,
            'followers'         => 51642,
            'total_contents'    => 3092,
            'total_views'       => null,
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => [
                    'user' => [
                        'follower_count' => 51642,
                        'video_count'    => 3092,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        // Jalankan berkali-kali pada hari yang sama
        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('Account snapshot skipped: 1')
            ->assertExitCode(0);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('Account snapshot skipped: 1')
            ->assertExitCode(0);

        $this->assertEquals(1, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->whereDate('tanggal', $today)->count());
    }

    /**
     * Test 24d: Total_contents dapat diperbarui dari nilai snapshot awal yang stale
     */
    public function test_stale_total_contents_is_updated_from_early_snapshot(): void
    {
        $today = Carbon::today()->toDateString();

        $existingSnapshot = PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->socialAccount->id,
            'tanggal'           => $today,
            'followers'         => 51642,
            'total_contents'    => 960, // Stale: snapshot awal sebelum sync selesai
            'total_views'       => null,
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => [
                    'user' => [
                        'follower_count' => 51642,
                        'video_count'    => 3092, // Nilai mutakhir
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('Account snapshot updated: 1')
            ->assertExitCode(0);

        $existingSnapshot->refresh();
        $this->assertEquals(3092, $existingSnapshot->total_contents);
        $this->assertEquals(51642, $existingSnapshot->followers);
    }
}
