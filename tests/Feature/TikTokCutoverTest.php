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

class TikTokCutoverTest extends TestCase
{
    use RefreshDatabase;

    protected Platform $tiktokPlatform;
    protected Platform $youtubePlatform;
    protected Platform $instagramPlatform;
    protected SocialAccount $tiktokAccount;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'admin_cutover@tvri.co.id']);

        $this->tiktokPlatform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        $this->youtubePlatform = Platform::firstOrCreate(
            ['slug' => 'youtube'],
            ['nama' => 'YouTube']
        );

        $this->instagramPlatform = Platform::firstOrCreate(
            ['slug' => 'instagram'],
            ['nama' => 'Instagram']
        );

        // Akun TikTok testing
        $this->tiktokAccount = SocialAccount::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'external_account_id' => 'test_open_id_12345',
            'username'            => 'projecttest5',
            'access_token'        => 'act_test_token_xyz',
            'refresh_token'       => 'rft_test_refresh_abc',
            'connected_by'        => $this->user->id,
            'expires_at'          => Carbon::now()->subDays(5),
        ]);

        // Konten TikTok testing (1 aktif, 1 dihapus)
        $contentAktif = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'TT_VID_AKTIF_01',
            'judul'               => 'TikTok Testing Aktif',
            'url'                 => 'https://tiktok.com/@test/video/01',
            'status'              => 'aktif',
            'tanggal_upload'      => Carbon::now()->subDays(10),
        ]);

        $contentDihapus = Content::create([
            'platform_id'         => $this->tiktokPlatform->id,
            'content_id_external' => 'TT_VID_DEL_02',
            'judul'               => 'TikTok Testing Dihapus',
            'url'                 => 'https://tiktok.com/@test/video/02',
            'status'              => 'dihapus',
            'tanggal_upload'      => Carbon::now()->subDays(15),
        ]);

        // Snapshots konten TikTok
        ContentStatsDaily::create([
            'content_id' => $contentAktif->id,
            'tanggal'    => Carbon::now()->subDays(2)->toDateString(),
            'views'      => 150,
            'likes'      => 20,
            'comments'   => 5,
        ]);

        ContentStatsDaily::create([
            'content_id' => $contentAktif->id,
            'tanggal'    => Carbon::now()->subDays(1)->toDateString(),
            'views'      => 200,
            'likes'      => 25,
            'comments'   => 8,
        ]);

        ContentStatsDaily::create([
            'content_id' => $contentDihapus->id,
            'tanggal'    => Carbon::now()->subDays(12)->toDateString(),
            'views'      => 50,
            'likes'      => 5,
            'comments'   => 1,
        ]);

        // Snapshots akun TikTok
        PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->tiktokAccount->id,
            'tanggal'           => Carbon::now()->subDays(2)->toDateString(),
            'followers'         => 10,
            'total_contents'    => 2,
        ]);

        PlatformStatsDaily::create([
            'platform_id'       => $this->tiktokPlatform->id,
            'social_account_id' => $this->tiktokAccount->id,
            'tanggal'           => Carbon::now()->subDays(1)->toDateString(),
            'followers'         => 12,
            'total_contents'    => 2,
        ]);
    }

    /**
     * Test 1: Dry run default (tanpa opsi) tidak mengubah database.
     */
    public function test_dry_run_default_does_not_modify_database(): void
    {
        $this->artisan('tiktok:cutover')
            ->expectsOutputToContain('TIKTOK CUTOVER — DRY RUN')
            ->expectsOutputToContain('projecttest5')
            ->expectsOutputToContain('TikTok contents      : 2')
            ->expectsOutputToContain('Aktif            : 1')
            ->expectsOutputToContain('Nonaktif/dihapus : 1')
            ->expectsOutputToContain('Content stats daily  : 3')
            ->expectsOutputToContain('Platform stats daily : 2')
            ->expectsOutputToContain('Total record target  : 7 record')
            ->expectsOutputToContain('STATUS: NO DATABASE CHANGES MADE')
            ->expectsOutputToContain('Row social_accounts TIDAK AKAN DIHAPUS')
            ->expectsOutputToContain('Data YouTube, Instagram, dan Facebook TIDAK AKAN DISENTUH')
            ->expectsOutputToContain('php artisan tiktok:cutover --execute')
            ->assertExitCode(0);

        // Verifikasi database tidak ada perubahan sama sekali
        $this->assertEquals(2, Content::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertEquals(3, ContentStatsDaily::whereHas('content', fn ($q) => $q->where('platform_id', $this->tiktokPlatform->id))->count());
        $this->assertEquals(2, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertEquals(1, SocialAccount::where('platform_id', $this->tiktokPlatform->id)->count());
    }

    /**
     * Test 2: Dry run eksplisit (--dry-run) tidak mengubah database.
     */
    public function test_dry_run_flag_does_not_modify_database(): void
    {
        $this->artisan('tiktok:cutover --dry-run')
            ->expectsOutputToContain('TIKTOK CUTOVER — DRY RUN')
            ->expectsOutputToContain('STATUS: NO DATABASE CHANGES MADE')
            ->assertExitCode(0);

        $this->assertEquals(2, Content::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertEquals(3, ContentStatsDaily::whereHas('content', fn ($q) => $q->where('platform_id', $this->tiktokPlatform->id))->count());
        $this->assertEquals(2, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->count());
    }

    /**
     * Test 3: Execute menghapus contents dan stats TikTok.
     */
    public function test_execute_removes_tiktok_contents_and_stats(): void
    {
        $this->artisan('tiktok:cutover --execute')
            ->expectsOutputToContain('TIKTOK CUTOVER — EXECUTE')
            ->expectsOutputToContain('[1/4] Content stats removed   : 3')
            ->expectsOutputToContain('[2/4] Contents removed        : 2')
            ->expectsOutputToContain('[3/4] Platform stats removed  : 2')
            ->expectsOutputToContain('[4/4] Social account preserved : YES')
            ->expectsOutputToContain('CUTOVER SUCCESSFUL')
            ->assertExitCode(0);

        // Seluruh data konten & statistik TikTok terhapus bersih
        $this->assertEquals(0, Content::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertEquals(0, ContentStatsDaily::whereHas('content', fn ($q) => $q->where('platform_id', $this->tiktokPlatform->id))->count());
        $this->assertEquals(0, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->count());
    }

    /**
     * Test 4: Execute tidak menghapus social_accounts TikTok.
     */
    public function test_execute_preserves_social_account(): void
    {
        $this->artisan('tiktok:cutover --execute')->assertExitCode(0);

        // Akun TikTok di social_accounts tetap ada
        $account = SocialAccount::where('platform_id', $this->tiktokPlatform->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals('projecttest5', $account->username);
        $this->assertEquals('test_open_id_12345', $account->external_account_id);
    }

    /**
     * Test 5: Execute tidak menghapus data YouTube.
     */
    public function test_execute_does_not_delete_youtube_data(): void
    {
        // Fixture YouTube
        $ytContent = Content::create([
            'platform_id'         => $this->youtubePlatform->id,
            'content_id_external' => 'YT_VIDEO_ABC_999',
            'judul'               => 'YouTube Berita Aceh',
            'url'                 => 'https://youtube.com/watch?v=999',
            'status'              => 'aktif',
            'tanggal_upload'      => Carbon::now()->subDays(5),
        ]);

        ContentStatsDaily::create([
            'content_id' => $ytContent->id,
            'tanggal'    => Carbon::now()->subDays(1)->toDateString(),
            'views'      => 5000,
            'likes'      => 300,
            'comments'   => 50,
        ]);

        ChannelStatsDaily::create([
            'tanggal'          => Carbon::now()->subDays(1)->toDateString(),
            'subscriber_count' => 85000,
            'video_count'      => 450,
            'view_count'       => 12000000,
        ]);

        $this->artisan('tiktok:cutover --execute')->assertExitCode(0);

        // Data YouTube tetap 100% utuh
        $this->assertEquals(1, Content::where('platform_id', $this->youtubePlatform->id)->count());
        $this->assertEquals(1, ContentStatsDaily::where('content_id', $ytContent->id)->count());
        $this->assertEquals(1, ChannelStatsDaily::count());
    }

    /**
     * Test 6: Execute tidak menghapus data Instagram.
     */
    public function test_execute_does_not_delete_instagram_data(): void
    {
        // Fixture Instagram
        $igAccount = SocialAccount::create([
            'platform_id'         => $this->instagramPlatform->id,
            'external_account_id' => '17841414598432682',
            'username'            => 'tvriaceh_ig',
            'access_token'        => 'ig_token_123',
            'connected_by'        => $this->user->id,
        ]);

        $igContent = Content::create([
            'platform_id'         => $this->instagramPlatform->id,
            'content_id_external' => 'IG_POST_555',
            'judul'               => 'Instagram Post TVRI',
            'url'                 => 'https://instagram.com/p/555',
            'status'              => 'aktif',
            'tanggal_upload'      => Carbon::now()->subDays(3),
        ]);

        ContentStatsDaily::create([
            'content_id' => $igContent->id,
            'tanggal'    => Carbon::now()->subDays(1)->toDateString(),
            'views'      => null,
            'likes'      => 450,
            'comments'   => 30,
        ]);

        PlatformStatsDaily::create([
            'platform_id'       => $this->instagramPlatform->id,
            'social_account_id' => $igAccount->id,
            'tanggal'           => Carbon::now()->subDays(1)->toDateString(),
            'followers'         => 35000,
            'total_contents'    => 1,
        ]);

        $this->artisan('tiktok:cutover --execute')->assertExitCode(0);

        // Data Instagram tetap 100% utuh
        $this->assertEquals(1, SocialAccount::where('platform_id', $this->instagramPlatform->id)->count());
        $this->assertEquals(1, Content::where('platform_id', $this->instagramPlatform->id)->count());
        $this->assertEquals(1, ContentStatsDaily::where('content_id', $igContent->id)->count());
        $this->assertEquals(1, PlatformStatsDaily::where('platform_id', $this->instagramPlatform->id)->count());
    }

    /**
     * Test 7: Idempotensi — execute dapat dijalankan dua kali secara aman.
     */
    public function test_execute_is_idempotent_can_be_run_twice(): void
    {
        // Run pertama: membersihkan data
        $this->artisan('tiktok:cutover --execute')
            ->expectsOutputToContain('CUTOVER SUCCESSFUL')
            ->assertExitCode(0);

        // Run kedua: data sudah bersih, tetap berhasil dengan 0 record removed
        $this->artisan('tiktok:cutover --execute')
            ->expectsOutputToContain('Data analitik TikTok sudah dalam kondisi BERSIH (0 record ditemukan)')
            ->expectsOutputToContain('[1/4] Content stats removed  : 0')
            ->expectsOutputToContain('[2/4] Contents removed       : 0')
            ->expectsOutputToContain('[3/4] Platform stats removed : 0')
            ->expectsOutputToContain('[4/4] Social account preserved: YES')
            ->expectsOutputToContain('CUTOVER SUCCESSFUL (Data already clean)')
            ->assertExitCode(0);

        // Akun sosial tetap aman
        $this->assertEquals(1, SocialAccount::where('platform_id', $this->tiktokPlatform->id)->count());
    }

    /**
     * Test 8: Transaction rollback ketika terjadi error saat penghapusan.
     */
    public function test_execute_rolls_back_transaction_on_error(): void
    {
        // Pasang DB listener untuk mensimulasikan query error saat menghapus platform_stats_daily
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'platform_stats_daily') && str_contains($sql, 'delete')) {
                throw new \RuntimeException('Simulated Database Failure during cutover');
            }
        });

        $this->artisan('tiktok:cutover --execute')
            ->expectsOutputToContain('TIKTOK CUTOVER FAILED — TRANSACTION ROLLED BACK')
            ->expectsOutputToContain('Simulated Database Failure during cutover')
            ->assertExitCode(1);

        // Verifikasi bahwa rollback membatalkan seluruh operasi:
        // contents dan content_stats_daily TIDAK BOLEH terhapus sebagian!
        $this->assertEquals(2, Content::where('platform_id', $this->tiktokPlatform->id)->count());
        $this->assertEquals(3, ContentStatsDaily::whereHas('content', fn ($q) => $q->where('platform_id', $this->tiktokPlatform->id))->count());
        $this->assertEquals(2, PlatformStatsDaily::where('platform_id', $this->tiktokPlatform->id)->count());
    }
}
