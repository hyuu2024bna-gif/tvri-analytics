<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Ubah kolom `contents.judul` dari VARCHAR(255) ke TEXT.
 *
 * ALASAN:
 * TikTok memungkinkan caption/judul video melebihi 255 karakter.
 * Sync pertama akun resmi TVRI Aceh (@tvriacehofficial) menghasilkan 59 error:
 *   SQLSTATE[22001]: String data, right truncated for column 'judul' at row 1
 * TEXT (max 65.535 byte di MySQL) menghilangkan batasan ini tanpa efek
 * negatif pada platform lain (YouTube, Instagram, Facebook).
 *
 * DOWN:
 * Rollback menyesuaikan kembali ke VARCHAR(255) — catatan: data yang sudah
 * lebih panjang dari 255 karakter akan dipotong oleh MySQL saat rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->text('judul')->change();
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            // PERINGATAN: rollback dapat memotong nilai yang > 255 karakter.
            $table->string('judul', 255)->change();
        });
    }
};
