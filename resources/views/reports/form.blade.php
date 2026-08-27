<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Laporan Performa Konten</h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <p class="text-sm text-gray-600 mb-4">
                Pilih rentang tanggal <strong>upload konten</strong>, lalu export laporan berisi
                konten yang diupload pada periode tersebut beserta performanya (views, likes,
                comments terkini) — siap untuk dikirim ke TVRI Pusat.
            </p>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                    <p class="font-medium mb-1">Rentang tanggal tidak valid:</p>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" id="reportForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date"
                           value="{{ old('start_date', now()->subDays(29)->format('Y-m-d')) }}"
                           class="border rounded w-full p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date"
                           value="{{ old('end_date', now()->format('Y-m-d')) }}"
                           class="border rounded w-full p-2" required>
                </div>

                <div class="relative inline-block pt-2" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="bg-blue-600 text-white px-4 py-2 rounded font-medium hover:bg-blue-700 inline-flex items-center gap-2">
                        Export
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false"
                         class="absolute mt-1 w-52 bg-white border rounded shadow-lg z-10" style="display: none;">
                        <button type="submit" formaction="{{ route('reports.pdf') }}"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50">
                            📄 Export PDF (Tabel)
                        </button>
                        <button type="submit" formaction="{{ route('reports.excel') }}"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50">
                            📊 Export Excel
                        </button>
                        <button type="submit" formaction="{{ route('reports.pdf-visual') }}"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-50 border-t">
                            🎨 Laporan Visual (PDF)
                        </button>
                    </div>
                </div>
            </form>

            <p class="text-xs text-gray-400 mt-4">
                Tips: untuk laporan bulanan rutin ke TVRI Pusat, pilih tanggal awal dan
                akhir bulan yang bersangkutan (default di atas sudah 30 hari terakhir).
            </p>
        </div>
    </div>
</x-app-layout>
