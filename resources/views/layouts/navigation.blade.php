{{-- Navigation: TVRI KMB Analytics
     P1: TVRI logo (no more Laravel hexagon)
     P2: User dropdown unified with TVRI navy design system
     P3: Mobile nav as slide-in sidebar with dark overlay
--}}
<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo area (logo Laravel sudah dihapus, tidak diganti elemen baru) -->
                <div class="shrink-0 flex items-center">
                    {{-- Tidak ada logo --}}
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('app.nav.dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('reports.form')" :active="request()->routeIs('reports.*')">
                        {{ __('app.nav.reports') }}
                    </x-nav-link>
                    @if(config('services.sync.instagram', false))
                        <x-nav-link :href="route('instagram.status')" :active="request()->routeIs('instagram.*')">
                            {{ __('app.nav.instagram') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- P2: User Dropdown — TVRI design system -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="relative" x-data="{ dropOpen: false }" @click.outside="dropOpen = false">
                    <!-- Trigger button -->
                    <button @click="dropOpen = !dropOpen"
                            :aria-expanded="dropOpen"
                            class="inline-flex items-center gap-2.5 px-3 py-2 rounded-lg border text-sm font-medium
                                   bg-white text-[#002b66] border-[#93c5fd]
                                   hover:bg-[#e8f0fe] hover:border-[#003882]
                                   focus:outline-none focus:ring-2 focus:ring-[#003882] focus:ring-offset-1
                                   transition duration-150 ease-in-out shadow-sm">
                        <!-- Avatar initials -->
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#003882] text-white text-xs font-bold shadow-sm">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </span>
                        <span class="font-semibold max-w-[140px] truncate">{{ Auth::user()->name }}</span>
                        <!-- Chevron -->
                        <svg :class="dropOpen ? 'rotate-180' : ''"
                             class="w-4 h-4 text-[#003882] transition-transform duration-150"
                             fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" fill="currentColor"/>
                        </svg>
                    </button>

                    <!-- Dropdown panel -->
                    <div x-show="dropOpen"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute end-0 z-50 mt-2 w-60 rounded-xl bg-white shadow-xl ring-1 ring-[#003882]/10 origin-top-right"
                         style="display:none;"
                         @click="dropOpen = false">

                        <!-- User info header -->
                        <div class="px-4 py-3 border-b border-slate-100 bg-[#f0f6ff] rounded-t-xl">
                            <p class="text-xs font-semibold text-[#003882] truncate">{{ Auth::user()->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <!-- Profile -->
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882] transition duration-100">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ __('app.nav.profile') }}
                        </a>

                        <!-- Divider -->
                        <div class="border-t border-slate-100 mx-1"></div>

                        <!-- Language section -->
                        <div class="px-4 py-1.5 flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('app.nav.language') }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-[#e8f0fe] text-[#003882]">
                                {{ app()->getLocale() === 'en' ? '🇬🇧 EN' : '🇮🇩 ID' }}
                            </span>
                        </div>

                        <!-- ID -->
                        <a href="{{ route('locale.switch', 'id') }}"
                           class="flex items-center justify-between px-4 py-2 text-sm transition duration-100
                                  {{ app()->getLocale() === 'id' ? 'bg-[#e8f0fe] text-[#003882] font-semibold' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                            <span class="flex items-center gap-2"><span>🇮🇩</span><span>Indonesia</span></span>
                            @if(app()->getLocale() === 'id')
                                <span class="text-[#003882] font-extrabold text-xs">✓</span>
                            @endif
                        </a>

                        <!-- EN -->
                        <a href="{{ route('locale.switch', 'en') }}"
                           class="flex items-center justify-between px-4 py-2 text-sm transition duration-100
                                  {{ app()->getLocale() === 'en' ? 'bg-[#e8f0fe] text-[#003882] font-semibold' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                            <span class="flex items-center gap-2"><span>🇬🇧</span><span>English</span></span>
                            @if(app()->getLocale() === 'en')
                                <span class="text-[#003882] font-extrabold text-xs">✓</span>
                            @endif
                        </a>

                        <!-- Divider -->
                        <div class="border-t border-slate-100 mx-1 my-1"></div>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition duration-100 rounded-b-xl">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                {{ __('app.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- P3: Hamburger button (mobile only) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = true"
                        aria-label="Open navigation"
                        class="inline-flex items-center justify-center p-2 rounded-md text-[#003882] hover:bg-[#e8f0fe] focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- P3: Mobile sidebar overlay -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="sm:hidden fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
         style="display:none;"
         @click="open = false"
         aria-hidden="true">
    </div>

    <!-- P3: Sidebar panel — slides in from left -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 -translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-full"
         class="sm:hidden fixed top-0 left-0 z-50 h-full w-72 max-w-[85vw] bg-white shadow-2xl flex flex-col"
         style="display:none;"
         @click.outside="open = false">

        <!-- Sidebar header: TVRI logo + close -->
        <div class="flex items-center justify-between px-5 py-4 bg-[#003882]">
            <a href="{{ route('dashboard') }}" @click="open = false" class="flex items-center gap-2">
                <img src="{{ asset('images/tvri-logo.webp') }}"
                     alt="Logo TVRI"
                     class="h-7 w-auto object-contain brightness-0 invert"
                     onerror="this.style.display='none'">
                <span class="flex flex-col leading-tight">
                    <span class="text-[13px] font-extrabold text-white tracking-tight">KMB Analytics</span>
                    <span class="text-[10px] text-blue-200 font-medium">TVRI Stasiun Aceh</span>
                </span>
            </a>
            <button @click="open = false"
                    aria-label="Close navigation"
                    class="p-1.5 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 transition duration-150">
                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Nav links -->
        <nav class="px-3 pt-4 pb-2 space-y-1">
            <p class="px-3 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Menu</p>
            <a href="{{ route('dashboard') }}"
               @click="open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition duration-150
                      {{ request()->routeIs('dashboard') ? 'bg-[#003882] text-white shadow-sm' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                <svg class="w-4.5 h-4.5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-200' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                {{ __('app.nav.dashboard') }}
            </a>
            <a href="{{ route('reports.form') }}"
               @click="open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition duration-150
                      {{ request()->routeIs('reports.*') ? 'bg-[#003882] text-white shadow-sm' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                <svg class="w-4.5 h-4.5 flex-shrink-0 {{ request()->routeIs('reports.*') ? 'text-blue-200' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('app.nav.reports') }}
            </a>
            @if(config('services.sync.instagram', false))
                <a href="{{ route('instagram.status') }}"
                   @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition duration-150
                          {{ request()->routeIs('instagram.*') ? 'bg-[#003882] text-white shadow-sm' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                    <svg class="w-4.5 h-4.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="4" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>
                    </svg>
                    {{ __('app.nav.instagram') }}
                </a>
            @endif
        </nav>

        <!-- Divider -->
        <div class="border-t border-slate-100 mx-3 my-2"></div>

        <!-- User info -->
        <div class="px-3 pb-1">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-[#f0f6ff]">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#003882] text-white text-sm font-bold flex-shrink-0">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#002b66] truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>
        </div>

        <!-- User actions -->
        <div class="px-3 pb-2 space-y-1">
            <p class="px-3 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Akun</p>
            <a href="{{ route('profile.edit') }}"
               @click="open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882] transition duration-150">
                <svg class="w-4.5 h-4.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ __('app.nav.profile') }}
            </a>
        </div>

        <!-- Language section -->
        <div class="px-3 pb-2">
            <div class="px-3 pb-1 flex items-center justify-between">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('app.nav.language') }}</span>
                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-[#e8f0fe] text-[#003882]">
                    {{ app()->getLocale() === 'en' ? '🇬🇧 EN' : '🇮🇩 ID' }}
                </span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('locale.switch', 'id') }}"
                   class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition duration-150
                          {{ app()->getLocale() === 'id' ? 'bg-[#e8f0fe] text-[#003882] font-semibold' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                    <span class="flex items-center gap-2"><span>🇮🇩</span><span>Indonesia</span></span>
                    @if(app()->getLocale() === 'id')
                        <span class="text-[#003882] font-extrabold text-xs">✓</span>
                    @endif
                </a>
                <a href="{{ route('locale.switch', 'en') }}"
                   class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition duration-150
                          {{ app()->getLocale() === 'en' ? 'bg-[#e8f0fe] text-[#003882] font-semibold' : 'text-slate-700 hover:bg-[#e8f0fe] hover:text-[#003882]' }}">
                    <span class="flex items-center gap-2"><span>🇬🇧</span><span>English</span></span>
                    @if(app()->getLocale() === 'en')
                        <span class="text-[#003882] font-extrabold text-xs">✓</span>
                    @endif
                </a>
            </div>
        </div>

        <!-- Logout — pinned to bottom -->
        <div class="mt-auto border-t border-slate-100 px-3 py-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition duration-150">
                    <svg class="w-4.5 h-4.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('app.nav.logout') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Prevent body scroll when sidebar open (mobile) --}}
    <style>
        body:has(nav [x-show="open"][style=""]:not([style*="none"])) { overflow: hidden; }
    </style>
</nav>
