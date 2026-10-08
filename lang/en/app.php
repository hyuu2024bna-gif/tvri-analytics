<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TVRI Analytics Application Localization (English)
    |--------------------------------------------------------------------------
    */

    'nav' => [
        'dashboard' => 'Dashboard',
        'reports' => 'Reports',
        'manual' => 'Manual Input',
        'instagram' => 'Instagram',
        'profile' => 'Profile',
        'logout' => 'Log Out',
        'language' => 'Language',
        'settings' => 'Settings',
    ],

    'platform' => [
        'all' => 'All Platforms',
        'all_connected' => 'All Integrated Platforms',
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'coming_soon' => 'Coming Soon',
        'filter_title' => 'Filter :name platform',
        'all_title' => 'Cross-platform aggregated analytics',
    ],

    'kpi' => [
        'active_content' => 'Active Content',
        'all_active_platforms' => 'All active platforms',
        'platform_active' => ':name platform',
        'followers_subs' => 'Followers / Subs',
        'subscribers' => 'Subscribers',
        'followers' => 'Followers',
        'connected_account' => 'Connected account',
        'all_connected_accounts' => 'Total connected accounts',
        'as_of_date' => 'As of :date',
        'current_views' => 'Current Views',
        'cumulative_last_snapshot' => 'Cumulative latest snapshot',
        'views_growth' => 'Views Growth',
        'total_likes' => 'Total Likes',
        'valid_likes_accumulated' => 'Valid likes accumulated',
        'total_comments' => 'Total Comments',
        'public_responses_accumulated' => 'Public responses accumulated',
        'content' => 'Content',
        'videos' => 'Videos',
        'posts' => 'Posts',
        'engagement' => 'Engagement',
        'metric_not_applicable' => 'Metric not applicable',
        'metric_unavailable' => 'Metric unavailable',
    ],

    'dashboard' => [
        'title' => 'TVRI Aceh Content Analytics Dashboard',
        'subtitle' => 'Multi-platform performance monitoring: YouTube, Instagram, Facebook, and TikTok',
        'all_platforms_badge' => 'All Integrated Platforms',
        'platform_badge' => 'Platform: :name',
        'snapshot' => 'Snapshot: :date',
        'latest_snapshot_footer' => 'Latest content snapshot: :date',
        'footer_note' => 'TVRI Aceh Analytics System • Historical data is immutable',

        // Periods & Filters
        'period' => 'Period',
        'today' => 'Today',
        'days_7' => '7 Days',
        'days_15' => '15 Days',
        'days_30' => '30 Days',
        'days' => ':days Days',
        'last_7_days' => 'Last 7 Days',
        'last_15_days' => 'Last 15 Days',
        'last_30_days' => 'Last 30 Days',
        'all_time' => 'All Time',
        'custom' => 'Custom',
        'custom_range' => 'Custom Range',
        'range_active' => 'Range: :range',
        'from_date' => 'From Date',
        'to_date' => 'To Date',
        'to' => 'to',
        'apply' => 'Apply Range',
        'close' => 'Close',
        'reset' => 'Reset',
        'reset_to_7_days' => 'Reset filter to 7 days',

        // Platform Summary Section
        'platform_summary_title' => 'Platform Performance Summary',
        'platform_summary_desc' => 'Performance comparison across YouTube, Instagram, Facebook, and TikTok for period :period',
        'strict_null_active' => 'Strict NULL Semantics Active',
        'action' => 'Action',
        'detail' => 'Details',

        // Daily Trend Section
        'trend_title' => 'Daily Views Performance Trend',
        'trend_desc_all' => 'Displaying daily video views metrics from YouTube',
        'trend_desc_single' => 'Displaying video views metrics on platform :name',
        'data_snapshot_active' => 'Active Snapshot Data',
        'views_metric_not_applicable_title' => 'Views Metric Not Applicable to :target',
        'views_metric_not_applicable_desc' => 'Video streaming views metrics are currently sourced from YouTube. For platforms such as Instagram, TikTok, and Facebook, analytics focus on interaction metrics (Followers, Likes, and Comments). Under Strict NULL Semantics principles, views figures are not falsely converted to 0.',
        'all_these_content' => 'All This Content',
        'chart_optimal_notice' => 'The chart will be more optimal after at least 2 days of snapshots are collected (:count snapshot data points available in this period).',

        // Top 10 Section
        'top_10_title' => 'Top 10 Content',
        'top_10_most_views_growth' => '— Highest Views Growth (:period)',
        'top_10_highest_views' => '— Highest Views',
        'top_10_highest_likes' => '— Highest Likes',
        'top_10_desc_all' => 'Most popular content ranking across all TVRI Aceh platforms',
        'top_10_desc_single' => 'Most popular content ranking for platform :name',
        'top_10_count' => 'Top :count Content',
        'media' => 'Media',
        'content_title' => 'Content Title',
        'growth' => 'Growth',
        'no_media' => 'No Media',
        'no_active_content' => 'No Active Content',
        'no_content_snapshot' => 'No content snapshot data found for period :period.',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'deleted' => 'Deleted',
        'coming_soon' => 'Coming Soon',
    ],

    'messages' => [
        'data_unavailable' => 'Data unavailable',
        'sync_failed' => 'Sync failed',
        'sync_successful' => 'Sync successful',
        'loading' => 'Loading...',
        'no_data_available' => 'No data available',
        'unavailable' => '—',
    ],

    'auth' => [
        'page_title' => 'Login | TVRI Aceh Analytics',
        'brand_title' => 'Social Media<br>Analytics Dashboard',
        'brand_subtitle' => 'TVRI Aceh Station',
        'brand_description' => 'Monitoring social media content performance to support wider and more targeted information dissemination.',
        'tagline' => 'Uniting the Nation',
        'welcome_title' => 'Welcome Back',
        'welcome_desc' => 'Please log in to your account to access TVRI Aceh Station social media analytics dashboard.',
        'email_or_username' => 'Email or Username',
        'password' => 'Password',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'login_button' => 'Log In',
        'login_failed' => 'These credentials do not match our records.',
        'copyright' => '© :year TVRI Aceh Station',
    ],

    'legal' => [
        'terms_of_service' => 'Terms of Service',
        'privacy_policy' => 'Privacy Policy',
        'all_rights_reserved' => 'All Rights Reserved.',
        'back_to_login' => 'Back to Login',
        'back_to_dashboard' => 'Back to Dashboard',
    ],
];
