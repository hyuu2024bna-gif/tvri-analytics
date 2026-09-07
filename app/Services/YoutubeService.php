<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YoutubeService
{
    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) config('services.youtube.key');
    }

    /**
     * Get configured HTTP client with retry and timeout.
     */
    protected function client(): PendingRequest
    {
        return Http::timeout(30)
            ->retry(3, 1000, function (\Throwable $exception, $request) {
                // Retry on transient network / connection drops / timeouts (cURL 28)
                if ($exception instanceof ConnectionException) {
                    return true;
                }
                // Retry on rate limit (429) or server errors (5xx)
                if ($exception instanceof RequestException) {
                    $status = $exception->response ? $exception->response->status() : 0;
                    return $status === 429 || $status >= 500;
                }
                return false;
            }, throw: false);
    }

    /**
     * Execute GET request and handle errors safely without exposing credentials.
     *
     * @throws \RuntimeException
     */
    protected function get(string $endpoint, array $query = []): Response
    {
        $query['key'] = $this->apiKey;
        $url = "{$this->baseUrl}/{$endpoint}";

        try {
            $response = $this->client()->get($url, $query);
        } catch (\Throwable $e) {
            Log::error("YouTube API network failure on endpoint [{$endpoint}]", [
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Koneksi ke YouTube API gagal pada [{$endpoint}]: " . $e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $status = $response->status();
            $body = $response->json() ?? $response->body();

            // Log error safely without query parameters/credentials
            Log::error("YouTube API error on endpoint [{$endpoint}]", [
                'status' => $status,
                'error'  => is_array($body) ? ($body['error'] ?? $body) : substr((string) $body, 0, 300),
            ]);

            throw new \RuntimeException("Gagal mengambil data dari YouTube API [{$endpoint}]: HTTP status {$status}");
        }

        return $response;
    }

    public function getVideoStats(array $videoIds): array
    {
        if (empty($videoIds)) {
            return [];
        }

        $response = $this->get('videos', [
            'part' => 'snippet,statistics',
            'id' => implode(',', $videoIds),
        ]);

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
        $response = $this->get('channels', [
            'part' => 'contentDetails',
            'id' => $channelId,
        ]);

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
            $query = [
                'part' => 'contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => 50,
            ];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->get('playlistItems', $query);

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
        $response = $this->get('channels', [
            'part' => 'statistics',
            'id' => $channelId,
        ]);

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

