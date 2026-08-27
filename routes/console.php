<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('youtube:sync')
    ->dailyAt('01:00')
    ->withoutOverlapping(60) // maksimal 60 menit, cegah tumpang tindih kalau sync sebelumnya belum selesai
    ->appendOutputTo(storage_path('logs/youtube-sync.log'))
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled youtube:sync GAGAL dijalankan. Cek log di storage/logs/youtube-sync.log');
    });
