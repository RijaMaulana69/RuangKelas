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
        foreach ($kelas->chapters->flatMap->quizzes as $quiz) {
            $quizBest = $attempts->get($quiz->id)?->sortByDesc('skor')->first();
            if ($quizBest) {
                $myQuizScores[] = $quizBest->skor;
            }
        }
        $avgQuiz = count($myQuizScores) > 0 ? round(array_sum($myQuizScores) / count($myQuizScores), 1) : null;

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
        <!-- HEADER & TOMBOL KEMBALI (Profesional & Singkron)              -->
        <!-- ============================================================ -->
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('siswa.kelas.index') }}" wire:navigate 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200/80 text-xs font-bold text-slate-700 hover:text-indigo-600 transition shadow-2xs group cursor-pointer shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:-translate-x-0.5 text-slate-500 group-hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali</span>
            </a>
            <span class="text-xs font-mono font-medium text-slate-400">
                Mata Pelajaran: <span class="font-bold text-slate-700">{{ $kelas->mapel }}</span>
            </span>
        </div>

        <!-- ============================================================ -->
        <!-- INFORMASI KELAS & PROGRES BELAJAR (Sederhana & Ringkas)       -->
        <!-- ============================================================ -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-2xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <!-- Info Kelas Kiri -->
                <div class="space-y-1.5 max-w-xl">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        {{ $kelas->nama }}
                    </h1>
                    <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                        {{ $kelas->deskripsi ?: 'Pelajari bab materi pembelajaran, kumpulkan tugas latihan, dan ikuti kuis evaluasi.' }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 pt-1 font-medium">
                        <span>Pengajar: <b class="text-slate-700">{{ $kelas->guru->name ?? 'Guru Pengajar' }}</b></span>
                        <span class="text-slate-300">&bull;</span>
                        <span><b class="text-slate-700">{{ $kelas->chapters->count() }}</b> Bab</span>
                        <span class="text-slate-300">&bull;</span>
                        <span><b class="text-slate-700">{{ $allAssignments->count() }}</b> Tugas</span>
                        <span class="text-slate-300">&bull;</span>
                        <span><b class="text-slate-700">{{ $kelas->chapters->flatMap->quizzes->count() }}</b> Kuis</span>
                    </div>
                </div>

                <!-- Progres Belajar Ringkas Kanan -->
                <div class="md:w-64 shrink-0 bg-slate-50/80 border border-slate-200/80 rounded-xl p-3.5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600 font-semibold">Progres Belajar</span>
                        <span class="font-black text-indigo-600">{{ $progresPersen }}%</span>
                    </div>
                    <div class="w-full bg-slate-200/80 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $progresPersen }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium">
                        <span>{{ $completedCount }}/{{ $allMaterialsCount }} materi</span>
                        <span>{{ $mySubmissions->count() }}/{{ $allAssignments->count() }} tugas</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB NAVIGASI (Singkron & Bersih)                             -->
        <!-- ============================================================ -->
        <div class="flex items-center gap-2 border-b border-slate-200">
            <button wire:click="$set('activeTab', 'silabus')" 
                    class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'silabus' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                <span>Silabus & Materi</span>
            </button>
            <button wire:click="$set('activeTab', 'tugas')" 
                    class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'tugas' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Tugas Latihan</span>
                @if($allAssignments->count() > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $mySubmissions->count() === $allAssignments->count() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $mySubmissions->count() }}/{{ $allAssignments->count() }}
                    </span>
                @endif
            </button>
            <button wire:click="$set('activeTab', 'nilai')" 
                    class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'nilai' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                <span>Buku Nilai</span>
            </button>
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
                                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Bab {{ $index + 1 }}</span>
                                        <span class="text-slate-300">&bull;</span>
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
                                                       class="font-bold text-xs sm:text-sm transition truncate block {{ $isDone ? 'line-through text-slate-400 hover:text-slate-600' : 'text-slate-800 hover:text-indigo-600' }}">
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
                                                    <h4 class="font-bold text-xs sm:text-sm text-slate-800 hover:text-amber-700 transition truncate block cursor-pointer" wire:click="openSubmitModal({{ $asg->id }})">
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
                                                    @if($sub->status === 'graded')
                                                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-lg">
                                                            Nilai: {{ $sub->nilai }}/100
                                                        </span>
                                                    @elseif($sub->status === 'late')
                                                        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-100 px-2.5 py-1 rounded-lg">
                                                            Terlambat
                                                        </span>
                                                    @else
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
                                                       class="font-bold text-xs sm:text-sm text-slate-800 hover:text-purple-700 transition truncate block">
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

                                            <!-- Kanan: Status Skor + Tombol -->
                                            <div class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                                @if($attempt)
                                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg border {{ $isPassed ? 'text-emerald-700 bg-emerald-50 border-emerald-100' : 'text-amber-700 bg-amber-50 border-amber-100' }}">
                                                        Skor: {{ $attempt->skor }} &bull; {{ $isPassed ? 'Lulus' : 'Remedial' }}
                                                    </span>
                                                @endif

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
        <!-- TAB 2: DAFTAR TUGAS LATIHAN                                  -->
        <!-- ============================================================ -->
        @if($activeTab === 'tugas')
            <div class="space-y-4">
                @if($allAssignments->isEmpty())
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-2xs space-y-3">
                        <div class="h-12 w-12 mx-auto rounded-xl bg-slate-50 border border-slate-200/80 text-slate-400 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-slate-800 text-sm">Tidak Ada Tugas Latihan</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Guru belum memberikan tugas latihan untuk kelas ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3.5">
                        @foreach($allAssignments as $asg)
                            @php
                                $sub = $mySubmissions->get($asg->id);
                            @endphp
                            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:border-slate-300 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span class="px-2 py-0.5 rounded-md font-semibold bg-slate-100 text-slate-600 text-[11px]">
                                                Bab: {{ $asg->chapter->judul ?? '-' }}
                                            </span>
                                            @if($sub)
                                                @if($sub->status === 'graded')
                                                    <span class="px-2 py-0.5 rounded-md font-black bg-emerald-50 text-emerald-700 border border-emerald-100 text-[11px]">
                                                        Nilai: {{ $sub->nilai }}/100
                                                    </span>
                                                @elseif($sub->status === 'late')
                                                    <span class="px-2 py-0.5 rounded-md font-bold bg-amber-50 text-amber-700 border border-amber-100 text-[11px]">
                                                        Terkumpul (Terlambat)
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 text-[11px]">
                                                        Sudah Dikumpulkan
                                                    </span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded-md font-bold bg-amber-50 text-amber-700 border border-amber-100 text-[11px]">
                                                    Belum Mengumpulkan
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-base font-bold text-slate-900">{{ $asg->judul }}</h3>

                                        @if($asg->deskripsi)
                                            <p class="text-xs text-slate-500 leading-relaxed max-w-2xl">{!! nl2br(e($asg->deskripsi)) !!}</p>
                                        @endif

                                        <!-- Lampiran Soal dari Guru jika ada -->
                                        @if($asg->file_lampiran || $asg->url_referensi)
                                            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                                                <span class="text-slate-400 font-semibold text-[11px]">Lampiran Guru:</span>
                                                @if($asg->file_lampiran)
                                                    <a href="{{ asset('storage/' . $asg->file_lampiran) }}" target="_blank" download class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2.5 py-1 rounded-lg">
                                                        <span>Berkas Soal &darr;</span>
                                                    </a>
                                                @endif
                                                @if($asg->url_referensi)
                                                    <a href="{{ $asg->url_referensi }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-600 hover:text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg">
                                                        <span>Tautan Referensi &nearr;</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Feedback dari Guru jika sudah dinilai -->
                                        @if($sub && $sub->status === 'graded' && $sub->catatan_guru)
                                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-0.5">
                                                <div class="font-bold text-slate-800">Catatan Guru:</div>
                                                <p class="text-slate-600 italic">"{{ $sub->catatan_guru }}"</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3 shrink-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                        <div class="text-right text-xs text-slate-400">
                                            <div>Tenggat:</div>
                                            <b class="text-slate-700">{{ $asg->deadline ? $asg->deadline->translatedFormat('d M Y, H:i') : 'Tanpa Batas' }}</b>
                                        </div>

                                        <button wire:click="openSubmitModal({{ $asg->id }})" 
                                                class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer {{ $sub ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                                            {{ $sub ? ($sub->status === 'graded' ? 'Lihat Jawaban' : 'Perbarui Jawaban') : 'Kumpulkan Tugas' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- TAB 3: BUKU NILAI & RAPOR SAYA                              -->
        <!-- ============================================================ -->
        @if($activeTab === 'nilai')
            <div class="space-y-6">
                <!-- Ringkasan Rata-rata Nilai -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Rata-rata Tugas</span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                            {{ $avgAssignment !== null ? $avgAssignment : '-' }}
                            @if($avgAssignment !== null)<span class="text-xs text-slate-400 font-normal">/100</span>@endif
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tugas yang sudah dinilai</p>
                    </div>

                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Rata-rata Kuis</span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                            {{ $avgQuiz !== null ? $avgQuiz : '-' }}
                            @if($avgQuiz !== null)<span class="text-xs text-slate-400 font-normal">/100</span>@endif
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">Skor kuis terbaik</p>
                    </div>

                    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs">
                        <span class="text-xs font-semibold text-slate-500">Materi Selesai</span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                            {{ $progresPersen }}%
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $completedCount }} dari {{ $allMaterialsCount }} materi</p>
                    </div>
                </div>

                <!-- Rekap Nilai Tugas -->
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                    <div class="px-5 sm:px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Rekapitulasi Nilai Tugas</h3>
                        <span class="text-xs text-slate-400 font-medium">{{ $allAssignments->count() }} Tugas Total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3">Nama Tugas & Bab</th>
                                    <th class="px-5 py-3">Waktu Kumpul</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Nilai</th>
                                    <th class="px-5 py-3">Catatan Guru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($allAssignments as $asg)
                                    @php
                                        $sub = $mySubmissions->get($asg->id);
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-slate-800">{{ $asg->judul }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $asg->chapter->judul ?? '-' }}</div>
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-600">
                                            {{ $sub && $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y H:i') : '-' }}
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @if($sub)
                                                @if($sub->status === 'graded')
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Sudah Dinilai</span>
                                                @elseif($sub->status === 'late')
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">Terkumpul Terlambat</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">Menunggu Penilaian</span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-600">Belum Kumpul</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 font-bold text-sm">
                                            @if($sub && $sub->status === 'graded')
                                                <span class="{{ $sub->nilai >= 75 ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    {{ $sub->nilai }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 font-normal">-</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-600 max-w-xs truncate">
                                            {{ $sub?->catatan_guru ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-slate-400 italic">Belum ada tugas latihan di kelas ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Rekap Nilai Kuis -->
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                    <div class="px-5 sm:px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Rekapitulasi Nilai Kuis</h3>
                        <span class="text-xs text-slate-400 font-medium">{{ $kelas->chapters->flatMap->quizzes->count() }} Kuis Total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3">Nama Kuis & Bab</th>
                                    <th class="px-5 py-3">KKM</th>
                                    <th class="px-5 py-3">Skor Terbaik</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($kelas->chapters->flatMap->quizzes as $quiz)
                                    @php
                                        $att = $quizAttempts->get($quiz->id)?->sortByDesc('skor')->first();
                                        $passed = $att && $att->skor >= $quiz->kkm;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-slate-800">{{ $quiz->judul }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $quiz->chapter->judul ?? '-' }}</div>
                                        </td>
                                        <td class="px-5 py-3.5 font-bold text-slate-600">{{ $quiz->kkm }}</td>
                                        <td class="px-5 py-3.5 font-bold text-sm">
                                            @if($att)
                                                <span class="{{ $passed ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    {{ $att->skor }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 font-normal">Belum Dikerjakan</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @if($att)
                                                @if($passed)
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">Lulus KKM</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">Remedial</span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-600">-</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-right">
                                            <a href="{{ route('siswa.kuis.show', $quiz->id) }}" wire:navigate class="font-bold text-indigo-600 hover:text-indigo-800">
                                                {{ $att ? 'Kerjakan Ulang' : 'Mulai Kuis' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-slate-400 italic">Belum ada kuis evaluasi di kelas ini.</td>
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
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full p-6 sm:p-7 space-y-5 shadow-xl border border-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                    <div>
                        <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Form Pengumpulan Tugas</span>
                        <h3 class="text-base sm:text-lg font-black text-slate-900">{{ $selectedAssignment->judul }}</h3>
                    </div>
                    <button wire:click="$set('showSubmitModal', false)" class="text-slate-400 hover:text-slate-600 text-2xl font-bold cursor-pointer leading-none">&times;</button>
                </div>

                <!-- Informasi Soal Tugas -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                    <div class="text-slate-700 leading-relaxed">
                        {!! nl2br(e($selectedAssignment->deskripsi ?: 'Tidak ada instruksi khusus.')) !!}
                    </div>
                    @if($selectedAssignment->file_lampiran)
                        <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-slate-500">Berkas Lampiran Soal:</span>
                            <a href="{{ asset('storage/' . $selectedAssignment->file_lampiran) }}" target="_blank" download class="font-bold text-indigo-600 hover:underline">
                                Unduh Lampiran Soal &darr;
                            </a>
                        </div>
                    @endif
                </div>

                @if($existingSub && $existingSub->status === 'graded')
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-emerald-900">Nilai yang Diperoleh:</span>
                            <span class="text-lg font-black text-emerald-700">{{ $existingSub->nilai }} / 100</span>
                        </div>
                        @if($existingSub->catatan_guru)
                            <div class="text-emerald-800">
                                <b>Catatan Guru:</b> {{ $existingSub->catatan_guru }}
                            </div>
                        @endif
                    </div>
                @endif

                <form wire:submit="submitTugas" class="space-y-4">
                    <!-- Upload File Jawaban -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Berkas Jawaban (PDF / Word / ZIP / Gambar)</label>
                        <input type="file" wire:model="fileJawaban" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-xl p-1.5">
                        <div wire:loading wire:target="fileJawaban" class="text-[11px] text-indigo-600 font-bold mt-1">Mengunggah berkas...</div>
                        @error('fileJawaban') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror

                        @if($existingSub && $existingSub->file_jawaban && !$fileJawaban)
                            <div class="mt-2 text-xs text-slate-500 flex items-center gap-1.5">
                                <span>Berkas yang sudah dikirim:</span>
                                <a href="{{ asset('storage/' . $existingSub->file_jawaban) }}" target="_blank" class="font-bold text-indigo-600 hover:underline">
                                    Lihat Berkas Terkirim &nearr;
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Input Link Tugas Eksternal (Opsional) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Atau Tautan Jawaban Eksternal (Google Drive / GitHub / dll)</label>
                        <input type="url" wire:model="linkTugas" placeholder="https://drive.google.com/..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 text-slate-800">
                        @error('linkTugas') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Catatan / Jawaban Teks -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Jawaban Tambahan / Uraian Jawaban</label>
                        <textarea wire:model="catatanSiswa" rows="3" placeholder="Tuliskan catatan atau penjelasan jawabanmu di sini..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 text-slate-800"></textarea>
                        @error('catatanSiswa') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showSubmitModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                            Tutup
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                            <span>{{ $existingSub ? 'Perbarui Pengumpulan' : 'Kirim Jawaban' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
