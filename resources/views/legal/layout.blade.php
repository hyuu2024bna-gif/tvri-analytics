<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | KMB TVRI Analytics</title>

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Bootstrap Icons --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        :root {
            --tvri-blue: #003882;
            --tvri-blue-light: #0757c9;
            --tvri-blue-hover: #00265c;
            --tvri-blue-subtle: #e8f0fe;
            --text-dark: #142957;
            --text-muted: #536a99;
            --border-color: #dbe4f3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f8fbff;
            color: #1e293b;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .legal-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 56, 130, 0.05);
            padding: 1rem 0;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .header-brand img {
            height: 38px;
            width: auto;
        }

        .brand-text-container {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .brand-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--tvri-blue);
            letter-spacing: -0.02em;
        }

        .brand-station {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Nav actions */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--tvri-blue);
            background-color: var(--tvri-blue-subtle);
            border: 1px solid #bfdbfe;
            padding: 0.45rem 0.95rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .btn-back:hover {
            background-color: #dbeafe;
            color: var(--tvri-blue-hover);
        }

        /* Document Card */
        .legal-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 4px 14px rgba(7, 87, 201, 0.04);
            padding: 2.5rem 3rem;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .legal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.35rem 0.75rem;
            background-color: var(--tvri-blue-subtle);
            color: var(--tvri-blue);
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 9999px;
            border: 1px solid #bfdbfe;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .legal-title {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            margin-top: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .legal-subtitle {
            font-size: 1.05rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .legal-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85rem;
            color: #64748b;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .legal-intro {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #334155;
            margin-bottom: 2rem;
            padding: 1rem 1.25rem;
            background-color: #f8fafc;
            border-left: 4px solid var(--tvri-blue);
            border-radius: 0 0.5rem 0.5rem 0;
        }

        /* Sections */
        .legal-section {
            margin-bottom: 2.25rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .section-body {
            font-size: 0.95rem;
            line-height: 1.75;
            color: #334155;
            white-space: pre-line;
        }

        .section-links {
            list-style: none;
            padding-left: 0;
            margin-top: 0.75rem;
        }

        .section-links li {
            margin-bottom: 0.5rem;
        }

        .section-links a {
            color: var(--tvri-blue-light);
            text-decoration: none;
            font-weight: 600;
            word-break: break-all;
        }

        .section-links a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .legal-footer {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 2rem 0;
            color: #64748b;
            font-size: 0.875rem;
        }

        .footer-links a {
            color: #64748b;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: color 0.15s ease;
        }

        .footer-links a:hover,
        .footer-links a.active {
            color: var(--tvri-blue);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .legal-card {
                padding: 1.75rem 1.25rem;
                margin-top: 1rem;
                border-radius: 0.75rem;
            }

            .legal-title {
                font-size: 1.75rem;
            }

            .brand-name {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

    {{-- Top Header --}}
    <header class="legal-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ url('/') }}" class="header-brand">
                <img src="{{ asset('images/tvri-logo.webp') }}" alt="TVRI Logo">
                <div class="brand-text-container">
                    <span class="brand-name">KMB TVRI Analytics</span>
                    <span class="brand-station">TVRI Stasiun Aceh</span>
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                {{-- Language Switcher --}}
                <div class="btn-group btn-group-sm" role="group" aria-label="Language selector">
                    <a href="{{ route('locale.switch', 'id') }}"
                       class="btn {{ app()->getLocale() === 'id' ? 'btn-primary' : 'btn-outline-primary' }}"
                       style="font-size: 12px; font-weight: 700; padding: 3px 10px;">
                        🇮🇩 ID
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="btn {{ app()->getLocale() === 'en' ? 'btn-primary' : 'btn-outline-primary' }}"
                       style="font-size: 12px; font-weight: 700; padding: 3px 10px;">
                        🇬🇧 EN
                    </a>
                </div>

                {{-- Back Navigation --}}
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-back">
                        <i class="bi bi-speedometer2"></i>
                        <span>{{ __('app.legal.back_to_dashboard') }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-back">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>{{ __('app.legal.back_to_login') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="container">
        <div class="legal-card">
            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="legal-footer text-center">
        <div class="container">
            <div class="footer-links mb-2">
                <a href="{{ route('terms') }}" class="{{ request()->routeIs('terms') ? 'active' : '' }}">
                    {{ __('legal.terms_of_service') }}
                </a>
                <span>•</span>
                <a href="{{ route('privacy') }}" class="{{ request()->routeIs('privacy') ? 'active' : '' }}">
                    {{ __('legal.privacy_policy') }}
                </a>
                <span>•</span>
                <a href="{{ route('login') }}">
                    {{ __('app.auth.login_button') }}
                </a>
            </div>
            <div>
                © {{ date('Y') }} TVRI Stasiun Aceh — <strong>KMB TVRI Analytics</strong>. {{ __('app.legal.all_rights_reserved') }}
            </div>
        </div>
    </footer>

</body>
</html>
