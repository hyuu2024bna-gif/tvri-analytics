<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom refresh_token dan refresh_expires_at ke tabel social_accounts.
     * Kedua kolom nullable agar data lama (Instagram, Facebook, TikTok testing)
     * yang belum memiliki refresh token tetap dapat dibaca tanpa error.
     */
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            // Refresh token yang dikembalikan TikTok saat OAuth.
            // Disimpan terenkripsi (via mutator model). Nullable untuk backward compatibility.
            $table->text('refresh_token')->nullable()->after('access_token');

            // Waktu kedaluwarsa refresh token (NOW + refresh_expires_in).
            // Nullable agar akun lama yang belum memiliki refresh token tetap valid.
            $table->timestamp('refresh_expires_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['refresh_token', 'refresh_expires_at']);
        });
    }
};
