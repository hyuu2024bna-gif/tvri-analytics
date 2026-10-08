<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legal Localization (English) - KMB TVRI Analytics
    |--------------------------------------------------------------------------
    */

    'terms_of_service' => 'Terms of Service',
    'terms_of_service_title' => 'Terms of Service',
    'privacy_policy' => 'Privacy Policy',
    'privacy_policy_title' => 'Privacy Policy',
    'back_to_login' => 'Back to Login',
    'back_to_home' => 'Back to Home',
    'last_updated' => 'Last updated: September 2026',
    'app_name' => 'KMB TVRI Analytics',

    'terms' => [
        'heading' => 'Terms of Service',
        'subheading' => 'Terms of Use for KMB TVRI Analytics Application',
        'last_updated' => 'Last updated: September 2026',

        'intro' => 'Welcome to KMB TVRI Analytics. Please read these Terms of Service carefully before accessing or using our application.',

        'sections' => [
            'acceptance' => [
                'title' => '1. Acceptance of Terms',
                'content' => 'By accessing or using the KMB TVRI Analytics application, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you may not access or use this service.',
            ],
            'description' => [
                'title' => '2. Description of Service',
                'content' => 'KMB TVRI Analytics is an internal analytics dashboard application developed to manage, monitor, and analyze the social media content performance of TVRI Aceh Station. The application integrates metric data from supported third-party platforms: YouTube, Instagram, Facebook, and TikTok.',
            ],
            'authorized_use' => [
                'title' => '3. Authorized Use',
                'content' => 'This application is intended solely for authorized personnel officially designated by TVRI Aceh Station. Users are required to maintain the confidentiality of their credentials and must not misuse the system, exploit security vulnerabilities, compromise data integrity, or utilize data for purposes outside official duties.',
            ],
            'third_party' => [
                'title' => '4. Third-Party Platforms',
                'content' => 'YouTube, Instagram, Facebook, and TikTok are independent third-party platforms. Data access and integration functionality within KMB TVRI Analytics are subject to the terms, conditions, and developer policies of each respective platform. TVRI Aceh Station does not control third-party platform operations or policies.',
            ],
            'data_analytics' => [
                'title' => '5. Data and Analytics',
                'content' => 'All statistical and content performance data displayed originates from the respective platform application programming interfaces (APIs). Data may change or update periodically based on availability from platform providers. This dashboard serves as an internal monitoring tool and is not a substitute for official platform reporting.',
            ],
            'availability' => [
                'title' => '6. Availability',
                'content' => 'Application services may experience scheduled maintenance downtime, network disruptions, API rate limiting, or upstream modifications from third-party platforms. While the system is actively maintained for high reliability, uninterrupted service availability cannot be guaranteed.',
            ],
            'security' => [
                'title' => '7. Security',
                'content' => 'Users and administrators must safeguard system access, credentials, and integration tokens. KMB TVRI Analytics is designed to securely store authentication tokens and never exposes raw secrets or sensitive access tokens to users within the interface or logs.',
            ],
            'intellectual_property' => [
                'title' => '8. Intellectual Property',
                'content' => 'All copyrights, trademarks, and ownership of TVRI Aceh Station content, logos, broadcasts, and media remain the property of LPP TVRI. Third-party trademarks and content remain the intellectual property of their respective platform owners.',
            ],
            'liability' => [
                'title' => '9. Limitation of Liability',
                'content' => 'KMB TVRI Analytics is provided on an "as is" and "as available" basis. The operators shall not be liable for any indirect or consequential damages resulting from data inaccuracies caused by third-party API changes, synchronization delays, or connectivity issues beyond reasonable control.',
            ],
            'changes' => [
                'title' => '10. Changes to Terms',
                'content' => 'These Terms of Service may be updated from time to time in accordance with technical enhancements, internal administrative policies, or third-party platform regulations. Any revisions will be published on this page with an updated revision date.',
            ],
            'contact' => [
                'title' => '11. Contact',
                'content' => 'If you have any questions regarding these Terms of Service, please contact the administrator:',
                'administrator' => '[System Administrator / KMB TVRI Analytics Team - TVRI Aceh Station]',
            ],
        ],
    ],

    'privacy' => [
        'heading' => 'Privacy Policy',
        'subheading' => 'Privacy Policy for KMB TVRI Analytics Application',
        'last_updated' => 'Last updated: September 2026',

        'intro' => 'This Privacy Policy explains how KMB TVRI Analytics collects, processes, stores, and protects data in the operation of the social media analytics dashboard for TVRI Aceh Station.',

        'sections' => [
            'information' => [
                'title' => '1. Information We Collect',
                'content' => 'KMB TVRI Analytics only collects and processes data necessary for API connectivity and analytics functions, including:
• Account and platform configuration required for authorized API connections
• Unique platform account identifiers (YouTube Channel ID, Instagram Page ID, Facebook Page ID, TikTok Open ID)
• Official usernames or display names when provided by platform APIs
• Account-level statistics (such as subscriber and follower counts)
• Public content metadata (post/video titles, content URLs, publish timestamps, public thumbnail URLs)
• Content performance metrics (views, likes, comments provided by APIs)
• Technical session and authentication records for internal application users.',
            ],
            'integrations' => [
                'title' => '2. Platform Integrations',
                'content' => 'The application integrates through official developer APIs with four social media platforms:
• YouTube (Google / YouTube Data API & Analytics API)
• Instagram (Meta Graph API / Instagram Graph API)
• Facebook (Meta Graph API)
• TikTok (TikTok API for Developers / Login Kit & Video List API)',
            ],
            'oauth_tokens' => [
                'title' => '3. OAuth and Access Tokens',
                'content' => 'Access tokens are granted through official OAuth mechanisms authorized by administrators of the official TVRI Aceh Station accounts. These tokens are used solely to query official platform APIs. The system never displays access tokens on dashboard pages, public interfaces, or application logs. Specifically, TikTok access tokens are stored in the database in encrypted form using application-level encryption. The system does not store TikTok refresh tokens as the current database schema does not include a refresh token storage column.',
            ],
            'data_use' => [
                'title' => '4. How We Use Data',
                'content' => 'Collected data is strictly used to:
• Render unified monitoring dashboards for the TVRI Aceh Station team
• Generate daily trend charts and visual content analytics
• Export periodic performance reports (PDF and Excel formats)
• Execute scheduled automated synchronization of statistical metrics
Data is never utilized for commercial advertising, user profiling, or activities outside official analytics functions.',
            ],
            'storage_security' => [
                'title' => '5. Data Storage and Security',
                'content' => 'Statistical metrics are stored in an application database deployed on secured server infrastructure with restricted access controls. Sensitive credentials are encrypted at rest. The application enforces role-based access control and standard web security protections.',
            ],
            'sharing' => [
                'title' => '6. Data Sharing',
                'content' => 'KMB TVRI Analytics does not sell, rent, trade, or distribute data to third parties for commercial gain. Data is processed internally and solely to support the social media monitoring operations of TVRI Aceh Station.',
            ],
            'retention' => [
                'title' => '7. Data Retention',
                'content' => 'Statistical snapshots and metric histories are maintained as long as needed for operational analytics and historical reporting purposes. Daily snapshots are stored immutably to ensure consistent long-term trend analysis.',
            ],
            'user_rights' => [
                'title' => '8. User Rights / Data Requests',
                'content' => 'Authorized internal users may submit inquiries, data verification requests, or integration adjustments through standard internal coordination channels with the administrative team.',
            ],
            'third_party_policies' => [
                'title' => '9. Third-Party Privacy Policies',
                'content' => 'The handling of data on external platforms is governed by the respective privacy policies of those providers:',
                'links' => [
                    'Google / YouTube' => 'https://policies.google.com/privacy',
                    'Meta (Facebook & Instagram)' => 'https://www.facebook.com/privacy/policy',
                    'TikTok' => 'https://www.tiktok.com/legal/privacy-policy',
                ],
            ],
            'changes' => [
                'title' => '10. Changes to Privacy Policy',
                'content' => 'This Privacy Policy may be updated periodically to remain aligned with application enhancements, third-party API revisions, or regulatory compliance standards.',
            ],
            'contact' => [
                'title' => '11. Contact',
                'content' => 'For questions or further information regarding this Privacy Policy for KMB TVRI Analytics, please contact:',
                'administrator' => '[System Administrator / KMB TVRI Analytics Team - TVRI Aceh Station]',
            ],
        ],
    ],
];
