<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YoutubeService
{
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.key');
    }

    public function getVideoStats(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $response = Http::get("{$this->baseUrl}/videos", [
            'part' => 'snippet,statistics',
            'id' => implode(',', $videoIds),
            'key' => $this->apiKey,
        ]);

        if (! $response->successful()) {
            Log::error('YouTube API error (videos.list)', ['body' => $response->body()]);
            throw new \RuntimeException('Gagal mengambil data dari YouTube API: ' . $response->status());
        }

        $items = $response->json('items', []);

        return collect($items)->map(fn ($item) => [
            'video_id'       => $item['id'],
            'judul'          => $item['snippet']['title'] ?? null,
            'thumbnail'      => $item['snippet']['thumbnails']['medium']['url'] ?? null,
            'tanggal_upload' => $item['snippet']['publishedAt'] ?? null,
            'views'          => (int) ($item['statistics']['viewCount'] ?? 0),
            'likes'          => (int) ($item['statistics']['likeCount'] ?? 0),
            'comments'       => (int) ($item['statistics']['commentCount'] ?? 0),
        ])->toArray();
    }

    public function getUploadsPlaylistId(string $channelId): string
    {
        $response = Http::get("{$this->baseUrl}/channels", [
            'part' => 'contentDetails',
            'id' => $channelId,
            'key' => $this->apiKey,
        ]);

        if (! $response->successful()) {
            Log::error('YouTube API error (channels.list contentDetails)', ['body' => $response->body()]);
            throw new \RuntimeException('Gagal mengambil data channel: ' . $response->status());
        }

        $playlistId = $response->json('items.0.contentDetails.relatedPlaylists.uploads');

        if (! $playlistId) {
            throw new \RuntimeException('Uploads playlist tidak ditemukan. Cek kembali Channel ID.');
        }

        return $playlistId;
    }

    public function getAllVideoIdsFromPlaylist(string $playlistId): array
    {
        $videoIds = [];
        $pageToken = null;

        do {
            $response = Http::get("{$this->baseUrl}/playlistItems", array_filter([
                'part' => 'contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => $this->apiKey,
            ]));

            if (! $response->successful()) {
                Log::error('YouTube API error (playlistItems.list)', ['body' => $response->body()]);
                throw new \RuntimeException('Gagal mengambil daftar video: ' . $response->status());
            }

            $items = $response->json('items', []);
            foreach ($items as $item) {
                $videoIds[] = $item['contentDetails']['videoId'];
            }

            $pageToken = $response->json('nextPageToken');
        } while ($pageToken);

        return $videoIds;
    }

    public function getAllChannelVideoIds(string $channelId): array
    {
        $playlistId = $this->getUploadsPlaylistId($channelId);

        return $this->getAllVideoIdsFromPlaylist($playlistId);
    }

    public function getChannelStats(string $channelId): array
    {
        $response = Http::get("{$this->baseUrl}/channels", [
            'part' => 'statistics',
            'id' => $channelId,
            'key' => $this->apiKey,
        ]);

        if (! $response->successful()) {
            Log::error('YouTube API error (channels.list statistics)', ['body' => $response->body()]);
            throw new \RuntimeException('Gagal mengambil statistik channel: ' . $response->status());
        }

        $stats = $response->json('items.0.statistics');

        if (! $stats) {
            throw new \RuntimeException('Statistik channel tidak ditemukan. Cek kembali Channel ID.');
        }

        return [
            'subscriber_count' => (int) ($stats['subscriberCount'] ?? 0),
            'view_count' => (int) ($stats['viewCount'] ?? 0),
            'video_count' => (int) ($stats['videoCount'] ?? 0),
        ];
    }
}
