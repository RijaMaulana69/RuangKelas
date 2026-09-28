<?php

use App\Models\Kelas;
use App\Models\Material;
use App\Models\Enrollment;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $guruId = auth()->id();

        $kelasList = Kelas::where('guru_id', $guruId)
            ->withCount(['siswa', 'chapters'])
            ->latest()
            ->get();

        $totalKelas = $kelasList->count();
        $totalSiswa = Enrollment::whereIn('class_id', $kelasList->pluck('id'))->distinct('user_id')->count('user_id');
        $totalMateri = Material::whereHas('chapter', function ($q) use ($kelasList) {
            $q->whereIn('class_id', $kelasList->pluck('id'));
        })->count();

        return [
            'kelasList' => $kelasList,
            'totalKelas' => $totalKelas,
            'totalSiswa' => $totalSiswa,
            'totalMateri' => $totalMateri,
        ];
    }
}; ?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Banner Selamat Datang -->
        <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-indigo-100">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-md mb-3">
                        👨‍🏫 Panel Guru RuangKelas
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Halo, {{ auth()->user()->name }}! 👋
                    </h1>
                    <p class="text-indigo-100 text-sm sm:text-base mt-1 max-w-xl">
                        Selamat datang kembali. Bagikan kode kelas ke siswa Anda dan mulai tambahkan materi pembelajaran interaktif.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('guru.kelas.index') }}" wire:navigate class="inline-flex items-center gap-2 bg-white text-indigo-700 hover:bg-indigo-50 font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-all transform active:scale-95 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat Kelas Baru
                    </a>
                </div>
            </div>
            <!-- Background Decorative Blob -->
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        <!-- Grid Statistik Realtime -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Total Kelas -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Kelas Diajar</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $totalKelas }}</h3>
                        <p class="text-xs text-indigo-600 mt-1 font-medium">Kelas aktif di RuangKelas</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Siswa -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Siswa Bergabung</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $totalSiswa }}</h3>
                        <p class="text-xs text-emerald-600 mt-1 font-medium">Total siswa terdaftar</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Materi -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Materi Terbit</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $totalMateri }}</h3>
                        <p class="text-xs text-amber-600 mt-1 font-medium">Video, PDF & teks bacaan</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Kelas Anda -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Kelas Yang Anda Ajar</h2>
                    <p class="text-xs text-gray-500">Pilih kelas untuk mengelola materi, kuis, atau melihat progres siswa</p>
                </div>
                <a href="{{ route('guru.kelas.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    Lihat Semua &rarr;
                </a>
            </div>

            @if($kelasList->isEmpty())
                <div class="text-center py-12 px-4 border-2 border-dashed border-gray-200 rounded-2xl">
                    <div class="h-16 w-16 mx-auto rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Belum ada kelas yang dibuat</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-4">Buat kelas pertama Anda sekarang. Kode kelas akan terbuat otomatis untuk dibagikan ke siswa.</p>
                    <a href="{{ route('guru.kelas.index') }}" wire:navigate class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-xl text-xs shadow-sm transition">
                        + Buat Kelas Pertama
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($kelasList as $kelas)
                        <div class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-5 hover:border-indigo-300 hover:bg-white hover:shadow-md transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-100 text-indigo-700">
                                        {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                                    </span>
                                    <div x-data="{ copied: false }" class="flex items-center gap-1 bg-white border border-gray-200 px-2 py-0.5 rounded-lg shadow-2xs">
                                        <span class="text-xs font-mono font-bold text-gray-700">{{ $kelas->kode_kelas }}</span>
                                        <button @click="navigator.clipboard.writeText('{{ $kelas->kode_kelas }}'); copied = true; setTimeout(() => copied = false, 2000)" title="Salin Kode Kelas" class="text-gray-400 hover:text-indigo-600 p-0.5">
                                            <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span x-show="copied" class="text-[10px] text-green-600 font-bold" style="display: none;">✓</span>
                                        </button>
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-900 text-base mb-1 line-clamp-1">
                                    {{ $kelas->nama }}
                                </h3>
                                <p class="text-xs text-gray-500 line-clamp-2 mb-4">
                                    {{ $kelas->deskripsi ?: 'Tidak ada deskripsi' }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-gray-200/60 flex items-center justify-between text-xs text-gray-500">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center gap-1">
                                        👥 <b>{{ $kelas->siswa_count }}</b> siswa
                                    </span>
                                    <span class="flex items-center gap-1">
                                        📚 <b>{{ $kelas->chapters_count }}</b> bab
                                    </span>
                                </div>
                                <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                    Kelola &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
