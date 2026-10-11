<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            ['slug' => 'youtube',   'nama' => 'YouTube'],
            ['slug' => 'instagram', 'nama' => 'Instagram'],
            ['slug' => 'tiktok',    'nama' => 'TikTok'],
            ['slug' => 'facebook',  'nama' => 'Facebook'],
        ];

        foreach ($platforms as $platform) {
            DB::table('platforms')->updateOrInsert(
                ['slug' => $platform['slug']],
                [
                    'nama'       => $platform['nama'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
