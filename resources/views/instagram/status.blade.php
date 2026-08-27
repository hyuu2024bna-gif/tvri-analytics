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

        <div class="bg-white p-6 rounded-lg shadow">
            @if(!$account)
                <p class="text-gray-600 mb-4">
                    Belum ada akun Instagram yang terhubung. Klik tombol di bawah untuk login
                    dengan akun Facebook yang mengelola Page resmi TVRI Aceh (yang sudah
                    terhubung ke akun Instagram Business-nya).
                </p>
                <a href="{{ route('instagram.connect') }}"
                   class="inline-block bg-blue-600 text-white px-5 py-2.5 rounded font-medium hover:bg-blue-700">
                    Hubungkan Akun Instagram
                </a>
            @else
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Akun Terhubung</p>
                        <p class="text-lg font-bold">@{{ $account->username ?? '(username tidak diketahui)' }}</p>
                    </div>
                    <a href="{{ route('instagram.connect') }}"
                       class="text-sm text-blue-600 hover:underline">
                        Hubungkan Ulang
                    </a>
                </div>

                @if($liveError)
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800 mb-4">
                        {{ $liveError }}
                    </div>
                @elseif($liveInfo)
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500">Followers</p>
                            <p class="text-xl font-bold">{{ number_format($liveInfo['followers_count'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500">Total Post</p>
                            <p class="text-xl font-bold">{{ number_format($liveInfo['media_count'] ?? 0) }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500">Token Berlaku Sampai</p>
                            <p class="text-sm font-medium">{{ $account->expires_at?->format('d-m-Y') ?? 'Tidak diketahui' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-green-600 mt-3">
                        ✓ Koneksi aktif dan berhasil diverifikasi lewat panggilan API live.
                    </p>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
