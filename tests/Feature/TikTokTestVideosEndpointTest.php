<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\TikTokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokTestVideosEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Platform $tiktokPlatform;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.tiktok.client_key', 'test_client_key_123');
        Config::set('services.tiktok.client_secret', 'test_client_secret_xyz');
        Config::set('services.tiktok.redirect_uri', 'http://localhost/tiktok/callback');

        $this->user = User::factory()->create([
            'email' => 'video_tester@tvri.co.id',
        ]);

        $this->tiktokPlatform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );
    }

    private function createConnectedAccount(string $token = 'valid_token_test_vid'): SocialAccount
    {
        return SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_tvri_vid_001',
            'username'            => 'TVRI Aceh Video Test',
            'access_token'        => $token,
            'expires_at'          => Carbon::now()->addHours(20),
        ]);
    }

    /**
     * Test 1: Endpoint memerlukan autentikasi — unauthenticated redirect ke login (302)
     */
    public function test_test_videos_requires_authentication(): void
    {
        // Route dengan middleware 'auth' akan redirect ke login page (302), bukan 401
        $response = $this->get(route('tiktok.test.videos'));

        $response->assertRedirect();
        // Pastikan tidak ada data sensitif pada redirect
        $this->assertStringNotContainsString('access_token', $response->getContent());
    }

    /**
     * Test 2: Akun belum terhubung mengembalikan 404
     */
    public function test_test_videos_returns_404_when_no_account_connected(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertStatus(404);
        $response->assertJson(['connected' => false]);
    }

    /**
     * Test 3: Token kedaluwarsa mengembalikan 401
     */
    public function test_test_videos_returns_401_when_token_expired(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_expired',
            'username'            => 'TVRI Expired',
            'access_token'        => 'expired_token_abc',
            'expires_at'          => Carbon::now()->subHours(2),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertStatus(401);
        $response->assertJson(['is_expired' => true]);
    }

    /**
     * Test 4: Video list berhasil — 3 video dikembalikan dengan struktur lengkap
     */
    public function test_test_videos_returns_videos_on_success(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'              => 'VID_001',
                            'title'           => 'Berita Aceh Hari Ini',
                            'cover_image_url' => 'https://p16-va.tiktokcdn.com/cover1.jpeg',
                            'share_url'       => 'https://www.tiktok.com/@tvriaceh/video/VID_001',
                            'view_count'      => 8500,
                            'like_count'      => 350,
                            'comment_count'   => 42,
                            'share_count'     => 15,
                            'create_time'     => 1725700000,
                        ],
                        [
                            'id'            => 'VID_002',
                            'title'         => 'Profil Aceh',
                            'view_count'    => 1200,
                            'like_count'    => 88,
                            'comment_count' => 10,
                            'share_count'   => 5,
                            'create_time'   => 1725600000,
                        ],
                        [
                            'id'            => 'VID_003',
                            'title'         => 'Wisata Sabang',
                            'view_count'    => 3400,
                            'like_count'    => 210,
                            'comment_count' => 27,
                            'share_count'   => 9,
                            'create_time'   => 1725500000,
                        ],
                    ],
                    'cursor'   => 1725500000,
                    'has_more' => true,
                ],
                'error' => ['code' => 'ok', 'message' => '', 'log_id' => 'log_vid_ok_001'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertOk();
        $response->assertJson([
            'connected'      => true,
            'status'         => 'ok',
            'username'       => 'TVRI Aceh Video Test',
            'total_returned' => 3,
            'has_more'       => true,
        ]);

        $data = $response->json();
        $this->assertCount(3, $data['videos']);

        $v = $data['videos'][0];
        $this->assertEquals('VID_001', $v['external_id']);
        $this->assertEquals('Berita Aceh Hari Ini', $v['title']);
        $this->assertEquals(8500, $v['views']);
        $this->assertEquals(350, $v['likes']);
        $this->assertEquals(42, $v['comments']);
        $this->assertEquals(15, $v['shares']);
        $this->assertNotNull($v['upload_date']);
        $this->assertEquals('https://www.tiktok.com/@tvriaceh/video/VID_001', $v['url']);
        $this->assertEquals('https://p16-va.tiktokcdn.com/cover1.jpeg', $v['thumbnail_url']);
    }

    /**
     * Test 5: Response kosong — total_returned=0, status ok
     */
    public function test_test_videos_returns_empty_list_gracefully(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => [],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok', 'message' => '', 'log_id' => 'log_empty_001'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertOk();
        $response->assertJson([
            'connected'      => true,
            'status'         => 'ok',
            'total_returned' => 0,
            'has_more'       => false,
            'videos'         => [],
        ]);
    }

    /**
     * Test 6: Endpoint hanya mengambil maks 5 video
     */
    public function test_test_videos_respects_max_5_video_limit(): void
    {
        $this->createConnectedAccount();

        $fakeVideos = [];
        for ($i = 1; $i <= 5; $i++) {
            $fakeVideos[] = [
                'id'          => "VID_{$i}",
                'title'       => "Video {$i}",
                'view_count'  => $i * 100,
                'create_time' => 1725700000 - ($i * 1000),
            ];
        }

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos'   => $fakeVideos,
                    'cursor'   => 1725695000,
                    'has_more' => true,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertOk();
        $data = $response->json();

        // Endpoint mengambil maksimal 5 video
        $this->assertLessThanOrEqual(5, $data['total_returned']);
        $this->assertLessThanOrEqual(5, count($data['videos']));
    }

    /**
     * Test 7: Strict NULL Semantics — views null tetap null, 0 tetap 0
     */
    public function test_test_videos_preserves_strict_null_semantics(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'            => 'VID_NULL',
                            'title'         => 'Video Without Metrics',
                            'view_count'    => null,
                            'like_count'    => null,
                            'comment_count' => null,
                            'share_count'   => null,
                            'create_time'   => null,
                        ],
                        [
                            'id'            => 'VID_ZERO',
                            'title'         => 'Video With Zero Metrics',
                            'view_count'    => 0,
                            'like_count'    => 0,
                            'comment_count' => 0,
                            'share_count'   => 0,
                            'create_time'   => 1725600000,
                        ],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response  = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));
        $response->assertOk();

        $data      = $response->json();
        $nullVideo = $data['videos'][0];
        $zeroVideo = $data['videos'][1];

        // Null tetap null
        $this->assertNull($nullVideo['views']);
        $this->assertNull($nullVideo['likes']);
        $this->assertNull($nullVideo['comments']);
        $this->assertNull($nullVideo['shares']);
        $this->assertNull($nullVideo['upload_date']);

        // 0 tetap 0 — bukan null
        $this->assertSame(0, $zeroVideo['views']);
        $this->assertSame(0, $zeroVideo['likes']);
        $this->assertSame(0, $zeroVideo['comments']);
        $this->assertSame(0, $zeroVideo['shares']);
    }

    /**
     * Test 8: HTTP 401 dari TikTok API menghasilkan response error informatif
     */
    public function test_test_videos_handles_tiktok_api_401_gracefully(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'error' => [
                    'code'    => 'access_token_invalid',
                    'message' => 'The access token provided is expired or invalid.',
                    'log_id'  => 'log_401_err_001',
                ],
            ], 401),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertStatus(500);
        $data = $response->json();

        $this->assertTrue($data['connected']);
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('tiktok_error_message', $data);
        $this->assertNotEmpty($data['tiktok_error_message']);
    }

    /**
     * Test 9: Malformed response dari TikTok API menghasilkan error graceful
     */
    public function test_test_videos_handles_malformed_api_response(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'unexpected_key' => 'bad_data',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertStatus(500);
        $data = $response->json();
        $this->assertEquals('error', $data['status']);
    }

    /**
     * Test 10: Token tidak muncul di response JSON saat sukses
     */
    public function test_test_videos_does_not_leak_access_token_in_success_response(): void
    {
        $secretToken = 'act_super_secret_token_xyz_do_not_leak';
        $this->createConnectedAccount($secretToken);

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data'  => ['videos' => [], 'cursor' => null, 'has_more' => false],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response     = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));
        $responseBody = $response->getContent();

        $this->assertStringNotContainsString($secretToken, $responseBody);
        $this->assertStringNotContainsString('access_token', $responseBody);
    }

    /**
     * Test 11: Token tidak muncul di response JSON saat error API
     */
    public function test_test_videos_does_not_leak_token_on_api_error(): void
    {
        $secretToken = 'act_super_secret_token_on_error';
        $this->createConnectedAccount($secretToken);

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'error' => ['code' => 'internal_error', 'message' => 'Server failure'],
            ], 500),
        ]);

        $response     = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));
        $responseBody = $response->getContent();

        $this->assertStringNotContainsString($secretToken, $responseBody);
        $this->assertStringNotContainsString('access_token', $responseBody);
    }

    /**
     * Test 12: has_more dan pagination info tersedia di response
     */
    public function test_test_videos_exposes_pagination_info(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        [
                            'id'          => 'VID_PAG',
                            'title'       => 'Paging Video',
                            'view_count'  => 100,
                            'create_time' => 1725700000,
                        ],
                    ],
                    'cursor'   => 1725700000,
                    'has_more' => true,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['has_more']);
        $this->assertEquals(1, $data['total_returned']);
    }

    /**
     * Test 13: diagnostic_note tersedia di response sukses
     */
    public function test_test_videos_includes_diagnostic_note_in_response(): void
    {
        $this->createConnectedAccount();

        Http::fake([
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data'  => ['videos' => [], 'cursor' => null, 'has_more' => false],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test.videos'));

        $response->assertOk();
        $data = $response->json();

        $this->assertArrayHasKey('diagnostic_note', $data);
        $this->assertStringContainsString('Tidak ada perubahan database', $data['diagnostic_note']);
    }
}
