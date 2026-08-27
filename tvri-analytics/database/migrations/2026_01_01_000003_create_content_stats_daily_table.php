<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->timestamps();

            // satu konten hanya boleh punya 1 snapshot per tanggal (mencegah data dobel kalau scheduler jalan 2x)
            $table->unique(['content_id', 'tanggal']);
            // index ini penting: nanti sering query "ambil semua stats tanggal tertentu" atau "tren 1 content urut tanggal"
            $table->index(['content_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_stats_daily');
    }
};
