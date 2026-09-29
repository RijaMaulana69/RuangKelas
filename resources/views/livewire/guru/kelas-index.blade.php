<?php

use App\Models\Kelas;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $kelasId = null;

    public string $search = '';
    public string $jenjangFilter = 'semua';

    #[Validate('required|string|max:100', message: 'Nama kelas wajib diisi (maks 100 karakter).')]
    public string $nama = '';

    #[Validate('required|string|max:100', message: 'Mata pelajaran wajib diisi.')]
    public string $mapel = '';

    #[Validate('required|string|max:50')]
    public string $jenjang = 'Kelas 7';

    #[Validate('nullable|string|max:1000')]
    public string $deskripsi = '';

    public function openCreateModal(): void
    {
        $this->reset(['nama', 'mapel', 'deskripsi', 'kelasId']);
        $this->jenjang = 'Kelas 7';
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $kelas = Kelas::where('guru_id', auth()->id())->findOrFail($id);
        $this->kelasId = $kelas->id;
        $this->nama = $kelas->nama;
        $this->mapel = $kelas->mapel;
        $this->jenjang = $kelas->jenjang;
        $this->deskripsi = $kelas->deskripsi ?? '';
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $this->validate();

        if ($this->isEdit && $this->kelasId) {
            $kelas = Kelas::where('guru_id', auth()->id())->findOrFail($this->kelasId);
            $kelas->update([
                'nama' => $this->nama,
                'mapel' => $this->mapel,
                'jenjang' => $this->jenjang,
                'deskripsi' => $this->deskripsi,
            ]);
            session()->flash('success', 'Perubahan informasi kelas berhasil disimpan!');
        } else {
            // Generate kode acak unik
            $kode = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $this->mapel), 0, 3) . rand(10, 99) . chr(rand(65, 90)));
            if (strlen($kode) < 6) {
                $kode = strtoupper(Str::random(6));
            }

            Kelas::create([
                'guru_id' => auth()->id(),
                'kode_kelas' => $kode,
                'nama' => $this->nama,
                'mapel' => $this->mapel,
                'jenjang' => $this->jenjang,
                'deskripsi' => $this->deskripsi,
                'aktif' => true,
            ]);
            session()->flash('success', 'Kelas baru berhasil dibuat! Bagikan kode kelas kepada siswa.');
        }

        $this->showModal = false;
    }

    public function hapus(int $id): void
    {
        $kelas = Kelas::where('guru_id', auth()->id())->findOrFail($id);
        $kelas->delete();
        session()->flash('success', 'Kelas dan seluruh datanya telah berhasil dihapus.');
        $this->dispatch('notify', message: 'Kelas berhasil dihapus');
    }

    public function with(): array
    {
        $guruId = auth()->id();
        $kelasSemuaGuru = Kelas::where('guru_id', $guruId)->get(['id', 'jenjang']);
        $totalSemuaKelas = $kelasSemuaGuru->count();
        
        // Ambil tingkatan kelas yang aktif diajar oleh guru ini
        $jenjangTersedia = $kelasSemuaGuru->pluck('jenjang')->unique()->filter()->values()->toArray();
        usort($jenjangTersedia, function($a, $b) {
            return strnatcmp($a, $b);
        });

        // Validasi filter aktif
        if ($this->jenjangFilter !== 'semua' && !in_array($this->jenjangFilter, $jenjangTersedia)) {
            $this->jenjangFilter = 'semua';
        }

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

        if ($this->jenjangFilter !== 'semua') {
            $query->where('jenjang', $this->jenjangFilter);
        }

        $kelasList = $query->get();

        return [
            'kelasList' => $kelasList,
            'totalSemuaKelas' => $totalSemuaKelas,
            'jenjangTersedia' => $jenjangTersedia,
        ];
    }
}; ?>

<div class="py-6 sm:py-8 space-y-6"
     x-data="{
         deleteModal: false,
         deleteTitle: '',
         deleteMessage: '',
         deleteAction: null,
         confirmDelete(title, message, callback) {
             this.deleteTitle = title;
             this.deleteMessage = message || 'Tindakan ini tidak dapat dibatalkan.';
             this.deleteAction = callback;
             this.deleteModal = true;
         },
         doDelete() {
             if (this.deleteAction) {
                 this.deleteAction();
             }
             this.deleteModal = false;
         }
     }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Flash Alert Notifikasi Ramah -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm font-semibold shadow-xs transition">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-600 text-base font-bold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-lg font-bold">&times;</button>
            </div>
        @endif

        <!-- Banner Header: Bersih, Profesional & Tombol Aksi Jelas -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1.5 max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Kelola Kelas
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Kelola proses pembelajaran kelas secara praktis dan terstruktur. Akses materi pelajaran, tugas latihan, kuis, serta pantau capaian hasil belajar siswa dalam satu tempat.
                </p>
            </div>

            <!-- Tombol Tambah Kelas Baru (Satu Icon Plus, Rapi & Elegan) -->
            <div class="shrink-0 flex items-center gap-3">
                <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs sm:text-sm shadow-sm shadow-indigo-600/20 transition active:scale-95 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Kelas Baru</span>
                </button>
            </div>
        </div>

        <!-- Filter & Pencarian Sederhana -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
            <!-- Search Box -->
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Cari nama kelas, mapel, atau kode kelas..." 
                       class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 text-slate-800 transition">
            </div>

            <!-- Filter Jenjang: Hanya muncul untuk tingkatan kelas yang aktif diajar oleh guru -->
            @if(count($jenjangTersedia) > 0)
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs font-bold text-slate-600">
                    <span class="text-slate-400 text-[11px] mr-1 hidden md:inline">Tingkat:</span>
                    <button wire:click="$set('jenjangFilter', 'semua')" class="px-3 py-1.5 rounded-xl transition cursor-pointer shrink-0 {{ $jenjangFilter === 'semua' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                        Semua
                    </button>
                    @foreach($jenjangTersedia as $itemJenjang)
                        <button wire:click="$set('jenjangFilter', '{{ $itemJenjang }}')" class="px-3 py-1.5 rounded-xl transition cursor-pointer shrink-0 {{ $jenjangFilter === $itemJenjang ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                            {{ $itemJenjang }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Grid Daftar Kelas Guru -->
        @if($kelasList->isEmpty())
            <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center space-y-4 shadow-xs">
                <div class="h-16 w-16 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl shadow-inner">
                    🏫
                </div>
                @if(!empty($search) || $jenjangFilter !== 'semua')
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-800">Tidak ada kelas yang sesuai pencarian</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Silakan bersihkan filter pencarian untuk melihat semua kelas.</p>
                    </div>
                    <button wire:click="$set('search', ''); $set('jenjangFilter', 'semua')" class="text-xs font-bold text-indigo-600 hover:underline cursor-pointer">
                        Reset Filter
                    </button>
                @else
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-800">Belum Ada Kelas yang Dibuat</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Buat ruang kelas pertama Anda sekarang. Kode unik akan dibuat otomatis untuk dibagikan kepada siswa Anda.</p>
                    </div>
                    <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Kelas Pertama</span>
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($kelasList as $kelas)
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                        
                        <!-- Konten Utama Kartu Kelas -->
                        <div class="p-5 sm:p-6 space-y-3.5">

                            <!-- Judul & Deskripsi Kelas -->
                            <div class="space-y-1">
                                <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate class="font-extrabold text-base sm:text-lg text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1 block">
                                    {{ $kelas->nama }}
                                </a>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed min-h-[34px]">
                                    {{ $kelas->deskripsi ?: 'Belum ada deskripsi untuk kelas ini.' }}
                                </p>
                            </div>

                            <!-- Statistik Ringkas Guru (Bersih, Ramping, Tanpa Emoji Kasar) -->
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

                        <!-- Footer Aksi Guru (Sederhana & Profesional, Ikon Halus) -->
                        <div class="px-5 sm:px-6 py-3 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="openEditModal({{ $kelas->id }})" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 transition cursor-pointer" 
                                        title="Ubah Kelas">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                    <span>Edit</span>
                                </button>
                                <span class="text-slate-200">&bull;</span>
                                <button type="button" @click="confirmDelete('Hapus Kelas {{ $kelas->nama }}?', 'Seluruh bab materi, tugas, kuis, dan data siswa di dalam kelas ini akan dihapus permanen.', () => $wire.hapus({{ $kelas->id }}))" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 transition cursor-pointer" 
                                        title="Hapus Kelas">
                                    <svg class="h-3.5 w-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </div>

                            <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate 
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs shadow-xs transition group-hover:shadow-sm">
                                <span>Kelola Kelas</span>
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- MODAL BUAT / EDIT KELAS (SEDERHANA & PROFESIONAL)           -->
        <!-- ============================================================ -->
        @if($showModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                {{ $isEdit ? 'Ubah Informasi Kelas' : 'Buat Kelas Baru' }}
                            </h3>
                            <p class="text-xs text-slate-500">Lengkapi data kelas untuk memulai proses pembelajaran</p>
                        </div>
                        <button type="button" wire:click="$set('showModal', false)" class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-xl font-bold leading-none transition cursor-pointer">&times;</button>
                    </div>

                    <form wire:submit="simpan" class="space-y-4">
                        <!-- Nama Kelas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Nama Kelas</label>
                            <input type="text" 
                                   wire:model="nama" 
                                   placeholder="Contoh: Matematika Kelas 7A" 
                                   class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition" 
                                   required>
                            @error('nama') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Mata Pelajaran -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Mata Pelajaran</label>
                                <input type="text" 
                                       wire:model="mapel" 
                                       placeholder="Contoh: Matematika" 
                                       class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition" 
                                       required>
                                @error('mapel') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Tingkat Kelas (Khusus SMP) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Tingkat Kelas</label>
                                <select wire:model="jenjang" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition">
                                    <option value="Kelas 7">Kelas 7</option>
                                    <option value="Kelas 8">Kelas 8</option>
                                    <option value="Kelas 9">Kelas 9</option>
                                </select>
                                @error('jenjang') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Deskripsi (Opsional)</label>
                            <textarea wire:model="deskripsi" rows="3" placeholder="Tuliskan petunjuk umum atau capaian belajar untuk siswa di kelas ini..." class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-slate-800 transition"></textarea>
                            @error('deskripsi') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tombol Aksi Modal -->
                        <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white shadow-sm shadow-indigo-600/20 transition active:scale-95 cursor-pointer">
                                <span>{{ $isEdit ? 'Simpan' : 'Buat Kelas' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>

    <!-- ============================================================ -->
    <!-- MODAL KONFIRMASI HAPUS (CLEAN, SEDERHANA & PROFESIONAL)      -->
    <!-- ============================================================ -->
    <div x-show="deleteModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         style="display: none;">
        
        <div @click.away="deleteModal = false"
             x-show="deleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 text-center space-y-4">
            
            <!-- Ikon Hapus Lembut -->
            <div class="h-12 w-12 mx-auto rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <!-- Teks Konfirmasi -->
            <div class="space-y-1">
                <h4 class="font-extrabold text-base text-slate-900" x-text="deleteTitle"></h4>
                <p class="text-xs text-slate-500 leading-relaxed max-w-xs mx-auto" x-text="deleteMessage"></p>
            </div>

            <!-- Tombol Batal & Hapus -->
            <div class="flex items-center gap-2.5 pt-2">
                <button type="button" @click="deleteModal = false"
                        class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 text-slate-700 text-xs font-bold transition cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="doDelete()"
                        class="flex-1 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 active:scale-95 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
