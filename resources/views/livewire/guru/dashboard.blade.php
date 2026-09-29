<?php

use App\Models\Kelas;
use App\Models\Material;
use App\Models\Enrollment;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function resetSearch(): void
    {
        $this->search = '';
    }

    public function with(): array
    {
        $guruId = auth()->id();

        $query = Kelas::where('guru_id', $guruId)
            ->withCount(['siswa', 'chapters'])
            ->latest();

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', $term)
                  ->orWhere('mapel', 'like', $term)
                  ->orWhere('kode_kelas', 'like', $term);
            });
        }

        $kelasList = $query->get();

        $allGuruClasses = Kelas::where('guru_id', $guruId)->get(['id']);
        $totalKelas = $allGuruClasses->count();
        $totalSiswa = Enrollment::whereIn('class_id', $allGuruClasses->pluck('id'))->distinct('user_id')->count('user_id');
        $totalMateri = Material::whereHas('chapter', function ($q) use ($allGuruClasses) {
            $q->whereIn('class_id', $allGuruClasses->pluck('id'));
        })->count();

        // Greeting waktu yang sopan dan profesional
        $hour = now()->hour;
        if ($hour < 12) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour < 15) {
            $greeting = 'Selamat Siang';
        } elseif ($hour < 18) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }

        return [
            'kelasList' => $kelasList,
            'totalKelas' => $totalKelas,
            'totalSiswa' => $totalSiswa,
            'totalMateri' => $totalMateri,
            'greeting' => $greeting,
        ];
    }
}; ?>

<div class="min-h-screen py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- ============================================================ -->
        <!-- BANNER SAMBUTAN (Bersih, Sederhana & Profesional)             -->
        <!-- ============================================================ -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-2xs">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ $greeting }}
                    </span>
                    <span class="text-xs text-slate-400 font-medium">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ auth()->user()->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 max-w-xl">
                    Ringkasan aktivitas pembelajaran dan pemantauan kelas yang Anda bimbing.
                </p>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- METRIC STATISTIK (Ringkas & Efisien)                         -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- Total Kelas -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5">
                    <p class="text-xs font-semibold text-slate-500">Total Kelas</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalKelas }}</h3>
                    <p class="text-[11px] text-slate-400">Kelas yang aktif diajar</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-indigo-50 border border-indigo-100/60 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>

            <!-- Total Siswa -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5">
                    <p class="text-xs font-semibold text-slate-500">Total Siswa</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalSiswa }}</h3>
                    <p class="text-[11px] text-slate-400">Siswa terdaftar aktif</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-emerald-50 border border-emerald-100/60 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>

            <!-- Total Materi -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5">
                    <p class="text-xs font-semibold text-slate-500">Total Materi</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalMateri }}</h3>
                    <p class="text-[11px] text-slate-400">Bab bahan ajar & tugas</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-amber-50 border border-amber-100/60 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DAFTAR KELAS (Desain Rapi & Elegan)                         -->
        <!-- ============================================================ -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Daftar Kelas</h2>
                    <p class="text-xs text-slate-500">Pilih kelas untuk mengelola materi, tugas, dan penilaian siswa</p>
                </div>

                <!-- Input Pencarian Kelas (Sesuai Gambar) -->
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" 
                           wire:model.live.debounce.250ms="search" 
                           placeholder="Cari kelas..." 
                           class="w-full pl-9 pr-8 py-2 text-xs rounded-full border border-slate-200 bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all text-slate-800 placeholder-slate-400 shadow-2xs">
                    @if(!empty($search))
                        <button wire:click="resetSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            @if($kelasList->isEmpty())
                <!-- State Kosong Bersih -->
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 p-8 sm:p-12 text-center space-y-4 shadow-2xs">
                    <div class="h-14 w-14 mx-auto rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    @if(!empty($search))
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-800">Tidak ada kelas yang cocok</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                                Tidak ditemukan kelas dengan kata kunci "<span class="font-semibold text-slate-700">{{ $search }}</span>".
                            </p>
                        </div>
                        <button type="button" wire:click="resetSearch" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                            Reset Pencarian
                        </button>
                    @else
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-800">Belum Ada Kelas yang Dibuat</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Mulai dengan membuat ruang kelas pertama Anda untuk membagikan materi dan tugas kepada siswa.</p>
                        </div>
                        <a href="{{ route('guru.kelas.index') }}" wire:navigate 
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Buat Kelas Pertama</span>
                        </a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    @foreach($kelasList as $kelas)
                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                            
                            <!-- Konten Kelas -->
                            <div class="p-5 space-y-3">
                                <div class="space-y-1">
                                    <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate class="font-extrabold text-base text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1 block">
                                        {{ $kelas->nama }}
                                    </a>
                                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed min-h-[34px]">
                                        {{ $kelas->deskripsi ?: 'Belum ada deskripsi untuk kelas ini.' }}
                                    </p>
                                </div>

                                <!-- Statistik Siswa & Bab -->
                                <div class="flex items-center gap-4 pt-3 border-t border-slate-100 text-xs text-slate-500 font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                        </svg>
                                        <span><b class="text-slate-800 font-bold">{{ $kelas->siswa_count }}</b> Siswa</span>
                                    </div>
                                    <span class="text-slate-300">&bull;</span>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                        <span><b class="text-slate-800 font-bold">{{ $kelas->chapters_count }}</b> Bab</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Aksi -->
                            <div class="px-5 py-3 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end">
                                <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate 
                                   class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 group-hover:gap-1.5 transition-all">
                                    <span>Kelola Kelas</span>
                                    <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
