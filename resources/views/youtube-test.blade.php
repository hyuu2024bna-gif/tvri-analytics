<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Test Fetch YouTube API</h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow rounded-lg">
            <p class="text-sm text-gray-600 mb-4">
                Masukkan satu atau beberapa Video ID YouTube (pisahkan dengan koma).
                Contoh Video ID dari url <code>youtube.com/watch?v=dQw4w9WgXcQ</code> adalah
                <code>dQw4w9WgXcQ</code>.
            </p>

            <form method="POST" action="{{ route('youtube.fetch') }}" class="mb-6">
                @csrf
                <input type="text" name="video_ids" value="{{ $input ?? '' }}"
                       placeholder="dQw4w9WgXcQ, abc123xyz"
                       class="border rounded w-full p-2 mb-2" required>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Fetch Data
                </button>
            </form>

            @if(isset($stats))
                <h3 class="font-semibold mb-2">Hasil ({{ count($stats) }} video):</h3>
                <table class="w-full text-sm border">
                    <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="p-2 border">Judul</th>
                            <th class="p-2 border">Views</th>
                            <th class="p-2 border">Likes</th>
                            <th class="p-2 border">Comments</th>
                            <th class="p-2 border">Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats as $item)
                            <tr>
                                <td class="p-2 border">{{ $item['judul'] }}</td>
                                <td class="p-2 border">{{ number_format($item['views']) }}</td>
                                <td class="p-2 border">{{ number_format($item['likes']) }}</td>
                                <td class="p-2 border">{{ number_format($item['comments']) }}</td>
                                <td class="p-2 border">{{ $item['tanggal_upload'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
