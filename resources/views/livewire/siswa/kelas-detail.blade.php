<?php

use App\Models\Kelas;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\QuizAttempt;
use Livewire\Volt\Component;

new class extends Component {
    public int $kelasId;

    public function mount(int $kelas): void
    {
        $this->kelasId = $kelas;

        // Pastikan siswa sudah terdaftar di kelas ini
        $isEnrolled = Enrollment::where('user_id', auth()->id())
            ->where('class_id', $this->kelasId)
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'Anda belum terdaftar di kelas ini.');
        }
    }

    public function with(): array
    {
        $userId = auth()->id();

        $kelas = Kelas::with(['guru', 'chapters.materials', 'chapters.quizzes'])
            ->findOrFail($this->kelasId);

        // Ambil ID materi yang sudah selesai
        $completedMaterialIds = Progress::where('user_id', $userId)
            ->where('status_selesai', true)
            ->pluck('material_id')
            ->toArray();

        // Ambil riwayat kuis siswa
        $attempts = QuizAttempt::where('user_id', $userId)
            ->where('status', 'selesai')
            ->get()
            ->groupBy('quiz_id');

        $allMaterialsCount = $kelas->chapters->flatMap->materials->count();
        $completedCount = count(array_intersect(
            $kelas->chapters->flatMap->materials->pluck('id')->toArray(),
            $completedMaterialIds
        ));

        $progresPersen = $allMaterialsCount > 0 
            ? round(($completedCount / $allMaterialsCount) * 100) 
            : 0;

        return [
            'kelas' => $kelas,
            'completedMaterialIds' => $completedMaterialIds,
            'quizAttempts' => $attempts,
            'progresPersen' => $progresPersen,
            'allMaterialsCount' => $allMaterialsCount,
            'completedCount' => $completedCount,
        ];
    }
}; ?>

<div class="py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Header Info Kelas -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-xs">
                <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="font-semibold text-gray-500 hover:text-emerald-600 flex items-center gap-1">
                    &larr; Kelas Saya
                </a>
                <span class="text-gray-300">&bull;</span>
                <span class="px-2.5 py-0.5 rounded font-semibold bg-emerald-50 text-emerald-800">
                    {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                {{ $kelas->nama }}
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 max-w-2xl">
                {{ $kelas->deskripsi ?: 'Pelajari bab dan materi terstruktur berikut.' }}
            </p>
            <p class="text-xs text-gray-400 mt-2">
                👨‍🏫 Pengajar: <b>{{ $kelas->guru->name ?? 'Guru RuangKelas' }}</b>
            </p>

            <!-- Progres Bar Pembelajaran -->
            <div class="mt-6 pt-6 border-t border-gray-100 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-700">Progres Belajar Kamu</span>
                    <span class="font-extrabold text-emerald-600 text-sm">{{ $progresPersen }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progresPersen }}%"></div>
                </div>
                <p class="text-[11px] text-gray-400">
                    {{ $completedCount }} dari {{ $allMaterialsCount }} materi telah kamu selesaikan
                </p>
            </div>
        </div>

        <!-- Daftar Bab & Konten Belajar -->
        <div class="space-y-5">
            <h2 class="text-lg font-bold text-gray-900">Silabus & Materi Belajar</h2>

            @if($kelas->chapters->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                    <div class="text-4xl mb-2">📖</div>
                    <h3 class="font-bold text-gray-800 text-sm">Belum ada materi pembelajaran</h3>
                    <p class="text-xs text-gray-400 mt-1">Gurumu sedang menyiapkan bahan ajar untuk kelas ini.</p>
                </div>
            @else
                @foreach($kelas->chapters as $index => $chapter)
                    <div class="bg-white rounded-2xl border border-gray-200/90 shadow-2xs overflow-hidden">
                        <!-- Chapter Header -->
                        <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="h-7 w-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm sm:text-base">{{ $chapter->judul }}</h3>
                                    @if($chapter->deskripsi)
                                        <p class="text-xs text-gray-500">{{ $chapter->deskripsi }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 hidden sm:inline">
                                {{ $chapter->materials->count() }} Materi &bull; {{ $chapter->quizzes->count() }} Kuis
                            </span>
                        </div>

                        <!-- Chapter Items -->
                        <div class="p-5 divide-y divide-gray-100">
                            @if($chapter->materials->isEmpty() && $chapter->quizzes->isEmpty())
                                <p class="text-xs text-gray-400 italic text-center py-3">Belum ada materi di bab ini.</p>
                            @endif

                            <!-- Materi -->
                            @foreach($chapter->materials as $mat)
                                @php
                                    $isDone = in_array($mat->id, $completedMaterialIds);
                                @endphp
                                <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 {{ $isDone ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            @if($isDone)
                                                ✓
                                            @elseif($mat->tipe === 'video')
                                                🎥
                                            @elseif($mat->tipe === 'pdf')
                                                📄
                                            @else
                                                📝
                                            @endif
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-semibold text-xs sm:text-sm text-gray-800 {{ $isDone ? 'line-through text-gray-400' : '' }}">
                                                    {{ $mat->judul }}
                                                </h4>
                                                @if($isDone)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Selesai
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                                                <span class="uppercase font-semibold text-emerald-600">{{ $mat->tipe }}</span>
                                                <span>&bull;</span>
                                                <span>⏱️ {{ $mat->durasi_menit ?: 10 }} Menit</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="{{ route('siswa.materi.show', $mat->id) }}" wire:navigate class="shrink-0 text-xs font-bold px-3.5 py-1.5 rounded-lg transition {{ $isDone ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' }}">
                                        {{ $isDone ? 'Ulas Lagi' : 'Pelajari &rarr;' }}
                                    </a>
                                </div>
                            @endforeach

                            <!-- Kuis -->
                            @foreach($chapter->quizzes as $quiz)
                                @php
                                    $attempt = $quizAttempts->get($quiz->id)?->sortByDesc('skor')->first();
                                    $isPassed = $attempt && $attempt->skor >= $quiz->kkm;
                                @endphp
                                <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4 bg-purple-50/20 -mx-5 px-5 my-1">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 {{ $attempt ? ($isPassed ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') : 'bg-purple-100 text-purple-700' }}">
                                            🎯
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-xs sm:text-sm text-gray-900">
                                                    {{ $quiz->judul }}
                                                </h4>
                                                @if($attempt)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isPassed ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                                        Nilai: {{ $attempt->skor }} (KKM: {{ $quiz->kkm }}) {{ $isPassed ? '✓ Lulus' : 'Belum Lulus' }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                                                <span>⏱️ {{ $quiz->durasi_menit }} Menit</span>
                                                <span>&bull;</span>
                                                <span>KKM: {{ $quiz->kkm }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="{{ route('siswa.kuis.show', $quiz->id) }}" wire:navigate class="shrink-0 text-xs font-bold px-3.5 py-1.5 rounded-lg transition {{ $attempt ? 'bg-purple-100 hover:bg-purple-200 text-purple-800' : 'bg-purple-600 hover:bg-purple-700 text-white shadow-xs' }}">
                                        {{ $attempt ? 'Coba Lagi' : 'Mulai Kuis &rarr;' }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
