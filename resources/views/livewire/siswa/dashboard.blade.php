<?php

use App\Models\Kelas;
use App\Models\Enrollment;
use App\Models\Material;
use App\Models\Progress;
use App\Models\QuizAttempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|string|min:4|max:10')]
    public string $kodeKelas = '';
    
    public string $search = '';
    public string $statusFilter = 'semua'; // 'semua', 'aktif', 'selesai'
    public bool $showJoinModal = false;

    public function openJoinModal(): void
    {
        $this->reset('kodeKelas');
        $this->resetValidation();
        $this->showJoinModal = true;
    }

    public function closeJoinModal(): void
    {
        $this->showJoinModal = false;
        $this->reset('kodeKelas');
        $this->resetValidation();
    }

    public function gabungKelas(): void
    {
        $this->validate([
            'kodeKelas' => 'required|string|min:4|max:10',
        ], [
            'kodeKelas.required' => 'Masukkan kode kelas dari gurumu.',
            'kodeKelas.min' => 'Kode kelas minimal 4 karakter.',
            'kodeKelas.max' => 'Kode kelas maksimal 10 karakter.',
        ]);

        $kode = strtoupper(trim($this->kodeKelas));
        $kelas = Kelas::where('kode_kelas', $kode)->where('aktif', true)->first();

        if (!$kelas) {
            $this->addError('kodeKelas', 'Kode kelas tidak ditemukan atau sudah dinonaktifkan.');
            return;
        }

        $userId = auth()->id();
        $isEnrolled = Enrollment::where('user_id', $userId)->where('class_id', $kelas->id)->exists();

        if ($isEnrolled) {
            $this->addError('kodeKelas', 'Kamu sudah bergabung di kelas ' . $kelas->nama . '.');
            return;
        }

        Enrollment::create([
            'user_id' => $userId,
            'class_id' => $kelas->id,
            'tanggal_gabung' => now(),
        ]);

        $this->reset('kodeKelas');
        $this->showJoinModal = false;
        session()->flash('success', 'Selamat! Kamu berhasil bergabung ke kelas ' . $kelas->nama . '.');
    }

    public function setStatusFilter(string $filter): void
    {
        $this->statusFilter = in_array($filter, ['semua', 'aktif', 'selesai']) ? $filter : 'semua';
    }

    public function resetSearch(): void
    {
        $this->reset('search');
    }

    public function with(): array
    {
        $userId = auth()->id();

        $allEnrolled = Kelas::whereHas('enrollments', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['guru', 'chapters.materials'])
        ->latest('id')
        ->get()
        ->map(function ($kelas) use ($userId) {
            $allMaterials = $kelas->chapters->flatMap->materials;
            $totalMateri = $allMaterials->count();
            
            $completedCount = Progress::where('user_id', $userId)
                ->whereIn('material_id', $allMaterials->pluck('id'))
                ->where('status_selesai', true)
                ->count();

            $kelas->total_materi = $totalMateri;
            $kelas->completed_materi = $completedCount;
            $kelas->progres_persen = $totalMateri > 0 ? round(($completedCount / $totalMateri) * 100) : 0;

            return $kelas;
        });

        // Metrik Statistik
        $totalKelasDiikuti = $allEnrolled->count();
        $totalMateriSelesai = Progress::where('user_id', $userId)->where('status_selesai', true)->count();
        $totalKuisSelesai = QuizAttempt::where('user_id', $userId)->where('status', 'selesai')->count();
        
        $avgProgress = $totalKelasDiikuti > 0 
            ? round($allEnrolled->avg('progres_persen')) 
            : 0;

        // Salam waktu formal dan sopan
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

        // Filter pencarian dan status
        $filteredClasses = $allEnrolled->filter(function ($kelas) {
            $matchesSearch = true;
            if (!empty($this->search)) {
                $term = strtolower(trim($this->search));
                $matchesSearch = str_contains(strtolower($kelas->nama), $term)
                    || str_contains(strtolower($kelas->mapel), $term)
                    || str_contains(strtolower($kelas->guru->name ?? ''), $term);
            }

            $matchesFilter = true;
            if ($this->statusFilter === 'aktif') {
                $matchesFilter = $kelas->progres_persen < 100;
            } elseif ($this->statusFilter === 'selesai') {
                $matchesFilter = $kelas->progres_persen >= 100 && $kelas->total_materi > 0;
            }

            return $matchesSearch && $matchesFilter;
        });

        return [
            'enrolledClasses' => $filteredClasses,
            'totalKelasDiikuti' => $totalKelasDiikuti,
            'totalMateriSelesai' => $totalMateriSelesai,
            'totalKuisSelesai' => $totalKuisSelesai,
            'avgProgress' => $avgProgress,
            'greeting' => $greeting,
        ];
    }
}; ?>

<div class="min-h-screen py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- 1. FLASH MESSAGE (TOAST NOTIFIKASI BERSIH) -->
        @if (session('success'))
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-transition:enter="transition ease-out duration-250 transform"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 x-init="setTimeout(() => show = false, 5000)" 
                 class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-8 w-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-semibold truncate">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="p-1 rounded-lg text-emerald-600 hover:text-emerald-800 hover:bg-emerald-100/50 transition shrink-0 ml-3 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- 2. BANNER SAMBUTAN (SEDERHANA, BERSIH & PROFESIONAL) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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
                    Pantau kemajuan belajar, lanjutkan materi yang sedang berjalan, dan ikuti aktivitas kelas Anda.
                </p>
            </div>
            
            <div class="shrink-0 self-start sm:self-center">
                <button type="button" 
                        wire:click="openJoinModal" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs sm:text-sm shadow-xs transition active:scale-95 cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Gabung Kelas</span>
                </button>
            </div>
        </div>

        <!-- 3. METRIK STATISTIK BELAJAR (RINGKAS & EFISIEN) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            
            <!-- Card 1: Kelas Diikuti -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5">
                    <p class="text-xs font-semibold text-slate-500">Kelas Diikuti</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalKelasDiikuti }}</h3>
                    <p class="text-[11px] text-slate-400">Kelas aktif terdaftar</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-indigo-50 border border-indigo-100/60 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>

            <!-- Card 2: Materi Selesai -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5">
                    <p class="text-xs font-semibold text-slate-500">Materi Tuntas</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalMateriSelesai }}</h3>
                    <p class="text-[11px] text-slate-400">Materi diselesaikan</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-emerald-50 border border-emerald-100/60 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Card 3: Kuis Selesai -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5">
                    <p class="text-xs font-semibold text-slate-500">Kuis Selesai</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $totalKuisSelesai }}</h3>
                    <p class="text-[11px] text-slate-400">Evaluasi dikerjakan</p>
                </div>
                <div class="h-11 w-11 rounded-xl bg-purple-50 border border-purple-100/60 text-purple-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
            </div>

            <!-- Card 4: Rata-rata Progres -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs flex items-center justify-between">
                <div class="space-y-0.5 flex-1 pr-3">
                    <p class="text-xs font-semibold text-slate-500">Rata-rata Progres</p>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $avgProgress }}%</h3>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="bg-amber-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $avgProgress }}%"></div>
                    </div>
                </div>
                <div class="h-11 w-11 rounded-xl bg-amber-50 border border-amber-100/60 text-amber-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>

        </div>

        <!-- 4. DAFTAR KELAS BELAJAR (BERSIH & TERSTRUKTUR) -->
        <div class="space-y-4">
            
            <!-- Header Section & Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Kelas Belajar</h2>
                    <p class="text-xs text-slate-500">Lanjutkan materi dan tuntaskan aktivitas pembelajaran di kelas Anda</p>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" 
                           wire:model.live.debounce.250ms="search" 
                           placeholder="Cari kelas..." 
                           class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all text-slate-800 placeholder-slate-400">
                    @if(!empty($search))
                        <button wire:click="resetSearch" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <!-- List Grid Kelas -->
            @if($enrolledClasses->isEmpty())
                <div class="text-center py-12 px-4 border border-dashed border-slate-200 rounded-2xl bg-white space-y-3">
                    <div class="h-12 w-12 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    @if(!empty($search))
                        <h3 class="text-sm font-bold text-slate-800">Tidak ada kelas yang cocok</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            Tidak ditemukan kelas dengan kata kunci "<span class="font-semibold text-slate-700">{{ $search }}</span>".
                        </p>
                        <button type="button" wire:click="resetSearch" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                            Reset Pencarian
                        </button>
                    @else
                        <h3 class="text-sm font-bold text-slate-800">Belum Bergabung di Kelas Manapun</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            Masukkan kode kelas dari guru pengajar Anda untuk mulai mengikuti kegiatan belajar.
                        </p>
                        <button type="button" 
                                wire:click="openJoinModal" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition cursor-pointer mt-1">
                            <span>Gabung Kelas Sekarang</span>
                        </button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                    @foreach($enrolledClasses as $kelas)
                        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 hover:border-indigo-200 hover:shadow-sm transition-all duration-200 flex flex-col justify-between group">
                            
                            <!-- Konten Atas -->
                            <div class="space-y-3">
                                
                                <!-- Judul Kelas, Kode Kelas & Info Guru -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="space-y-1 flex-1 min-w-0">
                                        <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate class="font-bold text-base text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1 block">
                                            {{ $kelas->nama }}
                                        </a>
                                        
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <div class="h-4 w-4 rounded-full bg-slate-100 text-slate-600 font-bold text-[9px] flex items-center justify-center shrink-0 uppercase border border-slate-200">
                                                {{ substr($kelas->guru->name ?? 'P', 0, 1) }}
                                            </div>
                                            <span class="truncate">{{ $kelas->guru->name ?? 'Pengajar' }}</span>
                                        </div>
                                    </div>

                                    <span class="font-mono text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60 shrink-0">
                                        {{ $kelas->kode_kelas }}
                                    </span>
                                </div>

                                <!-- Deskripsi Ringkas -->
                                <p class="text-xs text-slate-500 line-clamp-1 leading-relaxed">
                                    {{ $kelas->deskripsi ?: 'Modul dan aktivitas belajar terstruktur per bab.' }}
                                </p>

                                <!-- Progres Belajar Ringkas -->
                                <div class="space-y-1.5 pt-2.5 border-t border-slate-100">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 text-[11px]">Progres ({{ $kelas->completed_materi }}/{{ $kelas->total_materi }} materi)</span>
                                        <span class="font-bold {{ $kelas->progres_persen >= 100 ? 'text-emerald-600' : 'text-indigo-600' }}">
                                            {{ $kelas->progres_persen }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full transition-all duration-300 {{ $kelas->progres_persen >= 100 ? 'bg-emerald-500' : 'bg-indigo-600' }}" style="width: {{ $kelas->progres_persen }}%"></div>
                                    </div>
                                </div>

                            </div>

                            <!-- Footer: Bab & Aksi Buka Kelas -->
                            <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>{{ $kelas->chapters->count() }} Bab</span>
                                </span>

                                <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate 
                                   class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-800 transition-colors group-hover:translate-x-0.5">
                                    <span>Buka Kelas</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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

    <!-- ============================================================ -->
    <!-- MODAL GABUNG KELAS (BERSIH & MINIMALIS)                      -->
    <!-- ============================================================ -->
    @if ($showJoinModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-3 sm:p-4"
             x-data
             x-trap="true"
             @keydown.escape.window="$wire.closeJoinModal()">
            <div class="bg-white w-full max-w-md rounded-2xl shadow-xl border border-slate-200 p-6 space-y-5 transition-all my-auto animate-in fade-in zoom-in-95 duration-200">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Gabung Kelas Baru</h3>
                        <p class="text-xs text-slate-500">Masukkan kode kelas dari guru pengajar Anda</p>
                    </div>
                    <button type="button" 
                            wire:click="closeJoinModal" 
                            class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-xl font-bold leading-none transition cursor-pointer">
                        &times;
                    </button>
                </div>

                <form wire:submit="gabungKelas" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Kode Kelas
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   wire:model="kodeKelas" 
                                   placeholder="MISAL: BIN9A" 
                                   maxlength="10"
                                   autofocus
                                   class="w-full px-4 py-2.5 text-sm font-mono font-bold tracking-widest uppercase rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition" 
                                   required>
                        </div>
                        @error('kodeKelas')
                            <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                            Minta 6 karakter kode kelas kepada guru pengajar Anda, lalu ketikkan pada kolom di atas untuk bergabung.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" 
                                wire:click="closeJoinModal" 
                                class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer disabled:opacity-70">
                            <span wire:loading.remove wire:target="gabungKelas">Gabung Kelas</span>
                            <span wire:loading wire:target="gabungKelas">Memeriksa...</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

</div>
