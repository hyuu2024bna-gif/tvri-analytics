<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\SocialAccount;
use App\Services\InstagramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramAuthController extends Controller
{
    protected string $graphUrl;

    public function __construct()
    {
        $version = config('services.facebook.graph_api_version', 'v19.0');
        $this->graphUrl = "https://graph.facebook.com/{$version}";
    }

    public function connect()
    {
        $appId = config('services.facebook.app_id');
        $redirectUri = config('services.facebook.redirect');

        $scopes = implode(',', [
            'pages_show_list',
            'pages_read_engagement',
            'instagram_basic',
            'instagram_manage_insights',
        ]);

        $version = config('services.facebook.graph_api_version', 'v19.0');

        $url = "https://www.facebook.com/{$version}/dialog/oauth?" . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
            // re-request agar user bisa grant ulang permission yang sebelumnya ditolak
            'auth_type' => 'rerequest',
        ]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::warning('Instagram OAuth: user menolak/gagal otorisasi', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);
            return redirect()->route('instagram.status')
                ->with('error', 'Otorisasi dibatalkan/gagal: ' . $request->get('error_description', $request->get('error')));
        }

        $code = $request->get('code');

        if (! $code) {
            return redirect()->route('instagram.status')
                ->with('error', 'Kode otorisasi tidak diterima dari Facebook. Coba hubungkan lagi.');
        }

        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');
        $redirectUri = config('services.facebook.redirect');

        // ======================================================
        // STEP 1: Tukar authorization code → short-lived token
        // ======================================================
        $tokenResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'client_secret' => $appSecret,
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            $errorBody = $tokenResponse->json();
            $metaError = $errorBody['error']['message'] ?? 'Unknown error';
            $metaCode = $errorBody['error']['code'] ?? $tokenResponse->status();

            Log::error('Instagram OAuth: gagal tukar code jadi token', [
                'http_status' => $tokenResponse->status(),
                'meta_error_code' => $metaCode,
                'meta_error_message' => $metaError,
            ]);

            return redirect()->route('instagram.status')
                ->with('error', "Gagal menukar kode otorisasi (Meta error {$metaCode}: {$metaError}). Coba hubungkan lagi.");
        }

        $shortLivedToken = $tokenResponse->json('access_token');

        if (! $shortLivedToken) {
            Log::error('Instagram OAuth: response token berhasil tapi access_token kosong', [
                'response_keys' => array_keys($tokenResponse->json() ?? []),
            ]);
            return redirect()->route('instagram.status')
                ->with('error', 'Token tidak ditemukan dalam response Facebook. Coba hubungkan lagi.');
        }

        // ======================================================
        // STEP 2: Perpanjang token → long-lived token (~60 hari)
        // ======================================================
        $longLivedResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (! $longLivedResponse->successful()) {
            $errorBody = $longLivedResponse->json();
            $metaError = $errorBody['error']['message'] ?? 'Unknown error';
            $metaCode = $errorBody['error']['code'] ?? $longLivedResponse->status();

            Log::error('Instagram OAuth: gagal perpanjang token', [
                'http_status' => $longLivedResponse->status(),
                'meta_error_code' => $metaCode,
                'meta_error_message' => $metaError,
            ]);

            return redirect()->route('instagram.status')
                ->with('error', "Gagal memperpanjang token (Meta error {$metaCode}: {$metaError}). Coba hubungkan lagi.");
        }

        $longLivedToken = $longLivedResponse->json('access_token');
        $expiresIn = $longLivedResponse->json('expires_in');

        // ======================================================
        // STEP 3: Diagnostic Inspeksi User & Token (Aman / Sanitasi)
        // ======================================================
        
        // 3a. GET /me (User Info)
        $meResponse = Http::get("{$this->graphUrl}/me", [
            'fields' => 'id,name',
            'access_token' => $longLivedToken,
        ]);
        $meData = $meResponse->json() ?? [];

        // 3b. GET /me/permissions (Granted vs Declined Scopes)
        $permissionsResponse = Http::get("{$this->graphUrl}/me/permissions", [
            'access_token' => $longLivedToken,
        ]);
        $permissionsData = $permissionsResponse->json('data', []);

        // 3c. GET /debug_token (Token metadata & granular scopes)
        $appAccessToken = "{$appId}|{$appSecret}";
        $debugTokenResponse = Http::get("{$this->graphUrl}/debug_token", [
            'input_token' => $longLivedToken,
            'access_token' => $appAccessToken,
        ]);
        $debugData = $debugTokenResponse->json('data', []);

        // ======================================================
        // STEP 4: Ambil daftar Facebook Pages (/me/accounts)
        // ======================================================
        $pagesResponse = Http::get("{$this->graphUrl}/me/accounts", [
            'fields' => 'id,name,access_token,tasks,instagram_business_account{id,username,name}',
            'limit' => 100,
            'access_token' => $longLivedToken,
        ]);

        $httpStatus = $pagesResponse->status();
        $rawBody = $pagesResponse->json() ?? [];

        // Sanitasi response body (HAPUS SEMUA access_token sebelum logging / session)
        $sanitizedBody = $rawBody;
        if (isset($sanitizedBody['data']) && is_array($sanitizedBody['data'])) {
            foreach ($sanitizedBody['data'] as &$pageItem) {
                unset($pageItem['access_token']);
            }
            unset($pageItem);
        }

        $pages = $rawBody['data'] ?? [];
        $metaError = $rawBody['error'] ?? null;

        // ======================================================
        // STEP 5: Evaluasi Strategi A (/me/accounts) & Strategi B (Granular Scopes Fallback)
        // ======================================================
        $configuredPageId = config('services.facebook.page_id');
        $matchedPage = null;
        $matchedIg = null;
        $matchedToken = null;
        $directLookups = [];

        // Strategi A: Cek Page yang dikembalikan /me/accounts
        if (! empty($pages)) {
            foreach ($pages as $page) {
                if ($configuredPageId && $page['id'] !== $configuredPageId) {
                    continue;
                }

                if (isset($page['instagram_business_account']['id'])) {
                    $matchedPage = [
                        'id' => $page['id'],
                        'name' => $page['name'] ?? null,
                    ];
                    $matchedIg = $page['instagram_business_account'];
                    $matchedToken = $page['access_token'] ?? $longLivedToken;
                    break;
                }
            }
        }

        // Strategi B: Jika /me/accounts kosong atau belum menemukan IG, periksa granular_scopes target_ids secara dinamis
        if (! $matchedIg) {
            $targetPageIds = [];
            foreach ($debugData['granular_scopes'] ?? [] as $gScope) {
                if (in_array($gScope['scope'] ?? '', ['pages_show_list', 'pages_read_engagement'])) {
                    foreach ($gScope['target_ids'] ?? [] as $tId) {
                        $targetPageIds[] = (string) $tId;
                    }
                }
            }
            $targetPageIds = array_values(array_unique($targetPageIds));

            foreach ($targetPageIds as $targetPageId) {
                if ($configuredPageId && $targetPageId !== $configuredPageId) {
                    continue;
                }

                // Request direct page lookup
                $directResp = Http::get("{$this->graphUrl}/{$targetPageId}", [
                    'fields' => 'id,name,access_token,tasks,instagram_business_account{id,username,name}',
                    'access_token' => $longLivedToken,
                ]);

                if (! $directResp->successful()) {
                    $errMessage = $directResp->json('error.message', '');
                    if (str_contains($errMessage, 'tasks') || str_contains($errMessage, 'field')) {
                        $directResp = Http::get("{$this->graphUrl}/{$targetPageId}", [
                            'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
                            'access_token' => $longLivedToken,
                        ]);
                    }
                }

                $dHttpStatus = $directResp->status();
                $dRawBody = $directResp->json() ?? [];
                $hasPageToken = ! empty($dRawBody['access_token']);
                $pageAccessToken = $dRawBody['access_token'] ?? null;

                $dSanitized = $dRawBody;
                unset($dSanitized['access_token']);

                $dPageId = $dRawBody['id'] ?? $targetPageId;
                $dPageName = $dRawBody['name'] ?? null;
                $dIgAccount = $dRawBody['instagram_business_account'] ?? null;
                $dMetaError = $dRawBody['error'] ?? null;

                $directLookups[] = [
                    'target_page_id' => $targetPageId,
                    'http_status' => $dHttpStatus,
                    'page_id' => $dPageId,
                    'page_name' => $dPageName,
                    'has_page_access_token' => $hasPageToken ? 'YES' : 'NO',
                    'instagram_business_account' => $dIgAccount,
                    'instagram_business_account_id' => $dIgAccount['id'] ?? null,
                    'meta_error' => $dMetaError,
                    'sanitized_response' => $dSanitized,
                ];

                if ($directResp->successful() && isset($dIgAccount['id']) && ! $matchedIg) {
                    $matchedPage = [
                        'id' => $dPageId,
                        'name' => $dPageName,
                    ];
                    $matchedIg = $dIgAccount;
                    $matchedToken = $pageAccessToken ?: $longLivedToken;
                }
            }
        }

        // Kumpulkan data diagnostic aman
        $diagnostic = [
            'oauth' => [
                'status' => 'SUCCESS',
                'user_id' => $meData['id'] ?? ($debugData['user_id'] ?? '-'),
                'user_name' => $meData['name'] ?? '-',
                'app_id' => $debugData['app_id'] ?? $appId,
                'is_valid' => $debugData['is_valid'] ?? null,
                'type' => $debugData['type'] ?? null,
                'application' => $debugData['application'] ?? null,
                'expires_at' => (! empty($debugData['expires_at']) && $debugData['expires_at'] > 0)
                    ? date('Y-m-d H:i:s', $debugData['expires_at'])
                    : 'Tidak terbatas / Never',
                'data_access_expires_at' => (! empty($debugData['data_access_expires_at']) && $debugData['data_access_expires_at'] > 0)
                    ? date('Y-m-d H:i:s', $debugData['data_access_expires_at'])
                    : 'Tidak tersedia',
                'scopes' => $debugData['scopes'] ?? [],
                'granular_scopes' => $debugData['granular_scopes'] ?? [],
                'permissions' => $permissionsData,
            ],
            'me_accounts' => [
                'endpoint' => '/me/accounts',
                'http_status' => $httpStatus,
                'pages_count' => count($pages),
                'pages' => collect($pages)->map(fn ($p) => [
                    'id' => $p['id'] ?? null,
                    'name' => $p['name'] ?? '(tanpa nama)',
                    'tasks' => $p['tasks'] ?? [],
                    'instagram_business_account' => $p['instagram_business_account'] ?? null,
                ])->toArray(),
                'sanitized_response' => $sanitizedBody,
                'meta_error' => $metaError,
            ],
            'direct_page_lookups' => $directLookups,
        ];

        // Log diagnostic aman (tanpa token)
        Log::info('Instagram OAuth Full Diagnostic', [
            'user' => [
                'id' => $diagnostic['oauth']['user_id'],
                'name' => $diagnostic['oauth']['user_name'],
            ],
            'app_id' => $diagnostic['oauth']['app_id'],
            'token_is_valid' => $diagnostic['oauth']['is_valid'],
            'scopes' => $diagnostic['oauth']['scopes'],
            'granular_scopes' => $diagnostic['oauth']['granular_scopes'],
            'matched_page' => $matchedPage,
            'matched_ig' => $matchedIg,
            'direct_lookups' => $directLookups,
        ]);

        // ======================================================
        // STEP 6: Simpan Koneksi Permanen Jika Berhasil
        // ======================================================
        if ($matchedIg && $matchedPage) {
            $platform = Platform::where('slug', 'instagram')->firstOrFail();

            // Ambil profile live untuk memastikan username akurat
            $igUsername = $matchedIg['username'] ?? null;
            if (! $igUsername && $matchedToken) {
                $igProfileResp = Http::get("{$this->graphUrl}/{$matchedIg['id']}", [
                    'fields' => 'username,name',
                    'access_token' => $matchedToken,
                ]);
                if ($igProfileResp->successful()) {
                    $igUsername = $igProfileResp->json('username');
                }
            }

            SocialAccount::updateOrCreate(
                ['platform_id' => $platform->id],
                [
                    'external_account_id' => $matchedIg['id'],
                    'username' => $igUsername ?? ($matchedPage['name'] ?? null),
                    'access_token' => $matchedToken,
                    'page_id' => $matchedPage['id'],
                    'connected_by' => auth()->id(),
                    'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                ]
            );

            Log::info('Instagram OAuth: Koneksi berhasil disimpan secara permanen', [
                'ig_account_id' => $matchedIg['id'],
                'username' => $igUsername,
                'page_id' => $matchedPage['id'],
                'page_name' => $matchedPage['name'],
            ]);

            return redirect()->route('instagram.status')
                ->with('status', 'Instagram berhasil terhubung! Akun: @' . ($igUsername ?? $matchedIg['id'])
                    . ' (via Page: ' . ($matchedPage['name'] ?? $matchedPage['id']) . ')')
                ->with('diagnostic', $diagnostic);
        }

        // Penanganan Gagal
        if (! empty($pages)) {
            return redirect()->route('instagram.status')
                ->with('error', 'Facebook Page ditemukan, tetapi tidak ada Instagram Professional Account yang terhubung.')
                ->with('diagnostic', $diagnostic);
        }

        $firstDirectError = $directLookups[0]['meta_error'] ?? null;
        $errorDetail = $firstDirectError ? " (Direct lookup error: {$firstDirectError['message']})" : '';

        return redirect()->route('instagram.status')
            ->with('error', 'Tidak ada Facebook Page yang dapat diakses melalui /me/accounts maupun direct lookup.' . $errorDetail)
            ->with('diagnostic', $diagnostic);
    }

    public function status(InstagramService $instagram)
    {
        $platform = Platform::where('slug', 'instagram')->first();
        $account = $platform ? SocialAccount::where('platform_id', $platform->id)->first() : null;

        $liveInfo = null;
        $liveError = null;

        if ($account) {
            try {
                $liveInfo = $instagram->getInstagramAccount();
            } catch (\RuntimeException $e) {
                $liveError = $e->getMessage();
                Log::warning('Instagram status check gagal', ['error' => $liveError]);
            }
        }

        return view('instagram.status', compact('account', 'liveInfo', 'liveError'));
    }

    /**
     * Endpoint tes koneksi Instagram API.
     * Memanggil InstagramService untuk memastikan API berfungsi secara live.
     */
    public function test(InstagramService $instagram)
    {
        try {
            $accountInfo = $instagram->getInstagramAccount();
            $mediaResult = $instagram->getMedia(5);

            return response()->json([
                'success' => true,
                'message' => 'Koneksi Instagram API berhasil.',
                'account' => [
                    'id' => $accountInfo['id'] ?? null,
                    'username' => $accountInfo['username'] ?? null,
                    'name' => $accountInfo['name'] ?? null,
                ],
                'followers' => $accountInfo['followers_count'] ?? 0,
                'media_count' => $accountInfo['media_count'] ?? 0,
                'recent_media' => $mediaResult['data'] ?? [],
                'media_paging' => $mediaResult['paging'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengakses Instagram API.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint tes pengambilan media/postingan Instagram API live dan data yang sudah dinormalisasi.
     */
    public function testMedia(Request $request, InstagramService $instagram)
    {
        try {
            $accountInfo = $instagram->getAccount();
            $limit = min((int) $request->get('limit', 25), 100);
            if ($limit < 1) {
                $limit = 25;
            }
            $after = $request->get('after');

            $normalizedResult = $instagram->getNormalizedMedia($limit, $after, true);

            return response()->json([
                'success' => true,
                'account' => [
                    'id' => $accountInfo['id'] ?? null,
                    'username' => $accountInfo['username'] ?? null,
                    'name' => $accountInfo['name'] ?? null,
                ],
                'total_returned' => $normalizedResult['total'],
                'media' => $normalizedResult['data'],
                'paging' => $normalizedResult['paging'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil media dari Instagram API.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
