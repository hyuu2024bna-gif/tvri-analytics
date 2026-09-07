<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('platforms')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('followers')->default(0);
            $table->unsignedInteger('total_contents')->default(0);
            $table->unsignedBigInteger('total_views')->nullable()->default(null);
            $table->timestamps();

            $table->unique(['platform_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_stats_daily');
    }
};
