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

    public function gabungKelas(): void
    {
        $this->validate();

        $kode = strtoupper(trim($this->kodeKelas));
        $kelas = Kelas::where('kode_kelas', $kode)->where('aktif', true)->first();

        if (!$kelas) {
            $this->addError('kodeKelas', 'Kode kelas tidak ditemukan atau sudah tidak aktif.');
            return;
        }

        $userId = auth()->id();
        $isEnrolled = Enrollment::where('user_id', $userId)->where('class_id', $kelas->id)->exists();

        if ($isEnrolled) {
            $this->addError('kodeKelas', 'Anda sudah bergabung di kelas ' . $kelas->nama . ' ini.');
            return;
        }

        Enrollment::create([
            'user_id' => $userId,
            'class_id' => $kelas->id,
            'tanggal_gabung' => now(),
        ]);

        $this->reset('kodeKelas');
        session()->flash('success', 'Selamat! Anda berhasil bergabung ke kelas ' . $kelas->nama . '.');
    }

    public function with(): array
    {
        $userId = auth()->id();

        $enrolledClasses = Kelas::whereHas('enrollments', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['guru', 'chapters.materials'])
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

        $totalKelasDiikuti = $enrolledClasses->count();
        $totalMateriSelesai = Progress::where('user_id', $userId)->where('status_selesai', true)->count();
        $totalKuisSelesai = QuizAttempt::where('user_id', $userId)->where('status', 'selesai')->count();

        return [
            'enrolledClasses' => $enrolledClasses,
            'totalKelasDiikuti' => $totalKelasDiikuti,
            'totalMateriSelesai' => $totalMateriSelesai,
            'totalKuisSelesai' => $totalKuisSelesai,
        ];
    }
}; ?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Flash Alert -->
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

        <!-- Banner Siswa & Form Gabung Kelas Cepat -->
        <div class="relative overflow-hidden bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-emerald-100">
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <div class="lg:col-span-7">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-md mb-3">
                        🎒 Ruang Belajar Siswa
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Semangat Belajar, {{ auth()->user()->name }}! 🚀
                    </h1>
                    <p class="text-emerald-100 text-sm sm:text-base mt-1 max-w-xl">
                        Akses modul materi terstruktur, pelajari video penjelasan, dan uji pemahamanmu melalui kuis latihan.
                    </p>
                </div>

                <!-- Form Gabung Kelas via Kode -->
                <div class="lg:col-span-5 bg-white/10 backdrop-blur-md p-5 rounded-2xl border border-white/20">
                    <h3 class="font-bold text-sm text-white mb-2 flex items-center gap-1.5">
                        🔑 Gabung Kelas Baru
                    </h3>
                    <form wire:submit="gabungKelas" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" wire:model="kodeKelas" placeholder="Kode Kelas (mis: MTK10A)" class="w-full text-xs font-mono uppercase tracking-wider rounded-xl bg-white text-gray-900 placeholder-gray-400 border-0 focus:ring-2 focus:ring-emerald-400" required>
                        <button type="submit" class="bg-emerald-950 hover:bg-black text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-xs transition shrink-0">
                            Gabung &rarr;
                        </button>
                    </form>
                    @error('kodeKelas')
                        <p class="text-xs text-red-200 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -right-8 -bottom-8 w-56 h-56 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        <!-- Statistik Belajar Siswa -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Kelas Diikuti</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $totalKelasDiikuti }}</h3>
                        <p class="text-xs text-emerald-600 mt-1 font-medium">Kelas aktif kamu</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        📚
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Materi Selesai</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $totalMateriSelesai }}</h3>
                        <p class="text-xs text-teal-600 mt-1 font-medium">Materi dipelajari</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        ✅
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Latihan Dikerjakan</p>
                        <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ $totalKuisSelesai }}</h3>
                        <p class="text-xs text-cyan-600 mt-1 font-medium">Kuis diselesaikan</p>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                        🎯
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Kelas yang Diikuti -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Kelas Belajar Kamu</h2>
                    <p class="text-xs text-gray-500">Lanjutkan materi dan selesaikan bab yang belum tuntas</p>
                </div>
                <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 flex items-center gap-1">
                    Lihat Semua &rarr;
                </a>
            </div>

            @if($enrolledClasses->isEmpty())
                <div class="text-center py-12 px-4 border-2 border-dashed border-gray-200 rounded-2xl">
                    <div class="text-4xl mb-3">🏫</div>
                    <h3 class="text-base font-bold text-gray-800">Kamu belum bergabung di kelas manapun</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-4">
                        Minta kode kelas dari gurumu (contoh: MTK10A), lalu masukkan pada formulir di atas untuk mulai belajar.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($enrolledClasses as $kelas)
                        <div class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-5 hover:border-emerald-300 hover:bg-white hover:shadow-md transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                                    </span>
                                    <span class="text-xs text-gray-400">Guru: {{ $kelas->guru->name ?? 'Pengajar' }}</span>
                                </div>

                                <h3 class="font-bold text-gray-900 text-base mb-1 line-clamp-1">
                                    {{ $kelas->nama }}
                                </h3>
                                <p class="text-xs text-gray-500 line-clamp-2 mb-4">
                                    {{ $kelas->deskripsi ?: 'Kelas pembelajaran terstruktur.' }}
                                </p>

                                <!-- Progress Bar Pembelajaran -->
                                <div class="space-y-1 mb-4">
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

                            <div class="pt-3 border-t border-gray-200/60 flex items-center justify-between">
                                <span class="text-xs text-gray-400">
                                    📖 {{ $kelas->chapters->count() }} Bab
                                </span>
                                <a href="{{ route('siswa.kelas.show', $kelas->id) }}" wire:navigate class="font-bold text-emerald-600 hover:text-emerald-800 text-xs flex items-center gap-1">
                                    Buka Kelas &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
