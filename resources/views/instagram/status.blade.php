<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Koneksi Instagram</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">

        @if(session('status'))
            <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if(session('diagnostic'))
            @php $diag = session('diagnostic'); @endphp
            <div class="bg-gray-900 text-gray-100 p-5 rounded-lg font-mono text-xs space-y-4 shadow">
                <div class="flex items-center justify-between border-b border-gray-700 pb-2">
                    <span class="font-bold text-yellow-400 uppercase tracking-wider text-sm">Diagnostic Meta OAuth & /me/accounts</span>
                    <span class="px-2 py-0.5 rounded bg-blue-900 text-blue-200 text-xs">Live Inspection</span>
                </div>

                {{-- OAUTH & USER & TOKEN INFO --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-gray-800 p-3 rounded border border-gray-700">
                    <div>
                        <p class="text-gray-400 font-semibold mb-1 border-b border-gray-700 pb-1">1. User & App Identity</p>
                        <div><span class="text-gray-400">FB User:</span> <strong class="text-white">{{ $diag['oauth']['user_name'] ?? '-' }}</strong> (ID: {{ $diag['oauth']['user_id'] ?? '-' }})</div>
                        <div><span class="text-gray-400">Meta App ID:</span> <span class="text-white">{{ $diag['oauth']['app_id'] ?? '-' }}</span></div>
                        <div><span class="text-gray-400">App Name:</span> <span class="text-white">{{ $diag['oauth']['application'] ?? '-' }}</span></div>
                    </div>
                    <div>
                        <p class="text-gray-400 font-semibold mb-1 border-b border-gray-700 pb-1">2. Token Metadata</p>
                        <div><span class="text-gray-400">Token Valid:</span> <span class="{{ ($diag['oauth']['is_valid'] ?? false) ? 'text-green-400 font-bold' : 'text-red-400 font-bold' }}">{{ ($diag['oauth']['is_valid'] ?? false) ? 'YES (Valid)' : 'NO / Unknown' }}</span></div>
                        <div><span class="text-gray-400">Token Type:</span> <span class="text-white">{{ $diag['oauth']['type'] ?? '-' }}</span></div>
                        <div><span class="text-gray-400">Token Expires:</span> <span class="text-white">{{ $diag['oauth']['expires_at'] ?? 'Never/Long-lived' }}</span></div>
                    </div>
                </div>

                {{-- PERMISSIONS & SCOPES --}}
                <div class="bg-gray-800 p-3 rounded border border-gray-700 space-y-2">
                    <p class="text-gray-400 font-semibold border-b border-gray-700 pb-1">3. Scopes & Permissions Analysis</p>
                    
                    <div>
                        <span class="text-gray-400">Token Scopes (/debug_token):</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse($diag['oauth']['scopes'] ?? [] as $scope)
                                <span class="px-2 py-0.5 rounded bg-blue-950 text-blue-300 border border-blue-800 text-[11px]">{{ $scope }}</span>
                            @empty
                                <span class="text-red-400">(Tidak ada scopes terdeteksi)</span>
                            @endforelse
                        </div>
                    </div>

                    @if(!empty($diag['oauth']['granular_scopes']))
                        <div class="mt-2">
                            <span class="text-gray-400">Granular Scopes (Page Target IDs):</span>
                            <pre class="mt-1 p-2 bg-black rounded text-yellow-300 text-[11px]">{{ json_encode($diag['oauth']['granular_scopes'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif

                    @if(!empty($diag['oauth']['permissions']))
                        <div class="mt-2">
                            <span class="text-gray-400">Granted vs Declined (/me/permissions):</span>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-1 mt-1">
                                @foreach($diag['oauth']['permissions'] as $perm)
                                    <div class="px-2 py-1 bg-gray-900 rounded border border-gray-700 flex justify-between">
                                        <span class="text-gray-300">{{ $perm['permission'] }}</span>
                                        <span class="{{ $perm['status'] === 'granted' ? 'text-green-400' : 'text-red-400' }} font-bold uppercase">{{ $perm['status'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- /ME/ACCOUNTS RESPONSE --}}
                <div class="bg-gray-800 p-3 rounded border border-gray-700 space-y-2">
                    <p class="text-gray-400 font-semibold border-b border-gray-700 pb-1">4. Endpoint: GET /me/accounts</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div><span class="text-gray-400">HTTP Status:</span> <strong class="text-white">{{ $diag['me_accounts']['http_status'] ?? '-' }}</strong></div>
                        <div><span class="text-gray-400">Facebook Pages Found:</span> <strong class="{{ ($diag['me_accounts']['pages_count'] ?? 0) > 0 ? 'text-green-400' : 'text-red-400' }} font-bold">{{ $diag['me_accounts']['pages_count'] ?? 0 }}</strong></div>
                    </div>

                    @if(!empty($diag['me_accounts']['pages']))
                        <div class="mt-2 space-y-2">
                            <span class="text-gray-400">Daftar Page:</span>
                            @foreach($diag['me_accounts']['pages'] as $p)
                                <div class="p-2 bg-gray-900 rounded border border-gray-700">
                                    <div><strong class="text-blue-300">Page ID:</strong> {{ $p['id'] }} | <strong class="text-blue-300">Name:</strong> {{ $p['name'] }}</div>
                                    <div><strong class="text-blue-300">Tasks:</strong> {{ implode(', ', $p['tasks'] ?? []) }}</div>
                                    <div><strong class="text-blue-300">Instagram Account:</strong> {{ isset($p['instagram_business_account']) ? json_encode($p['instagram_business_account']) : '(Tidak ada IG terhubung)' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-2">
                        <span class="text-gray-400">Sanitized Raw Meta Response Body:</span>
                        <pre class="mt-1 p-2 bg-black rounded text-green-300 overflow-x-auto leading-relaxed">{{ json_encode($diag['me_accounts']['sanitized_response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    @if(!empty($diag['me_accounts']['meta_error']))
                        <div class="mt-2 p-2 bg-red-950 border border-red-800 rounded text-red-300">
                            <strong>Meta Error:</strong> {{ json_encode($diag['me_accounts']['meta_error']) }}
                        </div>
                    @endif
                </div>

                {{-- DIRECT PAGE LOOKUP SECTION --}}
                @if(!empty($diag['direct_page_lookups']))
                    <div class="bg-gray-800 p-3 rounded border border-gray-700 space-y-2">
                        <p class="text-gray-400 font-semibold border-b border-gray-700 pb-1">5. Direct Page Lookup Diagnostic</p>
                        @foreach($diag['direct_page_lookups'] as $dLook)
                            <div class="p-3 bg-gray-900 rounded border {{ $dLook['http_status'] === 200 ? 'border-green-800' : 'border-red-800' }} space-y-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-blue-300 font-bold">GET /{{ $dLook['target_page_id'] }}</span>
                                    <span class="px-2 py-0.5 rounded {{ $dLook['http_status'] === 200 ? 'bg-green-950 text-green-300' : 'bg-red-950 text-red-300' }} font-bold">HTTP {{ $dLook['http_status'] }}</span>
                                </div>
                                <div><span class="text-gray-400">Page ID:</span> <span class="text-white">{{ $dLook['page_id'] }}</span></div>
                                <div><span class="text-gray-400">Page Name:</span> <strong class="text-white">{{ $dLook['page_name'] ?? '(none)' }}</strong></div>
                                <div><span class="text-gray-400">Page Access Token Returned:</span> <span class="{{ $dLook['has_page_access_token'] === 'YES' ? 'text-green-400 font-bold' : 'text-yellow-400' }}">{{ $dLook['has_page_access_token'] }}</span></div>
                                <div><span class="text-gray-400">Instagram Business Account ID:</span> <strong class="{{ $dLook['instagram_business_account_id'] ? 'text-green-400' : 'text-red-400' }}">{{ $dLook['instagram_business_account_id'] ?? '(none)' }}</strong></div>
                                
                                @if(!empty($dLook['meta_error']))
                                    <div class="mt-2 p-2 bg-red-950 border border-red-800 rounded text-red-300">
                                        <div><strong>Code:</strong> {{ $dLook['meta_error']['code'] ?? '-' }} | <strong>Subcode:</strong> {{ $dLook['meta_error']['error_subcode'] ?? '-' }}</div>
                                        <div><strong>Type:</strong> {{ $dLook['meta_error']['type'] ?? '-' }}</div>
                                        <div><strong>Message:</strong> {{ $dLook['meta_error']['message'] ?? '-' }}</div>
                                    </div>
                                @endif

                                <div class="mt-2">
                                    <span class="text-gray-400">Sanitized Response:</span>
                                    <pre class="mt-1 p-2 bg-black rounded text-green-300 overflow-x-auto">{{ json_encode($dLook['sanitized_response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow space-y-4">
            @if(!$account)
                <div class="space-y-4">
                    <p class="text-gray-600">
                        Belum ada akun Instagram yang terhubung. Klik tombol di bawah untuk login
                        dengan akun Facebook yang mengelola Page resmi TVRI Aceh (yang sudah
                        terhubung ke akun Instagram Business/Professional-nya).
                    </p>
                    <a href="{{ route('instagram.connect') }}"
                       class="inline-block bg-blue-600 text-white px-5 py-2.5 rounded font-medium hover:bg-blue-700 shadow transition">
                        Hubungkan Akun Instagram
                    </a>
                </div>
            @else
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Akun Instagram Terhubung</p>
                        <p class="text-xl font-bold text-gray-900">
                            @{{ $account->username ?? '(username tidak diketahui)' }}
                        </p>
                        @if(!empty($liveInfo['name']))
                            <p class="text-sm text-gray-600 font-medium">{{ $liveInfo['name'] }}</p>
                        @endif
                    </div>
                    <a href="{{ route('instagram.connect') }}"
                       class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                        Hubungkan Ulang
                    </a>
                </div>

                @if($liveError)
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800 space-y-2">
                        <div class="flex items-center gap-2 font-semibold">
                            <span>⚠ Masalah Koneksi Token / API</span>
                        </div>
                        <p>{{ $liveError }}</p>
                        <div>
                            <a href="{{ route('instagram.connect') }}" class="inline-block bg-yellow-600 text-white px-4 py-1.5 rounded text-xs font-semibold hover:bg-yellow-700">
                                Hubungkan Ulang Sekarang
                            </a>
                        </div>
                    </div>
                @elseif($liveInfo)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 font-medium">Followers</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($liveInfo['followers_count'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 font-medium">Total Post</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($liveInfo['media_count'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 font-medium">Token Berlaku Sampai</p>
                            <p class="text-base font-semibold text-gray-800 mt-1">{{ $account->expires_at?->format('d M Y') ?? 'Tidak terbatas' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-green-700 bg-green-50 p-2.5 rounded border border-green-200 font-medium">
                        <span>✓</span>
                        <span>Koneksi aktif dan berhasil diverifikasi lewat panggilan API live.</span>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
