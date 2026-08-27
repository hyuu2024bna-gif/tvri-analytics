<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Update Statistik: {{ $content->judul }}</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow">
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('manual.input.store', $content) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengecekan</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', now()->format('Y-m-d')) }}"
                           class="border rounded w-full p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Views</label>
                    <input type="number" name="views" min="0" value="{{ old('views') }}"
                           class="border rounded w-full p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Likes</label>
                    <input type="number" name="likes" min="0" value="{{ old('likes', 0) }}"
                           class="border rounded w-full p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                    <input type="number" name="comments" min="0" value="{{ old('comments', 0) }}"
                           class="border rounded w-full p-2">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded font-medium hover:bg-blue-700">
                    Simpan Statistik
                </button>
            </form>

            <p class="text-xs text-gray-400 mt-3">
                Tips: kalau tanggal yang diisi sudah pernah diinput sebelumnya, datanya akan
                DIPERBARUI (bukan dobel), aman diisi ulang kalau salah ketik.
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="font-semibold mb-3">Riwayat Input Terakhir</h3>
            @if($history->isEmpty())
                <p class="text-sm text-gray-400">Belum ada data statistik untuk konten ini.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="p-2">Tanggal</th>
                            <th class="p-2 text-right">Views</th>
                            <th class="p-2 text-right">Likes</th>
                            <th class="p-2 text-right">Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $h)
                            <tr class="border-b">
                                <td class="p-2">{{ $h->tanggal->format('d-m-Y') }}</td>
                                <td class="p-2 text-right">{{ number_format($h->views) }}</td>
                                <td class="p-2 text-right">{{ number_format($h->likes) }}</td>
                                <td class="p-2 text-right">{{ number_format($h->comments) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
