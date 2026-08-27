<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Dashboard Analitik Konten TVRI Aceh — {{ $platform->nama }}</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex flex-wrap gap-2 border-b pb-3">
            @foreach($availablePlatforms as $p)
                @php $isActive = $platform->id === $p->id; @endphp
                <a href="{{ route('dashboard', array_filter(['platform' => $p->slug, 'period' => $period, 'start_date' => $customStart, 'end_date' => $customEnd])) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border"
                   style="{{ $isActive ? 'background-color:#111827;color:#ffffff;border-color:#111827;' : 'background-color:#ffffff;color:#374151;border-color:#d1d5db;' }}">
                    {{ $p->nama }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            @php
                $options = [
                    'today' => 'Hari Ini',
                    '7' => '7 Hari Terakhir',
                    '15' => '15 Hari Terakhir',
                    '30' => '30 Hari Terakhir',
                    'all' => 'Semua Waktu',
                ];
            @endphp
            @foreach($options as $value => $label)
                @php $isActive = $period === $value; @endphp
                <a href="{{ route('dashboard', ['platform' => $platform->slug, 'period' => $value]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium border"
                   style="{{ $isActive ? 'background-color:#2563eb;color:#ffffff;border-color:#2563eb;' : 'background-color:#ffffff;color:#374151;border-color:#d1d5db;' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-3 bg-white p-4 rounded-lg shadow">
            <input type="hidden" name="platform" value="{{ $platform->slug }}">
            <input type="hidden" name="period" value="custom">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $customStart }}"
                       class="border rounded p-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $customEnd }}"
                       class="border rounded p-2 text-sm" required>
            </div>
            <button type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-medium"
                    style="background-color:#111827;color:#ffffff;">
                Terapkan Filter Custom
            </button>
            @if($period === 'custom')
                <span class="text-xs text-gray-500">Filter custom sedang aktif: {{ $periodLabel }}</span>
            @endif
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-sm text-gray-500">Total Video Aktif</p>
                <p class="text-2xl font-bold">{{ number_format($totalVideo) }}</p>
            </div>
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-sm text-gray-500">Views Saat Ini</p>
                <p class="text-2xl font-bold">{{ number_format($totalViewsTerkini) }}</p>
            </div>
            <div class="bg-white p-5 rounded-lg shadow border-2 border-blue-100">
                <p class="text-sm text-gray-500">Views Bertambah ({{ $periodLabel }})</p>
                <p class="text-2xl font-bold text-blue-600">+{{ number_format($totalViewsBertambah) }}</p>
                @if($totalViewsBertambah === 0)
                    <p class="text-xs text-gray-400 mt-1">Butuh min. 2 hari data untuk hitung pertambahan</p>
                @endif
            </div>
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-sm text-gray-500">Total Likes Saat Ini</p>
                <p class="text-2xl font-bold">{{ number_format($totalLikes) }}</p>
            </div>
            <div class="bg-white p-5 rounded-lg shadow">
                <p class="text-sm text-gray-500">Snapshot Terakhir</p>
                <p class="text-2xl font-bold">{{ $latestSnapshotDate ?? '-' }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
            <h3 class="font-semibold mb-4">Tren Total Views Harian — {{ $periodLabel }}</h3>
            @if($trendLabels->count() < 2)
                <p class="text-sm text-gray-500">
                    Grafik akan lebih bermakna setelah beberapa hari snapshot terkumpul
                    (baru ada {{ $trendLabels->count() }} hari data di periode ini).
                </p>
            @endif
            <canvas id="trendChart" height="80"></canvas>
        </div>

        <div class="bg-white p-5 rounded-lg shadow">
            <h3 class="font-semibold mb-4">
                Top 10 Konten
                @if($totalViewsBertambah > 0)
                    — Paling Banyak Bertambah Views ({{ $periodLabel }})
                @else
                    — Views Tertinggi
                @endif
            </h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="p-2">#</th>
                        <th class="p-2">Thumbnail</th>
                        <th class="p-2">Judul</th>
                        <th class="p-2 text-right">Views Saat Ini</th>
                        @if($totalViewsBertambah > 0)
                            <th class="p-2 text-right">Bertambah</th>
                        @endif
                        <th class="p-2 text-right">Likes</th>
                        <th class="p-2 text-right">Comments</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top10 as $i => $item)
                        <tr class="border-b">
                            <td class="p-2">{{ $i + 1 }}</td>
                            <td class="p-2">
                                @if($item->thumbnail_url)
                                    <img src="{{ $item->thumbnail_url }}" class="w-20 rounded">
                                @endif
                            </td>
                            <td class="p-2">
                                <a href="{{ $item->url }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ $item->judul }}
                                </a>
                            </td>
                            <td class="p-2 text-right">{{ number_format($item->views_terkini) }}</td>
                            @if($totalViewsBertambah > 0)
                                <td class="p-2 text-right text-blue-600">+{{ number_format($item->views_bertambah) }}</td>
                            @endif
                            <td class="p-2 text-right">{{ number_format($item->likes_terkini) }}</td>
                            <td class="p-2 text-right">{{ number_format($item->comments_terkini) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('trendChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($trendLabels) !!},
                datasets: [{
                    label: 'Total Views',
                    data: {!! json_encode($trendData) !!},
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</x-app-layout>
