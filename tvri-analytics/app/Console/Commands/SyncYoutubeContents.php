<?php

namespace App\Console\Commands;

use App\Models\ChannelStatsDaily;
use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use App\Services\YoutubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncYoutubeContents extends Command
{
    protected $signature = 'youtube:sync';
    protected $description = 'Sync semua video dari channel YouTube TVRI Aceh + simpan snapshot statistik hari ini';

    public function handle(YoutubeService $youtube): int
    {
        $channelId = config('services.youtube.channel_id');

        if (! $channelId) {
            $this->error('YOUTUBE_CHANNEL_ID belum diisi di .env');
            return self::FAILURE;
        }

        $platform = Platform::where('slug', 'youtube')->first();
        if (! $platform) {
            $this->error('Platform "youtube" tidak ditemukan di tabel platforms. Jalankan PlatformSeeder dulu.');
            return self::FAILURE;
        }

        $today = Carbon::today()->toDateString();

        $this->info('Mengambil daftar video dari channel...');
        $videoIds = $youtube->getAllChannelVideoIds($channelId);
        $this->info('Ditemukan ' . count($videoIds) . ' video di channel.');

        $foundExternalIds = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $statsList = $youtube->getVideoStats($chunk);

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

                ContentStatsDaily::updateOrCreate(
                    [
                        'content_id' => $content->id,
                        'tanggal' => $today,
                    ],
                    [
                        'views' => $stat['views'],
                        'likes' => $stat['likes'],
                        'comments' => $stat['comments'],
                    ]
                );
            }
        }

        $hilang = Content::where('platform_id', $platform->id)
            ->where('status', 'aktif')
            ->whereNotIn('content_id_external', $foundExternalIds)
            ->update(['status' => 'dihapus']);

        $this->info("Sync video selesai. {$hilang} konten ditandai sebagai dihapus (tidak ditemukan lagi di channel).");

        $this->info('Mengambil statistik channel (subscriber, dst)...');
        $channelStats = $youtube->getChannelStats($channelId);

        ChannelStatsDaily::updateOrCreate(
            ['tanggal' => $today],
            [
                'subscriber_count' => $channelStats['subscriber_count'],
                'total_views' => $channelStats['view_count'],
                'video_count' => $channelStats['video_count'],
            ]
        );

        $this->info('Subscriber channel saat ini: ' . number_format($channelStats['subscriber_count']));
        $this->info('Sync selesai sepenuhnya.');

        return self::SUCCESS;
    }
}
