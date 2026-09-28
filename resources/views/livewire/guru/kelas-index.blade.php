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
    public string $jenjang = 'Kelas 10';

    #[Validate('nullable|string|max:1000')]
    public string $deskripsi = '';

    public function openCreateModal(): void
    {
        $this->reset(['nama', 'mapel', 'deskripsi', 'kelasId']);
        $this->jenjang = 'Kelas 10';
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

<div class="py-6 sm:py-8 space-y-6">
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
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-2xs hover:shadow-lg hover:border-indigo-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                        
                        <!-- Konten Utama Kartu Kelas -->
                        <div class="p-6 space-y-4">
                            <!-- Badge Jenjang & Tombol Salin Kode -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                                </span>

                                <!-- Box Kode Akses Kelas yang Interaktif -->
                                <div x-data="{ copied: false }" class="flex items-center gap-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 px-2.5 py-1 rounded-xl transition shadow-2xs">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Kode:</span>
                                    <span class="text-xs font-mono font-black text-indigo-600 select-all">{{ $kelas->kode_kelas }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $kelas->kode_kelas }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                            class="text-slate-400 hover:text-indigo-600 p-0.5 transition" 
                                            title="Salin Kode Kelas">
                                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span x-show="copied" class="text-[10px] text-emerald-600 font-black" style="display: none;">✓ Tersalin</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Judul & Deskripsi Kelas -->
                            <div>
                                <h3 class="font-bold text-slate-900 text-base sm:text-lg group-hover:text-indigo-600 transition-colors line-clamp-1">
                                    {{ $kelas->nama }}
                                </h3>
                                <p class="text-xs text-slate-500 line-clamp-2 mt-1 min-h-[32px] leading-relaxed">
                                    {{ $kelas->deskripsi ?: 'Belum ada deskripsi untuk kelas ini.' }}
                                </p>
                            </div>

                            <!-- Statistik Ringkas Guru -->
                            <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-100 text-xs">
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
                                    <span class="text-base">👥</span>
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-semibold uppercase">Siswa Terdaftar</div>
                                        <div class="font-black text-slate-800 text-sm">{{ $kelas->siswa_count }} Siswa</div>
                                    </div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
                                    <span class="text-base">📚</span>
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-semibold uppercase">Silabus Bab</div>
                                        <div class="font-black text-slate-800 text-sm">{{ $kelas->chapters_count }} Bab</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Aksi Guru (Sangat Mudah Dipahami) -->
                        <div class="px-6 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <button wire:click="openEditModal({{ $kelas->id }})" class="text-xs font-bold text-slate-500 hover:text-indigo-600 p-1.5 rounded-lg hover:bg-slate-200/60 transition" title="Ubah Nama/Mapel">
                                    ✏️ Edit
                                </button>
                                <span class="text-slate-300">&bull;</span>
                                <button wire:confirm="Apakah Anda yakin ingin menghapus kelas '{{ $kelas->nama }}' beserta seluruh materi dan tugas di dalamnya?" wire:click="hapus({{ $kelas->id }})" class="text-xs font-bold text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 transition" title="Hapus Kelas">
                                    🗑️ Hapus
                                </button>
                            </div>

                            <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition group-hover:translate-x-0.5">
                                <span>Kelola Kelas</span>
                                <span>&rarr;</span>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- MODAL BUAT / EDIT KELAS (INTUITIF & SEDERHANA)               -->
        <!-- ============================================================ -->
        @if($showModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center">
                                🏫
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900">
                                    {{ $isEdit ? 'Ubah Informasi Kelas' : 'Buat Ruang Kelas Baru' }}
                                </h3>
                                <p class="text-xs text-slate-400">Isi data dasar mata pelajaran Anda</p>
                            </div>
                        </div>
                        <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
                    </div>

                    <form wire:submit="simpan" class="space-y-4">
                        <!-- Nama Kelas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kelas Pembelajaran *</label>
                            <input type="text" wire:model="nama" placeholder="Contoh: Matematika Wajib Kelas 10, Biologi Sel X-A" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-400 focus:outline-hidden" required>
                            @error('nama') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Mata Pelajaran -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran *</label>
                                <input type="text" wire:model="mapel" placeholder="Matematika, Biologi, dll" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-400 focus:outline-hidden" required>
                                @error('mapel') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Tingkat Kelas -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tingkatan Kelas *</label>
                                <select wire:model="jenjang" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-400 focus:outline-hidden">
                                    <option value="Kelas 10">Kelas 10</option>
                                    <option value="Kelas 11">Kelas 11</option>
                                    <option value="Kelas 12">Kelas 12</option>
                                </select>
                                @error('jenjang') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi / Penjelasan Singkat (Opsional)</label>
                            <textarea wire:model="deskripsi" rows="3" placeholder="Tuliskan petunjuk umum atau capaian belajar untuk siswa di kelas ini..." class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-400 focus:outline-hidden"></textarea>
                            @error('deskripsi') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tombol Aksi Modal -->
                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition flex items-center gap-1.5">
                                <span>{{ $isEdit ? '💾 Simpan Perubahan' : '🚀 Buat Kelas Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</div>
