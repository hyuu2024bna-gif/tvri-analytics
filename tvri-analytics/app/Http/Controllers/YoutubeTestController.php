<?php

namespace App\Http\Controllers;

use App\Services\YoutubeService;
use Illuminate\Http\Request;

class YoutubeTestController extends Controller
{
    public function __construct(protected YoutubeService $youtube)
    {
    }

    public function form()
    {
        return view('youtube-test');
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'video_ids' => 'required|string',
        ]);

        $videoIds = array_map('trim', explode(',', $request->video_ids));
        $stats = $this->youtube->getVideoStats($videoIds);

        return view('youtube-test', ['stats' => $stats, 'input' => $request->video_ids]);
    }
}
