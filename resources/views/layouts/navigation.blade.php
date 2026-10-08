<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('app.nav.dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('reports.form')" :active="request()->routeIs('reports.*')">
                        {{ __('app.nav.reports') }}
                    </x-nav-link>
                    <x-nav-link :href="route('manual.index')" :active="request()->routeIs('manual.*')">
                        {{ __('app.nav.manual') }}
                    </x-nav-link>
                    @if(config('services.sync.instagram', false))
                        <x-nav-link :href="route('instagram.status')" :active="request()->routeIs('instagram.*')">
                            {{ __('app.nav.instagram') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings / User Dropdown with Integrated Language Switcher (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-slate-200 text-sm leading-4 font-medium rounded-lg text-slate-700 bg-white hover:text-slate-900 hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150 shadow-xs">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </span>
                                <span class="font-semibold">{{ Auth::user()->name }}</span>
                            </div>

                            <div class="ms-2">
                                <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Profile -->
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('app.nav.profile') }}
                        </x-dropdown-link>

                        <!-- Divider -->
                        <div class="border-t border-slate-100 my-1"></div>

                        <!-- Bahasa / Language Section Header -->
                        <div class="px-4 py-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                            <span>{{ __('app.nav.language') }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                                {{ app()->getLocale() === 'en' ? '🇬🇧 EN' : '🇮🇩 ID' }}
                            </span>
                        </div>

                        <!-- Indonesian Option -->
                        <x-dropdown-link :href="route('locale.switch', 'id')" class="flex items-center justify-between {{ app()->getLocale() === 'id' ? 'font-bold text-blue-700 bg-blue-50/70' : '' }}">
                            <span class="flex items-center gap-2">
                                <span>🇮🇩</span>
                                <span>Indonesia</span>
                            </span>
                            @if(app()->getLocale() === 'id')
                                <span class="text-blue-600 font-extrabold text-xs">✓</span>
                            @endif
                        </x-dropdown-link>

                        <!-- English Option -->
                        <x-dropdown-link :href="route('locale.switch', 'en')" class="flex items-center justify-between {{ app()->getLocale() === 'en' ? 'font-bold text-blue-700 bg-blue-50/70' : '' }}">
                            <span class="flex items-center gap-2">
                                <span>🇬🇧</span>
                                <span>English</span>
                            </span>
                            @if(app()->getLocale() === 'en')
                                <span class="text-blue-600 font-extrabold text-xs">✓</span>
                            @endif
                        </x-dropdown-link>

                        <!-- Divider -->
                        <div class="border-t border-slate-100 my-1"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                    class="text-rose-600 hover:text-rose-700 hover:bg-rose-50/50">
                                {{ __('app.nav.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('app.nav.dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.form')" :active="request()->routeIs('reports.*')">
                {{ __('app.nav.reports') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('manual.index')" :active="request()->routeIs('manual.*')">
                {{ __('app.nav.manual') }}
            </x-responsive-nav-link>
            @if(config('services.sync.instagram', false))
                <x-responsive-nav-link :href="route('instagram.status')" :active="request()->routeIs('instagram.*')">
                    {{ __('app.nav.instagram') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-2 border-t border-gray-200">
            <div class="px-4">
                <div class="font-bold text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('app.nav.profile') }}
                </x-responsive-nav-link>

                <!-- Language / Bahasa Header (Mobile) -->
                <div class="pt-2 pb-1 px-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider flex items-center justify-between">
                    <span>{{ __('app.nav.language') }}</span>
                    <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">
                        {{ app()->getLocale() === 'en' ? '🇬🇧 EN' : '🇮🇩 ID' }}
                    </span>
                </div>

                <x-responsive-nav-link :href="route('locale.switch', 'id')" class="flex items-center justify-between {{ app()->getLocale() === 'id' ? 'font-bold text-blue-700 bg-blue-50/70' : '' }}">
                    <span class="flex items-center gap-2">
                        <span>🇮🇩</span>
                        <span>Indonesia</span>
                    </span>
                    @if(app()->getLocale() === 'id')
                        <span class="text-blue-600 font-extrabold text-xs">✓</span>
                    @endif
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('locale.switch', 'en')" class="flex items-center justify-between {{ app()->getLocale() === 'en' ? 'font-bold text-blue-700 bg-blue-50/70' : '' }}">
                    <span class="flex items-center gap-2">
                        <span>🇬🇧</span>
                        <span>English</span>
                    </span>
                    @if(app()->getLocale() === 'en')
                        <span class="text-blue-600 font-extrabold text-xs">✓</span>
                    @endif
                </x-responsive-nav-link>

                <!-- Divider -->
                <div class="border-t border-gray-200 my-2"></div>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                            class="text-rose-600 hover:text-rose-700">
                        {{ __('app.nav.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
