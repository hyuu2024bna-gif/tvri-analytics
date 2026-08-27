<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedBigInteger('subscriber_count')->default(0);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->unsignedInteger('video_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_stats_daily');
    }
};
