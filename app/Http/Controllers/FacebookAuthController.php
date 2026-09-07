<?php

namespace App\Http\Controllers;

use App\Services\FacebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookAuthController extends Controller
{
    /**
     * Test live connection to Facebook Page profile.
     * GET /facebook/test
     */
    public function test(FacebookService $facebook): JsonResponse
    {
        try {
            $page = $facebook->getPage();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengakses data profil Facebook Page secara live dari Meta API.',
                'page'    => [
                    'id'          => $page['id'],
                    'name'        => $page['name'],
                    'link'        => $page['link'],
                    'followers'   => $page['followers_count'],
                    'fan_count'   => $page['fan_count'],
                    'picture_url' => $page['picture_url'],
                    'category'    => $page['category'],
                    'about'       => $page['about'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Facebook Page Test Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengakses Facebook Page API.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test live fetching of Facebook Page posts with stats.
     * GET /facebook/test-posts
     */
    public function testPosts(Request $request, FacebookService $facebook): JsonResponse
    {
        try {
            $page = $facebook->getPage();
            $limit = min(max((int) $request->query('limit', 25), 1), 50);

            $result = $facebook->getNormalizedPosts($limit, null, true);
            $withStatus = $facebook->getAllNormalizedPostsWithStatus(1, true);

            return response()->json([
                'success'        => true,
                'message'        => 'Berhasil mengambil daftar postingan Facebook Page secara live dari Meta API.',
                'page'           => [
                    'id'   => $page['id'],
                    'name' => $page['name'],
                ],
                'total_returned' => $result['total'],
                'fetch_complete' => $withStatus['is_complete'],
                'posts'          => $result['data'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Facebook Posts Test Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar postingan Facebook Page.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
