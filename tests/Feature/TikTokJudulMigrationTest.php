<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * TikTokJudulMigrationTest
 *
 * Memverifikasi bahwa kolom `contents.judul` menerima string yang lebih
 * panjang dari 255 karakter (TEXT) tanpa memotong atau mengembalikan error.
 *
 * Latar belakang:
 *   Sync pertama akun resmi TikTok TVRI Aceh menghasilkan 59 error:
 *   SQLSTATE[22001]: Data too long for column 'judul' at row 1
 *   karena kolom aslinya adalah VARCHAR(255).
 *
 * Test suite ini TIDAK memanggil TikTok API dan TIDAK memerlukan
 * OAuth / token / jaringan eksternal.
 */
class TikTokJudulMigrationTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────
    // Test 1: Skema — kolom judul bukan string/varchar setelah migrasi
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_kolom_judul_bertipe_text_setelah_migrasi(): void
    {
        // Menggunakan Schema::getColumnType() yang kompatibel baik dengan SQLite (:memory: di testing) maupun MySQL di production
        $columnType = Schema::getColumnType('contents', 'judul');

        $this->assertEquals(
            'text',
            strtolower($columnType),
            "Kolom judul harus bertipe text, tetapi saat ini bertipe: {$columnType}"
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 2: Insert — judul tepat 255 karakter berhasil disimpan
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_judul_255_karakter_berhasil_disimpan(): void
    {
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        $judul255 = str_repeat('A', 255);

        $content = Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => 'test_255_chars',
            'judul'               => $judul255,
            'url'                 => 'https://www.tiktok.com/@video/test_255',
            'status'              => 'aktif',
        ]);

        $this->assertDatabaseHas('contents', [
            'content_id_external' => 'test_255_chars',
        ]);

        $fresh = Content::find($content->id);
        $this->assertEquals(
            255,
            mb_strlen($fresh->judul),
            'Judul 255 karakter harus tersimpan utuh tanpa pemotongan.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 3: Insert — judul > 255 karakter berhasil disimpan (regression fix)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_judul_lebih_dari_255_karakter_berhasil_disimpan(): void
    {
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        // Simulasi caption TikTok panjang (300 karakter) — seperti yang menyebabkan 59 error
        $judulPanjang = str_repeat('B', 300);

        $content = Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => 'test_long_caption_300',
            'judul'               => $judulPanjang,
            'url'                 => 'https://www.tiktok.com/@video/test_long',
            'status'              => 'aktif',
        ]);

        $this->assertDatabaseHas('contents', [
            'content_id_external' => 'test_long_caption_300',
        ]);

        $fresh = Content::find($content->id);

        // Pastikan tidak ada pemotongan diam-diam
        $this->assertEquals(
            300,
            mb_strlen($fresh->judul),
            'Judul 300 karakter harus tersimpan utuh. Ini adalah regression test untuk 59 TikTok sync errors.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 4: Insert — judul sangat panjang (1000 karakter) berhasil disimpan
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_judul_1000_karakter_berhasil_disimpan(): void
    {
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        $judul1000 = str_repeat('C', 1000);

        $content = Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => 'test_1000_chars',
            'judul'               => $judul1000,
            'url'                 => 'https://www.tiktok.com/@video/test_1000',
            'status'              => 'aktif',
        ]);

        $fresh = Content::find($content->id);

        $this->assertEquals(
            1000,
            mb_strlen($fresh->judul),
            'Judul 1000 karakter harus tersimpan penuh setelah kolom diubah ke TEXT.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 5: Update — judul panjang dapat di-update tanpa error
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_update_judul_ke_teks_panjang_berhasil(): void
    {
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        // Buat record dengan judul pendek terlebih dahulu
        $content = Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => 'test_update_judul',
            'judul'               => 'Judul singkat awal',
            'url'                 => 'https://www.tiktok.com/@video/update_test',
            'status'              => 'aktif',
        ]);

        // Update ke judul panjang (simulasi sync ulang TikTok)
        $judulBaru = str_repeat('Update ', 50); // 350 karakter
        $content->update(['judul' => $judulBaru]);

        $fresh = Content::find($content->id);

        $this->assertEquals(
            mb_strlen($judulBaru),
            mb_strlen($fresh->judul),
            'Update judul ke string panjang harus berhasil tanpa pemotongan.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 6: Multi-platform — kolom judul TEXT tidak merusak YouTube/Instagram
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_judul_text_tidak_merusak_platform_lain(): void
    {
        $platforms = [
            ['slug' => 'youtube',   'nama' => 'YouTube'],
            ['slug' => 'instagram', 'nama' => 'Instagram'],
            ['slug' => 'facebook',  'nama' => 'Facebook'],
        ];

        foreach ($platforms as $idx => $platformData) {
            $platform = Platform::firstOrCreate(
                ['slug' => $platformData['slug']],
                ['nama' => $platformData['nama']]
            );

            $content = Content::create([
                'platform_id'         => $platform->id,
                'content_id_external' => "ext_{$platformData['slug']}_001",
                'judul'               => "Judul normal untuk {$platformData['nama']}",
                'url'                 => "https://example.com/{$platformData['slug']}/001",
                'status'              => 'aktif',
            ]);

            $this->assertDatabaseHas('contents', [
                'content_id_external' => "ext_{$platformData['slug']}_001",
                'judul'               => "Judul normal untuk {$platformData['nama']}",
            ]);
        }

        $this->assertEquals(3, Content::count());
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 7: Judul NULL tidak diizinkan (kolom NOT NULL)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_judul_tidak_boleh_null(): void
    {
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        $this->expectException(\Illuminate\Database\QueryException::class);

        Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => 'test_null_judul',
            'judul'               => null,
            'url'                 => 'https://www.tiktok.com/@video/null_test',
            'status'              => 'aktif',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test 8: Karakter Unicode/emoji dalam judul tersimpan dengan benar
    // ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * @group migration
     */
    public function test_judul_dengan_unicode_dan_emoji_tersimpan_benar(): void
    {
        $platform = Platform::firstOrCreate(
            ['slug' => 'tiktok'],
            ['nama' => 'TikTok']
        );

        // Caption TikTok sering mengandung emoji dan karakter non-ASCII
        $judulUnicode = '🎬 TVRI ACEH 📺 | Berita Terkini Aceh 🗞️ | #TVRIAceh #Berita #Aceh 🌙✨';

        $content = Content::create([
            'platform_id'         => $platform->id,
            'content_id_external' => 'test_unicode_emoji',
            'judul'               => $judulUnicode,
            'url'                 => 'https://www.tiktok.com/@video/emoji_test',
            'status'              => 'aktif',
        ]);

        $fresh = Content::find($content->id);

        $this->assertEquals(
            $judulUnicode,
            $fresh->judul,
            'Judul dengan emoji dan karakter Unicode harus tersimpan dan dibaca kembali dengan tepat.'
        );
    }
}
