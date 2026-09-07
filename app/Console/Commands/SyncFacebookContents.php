<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use App\Services\FacebookService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFacebookContents extends Command
{
    protected $signature = 'facebook:sync';
    protected $description = 'Sync semua postingan dari Facebook Page TVRI + simpan snapshot statistik hari ini';

    public function handle(FacebookService $facebook): int
    {
        $this->info('Facebook Sync Started');
        $this->line('');

        $platform = Platform::firstOrCreate(
            ['slug' => 'facebook'],
            ['nama' => 'Facebook']
        );

        // 1. Resolve Profil Facebook Page Live
        try {
            $page = $facebook->getPage();
        } catch (\Throwable $e) {
            $this->error('Facebook Sync FAILED');
            $this->line('');
            $this->line('Fetch complete: NO');
            $this->line('');
            $this->line('Marked inactive: 0');
            $this->line('Database rows deleted: 0');
            $this->line('Snapshots deleted: 0');
            $this->line('');
            $this->error('Gagal mengambil profil Facebook Page: ' . $e->getMessage());
            Log::error('Facebook Sync: Gagal resolve Page', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $pageName = $page['name'] ?? 'Facebook Page';
        $followers = $page['followers_count'];

        $this->info("Page: {$pageName}");
        $this->info('Followers: ' . ($followers !== null ? $followers : 'NULL'));

        // 2. Fetch Seluruh Postingan dengan Validasi Pagination Complete
        $fetchComplete = false;
        $postsList = [];

        try {
            $fetchResult = $facebook->getAllNormalizedPostsWithStatus(10, true);
            $postsList = $fetchResult['data'] ?? [];
            $fetchComplete = (bool) ($fetchResult['is_complete'] ?? false);
        } catch (\Throwable $e) {
            $this->error('Facebook Sync FAILED');
            $this->line('');
            $this->line('Fetch complete: NO');
            $this->line('');
            $this->line('Marked inactive: 0');
            $this->line('Database rows deleted: 0');
            $this->line('Snapshots deleted: 0');
            $this->line('');
            $this->error('Gagal mengambil postingan dari Facebook API: ' . $e->getMessage());
            Log::error('Facebook Sync: Gagal fetch posts', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        if (! $fetchComplete) {
            $this->error('Facebook Sync FAILED');
            $this->line('');
            $this->line('Fetch complete: NO');
            $this->line('');
            $this->line('Marked inactive: 0');
            $this->line('Database rows deleted: 0');
            $this->line('Snapshots deleted: 0');
            $this->line('');
            $this->error('Fetch Facebook posts tidak lengkap (partial/gagal). Sinkronisasi dibatalkan demi keamanan data.');
            return self::FAILURE;
        }

        $postsCount = count($postsList);
        $this->info("Total posts live: {$postsCount}");
        $this->line('');
        $this->info("Media fetched: {$postsCount}");
        $this->info('Fetch complete: ' . ($fetchComplete ? 'YES' : 'NO'));
        $this->line('');

        $today = Carbon::today()->toDateString();

        $contentsInserted = 0;
        $contentsUpdated = 0;
        $reactivatedCount = 0;
        $snapshotsInserted = 0;
        $snapshotsSkipped = 0;
        $errorsCount = 0;
        $markedInactive = 0;

        // 3. Upsert Konten & Simpan Daily Content Snapshot
        foreach ($postsList as $item) {
            try {
                DB::beginTransaction();

                // 3a. SINKRONISASI KONTEN (Tabel contents)
                $content = Content::where('platform_id', $platform->id)
                    ->where('content_id_external', $item['external_id'])
                    ->first();

                if (! $content) {
                    $content = Content::create([
                        'platform_id'         => $platform->id,
                        'content_id_external' => $item['external_id'],
                        'judul'               => $item['title'],
                        'url'                 => $item['url'],
                        'thumbnail_url'       => $item['thumbnail_url'],
                        'tanggal_upload'      => $item['upload_date'],
                        'status'              => 'aktif',
                    ]);
                    $contentsInserted++;
                } else {
                    $wasInactive = ($content->status !== 'aktif');
                    if ($wasInactive) {
                        $reactivatedCount++;
                    }

                    $content->update([
                        'judul'          => $item['title'],
                        'url'            => $item['url'],
                        'thumbnail_url'  => $item['thumbnail_url'],
                        'tanggal_upload' => $item['upload_date'],
                        'status'         => 'aktif',
                    ]);
                    $contentsUpdated++;
                }

                // 3b. SNAPSHOT HARIAN KONTEN (Tabel content_stats_daily)
                // ATURAN WAJIB: SNAPSHOT YANG SUDAH ADA TIDAK BOLEH DI-UPDATE / OVERWRITE
                $snapshotExists = ContentStatsDaily::where('content_id', $content->id)
                    ->where('tanggal', $today)
                    ->exists();

                if (! $snapshotExists) {
                    ContentStatsDaily::create([
                        'content_id' => $content->id,
                        'tanggal'    => $today,
                        'views'      => $item['views'],
                        'likes'      => $item['likes'],
                        'comments'   => $item['comments'],
                    ]);
                    $snapshotsInserted++;
                } else {
                    $snapshotsSkipped++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errorsCount++;
                Log::error('Facebook Sync Error pada item', [
                    'external_id' => $item['external_id'] ?? null,
                    'error'       => $e->getMessage(),
                ]);
                $this->warn("Error syncing post ID {$item['external_id']}: {$e->getMessage()}");
            }
        }

        // 4. SNAPSHOT STATISTIK AKUN HARIAN (Tabel platform_stats_daily)
        // ATURAN WAJIB: SNAPSHOT YANG SUDAH ADA TIDAK BOLEH DI-UPDATE / OVERWRITE
        $accountSnapshotExists = PlatformStatsDaily::where('platform_id', $platform->id)
            ->where('tanggal', $today)
            ->exists();

        $accountSnapshotInserted = 0;
        $accountSnapshotSkipped = 0;

        if (! $accountSnapshotExists) {
            $socialAccount = SocialAccount::where('platform_id', $platform->id)->first();
            if (! $socialAccount) {
                $socialAccount = SocialAccount::whereNotNull('page_id')->first();
            }

            PlatformStatsDaily::create([
                'platform_id'       => $platform->id,
                'social_account_id' => $socialAccount?->id,
                'tanggal'           => $today,
                'followers'         => $followers,
                'total_contents'    => count($postsList),
                'total_views'       => null,
            ]);
            $accountSnapshotInserted = 1;
        } else {
            $accountSnapshotSkipped = 1;
        }

        // 5. DETEKSI KONTEN DIHAPUS / HILANG DARI FACEBOOK
        // ATURAN WAJIB: Hanya dieksekusi jika fetch post dari API benar-benar COMPLETE
        if ($fetchComplete) {
            $foundExternalIds = collect($postsList)->pluck('external_id')->filter()->toArray();

            $markedInactive = Content::where('platform_id', $platform->id)
                ->where('status', 'aktif')
                ->whereNotIn('content_id_external', $foundExternalIds)
                ->update(['status' => 'nonaktif']);
        }

        // 6. Ringkasan Eksekusi Command
        $this->info("Contents inserted: {$contentsInserted}");
        $this->info("Contents updated: {$contentsUpdated}");
        $this->line('');
        $this->info("Content snapshots inserted: {$snapshotsInserted}");
        $this->info("Content snapshots skipped: {$snapshotsSkipped}");
        $this->line('');
        $this->info("Account snapshot inserted: {$accountSnapshotInserted}");
        $this->info("Account snapshot skipped: {$accountSnapshotSkipped}");
        $this->line('');
        $this->info("Marked inactive: {$markedInactive}");
        $this->info("Reactivated: {$reactivatedCount}");
        $this->line('');
        $this->info("Database rows deleted: 0");
        $this->info("Snapshots deleted: 0");
        $this->info("Errors: {$errorsCount}");
        $this->line('');
        $this->info('Facebook Sync Completed');

        return self::SUCCESS;
    }
}
