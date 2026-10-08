<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\TikTokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test suite untuk TikTok Refresh Token Lifecycle.
 *
 * Mencakup:
 * - Authorization code exchange menyimpan refresh_token
 * - expires_at & refresh_expires_at dihitung dari expires_in & refresh_expires_in
 * - access_token valid tidak melakukan refresh
 * - access_token hampir expired melakukan refresh
 * - access_token expired melakukan refresh
 * - Refresh berhasil mengganti access_token
 * - Refresh berhasil dengan refresh_token baru
 * - Refresh gagal tidak menghapus account
 * - Refresh token tidak muncul di response/view/log
 * - TikTok sync menggunakan token hasil refresh
 * - Token lama tanpa refresh_token memberikan error informatif
 */
class TikTokRefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Platform $tiktokPlatform;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.tiktok.client_key', 'test_client_key_refresh');
        Config::set('services.tiktok.client_secret', 'test_client_secret_refresh');
        Config::set('services.tiktok.redirect_uri', 'http://localhost/tiktok/callback');

        $this->user = User::factory()->create(['email' => 'refresh_tester@tvri.co.id']);

        $this->tiktokPlatform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );
    }

    // =========================================================================
    // TEST 1: OAuth Exchange menyimpan refresh_token
    // =========================================================================

    /**
     * Test 1: Authorization code exchange menyimpan refresh_token dan refresh_expires_at.
     */
    public function test_oauth_callback_stores_refresh_token_and_refresh_expires_at(): void
    {
        $state = 'valid_state_refresh_001';

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_fresh_token_abc',
                'expires_in'         => 86400,
                'open_id'            => 'open_id_refresh_test',
                'refresh_token'      => 'rft_secret_refresh_xyz',
                'refresh_expires_in' => 31536000, // 1 tahun
                'scope'              => 'user.info.basic,video.list',
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['open_id' => 'open_id_refresh_test', 'display_name' => 'TVRI Test']],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', ['code' => 'auth_code_001', 'state' => $state]));

        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();
        $this->assertNotNull($account);

        // refresh_token harus tersedia dan terdekripsi dengan benar
        $this->assertNotNull($account->refresh_token);
        $this->assertEquals('rft_secret_refresh_xyz', $account->refresh_token);

        // refresh_expires_at harus ada dan di masa depan
        $this->assertNotNull($account->refresh_expires_at);
        $this->assertTrue($account->refresh_expires_at->isFuture());

        // refresh_token harus terenkripsi di database (bukan plaintext)
        $rawAccount = DB::table('social_accounts')->where('id', $account->id)->first();
        $this->assertNotEquals('rft_secret_refresh_xyz', $rawAccount->refresh_token);
        $this->assertEquals('rft_secret_refresh_xyz', Crypt::decryptString($rawAccount->refresh_token));
    }

    // =========================================================================
    // TEST 2: expires_at dihitung dari expires_in
    // =========================================================================

    /**
     * Test 2: expires_at dihitung dengan benar dari expires_in.
     */
    public function test_oauth_callback_calculates_expires_at_from_expires_in(): void
    {
        $state = 'valid_state_expiry_calc';

        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 0, 0));

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_expiry_calc',
                'expires_in'         => 7200, // 2 jam
                'open_id'            => 'open_id_expiry',
                'refresh_token'      => 'rft_expiry_calc',
                'refresh_expires_in' => 3600 * 24 * 365, // 1 tahun
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['open_id' => 'open_id_expiry', 'display_name' => 'TVRI Expiry Test']],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', ['code' => 'auth_code_expiry', 'state' => $state]));

        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();

        // expires_at = now (12:00:00) + 7200 detik = 14:00:00
        $expectedExpiresAt = Carbon::create(2026, 10, 5, 14, 0, 0);
        $this->assertTrue($account->expires_at->eq($expectedExpiresAt));

        Carbon::setTestNow(); // reset
    }

    // =========================================================================
    // TEST 3: refresh_expires_at dihitung dari refresh_expires_in
    // =========================================================================

    /**
     * Test 3: refresh_expires_at dihitung dengan benar dari refresh_expires_in.
     */
    public function test_oauth_callback_calculates_refresh_expires_at_from_refresh_expires_in(): void
    {
        $state = 'valid_state_refresh_expiry';

        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 0, 0));

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_refresh_expiry',
                'expires_in'         => 86400,
                'open_id'            => 'open_id_rexpiry',
                'refresh_token'      => 'rft_refresh_expiry',
                'refresh_expires_in' => 86400 * 365, // 1 tahun dalam detik
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['open_id' => 'open_id_rexpiry', 'display_name' => 'Refresh Expiry Test']],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->withSession(['tiktok_oauth_state' => $state])
            ->get(route('tiktok.callback', ['code' => 'auth_code_rexpiry', 'state' => $state]));

        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();

        // refresh_expires_at = now + 365 hari
        $expectedRefreshExpiry = Carbon::create(2026, 10, 5, 12, 0, 0)->addSeconds(86400 * 365);
        $this->assertTrue($account->refresh_expires_at->eq($expectedRefreshExpiry));
        $this->assertTrue($account->refresh_expires_at->isFuture());

        Carbon::setTestNow(); // reset
    }

    // =========================================================================
    // TEST 4: access_token valid tidak melakukan refresh
    // =========================================================================

    /**
     * Test 4: tiktok:sync dengan access_token masih valid (tidak hampir expired) → tidak melakukan refresh.
     */
    public function test_sync_does_not_refresh_when_token_is_still_valid(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_still_valid',
            'username'            => 'TVRI Valid Token',
            'access_token'        => 'act_still_valid_9999',
            'refresh_token'       => 'rft_still_valid_xxxx',
            'expires_at'          => Carbon::now()->addHours(3), // 3 jam ke depan (> 15 menit buffer)
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'error' => 'should_not_be_called',
            ], 400),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 5000, 'video_count' => 10]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data'  => ['videos' => [], 'has_more' => false],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(0);

        // Token endpoint tidak boleh dipanggil
        Http::assertNotSent(fn ($request) => $request->url() === TikTokService::TOKEN_URL);
    }

    // =========================================================================
    // TEST 5: access_token hampir expired melakukan refresh
    // =========================================================================

    /**
     * Test 5: tiktok:sync dengan access_token hampir expired (< 15 menit) → auto-refresh dilakukan.
     */
    public function test_sync_refreshes_token_when_almost_expired(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_almost_expired',
            'username'            => 'TVRI Almost Expired',
            'access_token'        => 'act_old_almost_expired',
            'refresh_token'       => 'rft_valid_for_refresh',
            'expires_at'          => Carbon::now()->addMinutes(5), // hampir expired
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_refreshed_new_token',
                'expires_in'         => 86400,
                'refresh_token'      => 'rft_new_after_refresh',
                'refresh_expires_in' => 31536000,
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 5000, 'video_count' => 1]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data'  => ['videos' => [], 'has_more' => false],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('[TIKTOK SYNC] Access token berhasil diperbarui via refresh.')
            ->assertExitCode(0);

        // Token di DB harus diperbarui
        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();
        $this->assertEquals('act_refreshed_new_token', $account->access_token);
    }

    // =========================================================================
    // TEST 6: access_token expired melakukan refresh
    // =========================================================================

    /**
     * Test 6: tiktok:sync dengan access_token yang sudah expired → auto-refresh dilakukan.
     */
    public function test_sync_refreshes_token_when_expired(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_expired_rft',
            'username'            => 'TVRI Token Expired',
            'access_token'        => 'act_old_expired_token',
            'refresh_token'       => 'rft_valid_for_expired',
            'expires_at'          => Carbon::now()->subHours(2), // sudah expired
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_after_expired_refresh',
                'expires_in'         => 86400,
                'refresh_token'      => null, // TikTok tidak selalu mengembalikan refresh_token baru
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 8000, 'video_count' => 2]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data'  => ['videos' => [], 'has_more' => false],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('[TIKTOK SYNC] Access token berhasil diperbarui via refresh.')
            ->assertExitCode(0);

        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();
        $this->assertEquals('act_after_expired_refresh', $account->access_token);
        $this->assertTrue($account->expires_at->isFuture());
    }

    // =========================================================================
    // TEST 7: Refresh berhasil mengganti access_token di database
    // =========================================================================

    /**
     * Test 7: refreshAccessToken() service method berhasil memperbarui access_token di DB.
     */
    public function test_refresh_access_token_service_updates_db(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_service_refresh',
            'username'            => 'TVRI Service Refresh',
            'access_token'        => 'act_old_for_service_test',
            'refresh_token'       => 'rft_for_service_test',
            'expires_at'          => Carbon::now()->subHours(1),
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_new_from_service_refresh',
                'expires_in'         => 86400,
                'refresh_token'      => 'rft_new_from_service_refresh',
                'refresh_expires_in' => 31536000,
                'token_type'         => 'Bearer',
            ], 200),
        ]);

        $service = new TikTokService();
        $refreshedAccount = $service->refreshAccessToken($account);

        $this->assertEquals('act_new_from_service_refresh', $refreshedAccount->access_token);
        $this->assertTrue($refreshedAccount->expires_at->isFuture());

        // Pastikan tersimpan di DB
        $fromDb = SocialAccount::find($account->id);
        $this->assertEquals('act_new_from_service_refresh', $fromDb->access_token);
    }

    // =========================================================================
    // TEST 8: Refresh dengan refresh_token baru → token lama diganti
    // =========================================================================

    /**
     * Test 8: Saat TikTok mengembalikan refresh_token baru, refresh_token lama diganti di DB.
     */
    public function test_refresh_stores_new_refresh_token_when_returned_by_api(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_new_rft',
            'username'            => 'TVRI New Refresh Token',
            'access_token'        => 'act_old_for_new_rft',
            'refresh_token'       => 'rft_old_will_be_replaced',
            'expires_at'          => Carbon::now()->subHours(1),
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_brand_new',
                'expires_in'         => 86400,
                'refresh_token'      => 'rft_brand_new_replacement', // TikTok mengembalikan refresh_token baru
                'refresh_expires_in' => 31536000,
                'token_type'         => 'Bearer',
            ], 200),
        ]);

        $service = new TikTokService();
        $refreshedAccount = $service->refreshAccessToken($account);

        // refresh_token harus diperbarui ke nilai baru
        $this->assertEquals('rft_brand_new_replacement', $refreshedAccount->refresh_token);
        $this->assertTrue($refreshedAccount->refresh_expires_at->isFuture());

        // Pastikan enkripsi di DB (bukan plaintext)
        $raw = DB::table('social_accounts')->where('id', $account->id)->first();
        $this->assertNotEquals('rft_brand_new_replacement', $raw->refresh_token);
        $this->assertEquals('rft_brand_new_replacement', Crypt::decryptString($raw->refresh_token));
    }

    // =========================================================================
    // TEST 9: Refresh gagal tidak menghapus account, content, atau snapshot
    // =========================================================================

    /**
     * Test 9: Jika refresh token gagal (misal invalid_grant), account dan data konten TIDAK dihapus.
     */
    public function test_failed_refresh_does_not_delete_account_or_content(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_fail_refresh',
            'username'            => 'TVRI Fail Refresh',
            'access_token'        => 'act_old_invalid',
            'refresh_token'       => 'rft_old_invalid',
            'expires_at'          => Carbon::now()->subHours(2),
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        // Buat konten dan snapshot historis yang harus tetap aman
        $content = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'VID_MUST_SURVIVE_FAIL',
            'judul'               => 'Video Harus Selamat',
            'url'                 => 'https://tiktok.com/survive',
            'status'              => 'aktif',
        ]);

        ContentStatsDaily::create([
            'content_id' => $content->id,
            'tanggal'    => '2026-09-01',
            'views'      => 9999,
            'likes'      => 100,
            'comments'   => 10,
        ]);

        PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $account->id,
            'tanggal'           => '2026-09-01',
            'followers'         => 5000,
            'total_contents'    => 1,
        ]);

        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'Refresh token has been revoked',
                'log_id'            => 'log_fail_001',
            ], 400),
        ]);

        $this->artisan('tiktok:sync')->assertExitCode(1);

        // Account TIDAK dihapus
        $this->assertDatabaseHas('social_accounts', ['id' => $account->id]);
        $this->assertEquals(1, SocialAccount::where('platform_id', $this->tiktokPlatform->id)->count());

        // Konten TIDAK dihapus
        $this->assertDatabaseHas('contents', ['id' => $content->id, 'status' => 'aktif']);

        // Snapshot TIDAK dihapus
        $this->assertDatabaseHas('content_stats_daily', ['content_id' => $content->id, 'views' => 9999]);
        $this->assertDatabaseHas('platform_stats_daily', ['followers' => 5000]);
    }

    // =========================================================================
    // TEST 10: refresh_token tidak muncul di response/view/log
    // =========================================================================

    /**
     * Test 10: Status endpoint dan diagnostic endpoint tidak mengekspos refresh_token.
     */
    public function test_refresh_token_not_exposed_in_status_or_diagnostic_endpoints(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_no_expose',
            'username'            => 'TVRI No Expose',
            'access_token'        => 'act_no_expose_secret',
            'refresh_token'       => 'rft_no_expose_secret',
            'expires_at'          => Carbon::now()->addHours(20),
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        // Status endpoint
        $statusResponse = $this->actingAs($this->user)->getJson(route('tiktok.status'));
        $statusResponse->assertOk();
        $statusContent = $statusResponse->getContent();
        $this->assertStringNotContainsString('act_no_expose_secret', $statusContent);
        $this->assertStringNotContainsString('rft_no_expose_secret', $statusContent);
        $statusResponse->assertJsonMissing(['access_token', 'refresh_token', 'client_secret']);

        // Diagnostic test endpoint
        Http::fake([
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['open_id' => 'open_id_no_expose', 'display_name' => 'No Expose']],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $testResponse = $this->actingAs($this->user)->getJson(route('tiktok.test'));
        $testResponse->assertOk();
        $testContent = $testResponse->getContent();
        $this->assertStringNotContainsString('act_no_expose_secret', $testContent);
        $this->assertStringNotContainsString('rft_no_expose_secret', $testContent);
        $testResponse->assertJsonMissing(['access_token', 'refresh_token']);
    }

    // =========================================================================
    // TEST 11: TikTok sync menggunakan token hasil refresh
    // =========================================================================

    /**
     * Test 11: tiktok:sync menggunakan token baru setelah refresh untuk memanggil API.
     */
    public function test_sync_uses_refreshed_token_for_api_calls(): void
    {
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_use_refreshed',
            'username'            => 'TVRI Use Refreshed Token',
            'access_token'        => 'act_expired_old_token',
            'refresh_token'       => 'rft_valid_for_new_call',
            'expires_at'          => Carbon::now()->subHours(1), // expired
            'refresh_expires_at'  => Carbon::now()->addDays(360),
        ]);

        // Token refresh berhasil menghasilkan token baru
        // Kemudian API call berikutnya menggunakan token baru tersebut
        Http::fake([
            TikTokService::TOKEN_URL => Http::response([
                'access_token'       => 'act_newly_refreshed_for_sync',
                'expires_in'         => 86400,
                'refresh_token'      => 'rft_updated_after_sync',
                'refresh_expires_in' => 31536000,
                'token_type'         => 'Bearer',
            ], 200),
            TikTokService::API_BASE_URL . '/user/info/*' => Http::response([
                'data'  => ['user' => ['follower_count' => 12000, 'video_count' => 5]],
                'error' => ['code' => 'ok'],
            ], 200),
            TikTokService::API_BASE_URL . '/video/list/*' => Http::response([
                'data' => [
                    'videos' => [
                        ['id' => 'VID_AFTER_REFRESH', 'title' => 'Video After Refresh', 'view_count' => 100],
                    ],
                    'cursor'   => null,
                    'has_more' => false,
                ],
                'error' => ['code' => 'ok'],
            ], 200),
        ]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('[TIKTOK SYNC] Access token berhasil diperbarui via refresh.')
            ->expectsOutputToContain('TikTok Sync Completed')
            ->assertExitCode(0);

        // Konten berhasil disimpan — membuktikan sync jalan dengan token baru
        $this->assertDatabaseHas('contents', [
            'content_id_external' => 'VID_AFTER_REFRESH',
            'platform_id'         => $this->tiktokPlatform->id,
        ]);
    }

    // =========================================================================
    // TEST 12: Token lama tanpa refresh_token memberikan error informatif
    // =========================================================================

    /**
     * Test 12: Akun TikTok lama yang tidak memiliki refresh_token menghasilkan
     * error yang informatif (bukan exception mentah / crash).
     */
    public function test_old_token_without_refresh_token_gives_informative_error(): void
    {
        // Akun lama: tidak ada refresh_token (misal token dari sebelum fitur ini)
        SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_old_no_refresh',
            'username'            => 'TVRI Old No Refresh',
            'access_token'        => 'act_old_expired_no_refresh',
            'refresh_token'       => null, // tidak ada refresh_token
            'expires_at'          => Carbon::now()->subDays(2), // sudah lama expired
            'refresh_expires_at'  => null,
        ]);

        // HTTP tidak perlu dipanggil — error terjadi sebelum API call
        Http::fake([]);

        $this->artisan('tiktok:sync')
            ->expectsOutputToContain('[TIKTOK SYNC FAILED] Refresh token belum tersedia')
            ->assertExitCode(1);

        // Pastikan account tidak dihapus
        $this->assertEquals(1, SocialAccount::where('platform_id', $this->tiktokPlatform->id)->count());
    }

    // =========================================================================
    // HELPER TESTS: SocialAccount model helpers
    // =========================================================================

    /**
     * Test helper: isAccessTokenExpiredOrExpiring() benar untuk token valid.
     */
    public function test_helper_is_not_expiring_when_token_valid(): void
    {
        $account = new SocialAccount([
            'expires_at' => Carbon::now()->addHours(3),
        ]);

        $this->assertFalse($account->isAccessTokenExpiredOrExpiring(15));
    }

    /**
     * Test helper: isAccessTokenExpiredOrExpiring() benar untuk token hampir expired.
     */
    public function test_helper_is_expiring_when_within_buffer(): void
    {
        $account = new SocialAccount([
            'expires_at' => Carbon::now()->addMinutes(10), // dalam 10 menit
        ]);

        $this->assertTrue($account->isAccessTokenExpiredOrExpiring(15)); // buffer 15 menit
    }

    /**
     * Test helper: isAccessTokenExpiredOrExpiring() benar untuk token sudah expired.
     */
    public function test_helper_is_expired_when_past(): void
    {
        $account = new SocialAccount([
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        $this->assertTrue($account->isAccessTokenExpiredOrExpiring(15));
    }

    /**
     * Test helper: hasValidRefreshToken() false jika tidak ada refresh_token.
     */
    public function test_helper_no_valid_refresh_token_when_null(): void
    {
        $account = new SocialAccount([
            'refresh_token'      => null,
            'refresh_expires_at' => null,
        ]);

        // Manipulasi langsung attribute karena mutator akan encrypt null jadi null
        $account->setRawAttributes(['refresh_token' => null]);
        $this->assertFalse($account->hasValidRefreshToken());
    }

    /**
     * Test helper: hasValidRefreshToken() false jika refresh_token sudah expired.
     */
    public function test_helper_no_valid_refresh_token_when_refresh_expired(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_rft_expired',
            'username'            => 'Test Expired RFT',
            'access_token'        => 'act_any',
            'refresh_token'       => 'rft_expired_value',
            'expires_at'          => Carbon::now()->addDays(1),
            'refresh_expires_at'  => Carbon::now()->subDays(1), // refresh expired kemarin
        ]);

        $this->assertFalse($account->hasValidRefreshToken());
    }

    /**
     * Test helper: hasValidRefreshToken() true jika refresh_token ada dan belum expired.
     */
    public function test_helper_has_valid_refresh_token_when_not_expired(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_rft_valid',
            'username'            => 'Test Valid RFT',
            'access_token'        => 'act_any_2',
            'refresh_token'       => 'rft_valid_value',
            'expires_at'          => Carbon::now()->addDays(1),
            'refresh_expires_at'  => Carbon::now()->addDays(360), // masih jauh
        ]);

        $this->assertTrue($account->hasValidRefreshToken());
    }

    /**
     * Test: refreshAccessToken() service melempar exception jika refresh_token tidak ada.
     */
    public function test_refresh_access_token_throws_when_no_refresh_token(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_no_rft_service',
            'username'            => 'No Refresh Token',
            'access_token'        => 'act_any_3',
            'refresh_token'       => null,
            'expires_at'          => Carbon::now()->subHours(1),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refresh token belum tersedia');

        $service = new TikTokService();
        $service->refreshAccessToken($account);
    }

    /**
     * Test: refreshAccessToken() service melempar exception jika refresh_token sudah expired.
     */
    public function test_refresh_access_token_throws_when_refresh_token_expired(): void
    {
        $account = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'open_id_rft_expired_svc',
            'username'            => 'Expired Refresh Token',
            'access_token'        => 'act_any_4',
            'refresh_token'       => 'rft_is_expired',
            'expires_at'          => Carbon::now()->subHours(2),
            'refresh_expires_at'  => Carbon::now()->subDays(10), // sudah expired
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refresh token TikTok sudah kedaluwarsa');

        Http::fake([]);

        $service = new TikTokService();
        $service->refreshAccessToken($account);
    }
}
