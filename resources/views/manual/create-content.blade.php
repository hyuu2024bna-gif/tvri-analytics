<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Tambah Konten {{ $platform->nama }}</h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto sm:px-6 lg:px-8">
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

            <form method="POST" action="{{ route('manual.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="platform_id" value="{{ $platform->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Konten</label>
                    <input type="text" name="judul" value="{{ old('judul') }}"
                           class="border rounded w-full p-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Konten</label>
                    <input type="url" name="url" value="{{ old('url') }}"
                           placeholder="https://www.tiktok.com/@tvriaceh/video/..."
                           class="border rounded w-full p-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Upload</label>
                    <input type="date" name="tanggal_upload" value="{{ old('tanggal_upload') }}"
                           class="border rounded w-full p-2">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded font-medium hover:bg-blue-700">
                    Simpan & Lanjut Input Statistik
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
