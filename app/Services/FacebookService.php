<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    protected string $baseUrl;
    protected ?SocialAccount $account = null;
    protected ?string $resolvedPageId = null;

    public function __construct()
    {
        $version = config('services.facebook.graph_api_version', 'v19.0');
        $this->baseUrl = "https://graph.facebook.com/{$version}";
    }

    /**
     * Resolve SocialAccount Facebook / Meta yang tersimpan di database.
     * Menggunakan koneksi Meta yang sudah berhasil terhubung.
     *
     * @throws \RuntimeException jika belum ada koneksi Page / Meta
     */
    public function resolveAccount(): SocialAccount
    {
        if ($this->account) {
            return $this->account;
        }

        // Cek koneksi platform Facebook spesifik
        $fbPlatform = Platform::where('slug', 'facebook')->first();
        $account = null;

        if ($fbPlatform) {
            $account = SocialAccount::where('platform_id', $fbPlatform->id)->first();
        }

        // Jika belum ada record khusus Facebook, gunakan koneksi Meta dari Instagram (yang menyimpan page_id & token)
        if (! $account || ! $account->access_token || ! $account->page_id) {
            $account = SocialAccount::whereNotNull('page_id')
                ->whereNotNull('access_token')
                ->first();
        }

        if (! $account) {
            throw new \RuntimeException(
                'Belum ada Facebook Page yang terhubung. Silakan hubungkan akun Facebook/Instagram melalui halaman Meta Connect.'
            );
        }

        if (! $account->access_token) {
            throw new \RuntimeException(
                'Access token Facebook tidak ditemukan di database. Silakan hubungkan ulang akun.'
            );
        }

        // Periksa apakah token sudah kedaluwarsa
        if ($account->expires_at && $account->expires_at->isPast()) {
            throw new \RuntimeException(
                'Access token Facebook sudah kedaluwarsa sejak ' . $account->expires_at->format('d M Y H:i') . '. Silakan hubungkan ulang akun.'
            );
        }

        $this->account = $account;
        $this->resolvedPageId = $account->page_id ?: config('services.facebook.page_id');

        return $account;
    }

    /**
     * Dapatkan Page ID yang aktif.
     */
    public function getPageId(): string
    {
        if (! $this->resolvedPageId) {
            $this->resolveAccount();
        }

        if (! $this->resolvedPageId) {
            throw new \RuntimeException('Page ID Facebook tidak ditemukan pada koneksi aktif.');
        }

        return $this->resolvedPageId;
    }

    /**
     * Ambil informasi profil dasar Facebook Page secara live.
     *
     * Fields: id, name, link, followers_count, fan_count, picture, category, about
     */
    public function getPage(?string $pageId = null): array
    {
        $account = $this->resolveAccount();
        $targetPageId = $pageId ?: $this->getPageId();

        $fields = 'id,name,link,followers_count,fan_count,picture{url},about,category';

        try {
            $response = Http::get("{$this->baseUrl}/{$targetPageId}", [
                'fields' => $fields,
                'access_token' => $account->access_token,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facebook API: koneksi gagal (getPage)', [
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

            Log::error('Facebook API error (getPage)', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'page_id' => $targetPageId,
            ]);

            if (in_array($errorCode, [190, 102])) {
                throw new \RuntimeException(
                    "Access token Facebook tidak valid atau sudah kedaluwarsa (code: {$errorCode}). Silakan hubungkan ulang akun."
                );
            }

            throw new \RuntimeException(
                "Gagal mengambil data Facebook Page dari API (HTTP {$response->status()}): {$errorMessage}"
            );
        }

        $data = $response->json();

        if (! isset($data['id'])) {
            Log::warning('Facebook API: response getPage tidak memiliki field "id"', [
                'response' => $data,
                'page_id' => $targetPageId,
            ]);
            throw new \RuntimeException('Response dari API tidak memiliki field yang diharapkan (id).');
        }

        $followersCount = isset($data['followers_count'])
            ? (int) $data['followers_count']
            : (isset($data['fan_count']) ? (int) $data['fan_count'] : null);

        return [
            'id'              => (string) ($data['id'] ?? $targetPageId),
            'name'            => $data['name'] ?? null,
            'link'            => $data['link'] ?? "https://www.facebook.com/{$targetPageId}",
            'followers_count' => $followersCount,
            'fan_count'       => isset($data['fan_count']) ? (int) $data['fan_count'] : null,
            'picture_url'     => $data['picture']['data']['url'] ?? null,
            'about'           => $data['about'] ?? null,
            'category'        => $data['category'] ?? null,
        ];
    }

    /**
     * Ambil daftar postingan/feed dari Facebook Page.
     *
     * @param  int  $limit  Jumlah post maksimal (default 25, max 100)
     * @param  string|null  $after  Cursor pagination
     */
    public function getPosts(int $limit = 25, ?string $after = null): array
    {
        $account = $this->resolveAccount();
        $pageId = $this->getPageId();

        $fullFields = 'id,message,story,permalink_url,created_time,full_picture,status_type,shares,reactions.summary(total_count).limit(0),likes.summary(total_count).limit(0),comments.summary(total_count).limit(0)';
        $basicFields = 'id,message,story,permalink_url,created_time,full_picture,status_type,shares';

        $params = [
            'fields'       => $fullFields,
            'limit'        => min(max($limit, 1), 100),
            'access_token' => $account->access_token,
        ];

        if ($after) {
            $params['after'] = $after;
        }

        try {
            // Coba dengan full fields (termasuk reactions & comments summary)
            $response = Http::get("{$this->baseUrl}/{$pageId}/posts", $params);

            // Jika gagal HTTP 400 karena permission summary (pages_read_user_content), fallback ke basic fields
            if (! $response->successful() && $response->status() === 400) {
                $errMessage = $response->json('error.message', '');
                if (str_contains($errMessage, 'pages_read_user_content') || str_contains($errMessage, 'summary') || str_contains($errMessage, 'permission')) {
                    Log::info('Facebook API: pages_read_user_content belum granted pada token saat ini, fallback ke basic fields untuk membaca post.', [
                        'page_id' => $pageId,
                    ]);

                    $params['fields'] = $basicFields;
                    $response = Http::get("{$this->baseUrl}/{$pageId}/posts", $params);
                }
            }

            // Fallback ke /published_posts jika /posts masih gagal
            if (! $response->successful() && $response->status() === 400) {
                $response = Http::get("{$this->baseUrl}/{$pageId}/published_posts", $params);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facebook API: koneksi gagal (getPosts)', [
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

            Log::error('Facebook API error (getPosts)', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'page_id' => $pageId,
            ]);

            if (in_array($errorCode, [190, 102])) {
                throw new \RuntimeException(
                    "Access token Facebook tidak valid atau sudah kedaluwarsa (code: {$errorCode}). Silakan hubungkan ulang akun."
                );
            }

            throw new \RuntimeException(
                "Gagal mengambil daftar postingan Facebook Page dari API (HTTP {$response->status()}): {$errorMessage}"
            );
        }

        $data = $response->json();

        if (! isset($data['data']) || ! is_array($data['data'])) {
            return [
                'data'   => [],
                'paging' => null,
            ];
        }

        $posts = collect($data['data'])->map(function ($item) {
            $reactionsCount = isset($item['reactions']['summary']['total_count'])
                ? (int) $item['reactions']['summary']['total_count']
                : (isset($item['likes']['summary']['total_count']) ? (int) $item['likes']['summary']['total_count'] : null);

            $commentsCount = isset($item['comments']['summary']['total_count'])
                ? (int) $item['comments']['summary']['total_count']
                : null;

            $sharesCount = isset($item['shares']['count'])
                ? (int) $item['shares']['count']
                : null;

            return [
                'id'            => $item['id'] ?? null,
                'message'       => $item['message'] ?? ($item['story'] ?? null),
                'story'         => $item['story'] ?? null,
                'permalink_url' => $item['permalink_url'] ?? null,
                'created_time'  => $item['created_time'] ?? null,
                'full_picture'  => $item['full_picture'] ?? null,
                'status_type'   => $item['status_type'] ?? null,
                'like_count'    => $reactionsCount,
                'comments_count'=> $commentsCount,
                'shares_count'  => $sharesCount,
            ];
        })->toArray();

        return [
            'data'   => $posts,
            'paging' => $data['paging'] ?? null,
        ];
    }

    /**
     * Ambil insight performa untuk postingan video Facebook (jika bertipe video).
     * Jika bukan video atau tidak didukung, metrik views bernilai NULL (tanpa fallback palsu).
     */
    public function getPostInsights(string $postId, ?string $statusType = null): array
    {
        $account = $this->resolveAccount();

        // Hanya postingan yang bertipe video yang memiliki metrik video views
        $isVideo = in_array($statusType, ['added_video', 'mobile_status_update_video', 'video']);

        if (! $isVideo) {
            return [
                'success' => true,
                'views'   => null,
                'note'    => 'Post is not a video; views metric is not applicable (NULL).',
            ];
        }

        try {
            $response = Http::get("{$this->baseUrl}/{$postId}/insights", [
                'metric'       => 'post_video_views,post_video_views_unique',
                'access_token' => $account->access_token,
            ]);

            if ($response->successful()) {
                $raw = $response->json('data', []);
                $views = null;

                foreach ($raw as $m) {
                    $name = $m['name'] ?? '';
                    $val = $m['values'][0]['value'] ?? null;
                    if ($name === 'post_video_views' && is_numeric($val)) {
                        $views = (int) $val;
                        break;
                    }
                }

                return [
                    'success' => true,
                    'views'   => $views,
                ];
            }

            return [
                'success' => false,
                'views'   => null,
                'error'   => $response->json('error.message', 'Insights unavailable'),
            ];
        } catch (\Throwable $e) {
            Log::warning('Facebook Post Insights error', [
                'post_id' => $postId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'views'   => null,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar postingan yang sudah dinormalisasi ke struktur internal project.
     *
     * @param  int  $limit
     * @param  string|null  $after
     * @param  bool  $withInsights
     */
    public function getNormalizedPosts(int $limit = 25, ?string $after = null, bool $withInsights = true): array
    {
        $raw = $this->getPosts($limit, $after);
        $normalizedList = [];

        foreach ($raw['data'] as $item) {
            $postId = (string) $item['id'];
            $message = $item['message'] ?? '';
            $createdTime = $item['created_time'] ?? null;
            $statusType = $item['status_type'] ?? null;

            $title = $this->generateTitleFromMessage($message, $createdTime);
            $views = null;

            if ($withInsights) {
                $insightResult = $this->getPostInsights($postId, $statusType);
                if (isset($insightResult['views']) && $insightResult['views'] !== null) {
                    $views = (int) $insightResult['views'];
                }
            }

            $normalizedList[] = [
                'external_id'   => $postId,
                'title'         => $title,
                'url'           => $item['permalink_url'] ?? "https://www.facebook.com/{$postId}",
                'thumbnail_url' => $item['full_picture'] ?? null,
                'published_at'  => $createdTime,
                'upload_date'   => $createdTime ? date('Y-m-d', strtotime($createdTime)) : null,
                'post_type'     => $statusType,
                'views'         => $views,
                'likes'         => $item['like_count'] !== null ? (int) $item['like_count'] : null,
                'comments'      => $item['comments_count'] !== null ? (int) $item['comments_count'] : null,
                'shares'        => $item['shares_count'] !== null ? (int) $item['shares_count'] : null,
            ];
        }

        return [
            'data'   => $normalizedList,
            'total'  => count($normalizedList),
            'paging' => $raw['paging'] ?? null,
        ];
    }

    /**
     * Ambil semua postingan Page dengan pagination lengkap + status is_complete.
     *
     * @param  int  $maxPages  Batas maksimal halaman (default 10)
     * @param  bool  $withInsights
     * @return array{data: array, is_complete: bool, pages_fetched: int}
     */
    public function getAllNormalizedPostsWithStatus(int $maxPages = 10, bool $withInsights = true): array
    {
        $allPosts = [];
        $after = null;
        $pagesCount = 0;
        $isComplete = false;

        do {
            $result = $this->getNormalizedPosts(50, $after, $withInsights);
            $allPosts = array_merge($allPosts, $result['data']);
            $pagesCount++;

            $after = $result['paging']['cursors']['after'] ?? null;
            $hasNext = ! empty($result['paging']['next']) && ! empty($after);

            if (! $hasNext) {
                $isComplete = true;
                break;
            }
        } while ($hasNext && $pagesCount < $maxPages);

        return [
            'data'          => $allPosts,
            'is_complete'   => $isComplete,
            'pages_fetched' => $pagesCount,
        ];
    }

    /**
     * Helper: Ambil semua postingan tanpa status kelengkapan.
     */
    public function getAllNormalizedPosts(int $maxPages = 10, bool $withInsights = true): array
    {
        return $this->getAllNormalizedPostsWithStatus($maxPages, $withInsights)['data'];
    }

    /**
     * Helper: Generate judul ringkas dari pesan / caption postingan Facebook.
     */
    protected function generateTitleFromMessage(?string $message, ?string $timestamp): string
    {
        if (! empty($message)) {
            $clean = trim(preg_replace('/\s+/', ' ', $message));
            if (! empty($clean)) {
                return mb_strimwidth($clean, 0, 120, '...');
            }
        }

        $dateFormatted = $timestamp ? date('d-m-Y', strtotime($timestamp)) : date('d-m-Y');
        return "Facebook Post {$dateFormatted}";
    }
}
