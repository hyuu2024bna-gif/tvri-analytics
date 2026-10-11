<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-[#002b66] leading-tight">
            Laporan Performa Konten
        </h2>
    </x-slot>

    <div class="py-6 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-600 mb-5">
                Pilih rentang tanggal <strong>upload konten</strong>, lalu export laporan berisi
                konten yang diupload pada periode tersebut beserta performanya (views, likes,
                comments terkini) — siap untuk dikirim ke TVRI Pusat.
            </p>

            @if($errors->any())
                <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    <p class="font-semibold mb-1">Rentang tanggal tidak valid:</p>
                    <ul class="list-disc list-inside space-y-0.5">
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
                           class="border border-gray-300 focus:border-[#003882] focus:ring-[#003882] focus:ring-1 rounded-lg w-full px-3 py-2 text-sm shadow-sm transition duration-150" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date"
                           value="{{ old('end_date', now()->format('Y-m-d')) }}"
                           class="border border-gray-300 focus:border-[#003882] focus:ring-[#003882] focus:ring-1 rounded-lg w-full px-3 py-2 text-sm shadow-sm transition duration-150" required>
                </div>

                <div class="relative inline-block pt-2" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#003882] text-white text-sm font-semibold rounded-lg hover:bg-[#00265c] focus:outline-none focus:ring-2 focus:ring-[#003882] focus:ring-offset-2 shadow-sm transition duration-150 ease-in-out">
                        Export
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute mt-1.5 w-56 bg-white border border-gray-100 rounded-xl shadow-xl z-10 overflow-hidden origin-top-left"
                         style="display: none;">
                        <button type="submit" formaction="{{ route('reports.pdf') }}"
                                class="flex items-center gap-2.5 w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-[#e8f0fe] hover:text-[#003882] transition-colors duration-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 shrink-0"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/></svg>
                            <span>Export PDF (Tabel)</span>
                        </button>
                        <button type="submit" formaction="{{ route('reports.excel') }}"
                                class="flex items-center gap-2.5 w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-[#e8f0fe] hover:text-[#003882] transition-colors duration-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600 shrink-0"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="3" x2="21" y1="15" y2="15"/><line x1="9" x2="9" y1="3" y2="21"/><line x1="15" x2="15" y1="3" y2="21"/></svg>
                            <span>Export Excel</span>
                        </button>
                        <button type="submit" formaction="{{ route('reports.pdf-visual') }}"
                                class="flex items-center gap-2.5 w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-[#e8f0fe] hover:text-[#003882] border-t border-gray-100 transition-colors duration-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#003882] shrink-0"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                            <span>Laporan Visual (PDF)</span>
                        </button>
                    </div>
                </div>
            </form>

            <p class="text-xs text-gray-400 mt-5">
                Tips: untuk laporan bulanan rutin ke TVRI Pusat, pilih tanggal awal dan
                akhir bulan yang bersangkutan (default di atas sudah 30 hari terakhir).
            </p>
        </div>
    </div>
</x-app-layout>
