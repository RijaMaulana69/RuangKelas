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

        // Greeting berdasarkan waktu
        $hour = now()->hour;
        if ($hour < 12) {
            $greeting = 'Selamat Pagi';
            $greetingIcon = '☀️';
        } elseif ($hour < 15) {
            $greeting = 'Selamat Siang';
            $greetingIcon = '🌤️';
        } elseif ($hour < 18) {
            $greeting = 'Selamat Sore';
            $greetingIcon = '🌅';
        } else {
            $greeting = 'Selamat Malam';
            $greetingIcon = '🌙';
        }

        return [
            'kelasList' => $kelasList,
            'totalKelas' => $totalKelas,
            'totalSiswa' => $totalSiswa,
            'totalMateri' => $totalMateri,
            'greeting' => $greeting,
            'greetingIcon' => $greetingIcon,
        ];
    }
}; ?>

<div class="min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8">

        <!-- ============================================================ -->
        <!-- GREETING SECTION (Compact, Elegant)                          -->
        <!-- ============================================================ -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-5 sm:p-7 text-white shadow-xl shadow-indigo-200/50">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-48 h-48 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4 blur-sm"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4 blur-sm"></div>
            <div class="absolute top-4 right-8 w-2 h-2 bg-white/30 rounded-full animate-pulse"></div>
            <div class="absolute bottom-8 right-24 w-1.5 h-1.5 bg-white/20 rounded-full animate-pulse" style="animation-delay: 1s;"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <p class="text-indigo-200 text-sm font-medium flex items-center gap-1.5">
                        <span class="text-base">{{ $greetingIcon }}</span> {{ $greeting }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                        {{ auth()->user()->name }}
                    </h1>
                    <p class="text-indigo-200/90 text-sm max-w-md leading-relaxed">
                        Kelola kelas dan pantau perkembangan pembelajaran siswa Anda.
                    </p>
                </div>
                <a href="{{ route('guru.kelas.index') }}" wire:navigate 
                   class="group inline-flex items-center gap-2 bg-white text-indigo-700 hover:bg-indigo-50 font-bold px-5 py-2.5 rounded-2xl shadow-lg shadow-indigo-900/20 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] text-sm shrink-0 self-start sm:self-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 transition-transform duration-200 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Kelas Baru
                </a>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STAT CARDS (Modern, Animated)                                -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-3 gap-3 sm:gap-5">
            <!-- Total Kelas -->
            <div class="group bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-100 shadow-sm hover:shadow-lg hover:border-indigo-200/60 transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="space-y-1">
                        <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalKelas }}</h3>
                        <p class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Kelas</p>
                    </div>
                </div>
            </div>

            <!-- Total Siswa -->
            <div class="group bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-100 shadow-sm hover:shadow-lg hover:border-emerald-200/60 transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="space-y-1">
                        <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalSiswa }}</h3>
                        <p class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Siswa</p>
                    </div>
                </div>
            </div>

            <!-- Total Materi -->
            <div class="group bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-100 shadow-sm hover:shadow-lg hover:border-amber-200/60 transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="space-y-1">
                        <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white flex items-center justify-center shadow-md shadow-amber-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalMateri }}</h3>
                        <p class="text-[11px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider">Materi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DAFTAR KELAS (Premium Card Design)                          -->
        <!-- ============================================================ -->
        <div>
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">Kelas Anda</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Kelola materi dan pantau progres siswa</p>
                </div>
                @if(!$kelasList->isEmpty())
                    <a href="{{ route('guru.kelas.index') }}" wire:navigate 
                       class="text-xs sm:text-sm font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors">
                        Semua
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endif
            </div>

            @if($kelasList->isEmpty())
                <!-- Empty State (Premium) -->
                <div class="relative overflow-hidden bg-white border-2 border-dashed border-slate-200 rounded-3xl p-8 sm:p-12 text-center">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-violet-50/30"></div>
                    <div class="relative z-10">
                        <div class="h-20 w-20 mx-auto rounded-3xl bg-gradient-to-br from-indigo-100 to-violet-100 text-indigo-500 flex items-center justify-center mb-5 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-extrabold text-slate-800 mb-1.5">Belum ada kelas</h3>
                        <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6 leading-relaxed">
                            Buat kelas pertama Anda dan bagikan kode unik ke siswa untuk mulai pembelajaran.
                        </p>
                        <a href="{{ route('guru.kelas.index') }}" wire:navigate 
                           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-2xl text-sm shadow-lg shadow-indigo-200/50 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Buat Kelas Pertama
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                    @foreach($kelasList as $kelas)
                        <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate 
                           class="group bg-white border border-slate-100 rounded-2xl sm:rounded-3xl p-5 sm:p-6 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100/40 transition-all duration-300 hover:-translate-y-0.5 flex flex-col justify-between cursor-pointer">
                            <div>
                                <!-- Header: Badge + Kode Kelas -->
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[11px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100/60">
                                        {{ $kelas->jenjang }} · {{ $kelas->mapel }}
                                    </span>
                                    <div x-data="{ copied: false }" class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/80 px-2.5 py-1 rounded-xl shrink-0" @click.prevent.stop>
                                        <span class="text-xs font-mono font-bold text-slate-600 tracking-wide">{{ $kelas->kode_kelas }}</span>
                                        <button @click.prevent.stop="navigator.clipboard.writeText('{{ $kelas->kode_kelas }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                                title="Salin Kode Kelas" 
                                                class="text-slate-400 hover:text-indigo-600 transition-colors p-0.5">
                                            <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display: none;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Nama Kelas -->
                                <h3 class="font-extrabold text-slate-900 text-base sm:text-lg mb-1 line-clamp-1 group-hover:text-indigo-700 transition-colors">
                                    {{ $kelas->nama }}
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-500 line-clamp-2 leading-relaxed mb-4">
                                    {{ $kelas->deskripsi ?: 'Tidak ada deskripsi' }}
                                </p>
                            </div>

                            <!-- Footer Stats -->
                            <div class="pt-3.5 border-t border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-4 text-xs text-slate-500">
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <b class="text-slate-700">{{ $kelas->siswa_count }}</b> siswa
                                    </span>
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <b class="text-slate-700">{{ $kelas->chapters_count }}</b> bab
                                    </span>
                                </div>
                                <span class="flex items-center gap-1 text-xs font-bold text-indigo-600 group-hover:gap-2 transition-all duration-200">
                                    Kelola
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>


    </div>
</div>

