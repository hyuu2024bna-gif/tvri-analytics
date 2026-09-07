<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_stats_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('followers')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('platform_stats_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('followers')->default(0)->change();
        });
    }
};
