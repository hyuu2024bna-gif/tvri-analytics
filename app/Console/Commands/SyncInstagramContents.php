<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use App\Services\InstagramService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncInstagramContents extends Command
{
    protected $signature = 'instagram:sync';
    protected $description = 'Sync semua postingan/media dari akun Instagram TVRI + simpan snapshot statistik hari ini';

    public function handle(InstagramService $instagram): int
    {
        $this->info('Instagram Sync Started');
        $this->line('');

        $platform = Platform::firstOrCreate(
            ['slug' => 'instagram'],
            ['nama' => 'Instagram']
        );

        try {
            $account = $instagram->getAccount();
        } catch (\Throwable $e) {
            $this->error('Gagal mengakses akun Instagram: ' . $e->getMessage());
            Log::error('Instagram Sync: Gagal resolve account', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $followers = isset($account['followers_count']) ? (int) $account['followers_count'] : 0;
        $totalPosts = isset($account['media_count']) ? (int) $account['media_count'] : 0;

        $this->info('Account: @' . ($account['username'] ?? '-'));
        $this->info("Followers: {$followers}");
        $this->info("Total posts: {$totalPosts}");
        $this->line('');

        $this->info('Mengambil daftar media dari Instagram API...');
        $fetchComplete = false;
        $mediaList = [];

        try {
            $fetchResult = $instagram->getAllNormalizedMediaWithStatus(10, true);
            $mediaList = $fetchResult['data'] ?? [];
            $fetchComplete = (bool) ($fetchResult['is_complete'] ?? false);
        } catch (\Throwable $e) {
            $this->error('Gagal mengambil media dari Instagram: ' . $e->getMessage());
            Log::error('Instagram Sync: Gagal fetch media', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $mediaCount = count($mediaList);
        $this->info("Media fetched: {$mediaCount}");
        $this->info("Fetch complete: " . ($fetchComplete ? 'YES' : 'NO'));
        $this->line('');

        $today = Carbon::today()->toDateString();

        $contentsInserted = 0;
        $contentsUpdated = 0;
        $reactivatedCount = 0;
        $snapshotsInserted = 0;
        $snapshotsSkipped = 0;
        $errorsCount = 0;
        $markedInactive = 0;

        foreach ($mediaList as $item) {
            try {
                DB::beginTransaction();

                // 1. SINKRONISASI KONTEN (Tabel contents)
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

                // 2. SNAPSHOT HARIAN KONTEN (Tabel content_stats_daily)
                // ATURAN WAJIB: SNAPSHOT YANG SUDAH ADA TIDAK BOLEH DI-UPDATE / OVERWRITE
                $snapshotExists = ContentStatsDaily::where('content_id', $content->id)
                    ->where('tanggal', $today)
                    ->exists();

                if (! $snapshotExists) {
                    ContentStatsDaily::create([
                        'content_id' => $content->id,
                        'tanggal'    => $today,
                        'views'      => $item['views'],
                        'likes'      => $item['likes'] ?? 0,
                        'comments'   => $item['comments'] ?? 0,
                    ]);
                    $snapshotsInserted++;
                } else {
                    $snapshotsSkipped++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errorsCount++;
                Log::error('Instagram Sync Error pada item', [
                    'external_id' => $item['external_id'] ?? null,
                    'error'       => $e->getMessage(),
                ]);
                $this->warn("Error syncing media ID {$item['external_id']}: {$e->getMessage()}");
            }
        }

        // 3. SNAPSHOT STATISTIK AKUN HARIAN (Tabel platform_stats_daily)
        // ATURAN WAJIB: SNAPSHOT YANG SUDAH ADA TIDAK BOLEH DI-UPDATE / OVERWRITE
        $accountSnapshotExists = PlatformStatsDaily::where('platform_id', $platform->id)
            ->where('tanggal', $today)
            ->exists();

        $accountSnapshotInserted = 0;
        $accountSnapshotSkipped = 0;

        if (! $accountSnapshotExists) {
            $socialAccount = SocialAccount::where('platform_id', $platform->id)->first();

            PlatformStatsDaily::create([
                'platform_id'       => $platform->id,
                'social_account_id' => $socialAccount?->id,
                'tanggal'           => $today,
                'followers'         => $followers,
                'total_contents'    => $totalPosts,
                'total_views'       => null,
            ]);
            $accountSnapshotInserted = 1;
        } else {
            $accountSnapshotSkipped = 1;
        }

        // 4. DETEKSI KONTEN DIHAPUS / HILANG DARI INSTAGRAM
        // ATURAN WAJIB: Hanya dieksekusi jika fetch media dari API benar-benar COMPLETE
        if ($fetchComplete) {
            $foundExternalIds = collect($mediaList)->pluck('external_id')->filter()->toArray();

            $markedInactive = Content::where('platform_id', $platform->id)
                ->where('status', 'aktif')
                ->whereNotIn('content_id_external', $foundExternalIds)
                ->update(['status' => 'nonaktif']);
        } else {
            $this->warn('Fetch tidak lengkap (partial/gagal). Deleted-content detection dilewati.');
        }

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
        $this->info('Instagram Sync Completed');

        return self::SUCCESS;
    }
}
