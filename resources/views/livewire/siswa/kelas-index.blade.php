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
        $this->validate();

        $kode = strtoupper(trim($this->kodeKelas));
        $kelas = Kelas::where('kode_kelas', $kode)->where('aktif', true)->first();

        if (!$kelas) {
            $this->addError('kodeKelas', 'Kode kelas tidak valid atau tidak ditemukan.');
            return;
        }

        $userId = auth()->id();
        $isEnrolled = Enrollment::where('user_id', $userId)->where('class_id', $kelas->id)->exists();

        if ($isEnrolled) {
            $this->addError('kodeKelas', 'Kamu sudah bergabung di kelas ini.');
            return;
        }

        Enrollment::create([
            'user_id' => $userId,
            'class_id' => $kelas->id,
            'tanggal_gabung' => now(),
        ]);

        $this->reset('kodeKelas');
        session()->flash('success', 'Berhasil bergabung ke kelas ' . $kelas->nama);
    }

    public function keluarKelas(int $id): void
    {
        $enrollment = Enrollment::where('user_id', auth()->id())->where('class_id', $id)->first();
        if ($enrollment) {
            $enrollment->delete();
            session()->flash('success', 'Kamu telah keluar dari kelas tersebut.');
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

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 flex items-center justify-between text-sm shadow-sm transition">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
            </div>
        @endif

        <!-- Form Gabung Kelas -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Kelas Pembelajaran Saya</h1>
                <p class="text-xs text-gray-500 mt-1">Daftar kelas yang kamu ikuti. Masukkan kode baru untuk bergabung ke kelas lainnya.</p>
            </div>

            <form wire:submit="gabung" class="flex flex-col sm:flex-row gap-2 shrink-0">
                <div class="relative">
                    <input type="text" wire:model="kodeKelas" placeholder="Kode Kelas (mis: MTK10A)" class="text-xs font-mono uppercase tracking-wider rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 w-full sm:w-56" required>
                    @error('kodeKelas')
                        <span class="text-[11px] text-red-500 block absolute -bottom-5 left-0">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-xs transition">
                    + Gabung Kelas
                </button>
            </form>
        </div>

        <!-- Grid Kelas -->
        @if($kelasList->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16 px-4">
                <div class="text-4xl mb-3">🏫</div>
                <h3 class="text-base font-bold text-gray-800">Kamu belum mengikuti kelas</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-4">
                    Tanyakan kode kelas kepada gurumu untuk mulai belajar melalui materi terstruktur dan latihan soal.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($kelasList as $kelas)
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-lg hover:border-emerald-300 transition-all flex flex-col justify-between overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                                    {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                                </span>
                                <span class="text-xs text-gray-400">Guru: {{ $kelas->guru->name ?? 'Pengajar' }}</span>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 mb-2 leading-snug line-clamp-1">
                                {{ $kelas->nama }}
                            </h3>
                            <p class="text-xs text-gray-500 line-clamp-2 min-h-[32px] mb-4">
                                {{ $kelas->deskripsi ?: 'Tidak ada deskripsi.' }}
                            </p>

                            <!-- Progress Bar -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 font-medium">Progres Belajar</span>
                                    <span class="font-bold text-emerald-600">{{ $kelas->progres_persen }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $kelas->progres_persen }}%"></div>
                                </div>
                                <p class="text-[10px] text-gray-400">
                                    {{ $kelas->completed_materi }} dari {{ $kelas->total_materi }} materi selesai
                                </p>
                            </div>
                        </div>

                        <!-- Footer Action -->
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                            <button wire:confirm="Yakin ingin keluar dari kelas ini?" wire:click="keluarKelas({{ $kelas->id }})" class="text-xs text-gray-400 hover:text-red-600 transition">
                                Keluar Kelas
                            </button>
                            <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 hover:text-emerald-800">
                                Masuk Belajar &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
