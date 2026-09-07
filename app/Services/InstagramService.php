<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    protected string $baseUrl;
    protected ?SocialAccount $account = null;

    public function __construct()
    {
        $version = config('services.facebook.graph_api_version', 'v19.0');
        $this->baseUrl = "https://graph.facebook.com/{$version}";
    }

    /**
     * Ambil SocialAccount Instagram yang tersimpan di database.
     *
     * @throws \RuntimeException jika belum ada koneksi Instagram
     */
    protected function resolveAccount(): SocialAccount
    {
        if ($this->account) {
            return $this->account;
        }

        $platform = Platform::where('slug', 'instagram')->first();

        if (! $platform) {
            throw new \RuntimeException(
                'Platform "instagram" belum terdaftar di tabel platforms. Jalankan seeder atau tambahkan secara manual.'
            );
        }

        $account = SocialAccount::where('platform_id', $platform->id)->first();

        if (! $account) {
            throw new \RuntimeException(
                'Belum ada akun Instagram yang terhubung. Silakan hubungkan akun melalui halaman Instagram Connect.'
            );
        }

        if (! $account->access_token) {
            throw new \RuntimeException(
                'Access token Instagram tidak ditemukan di database. Silakan hubungkan ulang akun Instagram.'
            );
        }

        // Periksa apakah token sudah kedaluwarsa
        if ($account->expires_at && $account->expires_at->isPast()) {
            throw new \RuntimeException(
                'Access token Instagram sudah kedaluwarsa sejak ' . $account->expires_at->format('d M Y H:i') . '. Silakan hubungkan ulang akun Instagram.'
            );
        }

        $this->account = $account;

        return $account;
    }

    /**
     * Ambil informasi dasar Instagram Professional Account.
     *
     * Fields yang diminta: id, username, name, profile_picture_url,
     * followers_count, media_count.
     *
     * Tidak semua field mungkin tersedia tergantung permission/account type.
     */
    public function getInstagramAccount(): array
    {
        $account = $this->resolveAccount();

        $fields = 'id,username,name,profile_picture_url,followers_count,media_count';

        try {
            $response = Http::get("{$this->baseUrl}/{$account->external_account_id}", [
                'fields' => $fields,
                'access_token' => $account->access_token,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Instagram API: koneksi gagal (getInstagramAccount)', [
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'Gagal terhubung ke Meta Graph API. Periksa koneksi internet server.'
            );
        }

        if (! $response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown error';
            $errorCode = $errorBody['error']['code'] ?? $response->status();

            Log::error('Instagram API error (getInstagramAccount)', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'ig_account_id' => $account->external_account_id,
            ]);

            // Token invalid/expired dari sisi API
            if (in_array($errorCode, [190, 102])) {
                throw new \RuntimeException(
                    "Access token Instagram tidak valid atau sudah kedaluwarsa (code: {$errorCode}). Silakan hubungkan ulang akun Instagram."
                );
            }

            throw new \RuntimeException(
                "Gagal mengambil data akun Instagram dari API (HTTP {$response->status()}): {$errorMessage}"
            );
        }

        $data = $response->json();

        if (! isset($data['id'])) {
            Log::warning('Instagram API: response tidak memiliki field "id"', [
                'response' => $data,
                'ig_account_id' => $account->external_account_id,
            ]);
            throw new \RuntimeException(
                'Response dari API tidak memiliki field yang diharapkan (id). Periksa permission dan jenis akun Instagram.'
            );
        }

        return [
            'id' => $data['id'] ?? null,
            'username' => $data['username'] ?? null,
            'name' => $data['name'] ?? null,
            'profile_picture_url' => $data['profile_picture_url'] ?? null,
            'followers_count' => isset($data['followers_count']) ? (int) $data['followers_count'] : null,
            'media_count' => isset($data['media_count']) ? (int) $data['media_count'] : null,
        ];
    }

    /**
     * Alias untuk getInstagramAccount() agar sesuai konvensi getAccount().
     */
    public function getAccount(): array
    {
        return $this->getInstagramAccount();
    }

    /**
     * Ambil daftar media/postingan Instagram.
     *
     * @param  int  $limit  Jumlah media yang diambil (default 25, max 100 per page)
     * @param  string|null  $after  Cursor untuk pagination
     */
    public function getMedia(int $limit = 25, ?string $after = null): array
    {
        $account = $this->resolveAccount();

        $fields = 'id,caption,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count';

        $params = [
            'fields' => $fields,
            'limit' => min(max($limit, 1), 100),
            'access_token' => $account->access_token,
        ];

        if ($after) {
            $params['after'] = $after;
        }

        try {
            $response = Http::get("{$this->baseUrl}/{$account->external_account_id}/media", $params);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Instagram API: koneksi gagal (getMedia)', [
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'Gagal terhubung ke Meta Graph API. Periksa koneksi internet server.'
            );
        }

        if (! $response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown error';
            $errorCode = $errorBody['error']['code'] ?? $response->status();

            Log::error('Instagram API error (getMedia)', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'ig_account_id' => $account->external_account_id,
            ]);

            if (in_array($errorCode, [190, 102])) {
                throw new \RuntimeException(
                    "Access token Instagram tidak valid atau sudah kedaluwarsa (code: {$errorCode}). Silakan hubungkan ulang akun Instagram."
                );
            }

            throw new \RuntimeException(
                "Gagal mengambil daftar media Instagram dari API (HTTP {$response->status()}): {$errorMessage}"
            );
        }

        $data = $response->json();

        if (! isset($data['data'])) {
            Log::warning('Instagram API: response getMedia tidak memiliki field "data"', [
                'response' => $data,
                'ig_account_id' => $account->external_account_id,
            ]);

            return [
                'data' => [],
                'paging' => null,
            ];
        }

        $media = collect($data['data'])->map(fn ($item) => [
            'id' => $item['id'] ?? null,
            'caption' => $item['caption'] ?? null,
            'media_type' => $item['media_type'] ?? null,
            'media_product_type' => $item['media_product_type'] ?? null,
            'media_url' => $item['media_url'] ?? null,
            'thumbnail_url' => $item['thumbnail_url'] ?? null,
            'permalink' => $item['permalink'] ?? null,
            'timestamp' => $item['timestamp'] ?? null,
            'like_count' => isset($item['like_count']) ? (int) $item['like_count'] : 0,
            'comments_count' => isset($item['comments_count']) ? (int) $item['comments_count'] : 0,
        ])->toArray();

        return [
            'data' => $media,
            'paging' => $data['paging'] ?? null,
        ];
    }

    /**
     * Ambil insight/statistik performa untuk media tertentu (jika didukung API & tipe konten).
     *
     * @param  string  $mediaId  Instagram Media ID
     * @param  string  $mediaType  IMAGE, VIDEO, CAROUSEL_ALBUM
     * @param  string|null  $mediaProductType  FEED, REELS, STORY, AD
     */
    public function getMediaInsights(string $mediaId, string $mediaType = 'IMAGE', ?string $mediaProductType = null): array
    {
        $account = $this->resolveAccount();

        // Tentukan metrics yang valid berdasarkan media type & product type
        $metrics = [];
        $isReels = ($mediaProductType === 'REELS' || $mediaType === 'REELS');
        $isVideo = ($mediaType === 'VIDEO' || $isReels);

        if ($isReels) {
            $metrics = ['plays', 'reach', 'saved', 'total_interactions'];
        } elseif ($isVideo) {
            $metrics = ['plays', 'reach', 'saved', 'total_interactions'];
        } elseif ($mediaType === 'CAROUSEL_ALBUM') {
            $metrics = ['reach', 'saved', 'total_interactions'];
        } else {
            // IMAGE
            $metrics = ['reach', 'saved', 'total_interactions'];
        }

        try {
            $response = Http::get("{$this->baseUrl}/{$mediaId}/insights", [
                'metric' => implode(',', $metrics),
                'access_token' => $account->access_token,
            ]);

            if (! $response->successful()) {
                // Fallback: coba metric lebih sederhana jika ada metric yang tidak didukung
                $fallbackMetrics = ['reach', 'saved'];
                $response = Http::get("{$this->baseUrl}/{$mediaId}/insights", [
                    'metric' => implode(',', $fallbackMetrics),
                    'access_token' => $account->access_token,
                ]);
            }

            if ($response->successful()) {
                $rawInsights = $response->json('data', []);
                $insightMap = [];

                foreach ($rawInsights as $metricItem) {
                    $name = $metricItem['name'] ?? null;
                    $value = $metricItem['values'][0]['value'] ?? ($metricItem['value'] ?? null);
                    if ($name) {
                        $insightMap[$name] = $value;
                    }
                }

                return [
                    'success' => true,
                    'metrics' => $insightMap,
                    'views' => isset($insightMap['plays']) ? (int) $insightMap['plays'] : (isset($insightMap['views']) ? (int) $insightMap['views'] : null),
                    'reach' => isset($insightMap['reach']) ? (int) $insightMap['reach'] : null,
                    'saved' => isset($insightMap['saved']) ? (int) $insightMap['saved'] : null,
                ];
            }

            $errBody = $response->json();
            return [
                'success' => false,
                'metrics' => [],
                'views' => null,
                'error' => $errBody['error']['message'] ?? 'Insights unsupported',
                'error_code' => $errBody['error']['code'] ?? $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'metrics' => [],
                'views' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar media yang sudah dinormalisasi ke struktur internal project
     * (siap untuk disimpan ke tabel `contents` dan `content_stats_daily`).
     *
     * @param  int  $limit  Jumlah media maksimal (default 25)
     * @param  string|null  $after  Cursor pagination
     * @param  bool  $withInsights  Apakah perlu mengambil insights per media
     */
    public function getNormalizedMedia(int $limit = 25, ?string $after = null, bool $withInsights = true): array
    {
        $rawResult = $this->getMedia($limit, $after);
        $normalizedList = [];

        foreach ($rawResult['data'] as $item) {
            $mediaId = $item['id'];
            $mediaType = $item['media_type'] ?? 'IMAGE';
            $mediaProductType = $item['media_product_type'] ?? null;
            $caption = $item['caption'] ?? '';
            $timestamp = $item['timestamp'] ?? null;

            // Generate judul yang aman dan informatif
            $title = $this->generateTitleFromCaption($caption, $timestamp);

            // Thumbnail URL fallback
            $thumbnailUrl = $item['thumbnail_url'] ?? $item['media_url'] ?? null;

            // Engagement dasar (likes & comments selalu dari media object)
            $likes = (int) ($item['like_count'] ?? 0);
            $comments = (int) ($item['comments_count'] ?? 0);
            $views = null;
            $insightsInfo = null;

            // Ambil views/plays dari insights jika diminta dan media bertipe video/reels
            if ($withInsights) {
                $insightResult = $this->getMediaInsights($mediaId, $mediaType, $mediaProductType);
                $insightsInfo = $insightResult;
                if (! empty($insightResult['views'])) {
                    $views = (int) $insightResult['views'];
                }
            }

            $normalizedList[] = [
                'external_id'        => (string) $mediaId,
                'title'              => $title,
                'url'                => $item['permalink'] ?? '',
                'thumbnail_url'      => $thumbnailUrl,
                'published_at'       => $timestamp,
                'upload_date'        => $timestamp ? date('Y-m-d', strtotime($timestamp)) : null,
                'media_type'         => $mediaType,
                'media_product_type' => $mediaProductType,
                'views'              => $views,
                'likes'              => $likes,
                'comments'           => $comments,
                'insights_detail'    => $insightsInfo,
            ];
        }

        return [
            'data' => $normalizedList,
            'total' => count($normalizedList),
            'paging' => $rawResult['paging'] ?? null,
        ];
    }

    /**
     * Ambil semua media yang dinormalisasi beserta status kelengkapan fetch (is_complete).
     *
     * @param  int  $maxPages  Batas maksimal halaman untuk mencegah infinite loop (default 10 halaman)
     * @param  bool  $withInsights  Apakah perlu mengambil insights per media
     * @return array{data: array, is_complete: bool, pages_fetched: int}
     */
    public function getAllNormalizedMediaWithStatus(int $maxPages = 10, bool $withInsights = true): array
    {
        $allMedia = [];
        $after = null;
        $pagesCount = 0;
        $isComplete = false;

        do {
            $result = $this->getNormalizedMedia(50, $after, $withInsights);
            $allMedia = array_merge($allMedia, $result['data']);
            $pagesCount++;

            $after = $result['paging']['cursors']['after'] ?? null;
            $hasNext = ! empty($result['paging']['next']) && ! empty($after);

            if (! $hasNext) {
                $isComplete = true;
                break;
            }
        } while ($hasNext && $pagesCount < $maxPages);

        return [
            'data'          => $allMedia,
            'is_complete'   => $isComplete,
            'pages_fetched' => $pagesCount,
        ];
    }

    /**
     * Ambil semua media yang dinormalisasi dengan pagination (otomatis menyusuri next page sampai selesai).
     *
     * @param  int  $maxPages  Batas maksimal halaman untuk mencegah infinite loop (default 10 halaman)
     * @param  bool  $withInsights  Apakah perlu mengambil insights per media
     */
    public function getAllNormalizedMedia(int $maxPages = 10, bool $withInsights = true): array
    {
        return $this->getAllNormalizedMediaWithStatus($maxPages, $withInsights)['data'];
    }

    /**
     * Helper: Generate judul ringkas dari caption Instagram.
     */
    protected function generateTitleFromCaption(?string $caption, ?string $timestamp): string
    {
        if (! empty($caption)) {
            // Bersihkan baris baru dan spasi berlebih
            $cleanCaption = trim(preg_replace('/\s+/', ' ', $caption));
            if (! empty($cleanCaption)) {
                return mb_strimwidth($cleanCaption, 0, 120, '...');
            }
        }

        $dateFormatted = $timestamp ? date('d-m-Y', strtotime($timestamp)) : date('d-m-Y');
        return "Instagram Post {$dateFormatted}";
    }
}

