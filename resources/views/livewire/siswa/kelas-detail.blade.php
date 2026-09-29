<?php

use App\Models\Kelas;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $kelasId;
    public string $activeTab = 'silabus'; // silabus, tugas, nilai

    // State Pengumpulan Tugas
    public bool $showSubmitModal = false;
    public ?int $selectedAssignmentId = null;
    public ?Assignment $selectedAssignment = null;
    public $fileJawaban = null;
    public string $linkTugas = '';
    public string $catatanSiswa = '';

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

    public function openSubmitModal(int $assignmentId): void
    {
        $this->selectedAssignmentId = $assignmentId;
        $this->selectedAssignment = Assignment::with('chapter')->findOrFail($assignmentId);
        
        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('user_id', auth()->id())
            ->first();

        if ($submission) {
            $this->linkTugas = $submission->link_tugas ?? '';
            $this->catatanSiswa = $submission->catatan_siswa ?? '';
        } else {
            $this->linkTugas = '';
            $this->catatanSiswa = '';
        }

        $this->fileJawaban = null;
        $this->showSubmitModal = true;
    }

    public function submitTugas(): void
    {
        $this->validate([
            'fileJawaban' => 'nullable|file|max:20480', // Maks 20MB
            'linkTugas' => 'nullable|url|max:500',
            'catatanSiswa' => 'nullable|string|max:2000',
        ], [
            'fileJawaban.max' => 'Ukuran berkas jawaban maksimal 20 MB.',
            'linkTugas.url' => 'Format tautan tugas harus URL valid (misal https://...).',
        ]);

        if (empty($this->linkTugas) && empty($this->fileJawaban) && empty($this->catatanSiswa)) {
            $this->addError('fileJawaban', 'Harap unggah berkas, cantumkan tautan, atau tulis catatan jawaban.');
            return;
        }

        $filePath = null;
        if ($this->fileJawaban) {
            $filePath = $this->fileJawaban->store('submissions', 'public');
        }

        $submission = AssignmentSubmission::where('assignment_id', $this->selectedAssignmentId)
            ->where('user_id', auth()->id())
            ->first();

        $isLate = $this->selectedAssignment->isLewatDeadline();
        $status = $isLate ? 'late' : 'submitted';

        if ($submission) {
            $submission->catatan_siswa = $this->catatanSiswa ?: $submission->catatan_siswa;
            $submission->link_tugas = $this->linkTugas ?: $submission->link_tugas;
            if ($filePath) {
                $submission->file_jawaban = $filePath;
            }
            $submission->submitted_at = now();
            if ($submission->status !== 'graded') {
                $submission->status = $status;
            }
            $submission->save();
        } else {
            AssignmentSubmission::create([
                'assignment_id' => $this->selectedAssignmentId,
                'user_id' => auth()->id(),
                'file_jawaban' => $filePath,
                'link_tugas' => $this->linkTugas,
                'catatan_siswa' => $this->catatanSiswa,
                'submitted_at' => now(),
                'status' => $status,
            ]);
        }

        $this->showSubmitModal = false;
        $this->fileJawaban = null;
        session()->flash('success', 'Tugas berhasil dikumpulkan.');
        $this->dispatch('notify', message: 'Tugas berhasil dikumpulkan', type: 'success');
    }

    public function toggleMaterialStatus(int $matId): void
    {
        $userId = auth()->id();
        $progress = Progress::where('user_id', $userId)
            ->where('material_id', $matId)
            ->first();

        if ($progress) {
            $progress->status_selesai = !$progress->status_selesai;
            $progress->selesai_at = $progress->status_selesai ? now() : null;
            $progress->save();
        } else {
            Progress::create([
                'user_id' => $userId,
                'material_id' => $matId,
                'status_selesai' => true,
                'selesai_at' => now(),
            ]);
        }
    }

    public function with(): array
    {
        $userId = auth()->id();

        $kelas = Kelas::with([
            'guru',
            'chapters.materials',
            'chapters.quizzes',
            'chapters.assignments.submissions' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            }
        ])->findOrFail($this->kelasId);

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

        // Ambil semua submission tugas siswa di kelas ini
        $allAssignments = $kelas->chapters->flatMap->assignments;
        $assignmentIds = $allAssignments->pluck('id')->toArray();
        $mySubmissions = AssignmentSubmission::with('assignment.chapter')
            ->where('user_id', $userId)
            ->whereIn('assignment_id', $assignmentIds)
            ->get()
            ->keyBy('assignment_id');

        // Statistik Nilai Siswa
        $gradedAssignments = $mySubmissions->filter(fn($s) => $s->status === 'graded' && $s->nilai !== null);
        $avgAssignment = $gradedAssignments->count() > 0 ? round($gradedAssignments->avg('nilai'), 1) : null;

        $myQuizScores = [];
        $totalKuisLulus = 0;
        foreach ($kelas->chapters->flatMap->quizzes as $quiz) {
            $quizBest = $attempts->get($quiz->id)?->sortByDesc('skor')->first();
            if ($quizBest) {
                $myQuizScores[] = $quizBest->skor;
                if ($quizBest->skor >= $quiz->kkm) {
                    $totalKuisLulus++;
                }
            }
        }
        $avgQuiz = count($myQuizScores) > 0 ? round(array_sum($myQuizScores) / count($myQuizScores), 1) : null;

        // Nilai Akhir Kumulatif & Predikat
        $validScores = [];
        if ($avgAssignment !== null) $validScores[] = $avgAssignment;
        if ($avgQuiz !== null) $validScores[] = $avgQuiz;
        $nilaiAkhir = count($validScores) > 0 ? round(array_sum($validScores) / count($validScores), 1) : null;

        $predikat = '-';
        $predikatStatus = 'Belum Ada Penilaian';
        if ($nilaiAkhir !== null) {
            if ($nilaiAkhir >= 90) {
                $predikat = 'A';
                $predikatStatus = 'Sangat Memuaskan';
            } elseif ($nilaiAkhir >= 80) {
                $predikat = 'B';
                $predikatStatus = 'Baik';
            } elseif ($nilaiAkhir >= 70) {
                $predikat = 'C';
                $predikatStatus = 'Cukup (Tuntas)';
            } else {
                $predikat = 'D';
                $predikatStatus = 'Perlu Peningkatan';
            }
        }

        return [
            'kelas' => $kelas,
            'completedMaterialIds' => $completedMaterialIds,
            'quizAttempts' => $attempts,
            'progresPersen' => $progresPersen,
            'allMaterialsCount' => $allMaterialsCount,
            'completedCount' => $completedCount,
            'allAssignments' => $allAssignments,
            'mySubmissions' => $mySubmissions,
            'avgAssignment' => $avgAssignment,
            'avgQuiz' => $avgQuiz,
            'nilaiAkhir' => $nilaiAkhir,
            'predikat' => $predikat,
            'predikatStatus' => $predikatStatus,
            'totalKuisLulus' => $totalKuisLulus,
            'totalTugasDinilai' => $gradedAssignments->count(),
        ];
    }
}; ?>

<div class="py-6 sm:py-8 pb-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
        <!-- HEADER KELAS & NAVIGASI TERPADU (Selaras dengan Tampilan Guru)-->
        <!-- ============================================================ -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Info Kelas & Aksi Utama -->
            <div class="p-5 sm:p-6 pb-4 sm:pb-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1.5 min-w-0">
                        <div>
                            <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 hover:text-slate-900 text-xs font-bold shadow-xs transition group">
                                <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-700 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                <span>Kembali</span>
                            </a>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                            {{ $kelas->nama }}
                        </h1>
                        @if($kelas->deskripsi)
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-2xl line-clamp-2">
                                {{ $kelas->deskripsi }}
                            </p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2.5 pt-1 text-xs text-slate-500 font-medium">
                            <span>Pengajar: <b class="text-slate-700">{{ $kelas->guru->name ?? 'Guru Pengajar' }}</b></span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-bold text-slate-700">{{ $kelas->chapters->count() }} Bab</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-bold text-slate-700">{{ $allAssignments->count() }} Tugas</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-bold text-slate-700">{{ $kelas->chapters->flatMap->quizzes->count() }} Kuis</span>
                        </div>
                    </div>

                    <!-- Box Kode Kelas Minimalis -->
                    <div class="shrink-0 self-start sm:self-center" x-data="{ copied: false }">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kode:</span>
                            <span class="font-mono font-bold text-indigo-700 select-all cursor-pointer"
                                  @click="window.copyToClipboard('{{ $kelas->kode_kelas }}', 'Kode kelas berhasil disalin'); copied = true; setTimeout(() => copied = false, 2000)"
                                  title="Klik untuk salin">{{ $kelas->kode_kelas }}</span>
                            <button @click="window.copyToClipboard('{{ $kelas->kode_kelas }}', 'Kode kelas berhasil disalin'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="p-1 text-slate-400 hover:text-indigo-600 rounded-md hover:bg-white transition cursor-pointer" title="Salin Kode Kelas">
                                <svg x-show="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <svg x-show="copied" class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigasi Terintegrasi (Responsif & Mobile-friendly) -->
            <div class="px-3 sm:px-6 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between gap-3">
                <nav class="flex items-center gap-1 sm:gap-2 -mb-px overflow-x-auto no-scrollbar py-0.5">
                    <button wire:click="$set('activeTab', 'silabus')" 
                            class="shrink-0 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition cursor-pointer {{ $activeTab === 'silabus' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        Materi Belajar
                    </button>
                    <button wire:click="$set('activeTab', 'nilai')" 
                            class="shrink-0 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition cursor-pointer {{ $activeTab === 'nilai' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        Nilai Saya
                    </button>
                </nav>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 1: SILABUS & MATERI                                      -->
        <!-- ============================================================ -->
        @if($activeTab === 'silabus')
            <div class="space-y-4">
                @if($kelas->chapters->isEmpty())
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-2xs space-y-3">
                        <div class="h-12 w-12 mx-auto rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-slate-800 text-sm">Belum Ada Materi Pembelajaran</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Guru pengajar sedang menyiapkan bahan ajar untuk kelas ini.</p>
                    </div>
                @else
                    @foreach($kelas->chapters as $index => $chapter)
                        <div x-data="{ open: true }" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden transition-all">
                            <!-- Chapter Header (Clickable Dropdown Toggle) -->
                            <div class="px-5 py-3.5 flex items-center justify-between gap-3 cursor-pointer select-none bg-white hover:bg-slate-50/70 transition" 
                                 @click="open = !open">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-slate-500 font-medium">
                                            {{ $chapter->materials->count() }} Materi &bull; {{ $chapter->assignments->count() }} Tugas &bull; {{ $chapter->quizzes->count() }} Kuis
                                        </span>
                                    </div>
                                    <h3 class="font-bold text-sm sm:text-base text-slate-900 truncate mt-0.5">{{ $chapter->judul }}</h3>
                                    @if ($chapter->deskripsi)
                                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $chapter->deskripsi }}</p>
                                    @endif
                                </div>

                                <!-- Panah Dropdown -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs font-semibold text-slate-400 hidden sm:inline" x-text="open ? 'Sembunyikan' : 'Buka'"></span>
                                    <div class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Konten Bab (Collapsible Dropdown) -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-200" 
                                 x-transition:enter-start="opacity-0 -translate-y-1" 
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="border-t border-slate-100 bg-slate-50/60 p-3 sm:p-4 space-y-2">
                                
                                @if($chapter->materials->isEmpty() && $chapter->assignments->isEmpty() && $chapter->quizzes->isEmpty())
                                    <p class="text-xs text-slate-400 italic text-center py-4 bg-white rounded-xl border border-slate-200/80">Belum ada konten pembelajaran di bab ini.</p>
                                @else
                                    <!-- 1. Materi Belajar (Baris Menyamping) -->
                                    @foreach($chapter->materials as $mat)
                                        @php
                                            $isDone = in_array($mat->id, $completedMaterialIds);
                                        @endphp
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 sm:p-3.5 bg-white rounded-xl border border-slate-200/90 hover:border-indigo-300 hover:shadow-2xs transition group">
                                            <!-- Kiri: Ikon + Judul + Info Tipe -->
                                            <div class="flex items-center gap-3 min-w-0">
                                                <button wire:click="toggleMaterialStatus({{ $mat->id }})" wire:loading.attr="disabled" type="button" 
                                                        title="{{ $isDone ? 'Klik untuk tandai belum selesai' : 'Klik untuk tandai sudah dipelajari' }}"
                                                        class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0 transition transform active:scale-95 cursor-pointer {{ $isDone ? 'bg-emerald-50 text-emerald-600 border border-emerald-100 hover:bg-emerald-100' : 'bg-indigo-50 text-indigo-600 border border-indigo-100 hover:bg-indigo-100' }}">
                                                    @if($isDone)
                                                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                    @elseif($mat->tipe === 'video')
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                                                    @elseif($mat->tipe === 'pdf')
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    @else
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                                                    @endif
                                                </button>
                                                <div class="min-w-0">
                                                    <a href="{{ route('siswa.materi.show', $mat->id) }}" wire:navigate 
                                                       class="font-bold text-xs sm:text-sm transition break-words sm:truncate block {{ $isDone ? 'line-through text-slate-400 hover:text-slate-600' : 'text-slate-800 hover:text-indigo-600' }}">
                                                        {{ $mat->judul }}
                                                    </a>
                                                    <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5 font-medium">
                                                        <span class="uppercase text-indigo-600 font-bold text-[10px]">{{ $mat->tipe }}</span>
                                                        <span>&bull;</span>
                                                        <span>Bahan Ajar</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Kanan: Status Selesai + Tombol -->
                                            <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                                @if($isDone)
                                                    <button wire:click="toggleMaterialStatus({{ $mat->id }})" wire:loading.attr="disabled" type="button" 
                                                            title="Klik untuk membatalkan status selesai"
                                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 hover:bg-emerald-100 px-2.5 py-1 rounded-lg cursor-pointer transition transform active:scale-95">
                                                        <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                        <span>Selesai</span>
                                                    </button>
                                                @endif
                                                <a href="{{ route('siswa.materi.show', $mat->id) }}" wire:navigate 
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $isDone ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs' }}">
                                                    <span>{{ $isDone ? 'Ulas Lagi' : 'Pelajari' }}</span>
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- 2. Tugas Latihan (Baris Menyamping) -->
                                    @foreach($chapter->assignments as $asg)
                                        @php
                                            $sub = $mySubmissions->get($asg->id);
                                        @endphp
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 sm:p-3.5 bg-white rounded-xl border border-slate-200/90 hover:border-amber-300 hover:shadow-2xs transition group">
                                            <!-- Kiri: Ikon Tugas + Judul + Info Tenggat -->
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0 {{ $sub ? ($sub->status === 'graded' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-indigo-50 text-indigo-600 border border-indigo-100') : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <h4 class="font-bold text-xs sm:text-sm text-slate-800 hover:text-amber-700 transition break-words sm:truncate block cursor-pointer" wire:click="openSubmitModal({{ $asg->id }})">
                                                        {{ $asg->judul }}
                                                    </h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5 font-medium">
                                                        <span class="text-amber-600 font-bold text-[10px]">TUGAS</span>
                                                        <span>&bull;</span>
                                                        <span>Tenggat: {{ $asg->deadline ? $asg->deadline->translatedFormat('d M Y') : 'Tanpa batas' }}</span>
                                                        @if($asg->poin_maksimal)
                                                            <span>&bull;</span>
                                                            <span>Maks {{ $asg->poin_maksimal }} Poin</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Kanan: Status Nilai + Tombol -->
                                            <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                                @if($sub)
                                                    @if($sub->status === 'late')
                                                        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-lg">
                                                            Terlambat
                                                        </span>
                                                    @elseif($sub->status === 'submitted')
                                                        <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2.5 py-1 rounded-lg">
                                                            Terkumpul
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-lg">
                                                        Belum Kumpul
                                                    </span>
                                                @endif

                                                <button wire:click="openSubmitModal({{ $asg->id }})" 
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer {{ $sub ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-xs' }}">
                                                    <span>{{ $sub ? ($sub->status === 'graded' ? 'Lihat Nilai' : 'Ubah Jawaban') : 'Kumpulkan' }}</span>
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- 3. Kuis Evaluasi (Baris Menyamping) -->
                                    @foreach($chapter->quizzes as $quiz)
                                        @php
                                            $attempt = $quizAttempts->get($quiz->id)?->sortByDesc('skor')->first();
                                            $isPassed = $attempt && $attempt->skor >= $quiz->kkm;
                                        @endphp
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 sm:p-3.5 bg-white rounded-xl border border-slate-200/90 hover:border-purple-300 hover:shadow-2xs transition group">
                                            <!-- Kiri: Ikon Kuis + Judul + Info KKM -->
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="h-9 w-9 rounded-lg flex items-center justify-center shrink-0 {{ $attempt ? ($isPassed ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100') : 'bg-purple-50 text-purple-600 border border-purple-100' }}">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.496m5.007 0a7.454 7.454 0 01-.982-3.172M9.496 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721a1.5 1.5 0 011.5-1.5h10.5a1.5 1.5 0 011.5 1.5v1.515" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <a href="{{ route('siswa.kuis.show', $quiz->id) }}" wire:navigate 
                                                       class="font-bold text-xs sm:text-sm text-slate-800 hover:text-purple-700 transition break-words sm:truncate block">
                                                        {{ $quiz->judul }}
                                                    </a>
                                                    <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5 font-medium">
                                                        <span class="text-purple-600 font-bold text-[10px]">KUIS</span>
                                                        <span>&bull;</span>
                                                        <span>{{ $quiz->durasi_menit }} Menit</span>
                                                        <span>&bull;</span>
                                                        <span>KKM: {{ $quiz->kkm }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Kanan: Tombol Aksi Kuis -->
                                            <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                                <a href="{{ route('siswa.kuis.show', $quiz->id) }}" wire:navigate 
                                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $attempt ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-purple-600 hover:bg-purple-700 text-white shadow-xs' }}">
                                                    <span>{{ $attempt ? 'Ulangi Kuis' : 'Mulai Kuis' }}</span>
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
        
        <!-- ============================================================ -->
        <!-- TAB 2: BUKU NILAI & RAPOR SAYA                              -->
        <!-- ============================================================ -->
        @if($activeTab === 'nilai')
            <div class="space-y-4">
                <!-- 1. Ringkasan Capaian Belajar Siswa (4 Metrik Kompak) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- Nilai Akhir -->
                    <div class="bg-white rounded-xl p-3 sm:p-3.5 border border-slate-200/80 shadow-2xs space-y-1">
                        <span class="text-[11px] font-medium text-slate-500 block">Nilai Akhir</span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                            {{ $nilaiAkhir !== null ? $nilaiAkhir : '-' }}<span class="text-xs text-slate-400 font-normal">/100</span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate">{{ $predikatStatus }}</p>
                    </div>

                    <!-- Rata-rata Tugas -->
                    <div class="bg-white rounded-xl p-3 sm:p-3.5 border border-slate-200/80 shadow-2xs space-y-1">
                        <span class="text-[11px] font-medium text-slate-500 block">Rata-rata Tugas</span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                            {{ $avgAssignment !== null ? $avgAssignment : '-' }}<span class="text-xs text-slate-400 font-normal">/100</span>
                        </div>
                        <p class="text-[11px] text-slate-500">{{ $totalTugasDinilai }}/{{ $allAssignments->count() }} tugas dinilai</p>
                    </div>

                    <!-- Rata-rata Kuis -->
                    <div class="bg-white rounded-xl p-3 sm:p-3.5 border border-slate-200/80 shadow-2xs space-y-1">
                        <span class="text-[11px] font-medium text-slate-500 block">Rata-rata Kuis</span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                            {{ $avgQuiz !== null ? $avgQuiz : '-' }}<span class="text-xs text-slate-400 font-normal">/100</span>
                        </div>
                        <p class="text-[11px] text-slate-500">{{ $totalKuisLulus }}/{{ $kelas->chapters->flatMap->quizzes->count() }} kuis tuntas</p>
                    </div>

                    <!-- Progres Materi -->
                    <div class="bg-white rounded-xl p-3 sm:p-3.5 border border-slate-200/80 shadow-2xs space-y-1">
                        <span class="text-[11px] font-medium text-slate-500 block">Kelengkapan Materi</span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
                            {{ $progresPersen }}%
                        </div>
                        <p class="text-[11px] text-slate-500">{{ $completedCount }}/{{ $allMaterialsCount }} materi selesai</p>
                    </div>
                </div>

                <!-- 2. Rekapitulasi Nilai Tugas -->
                @php
                    $submittedAssignments = $allAssignments->filter(fn($asg) => $mySubmissions->has($asg->id));
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                    <div class="px-4 sm:px-5 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Nilai Tugas Latihan</h3>
                        <span class="text-[11px] font-medium text-slate-500">
                            {{ $totalTugasDinilai }} / {{ $allAssignments->count() }} Selesai Dinilai
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/40 text-slate-400 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-4 sm:px-5 py-2.5">Tugas</th>
                                    <th class="px-4 py-2.5">Status</th>
                                    <th class="px-4 py-2.5 text-center">Nilai</th>
                                    <th class="px-4 sm:px-5 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($submittedAssignments as $asg)
                                    @php
                                        $sub = $mySubmissions->get($asg->id);
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 sm:px-5 py-3">
                                            <div class="font-bold text-slate-800">{{ $asg->judul }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $asg->chapter->judul ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($sub->status === 'graded')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                    Sudah Dinilai
                                                </span>
                                            @elseif($sub->status === 'late')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                    Terlambat
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    Terkumpul
                                                </span>
                                            @endif
                                            @if($sub->submitted_at)
                                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $sub->submitted_at->translatedFormat('d M Y, H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($sub->status === 'graded' && $sub->nilai !== null)
                                                <div class="inline-flex items-baseline gap-0.5 font-bold">
                                                    <span class="text-sm {{ $sub->nilai >= 70 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $sub->nilai }}</span>
                                                    <span class="text-[10px] text-slate-400">/100</span>
                                                </div>
                                            @else
                                                <span class="text-slate-400 text-xs">Menunggu</span>
                                            @endif
                                        </td>
                                        <td class="px-4 sm:px-5 py-3 text-right">
                                            <button wire:click="openSubmitModal({{ $asg->id }})" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer">
                                                <span>Lihat Tugas</span>
                                                <span>&rarr;</span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-400 italic">Belum ada tugas yang dikerjakan. Kerjakan tugas pada tab Materi Belajar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. Rekapitulasi Nilai Kuis -->
                @php
                    $attemptedQuizzes = $kelas->chapters->flatMap->quizzes->filter(function($quiz) use ($quizAttempts) {
                        $attempts = $quizAttempts->get($quiz->id);
                        return $attempts && $attempts->isNotEmpty();
                    });
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                    <div class="px-4 sm:px-5 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Nilai Kuis</h3>
                        <span class="text-[11px] font-medium text-slate-500">
                            {{ $totalKuisLulus }} / {{ $kelas->chapters->flatMap->quizzes->count() }} Kuis Lulus
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/40 text-slate-400 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-4 sm:px-5 py-2.5">Kuis</th>
                                    <th class="px-4 py-2.5">Skor</th>
                                    <th class="px-4 py-2.5 text-center">Status</th>
                                    <th class="px-4 sm:px-5 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($attemptedQuizzes as $quiz)
                                    @php
                                        $quizAttemptsList = $quizAttempts->get($quiz->id) ?? collect();
                                        $att = $quizAttemptsList->sortByDesc('skor')->first();
                                        $passed = $att && $att->skor >= $quiz->kkm;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 sm:px-5 py-3">
                                            <div class="font-bold text-slate-800">{{ $quiz->judul }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $quiz->chapter->judul ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="inline-flex items-baseline gap-0.5 font-bold">
                                                <span class="text-sm {{ $passed ? 'text-emerald-700' : 'text-amber-700' }}">{{ $att->skor }}</span>
                                                <span class="text-[10px] text-slate-400">/100</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($passed)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                    Lulus
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                    Remedial
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 sm:px-5 py-3 text-right">
                                            <a href="{{ route('siswa.kuis.show', $quiz->id) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                                                <span>Lihat Kuis</span>
                                                <span>&rarr;</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-400 italic">Belum ada kuis yang dikerjakan. Kerjakan kuis pada tab Materi Belajar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- ============================================================ -->
    <!-- MODAL PENGUMPULAN TUGAS OLEH SISWA                           -->
    <!-- ============================================================ -->
    @if($showSubmitModal && $selectedAssignment)
        @php
            $existingSub = $mySubmissions->get($selectedAssignment->id);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl border border-slate-100/80 flex flex-col max-h-[92vh] sm:max-h-[85vh]">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 px-4 sm:px-5 pt-4 pb-3 border-b border-slate-100 shrink-0">
                    <div class="min-w-0">
                        <span class="text-amber-600 font-bold text-[10px] uppercase tracking-wide">Tugas</span>
                        <h3 class="font-bold text-sm text-slate-900 leading-snug break-words mt-0.5">{{ $selectedAssignment->judul }}</h3>
                        @if($selectedAssignment->deadline)
                            <p class="text-[11px] text-slate-400 mt-0.5">Tenggat: <span class="{{ $selectedAssignment->isLewatDeadline() ? 'text-rose-500 font-semibold' : 'text-slate-500' }}">{{ $selectedAssignment->deadline->translatedFormat('d M Y, H:i') }}</span></p>
                        @endif
                    </div>
                    <button wire:click="$set('showSubmitModal', false)" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer text-xl leading-none shrink-0" aria-label="Tutup">&times;</button>
                </div>

                {{-- Scrollable Body --}}
                <div class="overflow-y-auto flex-1 px-4 sm:px-5 py-3.5 space-y-3">

                    {{-- Status sudah dikumpulkan / dinilai --}}
                    @if($existingSub && $existingSub->status === 'graded')
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200">
                            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100 shrink-0">
                                <span class="text-lg font-black text-emerald-700">{{ $existingSub->nilai }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-wide">Nilai Kamu</p>
                                @if($existingSub->catatan_guru)
                                    <p class="text-[11px] text-emerald-800 leading-relaxed mt-0.5">{{ $existingSub->catatan_guru }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($existingSub)
                        <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-50 border border-indigo-100">
                            <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-[11px] text-indigo-700 font-semibold">Sudah dikumpulkan — menunggu penilaian</p>
                        </div>
                    @endif

                    {{-- Instruksi Tugas (collapsible) --}}
                    @if($selectedAssignment->deskripsi)
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                    class="flex items-center justify-between w-full text-left text-[11px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">
                                <span>Instruksi Tugas</span>
                                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-transition class="mt-1.5 text-[11px] text-slate-600 bg-slate-50 rounded-xl p-3 border border-slate-100 leading-relaxed">
                                {!! nl2br(e($selectedAssignment->deskripsi)) !!}
                            </div>
                        </div>
                    @endif

                    {{-- Lampiran Soal --}}
                    @if($selectedAssignment->file_lampiran)
                        <a href="{{ asset('storage/' . $selectedAssignment->file_lampiran) }}" target="_blank" download
                           class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition text-[11px] text-slate-700 font-semibold">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Unduh Lampiran Soal
                        </a>
                    @endif

                    {{-- Form --}}
                    <form wire:submit="submitTugas" class="space-y-3" id="form-tugas-siswa">

                        {{-- Upload File --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Berkas Jawaban <span class="font-normal text-slate-400">· PDF, Word, Gambar, ZIP, maks 20MB</span>
                            </label>
                            <input type="file" wire:model="fileJawaban"
                                   class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer border border-slate-200 rounded-xl p-1">
                            <div wire:loading wire:target="fileJawaban" class="flex items-center gap-1 text-[11px] text-indigo-600 font-medium mt-1">
                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                Mengunggah...
                            </div>
                            @error('fileJawaban') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                            @if($existingSub && $existingSub->file_jawaban && !$fileJawaban)
                                <a href="{{ asset('storage/' . $existingSub->file_jawaban) }}" target="_blank"
                                   class="inline-flex items-center gap-1 mt-1 text-[11px] text-indigo-600 hover:underline font-semibold">
                                    Lihat berkas terkirim &nearr;
                                </a>
                            @endif
                        </div>

                        {{-- Tautan --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Tautan Jawaban <span class="font-normal text-slate-400">(opsional)</span>
                            </label>
                            <input type="url" wire:model="linkTugas" placeholder="https://drive.google.com/..."
                                   class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-1 focus:ring-indigo-300 focus:border-indigo-400 text-slate-800 placeholder-slate-400 outline-none transition">
                            @error('linkTugas') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Catatan --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Catatan <span class="font-normal text-slate-400">(opsional)</span>
                            </label>
                            <textarea wire:model="catatanSiswa" rows="2" placeholder="Pesan singkat untuk guru..."
                                      class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 focus:ring-1 focus:ring-indigo-300 focus:border-indigo-400 text-slate-800 placeholder-slate-400 resize-none outline-none transition"></textarea>
                            @error('catatanSiswa') <span class="text-rose-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </form>
                </div>

                {{-- Footer Tombol --}}
                <div class="flex items-center gap-2.5 px-4 sm:px-5 py-3 border-t border-slate-100 shrink-0">
                    <button type="button" wire:click="$set('showSubmitModal', false)"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" form="form-tugas-siswa"
                            class="flex-1 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 active:bg-amber-700 shadow-xs transition active:scale-95 cursor-pointer">
                        {{ $existingSub ? 'Perbarui' : 'Kirim Jawaban' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
