<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\TikTokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TikTokAuthControllerTest extends TestCase
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
            'email' => 'admin_test@tvri.co.id',
            'role'  => 'admin',
        ]);

        $this->tiktokPlatform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );
    }

    /**
     * Test 1 & 2: Connect redirects to TikTok OAuth and saves state in session
     */
    public function test_connect_redirects_to_tiktok_oauth_and_saves_state_in_session(): void
    {
        $response = $this->actingAs($this->user)->get(route('tiktok.connect'));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');

        $this->assertStringStartsWith(TikTokService::AUTH_BASE_URL, $targetUrl);
        $this->assertStringContainsString('client_key=test_client_key_123', $targetUrl);
        $this->assertStringContainsString('response_type=code', $targetUrl);

        $response->assertSessionHas('tiktok_oauth_state');
        $savedState = session('tiktok_oauth_state');
        $this->assertNotEmpty($savedState);
        $this->assertStringContainsString("state={$savedState}", $targetUrl);
    }

    /**
     * Test: Connect with missing config redirects to status with error
     */
    public function test_connect_with_missing_credentials_redirects_with_error(): void
    {
        Config::set('services.tiktok.client_key', null);

        $response = $this->actingAs($this->user)->get(route('tiktok.connect'));

        $response->assertRedirect(route('tiktok.status'));
        $response->assertSessionHas('error');
    }

    /**
     * Test 3: Callback with invalid state is rejected
     */
    public function test_callback_with_invalid_state_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => 'valid_session_state_123'])
            ->get(route('tiktok.callback', [
                'code'  => 'auth_code_123',
                'state' => 'forged_or_invalid_state_456',
            ]));

        $response->assertRedirect(route('tiktok.status'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('State otorisasi tidak valid', session('error'));
    }

    /**
     * Test 4: Callback without code is handled
     */
    public function test_callback_without_code_is_handled(): void
    {
        $state = 'valid_session_state_123';

        $response = $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', [
                'state' => $state,
            ]));

        $response->assertRedirect(route('tiktok.status'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Kode otorisasi (code) tidak diterima', session('error'));
    }

    /**
     * Test 5: Callback with OAuth error parameter is handled
     */
    public function test_callback_with_oauth_error_is_handled(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('tiktok.callback', [
                'error'             => 'access_denied',
                'error_description' => 'User denied authorization request',
            ]));

        $response->assertRedirect(route('tiktok.status'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('User denied authorization request', session('error'));
    }

    /**
     * Test 6, 7, 8, 10, 11: Token exchange, user info, creates social account & precise expires_at
     */
    public function test_callback_success_creates_social_account_with_encrypted_token(): void
    {
        $state = 'valid_session_state_xyz';

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_live_access_token_123',
                'expires_in'         => 86400,
                'open_id'            => 'open_id_tvri_aceh_001',
                'refresh_token'      => 'rft_live_refresh_token_456',
                'refresh_expires_in' => 31536000,
                'scope'              => 'user.info.basic,user.info.stats,video.list',
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'      => 'open_id_tvri_aceh_001',
                        'union_id'     => 'union_id_tvri_aceh_002',
                        'avatar_url'   => 'https://p16-va.tiktokcdn.com/avatar.jpeg',
                        'display_name' => 'TVRI Aceh Official',
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', [
                'code'  => 'valid_auth_code_999',
                'state' => $state,
            ]));

        $response->assertRedirect(route('tiktok.status'));
        $response->assertSessionHas('success');

        // Verify account creation in database
        $this->assertDatabaseHas('social_accounts', [
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_tvri_aceh_001',
            'username'            => 'TVRI Aceh Official',
            'connected_by'        => $this->user->id,
        ]);

        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();
        $this->assertNotNull($account);

        // Access token is transparently decrypted via accessor
        $this->assertEquals('act_live_access_token_123', $account->access_token);

        // Access token is encrypted in raw database
        $rawAccount = \Illuminate\Support\Facades\DB::table('social_accounts')
            ->where('id', $account->id)
            ->first();
        $this->assertNotEquals('act_live_access_token_123', $rawAccount->access_token);
        $this->assertEquals('act_live_access_token_123', Crypt::decryptString($rawAccount->access_token));

        // Expiration calculation
        $this->assertNotNull($account->expires_at);
        $this->assertTrue($account->expires_at->isFuture());

        // Token is NOT in flash session or response
        $this->assertStringNotContainsString('act_live_access_token_123', session('success'));
        $this->assertStringNotContainsString('rft_live_refresh_token_456', session('success'));
    }

    /**
     * Test 9: Existing social account is updated (not duplicated)
     */
    public function test_callback_updates_existing_social_account(): void
    {
        // Existing account
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'old_open_id',
            'username'            => 'Old Username',
            'access_token'        => 'old_access_token',
            'expires_at'          => Carbon::now()->subDays(2),
        ]);

        $state = 'valid_session_state_update';

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_updated_token_999',
                'expires_in'         => 7200,
                'open_id'            => 'new_open_id_updated',
                'refresh_token'      => 'rft_updated_refresh',
                'refresh_expires_in' => 31536000,
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'      => 'new_open_id_updated',
                        'display_name' => 'TVRI Aceh Updated',
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', [
                'code'  => 'auth_code_update',
                'state' => $state,
            ]));

        // Exactly 1 record should exist for tiktok platform
        $this->assertEquals(1, SocialAccount::where('platform_id', $this->tiktokPlatform->id)->count());

        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();
        $this->assertEquals('new_open_id_updated', $account->external_account_id);
        $this->assertEquals('TVRI Aceh Updated', $account->username);
        $this->assertEquals('act_updated_token_999', $account->access_token);
    }

    /**
     * Test 12: Token / credentials are not leaked in log/error output
     */
    public function test_token_and_secret_are_not_leaked_in_callback_errors(): void
    {
        $state = 'valid_state_for_fail';

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'Authorization code expired',
                'log_id'            => 'log_123_no_leak',
            ], 400),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', [
                'code'  => 'expired_code',
                'state' => $state,
            ]));

        $response->assertRedirect(route('tiktok.status'));
        $errorMsg = session('error');
        $this->assertStringNotContainsString('test_client_secret_xyz', $errorMsg);
        $this->assertStringContainsString('Authorization code expired', $errorMsg);
    }

    /**
     * Test 13: Status endpoint when disconnected
     */
    public function test_status_endpoint_when_disconnected(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('tiktok.status'));

        $response->assertOk();
        $response->assertJson([
            'connected' => false,
            'platform'  => 'tiktok',
        ]);
        $response->assertJsonMissing(['access_token', 'refresh_token', 'client_secret']);
    }

    /**
     * Test 14: Status endpoint when connected
     */
    public function test_status_endpoint_when_connected(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_tvri_status',
            'username'            => 'TVRI Aceh Staging',
            'access_token'        => 'secret_access_token_123',
            'expires_at'          => Carbon::now()->addDays(1),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.status'));

        $response->assertOk();
        $response->assertJson([
            'connected'           => true,
            'platform'            => 'tiktok',
            'username'            => 'TVRI Aceh Staging',
            'external_account_id' => 'open_id_tvri_status',
            'is_expired'          => false,
        ]);

        // Ensure token is NOT present in json response
        $response->assertJsonMissing(['access_token', 'refresh_token', 'client_secret']);
        $this->assertStringNotContainsString('secret_access_token_123', $response->getContent());
    }

    /**
     * Test 15: Test diagnostic endpoint when disconnected returns 404
     */
    public function test_test_endpoint_when_disconnected_returns_404(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('tiktok.test'));

        $response->assertStatus(404);
        $response->assertJson([
            'connected' => false,
        ]);
    }

    /**
     * Test: Test diagnostic endpoint when token is expired returns 401
     */
    public function test_test_endpoint_when_token_expired_returns_401(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_expired',
            'username'            => 'TVRI Aceh Expired',
            'access_token'        => 'expired_token',
            'expires_at'          => Carbon::now()->subHours(2),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test'));

        $response->assertStatus(401);
        $response->assertJson([
            'connected'  => true,
            'is_expired' => true,
        ]);
    }

    /**
     * Test 16: Test diagnostic endpoint when connected and token is valid
     * Endpoint mengambil profil dasar dan statistik via scope user.info.basic dan user.info.stats.
     */
    public function test_test_endpoint_when_connected_returns_live_stats(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_tvri_live',
            'username'            => 'TVRI Aceh Live',
            'access_token'        => 'valid_token_test_abc',
            'expires_at'          => Carbon::now()->addHours(20),
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data' => [
                    'user' => [
                        'open_id'         => 'open_id_tvri_live',
                        'union_id'        => 'union_id_tvri_live',
                        'avatar_url'      => 'https://p16-va.tiktokcdn.com/avatar.jpeg',
                        'display_name'    => 'TVRI Aceh Official',
                        'follower_count'  => 12500,
                        'following_count' => 45,
                        'likes_count'     => 150000,
                        'video_count'     => 959,
                    ],
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test'));

        $response->assertOk();
        $response->assertJson([
            'connected' => true,
            'status'    => 'ok',
            'account'   => [
                'username'            => 'TVRI Aceh Live',
                'external_account_id' => 'open_id_tvri_live',
            ],
            'live_profile' => [
                'open_id'      => 'open_id_tvri_live',
                'display_name' => 'TVRI Aceh Official',
            ],
            'live_stats' => [
                'follower_count'  => 12500,
                'following_count' => 45,
                'likes_count'     => 150000,
                'video_count'     => 959,
            ],
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('stats_note', $data);
        $this->assertStringContainsString('user.info.stats', $data['stats_note']);
        $this->assertArrayHasKey('live_stats', $data);
        $this->assertEquals(12500, $data['live_stats']['follower_count']);
        $this->assertEquals(959, $data['live_stats']['video_count']);

        // Ensure token is NOT present in json response
        $this->assertStringNotContainsString('valid_token_test_abc', $response->getContent());
    }

    /**
     * Test 17: TikTok API failure during test diagnostic endpoint is handled gracefully
     */
    public function test_test_endpoint_api_failure_is_handled(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_tvri_err',
            'username'            => 'TVRI Aceh Err',
            'access_token'        => 'valid_token_err',
            'expires_at'          => Carbon::now()->addHours(20),
        ]);

        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'error' => [
                    'code'    => 'internal_error',
                    'message' => 'TikTok internal server error',
                ],
            ], 500),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('tiktok.test'));

        $response->assertStatus(500);
        $response->assertJson([
            'connected' => true,
            'status'    => 'error',
        ]);
    }

    /**
     * Test 18: Malformed OAuth response is handled defensively
     */
    public function test_malformed_oauth_response_handled_defensively(): void
    {
        $state = 'valid_state_malformed';

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'unexpected_format' => true,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', [
                'code'  => 'auth_code_malformed',
                'state' => $state,
            ]));

        $response->assertRedirect(route('tiktok.status'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Gagal menukarkan kode otorisasi', session('error'));
    }
}
