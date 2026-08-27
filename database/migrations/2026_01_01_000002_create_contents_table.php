<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('platforms')->cascadeOnDelete();
            $table->string('content_id_external', 100)->index(); // ID video/postingan di platform aslinya
            $table->string('judul');
            $table->string('url');
            $table->string('thumbnail_url')->nullable();
            $table->date('tanggal_upload')->nullable();
            $table->enum('status', ['aktif', 'dihapus'])->default('aktif');
            $table->timestamps();

            // satu content_id_external unik per platform (video ID YouTube tidak boleh dobel didaftarkan)
            $table->unique(['platform_id', 'content_id_external']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
