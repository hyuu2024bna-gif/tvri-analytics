<x-app-layout>
    {{-- TVRI Brand Styling & Button Design System --}}
    <style>
        :root {
            --tvri-blue: #003882;          /* Biru Utama TVRI */
            --tvri-blue-hover: #00265c;    /* Biru TVRI Gelap */
            --tvri-blue-light: #e8f0fe;    /* Biru TVRI Sangat Muda (Hover) */
            --tvri-blue-border: #93c5fd;   /* Border Biru Muda */
            --tvri-text-dark: #002b66;     /* Teks Biru Tua TVRI */
            --tvri-border-gray: #cbd5e1;   /* Border Abu Halus */
        }

        /* Platform Navigation Filter Buttons */
        .tvri-btn-platform {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.15rem;
            border-radius: 0.625rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.25rem;
            text-decoration: none;
            cursor: pointer;
            background-color: #ffffff;
            color: var(--tvri-text-dark);
            border: 1.5px solid var(--tvri-border-gray);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            user-select: none;
            opacity: 1 !important;
        }

        .tvri-btn-platform:hover {
            background-color: var(--tvri-blue-light);
            color: var(--tvri-blue-hover);
            border-color: var(--tvri-blue);
            box-shadow: 0 2px 4px rgba(0, 56, 130, 0.12);
        }

        .tvri-btn-platform:focus, .tvri-btn-platform:focus-visible {
            outline: 2px solid var(--tvri-blue);
            outline-offset: 2px;
        }

        /* Active Platform Button State */
        .tvri-btn-platform.is-active {
            background-color: var(--tvri-blue) !important;
            color: #ffffff !important;
            border-color: var(--tvri-blue) !important;
            box-shadow: 0 2px 6px rgba(0, 56, 130, 0.35) !important;
        }

        .tvri-btn-platform.is-active .tvri-badge-status {
            background-color: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.35) !important;
        }

        .tvri-badge-status {
            font-size: 0.6875rem;
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            font-weight: 700;
            letter-spacing: 0.025em;
        }

        /* Date Period Filter Button */
        .tvri-btn-period {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.42rem 0.85rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1rem;
            text-decoration: none;
            cursor: pointer;
            background-color: #ffffff;
            color: var(--tvri-text-dark);
            border: 1.5px solid var(--tvri-border-gray);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            user-select: none;
            opacity: 1 !important;
        }

        .tvri-btn-period:hover {
            background-color: var(--tvri-blue-light);
            color: var(--tvri-blue-hover);
            border-color: var(--tvri-blue);
        }

        .tvri-btn-period:focus, .tvri-btn-period:focus-visible {
            outline: 2px solid var(--tvri-blue);
            outline-offset: 1px;
        }

        /* Active Period Button State */
        .tvri-btn-period.is-active {
            background-color: var(--tvri-blue) !important;
            color: #ffffff !important;
            border-color: var(--tvri-blue) !important;
            font-weight: 800 !important;
            font-style: normal !important;
            letter-spacing: -0.01em !important;
            box-shadow: 0 2px 6px rgba(0, 56, 130, 0.35) !important;
        }

        /* Ensure Tailwind font-semibold doesn't override active state */
        .tvri-btn-period.is-active.font-semibold,
        .tvri-btn-period.is-active.font-extrabold {
            font-weight: 800 !important;
        }

        /* Custom Date Range Container & Inputs */
        .tvri-custom-date-box {
            background-color: #f8fafc;
            border: 1.5px solid #bfdbfe;
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
        }

        .tvri-date-input {
            background-color: #ffffff !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            padding: 0.4rem 0.65rem !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            color: #0f172a !important;
            transition: all 0.15s !important;
        }

        .tvri-date-input:focus {
            border-color: var(--tvri-blue) !important;
            outline: 2px solid var(--tvri-blue) !important;
            outline-offset: 1px !important;
            background-color: #ffffff !important;
        }

        .tvri-btn-apply {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1rem;
            background-color: var(--tvri-blue);
            color: #ffffff !important;
            border: 1.5px solid var(--tvri-blue);
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            box-shadow: 0 1px 2px rgba(0, 56, 130, 0.25);
        }

        .tvri-btn-apply:hover {
            background-color: var(--tvri-blue-hover);
            border-color: var(--tvri-blue-hover);
            box-shadow: 0 2px 4px rgba(0, 56, 130, 0.35);
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg font-bold text-base shadow-sm text-white" style="background-color: #003882;">
                        TV
                    </span>
                    <h2 class="font-bold text-xl sm:text-2xl text-slate-900 tracking-tight">
                        {{ __('app.dashboard.title') }}
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ __('app.dashboard.subtitle') }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold" style="{{ $isAll ? 'background-color: #e8f0fe; color: #003882; border: 1px solid #bfdbfe;' : 'background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;' }}">
                    @if($isAll)
                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: #003882;"></span>
                        {{ __('app.dashboard.all_platforms_badge') }}
                    @else
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                        {{ __('app.dashboard.platform_badge', ['name' => $currentPlatform->nama]) }}
                    @endif
                </span>

                @if($latestSnapshotDate)
                    <span class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium bg-white text-slate-700 border border-slate-300 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{ __('app.dashboard.snapshot', ['date' => \Carbon\Carbon::parse($latestSnapshotDate)->translatedFormat('d M Y')]) }}
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- 1. PLATFORM NAVIGATION TABS --}}
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex flex-wrap items-center gap-2.5">
                @php
                    // Ambil seluruh platform terdaftar (YouTube, Instagram, TikTok, Facebook)
                    $allPlatformsList = \App\Models\Platform::orderBy('id')->get();
                @endphp

                {{-- Mode Semua Platform --}}
                <a href="{{ route('dashboard', array_filter(['platform' => 'all', 'period' => $period, 'start_date' => $customStart, 'end_date' => $customEnd])) }}"
                   class="tvri-btn-platform {{ $isAll ? 'is-active' : '' }}"
                   title="{{ __('app.platform.all_title') }}">
                    {{-- Globe icon (Lucide) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span>{{ __('app.platform.all') }}</span>
                    @if($isAll)
                        <span class="tvri-badge-status">{{ __('app.status.active') }}</span>
                    @endif
                </a>

                {{-- Individual Platforms (YouTube, Instagram, TikTok, Facebook) --}}
                @foreach($allPlatformsList as $p)
                    @php
                        $isActive = (! $isAll && $currentPlatform && $currentPlatform->id === $p->id);
                        $isSyncEnabled = (bool) config("services.sync.{$p->slug}", in_array($p->slug, ['youtube', 'tiktok']));
                        $targetUrl = route('dashboard', array_filter(['platform' => $p->slug, 'period' => $period, 'start_date' => $customStart, 'end_date' => $customEnd]));
                    @endphp

                    <a href="{{ $targetUrl }}"
                       class="tvri-btn-platform {{ $isActive ? 'is-active' : '' }}"
                       title="{{ __('app.platform.filter_title', ['name' => $p->nama]) }}">
                        @if($p->slug === 'youtube')
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: {{ $isActive ? '#ffffff' : '#dc2626' }};"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                        @elseif($p->slug === 'instagram')
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: {{ $isActive ? '#ffffff' : '#e1306c' }};"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                        @elseif($p->slug === 'facebook')
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: {{ $isActive ? '#ffffff' : '#1877f2' }};"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        @elseif($p->slug === 'tiktok')
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: {{ $isActive ? '#ffffff' : '#0f172a' }};"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                        @else
                            <span class="inline-block w-2 h-2 rounded-full bg-slate-400"></span>
                        @endif

                        <span>{{ $p->nama }}</span>

                        @if($isActive)
                            <span class="tvri-badge-status">{{ __('app.status.active') }}</span>
                        @elseif(! $isSyncEnabled)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-normal">Belum diaktifkan</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Banner Pemberitahuan Platform Belum Diaktifkan (Production Phase 1) --}}
        @if(! $isAll && $currentPlatform && ! config("services.sync.{$currentPlatform->slug}", in_array($currentPlatform->slug, ['youtube', 'tiktok'])))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-xs text-amber-800 space-y-1">
                    <p class="font-bold text-sm text-amber-900">{{ $currentPlatform->nama }} Belum Diaktifkan (Production Tahap 1)</p>
                    <p>Integrasi dan sinkronisasi otomatis untuk platform {{ $currentPlatform->nama }} sedang dinonaktifkan sementara. Saat ini analitik aktif difokuskan pada YouTube dan TikTok.</p>
                </div>
            </div>
        @endif

        {{-- 2. PERIOD FILTER BAR & CUSTOM DATE TOGGLE --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                {{-- Quick Filter Pills --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider mr-1">{{ __('app.dashboard.period') }}:</span>
                    @php
                        $periods = [
                            'today' => __('app.dashboard.today'),
                            '7'     => __('app.dashboard.days_7'),
                            '15'    => __('app.dashboard.days_15'),
                            '30'    => __('app.dashboard.days_30'),
                            'all'   => __('app.dashboard.all_time'),
                        ];
                    @endphp
                    @foreach($periods as $val => $label)
                        @php $isPeriodActive = ((string) $period === (string) $val); @endphp
                        <a href="{{ route('dashboard', ['platform' => $platformSlug, 'period' => $val]) }}"
                           class="tvri-btn-period {{ $isPeriodActive ? 'is-active' : '' }}"
                           style="{{ $isPeriodActive ? 'font-weight: 800;' : 'font-weight: 600;' }}"
                           @if($isPeriodActive) aria-current="page" @endif>
                            @if($isPeriodActive)
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-white mr-0.5"></span>
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach

                    {{-- Tombol "Custom" --}}
                    @php $isCustomActive = ($period === 'custom'); @endphp
                    <button type="button"
                            onclick="toggleCustomDateForm()"
                            id="btn-custom-toggle"
                            class="tvri-btn-period {{ $isCustomActive ? 'is-active' : '' }}"
                            style="{{ $isCustomActive ? 'font-weight: 800;' : 'font-weight: 600;' }}"
                            title="{{ __('app.dashboard.custom_range') }}"
                            @if($isCustomActive) aria-current="page" @endif>
                        @if($isCustomActive)
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-white mr-0.5"></span>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle shrink-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        @endif
                        <span>{{ __('app.dashboard.custom') }}</span>
                        @if($isCustomActive)
                            <span class="text-[10px] bg-white/25 px-1 py-0.2 rounded font-bold ml-1">({{ __('app.status.active') }})</span>
                        @endif
                    </button>
                </div>

                @if($isCustomActive)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-semibold px-2.5 py-1 rounded-md" style="background-color: #e8f0fe; color: #003882; border: 1px solid #bfdbfe;">
                            {{ __('app.dashboard.range_active', ['range' => $periodLabel]) }}
                        </span>
                        <a href="{{ route('dashboard', ['platform' => $platformSlug, 'period' => '7']) }}"
                           class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline ml-1"
                           title="{{ __('app.dashboard.reset_to_7_days') }}">
                            {{ __('app.dashboard.reset') }} &times;
                        </a>
                    </div>
                @endif
            </div>

            {{-- Form Rentang Tanggal Custom (tampil jika period=custom atau saat tombol Custom ditekan) --}}
            <div id="custom-date-form-wrapper"
                 class="tvri-custom-date-box"
                 style="{{ $isCustomActive ? 'display: block;' : 'display: none;' }}">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-3 text-xs">
                    <input type="hidden" name="platform" value="{{ $platformSlug }}">
                    <input type="hidden" name="period" value="custom">

                    <div class="flex items-center gap-2">
                        <label for="start_date_input" class="font-bold text-slate-700">{{ __('app.dashboard.from_date') }}:</label>
                        <input type="date" id="start_date_input" name="start_date" value="{{ $customStart ?? \Illuminate\Support\Carbon::today()->subDays(14)->toDateString() }}" required
                                class="tvri-date-input">
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="end_date_input" class="font-bold text-slate-700">{{ __('app.dashboard.to_date') }}:</label>
                        <input type="date" id="end_date_input" name="end_date" value="{{ $customEnd ?? \Illuminate\Support\Carbon::today()->toDateString() }}" required
                                class="tvri-date-input">
                    </div>

                    <button type="submit" class="tvri-btn-apply">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>{{ __('app.dashboard.apply') }}</span>
                    </button>

                    <button type="button" onclick="hideCustomDateForm()" class="px-2.5 py-1 text-slate-600 hover:text-slate-900 font-medium">
                        {{ __('app.dashboard.close') }}
                    </button>
                </form>
            </div>
        </div>

        <script>
            function toggleCustomDateForm() {
                const el = document.getElementById('custom-date-form-wrapper');
                if (el) {
                    if (el.style.display === 'none' || el.classList.contains('hidden')) {
                        el.style.display = 'block';
                        el.classList.remove('hidden');
                        document.getElementById('start_date_input')?.focus();
                    } else {
                        el.style.display = 'none';
                        el.classList.add('hidden');
                    }
                }
            }
            function hideCustomDateForm() {
                const el = document.getElementById('custom-date-form-wrapper');
                if (el) {
                    el.style.display = 'none';
                    el.classList.add('hidden');
                }
            }
        </script>

        {{-- 3. GLOBAL KPI CARDS --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">

            {{-- Card 1: Konten Aktif --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">{{ __('app.kpi.active_content') }}</span>
                        {{-- Video icon --}}
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ number_format($totalVideo) }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $isAll ? __('app.kpi.all_active_platforms') : __('app.kpi.platform_active', ['name' => $currentPlatform->nama]) }}
                    </p>
                </div>
            </div>

            {{-- Card 2: Followers / Subscribers --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                            {{ $isAll ? __('app.kpi.followers_subs') : ($currentPlatform->slug === 'youtube' ? __('app.kpi.subscribers') : __('app.kpi.followers')) }}
                        </span>
                        {{-- Users icon --}}
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        @if($isAll)
                            {{ $totalFollowers !== null ? number_format($totalFollowers) : '—' }}
                        @else
                            {{ $currentFollowers !== null ? number_format($currentFollowers) : '—' }}
                        @endif
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    @if($followersGrowth !== null)
                        <p class="text-[11px] font-semibold flex items-center gap-1 {{ $followersGrowth >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            <span>{{ ($followersGrowth >= 0 ? '▲ +' : '▼ ') . number_format($followersGrowth) }}</span>
                            <span class="text-slate-400 font-normal">({{ $periodLabel }})</span>
                        </p>
                    @else
                        <p class="text-[11px] text-slate-500 truncate">
                            @if($latestFollowersDate)
                                {{ __('app.kpi.as_of_date', ['date' => \Carbon\Carbon::parse($latestFollowersDate)->translatedFormat('d M Y')]) }}
                            @else
                                {{ $isAll ? __('app.kpi.all_connected_accounts') : __('app.kpi.connected_account') }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Card 3: Views Saat Ini --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">{{ __('app.kpi.current_views') }}</span>
                        {{-- Eye icon --}}
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ $totalViewsTerkini !== null ? number_format($totalViewsTerkini) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $totalViewsTerkini !== null ? __('app.kpi.cumulative_last_snapshot') : __('app.kpi.metric_not_applicable') }}
                    </p>
                </div>
            </div>

            {{-- Card 4: Views Bertambah (Featured Card) --}}
            <div class="p-4 rounded-xl border-2 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow"
                 style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 50%, #e8f0fe 100%); border-color: #93c5fd;">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider" style="color: #003882;">{{ __('app.kpi.views_growth') }}</span>
                        {{-- TrendingUp icon --}}
                        <span class="p-1.5 rounded-lg flex items-center justify-center" style="background-color: #dbeafe; color: #003882;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold tracking-tight mt-2 tabular-nums" style="color: #003882;">
                        {{ $totalViewsBertambah !== null ? '+' . number_format($totalViewsBertambah) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-blue-100 mt-3">
                    <p class="text-[11px] font-semibold truncate" style="color: #003882;">
                        {{ __('app.dashboard.period') }}: {{ $periodLabel }}
                    </p>
                </div>
            </div>

            {{-- Card 5: Total Likes --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">{{ __('app.kpi.total_likes') }}</span>
                        {{-- Heart icon --}}
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ $totalLikes !== null ? number_format($totalLikes) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $totalLikes !== null ? __('app.kpi.valid_likes_accumulated') : __('app.kpi.metric_unavailable') }}
                    </p>
                </div>
            </div>

            {{-- Card 6: Total Komentar --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">{{ __('app.kpi.total_comments') }}</span>
                        {{-- MessageCircle icon --}}
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ $totalComments !== null ? number_format($totalComments) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $totalComments !== null ? __('app.kpi.public_responses_accumulated') : __('app.kpi.metric_unavailable') }}
                    </p>
                </div>
            </div>

        </div>

        {{-- 4. SECTION: RINGKASAN PERFORMA PLATFORM (Hanya di mode Semua Platform) --}}
        @if($isAll && ! empty($platformSummaries))
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                            {{-- BarChart2 icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            <span>{{ __('app.dashboard.platform_summary_title') }}</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ __('app.dashboard.platform_summary_desc', ['period' => $periodLabel]) }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full self-start sm:self-auto" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;">
                        {{ __('app.dashboard.strict_null_active') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-slate-200 bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                                <th class="py-3 px-4 font-semibold">Platform</th>
                                <th class="py-3 px-4 text-right font-semibold">{{ __('app.kpi.active_content') }}</th>
                                <th class="py-3 px-4 text-right font-semibold">{{ __('app.kpi.followers_subs') }}</th>
                                <th class="py-3 px-4 text-right font-semibold">{{ __('app.kpi.current_views') }}</th>
                                <th class="py-3 px-4 text-right font-semibold">{{ __('app.kpi.views_growth') }}</th>
                                <th class="py-3 px-4 text-right font-semibold">{{ __('app.kpi.total_likes') }}</th>
                                <th class="py-3 px-4 text-right font-semibold">{{ __('app.kpi.total_comments') }}</th>
                                <th class="py-3 px-4 text-center font-semibold">{{ __('app.dashboard.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            @foreach($platformSummaries as $ps)
                                <tr class="hover:bg-slate-50/75 transition-colors">
                                    {{-- Platform Name & Logo --}}
                                    <td class="py-3.5 px-4 font-medium flex items-center gap-2.5">
                                        @if($ps['platform']->slug === 'youtube')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                                            </span>
                                        @elseif($ps['platform']->slug === 'instagram')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-pink-100 text-pink-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                                            </span>
                                        @elseif($ps['platform']->slug === 'facebook')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-100 text-blue-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                            </span>
                                        @elseif($ps['platform']->slug === 'tiktok')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold">●</span>
                                        @endif
                                        <div>
                                            <span class="text-slate-900 font-bold block">{{ $ps['platform']->nama }}</span>
                                            <span class="text-[11px] text-slate-400 font-mono">{{ $ps['platform']->slug }}</span>
                                        </div>
                                    </td>

                                    {{-- Konten Aktif --}}
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-900 tabular-nums">
                                        {{ number_format($ps['active_contents']) }}
                                    </td>

                                    {{-- Followers / Subscribers --}}
                                    <td class="py-3.5 px-4 text-right tabular-nums">
                                        <div class="font-semibold text-slate-900">
                                            {{ $ps['followers'] !== null ? number_format($ps['followers']) : '—' }}
                                        </div>
                                        @if($ps['followers_growth'] !== null)
                                            <div class="text-[11px] {{ $ps['followers_growth'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-semibold">
                                                {{ ($ps['followers_growth'] >= 0 ? '+' : '') . number_format($ps['followers_growth']) }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Total Views --}}
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-900 tabular-nums">
                                        {{ $ps['views_terkini'] !== null ? number_format($ps['views_terkini']) : '—' }}
                                    </td>

                                    {{-- Views Bertambah --}}
                                    <td class="py-3.5 px-4 text-right font-semibold tabular-nums" style="color: {{ $ps['views_bertambah'] !== null ? '#003882' : '#94a3b8' }};">
                                        {{ $ps['views_bertambah'] !== null ? '+' . number_format($ps['views_bertambah']) : '—' }}
                                    </td>

                                    {{-- Likes --}}
                                    <td class="py-3.5 px-4 text-right font-medium text-slate-700 tabular-nums">
                                        {{ $ps['likes_terkini'] !== null ? number_format($ps['likes_terkini']) : '—' }}
                                    </td>

                                    {{-- Comments --}}
                                    <td class="py-3.5 px-4 text-right font-medium text-slate-700 tabular-nums">
                                        {{ $ps['comments_terkini'] !== null ? number_format($ps['comments_terkini']) : '—' }}
                                    </td>

                                    {{-- Action Link --}}
                                    <td class="py-3.5 px-4 text-center">
                                        <a href="{{ route('dashboard', ['platform' => $ps['platform']->slug, 'period' => $period, 'start_date' => $customStart, 'end_date' => $customEnd]) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold rounded-lg transition-colors">
                                            <span>{{ __('app.dashboard.detail') }}</span>
                                            <span>&rarr;</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 5. SECTION: TREN TOTAL VIEWS HARIAN --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                <div>
                    <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        {{-- TrendingUp icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        <span>{{ __('app.dashboard.trend_title') }}</span>
                        <span class="text-xs font-normal text-slate-500">({{ $periodLabel }})</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $isAll ? __('app.dashboard.trend_desc_all') : __('app.dashboard.trend_desc_single', ['name' => $currentPlatform->nama]) }}
                    </p>
                </div>

                @if($hasTrendViews && $trendLabels->count() >= 2)
                    <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full font-medium self-start sm:self-auto">
                        ● {{ __('app.dashboard.data_snapshot_active') }}
                    </span>
                @endif
            </div>

            @if(! $hasTrendViews)
                {{-- Empty state informatif tanpa chart 0 palsu --}}
                <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-2">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-50 text-blue-600 mb-1">
                        {{-- Info icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm">
                        {{ __('app.dashboard.views_metric_not_applicable_title', ['target' => $isAll ? __('app.dashboard.all_these_content') : $currentPlatform->nama]) }}
                    </h4>
                    <p class="text-xs text-slate-500 max-w-xl mx-auto leading-relaxed">
                        {!! __('app.dashboard.views_metric_not_applicable_desc') !!}
                    </p>
                </div>
            @else
                @if($trendLabels->count() < 2)
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs flex items-center gap-2">
                        {{-- AlertTriangle icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>{{ __('app.dashboard.chart_optimal_notice', ['count' => $trendLabels->count()]) }}</span>
                    </div>
                @endif

                <div class="relative w-full" style="height: 260px;">
                    <canvas id="trendChart"></canvas>
                </div>
            @endif
        </div>

        {{-- 6. SECTION: TOP 10 KONTEN --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">
                        {{ __('app.dashboard.top_10_title') }}
                        @if($totalViewsBertambah !== null && $totalViewsBertambah > 0)
                            <span class="font-semibold" style="color: #003882;">{{ __('app.dashboard.top_10_most_views_growth', ['period' => $periodLabel]) }}</span>
                        @elseif($totalViewsTerkini !== null)
                            <span class="text-slate-700 font-semibold">{{ __('app.dashboard.top_10_highest_views') }}</span>
                        @else
                            <span class="text-slate-700 font-semibold">{{ __('app.dashboard.top_10_highest_likes') }}</span>
                        @endif
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $isAll ? __('app.dashboard.top_10_desc_all') : __('app.dashboard.top_10_desc_single', ['name' => $currentPlatform->nama]) }}
                    </p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 self-start sm:self-auto border border-slate-200">
                    {{ __('app.dashboard.top_10_count', ['count' => $top10->count()]) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b border-slate-200 bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            @if($isAll)
                                <th class="py-3 px-4 w-28">Platform</th>
                            @endif
                            <th class="py-3 px-3 w-20 text-center">{{ __('app.dashboard.media') }}</th>
                            <th class="py-3 px-4">{{ __('app.dashboard.content_title') }}</th>
                            <th class="py-3 px-4 text-right">{{ __('app.kpi.current_views') }}</th>
                            @if($totalViewsBertambah !== null && $totalViewsBertambah > 0)
                                <th class="py-3 px-4 text-right">{{ __('app.dashboard.growth') }}</th>
                            @endif
                            <th class="py-3 px-4 text-right">Likes</th>
                            <th class="py-3 px-4 text-right">{{ __('app.kpi.total_comments') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @forelse($top10 as $i => $item)
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                {{-- Ranking Badge --}}
                                <td class="py-3.5 px-4 text-center">
                                    @if($i === 0)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 font-extrabold text-xs border border-amber-300 shadow-xs">
                                            1
                                        </span>
                                    @elseif($i === 1)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold text-xs border border-slate-300">
                                            2
                                        </span>
                                    @elseif($i === 2)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-800 font-bold text-xs border border-orange-300">
                                            3
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-slate-500 tabular-nums">
                                            {{ $i + 1 }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Platform Badge (Mode All) --}}
                                @if($isAll)
                                    <td class="py-3.5 px-4">
                                        @if($item->platform_slug === 'youtube')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                                                YouTube
                                            </span>
                                        @elseif($item->platform_slug === 'instagram')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-pink-50 text-pink-700 border border-pink-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                                                Instagram
                                            </span>
                                        @elseif($item->platform_slug === 'facebook')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                                Facebook
                                            </span>
                                        @elseif($item->platform_slug === 'tiktok')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                                                TikTok
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $item->platform_nama }}
                                            </span>
                                        @endif
                                    </td>
                                @endif

                                {{-- Compact Standardized Thumbnail (64x48px) --}}
                                <td class="py-3.5 px-3">
                                    <div class="w-16 h-12 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center shadow-xs mx-auto">
                                        @if($item->thumbnail_url)
                                            <img src="{{ $item->thumbnail_url }}"
                                                 alt="Thumbnail"
                                                 class="w-full h-full object-cover"
                                                 loading="lazy"
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-[10px] text-slate-400 font-medium\'>{{ __('app.dashboard.no_media') }}</span>';">
                                        @else
                                            <span class="text-[10px] text-slate-400 font-medium">{{ __('app.dashboard.no_media') }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Judul Konten & Link --}}
                                <td class="py-3.5 px-4 max-w-md lg:max-w-xl">
                                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                                       class="font-semibold text-slate-900 hover:text-blue-700 transition-colors line-clamp-2 leading-snug group"
                                       title="{{ $item->judul }}">
                                        <span>{{ $item->judul }}</span>
                                        <svg class="w-3 h-3 inline-block ml-0.5 text-slate-400 group-hover:text-blue-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                </td>

                                {{-- Views Saat Ini --}}
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900 tabular-nums">
                                    {{ $item->views_terkini !== null ? number_format($item->views_terkini) : '—' }}
                                </td>

                                {{-- Views Bertambah (jika applicable) --}}
                                @if($totalViewsBertambah !== null && $totalViewsBertambah > 0)
                                    <td class="py-3.5 px-4 text-right font-bold tabular-nums" style="color: #003882;">
                                        {{ $item->views_bertambah !== null ? '+' . number_format($item->views_bertambah) : '—' }}
                                    </td>
                                @endif

                                {{-- Likes --}}
                                <td class="py-3.5 px-4 text-right font-medium text-slate-700 tabular-nums">
                                    {{ $item->likes_terkini !== null ? number_format($item->likes_terkini) : '—' }}
                                </td>

                                {{-- Komentar --}}
                                <td class="py-3.5 px-4 text-right font-medium text-slate-700 tabular-nums">
                                    {{ $item->comments_terkini !== null ? number_format($item->comments_terkini) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAll ? 8 : 7 }}" class="py-12 px-4 text-center">
                                    <div class="max-w-sm mx-auto text-slate-500 space-y-1">
                                        {{-- Inbox icon --}}
                                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                                        </span>
                                        <p class="font-semibold text-slate-800 text-sm">{{ __('app.dashboard.no_active_content') }}</p>
                                        <p class="text-xs text-slate-400">{{ __('app.dashboard.no_content_snapshot', ['period' => $periodLabel]) }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 7. SNAPSHOT INFORMATION FOOTER --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 py-3 border-t border-slate-200 gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full" style="background-color: #003882;"></span>
                <span>{{ __('app.dashboard.footer_note') }}</span>
            </div>
            @if($latestSnapshotDate)
                <div>
                    {{ __('app.dashboard.latest_snapshot_footer', ['date' => \Carbon\Carbon::parse($latestSnapshotDate)->translatedFormat('d F Y')]) }}
                </div>
            @endif
        </div>

    </div>

    {{-- CHART.JS SCRIPT (Hanya dimuat jika hasTrendViews) --}}
    @if($hasTrendViews)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('trendChart');
                if (ctx) {
                    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
                    gradient.addColorStop(0, 'rgba(0, 56, 130, 0.20)');
                    gradient.addColorStop(1, 'rgba(0, 56, 130, 0.00)');

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($trendLabels) !!},
                            datasets: [{
                                label: 'Total Views',
                                data: {!! json_encode($trendData) !!},
                                borderColor: '#003882',
                                backgroundColor: gradient,
                                borderWidth: 2.5,
                                tension: 0.35,
                                fill: true,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: '#003882',
                                pointBorderWidth: 2,
                                pointRadius: 3.5,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#003882',
                                pointHoverBorderColor: '#ffffff',
                                pointHoverBorderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#00265c',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    padding: 10,
                                    cornerRadius: 8,
                                    titleFont: { size: 12, weight: 'bold' },
                                    bodyFont: { size: 12 },
                                    callbacks: {
                                        label: function(context) {
                                            const val = context.parsed.y;
                                            const labelText = @json(__('app.kpi.current_views'));
                                            const localeStr = '{{ app()->getLocale() === "id" ? "id-ID" : "en-US" }}';
                                            return ' ' + labelText + ': ' + (val !== null ? val.toLocaleString(localeStr) : '—');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false,
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        font: { size: 11 },
                                        maxRotation: 0,
                                    }
                                },
                                y: {
                                    beginAtZero: false,
                                    grid: {
                                        color: 'rgba(226, 232, 240, 0.8)',
                                        drawBorder: false,
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        font: { size: 11 },
                                        callback: function(value) {
                                            if (value >= 1000000) {
                                                return (value / 1000000).toFixed(1) + 'M';
                                            } else if (value >= 1000) {
                                                return (value / 1000).toFixed(0) + 'K';
                                            }
                                            return value.toLocaleString('{{ app()->getLocale() === "id" ? "id-ID" : "en-US" }}');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endif
</x-app-layout>
