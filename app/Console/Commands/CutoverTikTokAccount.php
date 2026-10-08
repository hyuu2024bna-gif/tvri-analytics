<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Models\PlatformStatsDaily;
use App\Models\SocialAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CutoverTikTokAccount extends Command
{
    /**
     * Nama dan signature console command.
     * Default berjalan dalam mode DRY-RUN yang aman.
     * Hanya menghapus data jika opsi --execute diberikan.
     */
    protected $signature = 'tiktok:cutover 
                            {--dry-run : Lakukan simulasi cutover tanpa mengubah database (default)}
                            {--execute : Eksekusi pembersihan data analitik testing TikTok}';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Bersihkan data konten dan snapshot analitik testing TikTok untuk mempersiapkan migrasi ke akun resmi TVRI Aceh';

    public function handle(): int
    {
        $isExecute = (bool) $this->option('execute');

        // 1. Ambil platform TikTok
        $platform = Platform::where('slug', 'tiktok')->first();
        if (! $platform) {
            $this->error('[TIKTOK CUTOVER FAILED] Platform dengan slug "tiktok" tidak ditemukan di database.');
            return self::FAILURE;
        }

        // 2. Ambil informasi akun TikTok saat ini (tanpa menampilkan token / secret)
        $account = SocialAccount::where('platform_id', $platform->id)->first();
        $accountUsername = $account?->username ?? '(Belum ada akun terhubung)';
        $accountExternalId = $account?->external_account_id ?? '-';

        // 3. Hitung jumlah data analitik TikTok saat ini
        $contentsQuery = Content::where('platform_id', $platform->id);
        $totalContents = (clone $contentsQuery)->count();
        $activeContents = (clone $contentsQuery)->where('status', 'aktif')->count();
        $inactiveContents = (clone $contentsQuery)->where('status', '!=', 'aktif')->count();
        $contentIds = (clone $contentsQuery)->pluck('id')->toArray();

        $contentStatsCount = ! empty($contentIds)
            ? ContentStatsDaily::whereIn('content_id', $contentIds)->count()
            : 0;

        $platformStatsCount = PlatformStatsDaily::where('platform_id', $platform->id)->count();
        $totalRecords = $totalContents + $contentStatsCount + $platformStatsCount;

        // =====================================================================
        // MODE A: DRY RUN (DEFAULT / TANPA --execute)
        // =====================================================================
        if (! $isExecute) {
            $this->info('==================================================');
            $this->info('TIKTOK CUTOVER — DRY RUN (SIMULASI AMAN)');
            $this->info('==================================================');
            $this->line("Social account       : {$accountUsername} (OpenID: {$accountExternalId})");
            $this->line("TikTok contents      : {$totalContents}");
            $this->line("  - Aktif            : {$activeContents}");
            $this->line("  - Nonaktif/dihapus : {$inactiveContents}");
            $this->line("Content stats daily  : {$contentStatsCount}");
            $this->line("Platform stats daily : {$platformStatsCount}");
            $this->line('--------------------------------------------------');
            $this->line("Total record target  : {$totalRecords} record");
            $this->line('');
            $this->comment('STATUS: NO DATABASE CHANGES MADE');
            $this->line('');
            $this->line('JAMINAN KEAMANAN:');
            $this->line('  [v] Row social_accounts TIDAK AKAN DIHAPUS.');
            $this->line('  [v] Platform TikTok TIDAK AKAN DIHAPUS.');
            $this->line('  [v] Data YouTube, Instagram, dan Facebook TIDAK AKAN DISENTUH.');
            $this->line('');
            $this->warn('PERINGATAN: Pastikan database sudah di-backup sebelum melanjutkan.');
            $this->line('');
            $this->info('Untuk mengeksekusi pembersihan nyata:');
            $this->info('  php artisan tiktok:cutover --execute');
            $this->info('==================================================');

            return self::SUCCESS;
        }

        // =====================================================================
        // MODE B: EXECUTE (--execute)
        // =====================================================================
        $this->warn('Pastikan database sudah di-backup sebelum melanjutkan.');

        // Idempotensi: jika data sudah bersih, laporkan tanpa error
        if ($totalRecords === 0) {
            $this->info('==================================================');
            $this->info('TIKTOK CUTOVER — EXECUTE');
            $this->info('==================================================');
            $this->info('Data analitik TikTok sudah dalam kondisi BERSIH (0 record ditemukan).');
            $this->info('Tidak ada konten maupun statistik yang perlu dihapus.');
            $this->line('--------------------------------------------------');
            $this->info('[1/4] Content stats removed  : 0');
            $this->info('[2/4] Contents removed       : 0');
            $this->info('[3/4] Platform stats removed : 0');
            $this->info('[4/4] Social account preserved: YES');
            $this->line('--------------------------------------------------');
            $this->info('CUTOVER SUCCESSFUL (Data already clean)');
            $this->info('==================================================');

            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // Langkah 1: Hapus content_stats_daily yang terkait dengan konten TikTok
            $contentStatsDeleted = 0;
            if (! empty($contentIds)) {
                $contentStatsDeleted = ContentStatsDaily::whereIn('content_id', $contentIds)->delete();
            }

            // Langkah 2: Hapus contents milik platform TikTok
            $contentsDeleted = Content::where('platform_id', $platform->id)->delete();

            // Langkah 3: Hapus platform_stats_daily milik platform TikTok
            $platformStatsDeleted = PlatformStatsDaily::where('platform_id', $platform->id)->delete();

            // Langkah 4: Pastikan social_accounts TETAP ADA dan TIDAK DIHAPUS
            $accountStillExists = SocialAccount::where('platform_id', $platform->id)->exists();

            DB::commit();

            $this->info('==================================================');
            $this->info('TIKTOK CUTOVER — EXECUTE');
            $this->info('==================================================');
            $this->info("[1/4] Content stats removed   : {$contentStatsDeleted}");
            $this->info("[2/4] Contents removed        : {$contentsDeleted}");
            $this->info("[3/4] Platform stats removed  : {$platformStatsDeleted}");
            $this->info('[4/4] Social account preserved : ' . ($accountStillExists ? 'YES' : 'NO'));
            $this->line('--------------------------------------------------');
            $this->info('CUTOVER SUCCESSFUL');
            $this->info('==================================================');
            $this->comment('Kondisi data TikTok kini bersih (clean slate).');
            $this->comment('Akun TikTok resmi TVRI Aceh siap dihubungkan melalui /tiktok/connect.');

            Log::info('[TIKTOK CUTOVER] Pembersihan data testing berhasil dieksekusi', [
                'platform_id'            => $platform->id,
                'content_stats_deleted'  => $contentStatsDeleted,
                'contents_deleted'       => $contentsDeleted,
                'platform_stats_deleted' => $platformStatsDeleted,
                'social_account_kept'    => $accountStillExists,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error('==================================================');
            $this->error('TIKTOK CUTOVER FAILED — TRANSACTION ROLLED BACK');
            $this->error('==================================================');
            $this->error("Terjadi error: {$e->getMessage()}");
            $this->error('Seluruh perubahan dibatalkan. Tidak ada data yang terhapus.');

            Log::error('[TIKTOK CUTOVER] Gagal mengeksekusi cutover, rollback dilakukan', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
