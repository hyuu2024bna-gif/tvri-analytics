<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\ContentStatsDaily;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualInputController extends Controller
{
    public function index(Request $request)
    {
        $platformSlug = $request->query('platform', 'tiktok');
        $platform = Platform::where('slug', $platformSlug)->firstOrFail();

        $contents = Content::where('platform_id', $platform->id)
            ->where('status', 'aktif')
            ->with(['statsDaily' => function ($q) {
                $q->orderByDesc('tanggal')->limit(1);
            }])
            ->orderByDesc('tanggal_upload')
            ->get();

        $manualPlatforms = Platform::whereIn('slug', ['tiktok'])->get();

        return view('manual.index', compact('contents', 'platform', 'manualPlatforms'));
    }

    public function createContent(Request $request)
    {
        $platformSlug = $request->query('platform', 'tiktok');
        $platform = Platform::where('slug', $platformSlug)->firstOrFail();

        return view('manual.create-content', compact('platform'));
    }

    public function storeContent(Request $request)
    {
        $validated = $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'judul' => 'required|string|max:255',
            'url' => 'required|url',
            'tanggal_upload' => 'nullable|date',
        ]);

        $content = Content::create([
            ...$validated,
            'content_id_external' => 'manual-' . uniqid(),
            'status' => 'aktif',
        ]);

        return redirect()
            ->route('manual.input.create', $content)
            ->with('status', 'Konten berhasil ditambahkan. Sekarang input statistiknya.');
    }

    public function createInput(Content $content)
    {
        $history = $content->statsDaily()->orderByDesc('tanggal')->limit(10)->get();

        return view('manual.input-stats', compact('content', 'history'));
    }

    public function storeInput(Request $request, Content $content)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'views' => 'required|integer|min:0',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
        ]);

        ContentStatsDaily::updateOrCreate(
            ['content_id' => $content->id, 'tanggal' => $validated['tanggal']],
            [
                'views' => $validated['views'],
                'likes' => $validated['likes'] ?? 0,
                'comments' => $validated['comments'] ?? 0,
                'diinput_oleh' => Auth::id(),
            ]
        );

        return redirect()
            ->route('manual.index', ['platform' => $content->platform->slug])
            ->with('status', 'Statistik berhasil disimpan.');
    }
}
