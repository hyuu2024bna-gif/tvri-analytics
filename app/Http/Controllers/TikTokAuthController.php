<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\SocialAccount;
use App\Services\TikTokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokAuthController extends Controller
{
    public function __construct(
        protected TikTokService $tiktokService
    ) {
    }

    /**
     * Redirect pengguna ke halaman otorisasi TikTok OAuth 2.0.
     */
    public function connect(Request $request): RedirectResponse
    {
        $clientKey = config('services.tiktok.client_key');
        $clientSecret = config('services.tiktok.client_secret');

        if (empty($clientKey) || empty($clientSecret)) {
            return redirect()->route('tiktok.status')
                ->with('error', 'TIKTOK_CLIENT_KEY dan TIKTOK_CLIENT_SECRET belum dikonfigurasi di file .env.');
        }

        // Generate cryptographic random state for CSRF mitigation
        $state = Str::random(40);
        $request->session()->put('tiktok_oauth_state', $state);

        try {
            $authorizationUrl = $this->tiktokService->getAuthorizationUrl($state);
        } catch (\Throwable $e) {
            Log::error('TikTok OAuth: gagal membuat authorization URL', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tiktok.status')
                ->with('error', 'Gagal memulai koneksi TikTok: ' . $e->getMessage());
        }

        return redirect($authorizationUrl);
    }

    /**
     * Callback handler setelah user memberikan otorisasi di TikTok.
     */
    public function callback(Request $request): RedirectResponse
    {
        // 1. Tangani jika ada error dari provider OAuth
        if ($request->has('error')) {
            $error = $request->query('error');
            $errorDescription = $request->query('error_description', $error);

            Log::warning('TikTok OAuth: otorisasi ditolak/gagal dari provider', [
                'error' => $error,
            ]);

            return redirect()->route('tiktok.status')
                ->with('error', "Otorisasi TikTok dibatalkan atau gagal: {$errorDescription}");
        }

        // 2. Validasi CSRF state
        $savedState = $request->session()->pull('tiktok_oauth_state');
        $receivedState = $request->query('state');

        if (empty($savedState) || empty($receivedState) || ! hash_equals($savedState, $receivedState)) {
            Log::warning('TikTok OAuth: state validation failed / mismatch');

            return redirect()->route('tiktok.status')
                ->with('error', 'State otorisasi tidak valid atau sesi telah kedaluwarsa. Silakan coba hubungkan ulang.');
        }

        // 3. Validasi authorization code
        $code = $request->query('code');
        if (empty($code)) {
            Log::warning('TikTok OAuth: authorization code missing');

            return redirect()->route('tiktok.status')
                ->with('error', 'Kode otorisasi (code) tidak diterima dari TikTok.');
        }

        // 4. Tukarkan authorization code menjadi access token
        try {
            $tokenData = $this->tiktokService->exchangeCodeForToken($code);
        } catch (\Throwable $e) {
            Log::error('TikTok OAuth: gagal menukarkan code', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tiktok.status')
                ->with('error', 'Gagal menukarkan kode otorisasi TikTok: ' . $e->getMessage());
        }

        // 5. Ambil data profil user via TikTokService
        $accessToken = $tokenData['access_token'] ?? null;
        if (empty($accessToken)) {
            return redirect()->route('tiktok.status')
                ->with('error', 'Access token tidak ditemukan dalam respons otorisasi.');
        }

        try {
            $userInfo = $this->tiktokService->getUserInfo($accessToken);
        } catch (\Throwable $e) {
            Log::error('TikTok OAuth: gagal mengambil info user', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('tiktok.status')
                ->with('error', 'Gagal mengambil informasi akun TikTok: ' . $e->getMessage());
        }

        // 6. Hitung waktu kedaluwarsa token secara presisi
        $expiresIn = isset($tokenData['expires_in']) && (int) $tokenData['expires_in'] > 0
            ? (int) $tokenData['expires_in']
            : 86400;
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        // Hitung expiry refresh_token jika tersedia
        $refreshExpiresAt = null;
        if (isset($tokenData['refresh_expires_in']) && (int) $tokenData['refresh_expires_in'] > 0) {
            $refreshExpiresAt = Carbon::now()->addSeconds((int) $tokenData['refresh_expires_in']);
        }

        // 7. Simpan / Perbarui record pada social_accounts (single account architecture)
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        $externalAccountId = ! empty($tokenData['open_id'])
            ? (string) $tokenData['open_id']
            : (! empty($userInfo['open_id']) ? (string) $userInfo['open_id'] : 'tiktok_account');

        $username = ! empty($userInfo['display_name'])
            ? (string) $userInfo['display_name']
            : 'TikTok Account';

        SocialAccount::updateOrCreate(
            ['platform_id' => $platform->id],
            [
                'external_account_id' => $externalAccountId,
                'username'            => $username,
                'access_token'        => $accessToken,             // Terenkripsi otomatis via mutator model SocialAccount
                'refresh_token'       => $tokenData['refresh_token'] ?? null, // Terenkripsi otomatis via mutator
                'connected_by'        => Auth::id(),
                'expires_at'          => $expiresAt,
                'refresh_expires_at'  => $refreshExpiresAt,
            ]
        );

        return redirect()->route('tiktok.status')
            ->with('success', "Akun TikTok {$username} berhasil dihubungkan ke TVRI Analytics!");

    }

    /**
     * Endpoint status koneksi TikTok.
     * Mengembalikan informasi koneksi yang aman tanpa membocorkan token / secret.
     */
    public function status(Request $request): JsonResponse|RedirectResponse
    {
        $platform = Platform::where('slug', 'tiktok')->first();
        $account = $platform ? SocialAccount::where('platform_id', $platform->id)->first() : null;

        if (! $account) {
            return response()->json([
                'connected' => false,
                'message'   => 'Belum ada akun TikTok yang terhubung.',
                'platform'  => 'tiktok',
            ]);
        }

        $isExpired = $account->expires_at ? $account->expires_at->isPast() : false;

        return response()->json([
            'connected'           => true,
            'platform'            => 'tiktok',
            'username'            => $account->username,
            'external_account_id' => $account->external_account_id,
            'connected_at'        => $account->created_at?->toIso8601String(),
            'updated_at'          => $account->updated_at?->toIso8601String(),
            'expires_at'          => $account->expires_at?->toIso8601String(),
            'is_expired'          => $isExpired,
        ]);
    }

    /**
     * Endpoint diagnostik pengujian API TikTok untuk akun yang sudah terhubung.
     * Mengambil profil + statistik live dari TikTok API v2 menggunakan scope
     * user.info.basic dan user.info.stats.
     */
    public function test(Request $request): JsonResponse
    {
        $platform = Platform::where('slug', 'tiktok')->first();
        $account = $platform ? SocialAccount::where('platform_id', $platform->id)->first() : null;

        if (! $account) {
            return response()->json([
                'connected' => false,
                'message'   => 'Akun TikTok belum terhubung. Silakan lakukan otorisasi di /tiktok/connect terlebih dahulu.',
            ], 404);
        }

        if (empty($account->access_token)) {
            return response()->json([
                'connected' => false,
                'message'   => 'Access token TikTok tidak ditemukan pada database.',
            ], 400);
        }

        if ($account->expires_at && $account->expires_at->isPast()) {
            return response()->json([
                'connected'  => true,
                'is_expired' => true,
                'message'    => 'Access token TikTok sudah kedaluwarsa pada ' . $account->expires_at->toIso8601String() . '. Silakan hubungkan ulang.',
            ], 401);
        }

        // Ambil profil dasar + statistik akun (memerlukan scope user.info.basic + user.info.stats)
        $allFields = array_merge(TikTokService::BASIC_INFO_FIELDS, TikTokService::STATS_FIELDS);
        $liveStats = null;

        try {
            $accountInfo = $this->tiktokService->getAccountStats($account->access_token, $allFields);
        } catch (\Throwable $e) {
            Log::error('TikTok diagnostic test failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'connected' => true,
                'status'    => 'error',
                'message'   => 'Gagal mengambil data dari TikTok API: ' . $e->getMessage(),
            ], 500);
        }

        // Statistik akun tersedia jika scope user.info.stats sudah dikonfigurasi di TikTok Developer Portal
        if (
            isset($accountInfo['follower_count']) ||
            isset($accountInfo['following_count']) ||
            isset($accountInfo['likes_count']) ||
            isset($accountInfo['video_count'])
        ) {
            $liveStats = [
                'follower_count'  => $accountInfo['follower_count'] ?? null,
                'following_count' => $accountInfo['following_count'] ?? null,
                'likes_count'     => $accountInfo['likes_count'] ?? null,
                'video_count'     => $accountInfo['video_count'] ?? null,
            ];
        }

        $response = [
            'connected'    => true,
            'status'       => 'ok',
            'account'      => [
                'username'            => $account->username,
                'external_account_id' => $account->external_account_id,
                'expires_at'          => $account->expires_at?->toIso8601String(),
            ],
            'live_profile' => [
                'open_id'      => $accountInfo['open_id'] ?? null,
                'union_id'     => $accountInfo['union_id'] ?? null,
                'display_name' => $accountInfo['display_name'] ?? null,
                'avatar_url'   => $accountInfo['avatar_url'] ?? null,
            ],
            'stats_note'   => 'Statistik akun (follower_count, likes_count, dll.) tersedia via scope user.info.stats yang sudah dikonfigurasi di TikTok Developer Portal.',
        ];

        if ($liveStats !== null) {
            $response['live_stats'] = $liveStats;
        }

        return response()->json($response);
    }

    /**
     * Endpoint diagnostik video list TikTok — read-only, tidak menyimpan ke database.
     * Mengambil maksimal 5 video pertama dari TikTok API v2 video.list.
     * Hanya menampilkan informasi aman tanpa token atau credential.
     */
    public function testVideos(Request $request): JsonResponse
    {
        $platform = Platform::where('slug', 'tiktok')->first();
        $account  = $platform ? SocialAccount::where('platform_id', $platform->id)->first() : null;

        if (! $account) {
            return response()->json([
                'connected' => false,
                'message'   => 'Akun TikTok belum terhubung. Silakan lakukan otorisasi di /tiktok/connect terlebih dahulu.',
            ], 404);
        }

        if (empty($account->access_token)) {
            return response()->json([
                'connected' => false,
                'message'   => 'Access token TikTok tidak ditemukan pada database.',
            ], 400);
        }

        if ($account->expires_at && $account->expires_at->isPast()) {
            return response()->json([
                'connected'  => true,
                'is_expired' => true,
                'message'    => 'Access token TikTok sudah kedaluwarsa pada ' . $account->expires_at->toIso8601String() . '. Silakan hubungkan ulang.',
            ], 401);
        }

        try {
            // Ambil hanya 5 video pertama — diagnostik, bukan sync
            $result = $this->tiktokService->getVideoList(
                $account->access_token,
                5,    // max_count = 5
                null, // mulai dari cursor awal
            );
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();

            Log::error('TikTok test-videos diagnostic failed', [
                'error' => $errMsg,
            ]);

            // Ekstrak error code TikTok dari pesan exception jika tersedia
            $tiktokErrorCode    = null;
            $tiktokErrorMessage = null;
            $logId              = null;

            // Coba parse structured info dari exception message
            if (preg_match('/\[([a-z_]+)\]/', $errMsg, $matches)) {
                $tiktokErrorCode = $matches[1];
            }

            return response()->json([
                'connected'           => true,
                'status'              => 'error',
                'username'            => $account->username,
                'tiktok_error_code'   => $tiktokErrorCode,
                'tiktok_error_message'=> $errMsg,
                'message'             => 'Gagal mengambil daftar video dari TikTok API.',
            ], 500);
        }

        $videos        = $result['videos'] ?? [];
        $hasMore       = $result['has_more'] ?? false;
        $nextCursor    = $result['cursor'] ?? null;
        $totalReturned = count($videos);

        // Saring hanya field yang aman untuk ditampilkan
        $safeVideos = array_map(function (array $video): array {
            return [
                'external_id'   => $video['external_id'] ?? null,
                'title'         => $video['title'] ?? null,
                'url'           => $video['url'] ?? null,
                'thumbnail_url' => $video['thumbnail_url'] ?? null,
                'upload_date'   => $video['upload_date'] ?? null,
                // Strict NULL Semantics: null tetap null, 0 tetap 0
                'views'    => array_key_exists('views', $video) ? $video['views'] : null,
                'likes'    => array_key_exists('likes', $video) ? $video['likes'] : null,
                'comments' => array_key_exists('comments', $video) ? $video['comments'] : null,
                'shares'   => array_key_exists('shares', $video) ? $video['shares'] : null,
            ];
        }, $videos);

        return response()->json([
            'connected'      => true,
            'status'         => 'ok',
            'username'       => $account->username,
            'total_returned' => $totalReturned,
            'has_more'       => $hasMore,
            'videos'         => $safeVideos,
            'diagnostic_note'=> 'Endpoint ini hanya membaca data. Tidak ada perubahan database.',
        ]);
    }
}
