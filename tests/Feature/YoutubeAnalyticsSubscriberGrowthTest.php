<?php

namespace Tests\Feature;

use App\Models\ChannelStatsDaily;
use App\Models\Content;
use App\Models\Platform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\YoutubeAnalyticsService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YoutubeAnalyticsSubscriberGrowthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Platform $youtubePlatform;

    protected function setUp(): void
    {
        parent::setUp();

        // Bersihkan cache
        Cache::flush();

        $this->user = User::factory()->create([
            'email' => 'test_admin@tvri.co.id',
        ]);

        $this->youtubePlatform = Platform::create([
            'nama'      => 'YouTube',
            'slug'      => 'youtube',
            'icon'      => 'youtube',
            'color'     => '#FF0000',
            'is_active' => true,
        ]);

        Content::create([
            'platform_id'         => $this->youtubePlatform->id,
            'content_id_external' => 'TEST_VID_001',
            'judul'               => 'Test Video TVRI',
            'url'                 => 'https://www.youtube.com/watch?v=TEST_VID_001',
            'status'              => 'aktif',
            'tanggal_upload'      => '2026-09-01',
        ]);
    }

    /**
     * Test 1: Parsing response standar subscribersGained & subscribersLost.
     */
    public function test_parsing_subscribers_gained_and_lost_calculates_net_growth_correctly(): void
    {
        $service = new YoutubeAnalyticsService();

        $apiData = [
            'kind'          => 'youtubeAnalytics#resultTable',
            'columnHeaders' => [
                ['name' => 'subscribersGained', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
                ['name' => 'subscribersLost', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
            ],
            'rows' => [
                [350, 38],
            ],
        ];

        $parsed = $service->parseReportResponse($apiData, '2026-08-01', '2026-08-31');

        $this->assertEquals(350, $parsed['subscribersGained']);
        $this->assertEquals(38, $parsed['subscribersLost']);
        $this->assertEquals(312, $parsed['net'], 'Net growth harus exact 350 - 38 = 312');
        $this->assertEquals('2026-08-01', $parsed['startDate']);
        $this->assertEquals('2026-08-31', $parsed['endDate']);
    }

    /**
     * Test 2: Parsing response saat tidak ada rows / data kosong.
     */
    public function test_parsing_handles_missing_or_empty_rows_gracefully(): void
    {
        $service = new YoutubeAnalyticsService();

        $emptyData = [
            'kind'          => 'youtubeAnalytics#resultTable',
            'columnHeaders' => [
                ['name' => 'subscribersGained', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
                ['name' => 'subscribersLost', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
            ],
            'rows' => [],
        ];

        $parsed = $service->parseReportResponse($emptyData, '2026-09-01', '2026-09-07');

        $this->assertEquals(0, $parsed['subscribersGained']);
        $this->assertEquals(0, $parsed['subscribersLost']);
        $this->assertEquals(0, $parsed['net']);
    }

    /**
     * Test 3: Parsing response saat posisi columnHeaders dibalik oleh API.
     */
    public function test_parsing_handles_reordered_column_headers(): void
    {
        $service = new YoutubeAnalyticsService();

        $reorderedData = [
            'kind'          => 'youtubeAnalytics#resultTable',
            'columnHeaders' => [
                ['name' => 'subscribersLost', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
                ['name' => 'subscribersGained', 'columnType' => 'METRIC', 'dataType' => 'INTEGER'],
            ],
            'rows' => [
                [25, 400], // Lost=25, Gained=400
            ],
        ];

        $parsed = $service->parseReportResponse($reorderedData, '2026-08-01', '2026-08-15');

        $this->assertEquals(400, $parsed['subscribersGained']);
        $this->assertEquals(25, $parsed['subscribersLost']);
        $this->assertEquals(375, $parsed['net']);
    }

    /**
     * Test 4: Parsing saat net growth bernilai negatif (subscribersLost > subscribersGained).
     */
    public function test_parsing_handles_negative_net_growth(): void
    {
        $service = new YoutubeAnalyticsService();

        $lossData = [
            'kind'          => 'youtubeAnalytics#resultTable',
            'columnHeaders' => [
                ['name' => 'subscribersGained', 'columnType' => 'METRIC'],
                ['name' => 'subscribersLost', 'columnType' => 'METRIC'],
            ],
            'rows' => [
                [10, 55],
            ],
        ];

        $parsed = $service->parseReportResponse($lossData, '2026-09-01', '2026-09-05');

        $this->assertEquals(10, $parsed['subscribersGained']);
        $this->assertEquals(55, $parsed['subscribersLost']);
        $this->assertEquals(-45, $parsed['net']);
    }

    /**
     * Test 5: Panggilan API getSubscriberGrowth sukses dengan token & parameter query.
     */
    public function test_get_subscriber_growth_successful_api_call(): void
    {
        Http::fake([
            'https://youtubeanalytics.googleapis.com/v2/reports*' => function ($request) {
                $query = $request->data();
                $this->assertEquals('2026-08-15', $query['startDate']);
                $this->assertEquals('2026-08-31', $query['endDate']);
                $this->assertEquals('subscribersGained,subscribersLost', $query['metrics']);

                return Http::response([
                    'columnHeaders' => [
                        ['name' => 'subscribersGained'],
                        ['name' => 'subscribersLost'],
                    ],
                    'rows' => [
                        [420, 54],
                    ],
                ], 200);
            },
        ]);

        $service = new YoutubeAnalyticsService();
        $service->setAccessToken('fake-test-bearer-token');

        $result = $service->getSubscriberGrowth('2026-08-15', '2026-08-31');

        $this->assertNotNull($result);
        $this->assertEquals(420, $result['subscribersGained']);
        $this->assertEquals(54, $result['subscribersLost']);
        $this->assertEquals(366, $result['net'], 'Net exact growth harus 420 - 54 = 366');
    }

    /**
     * Test 6: Normalisasi tanggal saat startDate null atau urutan tanggal terbalik.
     */
    public function test_get_subscriber_growth_normalizes_dates(): void
    {
        ChannelStatsDaily::create([
            'tanggal'          => '2026-08-01',
            'subscriber_count' => 27000,
            'total_views'      => 8500000,
            'video_count'      => 6000,
        ]);

        Http::fake([
            'https://youtubeanalytics.googleapis.com/v2/reports*' => function ($request) {
                $query = $request->data();
                // startDate null harus dinormalisasi ke tanggal snapshot terawal
                $this->assertEquals('2026-08-01', $query['startDate']);
                $this->assertEquals('2026-09-01', $query['endDate']);

                return Http::response([
                    'rows' => [[100, 10]],
                ], 200);
            },
        ]);

        $service = new YoutubeAnalyticsService();
        $service->setAccessToken('fake-test-bearer-token');

        // Test startDate null (periode "Semua Waktu")
        $result = $service->getSubscriberGrowth(null, '2026-09-01');
        $this->assertNotNull($result);
        $this->assertEquals(90, $result['net']);
    }

    /**
     * Test 7: API failure (HTTP 500 / Network timeout) ditangani aman dan return null.
     */
    public function test_api_failure_returns_null_without_crashing(): void
    {
        Http::fake([
            'https://youtubeanalytics.googleapis.com/v2/reports*' => function () {
                throw new ConnectException('cURL error 28: Timeout', new PsrRequest('GET', 'https://youtubeanalytics.googleapis.com/v2/reports'));
            },
        ]);

        $service = new YoutubeAnalyticsService();
        $service->setAccessToken('fake-test-bearer-token');

        $result = $service->getSubscriberGrowth('2026-08-01', '2026-08-15');

        $this->assertNull($result, 'Kegagalan koneksi harus mengembalikan null tanpa unhandled exception');
    }

    /**
     * Test 8: HTTP 401 Unauthorized membersihkan cache access token dan return null.
     */
    public function test_authentication_failure_clears_token_cache_and_returns_null(): void
    {
        Cache::put('youtube_analytics_access_token', 'stale-token', 3600);

        Http::fake([
            'https://youtubeanalytics.googleapis.com/v2/reports*' => Http::response([
                'error' => ['code' => 401, 'message' => 'Request had invalid authentication credentials.'],
            ], 401),
        ]);

        $service = new YoutubeAnalyticsService();

        $result = $service->getSubscriberGrowth('2026-08-01', '2026-08-15');

        $this->assertNull($result);
        $this->assertFalse(Cache::has('youtube_analytics_access_token'), 'Cache token harus dibersihkan saat 401');
    }

    /**
     * Test 9: Jika kredensial belum ada sama sekali, return null tanpa memanggil API.
     */
    public function test_missing_credentials_returns_null_without_calling_api(): void
    {
        Config::set('services.youtube.client_id', null);
        Config::set('services.youtube.client_secret', null);
        Config::set('services.youtube.refresh_token', null);

        Http::fake();

        $service = new YoutubeAnalyticsService();

        $this->assertFalse($service->hasCredentials());

        $result = $service->getSubscriberGrowth('2026-08-01', '2026-08-15');
        $this->assertNull($result);

        Http::assertNothingSent();
    }

    /**
     * Test 10: Alur refresh token via oauth2.googleapis.com/token.
     */
    public function test_token_refresh_flow_fetches_and_caches_access_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'newly-refreshed-access-token-12345',
                'expires_in'   => 3600,
                'token_type'   => 'Bearer',
            ], 200),
        ]);

        $service = new YoutubeAnalyticsService(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            refreshToken: 'test-refresh-token-abc'
        );

        $token = $service->getAccessToken();

        $this->assertEquals('newly-refreshed-access-token-12345', $token);
        $this->assertTrue(Cache::has('youtube_analytics_access_token'));
        $this->assertEquals('newly-refreshed-access-token-12345', Cache::get('youtube_analytics_access_token'));
    }

    /**
     * Test 11: Dashboard menampilkan exact subscriber growth jika YouTube Analytics tersedia.
     */
    public function test_dashboard_uses_exact_subscriber_growth_when_analytics_available(): void
    {
        // Snapshot channel historis dengan selisih rounded +300 (27600 ke 27900)
        ChannelStatsDaily::create([
            'tanggal'          => Carbon::today()->subDays(6)->toDateString(),
            'subscriber_count' => 27600,
            'total_views'      => 8600000,
            'video_count'      => 6100,
        ]);
        ChannelStatsDaily::create([
            'tanggal'          => Carbon::today()->toDateString(),
            'subscriber_count' => 27900,
            'total_views'      => 8860000,
            'video_count'      => 6300,
        ]);

        // Buat mock service yang mengembalikan exact growth net: 312
        $mockAnalytics = $this->createMock(YoutubeAnalyticsService::class);
        $mockAnalytics->method('getSubscriberGrowth')->willReturn([
            'subscribersGained' => 350,
            'subscribersLost'   => 38,
            'net'               => 312,
            'startDate'         => Carbon::today()->subDays(6)->toDateString(),
            'endDate'           => Carbon::today()->toDateString(),
        ]);

        $this->app->instance(YoutubeAnalyticsService::class, $mockAnalytics);

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        // Dashboard harus menerima angka exact 312, bukan rounded 300
        $response->assertViewHas('followersGrowth', 312);
        // Snapshot total subscriber tetap 27.900
        $response->assertViewHas('currentFollowers', 27900);
        $response->assertSee('+312');
    }

    /**
     * Test 12: Backward compatibility - jika Analytics API belum diotorisasi / null,
     * dashboard otomatis fallback ke ChannelStatsDaily::getFollowersGained tanpa error.
     */
    public function test_dashboard_backward_compatibility_fallback_when_analytics_unavailable(): void
    {
        ChannelStatsDaily::create([
            'tanggal'          => Carbon::today()->subDays(6)->toDateString(),
            'subscriber_count' => 27600,
            'total_views'      => 8600000,
            'video_count'      => 6100,
        ]);
        ChannelStatsDaily::create([
            'tanggal'          => Carbon::today()->toDateString(),
            'subscriber_count' => 27900,
            'total_views'      => 8860000,
            'video_count'      => 6300,
        ]);

        // Mock Analytics API mengembalikan null (misal belum diotorisasi)
        $mockAnalytics = $this->createMock(YoutubeAnalyticsService::class);
        $mockAnalytics->method('getSubscriberGrowth')->willReturn(null);

        $this->app->instance(YoutubeAnalyticsService::class, $mockAnalytics);

        $response = $this->actingAs($this->user)->get(route('dashboard', ['platform' => 'youtube', 'period' => '7']));

        $response->assertOk();
        // Fallback ke selisih rounded snapshot: 27900 - 27600 = 300
        $response->assertViewHas('followersGrowth', 300);
        $response->assertViewHas('currentFollowers', 27900);
        $response->assertSee('+300');
    }

    /**
     * Test 13: Alur route OAuth connect & callback.
     */
    public function test_youtube_auth_routes(): void
    {
        Config::set('services.youtube.client_id', 'test-client-id-123');
        Config::set('services.youtube.client_secret', 'test-secret-456');
        Config::set('services.youtube.redirect_uri', 'http://localhost/youtube/callback');

        // Test connect redirect ke Google OAuth consent screen
        $connectRes = $this->actingAs($this->user)->get(route('youtube.connect'));
        $connectRes->assertRedirect();
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $connectRes->headers->get('Location'));
        $this->assertStringContainsString('yt-analytics.readonly', $connectRes->headers->get('Location'));

        // Test callback dengan exchangeCodeForTokens
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'mock-access-token',
                'refresh_token' => 'mock-refresh-token',
                'expires_in'    => 3600,
            ], 200),
        ]);

        $callbackRes = $this->actingAs($this->user)->get(route('youtube.callback', ['code' => 'test-auth-code']));
        $callbackRes->assertRedirect(route('dashboard', ['platform' => 'youtube']));
        $callbackRes->assertSessionHas('success');

        // Pastikan SocialAccount YouTube tersimpan
        $this->assertDatabaseHas('social_accounts', [
            'platform_id' => $this->youtubePlatform->id,
        ]);
    }
}
