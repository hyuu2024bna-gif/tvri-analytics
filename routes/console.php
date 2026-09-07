<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::command('youtube:sync')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(60) // Lock expiration 60 menit (mencegah overlapping jika run sebelumnya hang/crash)
    ->appendOutputTo(storage_path('logs/youtube-sync.log'))
    ->before(function () {
        Log::info('YouTube scheduled sync started');
    })
    ->onSuccess(function () {
        Log::info('YouTube scheduled sync completed');
    })
    ->onFailure(function () {
        Log::error('Scheduled youtube:sync GAGAL dijalankan. Cek log di storage/logs/youtube-sync.log');
    });


Schedule::command('instagram:sync')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(60) // maksimal 60 menit, cegah tumpang tindih kalau sync sebelumnya belum selesai
    ->appendOutputTo(storage_path('logs/instagram-sync.log'))
    ->before(function () {
        Log::info('Instagram scheduled sync started');
    })
    ->onSuccess(function () {
        Log::info('Instagram scheduled sync completed');
    })
    ->onFailure(function () {
        Log::error('Scheduled instagram:sync GAGAL dijalankan. Cek log di storage/logs/instagram-sync.log');
    });

Schedule::command('facebook:sync')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(60) // maksimal 60 menit, cegah tumpang tindih kalau sync sebelumnya belum selesai
    ->appendOutputTo(storage_path('logs/facebook-sync.log'))
    ->before(function () {
        Log::info('Facebook scheduled sync started');
    })
    ->onSuccess(function () {
        Log::info('Facebook scheduled sync completed');
    })
    ->onFailure(function () {
        Log::error('Scheduled facebook:sync GAGAL dijalankan. Cek log di storage/logs/facebook-sync.log');
    });


