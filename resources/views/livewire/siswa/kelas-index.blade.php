<?php

use App\Models\Kelas;
use App\Models\Enrollment;
use App\Models\Progress;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|string|min:4|max:10')]
    public string $kodeKelas = '';

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

        $kelasList = Kelas::whereHas('enrollments', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['guru', 'chapters.materials'])
        ->latest()
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
        @endif

        <!-- ============================================================ -->
        <!-- BANNER & FORM GABUNG KELAS (Bersih, Rapi & Elegan)            -->
        <!-- ============================================================ -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-1 max-w-xl">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    Kelas Pembelajaran
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Daftar ruang kelas aktif yang kamu ikuti. Masukkan kode kelas dari guru untuk mulai mengikuti materi pelajaran baru.
                </p>
            </div>

            <!-- Form Gabung Kelas -->
            <form wire:submit="gabung" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
                <div class="relative">
                    <input type="text" 
                           wire:model="kodeKelas" 
                           placeholder="KODE KELAS (MIS: MTK10A)" 
                           class="w-full sm:w-56 px-3.5 py-2 text-xs font-mono font-bold tracking-wider uppercase rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition" 
                           required>
                    @error('kodeKelas')
                        <span class="text-[11px] font-semibold text-rose-500 block sm:absolute sm:-bottom-5 sm:left-0 mt-1 sm:mt-0">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" 
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-xs transition active:scale-95 cursor-pointer shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Gabung Kelas</span>
                </button>
            </form>
        </div>

        <!-- ============================================================ -->
        <!-- DAFTAR KELAS SISWA (Desain Ramping & Profesional)            -->
        <!-- ============================================================ -->
        @if($kelasList->isEmpty())
            <!-- Empty State Bersih -->
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 p-8 sm:p-12 text-center space-y-4 shadow-2xs">
                <div class="h-14 w-14 mx-auto rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-800">Kamu Belum Mengikuti Kelas</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        Mintalah kode kelas kepada guru pengajar, lalu masukkan pada formulir di atas untuk mulai belajar.
                    </p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                @foreach($kelasList as $kelas)
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                        
                        <!-- Konten Utama Kartu Kelas -->
                        <div class="p-5 space-y-3.5">
                            <!-- Info Guru Pengajar -->
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span class="flex items-center gap-1.5 font-medium truncate">
                                    <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                    <span class="truncate">{{ $kelas->guru->name ?? 'Guru Pengajar' }}</span>
                                </span>
                                <span class="font-mono text-[11px] font-bold text-slate-400 uppercase shrink-0">{{ $kelas->mapel }}</span>
                            </div>

                            <!-- Judul & Deskripsi Kelas -->
                            <div class="space-y-1">
                                <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate class="font-extrabold text-base text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1 block">
                                    {{ $kelas->nama }}
                                </a>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed min-h-[34px]">
                                    {{ $kelas->deskripsi ?: 'Tidak ada deskripsi untuk kelas ini.' }}
                                </p>
                            </div>

                            <!-- Indikator Progres Belajar Siswa -->
                            <div class="space-y-1.5 pt-3 border-t border-slate-100">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-medium">Progres Materi</span>
                                    <span class="font-bold text-slate-800">{{ $kelas->progres_persen }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $kelas->progres_persen }}%"></div>
                                </div>
                                <p class="text-[11px] text-slate-400">
                                    <b class="text-slate-700 font-semibold">{{ $kelas->completed_materi }}</b> dari {{ $kelas->total_materi }} materi selesai
                                </p>
                            </div>
                        </div>

                        <!-- Footer Aksi Kartu -->
                        <div class="px-5 py-3 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" 
                                    @click="confirmKeluar({{ $kelas->id }}, '{{ addslashes($kelas->nama) }}')" 
                                    class="text-xs font-semibold text-slate-400 hover:text-rose-600 transition cursor-pointer">
                                Keluar
                            </button>
                            <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate 
                               class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 group-hover:gap-1.5 transition-all">
                                <span>Buka Kelas</span>
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
</div>
