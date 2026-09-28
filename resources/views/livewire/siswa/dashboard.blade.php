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
        ];
    }
}; ?>

<div class="py-6 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
        
        <!-- 1. FLASH MESSAGE (MODERN TOAST ALERT) -->
        @if (session('success'))
            <div x-data="{ show: true }" 
                 x-show="show" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 x-init="setTimeout(() => show = false, 6000)" 
                 class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-8 w-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-semibold truncate">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="p-1 rounded-lg text-emerald-600 hover:text-emerald-800 hover:bg-emerald-100/50 transition shrink-0 ml-3">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- 2. HERO BANNER: MODERN GREETING & INTERACTIVE JOIN CLASS -->
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-950 rounded-3xl p-6 sm:p-8 lg:p-10 text-white shadow-xl shadow-indigo-950/20 border border-indigo-800/50">
            
            <!-- Ambient Decorative Glow Spheres -->
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-indigo-500/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
                
                <!-- Left Column: Sapaan & Motivasi Belajar -->
                <div class="lg:col-span-7 space-y-3 sm:space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-indigo-200 border border-white/10 backdrop-blur-md">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Ruang Belajar Siswa Aktif</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white leading-tight">
                        Semangat Belajar, <br class="hidden sm:inline">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-200 via-purple-200 to-pink-200">
                            {{ auth()->user()->name }}!
                        </span>
                    </h1>

                    <p class="text-indigo-100/90 text-xs sm:text-sm lg:text-base leading-relaxed max-w-xl font-normal">
                        Lanjutkan bab materi yang belum tuntas, tonton video pembelajaran, dan uji pemahamanmu dengan kuis latihan interaktif.
                    </p>

                    <!-- Mini Highlights -->
                    <div class="pt-1 flex flex-wrap items-center gap-3 sm:gap-4 text-xs text-indigo-200 font-medium">
                        <div class="inline-flex items-center gap-1.5 bg-white/5 border border-white/10 px-3 py-1 rounded-xl">
                            <span class="font-bold text-white">{{ $totalKelasDiikuti }}</span> Kelas Terdaftar
                        </div>
                        <div class="inline-flex items-center gap-1.5 bg-white/5 border border-white/10 px-3 py-1 rounded-xl">
                            <span class="font-bold text-emerald-400">{{ $avgProgress }}%</span> Rata-rata Progres
                        </div>
                    </div>
                </div>

                <!-- Right Column: Glassmorphism Form Gabung Kelas -->
                <div class="lg:col-span-5 bg-white/10 backdrop-blur-xl p-5 sm:p-6 rounded-2xl sm:rounded-3xl border border-white/15 shadow-xl">
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <div class="h-8 w-8 rounded-xl bg-white/15 flex items-center justify-center text-indigo-200 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm sm:text-base text-white leading-tight">
                                Gabung Kelas Baru
                            </h3>
                            <p class="text-[11px] text-indigo-200/80">Masukkan 6-digit kode kelas dari gurumu</p>
                        </div>
                    </div>

                    <form wire:submit="gabungKelas" class="space-y-2.5 pt-2">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="relative flex-1">
                                <input type="text" 
                                       wire:model="kodeKelas" 
                                       placeholder="Contoh: MTK10A" 
                                       maxlength="10"
                                       class="w-full text-xs sm:text-sm font-mono uppercase tracking-widest font-bold rounded-xl bg-white text-slate-900 placeholder-slate-400 border-0 focus:ring-2 focus:ring-indigo-400 py-2.5 sm:py-3 px-3.5 shadow-inner" 
                                       required>
                            </div>
                            <button type="submit" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold text-xs sm:text-sm px-5 py-2.5 sm:py-3 rounded-xl shadow-md transition-all shrink-0 active:scale-95 disabled:opacity-75 cursor-pointer">
                                <span wire:loading.remove wire:target="gabungKelas">Gabung</span>
                                <span wire:loading wire:target="gabungKelas">Memeriksa...</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>

                        @error('kodeKelas')
                            <p class="text-xs text-rose-300 font-medium flex items-center gap-1.5 pt-0.5">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </form>
                </div>

            </div>
        </div>

        <!-- 3. METRIK STATISTIK BELAJAR (RESPONSIF & CLEAN) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5">
            
            <!-- Card 1: Kelas Diikuti -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs hover:border-indigo-200 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Kelas Diikuti</span>
                    <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 sm:mt-3">{{ $totalKelasDiikuti }}</h3>
                <p class="text-[11px] sm:text-xs text-indigo-600 mt-1 font-semibold flex items-center gap-1">
                    <span>Kelas aktif terdaftar</span>
                </p>
            </div>

            <!-- Card 2: Materi Selesai -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs hover:border-emerald-200 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Materi Tuntas</span>
                    <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 sm:mt-3">{{ $totalMateriSelesai }}</h3>
                <p class="text-[11px] sm:text-xs text-emerald-600 mt-1 font-semibold flex items-center gap-1">
                    <span>Materi telah dibaca</span>
                </p>
            </div>

            <!-- Card 3: Latihan Dikerjakan -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs hover:border-purple-200 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Kuis Selesai</span>
                    <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 sm:mt-3">{{ $totalKuisSelesai }}</h3>
                <p class="text-[11px] sm:text-xs text-purple-600 mt-1 font-semibold flex items-center gap-1">
                    <span>Evaluasi diselesaikan</span>
                </p>
            </div>

            <!-- Card 4: Rata-rata Progres -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs hover:border-teal-200 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Progres</span>
                    <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-2 sm:mt-3">{{ $avgProgress }}%</h3>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $avgProgress }}%"></div>
                </div>
            </div>

        </div>

        <!-- 4. DAFTAR KELAS BELAJAR (INTERAKTIF & FILTERABLE) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs p-5 sm:p-7 space-y-6">
            
            <!-- Header Section & Search Navigation Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-100">
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                        Kelas Belajar Kamu
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Lanjutkan materi pelajaran dan tuntaskan latihan kuis
                    </p>
                </div>

                <!-- Interactive Search Input -->
                <div class="flex items-center gap-2 sm:gap-3 w-full md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" 
                               wire:model.live.debounce.250ms="search" 
                               placeholder="Cari kelas atau mapel..." 
                               class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all text-slate-800 placeholder-slate-400">
                        @if(!empty($search))
                            <button wire:click="resetSearch" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Filter Status Tabs (Interaktif, Cepat, Responsif) -->
            <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 text-xs">
                <button type="button" 
                        wire:click="setStatusFilter('semua')" 
                        class="px-3.5 py-1.5 rounded-xl font-bold transition-all cursor-pointer shrink-0 {{ $statusFilter === 'semua' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200/70 text-slate-600' }}">
                    Semua Kelas
                </button>
                <button type="button" 
                        wire:click="setStatusFilter('aktif')" 
                        class="px-3.5 py-1.5 rounded-xl font-bold transition-all cursor-pointer shrink-0 {{ $statusFilter === 'aktif' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200/70 text-slate-600' }}">
                    Sedang Berjalan
                </button>
                <button type="button" 
                        wire:click="setStatusFilter('selesai')" 
                        class="px-3.5 py-1.5 rounded-xl font-bold transition-all cursor-pointer shrink-0 {{ $statusFilter === 'selesai' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200/70 text-slate-600' }}">
                    Tuntas (100%)
                </button>
            </div>

            <!-- List Grid Kelas -->
            @if($enrolledClasses->isEmpty())
                <div class="text-center py-12 px-4 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50 space-y-3">
                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center mx-auto shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    @if(!empty($search))
                        <h3 class="text-base font-bold text-slate-800">Tidak ada kelas yang cocok</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            Tidak ditemukan kelas dengan kata kunci "<span class="font-semibold text-slate-700">{{ $search }}</span>". Coba gunakan kata kunci lain.
                        </p>
                        <button type="button" wire:click="resetSearch" class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer">
                            Hapus Pencarian
                        </button>
                    @else
                        <h3 class="text-base font-bold text-slate-800">Kamu belum bergabung di kelas manapun</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                            Minta kode kelas 6 karakter dari gurumu (contoh: MTK10A), lalu masukkan pada formulir di atas untuk mulai belajar.
                        </p>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($enrolledClasses as $kelas)
                        <div class="bg-white border border-slate-200/90 rounded-2xl sm:rounded-3xl p-5 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-200 flex flex-col justify-between group">
                            
                            <!-- Top Card Info -->
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                                    </span>
                                    <span class="font-mono text-[11px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                                        {{ $kelas->kode_kelas }}
                                    </span>
                                </div>

                                <h3 class="font-bold text-slate-900 text-base sm:text-lg group-hover:text-indigo-600 transition-colors line-clamp-1">
                                    {{ $kelas->nama }}
                                </h3>

                                <!-- Teacher Info with Avatar -->
                                <div class="flex items-center gap-2 mt-1.5 mb-3 text-xs text-slate-500">
                                    <div class="h-5 w-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[9px] shrink-0 uppercase">
                                        {{ substr($kelas->guru->name ?? 'P', 0, 1) }}
                                    </div>
                                    <span class="truncate">{{ $kelas->guru->name ?? 'Pengajar' }}</span>
                                </div>

                                <p class="text-xs text-slate-500 line-clamp-2 mb-4 leading-relaxed font-normal">
                                    {{ $kelas->deskripsi ?: 'Modul dan aktivitas belajar terstruktur per bab.' }}
                                </p>

                                <!-- Modern Dynamic Progress Bar -->
                                <div class="space-y-1.5 mb-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-600 font-semibold text-[11px]">Progres Belajar</span>
                                        <span class="font-bold {{ $kelas->progres_persen >= 100 ? 'text-emerald-600' : 'text-indigo-600' }}">
                                            {{ $kelas->progres_persen }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-500 {{ $kelas->progres_persen >= 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-indigo-500 to-purple-600' }}" style="width: {{ $kelas->progres_persen }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] text-slate-400 font-medium">
                                        <span>{{ $kelas->completed_materi }} dari {{ $kelas->total_materi }} materi selesai</span>
                                        @if($kelas->progres_persen >= 100)
                                            <span class="text-emerald-600 font-bold">Tuntas ✓</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Action Card -->
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>{{ $kelas->chapters->count() }} Bab</span>
                                </span>

                                <a href="{{ route('siswa.kelas.show', $kelas->id) }}" 
                                   wire:navigate 
                                   class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 group-hover:translate-x-0.5 transition-all">
                                    <span>Masuk Kelas</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
