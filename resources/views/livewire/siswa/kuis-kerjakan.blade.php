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

<div class="py-5 sm:py-6 pb-20 md:pb-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">
        <!-- Top Navigasi -->
        <div class="flex items-center justify-between">
            <a href="{{ route('siswa.kelas.show', $quiz->chapter->class_id) }}" wire:navigate 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali</span>
            </a>

            @if($sudahSubmit && $attemptTerakhir)
                <button wire:click="mulaiUlang" type="button" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition cursor-pointer">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Ulangi Kuis</span>
                </button>
            @endif
        </div>

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs shadow-2xs transition">
                <div class="flex items-center gap-2 font-medium">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-sm leading-none">&times;</button>
            </div>
        @endif

        <!-- Card Utama Kuis -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/90 shadow-2xs space-y-4">
            <!-- Header Kuis -->
            <div class="border-b border-slate-100 pb-3.5 space-y-1">
                <div class="text-xs text-slate-400 font-medium">
                    <span>{{ $quiz->chapter->judul }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
                    {{ $quiz->judul }}
                </h1>
                @if($quiz->deskripsi)
                    <p class="text-xs text-slate-500 leading-relaxed">{{ $quiz->deskripsi }}</p>
                @endif
            </div>

            <!-- Strip Ringkasan Hasil Nilai (Jika Sudah Submit) -->
            @if($sudahSubmit && $attemptTerakhir)
                @php
                    $isPassed = $attemptTerakhir->skor >= $quiz->kkm;
                    $hasEssay = $quiz->questions->where('tipe', 'essay')->count() > 0;
                @endphp
                <div class="p-3 rounded-xl {{ $isPassed ? 'bg-emerald-50/70 border border-emerald-200/70' : 'bg-amber-50/70 border border-amber-200/70' }} space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold {{ $isPassed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $isPassed ? 'Lulus' : 'Belum Lulus' }}
                            </span>
                            <span class="text-slate-700 font-medium">
                                Skor: <b class="text-sm {{ $isPassed ? 'text-emerald-700' : 'text-amber-700' }}">{{ $attemptTerakhir->skor }}</b> / 100
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Benar: <b class="text-slate-700">{{ $attemptTerakhir->total_benar }}</b> &bull; Salah: <b class="text-slate-700">{{ $attemptTerakhir->total_salah }}</b>
                        </div>
                    </div>
                    @if ($hasEssay)
                        <div class="pt-1.5 border-t border-slate-200/60 text-[11px] text-slate-500">
                            Catatan: Nilai di atas evaluasi pilihan ganda. Nilai essay akan diperbarui setelah diperiksa guru.
                        </div>
                    @endif
                </div>
            @endif

            <!-- List Soal Kuis -->
            @if($quiz->questions->isEmpty())
                <div class="text-center py-8 text-slate-400 text-xs">
                    Kuis ini belum memiliki butir soal.
                </div>
            @else
                <form wire:submit="kumpulkanJawaban" class="space-y-4">
                    @php
                        $penilaianEssay = $attemptTerakhir?->jawaban['penilaian_essay'] ?? [];
                        $feedbackEssay = $attemptTerakhir?->jawaban['feedback_essay'] ?? [];
                    @endphp

                    <div class="space-y-3.5">
                        @foreach($quiz->questions as $index => $q)
                            <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50/60 border border-slate-200/70 space-y-2.5">
                                <!-- Pertanyaan Soal -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="text-xs sm:text-sm font-semibold text-slate-800 leading-snug">
                                        <span class="text-indigo-600 font-bold mr-1">{{ $index + 1 }}.</span>
                                        {{ $q->pertanyaan }}
                                    </div>
                                    <span class="text-[10px] font-medium text-slate-400 shrink-0">
                                        {{ $q->bobot }} Poin
                                    </span>
                                </div>

                                <!-- 1. SOAL PILIHAN GANDA -->
                                @if ($q->tipe === 'pilihan_ganda')
                                    @php
                                        $selectedOpt = $jawaban[$q->id] ?? null;
                                        $correctOpt = $q->options->where('is_benar', true)->first();
                                    @endphp
                                    <div class="grid grid-cols-1 gap-1.5 text-xs">
                                        @foreach($q->options as $opt)
                                            @php
                                                $isSelected = ($selectedOpt == $opt->id);
                                                $isCorrect = $opt->is_benar;
                                            @endphp
                                            <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border transition cursor-pointer {{ $sudahSubmit ? ($isCorrect ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-medium' : ($isSelected ? 'bg-rose-50 border-rose-300 text-rose-950' : 'bg-white border-slate-200/70 text-slate-500 opacity-70')) : ($isSelected ? 'bg-indigo-50 border-indigo-300 text-indigo-950 font-medium' : 'bg-white border-slate-200/70 hover:bg-slate-50 text-slate-700') }}">
                                                <input type="radio" wire:model="jawaban.{{ $q->id }}" value="{{ $opt->id }}" {{ $sudahSubmit ? 'disabled' : '' }} class="h-3.5 w-3.5 text-indigo-600 focus:ring-indigo-500 shrink-0">
                                                <span class="font-bold text-slate-400 w-3">{{ $opt->label }}.</span>
                                                <span class="flex-1">{{ $opt->teks }}</span>

                                                @if($sudahSubmit)
                                                    @if($isCorrect)
                                                        <span class="text-[10px] text-emerald-700 font-bold shrink-0">
                                                            Kunci Benar ✓
                                                        </span>
                                                    @elseif($isSelected)
                                                        <span class="text-[10px] text-rose-600 font-bold shrink-0">
                                                            Pilihanmu ✗
                                                        </span>
                                                    @endif
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>

                                <!-- 2. SOAL ESSAY / URAIAN -->
                                @elseif ($q->tipe === 'essay')
                                    <div class="space-y-2 text-xs">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700">Soal Essay</span>
                                        @if (!$sudahSubmit)
                                            <textarea wire:model="jawaban.{{ $q->id }}" rows="3" placeholder="Tuliskan jawaban essay Anda..." class="w-full text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-2.5 leading-relaxed placeholder-slate-400 resize-none"></textarea>
                                        @else
                                            <div class="p-2.5 rounded-lg bg-white border border-slate-200 text-slate-800 whitespace-pre-line leading-relaxed">
                                                <span class="text-[10px] font-semibold text-slate-400 block mb-0.5">Jawaban Anda:</span>
                                                {{ $jawaban[$q->id] ?? 'Tidak mengisi jawaban.' }}
                                            </div>

                                            @if (isset($penilaianEssay[$q->id]) && $penilaianEssay[$q->id] !== null)
                                                <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-950 space-y-1">
                                                    <div class="flex items-center justify-between font-semibold">
                                                        <span>Nilai Guru:</span>
                                                        <span class="font-bold text-emerald-700">{{ $penilaianEssay[$q->id] }} / {{ $q->bobot }} Poin</span>
                                                    </div>
                                                    @if (!empty($feedbackEssay[$q->id]))
                                                        <p class="text-emerald-800 pt-1 border-t border-emerald-200/60 leading-relaxed text-[11px]">
                                                            <b>Catatan:</b> {{ $feedbackEssay[$q->id] }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="p-2 rounded-lg bg-amber-50 text-[11px] text-amber-800 font-medium">
                                                    Menunggu penilaian essay dari guru.
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Tombol Submit Bawah (Hanya jika belum submit) -->
                    @if(!$sudahSubmit)
                        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                            <a href="{{ route('siswa.kelas.show', $quiz->chapter->class_id) }}" wire:navigate class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                                Batal
                            </a>
                            <button type="submit" wire:confirm="Yakin ingin mengumpulkan jawaban kuis ini?" class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold text-xs px-4 py-2 rounded-lg shadow-xs transition cursor-pointer">
                                Kumpulkan Jawaban
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>
</div>
