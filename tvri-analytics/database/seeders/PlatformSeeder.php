<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('platforms')->insert([
            ['nama' => 'YouTube', 'slug' => 'youtube', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Instagram', 'slug' => 'instagram', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'TikTok', 'slug' => 'tiktok', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Facebook', 'slug' => 'facebook', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
