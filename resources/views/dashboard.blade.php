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
            box-shadow: 0 2px 4px rgba(0, 56, 130, 0.25) !important;
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
                        Dashboard Analitik Konten TVRI Aceh
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Monitoring performa multi-platform: YouTube, Instagram, Facebook, dan TikTok</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold" style="{{ $isAll ? 'background-color: #e8f0fe; color: #003882; border: 1px solid #bfdbfe;' : 'background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;' }}">
                    @if($isAll)
                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: #003882;"></span>
                        Semua Platform Terpadu
                    @else
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                        Platform: {{ $currentPlatform->nama }}
                    @endif
                </span>

                @if($latestSnapshotDate)
                    <span class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium bg-white text-slate-700 border border-slate-300 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Snapshot: {{ \Carbon\Carbon::parse($latestSnapshotDate)->translatedFormat('d M Y') }}
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
                   title="Analitik gabungan seluruh platform">
                    <span class="text-base">🌐</span>
                    <span>Semua Platform</span>
                    @if($isAll)
                        <span class="tvri-badge-status">Aktif</span>
                    @endif
                </a>

                {{-- Individual Platforms (YouTube, Instagram, TikTok, Facebook) --}}
                @foreach($allPlatformsList as $p)
                    @php
                        $isActive = (! $isAll && $currentPlatform && $currentPlatform->id === $p->id);
                        $targetUrl = route('dashboard', array_filter(['platform' => $p->slug, 'period' => $period, 'start_date' => $customStart, 'end_date' => $customEnd]));
                    @endphp

                    <a href="{{ $targetUrl }}"
                       class="tvri-btn-platform {{ $isActive ? 'is-active' : '' }}"
                       title="Filter platform {{ $p->nama }}">
                        @if($p->slug === 'youtube')
                            <span style="color: {{ $isActive ? '#ffffff' : '#dc2626' }}; font-weight: bold; font-size: 0.85rem;">▶</span>
                        @elseif($p->slug === 'instagram')
                            <span style="color: {{ $isActive ? '#ffffff' : '#e1306c' }}; font-weight: bold; font-size: 0.85rem;">📷</span>
                        @elseif($p->slug === 'facebook')
                            <span style="color: {{ $isActive ? '#ffffff' : '#1877f2' }}; font-weight: bold; font-size: 0.85rem;">📘</span>
                        @elseif($p->slug === 'tiktok')
                            <span style="color: {{ $isActive ? '#ffffff' : '#0f172a' }}; font-weight: bold; font-size: 0.85rem;">🎵</span>
                        @else
                            <span>●</span>
                        @endif

                        <span>{{ $p->nama }}</span>

                        @if($p->slug === 'tiktok')
                            <span class="tvri-badge-status" style="{{ $isActive ? 'background-color: rgba(255,255,255,0.25); color: #ffffff;' : 'background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;' }}">
                                Segera
                            </span>
                        @elseif($isActive)
                            <span class="tvri-badge-status">Aktif</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 2. PERIOD FILTER BAR & CUSTOM DATE TOGGLE --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                {{-- Quick Filter Pills --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider mr-1">Periode:</span>
                    @php
                        $periods = [
                            'today' => 'Hari Ini',
                            '7'     => '7 Hari',
                            '15'    => '15 Hari',
                            '30'    => '30 Hari',
                            'all'   => 'Semua Waktu',
                        ];
                    @endphp
                    @foreach($periods as $val => $label)
                        @php $isPeriodActive = ($period === $val); @endphp
                        <a href="{{ route('dashboard', ['platform' => $platformSlug, 'period' => $val]) }}"
                           class="tvri-btn-period {{ $isPeriodActive ? 'is-active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach

                    {{-- Tombol "Custom" --}}
                    @php $isCustomActive = ($period === 'custom'); @endphp
                    <button type="button"
                            onclick="toggleCustomDateForm()"
                            id="btn-custom-toggle"
                            class="tvri-btn-period {{ $isCustomActive ? 'is-active' : '' }}"
                            title="Pilih rentang tanggal khusus">
                        <span>📅</span>
                        <span>Custom</span>
                        @if($isCustomActive)
                            <span style="font-size: 10px; margin-left: 2px;">(Aktif)</span>
                        @endif
                    </button>
                </div>

                @if($isCustomActive)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-semibold px-2.5 py-1 rounded-md" style="background-color: #e8f0fe; color: #003882; border: 1px solid #bfdbfe;">
                            Rentang: {{ $periodLabel }}
                        </span>
                        <a href="{{ route('dashboard', ['platform' => $platformSlug, 'period' => '7']) }}"
                           class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline ml-1"
                           title="Reset filter ke 7 hari">
                            Reset &times;
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
                        <label for="start_date_input" class="font-bold text-slate-700">Dari Tanggal:</label>
                        <input type="date" id="start_date_input" name="start_date" value="{{ $customStart ?? \Illuminate\Support\Carbon::today()->subDays(14)->toDateString() }}" required
                               class="tvri-date-input">
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="end_date_input" class="font-bold text-slate-700">Sampai Tanggal:</label>
                        <input type="date" id="end_date_input" name="end_date" value="{{ $customEnd ?? \Illuminate\Support\Carbon::today()->toDateString() }}" required
                               class="tvri-date-input">
                    </div>

                    <button type="submit" class="tvri-btn-apply">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Terapkan Rentang</span>
                    </button>

                    <button type="button" onclick="hideCustomDateForm()" class="px-2.5 py-1 text-slate-600 hover:text-slate-900 font-medium">
                        Tutup
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
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Konten Aktif</span>
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs">📹</span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ number_format($totalVideo) }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $isAll ? 'Semua platform aktif' : 'Platform ' . $currentPlatform->nama }}
                    </p>
                </div>
            </div>

            {{-- Card 2: Followers / Subscribers --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                            {{ $isAll ? 'Followers / Subs' : ($currentPlatform->slug === 'youtube' ? 'Subscribers' : 'Followers') }}
                        </span>
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs">👥</span>
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
                                Per {{ \Carbon\Carbon::parse($latestFollowersDate)->translatedFormat('d M Y') }}
                            @else
                                {{ $isAll ? 'Total akun terhubung' : 'Akun terhubung' }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Card 3: Views Saat Ini --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Views Saat Ini</span>
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs">👁️</span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ $totalViewsTerkini !== null ? number_format($totalViewsTerkini) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $totalViewsTerkini !== null ? 'Kumulatif snapshot terakhir' : 'Metrik tidak berlaku' }}
                    </p>
                </div>
            </div>

            {{-- Card 4: Views Bertambah (Featured Card) --}}
            <div class="p-4 rounded-xl border-2 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow"
                 style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 50%, #e8f0fe 100%); border-color: #93c5fd;">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider" style="color: #003882;">Views Bertambah</span>
                        <span class="p-1.5 rounded-lg text-xs font-bold" style="background-color: #dbeafe; color: #003882;">📈</span>
                    </div>
                    <p class="text-2xl font-extrabold tracking-tight mt-2 tabular-nums" style="color: #003882;">
                        {{ $totalViewsBertambah !== null ? '+' . number_format($totalViewsBertambah) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-blue-100 mt-3">
                    <p class="text-[11px] font-semibold truncate" style="color: #003882;">
                        Periode: {{ $periodLabel }}
                    </p>
                </div>
            </div>

            {{-- Card 5: Total Likes --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Total Likes</span>
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs">❤️</span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ $totalLikes !== null ? number_format($totalLikes) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $totalLikes !== null ? 'Akumulasi suka valid' : 'Metrik tidak tersedia' }}
                    </p>
                </div>
            </div>

            {{-- Card 6: Total Komentar --}}
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:shadow-sm transition-shadow">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Total Komentar</span>
                        <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs">💬</span>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 tracking-tight mt-2 tabular-nums">
                        {{ $totalComments !== null ? number_format($totalComments) : '—' }}
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 mt-3">
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ $totalComments !== null ? 'Akumulasi respon publik' : 'Metrik tidak tersedia' }}
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
                            <span>📊 Ringkasan Performa Platform</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Perbandingan performa lintas YouTube, Instagram, dan Facebook pada periode {{ $periodLabel }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full self-start sm:self-auto" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;">
                        Strict NULL Semantics Aktif
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-slate-200 bg-slate-50 text-slate-600 text-xs uppercase tracking-wider">
                                <th class="py-3 px-4 font-semibold">Platform</th>
                                <th class="py-3 px-4 text-right font-semibold">Konten Aktif</th>
                                <th class="py-3 px-4 text-right font-semibold">Followers / Subs</th>
                                <th class="py-3 px-4 text-right font-semibold">Total Views</th>
                                <th class="py-3 px-4 text-right font-semibold">Views Bertambah</th>
                                <th class="py-3 px-4 text-right font-semibold">Total Likes</th>
                                <th class="py-3 px-4 text-right font-semibold">Total Komentar</th>
                                <th class="py-3 px-4 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            @foreach($platformSummaries as $ps)
                                <tr class="hover:bg-slate-50/75 transition-colors">
                                    {{-- Platform Name & Logo --}}
                                    <td class="py-3.5 px-4 font-medium flex items-center gap-2.5">
                                        @if($ps['platform']->slug === 'youtube')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 text-red-600 text-xs font-bold">▶</span>
                                        @elseif($ps['platform']->slug === 'instagram')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-pink-100 text-pink-600 text-xs font-bold">📷</span>
                                        @elseif($ps['platform']->slug === 'facebook')
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-blue-100 text-blue-600 text-xs font-bold">📘</span>
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
                                            <span>Detail</span>
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
                        <span>📈 Tren Performa Views Harian</span>
                        <span class="text-xs font-normal text-slate-500">({{ $periodLabel }})</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $isAll ? 'Menampilkan metrik penayangan video harian dari YouTube' : 'Menampilkan metrik penayangan video pada platform ' . $currentPlatform->nama }}
                    </p>
                </div>

                @if($hasTrendViews && $trendLabels->count() >= 2)
                    <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full font-medium self-start sm:self-auto">
                        ● Data Snapshot Aktif
                    </span>
                @endif
            </div>

            @if(! $hasTrendViews)
                {{-- Empty state informatif tanpa chart 0 palsu --}}
                <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-2">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-50 text-blue-600 text-lg mb-1">
                        ℹ️
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm">
                        Metrik Penayangan (Views) Tidak Berlaku untuk {{ $isAll ? 'Semua Konten Ini' : $currentPlatform->nama }}
                    </h4>
                    <p class="text-xs text-slate-500 max-w-xl mx-auto leading-relaxed">
                        Metrik streaming video views saat ini bersumber dari YouTube. Untuk platform seperti Instagram, TikTok, dan Facebook, analitik berfokus pada metrik interaksi (Followers, Likes, dan Komentar). Sesuai prinsip <span class="font-semibold text-slate-700">Strict NULL Semantics</span>, angka penayangan tidak dipaksakan menjadi angka 0 palsu.
                    </p>
                </div>
            @else
                @if($trendLabels->count() < 2)
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs flex items-center gap-2">
                        <span>⚠️</span>
                        <span>Grafik akan lebih optimal setelah minimal 2 hari snapshot terkumpul (tersedia {{ $trendLabels->count() }} titik data snapshot pada periode ini).</span>
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
                        Top 10 Konten
                        @if($totalViewsBertambah !== null && $totalViewsBertambah > 0)
                            <span class="font-semibold" style="color: #003882;">— Paling Banyak Bertambah Views ({{ $periodLabel }})</span>
                        @elseif($totalViewsTerkini !== null)
                            <span class="text-slate-700 font-semibold">— Views Tertinggi</span>
                        @else
                            <span class="text-slate-700 font-semibold">— Likes Tertinggi</span>
                        @endif
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $isAll ? 'Peringkat konten terpopuler lintas seluruh platform TVRI Aceh' : 'Peringkat konten terpopuler platform ' . $currentPlatform->nama }}
                    </p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 self-start sm:self-auto border border-slate-200">
                    {{ $top10->count() }} Konten Teratas
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
                            <th class="py-3 px-3 w-20 text-center">Media</th>
                            <th class="py-3 px-4">Judul Konten</th>
                            <th class="py-3 px-4 text-right">Views Saat Ini</th>
                            @if($totalViewsBertambah !== null && $totalViewsBertambah > 0)
                                <th class="py-3 px-4 text-right">Bertambah</th>
                            @endif
                            <th class="py-3 px-4 text-right">Likes</th>
                            <th class="py-3 px-4 text-right">Komentar</th>
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
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                                <span>▶</span> YouTube
                                            </span>
                                        @elseif($item->platform_slug === 'instagram')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-pink-50 text-pink-700 border border-pink-200">
                                                <span>📷</span> Instagram
                                            </span>
                                        @elseif($item->platform_slug === 'facebook')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span>📘</span> Facebook
                                            </span>
                                        @elseif($item->platform_slug === 'tiktok')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                                <span>🎵</span> TikTok
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
                                                 onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'text-[10px] text-slate-400 font-medium\'>No Media</span>';">
                                        @else
                                            <span class="text-[10px] text-slate-400 font-medium">No Media</span>
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
                                        <span class="text-2xl block mb-1">📭</span>
                                        <p class="font-semibold text-slate-800 text-sm">Tidak Ada Konten Aktif</p>
                                        <p class="text-xs text-slate-400">Tidak ada snapshot data konten yang ditemukan pada periode {{ $periodLabel }}.</p>
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
                <span>Sistem Analitik TVRI Aceh • Data historis bersifat immutable</span>
            </div>
            @if($latestSnapshotDate)
                <div>
                    Snapshot konten terakhir: <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($latestSnapshotDate)->translatedFormat('d F Y') }}</span>
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
                                            return ' Total Views: ' + (val !== null ? val.toLocaleString('id-ID') : '—');
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
                                            return value.toLocaleString('id-ID');
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
