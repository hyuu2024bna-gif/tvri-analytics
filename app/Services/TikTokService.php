<?php

namespace App\Services;

use App\Models\SocialAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokService
{
    /**
     * URL dasar endpoint OAuth Authorization TikTok
     */
    public const AUTH_BASE_URL = 'https://www.tiktok.com/v2/auth/authorize/';

    /**
     * URL dasar endpoint Open API TikTok v2
     */
    public const API_BASE_URL = 'https://open.tiktokapis.com/v2';

    /**
     * URL endpoint pertukaran & refresh token
     */
    public const TOKEN_URL = 'https://open.tiktokapis.com/v2/oauth/token/';

    /**
     * Scopes yang diminta saat OAuth.
     * Hanya request scope yang sudah dikonfigurasi & disetujui di TikTok Developer Portal.
     * user.info.stats DISERTAKAN untuk membaca follower_count, following_count, likes_count, video_count.
     */
    public const DEFAULT_SCOPES = [
        'user.info.basic',
        'user.info.stats',
        'video.list',
    ];

    /**
     * Field profil dasar — tersedia di scope user.info.basic.
     */
    public const BASIC_INFO_FIELDS = ['open_id', 'union_id', 'avatar_url', 'display_name'];

    /**
     * Field statistik akun — memerlukan scope user.info.stats.
     * Tidak tersedia di Sandbox / aplikasi tanpa scope user.info.stats.
     */
    public const STATS_FIELDS = ['follower_count', 'following_count', 'likes_count', 'video_count'];

    protected ?string $clientKey;
    protected ?string $clientSecret;
    protected ?string $redirectUri;

    /**
     * Timeout request HTTP dalam detik
     */
    protected int $timeout = 15;

    /**
     * Jumlah maksimum percobaan retry untuk kegagalan sementara
     */
    protected int $maxRetries = 3;

    /**
     * Delay retry dalam milidetik
     */
    protected int $retryDelayMs = 200;

    /**
     * Batas maksimum halaman pagination yang dikonfigurasi
     */
    protected int $maxPages;

    public function __construct(
        ?string $clientKey = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null,
        ?int $maxPages = null
    ) {
        $this->clientKey = $clientKey ?? config('services.tiktok.client_key');
        $this->clientSecret = $clientSecret ?? config('services.tiktok.client_secret');
        $this->redirectUri = $redirectUri ?? config('services.tiktok.redirect_uri');
        $this->maxPages = $maxPages ?? ((int) config('services.tiktok.max_pages') ?: self::DEFAULT_MAX_PAGES);
    }

    /**
     * Dapatkan batas maksimum halaman pagination yang dikonfigurasi.
     */
    public function getMaxPages(): int
    {
        return $this->maxPages;
    }

    /**
     * Dapatkan Client Key yang dikonfigurasi.
     */
    public function getClientKey(): ?string
    {
        return $this->clientKey;
    }

    /**
     * Dapatkan Redirect URI yang dikonfigurasi.
     */
    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    /**
     * Buat URL otorisasi OAuth 2.0 untuk user login/connect TikTok.
     *
     * @param  string|null  $state  CSRF token / state parameter
     * @param  array<string>  $scopes  Daftar scope yang diminta
     * @return string URL otorisasi lengkap
     *
     * @throws \RuntimeException jika client_key atau redirect_uri belum dikonfigurasi
     */
    public function getAuthorizationUrl(?string $state = null, array $scopes = self::DEFAULT_SCOPES): string
    {
        if (empty($this->clientKey)) {
            throw new \RuntimeException('TikTok Client Key belum dikonfigurasi pada config/services.php atau environment.');
        }

        if (empty($this->redirectUri)) {
            throw new \RuntimeException('TikTok Redirect URI belum dikonfigurasi pada config/services.php atau environment.');
        }

        $params = [
            'client_key'    => $this->clientKey,
            'scope'         => implode(',', $scopes),
            'response_type' => 'code',
            'redirect_uri'  => $this->redirectUri,
        ];

        if (! empty($state)) {
            $params['state'] = $state;
        }

        return self::AUTH_BASE_URL . '?' . http_build_query($params);
    }

    /**
     * Tukarkan authorization code dari callback OAuth menjadi access token & refresh token.
     *
     * @param  string  $code  Authorization code yang didapat dari callback
     * @return array{
     *     access_token: string,
     *     expires_in: int,
     *     open_id: string,
     *     refresh_token: ?string,
     *     refresh_expires_in: ?int,
     *     scope: ?string,
     *     token_type: string
     * }
     *
     * @throws \RuntimeException jika kredensial tidak lengkap atau request token gagal
     */
    public function exchangeCodeForToken(string $code): array
    {
        if (empty($this->clientKey) || empty($this->clientSecret)) {
            throw new \RuntimeException('TikTok Client Key atau Client Secret belum dikonfigurasi.');
        }

        if (empty($code)) {
            throw new \RuntimeException('Authorization code tidak boleh kosong.');
        }

        $payload = [
            'client_key'    => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUri,
        ];

        $response = $this->executePostFormWithRetry(self::TOKEN_URL, $payload, 'exchangeCodeForToken');

        $data = $response->json() ?? [];

        // Validasi response sukses OAuth TikTok
        if (isset($data['error']) || ! isset($data['access_token'])) {
            $errorMsg = $data['error_description'] ?? ($data['error'] ?? 'Unknown token exchange error');
            $logId = $data['log_id'] ?? 'unknown';

            Log::error('TikTok OAuth token exchange failed', [
                'error_type' => $data['error'] ?? 'missing_access_token',
                'log_id'     => $logId,
            ]);

            throw new \RuntimeException("Gagal menukarkan authorization code TikTok: {$errorMsg} (log_id: {$logId})");
        }

        return [
            'access_token'       => (string) $data['access_token'],
            'expires_in'         => (int) ($data['expires_in'] ?? 86400),
            'open_id'            => (string) ($data['open_id'] ?? ''),
            'refresh_token'      => isset($data['refresh_token']) ? (string) $data['refresh_token'] : null,
            'refresh_expires_in' => isset($data['refresh_expires_in']) ? (int) $data['refresh_expires_in'] : null,
            'scope'              => $data['scope'] ?? null,
            'token_type'         => $data['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * Perbarui access token menggunakan refresh token.
     *
     * @param  string  $refreshToken  Refresh token yang masih valid
     * @return array{
     *     access_token: string,
     *     expires_in: int,
     *     open_id: ?string,
     *     refresh_token: ?string,
     *     refresh_expires_in: ?int,
     *     scope: ?string,
     *     token_type: string
     * }
     *
     * @throws \RuntimeException jika refresh token gagal
     */
    public function refreshToken(string $refreshToken): array
    {
        if (empty($this->clientKey) || empty($this->clientSecret)) {
            throw new \RuntimeException('TikTok Client Key atau Client Secret belum dikonfigurasi.');
        }

        if (empty($refreshToken)) {
            throw new \RuntimeException('Refresh token tidak boleh kosong.');
        }

        $payload = [
            'client_key'    => $this->clientKey,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $response = $this->executePostFormWithRetry(self::TOKEN_URL, $payload, 'refreshToken');

        $data = $response->json() ?? [];

        if (isset($data['error']) || ! isset($data['access_token'])) {
            $errorMsg = $data['error_description'] ?? ($data['error'] ?? 'Unknown token refresh error');
            $logId = $data['log_id'] ?? 'unknown';

            Log::error('TikTok OAuth token refresh failed', [
                'error_type' => $data['error'] ?? 'missing_access_token',
                'log_id'     => $logId,
            ]);

            throw new \RuntimeException("Gagal memperbarui access token TikTok: {$errorMsg} (log_id: {$logId})");
        }

        return [
            'access_token'       => (string) $data['access_token'],
            'expires_in'         => (int) ($data['expires_in'] ?? 86400),
            'open_id'            => isset($data['open_id']) ? (string) $data['open_id'] : null,
            'refresh_token'      => isset($data['refresh_token']) ? (string) $data['refresh_token'] : null,
            'refresh_expires_in' => isset($data['refresh_expires_in']) ? (int) $data['refresh_expires_in'] : null,
            'scope'              => $data['scope'] ?? null,
            'token_type'         => $data['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * Perbarui access_token untuk SocialAccount yang diberikan menggunakan refresh_token.
     *
     * Method ini menangani seluruh lifecycle token secara aman:
     * 1. Validasi bahwa refresh_token tersedia dan belum expired.
     * 2. Panggil TikTok OAuth /v2/oauth/token/ dengan grant_type=refresh_token.
     * 3. Simpan token baru (access_token, expires_at, refresh_token, refresh_expires_at) ke database.
     * 4. Return SocialAccount yang sudah diperbarui.
     *
     * Aturan keamanan:
     * - Token tidak pernah di-log.
     * - Jika refresh gagal, account TIDAK dihapus dan data konten TIDAK disentuh.
     * - Jika refresh_token expired atau invalid_grant, RuntimeException dilempar agar caller bisa
     *   memberikan notifikasi "OAuth ulang diperlukan" tanpa crash.
     *
     * @param  SocialAccount  $account  Account TikTok yang access_token-nya akan diperbarui
     * @return SocialAccount  Account yang sudah diperbarui dengan token baru
     *
     * @throws \RuntimeException jika refresh_token tidak tersedia, sudah expired, atau refresh gagal
     */
    public function refreshAccessToken(SocialAccount $account): SocialAccount
    {
        // Guard 1: Pastikan refresh_token tersedia
        $currentRefreshToken = $account->refresh_token;
        if (empty($currentRefreshToken)) {
            throw new \RuntimeException(
                'Refresh token belum tersedia pada akun TikTok ini. OAuth ulang diperlukan via /tiktok/connect.'
            );
        }

        // Guard 2: Pastikan refresh_token belum expired
        if ($account->refresh_expires_at && $account->refresh_expires_at->isPast()) {
            $expiredAt = $account->refresh_expires_at->toIso8601String();
            Log::warning('[TIKTOK TOKEN] Refresh token sudah kedaluwarsa', [
                'account_id'         => $account->id,
                'refresh_expires_at' => $expiredAt,
            ]);
            throw new \RuntimeException(
                "Refresh token TikTok sudah kedaluwarsa sejak {$expiredAt}. OAuth ulang diperlukan via /tiktok/connect."
            );
        }

        Log::info('[TIKTOK TOKEN] Memulai refresh access token', [
            'account_id' => $account->id,
            'username'   => $account->username,
        ]);

        // Panggil TikTok API untuk refresh token
        $tokenData = $this->refreshToken($currentRefreshToken);

        // Hitung waktu expiry baru secara presisi
        $newExpiresIn = isset($tokenData['expires_in']) && (int) $tokenData['expires_in'] > 0
            ? (int) $tokenData['expires_in']
            : 86400;
        $newExpiresAt = Carbon::now()->addSeconds($newExpiresIn);

        // Data yang akan diperbarui — minimal access_token dan expires_at
        $updateData = [
            'access_token' => $tokenData['access_token'],
            'expires_at'   => $newExpiresAt,
        ];

        // Perbarui refresh_token hanya jika TikTok mengembalikan refresh_token baru
        // (TikTok v2 kadang mengembalikan refresh_token baru, kadang tidak)
        if (! empty($tokenData['refresh_token'])) {
            $updateData['refresh_token'] = $tokenData['refresh_token'];

            if (isset($tokenData['refresh_expires_in']) && (int) $tokenData['refresh_expires_in'] > 0) {
                $updateData['refresh_expires_at'] = Carbon::now()->addSeconds((int) $tokenData['refresh_expires_in']);
            }
        }

        // Simpan ke database — enkripsi dilakukan otomatis via mutator model
        $account->update($updateData);

        Log::info('[TIKTOK TOKEN] Access token berhasil diperbarui via refresh', [
            'account_id'  => $account->id,
            'username'    => $account->username,
            'expires_at'  => $newExpiresAt->toIso8601String(),
            'token_renewed' => ! empty($tokenData['refresh_token']),
        ]);

        return $account->fresh();
    }

    /**
     * Ambil informasi profil dasar user TikTok.
     *
     * @param  string  $accessToken  Bearer access token
     * @param  array<string>  $fields  Field profil yang diminta
     * @return array{
     *     open_id: ?string,
     *     union_id: ?string,
     *     avatar_url: ?string,
     *     display_name: ?string
     * }
     */
    public function getUserInfo(
        string $accessToken,
        array $fields = ['open_id', 'union_id', 'avatar_url', 'display_name']
    ): array {
        $stats = $this->getAccountStats($accessToken, $fields);

        return [
            'open_id'      => $stats['open_id'] ?? null,
            'union_id'     => $stats['union_id'] ?? null,
            'avatar_url'   => $stats['avatar_url'] ?? null,
            'display_name' => $stats['display_name'] ?? null,
        ];
    }


    /**
     * Ambil statistik akun dan profil TikTok.
     * Menerapkan Strict NULL Semantics: metrik yang tidak dikembalikan oleh API
     * dipertahankan bernilai NULL (tidak diubah menjadi 0).
     *
     * @param  string  $accessToken  Bearer access token
     * @param  array<string>  $fields  Field yang diminta
     * @return array{
     *     open_id: ?string,
     *     union_id: ?string,
     *     avatar_url: ?string,
     *     display_name: ?string,
     *     follower_count: ?int,
     *     following_count: ?int,
     *     likes_count: ?int,
     *     video_count: ?int
     * }
     */
    public function getAccountStats(
        string $accessToken,
        array $fields = self::BASIC_INFO_FIELDS
    ): array {
        if (empty($accessToken)) {
            throw new \RuntimeException('Access token tidak boleh kosong.');
        }

        $url = self::API_BASE_URL . '/user/info/?fields=' . implode(',', $fields);

        $response = $this->executeGetWithRetry($url, $accessToken, 'getAccountStats');

        $body = $response->json() ?? [];

        // Periksa error response dari TikTok API
        $this->assertApiSuccess($body, 'getAccountStats');

        $user = $body['data']['user'] ?? [];

        if (empty($user)) {
            Log::warning('TikTok API: response user info kosong', ['response' => $body]);
            throw new \RuntimeException('Response dari TikTok API tidak berisi data user yang valid.');
        }

        return [
            'open_id'         => isset($user['open_id']) ? (string) $user['open_id'] : null,
            'union_id'        => isset($user['union_id']) ? (string) $user['union_id'] : null,
            'avatar_url'      => isset($user['avatar_url']) ? (string) $user['avatar_url'] : null,
            'display_name'    => isset($user['display_name']) ? (string) $user['display_name'] : null,
            'follower_count'  => (isset($user['follower_count']) && $user['follower_count'] !== null) ? (int) $user['follower_count'] : null,
            'following_count' => (isset($user['following_count']) && $user['following_count'] !== null) ? (int) $user['following_count'] : null,
            'likes_count'     => (isset($user['likes_count']) && $user['likes_count'] !== null) ? (int) $user['likes_count'] : null,
            'video_count'     => (isset($user['video_count']) && $user['video_count'] !== null) ? (int) $user['video_count'] : null,
        ];
    }

    /**
     * Ambil 1 halaman daftar video TikTok menggunakan cursor pagination.
     *
     * @param  string  $accessToken  Bearer access token
     * @param  int  $maxCount  Jumlah video per page (1..20)
     * @param  int|string|null  $cursor  Cursor pagination
     * @param  array<string>  $fields  Field yang diminta
     * @return array{
     *     videos: array<int, array>,
     *     cursor: int|string|null,
     *     has_more: bool
     * }
     */
    public function getVideoList(
        string $accessToken,
        int $maxCount = 20,
        int|string|null $cursor = null,
        array $fields = ['id', 'title', 'video_description', 'duration', 'cover_image_url', 'share_url', 'embed_link', 'like_count', 'comment_count', 'share_count', 'view_count', 'create_time']
    ): array {
        if (empty($accessToken)) {
            throw new \RuntimeException('Access token tidak boleh kosong.');
        }

        $url = self::API_BASE_URL . '/video/list/?fields=' . implode(',', $fields);

        $clampedMaxCount = min(20, max(1, $maxCount));

        $payload = [
            'max_count' => $clampedMaxCount,
        ];

        if ($cursor !== null) {
            $payload['cursor'] = is_numeric($cursor) ? (int) $cursor : $cursor;
        } else {
            $payload['cursor'] = 0;
        }

        $response = $this->executePostJsonWithRetry($url, $payload, $accessToken, 'getVideoList');

        $body = $response->json() ?? [];

        // Periksa error response dari TikTok API
        $this->assertApiSuccess($body, 'getVideoList');

        $data = $body['data'] ?? null;
        if (! is_array($data)) {
            Log::warning('TikTok API: response video list tidak memiliki field "data"', ['response' => $body]);
            throw new \RuntimeException('Response dari TikTok API tidak memiliki field data yang valid.');
        }

        $rawVideos = $data['videos'] ?? [];
        $responseCursor = $data['cursor'] ?? null;
        $hasMore = (bool) ($data['has_more'] ?? false);

        $normalizedVideos = [];
        if (is_array($rawVideos)) {
            foreach ($rawVideos as $rawVideo) {
                if (is_array($rawVideo)) {
                    $normalizedVideos[] = $this->normalizeVideoItem($rawVideo);
                }
            }
        }

        return [
            'videos'   => $normalizedVideos,
            'cursor'   => $responseCursor,
            'has_more' => $hasMore,
        ];
    }
    /**
     * Batas default halaman pagination.
     * Digunakan sebagai fallback aman jika env TIKTOK_MAX_PAGES tidak tersedia.
     * (100 halaman × 20 = 2.000 video)
     */
    public const DEFAULT_MAX_PAGES = 100;

    /**
     * Ambil seluruh daftar video secara iteratif dengan fail-safe pagination.
     *
     * @param  string  $accessToken  Bearer access token
     * @param  int|null  $maxPages  Batas fail-safe iterasi halaman.
     *                              Jika null, menggunakan $this->maxPages (dari config/env).
     *                              Fallback akhir: DEFAULT_MAX_PAGES.
     * @param  int  $perPage  Jumlah per halaman (default 20 = batas API)
     * @return array{
     *     data: array<int, array>,
     *     is_complete: bool,
     *     total_fetched: int
     * }
     */
    public function getAllNormalizedVideos(string $accessToken, ?int $maxPages = null, int $perPage = 20): array
    {
        // Resolusi batas halaman: argumen eksplisit → config/env ($this->maxPages) → DEFAULT_MAX_PAGES
        $limitPages = $maxPages ?? $this->maxPages;

        $allVideos = [];
        $cursor = null;
        $hasMore = true;
        $page = 0;
        $previousCursor = null;
        $isComplete = false;

        while ($hasMore && $page < $limitPages) {
            $page++;

            // Pacing delay antar request halaman untuk mencegah HTTP 429 burst (400ms di production)
            if ($page > 1 && ! app()->environment('testing')) {
                usleep(400000);
            }

            try {
                $result = $this->getVideoList($accessToken, $perPage, $cursor);
            } catch (\Throwable $e) {
                Log::error('TikTok getAllNormalizedVideos: gagal mengambil halaman video', [
                    'page'      => $page,
                    'cursor'    => $cursor,
                    'error_msg' => $e->getMessage(),
                ]);

                // Jika sudah ada data yang berhasil diambil sebelumnya, return data parsial dengan is_complete = false
                return [
                    'data'          => $allVideos,
                    'is_complete'   => false,
                    'total_fetched' => count($allVideos),
                ];
            }

            $videos = $result['videos'] ?? [];
            $newCursor = $result['cursor'] ?? null;
            $hasMore = (bool) ($result['has_more'] ?? false);

            if (! empty($videos)) {
                foreach ($videos as $v) {
                    $allVideos[] = $v;
                }
            }

            // Fail-safe 1: Response kosong walaupun has_more true -> hentikan
            if (empty($videos)) {
                $hasMore = false;
                break;
            }

            // Fail-safe 2: Cursor tidak berubah / identik dengan sebelumnya -> cegah infinite loop
            if ($newCursor !== null && $newCursor === $previousCursor) {
                Log::warning('TikTok pagination: cursor tidak berubah, menghentikan iterasi', [
                    'page'   => $page,
                    'cursor' => $newCursor,
                ]);
                break;
            }

            $previousCursor = $newCursor;
            $cursor = $newCursor;
        }

        // is_complete true hanya jika has_more sudah false (seluruh data habis terbaca)
        $isComplete = (! $hasMore);

        return [
            'data'          => $allVideos,
            'is_complete'   => $isComplete,
            'total_fetched' => count($allVideos),
        ];
    }

    /**
     * Normalisasi 1 item data video dari API TikTok ke struktur standar TVRI Analytics.
     * Menerapkan Strict NULL Semantics: metrik yang tidak dikembalikan dibiarkan bernilai NULL.
     *
     * @param  array  $video  Raw video object dari TikTok API
     * @return array{
     *     external_id: string,
     *     title: string,
     *     description: ?string,
     *     url: string,
     *     thumbnail_url: ?string,
     *     duration: ?int,
     *     upload_date: ?string,
     *     views: ?int,
     *     likes: ?int,
     *     comments: ?int,
     *     shares: ?int
     * }
     */
    public function normalizeVideoItem(array $video): array
    {
        $id = (string) ($video['id'] ?? '');
        $title = (string) ($video['title'] ?? ($video['video_description'] ?? 'TikTok Video'));
        $description = isset($video['video_description']) ? (string) $video['video_description'] : null;

        // URL share video
        $url = ! empty($video['share_url'])
            ? (string) $video['share_url']
            : (! empty($id) ? "https://www.tiktok.com/@video/{$id}" : '');

        // Thumbnail cover
        $thumbnailUrl = isset($video['cover_image_url']) && ! empty($video['cover_image_url'])
            ? (string) $video['cover_image_url']
            : null;

        // Tanggal upload dari UNIX timestamp create_time
        $uploadDate = null;
        if (isset($video['create_time']) && is_numeric($video['create_time']) && (int) $video['create_time'] > 0) {
            $uploadDate = Carbon::createFromTimestamp((int) $video['create_time'])->toDateString();
        }

        // Strict NULL Semantics untuk metrik
        $views = (isset($video['view_count']) && $video['view_count'] !== null) ? (int) $video['view_count'] : null;
        $likes = (isset($video['like_count']) && $video['like_count'] !== null) ? (int) $video['like_count'] : null;
        $comments = (isset($video['comment_count']) && $video['comment_count'] !== null) ? (int) $video['comment_count'] : null;
        $shares = (isset($video['share_count']) && $video['share_count'] !== null) ? (int) $video['share_count'] : null;
        $duration = (isset($video['duration']) && $video['duration'] !== null) ? (int) $video['duration'] : null;

        return [
            'external_id'   => $id,
            'title'         => $title,
            'description'   => $description,
            'url'           => $url,
            'thumbnail_url' => $thumbnailUrl,
            'duration'      => $duration,
            'upload_date'   => $uploadDate,
            'views'         => $views,
            'likes'         => $likes,
            'comments'      => $comments,
            'shares'        => $shares,
        ];
    }

    /**
     * Eksekusi HTTP POST form-urlencoded dengan retry untuk transient failure.
     */
    protected function executePostFormWithRetry(string $url, array $params, string $action): Response
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = Http::asForm()
                    ->timeout($this->timeout)
                    ->post($url, $params);

                if ($this->shouldRetryResponse($response) && $attempt < $this->maxRetries) {
                    $delayMicroseconds = $this->calculateRetryDelay($response, $attempt);
                    usleep($delayMicroseconds);
                    continue;
                }

                if (! $response->successful() && ! $this->isExpectedOauthError($response)) {
                    Log::error("TikTok API HTTP error ({$action})", [
                        'status' => $response->status(),
                        'action' => $action,
                    ]);
                }

                return $response;
            } catch (ConnectionException $e) {
                Log::warning("TikTok API ConnectionException on attempt {$attempt} ({$action})", [
                    'message' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    throw new \RuntimeException("Gagal terhubung ke TikTok API setelah {$this->maxRetries} percobaan: {$e->getMessage()}");
                }

                usleep($this->retryDelayMs * 1000 * $attempt);
            }
        }

        throw new \RuntimeException("Gagal mengeksekusi request ke TikTok API ({$action}).");
    }

    /**
     * Eksekusi HTTP GET dengan Bearer token dan retry untuk transient failure.
     */
    protected function executeGetWithRetry(string $url, string $accessToken, string $action): Response
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = Http::withToken($accessToken)
                    ->timeout($this->timeout)
                    ->get($url);

                if ($this->shouldRetryResponse($response) && $attempt < $this->maxRetries) {
                    $delayMicroseconds = $this->calculateRetryDelay($response, $attempt);
                    usleep($delayMicroseconds);
                    continue;
                }

                if (! $response->successful()) {
                    $status = $response->status();

                    // Coba baca TikTok error body untuk pesan yang lebih informatif
                    $body = $response->json() ?? [];
                    $tiktokCode = null;
                    $tiktokMsg  = null;

                    if (isset($body['error']) && is_array($body['error'])) {
                        $tiktokCode = $body['error']['code'] ?? null;
                        $tiktokMsg  = $body['error']['message'] ?? null;
                    }

                    Log::error("TikTok API HTTP error ({$action})", [
                        'status'      => $status,
                        'action'      => $action,
                        'error_code'  => $tiktokCode,
                    ]);

                    if ($status === 401 || $status === 403) {
                        // Bedakan scope error dari invalid token
                        if ($tiktokCode === 'scope_not_authorized') {
                            throw new \RuntimeException(
                                "Scope tidak diizinkan oleh TikTok API (HTTP {$status}): [{$tiktokCode}] {$tiktokMsg}. "
                                . 'Pastikan scope yang diminta sudah dikonfigurasi di TikTok Developer Portal.'
                            );
                        }

                        throw new \RuntimeException(
                            "Akses ditolak oleh TikTok API (HTTP {$status}). Token mungkin tidak valid atau kedaluwarsa."
                            . ($tiktokCode ? " [{$tiktokCode}]" : '')
                        );
                    }

                    throw new \RuntimeException("TikTok API mengembalikan error HTTP {$status} pada aksi {$action}.");
                }

                return $response;
            } catch (ConnectionException $e) {
                Log::warning("TikTok API ConnectionException on attempt {$attempt} ({$action})", [
                    'message' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    throw new \RuntimeException("Gagal terhubung ke TikTok API setelah {$this->maxRetries} percobaan: {$e->getMessage()}");
                }

                usleep($this->retryDelayMs * 1000 * $attempt);
            }
        }

        throw new \RuntimeException("Gagal mengeksekusi request GET ke TikTok API ({$action}).");
    }

    /**
     * Eksekusi HTTP POST JSON dengan Bearer token dan retry untuk transient failure.
     */
    protected function executePostJsonWithRetry(string $url, array $payload, string $accessToken, string $action): Response
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = Http::withToken($accessToken)
                    ->timeout($this->timeout)
                    ->post($url, $payload);

                if ($this->shouldRetryResponse($response) && $attempt < $this->maxRetries) {
                    $delayMicroseconds = $this->calculateRetryDelay($response, $attempt);
                    usleep($delayMicroseconds);
                    continue;
                }

                if (! $response->successful()) {
                    $status = $response->status();

                    // Baca TikTok error body untuk pesan yang lebih informatif
                    $body       = $response->json() ?? [];
                    $tiktokCode = null;
                    $tiktokMsg  = null;

                    if (isset($body['error']) && is_array($body['error'])) {
                        $tiktokCode = $body['error']['code'] ?? null;
                        $tiktokMsg  = $body['error']['message'] ?? null;
                    }

                    Log::error("TikTok API HTTP error ({$action})", [
                        'status'     => $status,
                        'action'     => $action,
                        'error_code' => $tiktokCode,
                    ]);

                    if ($status === 401 || $status === 403) {
                        if ($tiktokCode === 'scope_not_authorized') {
                            throw new \RuntimeException(
                                "Scope tidak diizinkan oleh TikTok API (HTTP {$status}): [{$tiktokCode}] {$tiktokMsg}. "
                                . 'Pastikan scope yang diminta sudah dikonfigurasi di TikTok Developer Portal.'
                            );
                        }

                        throw new \RuntimeException(
                            "Akses ditolak oleh TikTok API (HTTP {$status}). Token mungkin tidak valid atau kedaluwarsa."
                            . ($tiktokCode ? " [{$tiktokCode}]" : '')
                        );
                    }

                    throw new \RuntimeException("TikTok API mengembalikan error HTTP {$status} pada aksi {$action}.");
                }

                return $response;
            } catch (ConnectionException $e) {
                Log::warning("TikTok API ConnectionException on attempt {$attempt} ({$action})", [
                    'message' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    throw new \RuntimeException("Gagal terhubung ke TikTok API setelah {$this->maxRetries} percobaan: {$e->getMessage()}");
                }

                usleep($this->retryDelayMs * 1000 * $attempt);
            }
        }

        throw new \RuntimeException("Gagal mengeksekusi request POST ke TikTok API ({$action}).");
    }

    /**
     * Hitung durasi jeda retry (dalam mikrodetik).
     * Menerapkan handling khusus untuk HTTP 429 Rate Limit (Retry-After header atau exponential backoff).
     */
    protected function calculateRetryDelay(Response $response, int $attempt): int
    {
        // Khusus environment testing: pertahankan delay minimal agar unit tests cepat selesai
        if (app()->environment('testing')) {
            return $this->retryDelayMs * 1000 * $attempt;
        }

        if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After');
            if ($retryAfter > 0) {
                Log::warning("[TIKTOK RATE LIMIT] Menerima header Retry-After {$retryAfter} detik dari TikTok API");
                return $retryAfter * 1000 * 1000;
            }

            // Backoff untuk 429: attempt 1 = 5s, attempt 2 = 10s, attempt 3 = 15s
            $backoffSeconds = $attempt * 5;
            Log::warning("[TIKTOK RATE LIMIT] HTTP 429 terdeteksi. Melakukan backoff {$backoffSeconds}s (attempt {$attempt}/{$this->maxRetries})");
            return $backoffSeconds * 1000 * 1000;
        }

        // Server error 5xx: delay standar bertahap
        return $this->retryDelayMs * 1000 * $attempt;
    }

    /**
     * Tentukan apakah response HTTP perlu di-retry (Rate Limit 429 atau Server Error 5xx).
     */
    protected function shouldRetryResponse(Response $response): bool
    {
        $status = $response->status();

        // 429 = Rate Limited, 500+ = Server Error (500, 502, 503, 504)
        return $status === 429 || ($status >= 500 && $status <= 599);
    }

    /**
     * Tentukan apakah error response OAuth adalah expected client-side error (misal invalid_grant 400).
     */
    protected function isExpectedOauthError(Response $response): bool
    {
        $status = $response->status();
        return $status === 400 || $status === 401;
    }

    /**
     * Validasi status kode 'ok' pada body response TikTok API v2.
     *
     * @throws \RuntimeException jika response mengandung error
     */
    protected function assertApiSuccess(array $body, string $action): void
    {
        if (isset($body['error'])) {
            $err = $body['error'];
            $code = is_array($err) ? ($err['code'] ?? 'unknown') : $err;
            $message = is_array($err) ? ($err['message'] ?? '') : ($body['error_description'] ?? '');
            $logId = is_array($err) ? ($err['log_id'] ?? ($body['log_id'] ?? '')) : ($body['log_id'] ?? '');

            if ($code !== 'ok' && ! empty($code)) {
                Log::error("TikTok API response error ({$action})", [
                    'code'   => $code,
                    'log_id' => $logId,
                ]);

                throw new \RuntimeException("TikTok API error ({$action}): [{$code}] {$message} (log_id: {$logId})");
            }
        }
    }
}
