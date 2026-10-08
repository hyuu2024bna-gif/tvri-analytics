<?php

namespace App\Services;

use App\Models\ChannelStatsDaily;
use App\Models\Platform;
use App\Models\SocialAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YoutubeAnalyticsService
{
    protected string $analyticsBaseUrl = 'https://youtubeanalytics.googleapis.com/v2';
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';

    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $refreshToken;
    protected ?string $channelId;
    protected ?string $redirectUri;
    protected ?string $directAccessToken = null;

    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $refreshToken = null,
        ?string $channelId = null,
        ?string $redirectUri = null
    ) {
        $this->clientId = $clientId ?? config('services.youtube.client_id');
        $this->clientSecret = $clientSecret ?? config('services.youtube.client_secret');
        $this->refreshToken = $refreshToken ?? config('services.youtube.refresh_token');
        $this->channelId = $channelId ?? config('services.youtube.channel_id');
        $this->redirectUri = $redirectUri ?? config('services.youtube.redirect_uri');

        // Jika refresh token belum terkonfigurasi di env, coba temukan di SocialAccount
        if (! $this->refreshToken) {
            $this->resolveFromSocialAccount();
        }
    }

    /**
     * Set direct access token (biasa digunakan untuk testing atau short-lived bearer token).
     */
    public function setAccessToken(?string $token): self
    {
        $this->directAccessToken = $token;
        return $this;
    }

    /**
     * Cek apakah kredensial OAuth YouTube Analytics sudah tersedia.
     */
    public function hasCredentials(): bool
    {
        if (! empty($this->directAccessToken)) {
            return true;
        }

        if (Cache::has('youtube_analytics_access_token')) {
            return true;
        }

        return ! empty($this->clientId) && ! empty($this->clientSecret) && ! empty($this->refreshToken);
    }

    /**
     * Dapatkan Access Token OAuth yang valid (dari direct, cache, atau refresh token).
     */
    public function getAccessToken(): ?string
    {
        if (! empty($this->directAccessToken)) {
            return $this->directAccessToken;
        }

        $cached = Cache::get('youtube_analytics_access_token');
        if ($cached) {
            return $cached;
        }

        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->refreshToken)) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->retry(2, 500, throw: false)
                ->post($this->tokenUrl, [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type'    => 'refresh_token',
                ]);

            if (! $response->successful()) {
                Log::error('YouTube Analytics OAuth token refresh failed', [
                    'status' => $response->status(),
                    'error'  => $response->json('error') ?? 'unknown',
                ]);
                return null;
            }

            $accessToken = $response->json('access_token');
            $expiresIn = (int) $response->json('expires_in', 3600);

            if (! $accessToken) {
                Log::error('YouTube Analytics OAuth response missing access_token');
                return null;
            }

            // Simpan di cache (kurangi 120 detik untuk safety margin)
            $ttl = max(60, $expiresIn - 120);
            Cache::put('youtube_analytics_access_token', $accessToken, $ttl);

            return $accessToken;
        } catch (\Throwable $e) {
            Log::error('YouTube Analytics OAuth request exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mengambil data pertumbuhan subscriber exact (subscribersGained, subscribersLost, net)
     * dari YouTube Analytics API.
     *
     * @return array{subscribersGained: int, subscribersLost: int, net: int, startDate: string, endDate: string}|null
     */
    public function getSubscriberGrowth(?string $startDate = null, ?string $endDate = null, ?string $channelId = null): ?array
    {
        if (! $this->hasCredentials()) {
            return null;
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return null;
        }

        // Normalisasi tanggal YYYY-MM-DD
        $resolvedEndDate = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::today()->toDateString();

        if ($startDate) {
            $resolvedStartDate = Carbon::parse($startDate)->toDateString();
        } else {
            // Jika startDate null (periode 'all' / Semua Waktu), gunakan tanggal snapshot terlama atau baseline 5 tahun
            $earliest = null;
            try {
                $earliest = ChannelStatsDaily::min('tanggal');
            } catch (\Throwable) {
                // Abaikan jika tabel belum siap
            }
            $resolvedStartDate = $earliest ? Carbon::parse($earliest)->toDateString() : Carbon::now()->subYears(5)->toDateString();
        }

        // Pastikan startDate <= endDate
        if ($resolvedStartDate > $resolvedEndDate) {
            $temp = $resolvedStartDate;
            $resolvedStartDate = $resolvedEndDate;
            $resolvedEndDate = $temp;
        }

        $targetChannel = $channelId ?? $this->channelId;
        $idsParam = $targetChannel ? "channel=={$targetChannel}" : 'channel==MINE';

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->retry(2, 500, throw: false)
                ->get("{$this->analyticsBaseUrl}/reports", [
                    'ids'        => $idsParam,
                    'startDate'  => $resolvedStartDate,
                    'endDate'    => $resolvedEndDate,
                    'metrics'    => 'subscribersGained,subscribersLost',
                ]);

            if ($response->status() === 401) {
                // Token kedaluwarsa atau dibatalkan, bersihkan cache
                Cache::forget('youtube_analytics_access_token');
                Log::warning('YouTube Analytics API 401 Unauthorized, token cache cleared');
                return null;
            }

            if (! $response->successful()) {
                Log::warning('YouTube Analytics API request failed', [
                    'status' => $response->status(),
                    'error'  => $response->json('error.message') ?? $response->json('error') ?? 'HTTP ' . $response->status(),
                ]);
                return null;
            }

            $data = $response->json();
            return $this->parseReportResponse($data ?? [], $resolvedStartDate, $resolvedEndDate);
        } catch (\Throwable $e) {
            Log::warning('YouTube Analytics API connection exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse hasil response dari YouTube Analytics API secara dinamis berdasarkan columnHeaders.
     */
    public function parseReportResponse(array $data, string $startDate, string $endDate): array
    {
        $columnHeaders = $data['columnHeaders'] ?? [];
        $gainedIndex = null;
        $lostIndex = null;

        foreach ($columnHeaders as $index => $col) {
            $name = $col['name'] ?? '';
            if ($name === 'subscribersGained') {
                $gainedIndex = $index;
            } elseif ($name === 'subscribersLost') {
                $lostIndex = $index;
            }
        }

        // Fallback default index jika header tidak eksplisit
        $gainedIndex = $gainedIndex ?? 0;
        $lostIndex = $lostIndex ?? 1;

        $rows = $data['rows'] ?? [];
        $subscribersGained = 0;
        $subscribersLost = 0;

        if (! empty($rows) && isset($rows[0])) {
            $row = $rows[0];
            $subscribersGained = isset($row[$gainedIndex]) ? (int) $row[$gainedIndex] : 0;
            $subscribersLost = isset($row[$lostIndex]) ? (int) $row[$lostIndex] : 0;
        }

        $net = $subscribersGained - $subscribersLost;

        return [
            'subscribersGained' => $subscribersGained,
            'subscribersLost'   => $subscribersLost,
            'net'               => $net,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
        ];
    }

    /**
     * Generate URL untuk Google OAuth Consent Screen.
     */
    public function getAuthorizationUrl(): string
    {
        $params = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/yt-analytics.readonly',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Tukar authorization code dengan access token & refresh token.
     */
    public function exchangeCodeForTokens(string $code): ?array
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->redirectUri)) {
            Log::error('YouTube Analytics OAuth code exchange failed: missing client_id, client_secret, or redirect_uri');
            return null;
        }

        try {
            $response = Http::asForm()->timeout(15)->post($this->tokenUrl, [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
            ]);

            if (! $response->successful()) {
                Log::error('YouTube Analytics OAuth exchangeCodeForTokens error', [
                    'status' => $response->status(),
                    'error'  => $response->json('error') ?? 'unknown',
                ]);
                return null;
            }

            $json = $response->json();
            $accessToken = $json['access_token'] ?? null;
            $refreshToken = $json['refresh_token'] ?? null;
            $expiresIn = (int) ($json['expires_in'] ?? 3600);

            if ($accessToken) {
                Cache::put('youtube_analytics_access_token', $accessToken, max(60, $expiresIn - 120));
            }

            if ($refreshToken) {
                $this->refreshToken = $refreshToken;
                $this->saveRefreshTokenToSocialAccount($refreshToken, $accessToken);
            }

            // Kembalikan status sukses tanpa membocorkan token di return
            return [
                'has_access_token'  => ! empty($accessToken),
                'has_refresh_token' => ! empty($refreshToken),
                'expires_in'        => $expiresIn,
            ];
        } catch (\Throwable $e) {
            Log::error('YouTube Analytics OAuth exchange exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Cari refresh token yang tersimpan di tabel social_accounts jika ada.
     */
    protected function resolveFromSocialAccount(): void
    {
        try {
            $platform = Platform::where('slug', 'youtube')->first();
            if (! $platform) {
                return;
            }

            $account = SocialAccount::where('platform_id', $platform->id)->first();
            if (! $account || ! $account->access_token) {
                return;
            }

            // Model SocialAccount otomatis mendekripsi access_token
            $tokenRaw = $account->access_token;
            $json = json_decode($tokenRaw, true);

            if (is_array($json) && isset($json['refresh_token'])) {
                $this->refreshToken = $json['refresh_token'];
            }
        } catch (\Throwable) {
            // Abaikan kegagalan akses database saat environment belum siap
        }
    }

    /**
     * Simpan refresh token ke SocialAccount untuk platform YouTube.
     */
    protected function saveRefreshTokenToSocialAccount(string $refreshToken, ?string $accessToken = null): void
    {
        try {
            $platform = Platform::where('slug', 'youtube')->first();
            if (! $platform) {
                return;
            }

            SocialAccount::updateOrCreate(
                ['platform_id' => $platform->id],
                [
                    'external_account_id' => $this->channelId ?? 'youtube_main',
                    'username'            => 'YouTube TVRI Aceh',
                    'access_token'        => json_encode([
                        'refresh_token' => $refreshToken,
                        'access_token'  => $accessToken,
                        'updated_at'    => Carbon::now()->toIso8601String(),
                    ]),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal menyimpan YouTube refresh token ke SocialAccount', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
