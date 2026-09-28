<?php

use App\Models\Kelas;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $kelasId = null;

    #[Validate('required|string|max:100')]
    public string $nama = '';

    #[Validate('required|string|max:100')]
    public string $mapel = '';

    #[Validate('required|string|in:SD,SMP,SMA,SMK,Umum')]
    public string $jenjang = 'SMA';

    #[Validate('nullable|string|max:1000')]
    public string $deskripsi = '';

    public function openCreateModal(): void
    {
        $this->reset(['nama', 'mapel', 'deskripsi', 'kelasId']);
        $this->jenjang = 'SMA';
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
            session()->flash('success', 'Kelas berhasil diperbarui!');
        } else {
            Kelas::create([
                'guru_id' => auth()->id(),
                'nama' => $this->nama,
                'mapel' => $this->mapel,
                'jenjang' => $this->jenjang,
                'deskripsi' => $this->deskripsi,
                'aktif' => true,
            ]);
            session()->flash('success', 'Kelas baru berhasil dibuat!');
        }

        $this->showModal = false;
    }

    public function hapus(int $id): void
    {
        $kelas = Kelas::where('guru_id', auth()->id())->findOrFail($id);
        $kelas->delete();
        session()->flash('success', 'Kelas berhasil dihapus.');
    }

    public function with(): array
    {
        $kelasList = Kelas::where('guru_id', auth()->id())
            ->withCount(['siswa', 'chapters'])
            ->latest()
            ->get();

        return [
            'kelasList' => $kelasList,
        ];
    }
}; ?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Flash Alert -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 flex items-center justify-between text-sm shadow-sm transition">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
            </div>
        @endif

        <!-- Header Tindakan -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Kelola Semua Kelas</h1>
                <p class="text-xs text-gray-500 mt-1">Daftar kelas pembelajaran yang Anda bimbing</p>
            </div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl shadow-sm transition-all transform active:scale-95 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                + Tambah Kelas Baru
            </button>
        </div>

        <!-- Grid Kelas -->
        @if($kelasList->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16 px-4">
                <div class="h-20 w-20 mx-auto rounded-3xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Anda belum memiliki kelas</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-5">Mulai dengan membuat kelas pertama Anda. Anda akan mendapatkan kode kelas acak 6 digit yang bisa dibagikan ke siswa.</p>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm shadow-sm transition">
                    + Buat Kelas Sekarang
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($kelasList as $kelas)
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-lg hover:border-indigo-300 transition-all flex flex-col justify-between overflow-hidden">
                        <!-- Header Card -->
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                                </span>
                                
                                <!-- Copy Kode Kelas -->
                                <div x-data="{ copied: false }" class="flex items-center gap-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-2.5 py-1 rounded-lg">
                                    <span class="text-xs font-mono font-bold text-gray-800 tracking-wider">{{ $kelas->kode_kelas }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $kelas->kode_kelas }}'); copied = true; setTimeout(() => copied = false, 2000)" title="Salin Kode Kelas" class="text-gray-400 hover:text-indigo-600 transition">
                                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span x-show="copied" class="text-[11px] text-green-600 font-bold" style="display: none;">Tersalin!</span>
                                    </button>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 mb-2 leading-snug line-clamp-1">
                                {{ $kelas->nama }}
                            </h3>
                            <p class="text-xs text-gray-500 line-clamp-2 min-h-[32px]">
                                {{ $kelas->deskripsi ?: 'Tidak ada deskripsi untuk kelas ini.' }}
                            </p>

                            <!-- Statistik Kecil -->
                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
                                <span class="flex items-center gap-1">
                                    👥 <strong class="text-gray-900">{{ $kelas->siswa_count }}</strong> Siswa
                                </span>
                                <span class="flex items-center gap-1">
                                    📖 <strong class="text-gray-900">{{ $kelas->chapters_count }}</strong> Bab Materi
                                </span>
                            </div>
                        </div>

                        <!-- Footer Card Action -->
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button wire:click="openEditModal({{ $kelas->id }})" class="text-xs text-gray-500 hover:text-indigo-600 font-medium">
                                    Edit
                                </button>
                                <span class="text-gray-300">&bull;</span>
                                <button wire:confirm="Apakah Anda yakin ingin menghapus kelas ini beserta seluruh materi dan kuisnya?" wire:click="hapus({{ $kelas->id }})" class="text-xs text-red-500 hover:text-red-700 font-medium">
                                    Hapus
                                </button>
                            </div>
                            <a href="{{ route('guru.kelas.show', $kelas->id) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                Masuk Kelas &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Modal Buat / Edit Kelas -->
        @if($showModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-100 transform transition-all">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $isEdit ? 'Edit Kelas' : 'Buat Kelas Baru' }}
                        </h3>
                        <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                    </div>

                    <form wire:submit="simpan" class="space-y-4">
                        <!-- Nama Kelas -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Nama Kelas *</label>
                            <input type="text" wire:model="nama" placeholder="Contoh: Matematika Dasar X, IPA Biologi XII" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            @error('nama') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Mata Pelajaran -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Mata Pelajaran *</label>
                                <input type="text" wire:model="mapel" placeholder="Matematika / Fisika" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                                @error('mapel') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Jenjang -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Jenjang *</label>
                                <select wire:model="jenjang" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                    <option value="SMK">SMK</option>
                                    <option value="Umum">Umum</option>
                                </select>
                                @error('jenjang') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Deskripsi / Penjelasan Singkat</label>
                            <textarea wire:model="deskripsi" rows="3" placeholder="Jelaskan ringkasan materi atau tujuan pembelajaran kelas ini..." class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            @error('deskripsi') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                                {{ $isEdit ? 'Simpan Perubahan' : 'Buat Kelas Sekarang' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
