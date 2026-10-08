<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use App\Services\TikTokService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncTikTokContents extends Command
{
    /**
     * Nama dan signature console command.
     */
    protected $signature = 'tiktok:sync';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Sinkronisasi video dari akun TikTok TVRI + simpan immutable snapshot statistik harian';

    public function handle(TikTokService $tiktokService): int
    {
        $this->info('TikTok Sync Started');
        Log::info('[TIKTOK SYNC START] Memulai proses sinkronisasi TikTok');

        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        // 1. Validasi Akun TikTok pada tabel social_accounts
        $account = SocialAccount::where('platform_id', $platform->id)->first();

        if (! $account) {
            $msg = 'Belum ada akun TikTok yang terhubung. Silakan hubungkan akun melalui /tiktok/connect terlebih dahulu.';
            $this->error("[TIKTOK SYNC FAILED] {$msg}");
            Log::error('[TIKTOK SYNC FAILED] Akun TikTok belum terhubung di tabel social_accounts');

            return self::FAILURE;
        }

        // 2. Validasi Token dan Auto-Refresh
        if (empty($account->access_token)) {
            $msg = 'Access token TikTok tidak ditemukan pada database. Silakan hubungkan ulang akun di /tiktok/connect.';
            $this->error("[TIKTOK SYNC FAILED] {$msg}");
            Log::error('[TIKTOK SYNC FAILED] Access token TikTok kosong');

            return self::FAILURE;
        }

        // Cek apakah token perlu di-refresh (expired atau akan expired dalam 15 menit)
        if ($account->isAccessTokenExpiredOrExpiring(15)) {
            $this->warn('[TIKTOK SYNC] Access token expired atau hampir expired. Mencoba refresh...');

            if (! $account->hasValidRefreshToken()) {
                $reason = empty($account->refresh_token)
                    ? 'Refresh token belum tersedia (OAuth lama tanpa refresh token). Silakan hubungkan ulang akun di /tiktok/connect.'
                    : 'Refresh token sudah kedaluwarsa. Silakan hubungkan ulang akun di /tiktok/connect.';

                $this->error("[TIKTOK SYNC FAILED] {$reason}");
                Log::error('[TIKTOK SYNC FAILED] Tidak dapat melakukan refresh token TikTok', [
                    'has_refresh_token'   => ! empty($account->refresh_token),
                    'refresh_expires_at'  => $account->refresh_expires_at?->toIso8601String(),
                ]);

                return self::FAILURE;
            }

            try {
                $account = $tiktokService->refreshAccessToken($account);
                $this->info('[TIKTOK SYNC] Access token berhasil diperbarui via refresh.');
            } catch (\Throwable $e) {
                $errMsg = $e->getMessage();
                $this->error("[TIKTOK SYNC FAILED] Gagal refresh access token: {$errMsg}");
                Log::error('[TIKTOK SYNC FAILED] Gagal refresh access token TikTok', [
                    'error' => $errMsg,
                ]);

                return self::FAILURE;
            }
        }

        // 3. Ambil Statistik Profil Akun TikTok (graceful degradation jika scope terbatas)
        $this->info("Account: {$account->username}");
        $followers = null;
        $totalVideosFromApi = null;

        try {
            // Request basic + stats fields; jika scope user.info.stats tidak tersedia,
            // TikTok API akan mengembalikan 401 scope_not_authorized → tangkap & lanjut
            $allFields = array_merge(TikTokService::BASIC_INFO_FIELDS, TikTokService::STATS_FIELDS);
            $accountStats = $tiktokService->getAccountStats($account->access_token, $allFields);
            $followers = $accountStats['follower_count'] ?? null;
            $totalVideosFromApi = $accountStats['video_count'] ?? null;
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
            // Jika scope tidak diizinkan, lanjutkan tanpa stats (bukan fatal error)
            if (str_contains($errMsg, 'scope_not_authorized')) {
                $this->warn('[TIKTOK SYNC] Statistik akun tidak tersedia (scope user.info.stats belum dikonfigurasi). Sync video tetap dilanjutkan.');
                Log::warning('[TIKTOK SYNC] Stats TikTok tidak dapat diambil karena scope terbatas', [
                    'scope_error' => true,
                ]);
            } else {
                $this->error('[TIKTOK SYNC FAILED] Gagal mengambil data akun TikTok: ' . $errMsg);
                Log::error('[TIKTOK SYNC FAILED] Gagal mengambil data akun TikTok dari API', [
                    'error' => $errMsg,
                ]);

                return self::FAILURE;
            }
        }

        $this->info('Followers: ' . ($followers !== null ? $followers : '— (tidak tersedia)'));
        $this->info('Total posts: ' . ($totalVideosFromApi !== null ? $totalVideosFromApi : '— (tidak tersedia)'));
        $this->line('');

        // 4. Ambil Seluruh Daftar Video dari TikTok API (Cursor Pagination)
        $this->info('Mengambil daftar video dari TikTok API...');
        try {
            $fetchResult = $tiktokService->getAllNormalizedVideos($account->access_token, null, 20);
        } catch (\Throwable $e) {
            $this->error('[TIKTOK SYNC FAILED] Gagal mengambil video dari TikTok: ' . $e->getMessage());
            Log::error('[TIKTOK SYNC FAILED] Gagal fetch video TikTok', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $videoList = $fetchResult['data'] ?? [];
        $fetchComplete = (bool) ($fetchResult['is_complete'] ?? false);
        $mediaCount = count($videoList);

        $this->info("Media fetched: {$mediaCount}");
        $this->info('Fetch complete: ' . ($fetchComplete ? 'YES' : 'NO'));
        $this->line('');

        // Jika fetch tidak complete dan tidak ada video sama sekali, hentikan command dengan aman
        if (! $fetchComplete && $mediaCount === 0) {
            $this->error('[TIKTOK SYNC FAILED] Gagal mengambil daftar video dari TikTok API.');
            Log::error('[TIKTOK SYNC FAILED] Fetch video tidak lengkap dan data kosong.');

            return self::FAILURE;
        }

        $today = Carbon::today()->toDateString();

        $contentsInserted = 0;
        $contentsUpdated = 0;
        $reactivatedCount = 0;
        $snapshotsInserted = 0;
        $snapshotsSkipped = 0;
        $errorsCount = 0;
        $markedDeleted = 0;

        // 5. Sinkronisasi Video dan Simpan Daily Snapshot
        foreach ($videoList as $item) {
            try {
                DB::beginTransaction();

                // 5a. SINKRONISASI KONTEN (Tabel contents)
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

                    $updateData = [
                        'judul'  => $item['title'] ?: $content->judul,
                        'url'    => $item['url'] ?: $content->url,
                        'status' => 'aktif',
                    ];

                    if (! empty($item['thumbnail_url'])) {
                        $updateData['thumbnail_url'] = $item['thumbnail_url'];
                    }
                    if (! empty($item['upload_date'])) {
                        $updateData['tanggal_upload'] = $item['upload_date'];
                    }

                    $content->update($updateData);
                    $contentsUpdated++;
                }

                // 5b. SNAPSHOT HARIAN KONTEN (Tabel content_stats_daily)
                // ATURAN WAJIB: SNAPSHOT YANG SUDAH ADA BERSIFAT IMMUTABLE (TIDAK OVERWRITE / UPDATE)
                $snapshotExists = ContentStatsDaily::where('content_id', $content->id)
                    ->whereDate('tanggal', $today)
                    ->exists();

                if (! $snapshotExists) {
                    ContentStatsDaily::create([
                        'content_id' => $content->id,
                        'tanggal'    => $today,
                        'views'      => $item['views'],    // Strict NULL: NULL tetap NULL, 0 tetap 0
                        'likes'      => $item['likes'],    // Strict NULL
                        'comments'   => $item['comments'], // Strict NULL
                    ]);
                    $snapshotsInserted++;
                } else {
                    $snapshotsSkipped++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errorsCount++;
                Log::error('[TIKTOK SYNC] Error syncing video item', [
                    'external_id' => $item['external_id'] ?? null,
                    'error'       => $e->getMessage(),
                ]);
                $this->warn("Error syncing video ID {$item['external_id']}: {$e->getMessage()}");
            }
        }

        // 6. SNAPSHOT STATISTIK AKUN HARIAN (Tabel platform_stats_daily)
        // Aturan: Idempoten, tidak membuat duplicate, melengkapi field yang sebelumnya NULL,
        // serta memperbarui total_contents jika snapshot awal dibuat sebelum proses sinkronisasi selesai.
        $accountSnapshot = PlatformStatsDaily::where('platform_id', $platform->id)
            ->whereDate('tanggal', $today)
            ->first();

        $accountSnapshotInserted = 0;
        $accountSnapshotUpdated  = 0;
        $accountSnapshotSkipped  = 0;

        $totalContentsCount = $totalVideosFromApi !== null
            ? (int) $totalVideosFromApi
            : Content::where('platform_id', $platform->id)->where('status', 'aktif')->count();

        if (! $accountSnapshot) {
            PlatformStatsDaily::create([
                'platform_id'       => $platform->id,
                'social_account_id' => $account->id,
                'tanggal'           => $today,
                'followers'         => $followers,
                'total_contents'    => $totalContentsCount,
                'total_views'       => null, // Strict NULL: TikTok API tidak menyediakan metrik agregat views channel
            ]);
            $accountSnapshotInserted = 1;
        } else {
            $updateFields = [];

            // Aturan: Jika followers sebelumnya NULL dan nilai baru tersedia (non-null), lakukan backfill
            if ($accountSnapshot->followers === null && $followers !== null) {
                $updateFields['followers'] = $followers;
            }

            // Aturan: Perbarui total_contents jika kondisi terbaru berbeda/lebih mutakhir (> 0)
            if ($totalContentsCount > 0 && $totalContentsCount !== (int) $accountSnapshot->total_contents) {
                $updateFields['total_contents'] = $totalContentsCount;
            }

            // Aturan: Pastikan social_account_id terisi jika sebelumnya NULL
            if ($accountSnapshot->social_account_id === null && $account->id !== null) {
                $updateFields['social_account_id'] = $account->id;
            }

            if (! empty($updateFields)) {
                $accountSnapshot->update($updateFields);
                $accountSnapshotUpdated = 1;
            } else {
                $accountSnapshotSkipped = 1;
            }
        }

        // 7. DETEKSI & REKONSILIASI KONTEN DIHAPUS DARI TIKTOK
        // ATURAN WAJIB: Hanya dieksekusi jika seluruh proses fetch video dari API berstatus COMPLETE
        if ($fetchComplete) {
            $foundExternalIds = collect($videoList)->pluck('external_id')->filter()->toArray();

            $markedDeleted = Content::where('platform_id', $platform->id)
                ->where('status', 'aktif')
                ->whereNotIn('content_id_external', $foundExternalIds)
                ->update(['status' => 'dihapus']);
        } else {
            $this->warn('Fetch tidak lengkap (partial/gagal). Deleted-content reconciliation dilewati untuk melindungi data.');
        }

        // 8. Ringkasan Hasil Eksekusi
        $this->info("Contents inserted: {$contentsInserted}");
        $this->info("Contents updated: {$contentsUpdated}");
        $this->line('');
        $this->info("Content snapshots inserted: {$snapshotsInserted}");
        $this->info("Content snapshots skipped: {$snapshotsSkipped}");
        $this->line('');
        $this->info("Account snapshot inserted: {$accountSnapshotInserted}");
        $this->info("Account snapshot updated: {$accountSnapshotUpdated}");
        $this->info("Account snapshot skipped: {$accountSnapshotSkipped}");
        $this->line('');
        $this->info("Marked deleted: {$markedDeleted}");
        $this->info("Reactivated: {$reactivatedCount}");
        $this->line('');
        $this->info('Database rows deleted: 0');
        $this->info('Snapshots deleted: 0');
        $this->info("Errors: {$errorsCount}");
        $this->line('');
        $this->info('TikTok Sync Completed');

        Log::info('[TIKTOK SYNC SUCCESS] Sinkronisasi TikTok berhasil diselesaikan', [
            'contents_inserted'         => $contentsInserted,
            'contents_updated'          => $contentsUpdated,
            'snapshots_inserted'        => $snapshotsInserted,
            'account_snapshot_inserted' => $accountSnapshotInserted,
            'account_snapshot_updated'  => $accountSnapshotUpdated,
            'marked_deleted'            => $markedDeleted,
            'reactivated'               => $reactivatedCount,
            'errors'                    => $errorsCount,
        ]);

        return self::SUCCESS;
    }
}
