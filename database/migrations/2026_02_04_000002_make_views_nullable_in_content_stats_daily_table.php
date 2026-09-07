<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_stats_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_stats_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0)->change();
        });
    }
};
