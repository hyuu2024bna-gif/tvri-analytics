<?php

namespace Tests\Feature;

use App\Services\TikTokService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TikTokServiceTest extends TestCase
{
    protected TikTokService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.tiktok.client_key', 'test_client_key_123');
        Config::set('services.tiktok.client_secret', 'test_client_secret_xyz');
        Config::set('services.tiktok.redirect_uri', 'http://localhost/tiktok/callback');

        $this->service = new TikTokService();
    }

    /**
     * Test 1: Authorization URL generation
     */
    public function test_authorization_url_generation(): void
    {
        $url = $this->service->getAuthorizationUrl('csrf_token_abc123');

        $this->assertStringStartsWith(TikTokService::AUTH_BASE_URL, $url);
        $this->assertStringContainsString('client_key=test_client_key_123', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=csrf_token_abc123', $url);
        $this->assertStringContainsString(urlencode('http://localhost/tiktok/callback'), $url);
        // Scope yang disetujui di TikTok Developer Portal
        $this->assertStringContainsString('user.info.basic', $url);
        $this->assertStringContainsString('video.list', $url);
        // user.info.stats sudah dikonfigurasi di TikTok Developer Portal — HARUS ada di URL
        $this->assertStringContainsString('user.info.stats', $url);
    }

    /**
     * Test 2: Token exchange success
     */
    public function test_token_exchange_success(): void
    {
        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_test_access_token_123',
                'expires_in'         => 86400,
                'open_id'            => '_000_test_openid_abc',
                'refresh_token'      => 'rft_test_refresh_token_456',
                'refresh_expires_in' => 31536000,
                'scope'              => 'user.info.basic,user.info.stats,video.list',
                'token_type'         => 'Bearer',
            ], 200),
        ]);

        $result = $this->service->exchangeCodeForToken('valid_auth_code_789');

        $this->assertEquals('act_test_access_token_123', $result['access_token']);
        $this->assertEquals(86400, $result['expires_in']);
        $this->assertEquals('_000_test_openid_abc', $result['open_id']);
        $this->assertEquals('rft_test_refresh_token_456', $result['refresh_token']);
        $this->assertEquals(31536000, $result['refresh_expires_in']);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    /**
     * Test 3: Token exchange failure
     */
    public function test_token_exchange_failure_throws_exception(): void
    {
        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'The authorization code is invalid or has expired.',
                'log_id'            => '202609081234567890ABCDEF',
            ], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The authorization code is invalid or has expired');

        $this->service->exchangeCodeForToken('invalid_code_999');
    }

    /**
     * Test 4: Refresh token success
     */
    public function test_refresh_token_success(): void
    {
        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_new_rotated_token_777',
                'expires_in'         => 86400,
                'open_id'            => '_000_test_openid_abc',
                'refresh_token'      => 'rft_new_rotated_refresh_888',
                'refresh_expires_in' => 31536000,
                'scope'              => 'user.info.basic,user.info.stats,video.list',
                'token_type'         => 'Bearer',
            ], 200),
        ]);

        $result = $this->service->refreshToken('rft_old_token_111');

        $this->assertEquals('act_new_rotated_token_777', $result['access_token']);
        $this->assertEquals('rft_new_rotated_refresh_888', $result['refresh_token']);
        $this->assertEquals(86400, $result['expires_in']);
    }

    /**
     * Test 5: Refresh token failure
     */
    public function test_refresh_token_failure_throws_exception(): void
    {
        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'Refresh token is expired or revoked.',
                'log_id'            => '202609089999999999XYZ',
            ], 400),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refresh token is expired or revoked');

        $this->service->refreshToken('rft_expired_token');
    }

    /**
     * Test 6: User info parsing
     */
    public function test_user_info_parsing(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'      => 'user_openid_123',
                        'union_id'     => 'user_unionid_456',
                        'avatar_url'   => 'https://p16-va.tiktokcdn.com/avatar.jpeg',
                        'display_name' => 'TVRI Aceh Official',
                    ],
                ],
                'error' => [
                    'code'    => 'ok',
                    'message' => '',
                    'log_id'  => 'log_user_001',
                ],
            ], 200),
        ]);

        $userInfo = $this->service->getUserInfo('test_valid_token');

        $this->assertEquals('user_openid_123', $userInfo['open_id']);
        $this->assertEquals('user_unionid_456', $userInfo['union_id']);
        $this->assertEquals('https://p16-va.tiktokcdn.com/avatar.jpeg', $userInfo['avatar_url']);
        $this->assertEquals('TVRI Aceh Official', $userInfo['display_name']);
    }

    /**
     * Test 7: Account stats parsing \u2014 stats fields harus di-pass eksplisit
     * karena default sekarang hanya BASIC_INFO_FIELDS (scope user.info.basic).
     */
    public function test_account_stats_parsing(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'         => 'user_openid_123',
                        'union_id'        => 'user_unionid_456',
                        'avatar_url'      => 'https://p16-va.tiktokcdn.com/avatar.jpeg',
                        'display_name'    => 'TVRI Aceh Official',
                        'follower_count'  => 52800,
                        'following_count' => 120,
                        'likes_count'     => 1450000,
                        'video_count'     => 340,
                    ],
                ],
                'error' => [
                    'code'    => 'ok',
                    'message' => '',
                    'log_id'  => 'log_stats_001',
                ],
            ], 200),
        ]);

        // Pass stats fields eksplisit \u2014 memerlukan scope user.info.stats
        $allFields = array_merge(TikTokService::BASIC_INFO_FIELDS, TikTokService::STATS_FIELDS);
        $stats = $this->service->getAccountStats('test_valid_token', $allFields);

        $this->assertEquals(52800, $stats['follower_count']);
        $this->assertEquals(120, $stats['following_count']);
        $this->assertEquals(1450000, $stats['likes_count']);
        $this->assertEquals(340, $stats['video_count']);
        $this->assertEquals('TVRI Aceh Official', $stats['display_name']);
    }

    /**
     * Test 8: Video list parsing with fields
     */
    public function test_video_list_parsing(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'                => '7123456789012345678',
                            'title'             => 'Berita Aceh Hari Ini',
                            'video_description' => 'Liputan khusus TVRI Aceh mengenai PON XXI.',
                            'duration'          => 45,
                            'cover_image_url'   => 'https://p16-va.tiktokcdn.com/cover1.jpeg',
                            'share_url'         => 'https://www.tiktok.com/@tvriaceh/video/7123456789012345678',
                            'like_count'        => 350,
                            'comment_count'     => 42,
                            'share_count'       => 15,
                            'view_count'        => 8500,
                            'create_time'       => 1725700000,
                        ],
                    ],
                    'cursor'   => 1725700000,
                    'has_more' => true,
                ],
                'error' => [
                    'code'    => 'ok',
                    'message' => '',
                    'log_id'  => 'log_vid_001',
                ],
            ], 200),
        ]);

        $response = $this->service->getVideoList('test_token', 20, 0);

        $this->assertCount(1, $response['videos']);
        $this->assertTrue($response['has_more']);
        $this->assertEquals(1725700000, $response['cursor']);

        $video = $response['videos'][0];
        $this->assertEquals('7123456789012345678', $video['external_id']);
        $this->assertEquals('Berita Aceh Hari Ini', $video['title']);
        $this->assertEquals(8500, $video['views']);
        $this->assertEquals(350, $video['likes']);
        $this->assertEquals(42, $video['comments']);
        $this->assertEquals(15, $video['shares']);
        $this->assertEquals(45, $video['duration']);
        $this->assertNotNull($video['upload_date']);
    }

    /**
     * Test 9: Cursor pagination multiple pages
     */
    public function test_cursor_pagination_fetches_multiple_pages(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::sequence()
                ->push([
                    'data' => [
                        'videos' => [
                            ['id' => 'VID_PAGE_1_A', 'title' => 'Video 1', 'view_count' => 100, 'create_time' => 1700000000],
                            ['id' => 'VID_PAGE_1_B', 'title' => 'Video 2', 'view_count' => 200, 'create_time' => 1700000000],
                        ],
                        'cursor'   => 1700000000,
                        'has_more' => true,
                    ],
                    'error' => ['code' => 'ok'],
                ], 200)
                ->push([
                    'data' => [
                        'videos' => [
                            ['id' => 'VID_PAGE_2_A', 'title' => 'Video 3', 'view_count' => 300, 'create_time' => 1699000000],
                        ],
                        'cursor'   => 1699000000,
                        'has_more' => false,
                    ],
                    'error' => ['code' => 'ok'],
                ], 200),
        ]);

        $result = $this->service->getAllNormalizedVideos('test_token', 10, 2);

        $this->assertTrue($result['is_complete']);
        $this->assertEquals(3, $result['total_fetched']);
        $this->assertCount(3, $result['data']);
        $this->assertEquals('VID_PAGE_1_A', $result['data'][0]['external_id']);
        $this->assertEquals('VID_PAGE_2_A', $result['data'][2]['external_id']);
    }

    /**
     * Test 10: Empty video response
     */
    public function test_empty_video_response_handled_gracefully(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $result = $this->service->getAllNormalizedVideos('test_token');

        $this->assertTrue($result['is_complete']);
        $this->assertEquals(0, $result['total_fetched']);
        $this->assertEmpty($result['data']);
    }

    /**
     * Test 11: Malformed response handling
     */
    public function test_malformed_response_handling(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'invalid_root_key' => 'corrupted_json',
            ], 200),
        ]);

        $result = $this->service->getAllNormalizedVideos('test_token');

        $this->assertFalse($result['is_complete']);
        $this->assertEquals(0, $result['total_fetched']);
    }

    /**
     * Test 12: Unchanged cursor fail-safe (prevents infinite loop)
     */
    public function test_unchanged_cursor_fail_safe_stops_infinite_loop(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_STUCK_1', 'title' => 'Stuck Video', 'view_count' => 10, 'create_time' => 1700000000],
                    ],
                    'cursor'   => 1700000000, // Same cursor returned repeatedly
                    'has_more' => true,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $result = $this->service->getAllNormalizedVideos('test_token', 10);

        // Fail-safe should stop on second iteration due to cursor remaining 1700000000
        $this->assertLessThanOrEqual(2, $result['total_fetched']);
    }

    /**
     * Test 13: Transient HTTP failure + retry
     */
    public function test_transient_connection_failure_retries_and_succeeds(): void
    {
        $attempts = 0;
        Http::fake(function ($request) use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response([
                'data' => [
                    'user' => [
                        'open_id'        => 'user_openid_after_retry',
                        'display_name'   => 'TVRI Aceh',
                        'follower_count' => 1000,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200);
        });

        $stats = $this->service->getAccountStats('test_token');

        $this->assertEquals('user_openid_after_retry', $stats['open_id']);
        $this->assertEquals(1000, $stats['follower_count']);
        $this->assertEquals(2, $attempts);
    }

    /**
     * Test 14: HTTP 429 rate limit retry
     */
    public function test_http_429_rate_limit_retries_and_succeeds(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::sequence()
                ->push(['error' => 'rate_limit_exceeded'], 429)
                ->push([
                    'data' => [
                        'user' => [
                            'open_id'        => 'user_rate_limited_ok',
                            'follower_count' => 2500,
                        ],
                    ],
                    'error' => ['code' => 'ok'],
                ], 200),
        ]);

        $stats = $this->service->getAccountStats('test_token');

        $this->assertEquals('user_rate_limited_ok', $stats['open_id']);
    }

    /**
     * Test 14b: HTTP 429 rate limit dengan header Retry-After
     */
    public function test_http_429_rate_limit_with_retry_after_header_retries_and_succeeds(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::sequence()
                ->push(['error' => 'rate_limit_exceeded'], 429, ['Retry-After' => '1'])
                ->push([
                    'data' => [
                        'user' => [
                            'open_id'        => 'user_retry_after_ok',
                            'follower_count' => 1200,
                        ],
                    ],
                    'error' => ['code' => 'ok'],
                ], 200),
        ]);

        $stats = $this->service->getAccountStats('test_token');

        $this->assertEquals('user_retry_after_ok', $stats['open_id']);
        $this->assertEquals(1200, $stats['follower_count']);
    }

    /**
     * Test 14c: Default max pages adalah 100 untuk menampung > 1.000 video
     */
    public function test_default_max_pages_is_100(): void
    {
        $this->assertEquals(100, TikTokService::DEFAULT_MAX_PAGES);
    }

    /**
     * Test 15: HTTP 5xx server error retry
     */
    public function test_http_5xx_server_error_retries_and_succeeds(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::sequence()
                ->push(['error' => 'Internal Server Error'], 503)
                ->push([
                    'data' => [
                        'user' => [
                            'open_id'        => 'user_503_recovered',
                            'follower_count' => 3000,
                        ],
                    ],
                    'error' => ['code' => 'ok'],
                ], 200),
        ]);

        $stats = $this->service->getAccountStats('test_token');

        $this->assertEquals('user_503_recovered', $stats['open_id']);
        $this->assertEquals(3000, $stats['follower_count']);
    }

    /**
     * Test 16: Permanent 4xx (401/403) throws directly without redundant retries
     */
    public function test_permanent_401_throws_directly(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'error' => [
                    'code'    => 'access_token_invalid',
                    'message' => 'The access token provided is expired or invalid.',
                ],
            ], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Akses ditolak oleh TikTok API (HTTP 401)');

        $this->service->getAccountStats('invalid_bearer_token');
    }

    /**
     * Test 17: Nullable metrics remain NULL (Strict NULL Semantics)
     */
    public function test_nullable_metrics_remain_null(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'         => 'user_null_stats',
                        'display_name'    => 'User Without Stats',
                        'follower_count'  => null, // Unavailable metric
                        'following_count' => null,
                        'likes_count'     => null,
                        'video_count'     => null,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $stats = $this->service->getAccountStats('test_token');

        $this->assertNull($stats['follower_count']);
        $this->assertNull($stats['following_count']);
        $this->assertNull($stats['likes_count']);
        $this->assertNull($stats['video_count']);

        // Test video item with NULL metrics
        $normalizedVideo = $this->service->normalizeVideoItem([
            'id'                => 'VID_NULL_METRICS',
            'title'             => 'Video without views',
            'view_count'        => null, // Unavailable metric
            'like_count'        => null,
            'comment_count'     => null,
            'share_count'       => null,
            'duration'          => null,
            'create_time'       => null,
        ]);

        $this->assertNull($normalizedVideo['views']);
        $this->assertNull($normalizedVideo['likes']);
        $this->assertNull($normalizedVideo['comments']);
        $this->assertNull($normalizedVideo['shares']);
        $this->assertNull($normalizedVideo['duration']);
        $this->assertNull($normalizedVideo['upload_date']);
    }

    /**
     * Test 18: Credentials are never leaked in errors or logs
     */
    public function test_credentials_are_not_leaked_in_exception_messages(): void
    {
        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'error'             => 'invalid_client',
                'error_description' => 'Client authentication failed',
                'log_id'            => 'log_leak_check_123',
            ], 400),
        ]);

        try {
            $this->service->exchangeCodeForToken('auth_code');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringNotContainsString('test_client_secret_xyz', $message);
            $this->assertStringContainsString('Client authentication failed', $message);
        }
    }

    /**
     * Test 19: HTTP 401 scope_not_authorized menghasilkan pesan error yang informatif
     * dan dapat dibedakan dari generic token invalid error.
     */
    public function test_scope_not_authorized_produces_informative_error(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'error' => [
                    'code'    => 'scope_not_authorized',
                    'message' => 'The required scope is not authorized.',
                    'log_id'  => 'log_scope_err_001',
                ],
            ], 401),
        ]);

        $allFields = array_merge(TikTokService::BASIC_INFO_FIELDS, TikTokService::STATS_FIELDS);

        try {
            $this->service->getAccountStats('valid_token_but_no_stats_scope', $allFields);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            // Harus mengandung kode error TikTok yang spesifik
            $this->assertStringContainsString('scope_not_authorized', $message);
            // Harus memberi petunjuk tentang konfigurasi Developer Portal
            $this->assertStringContainsString('TikTok Developer Portal', $message);
            // Tidak boleh mengandung pesan generic token invalid
            $this->assertStringNotContainsString('Token mungkin tidak valid atau kedaluwarsa', $message);
        }
    }

    /**
     * Test 20: has_more = false → is_complete = true
     */
    public function test_pagination_complete_when_has_more_is_false(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_COMPLETE_1', 'title' => 'Complete Video', 'view_count' => 100, 'create_time' => 1700000000],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $result = $this->service->getAllNormalizedVideos('test_token', 10);

        $this->assertTrue($result['is_complete'], 'Harus complete jika has_more = false');
        $this->assertEquals(1, $result['total_fetched']);
    }

    /**
     * Test 21: maxPages tercapai DAN has_more = true → is_complete = false
     */
    public function test_pagination_incomplete_when_max_pages_reached_and_has_more_true(): void
    {
        // Always returns has_more = true (API claims more data exists)
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_LIMIT_1', 'title' => 'Limit Video', 'view_count' => 50, 'create_time' => 1700000001],
                    ],
                    'cursor'   => 1700000001,
                    'has_more' => true,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        // maxPages = 1: loop berhenti setelah 1 halaman, has_more masih true → incomplete
        $result = $this->service->getAllNormalizedVideos('test_token', 1);

        $this->assertFalse($result['is_complete'], 'Harus incomplete jika maxPages tercapai dan has_more masih true');
        $this->assertGreaterThanOrEqual(1, $result['total_fetched']);
    }

    /**
     * Test 22: Exception saat fetch → is_complete = false
     */
    public function test_pagination_incomplete_when_exception_occurs(): void
    {
        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'error' => [
                    'code'    => 'server_error',
                    'message' => 'Internal server error',
                ],
            ], 500),
        ]);

        $result = $this->service->getAllNormalizedVideos('test_token', 5);

        $this->assertFalse($result['is_complete'], 'Harus incomplete jika terjadi exception/error API');
        $this->assertEquals(0, $result['total_fetched']);
    }

    /**
     * Test 23: Config services.tiktok.max_pages dibaca dengan benar oleh TikTokService
     */
    public function test_config_max_pages_is_read_properly(): void
    {
        Config::set('services.tiktok.max_pages', 250);

        $service = new TikTokService();

        $this->assertEquals(250, $service->getMaxPages(), 'getMaxPages() harus mengembalikan nilai dari config services.tiktok.max_pages');
    }

    /**
     * Test 24: DEFAULT_MAX_PAGES digunakan sebagai fallback jika env/config tidak tersedia
     */
    public function test_default_fallback_max_pages_works(): void
    {
        // Hapus config max_pages agar fallback ke DEFAULT_MAX_PAGES
        Config::set('services.tiktok.max_pages', null);

        $service = new TikTokService();

        // config('services.tiktok.max_pages', DEFAULT_MAX_PAGES) harus fallback ke 100
        // Jika config null, (int) null = 0, sehingga konstruktor harus menggunakan fallback
        // Pastikan getMaxPages() tidak 0
        $this->assertGreaterThan(0, $service->getMaxPages(), 'getMaxPages() tidak boleh 0 saat config null');
        $this->assertEquals(TikTokService::DEFAULT_MAX_PAGES, $service->getMaxPages(), 'Harus fallback ke DEFAULT_MAX_PAGES = 100');
    }
}
