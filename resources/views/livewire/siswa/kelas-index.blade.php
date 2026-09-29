<?php

use App\Models\Kelas;
use App\Models\Enrollment;
use App\Models\Progress;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|string|min:4|max:10')]
    public string $kodeKelas = '';
    public string $search = '';
    public bool $showGabungModal = false;

    public function openGabungModal(): void
    {
        $this->reset('kodeKelas');
        $this->resetErrorBag();
        $this->showGabungModal = true;
    }

    public function closeGabungModal(): void
    {
        $this->showGabungModal = false;
        $this->reset('kodeKelas');
        $this->resetErrorBag();
    }

    public function gabung(): void
    {
        $this->validate([
            'kodeKelas' => 'required|string|min:4|max:10',
        ], [
            'kodeKelas.required' => 'Masukkan kode kelas terlebih dahulu.',
            'kodeKelas.min' => 'Kode kelas minimal 4 karakter.',
            'kodeKelas.max' => 'Kode kelas maksimal 10 karakter.',
        ]);

        $kode = strtoupper(trim($this->kodeKelas));
        $kelas = Kelas::where('kode_kelas', $kode)->where('aktif', true)->first();

        if (!$kelas) {
            $this->addError('kodeKelas', 'Kode kelas tidak valid atau tidak ditemukan.');
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

        $this->showGabungModal = false;
        $this->reset('kodeKelas');
        session()->flash('success', 'Berhasil bergabung ke kelas ' . $kelas->nama);
        $this->dispatch('notify', message: 'Berhasil bergabung ke kelas ' . $kelas->nama, type: 'success');
    }

    public function keluarKelas(int $id): void
    {
        $enrollment = Enrollment::where('user_id', auth()->id())->where('class_id', $id)->first();
        if ($enrollment) {
            $kelasNama = $enrollment->kelas->nama ?? 'Kelas';
            $enrollment->delete();
            session()->flash('success', 'Kamu telah keluar dari ' . $kelasNama . '.');
            $this->dispatch('notify', message: 'Telah keluar dari kelas', type: 'info');
        }
    }

    public function with(): array
    {
        $userId = auth()->id();

        $query = Kelas::whereHas('enrollments', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->when($this->search, function ($query) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('nama', 'like', $term)
                    ->orWhere('deskripsi', 'like', $term)
                    ->orWhere('mapel', 'like', $term)
                    ->orWhere('kode_kelas', 'like', $term)
                    ->orWhereHas('guru', function ($g) use ($term) {
                        $g->where('name', 'like', $term);
                    });
            });
        })
        ->with(['guru', 'chapters.materials'])
        ->latest();

        $kelasList = $query->get()
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

        return [
            'kelasList' => $kelasList,
        ];
    }
}; ?>

<div class="py-6 sm:py-8"
     x-data="{
         keluarModal: false,
         targetKelasId: null,
         targetKelasNama: '',
         confirmKeluar(id, nama) {
             this.targetKelasId = id;
             this.targetKelasNama = nama;
             this.keluarModal = true;
         },
         doKeluar() {
             if (this.targetKelasId) {
                 $wire.keluarKelas(this.targetKelasId);
             }
             this.keluarModal = false;
         }
     }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Notifikasi Berhasil -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm font-semibold shadow-2xs transition">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-600 text-base font-bold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-lg font-bold leading-none">&times;</button>
            </div>
        @endif        <!-- ============================================================ -->
        <!-- BANNER HEADER (Selaras dengan Tampilan Guru)                 -->
        <!-- ============================================================ -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1.5 max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Daftar Mata Pelajaran
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Akses proses pembelajaran secara praktis dan terstruktur. Buka materi pelajaran, selesaikan tugas latihan dan kuis, serta pantau capaian hasil belajarmu dalam satu tempat.
                </p>
            </div>

            <!-- Tombol Gabung Kelas Baru (Persis seperti Buat Kelas Baru di Guru) -->
            <div class="shrink-0 flex items-center gap-3">
                <button wire:click="openGabungModal" type="button" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs sm:text-sm shadow-sm shadow-indigo-600/20 transition active:scale-95 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Gabung Kelas</span>
                </button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- PENCARIAN KELAS (Bersih & Elegan)                            -->
        <!-- ============================================================ -->
        <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
            <div class="relative w-full max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cari nama kelas, mapel, atau guru..." 
                       class="w-full pl-9 pr-9 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 text-slate-800 transition">
                @if($search)
                    <button type="button" 
                            wire:click="$set('search', '')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- DAFTAR KELAS SISWA (Desain Selaras dengan Guru)              -->
        <!-- ============================================================ -->
        @if($kelasList->isEmpty())
            <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center space-y-4 shadow-xs">
                <div class="h-16 w-16 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl shadow-inner">
                    🏫
                </div>
                @if(!empty($search))
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-800">Tidak ada kelas yang sesuai pencarian</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Silakan bersihkan pencarian untuk melihat semua kelas yang kamu ikuti.</p>
                    </div>
                    <button wire:click="$set('search', '')" class="text-xs font-bold text-indigo-600 hover:underline cursor-pointer">
                        Reset Pencarian
                    </button>
                @else
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-800">Kamu Belum Mengikuti Kelas</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Mintalah kode kelas kepada guru pengajar, lalu klik tombol Gabung Kelas untuk mulai belajar.</p>
                    </div>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($kelasList as $kelas)
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                        
                        <!-- Konten Utama Kartu Kelas -->
                        <div class="p-5 sm:p-6 space-y-3.5">

                            <!-- Judul & Deskripsi Kelas (Posisinya di atas, selaras dengan guru) -->
                            <div class="space-y-1">
                                <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate class="font-extrabold text-base sm:text-lg text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1 block">
                                    {{ $kelas->nama }}
                                </a>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed min-h-[34px]">
                                    {{ $kelas->deskripsi ?: 'Belum ada deskripsi untuk kelas ini.' }}
                                </p>
                            </div>

                            <!-- Info Guru Pengajar & Progres Belajar Siswa (Informasi utuh terjaga) -->
                            <div class="space-y-2 pt-3 border-t border-slate-100">
                                <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                                    <span class="flex items-center gap-1.5 truncate">
                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                        <span class="truncate">{{ $kelas->guru->name ?? 'Guru Pengajar' }}</span>
                                    </span>
                                    <span class="font-bold text-slate-800 shrink-0">{{ $kelas->progres_persen }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $kelas->progres_persen }}%"></div>
                                </div>
                                <p class="text-[11px] text-slate-400">
                                    <b class="text-slate-700 font-semibold">{{ $kelas->completed_materi }}</b> dari {{ $kelas->total_materi }} materi selesai
                                </p>
                            </div>
                        </div>

                        <!-- Footer Aksi Siswa (Selaras dengan Footer Guru) -->
                        <div class="px-5 sm:px-6 py-3 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1">
                                <button type="button" 
                                        @click="confirmKeluar({{ $kelas->id }}, '{{ addslashes($kelas->nama) }}')" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                        title="Keluar dari Kelas">
                                    <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                    </svg>
                                    <span>Keluar</span>
                                </button>
                            </div>

                            <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate 
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs shadow-xs transition group-hover:shadow-sm">
                                <span>Buka Kelas</span>
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ============================================================ -->
    <!-- MODAL KONFIRMASI KELUAR KELAS (Clean & Elegan)               -->
    <!-- ============================================================ -->
    <div x-show="keluarModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-keluar-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="keluarModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="keluarModal = false"
                 class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" 
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div x-show="keluarModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200/80 p-6 space-y-4">
                
                <div class="flex items-start gap-3.5">
                    <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900" id="modal-keluar-title">
                            Keluar dari Kelas?
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Kamu akan keluar dari <span class="font-bold text-slate-700" x-text="targetKelasNama"></span>. Kamu memerlukan kode kelas kembali untuk dapat bergabung di kemudian hari.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                    <button type="button" 
                            @click="keluarModal = false" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" 
                            @click="doKeluar()" 
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 shadow-xs transition active:scale-95 cursor-pointer">
                        Ya, Keluar Kelas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL GABUNG KELAS BARU (Elegan, Sederhana & Ramah)          -->
    <!-- ============================================================ -->
    @if($showGabungModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-3 sm:p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100 my-auto animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div>
                        <h3 class="text-base font-black text-slate-900">
                            Gabung Ruang Kelas
                        </h3>
                        <p class="text-xs text-slate-500">Masukkan kode kelas yang diberikan oleh gurumu</p>
                    </div>
                    <button type="button" 
                            wire:click="closeGabungModal" 
                            class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-xl font-bold leading-none transition cursor-pointer">
                        &times;
                    </button>
                </div>

                <!-- Form Masukkan Kode -->
                <form wire:submit="gabung" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                            Kode Kelas
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   wire:model="kodeKelas" 
                                   placeholder="MISAL: BIN9A" 
                                   autofocus
                                   class="w-full px-4 py-2.5 text-sm font-mono font-bold tracking-widest uppercase rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition" 
                                   required>
                        </div>
                        @error('kodeKelas')
                            <p class="text-xs font-semibold text-rose-500 mt-1.5">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                            Minta kode kelas kepada guru pengajarmu, lalu masukkan kode di atas untuk mulai mengikuti materi dan tugas.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" 
                                wire:click="closeGabungModal" 
                                class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-sm shadow-indigo-600/20 transition active:scale-95 cursor-pointer">
                            <span>Gabung Sekarang</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
