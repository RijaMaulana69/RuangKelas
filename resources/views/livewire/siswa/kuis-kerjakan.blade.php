<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Enrollment;
use Livewire\Volt\Component;

new class extends Component {
    public int $quizId;
    public array $jawaban = []; // [questionId => optionId or text]
    public bool $sudahSubmit = false;
    public ?QuizAttempt $attemptTerakhir = null;

    public function mount(int $quiz): void
    {
        $this->quizId = $quiz;
        $quizModel = Quiz::with('chapter.kelas')->findOrFail($this->quizId);

        // Validasi siswa terdaftar di kelas kuis ini
        $isEnrolled = Enrollment::where('user_id', auth()->id())
            ->where('class_id', $quizModel->chapter->class_id)
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'Anda belum terdaftar di kelas kuis ini.');
        }

        // Cek apakah ada riwayat attempt sebelumnya
        $latest = QuizAttempt::where('user_id', auth()->id())
            ->where('quiz_id', $this->quizId)
            ->where('status', 'selesai')
            ->latest()
            ->first();

        if ($latest) {
            $this->attemptTerakhir = $latest;
            $this->jawaban = $latest->jawaban ?? [];
            $this->sudahSubmit = true;
        }
    }

    public function mulaiUlang(): void
    {
        $this->jawaban = [];
        $this->sudahSubmit = false;
        $this->attemptTerakhir = null;
    }

    public function kumpulkanJawaban(): void
    {
        $quiz = Quiz::with('questions.options')->findOrFail($this->quizId);

        $totalBobot = $quiz->questions->sum('bobot');
        $bobotDidapat = 0;
        $totalBenar = 0;
        $totalSalah = 0;

        foreach ($quiz->questions as $q) {
            if ($q->tipe === 'pilihan_ganda') {
                $selectedOptionId = $this->jawaban[$q->id] ?? null;
                $correctOption = $q->options->where('is_benar', true)->first();

                if ($selectedOptionId && $correctOption && $selectedOptionId == $correctOption->id) {
                    $bobotDidapat += $q->bobot;
                    $totalBenar++;
                } else {
                    $totalSalah++;
                }
            } elseif ($q->tipe === 'essay') {
                // Untuk essay, skor awal 0 sampai guru memberikan penilaian manual
            }
        }

        $skorAkhir = $totalBobot > 0 
            ? round(($bobotDidapat / $totalBobot) * 100, 2) 
            : 0;

        $attempt = QuizAttempt::create([
            'quiz_id' => $this->quizId,
            'user_id' => auth()->id(),
            'skor' => $skorAkhir,
            'total_benar' => $totalBenar,
            'total_salah' => $totalSalah,
            'jawaban' => $this->jawaban,
            'status' => 'selesai',
            'completed_at' => now(),
        ]);

        $this->attemptTerakhir = $attempt;
        $this->sudahSubmit = true;
        session()->flash('success', 'Kuis berhasil dikumpulkan!');
    }

    public function with(): array
    {
        $quiz = Quiz::with(['chapter.kelas', 'questions.options'])->findOrFail($this->quizId);

        return [
            'quiz' => $quiz,
        ];
    }
}; ?>

<div class="py-6 sm:py-8 pb-28 md:pb-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Breadcrumb Navigasi -->
        <div>
            <a href="{{ route('siswa.kelas.show', $quiz->chapter->class_id) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-emerald-600 transition bg-white px-3.5 py-1.5 rounded-xl border border-slate-200/80 shadow-2xs">
                &larr; Silabus: {{ $quiz->chapter->kelas->nama }}
            </a>
        </div>

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm shadow-sm transition">
                <div class="flex items-center gap-2 font-semibold">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-lg">&times;</button>
            </div>
        @endif

        <!-- Card Hasil Skor jika sudah submit -->
        @if($sudahSubmit && $attemptTerakhir)
            @php
                $isPassed = $attemptTerakhir->skor >= $quiz->kkm;
                $hasEssay = $quiz->questions->where('tipe', 'essay')->count() > 0;
            @endphp
            <div class="rounded-3xl p-6 sm:p-8 border {{ $isPassed ? 'bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-100/50 border-emerald-200' : 'bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100/50 border-amber-200' }} shadow-md space-y-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 text-center sm:text-left">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold {{ $isPassed ? 'bg-emerald-600 text-white shadow-sm' : 'bg-amber-600 text-white shadow-sm' }}">
                            {{ $isPassed ? '🎉 LULUS (Memenuhi KKM)' : '⚠️ BELUM LULUS (Di Bawah KKM)' }}
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mt-2">
                            Skor: <span class="{{ $isPassed ? 'text-emerald-600' : 'text-amber-600' }}">{{ $attemptTerakhir->skor }}</span> / 100
                        </h2>
                        <p class="text-xs text-slate-600">
                            Batas Lulus (KKM): <b>{{ $quiz->kkm }}</b> &bull; PG Benar: <b>{{ $attemptTerakhir->total_benar }}</b> &bull; PG Salah: <b>{{ $attemptTerakhir->total_salah }}</b>
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
                        <button wire:click="mulaiUlang" class="flex-1 sm:flex-none bg-white hover:bg-slate-50 text-slate-800 font-bold text-xs px-5 py-3 rounded-xl border border-slate-200 shadow-2xs transition cursor-pointer">
                            🔄 Ulangi Kuis
                        </button>
                        <a href="{{ route('siswa.kelas.show', $quiz->chapter->class_id) }}" wire:navigate class="flex-1 sm:flex-none text-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-xs transition">
                            Selesai &rarr;
                        </a>
                    </div>
                </div>

                @if ($hasEssay)
                    <div class="p-3.5 rounded-2xl bg-white/80 border border-slate-200/80 text-xs text-slate-700 flex items-center gap-2.5">
                        <span class="text-base">📝</span>
                        <span><b>Catatan:</b> Kuis ini mencakup soal essay. Skor di atas adalah evaluasi awal untuk Pilihan Ganda. Nilai total Anda akan bertambah setelah guru memeriksa jawaban essay secara manual.</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Card Pertanyaan Kuis -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-6">
            <!-- Header Kuis -->
            <div class="border-b border-slate-100 pb-5">
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400 mb-2">
                    <span class="font-semibold text-slate-600">{{ $quiz->chapter->judul }}</span>
                    <span>&bull;</span>
                    <span>⏱️ Durasi: {{ $quiz->durasi_menit }} Menit</span>
                    <span>&bull;</span>
                    <span>🎯 KKM: {{ $quiz->kkm }}</span>
                    <span>&bull;</span>
                    <span class="font-semibold text-purple-700">{{ $quiz->questions->count() }} Butir Soal</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    {{ $quiz->judul }}
                </h1>
                @if($quiz->deskripsi)
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">{{ $quiz->deskripsi }}</p>
                @endif
            </div>

            <!-- List Soal -->
            @if($quiz->questions->isEmpty())
                <div class="text-center py-12 text-slate-400 text-xs">
                    Kuis ini belum memiliki soal. Silakan hubungi guru Anda.
                </div>
            @else
                <form wire:submit="kumpulkanJawaban" class="space-y-6">
                    @php
                        $penilaianEssay = $attemptTerakhir?->jawaban['penilaian_essay'] ?? [];
                        $feedbackEssay = $attemptTerakhir?->jawaban['feedback_essay'] ?? [];
                    @endphp

                    @foreach($quiz->questions as $index => $q)
                        <div class="p-5 sm:p-6 rounded-2xl border transition border-slate-200/90 bg-white space-y-4">
                            <!-- Pertanyaan Soal -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold {{ $q->tipe === 'essay' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $q->tipe === 'essay' ? '📝 Soal Essay (Uraian)' : '🔘 Pilihan Ganda' }}
                                    </span>
                                    <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                                        <span class="text-emerald-600 font-black">{{ $index + 1 }}.</span> {{ $q->pertanyaan }}
                                    </h3>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 shrink-0">
                                    {{ $q->bobot }} Poin
                                </span>
                            </div>

                            <!-- 1. SOAL PILIHAN GANDA (OTOMATIS) -->
                            @if ($q->tipe === 'pilihan_ganda')
                                @php
                                    $selectedOpt = $jawaban[$q->id] ?? null;
                                    $correctOpt = $q->options->where('is_benar', true)->first();
                                @endphp
                                <div class="space-y-2.5">
                                    @foreach($q->options as $opt)
                                        @php
                                            $isSelected = ($selectedOpt == $opt->id);
                                            $isCorrect = $opt->is_benar;
                                        @endphp
                                        <label class="flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition text-xs sm:text-sm {{ $sudahSubmit ? ($isCorrect ? 'border-emerald-500 bg-emerald-100/60 font-bold text-emerald-950' : ($isSelected ? 'border-rose-400 bg-rose-100/60 font-bold text-rose-950' : 'border-slate-200 opacity-60')) : ($isSelected ? 'border-emerald-600 bg-emerald-50 text-emerald-950 font-bold ring-2 ring-emerald-500/20' : 'border-slate-200 hover:bg-slate-50 hover:border-slate-300') }}">
                                            <input type="radio" wire:model="jawaban.{{ $q->id }}" value="{{ $opt->id }}" {{ $sudahSubmit ? 'disabled' : '' }} class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 shrink-0">
                                            <span class="font-extrabold w-5 text-slate-500">{{ $opt->label }}.</span>
                                            <span class="flex-1">{{ $opt->teks }}</span>

                                            @if($sudahSubmit)
                                                @if($isCorrect)
                                                    <span class="text-[10px] bg-emerald-600 text-white font-bold px-2.5 py-0.5 rounded-full shrink-0">
                                                        Kunci Benar ✓
                                                    </span>
                                                @elseif($isSelected)
                                                    <span class="text-[10px] bg-rose-600 text-white font-bold px-2.5 py-0.5 rounded-full shrink-0">
                                                        Pilihanmu ✗
                                                    </span>
                                                @endif
                                            @endif
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Pembahasan Jika Selesai -->
                                @if($sudahSubmit && !empty($q->penjelasan))
                                    <div class="pt-3 border-t border-slate-100 text-xs text-slate-600 bg-slate-50/70 p-3.5 rounded-xl space-y-1">
                                        <p class="font-bold text-slate-800">💡 Pembahasan:</p>
                                        <p class="leading-relaxed">{{ $q->penjelasan }}</p>
                                    </div>
                                @endif

                            <!-- 2. SOAL ESSAY / URAIAN (MANUAL DINILAI GURU) -->
                            @elseif ($q->tipe === 'essay')
                                <div class="space-y-3">
                                    @if (!$sudahSubmit)
                                        <textarea wire:model="jawaban.{{ $q->id }}" rows="4" placeholder="Tuliskan jawaban essay Anda di sini secara lengkap..." class="w-full text-xs sm:text-sm rounded-2xl border border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 p-4 leading-relaxed"></textarea>
                                    @else
                                        <!-- Kotak Jawaban yang Telah Dikirim Siswa -->
                                        <div class="space-y-1">
                                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Jawaban Essay Anda:</span>
                                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm text-slate-800 whitespace-pre-line leading-relaxed font-normal">
                                                {{ $jawaban[$q->id] ?? 'Anda tidak mengisi jawaban pada soal ini.' }}
                                            </div>
                                        </div>

                                        <!-- Status Penilaian & Catatan Guru untuk Essay Ini -->
                                        @if (isset($penilaianEssay[$q->id]) && $penilaianEssay[$q->id] !== null)
                                            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-950 space-y-1">
                                                <div class="flex items-center justify-between font-bold">
                                                    <span>✓ Dinilai oleh Guru:</span>
                                                    <span class="text-sm font-black text-emerald-700">{{ $penilaianEssay[$q->id] }} / {{ $q->bobot }} Poin</span>
                                                </div>
                                                @if (!empty($feedbackEssay[$q->id]))
                                                    <p class="text-emerald-800 pt-1 border-t border-emerald-200/60 leading-relaxed">
                                                        <b>Catatan Guru:</b> {{ $feedbackEssay[$q->id] }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-center gap-2 font-medium">
                                                <span>⏳</span>
                                                <span>Jawaban essay Anda telah tersimpan dan sedang menunggu penilaian manual dari guru.</span>
                                            </div>
                                        @endif

                                        @if(!empty($q->penjelasan))
                                            <div class="pt-3 border-t border-slate-100 text-xs text-slate-600 bg-slate-50/70 p-3.5 rounded-xl space-y-1">
                                                <p class="font-bold text-slate-800">💡 Panduan Jawaban / Referensi:</p>
                                                <p class="leading-relaxed">{{ $q->penjelasan }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Tombol Submit Bawah -->
                    @if(!$sudahSubmit)
                        <div class="pt-4 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-slate-100">
                            <a href="{{ route('siswa.kelas.show', $quiz->chapter->class_id) }}" wire:navigate class="w-full sm:w-auto text-center px-5 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                Batal
                            </a>
                            <button type="submit" wire:confirm="Yakin ingin mengumpulkan jawaban kuis ini?" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm px-6 py-3.5 rounded-2xl shadow-lg shadow-emerald-200 transition transform active:scale-95 cursor-pointer">
                                🚀 Kumpulkan Jawaban Kuis
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>
</div>
