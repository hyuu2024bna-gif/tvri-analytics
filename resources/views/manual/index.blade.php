<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Input Manual — {{ $platform->nama }}</h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

        @if(session('status'))
            <div class="p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <p class="text-sm text-gray-600">
            Platform ini belum terintegrasi API otomatis, jadi statistiknya diisi manual
            oleh staf KMB. Tambahkan konten baru, lalu update statistiknya secara berkala
            (disarankan setiap kali cek performa, misal mingguan).
        </p>

        <div class="flex justify-between items-center">
            <div class="flex gap-2">
                @foreach($manualPlatforms as $p)
                    <a href="{{ route('manual.index', ['platform' => $p->slug]) }}"
                       class="px-3 py-1.5 rounded text-sm {{ $platform->id === $p->id ? 'bg-blue-600 text-white' : 'bg-white border text-gray-700' }}">
                        {{ $p->nama }}
                    </a>
                @endforeach
            </div>
            <a href="{{ route('manual.create', ['platform' => $platform->slug]) }}"
               class="bg-blue-600 text-white px-4 py-2 rounded font-medium hover:bg-blue-700">
                + Tambah Konten {{ $platform->nama }}
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left border-b">
                        <th class="p-3">Judul</th>
                        <th class="p-3">Tanggal Upload</th>
                        <th class="p-3 text-right">Views Terakhir</th>
                        <th class="p-3">Update Terakhir</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contents as $content)
                        @php($latest = $content->statsDaily->first())
                        <tr class="border-b">
                            <td class="p-3">
                                <a href="{{ $content->url }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ $content->judul }}
                                </a>
                            </td>
                            <td class="p-3">{{ $content->tanggal_upload?->format('d-m-Y') ?? '-' }}</td>
                            <td class="p-3 text-right">{{ $latest ? number_format($latest->views) : '-' }}</td>
                            <td class="p-3">{{ $latest?->tanggal?->format('d-m-Y') ?? 'Belum ada data' }}</td>
                            <td class="p-3">
                                <a href="{{ route('manual.input.create', $content) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    + Update Statistik
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-400">
                                Belum ada konten {{ $platform->nama }} yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
