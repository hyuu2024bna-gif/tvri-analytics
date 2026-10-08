<?php

namespace App\Http\Controllers;

use App\Services\YoutubeAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YoutubeAuthController extends Controller
{
    public function __construct(protected YoutubeAnalyticsService $youtubeAnalytics)
    {
    }

    /**
     * Redirect pengguna ke Google OAuth Consent Screen.
     */
    public function connect()
    {
        if (! config('services.youtube.client_id') || ! config('services.youtube.client_secret')) {
            return redirect()->route('dashboard', ['platform' => 'youtube'])
                ->with('error', 'YOUTUBE_CLIENT_ID dan YOUTUBE_CLIENT_SECRET belum dikonfigurasi di file .env.');
        }

        return redirect($this->youtubeAnalytics->getAuthorizationUrl());
    }

    /**
     * Callback dari Google OAuth setelah otorisasi.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::warning('YouTube OAuth: otorisasi dibatalkan atau gagal', [
                'error' => $request->get('error'),
            ]);

            return redirect()->route('dashboard', ['platform' => 'youtube'])
                ->with('error', 'Otorisasi YouTube Analytics dibatalkan atau gagal: ' . $request->get('error'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('dashboard', ['platform' => 'youtube'])
                ->with('error', 'Kode otorisasi (code) tidak ditemukan.');
        }

        $result = $this->youtubeAnalytics->exchangeCodeForTokens($code);

        if ($result && $result['has_refresh_token']) {
            return redirect()->route('dashboard', ['platform' => 'youtube'])
                ->with('success', 'YouTube Analytics API berhasil terhubung! Pertumbuhan subscriber kini dihitung secara exact.');
        }

        return redirect()->route('dashboard', ['platform' => 'youtube'])
            ->with('warning', 'Otorisasi berhasil, namun refresh token tidak diterima. Pastikan access_type=offline diaktifkan.');
    }
}
