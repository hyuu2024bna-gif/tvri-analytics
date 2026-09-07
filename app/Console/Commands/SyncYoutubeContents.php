<?php

namespace App\Console\Commands;

use App\Models\ChannelStatsDaily;
use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Services\YoutubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncYoutubeContents extends Command
{
    protected $signature = 'youtube:sync';
    protected $description = 'Sync semua video dari channel YouTube TVRI Aceh + simpan snapshot statistik hari ini';

    public function handle(YoutubeService $youtube): int
    {
        $today = Carbon::today()->toDateString();
        $this->info("[SYNC START] Memulai sinkronisasi YouTube untuk tanggal: {$today}");
        Log::info("[SYNC START] YouTube sync dimulai untuk tanggal: {$today}");

        $channelId = config('services.youtube.channel_id');

        if (! $channelId) {
            $msg = 'YOUTUBE_CHANNEL_ID belum diisi di .env';
            $this->error("[SYNC FAILED] Tahap: inisialisasi - {$msg}");
            Log::error("[SYNC FAILED] Stage: inisialisasi - {$msg}");
            return self::FAILURE;
        }

        $platform = Platform::where('slug', 'youtube')->first();
        if (! $platform) {
            $msg = 'Platform "youtube" tidak ditemukan di tabel platforms. Jalankan PlatformSeeder dulu.';
            $this->error("[SYNC FAILED] Tahap: inisialisasi - {$msg}");
            Log::error("[SYNC FAILED] Stage: inisialisasi - {$msg}");
            return self::FAILURE;
        }

        // Tahap 1: Ambil semua ID Video dari channel (Pagination playlistItems)
        $this->info('Mengambil daftar video dari channel...');
        try {
            $videoIds = $youtube->getAllChannelVideoIds($channelId);
        } catch (\Throwable $e) {
            $this->error("[SYNC FAILED] Tahap: playlistItems pagination - " . $e->getMessage());
            Log::error("[SYNC FAILED] Stage: playlistItems pagination", [
                'error' => $e->getMessage(),
                'channel_id' => $channelId,
            ]);
            return self::FAILURE;
        }

        $totalVideos = count($videoIds);
        $this->info("Ditemukan {$totalVideos} video di channel.");

        $chunks = array_chunk($videoIds, 50);
        $totalChunks = count($chunks);
        $foundExternalIds = [];
        $processedVideos = 0;
        $successfulChunks = 0;
        $snapshotsCreated = 0;
        $snapshotsSkipped = 0;

        // Tahap 2: Ambil statistik video dalam chunk 50
        foreach ($chunks as $chunkIndex => $chunk) {
            $currentChunkNum = $chunkIndex + 1;
            try {
                $statsList = $youtube->getVideoStats($chunk);
            } catch (\Throwable $e) {
                $this->error("[SYNC FAILED] Tahap: videos statistics chunk {$currentChunkNum}/{$totalChunks} - " . $e->getMessage());
                Log::error("[SYNC FAILED] Stage: videos statistics chunk", [
                    'chunk' => "{$currentChunkNum}/{$totalChunks}",
                    'processed_videos_before_failure' => $processedVideos,
                    'successful_chunks' => $successfulChunks,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Proses sync dihentikan. Snapshot channel TIDAK akan dibuat untuk mencegah data parsial.");
                return self::FAILURE;
            }

            foreach ($statsList as $stat) {
                $foundExternalIds[] = $stat['video_id'];

                $content = Content::updateOrCreate(
                    [
                        'platform_id' => $platform->id,
                        'content_id_external' => $stat['video_id'],
                    ],
                    [
                        'judul' => $stat['judul'],
                        'url' => 'https://www.youtube.com/watch?v=' . $stat['video_id'],
                        'thumbnail_url' => $stat['thumbnail'],
                        'tanggal_upload' => $stat['tanggal_upload']
                            ? Carbon::parse($stat['tanggal_upload'])->toDateString()
                            : null,
                        'status' => 'aktif',
                    ]
                );

                // Immutable snapshot: hanya simpan jika belum ada snapshot untuk tanggal hari ini
                $existingSnapshot = ContentStatsDaily::where('content_id', $content->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                if (! $existingSnapshot) {
                    ContentStatsDaily::create([
                        'content_id' => $content->id,
                        'tanggal' => $today,
                        'views' => $stat['views'],
                        'likes' => $stat['likes'],
                        'comments' => $stat['comments'],
                    ]);
                    $snapshotsCreated++;
                } else {
                    $snapshotsSkipped++;
                }

                $processedVideos++;
            }

            $successfulChunks++;
        }

        // Tandai konten yang sudah tidak ada di channel
        $hilang = Content::where('platform_id', $platform->id)
            ->where('status', 'aktif')
            ->whereNotIn('content_id_external', $foundExternalIds)
            ->update(['status' => 'dihapus']);

        $this->info("Sync video selesai ({$processedVideos}/{$totalVideos} video diproses). {$hilang} konten ditandai dihapus.");

        // Tahap 3: Ambil statistik channel & simpan snapshot channel
        $this->info('Mengambil statistik channel (subscriber, total views, video count)...');
        try {
            $channelStats = $youtube->getChannelStats($channelId);
        } catch (\Throwable $e) {
            $this->error("[SYNC FAILED] Tahap: channel statistics - " . $e->getMessage());
            Log::error("[SYNC FAILED] Stage: channel statistics", [
                'error' => $e->getMessage(),
                'processed_videos' => $processedVideos,
                'successful_chunks' => $successfulChunks,
            ]);
            return self::FAILURE;
        }

        // Immutable snapshot channel: hanya simpan jika belum ada snapshot untuk tanggal hari ini
        $existingChannelSnapshot = ChannelStatsDaily::whereDate('tanggal', $today)->first();
        if (! $existingChannelSnapshot) {
            ChannelStatsDaily::create([
                'tanggal' => $today,
                'subscriber_count' => $channelStats['subscriber_count'],
                'total_views' => $channelStats['view_count'],
                'video_count' => $channelStats['video_count'],
            ]);
            $this->info("Snapshot channel untuk tanggal {$today} berhasil dibuat.");
        } else {
            $this->info("Snapshot channel untuk tanggal {$today} sudah ada, dipertahankan (immutable).");
        }


        $this->info('Subscriber channel saat ini: ' . number_format($channelStats['subscriber_count']));
        $this->info("[SYNC SUCCESS] Sync selesai sepenuhnya. Chunk sukses: {$successfulChunks}/{$totalChunks}, Video: {$processedVideos}, Snapshot baru: {$snapshotsCreated}, Snapshot immutable/skip: {$snapshotsSkipped}.");

        Log::info("[SYNC SUCCESS] YouTube sync berhasil untuk tanggal: {$today}", [
            'total_videos' => $totalVideos,
            'processed_videos' => $processedVideos,
            'successful_chunks' => $successfulChunks,
            'snapshots_created' => $snapshotsCreated,
            'snapshots_skipped' => $snapshotsSkipped,
            'subscriber_count' => $channelStats['subscriber_count'],
            'view_count' => $channelStats['view_count'],
        ]);

        return self::SUCCESS;
    }
}

