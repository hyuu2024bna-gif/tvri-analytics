<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TVRI Analytics Application Localization (Indonesian)
    |--------------------------------------------------------------------------
    */

    'nav' => [
        'dashboard' => 'Dashboard',
        'reports' => 'Laporan',
        'manual' => 'Input Manual',
        'instagram' => 'Instagram',
        'profile' => 'Profil',
        'logout' => 'Keluar',
        'language' => 'Bahasa',
        'settings' => 'Pengaturan',
    ],

    'platform' => [
        'all' => 'Semua Platform',
        'all_connected' => 'Semua Platform Terpadu',
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'coming_soon' => 'Segera',
        'filter_title' => 'Filter platform :name',
        'all_title' => 'Analitik gabungan seluruh platform',
    ],

    'kpi' => [
        'active_content' => 'Konten Aktif',
        'all_active_platforms' => 'Semua platform aktif',
        'platform_active' => 'Platform :name',
        'followers_subs' => 'Followers / Subs',
        'subscribers' => 'Subscribers',
        'followers' => 'Followers',
        'connected_account' => 'Akun terhubung',
        'all_connected_accounts' => 'Total akun terhubung',
        'as_of_date' => 'Per :date',
        'current_views' => 'Views Saat Ini',
        'cumulative_last_snapshot' => 'Kumulatif snapshot terakhir',
        'views_growth' => 'Views Bertambah',
        'total_likes' => 'Total Likes',
        'valid_likes_accumulated' => 'Akumulasi suka valid',
        'total_comments' => 'Total Komentar',
        'public_responses_accumulated' => 'Akumulasi respon publik',
        'content' => 'Konten',
        'videos' => 'Video',
        'posts' => 'Postingan',
        'engagement' => 'Interaksi',
        'metric_not_applicable' => 'Metrik tidak berlaku',
        'metric_unavailable' => 'Metrik tidak tersedia',
    ],

    'dashboard' => [
        'title' => 'Dashboard Analitik Konten TVRI Aceh',
        'subtitle' => 'Monitoring performa multi-platform: YouTube, Instagram, Facebook, dan TikTok',
        'all_platforms_badge' => 'Semua Platform Terpadu',
        'platform_badge' => 'Platform: :name',
        'snapshot' => 'Snapshot: :date',
        'latest_snapshot_footer' => 'Snapshot konten terakhir: :date',
        'footer_note' => 'Sistem Analitik TVRI Aceh • Data historis bersifat immutable',

        // Periods & Filters
        'period' => 'Periode',
        'today' => 'Hari Ini',
        'days_7' => '7 Hari',
        'days_15' => '15 Hari',
        'days_30' => '30 Hari',
        'days' => ':days Hari',
        'last_7_days' => '7 Hari Terakhir',
        'last_15_days' => '15 Hari Terakhir',
        'last_30_days' => '30 Hari Terakhir',
        'all_time' => 'Semua Waktu',
        'custom' => 'Custom',
        'custom_range' => 'Rentang Khusus',
        'range_active' => 'Rentang: :range',
        'from_date' => 'Dari Tanggal',
        'to_date' => 'Sampai Tanggal',
        'to' => 's/d',
        'apply' => 'Terapkan Rentang',
        'close' => 'Tutup',
        'reset' => 'Reset',
        'reset_to_7_days' => 'Reset filter ke 7 hari',

        // Platform Summary Section
        'platform_summary_title' => 'Ringkasan Performa Platform',
        'platform_summary_desc' => 'Perbandingan performa lintas YouTube, Instagram, Facebook, dan TikTok pada periode :period',
        'strict_null_active' => 'Strict NULL Semantics Aktif',
        'action' => 'Aksi',
        'detail' => 'Detail',

        // Daily Trend Section
        'trend_title' => 'Tren Performa Views Harian',
        'trend_desc_all' => 'Menampilkan metrik penayangan video harian dari YouTube',
        'trend_desc_single' => 'Menampilkan metrik penayangan video pada platform :name',
        'data_snapshot_active' => 'Data Snapshot Aktif',
        'views_metric_not_applicable_title' => 'Metrik Penayangan (Views) Tidak Berlaku untuk :target',
        'views_metric_not_applicable_desc' => 'Metrik streaming video views saat ini bersumber dari YouTube. Untuk platform seperti Instagram, TikTok, dan Facebook, analitik berfokus pada metrik interaksi (Followers, Likes, dan Komentar). Sesuai prinsip Strict NULL Semantics, angka penayangan tidak dipaksakan menjadi angka 0 palsu.',
        'all_these_content' => 'Semua Konten Ini',
        'chart_optimal_notice' => 'Grafik akan lebih optimal setelah minimal 2 hari snapshot terkumpul (tersedia :count titik data snapshot pada periode ini).',

        // Top 10 Section
        'top_10_title' => 'Top 10 Konten',
        'top_10_most_views_growth' => '— Paling Banyak Bertambah Views (:period)',
        'top_10_highest_views' => '— Views Tertinggi',
        'top_10_highest_likes' => '— Likes Tertinggi',
        'top_10_desc_all' => 'Peringkat konten terpopuler lintas seluruh platform TVRI Aceh',
        'top_10_desc_single' => 'Peringkat konten terpopuler platform :name',
        'top_10_count' => ':count Konten Teratas',
        'media' => 'Media',
        'content_title' => 'Judul Konten',
        'growth' => 'Bertambah',
        'no_media' => 'No Media',
        'no_active_content' => 'Tidak Ada Konten Aktif',
        'no_content_snapshot' => 'Tidak ada snapshot data konten yang ditemukan pada periode :period.',
    ],

    'status' => [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'deleted' => 'Dihapus',
        'coming_soon' => 'Segera',
    ],

    'messages' => [
        'data_unavailable' => 'Data tidak tersedia',
        'sync_failed' => 'Sinkronisasi gagal',
        'sync_successful' => 'Sinkronisasi berhasil',
        'loading' => 'Memuat...',
        'no_data_available' => 'Tidak ada data tersedia',
        'unavailable' => '—',
    ],

    'auth' => [
        'page_title' => 'Login | TVRI Aceh Analytics',
        'brand_title' => 'Dashboard Analitik<br>Media Sosial',
        'brand_subtitle' => 'TVRI Stasiun Aceh',
        'brand_description' => 'Memonitor performa konten media sosial untuk mendukung penyebaran informasi yang lebih luas dan tepat sasaran.',
        'tagline' => 'Media Pemersatu Bangsa',
        'welcome_title' => 'Selamat Datang',
        'welcome_desc' => 'Silakan masuk ke akun Anda untuk mengakses dashboard analitik media sosial TVRI Stasiun Aceh.',
        'email_or_username' => 'Email atau Username',
        'password' => 'Password',
        'show_password' => 'Tampilkan password',
        'hide_password' => 'Sembunyikan password',
        'remember_me' => 'Ingatkan saya',
        'forgot_password' => 'Lupa password?',
        'login_button' => 'Masuk',
        'login_failed' => 'Email atau password yang Anda masukkan tidak sesuai.',
        'copyright' => '© :year TVRI Stasiun Aceh',
    ],

    'legal' => [
        'terms_of_service' => 'Terms of Service',
        'privacy_policy' => 'Privacy Policy',
        'all_rights_reserved' => 'Hak Cipta Dilindungi.',
        'back_to_login' => 'Kembali ke Login',
        'back_to_dashboard' => 'Kembali ke Dashboard',
    ],
];
