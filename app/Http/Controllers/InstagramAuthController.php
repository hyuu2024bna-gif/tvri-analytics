<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramAuthController extends Controller
{
    protected string $graphUrl = 'https://graph.facebook.com/v19.0';

    public function connect()
    {
        $appId = config('services.facebook.app_id');
        $redirectUri = config('services.facebook.redirect');

        $scopes = implode(',', [
            'instagram_basic',
            'instagram_manage_insights',
            'pages_show_list',
        ]);

        $url = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'response_type' => 'code',
        ]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('instagram.status')
                ->with('error', 'Otorisasi dibatalkan/gagal: ' . $request->get('error_description', $request->get('error')));
        }

        $code = $request->get('code');
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');
        $redirectUri = config('services.facebook.redirect');

        $tokenResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'client_secret' => $appSecret,
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            Log::error('Instagram OAuth: gagal tukar code jadi token', ['body' => $tokenResponse->body()]);
            return redirect()->route('instagram.status')->with('error', 'Gagal menukar kode otorisasi. Coba hubungkan lagi.');
        }

        $shortLivedToken = $tokenResponse->json('access_token');

        $longLivedResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (! $longLivedResponse->successful()) {
            Log::error('Instagram OAuth: gagal perpanjang token', ['body' => $longLivedResponse->body()]);
            return redirect()->route('instagram.status')->with('error', 'Gagal memperpanjang token. Coba hubungkan lagi.');
        }

        $longLivedToken = $longLivedResponse->json('access_token');
        $expiresIn = $longLivedResponse->json('expires_in');

        $pagesResponse = Http::get("{$this->graphUrl}/me/accounts", [
            'access_token' => $longLivedToken,
        ]);

        if (! $pagesResponse->successful() || empty($pagesResponse->json('data'))) {
            Log::error('Instagram OAuth: gagal ambil daftar Page', ['body' => $pagesResponse->body()]);
            return redirect()->route('instagram.status')
                ->with('error', 'Tidak ditemukan Facebook Page yang kamu kelola. Pastikan akun ini admin Page resmi TVRI Aceh.');
        }

        $igAccount = null;
        $matchedPage = null;

        foreach ($pagesResponse->json('data') as $page) {
            $igResponse = Http::get("{$this->graphUrl}/{$page['id']}", [
                'fields' => 'instagram_business_account{id,username,followers_count}',
                'access_token' => $page['access_token'],
            ]);

            $igBusinessAccount = $igResponse->json('instagram_business_account');

            if ($igBusinessAccount) {
                $igAccount = $igBusinessAccount;
                $matchedPage = $page;
                break;
            }
        }

        if (! $igAccount) {
            return redirect()->route('instagram.status')->with('error',
                'Tidak ada akun Instagram Business yang terhubung ke Page yang kamu kelola. ' .
                'Pastikan akun Instagram sudah tipe Business/Creator dan sudah dihubungkan ke Facebook Page-nya.'
            );
        }

        $platform = Platform::where('slug', 'instagram')->firstOrFail();

        SocialAccount::updateOrCreate(
            ['platform_id' => $platform->id],
            [
                'external_account_id' => $igAccount['id'],
                'username' => $igAccount['username'] ?? null,
                'access_token' => $matchedPage['access_token'],
                'page_id' => $matchedPage['id'],
                'connected_by' => auth()->id(),
                'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            ]
        );

        return redirect()->route('instagram.status')
            ->with('status', 'Instagram berhasil terhubung! Akun: @' . ($igAccount['username'] ?? '-'));
    }

    public function status()
    {
        $platform = Platform::where('slug', 'instagram')->firstOrFail();
        $account = SocialAccount::where('platform_id', $platform->id)->first();

        $liveInfo = null;
        $liveError = null;

        if ($account) {
            $response = Http::get("{$this->graphUrl}/{$account->external_account_id}", [
                'fields' => 'username,followers_count,media_count',
                'access_token' => $account->access_token,
            ]);

            if ($response->successful()) {
                $liveInfo = $response->json();
            } else {
                $liveError = 'Token tersimpan tapi gagal dipakai memanggil API - kemungkinan sudah kedaluwarsa. Coba hubungkan ulang.';
                Log::warning('Instagram status check gagal', ['body' => $response->body()]);
            }
        }

        return view('instagram.status', compact('account', 'liveInfo', 'liveError'));
    }
}
