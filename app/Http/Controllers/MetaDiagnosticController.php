<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class MetaDiagnosticController extends Controller
{
    protected string $graphUrl;

    public function __construct()
    {
        $version = config('services.facebook.graph_api_version', 'v19.0');
        $this->graphUrl = "https://graph.facebook.com/{$version}";
    }

    /**
     * GET /diagnostic/meta
     *
     * Halaman diagnostic internal Meta (Instagram + Facebook).
     * Read-only. Tidak menyimpan token baru, tidak menampilkan secret/token.
     */
    public function index()
    {
        $report = [
            'application'   => $this->checkApplication(),
            'oauth_routes'  => $this->checkOAuthRoutes(),
            'configuration' => $this->checkConfiguration(),
            'accounts'      => $this->checkStoredAccounts(),
            'permissions'   => $this->checkPermissions(),
            'api_health'    => $this->checkApiHealth(),
            'access_levels' => $this->checkAccessLevels(),
        ];

        return view('diagnostic.meta', compact('report'));
    }

    /**
     * Bagian 1: Application Info
     */
    protected function checkApplication(): array
    {
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');
        $redirectUri = config('services.facebook.redirect');
        $graphVersion = config('services.facebook.graph_api_version', 'v19.0');

        return [
            'app_id'        => $appId ?: null,
            'app_secret'    => $appSecret ? 'CONFIGURED' : 'NOT SET',
            'graph_api'     => $graphVersion,
            'redirect_uri'  => $redirectUri ? 'CONFIGURED' : 'NOT SET',
        ];
    }

    /**
     * Bagian 2: OAuth Routes Check
     */
    protected function checkOAuthRoutes(): array
    {
        return [
            'instagram_connect'  => Route::has('instagram.connect') ? 'OK' : 'NOT FOUND',
            'instagram_callback' => Route::has('instagram.callback') ? 'OK' : 'NOT FOUND',
            'instagram_status'   => Route::has('instagram.status') ? 'OK' : 'NOT FOUND',
            'redirect_uri_set'   => config('services.facebook.redirect') ? 'OK' : 'NOT FOUND',
        ];
    }

    /**
     * Bagian 3: Environment Configuration Check
     */
    protected function checkConfiguration(): array
    {
        return [
            'FACEBOOK_APP_ID'          => config('services.facebook.app_id') ? 'SET' : 'NOT SET',
            'FACEBOOK_APP_SECRET'      => config('services.facebook.app_secret') ? 'SET' : 'NOT SET',
            'FACEBOOK_REDIRECT_URI'    => config('services.facebook.redirect') ? 'SET' : 'NOT SET',
            'META_GRAPH_API_VERSION'   => config('services.facebook.graph_api_version') ? 'SET' : 'NOT SET',
            'META_FACEBOOK_PAGE_ID'    => config('services.facebook.page_id') ? 'SET' : 'NOT SET',
        ];
    }

    /**
     * Bagian 4: Stored Social Accounts
     */
    protected function checkStoredAccounts(): array
    {
        $result = [
            'instagram' => $this->checkPlatformAccount('instagram'),
            'facebook'  => $this->checkPlatformAccount('facebook'),
        ];

        return $result;
    }

    protected function checkPlatformAccount(string $slug): array
    {
        $platform = Platform::where('slug', $slug)->first();

        if (! $platform) {
            return [
                'connected'    => 'NO',
                'reason'       => 'Platform not registered',
                'username'     => null,
                'external_id'  => null,
                'page_id'      => null,
                'token_status' => 'UNKNOWN',
                'expires_info' => null,
            ];
        }

        $account = SocialAccount::where('platform_id', $platform->id)->first();

        // Facebook doesn't have its own SocialAccount row; it shares with Instagram
        if (! $account && $slug === 'facebook') {
            $igPlatform = Platform::where('slug', 'instagram')->first();
            $account = $igPlatform
                ? SocialAccount::where('platform_id', $igPlatform->id)
                    ->whereNotNull('page_id')
                    ->whereNotNull('access_token')
                    ->first()
                : null;
        }

        if (! $account) {
            return [
                'connected'    => 'NO',
                'reason'       => 'No account linked',
                'username'     => null,
                'external_id'  => null,
                'page_id'      => null,
                'token_status' => 'UNKNOWN',
                'expires_info' => null,
            ];
        }

        $hasToken = ! empty($account->access_token);

        $expiresInfo = null;
        $tokenStatus = 'UNKNOWN';

        if ($hasToken) {
            $tokenStatus = 'PRESENT';

            if ($account->expires_at) {
                if ($account->expires_at->isPast()) {
                    $tokenStatus = 'EXPIRED';
                    $expiresInfo = 'Expired: ' . $account->expires_at->format('d M Y H:i');
                } else {
                    $tokenStatus = 'VALID (not expired)';
                    $expiresInfo = 'Expires: ' . $account->expires_at->format('d M Y H:i');
                }
            } else {
                $expiresInfo = 'No expiration set (possibly never-expiring Page Token)';
            }
        } else {
            $tokenStatus = 'EMPTY';
        }

        // Facebook: gunakan Page ID & Page Name resmi, jangan pernah tampilkan username/external_id Instagram
        if ($slug === 'facebook') {
            $pageId = $account->page_id;
            $pageName = null;

            if ($pageId && $hasToken) {
                try {
                    $resp = Http::timeout(5)->get("{$this->graphUrl}/{$pageId}", [
                        'fields'       => 'id,name',
                        'access_token' => $account->access_token,
                    ]);
                    if ($resp->successful()) {
                        $pageName = $resp->json('name');
                    }
                } catch (\Throwable $e) {
                    Log::warning('MetaDiagnostic: Gagal mengambil nama Facebook Page', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (! $pageName) {
                $pageName = 'Facebook Page (shared Meta connection)';
            }

            return [
                'connected'    => 'YES',
                'is_facebook'  => true,
                'page_name'    => $pageName,
                'page_id'      => $pageId,
                'token_status' => $tokenStatus,
                'expires_info' => $expiresInfo,
            ];
        }

        return [
            'connected'    => 'YES',
            'is_facebook'  => false,
            'username'     => $account->username,
            'external_id'  => $account->external_account_id,
            'page_id'      => $account->page_id,
            'token_status' => $tokenStatus,
            'expires_info' => $expiresInfo,
        ];
    }

    /**
     * Bagian 5: Permissions Check (via debug_token)
     */
    protected function checkPermissions(): array
    {
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');

        if (! $appId || ! $appSecret) {
            return [
                'status'      => 'CANNOT CHECK',
                'reason'      => 'App ID or App Secret not configured',
                'scopes'      => [],
                'token_debug' => null,
            ];
        }

        // Find a stored token to inspect (read-only)
        $account = SocialAccount::whereNotNull('access_token')->first();

        if (! $account || ! $account->access_token) {
            return [
                'status'      => 'CANNOT CHECK',
                'reason'      => 'No stored token available',
                'scopes'      => [],
                'token_debug' => null,
            ];
        }

        try {
            $version = config('services.facebook.graph_api_version', 'v19.0');
            $debugResp = Http::timeout(10)->get("https://graph.facebook.com/{$version}/debug_token", [
                'input_token'  => $account->access_token,
                'access_token' => "{$appId}|{$appSecret}",
            ]);

            if (! $debugResp->successful()) {
                return [
                    'status'      => 'API ERROR',
                    'reason'      => 'debug_token request failed (HTTP ' . $debugResp->status() . ')',
                    'scopes'      => [],
                    'token_debug' => null,
                ];
            }

            $data = $debugResp->json('data', []);

            $requiredScopes = [
                'instagram_basic',
                'instagram_manage_insights',
                'pages_show_list',
                'pages_read_engagement',
            ];

            $grantedScopes = $data['scopes'] ?? [];
            $scopeResults = [];

            foreach ($requiredScopes as $scope) {
                $scopeResults[$scope] = in_array($scope, $grantedScopes) ? 'GRANTED' : 'NOT FOUND';
            }

            $tokenDebug = [
                'is_valid'     => ($data['is_valid'] ?? false) ? 'YES' : 'NO',
                'type'         => $data['type'] ?? 'UNKNOWN',
                'application'  => $data['application'] ?? 'UNKNOWN',
                'expires_at'   => (! empty($data['expires_at']) && $data['expires_at'] > 0)
                    ? date('Y-m-d H:i:s', $data['expires_at'])
                    : 'Never',
                'data_access_expires_at' => (! empty($data['data_access_expires_at']) && $data['data_access_expires_at'] > 0)
                    ? date('Y-m-d H:i:s', $data['data_access_expires_at'])
                    : 'Not available',
            ];

            return [
                'status'      => 'OK',
                'scopes'      => $scopeResults,
                'token_debug' => $tokenDebug,
            ];
        } catch (\Throwable $e) {
            Log::warning('Meta Diagnostic: debug_token check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status'      => 'ERROR',
                'reason'      => 'Exception: ' . $e->getMessage(),
                'scopes'      => [],
                'token_debug' => null,
            ];
        }
    }

    /**
     * Bagian 6: API Health Check (read-only GET)
     */
    protected function checkApiHealth(): array
    {
        $result = [
            'graph_api_reachable' => 'UNKNOWN',
            'instagram_api'       => 'UNKNOWN',
            'facebook_api'        => 'UNKNOWN',
        ];

        // Check if Graph API is reachable at all
        try {
            $pingResp = Http::timeout(5)->get("{$this->graphUrl}/me", [
                'access_token' => 'invalid_token_for_ping',
            ]);
            // Even with an invalid token, a reachable API returns HTTP 400, not a connection error
            $result['graph_api_reachable'] = 'YES';
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $result['graph_api_reachable'] = 'NO';
            return $result;
        } catch (\Throwable $e) {
            $result['graph_api_reachable'] = 'YES'; // HTTP error means API is reachable
        }

        // Check Instagram with stored token
        $igAccount = $this->getStoredAccountForPlatform('instagram');
        if ($igAccount && $igAccount->access_token && $igAccount->external_account_id) {
            try {
                $igResp = Http::timeout(10)->get("{$this->graphUrl}/{$igAccount->external_account_id}", [
                    'fields'       => 'id',
                    'access_token' => $igAccount->access_token,
                ]);
                $result['instagram_api'] = $igResp->successful() ? 'YES' : 'ERROR (HTTP ' . $igResp->status() . ')';
            } catch (\Throwable $e) {
                $result['instagram_api'] = 'ERROR: ' . $e->getMessage();
            }
        } else {
            $result['instagram_api'] = 'NO ACCOUNT';
        }

        // Check Facebook with stored token
        $fbAccount = $this->getStoredAccountForPlatform('facebook');
        if ($fbAccount && $fbAccount->access_token && $fbAccount->page_id) {
            try {
                $fbResp = Http::timeout(10)->get("{$this->graphUrl}/{$fbAccount->page_id}", [
                    'fields'       => 'id',
                    'access_token' => $fbAccount->access_token,
                ]);
                $result['facebook_api'] = $fbResp->successful() ? 'YES' : 'ERROR (HTTP ' . $fbResp->status() . ')';
            } catch (\Throwable $e) {
                $result['facebook_api'] = 'ERROR: ' . $e->getMessage();
            }
        } else {
            $result['facebook_api'] = 'NO ACCOUNT';
        }

        return $result;
    }

    /**
     * Bagian 7: Access Levels (Page vs Instagram vs Business Portfolio)
     */
    protected function checkAccessLevels(): array
    {
        return [
            'facebook_page' => [
                'status'      => $this->determinePageAccess(),
                'description' => 'Determined from stored Page ID and token validity',
            ],
            'instagram_account' => [
                'status'      => $this->determineInstagramAccess(),
                'description' => 'Determined from stored Instagram Business Account ID and API response',
            ],
            'business_portfolio' => [
                'status'      => 'Cannot determine from application credentials',
                'description' => 'Business Portfolio access requires admin-level permissions on Meta Business Suite, which cannot be determined through Graph API read calls alone.',
            ],
        ];
    }

    protected function determinePageAccess(): string
    {
        $account = $this->getStoredAccountForPlatform('facebook')
            ?? $this->getStoredAccountForPlatform('instagram');

        if (! $account || ! $account->page_id || ! $account->access_token) {
            return 'NO ACCESS';
        }

        try {
            $resp = Http::timeout(10)->get("{$this->graphUrl}/{$account->page_id}", [
                'fields'       => 'id,name',
                'access_token' => $account->access_token,
            ]);
            return $resp->successful() ? 'ACCESSIBLE' : 'ERROR (HTTP ' . $resp->status() . ')';
        } catch (\Throwable $e) {
            return 'CANNOT VERIFY';
        }
    }

    protected function determineInstagramAccess(): string
    {
        $platform = Platform::where('slug', 'instagram')->first();
        if (! $platform) {
            return 'NO ACCESS';
        }

        $account = SocialAccount::where('platform_id', $platform->id)->first();
        if (! $account || ! $account->external_account_id || ! $account->access_token) {
            return 'NO ACCESS';
        }

        try {
            $resp = Http::timeout(10)->get("{$this->graphUrl}/{$account->external_account_id}", [
                'fields'       => 'id,username',
                'access_token' => $account->access_token,
            ]);
            return $resp->successful() ? 'ACCESSIBLE' : 'ERROR (HTTP ' . $resp->status() . ')';
        } catch (\Throwable $e) {
            return 'CANNOT VERIFY';
        }
    }

    /**
     * Helper: Get stored SocialAccount for a platform, with Facebook fallback.
     */
    protected function getStoredAccountForPlatform(string $slug): ?SocialAccount
    {
        $platform = Platform::where('slug', $slug)->first();

        if ($platform) {
            $account = SocialAccount::where('platform_id', $platform->id)->first();
            if ($account) {
                return $account;
            }
        }

        if ($slug === 'facebook') {
            $igPlatform = Platform::where('slug', 'instagram')->first();
            return $igPlatform
                ? SocialAccount::where('platform_id', $igPlatform->id)
                    ->whereNotNull('page_id')
                    ->whereNotNull('access_token')
                    ->first()
                : null;
        }

        return null;
    }
}
