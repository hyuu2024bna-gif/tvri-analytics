<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-[#002b66] leading-tight">
                    Kelola Pengguna
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Manajemen akun administrator dan staf TVRI Analytics
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Flash Messages -->
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                <div class="flex items-start gap-3 mb-2">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold">Terjadi kesalahan pada data yang dimasukkan:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 pl-8">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Card: Form Tambah Pengguna Baru -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100">
                <div class="w-9 h-9 rounded-lg bg-[#e8f0fe] text-[#003882] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#002b66]">Tambah Pengguna Admin Baru</h3>
                    <p class="text-xs text-slate-500">Buat akun administrator baru untuk mengelola TVRI Analytics</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name') }}"
                               required
                               placeholder="Contoh: Administrator TVRI"
                               class="w-full text-sm rounded-lg border-slate-200 focus:border-[#003882] focus:ring-[#003882] shadow-sm transition">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                            Alamat Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               required
                               placeholder="admin@tvri.go.id"
                               class="w-full text-sm rounded-lg border-slate-200 focus:border-[#003882] focus:ring-[#003882] shadow-sm transition">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                            Kata Sandi <span class="text-rose-500">*</span> <span class="text-[11px] text-slate-400 font-normal">(Min. 8 karakter)</span>
                        </label>
                        <input type="password"
                               name="password"
                               id="password"
                               required
                               placeholder="••••••••"
                               class="w-full text-sm rounded-lg border-slate-200 focus:border-[#003882] focus:ring-[#003882] shadow-sm transition">
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">
                            Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               required
                               placeholder="••••••••"
                               class="w-full text-sm rounded-lg border-slate-200 focus:border-[#003882] focus:ring-[#003882] shadow-sm transition">
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#003882] hover:bg-[#00265c] text-white text-sm font-semibold rounded-lg shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-[#003882] focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Tambah Pengguna
                    </button>
                </div>
            </form>
        </div>

        <!-- Card: Daftar Pengguna -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-[#002b66]">Daftar Pengguna</h3>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-[#e8f0fe] text-[#003882]">
                        {{ $users->count() }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left">Pengguna</th>
                            <th scope="col" class="px-6 py-3.5 text-left">Email</th>
                            <th scope="col" class="px-6 py-3.5 text-left">Peran</th>
                            <th scope="col" class="px-6 py-3.5 text-left">Terdaftar</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Pengguna / Nama -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-full bg-[#003882] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 shadow-sm">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $user->name }}</p>
                                            @if ($user->id === auth()->id())
                                                <span class="inline-flex items-center text-[11px] font-medium text-emerald-600">
                                                    (Akun Anda saat ini)
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                    {{ $user->email }}
                                </td>

                                <!-- Role -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($user->role === 'admin')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e8f0fe] text-[#003882] border border-[#003882]/10">
                                            Administrator
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                            {{ ucfirst($user->role ?? 'Staf') }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Terdaftar -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                    {{ $user->created_at ? $user->created_at->format('d M Y, H:i') : '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if ($user->id === auth()->id())
                                        <span class="text-xs text-slate-400 italic">Tidak dapat dihapus</span>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.users.destroy', $user) }}"
                                              class="inline-block"
                                              onsubmit="return confirm('Yakin hapus user ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition duration-150 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                    Belum ada pengguna terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
