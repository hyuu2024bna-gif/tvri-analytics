<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-200 leading-tight">
            Meta Diagnostic
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Application --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    Application
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                        <span class="text-gray-400">App ID</span>
                        <span class="text-gray-100 font-mono">{{ $report['application']['app_id'] ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                        <span class="text-gray-400">App Secret</span>
                        <x-diagnostic-badge :value="$report['application']['app_secret']" />
                    </div>
                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                        <span class="text-gray-400">Graph API Version</span>
                        <span class="text-gray-100 font-mono">{{ $report['application']['graph_api'] }}</span>
                    </div>
                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                        <span class="text-gray-400">Redirect URI</span>
                        <x-diagnostic-badge :value="$report['application']['redirect_uri']" />
                    </div>
                </div>
            </div>

            {{-- OAuth Routes --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    OAuth Routes
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @foreach ($report['oauth_routes'] as $route => $status)
                        <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                            <span class="text-gray-400">{{ str_replace('_', ' ', ucfirst($route)) }}</span>
                            <x-diagnostic-badge :value="$status" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Configuration --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Configuration (.env)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @foreach ($report['configuration'] as $key => $status)
                        <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                            <span class="text-gray-400 font-mono text-xs">{{ $key }}</span>
                            <x-diagnostic-badge :value="$status" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Stored Social Accounts --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Stored Social Accounts
                </h3>

                @foreach (['instagram', 'facebook'] as $platform)
                    @php $acct = $report['accounts'][$platform]; @endphp
                    <div class="mb-4 last:mb-0">
                        <h4 class="text-md font-semibold text-gray-300 mb-2 capitalize">{{ $platform }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                <span class="text-gray-400">Connected</span>
                                <x-diagnostic-badge :value="$acct['connected']" />
                            </div>
                            @if ($acct['connected'] === 'YES')
                                @if (! empty($acct['is_facebook']))
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Page Name</span>
                                        <span class="text-gray-100 font-semibold">{{ $acct['page_name'] ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Page ID</span>
                                        <span class="text-gray-100 font-mono text-xs">{{ $acct['page_id'] ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Token Status</span>
                                        <x-diagnostic-badge :value="$acct['token_status']" />
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Expires</span>
                                        <span class="text-gray-100 text-xs">{{ $acct['expires_info'] ?? '—' }}</span>
                                    </div>
                                @else
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Username</span>
                                        <span class="text-gray-100">{{ $acct['username'] ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">External ID</span>
                                        <span class="text-gray-100 font-mono text-xs">{{ $acct['external_id'] ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Page ID</span>
                                        <span class="text-gray-100 font-mono text-xs">{{ $acct['page_id'] ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Token Status</span>
                                        <x-diagnostic-badge :value="$acct['token_status']" />
                                    </div>
                                    <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                        <span class="text-gray-400">Expires</span>
                                        <span class="text-gray-100 text-xs">{{ $acct['expires_info'] ?? '—' }}</span>
                                    </div>
                                @endif
                            @else
                                <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                    <span class="text-gray-400">Reason</span>
                                    <span class="text-gray-400 text-xs">{{ $acct['reason'] ?? '—' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Permissions --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    Permissions
                </h3>

                @if ($report['permissions']['status'] === 'OK')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-4">
                        @foreach ($report['permissions']['scopes'] as $scope => $status)
                            <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                <span class="text-gray-400 font-mono text-xs">{{ $scope }}</span>
                                <x-diagnostic-badge :value="$status" />
                            </div>
                        @endforeach
                    </div>

                    @if ($report['permissions']['token_debug'])
                        <h4 class="text-sm font-semibold text-gray-400 mb-2">Token Debug Info</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            @foreach ($report['permissions']['token_debug'] as $key => $value)
                                <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                                    <span class="text-gray-400">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                                    <x-diagnostic-badge :value="$value" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="bg-yellow-900/30 border border-yellow-700 text-yellow-300 p-3 rounded text-sm">
                        {{ $report['permissions']['status'] }}: {{ $report['permissions']['reason'] ?? 'Unknown reason' }}
                    </div>
                @endif
            </div>

            {{-- API Health --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    API Health
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @foreach ($report['api_health'] as $check => $status)
                        <div class="flex justify-between bg-gray-700/50 rounded px-3 py-2">
                            <span class="text-gray-400">{{ str_replace('_', ' ', ucfirst($check)) }}</span>
                            <x-diagnostic-badge :value="$status" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Access Levels --}}
            <div class="bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Access Levels (Page / Instagram / Business Portfolio)
                </h3>
                <div class="space-y-3 text-sm">
                    @foreach ($report['access_levels'] as $level => $info)
                        <div class="bg-gray-700/50 rounded px-4 py-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-gray-300 font-semibold capitalize">{{ str_replace('_', ' ', $level) }}</span>
                                <x-diagnostic-badge :value="$info['status']" />
                            </div>
                            <p class="text-gray-500 text-xs">{{ $info['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Timestamp --}}
            <div class="text-center text-gray-500 text-xs">
                Diagnostic generated at {{ now()->timezone('Asia/Jakarta')->format('d M Y H:i:s') }} WIB
            </div>

        </div>
    </div>
</x-app-layout>
