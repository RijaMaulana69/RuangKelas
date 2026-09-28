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
        session()->flash('success', 'Tugas berhasil dikumpulkan! Gurumu akan segera memeriksa jawabanmu.');
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

<div class="py-8 pb-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Flash Alert -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 flex items-center justify-between text-xs sm:text-sm font-semibold shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-700 hover:opacity-75 text-lg">&times;</button>
            </div>
        @endif

        <!-- Header Info Kelas -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
            <div class="flex items-center gap-2 mb-2 text-xs">
                <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="font-semibold text-slate-500 hover:text-emerald-600 flex items-center gap-1 transition">
                    &larr; Kelas Saya
                </a>
                <span class="text-slate-300">&bull;</span>
                <span class="px-2.5 py-0.5 rounded font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                    {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $kelas->nama }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-relaxed">
                {{ $kelas->deskripsi ?: 'Pelajari bab, kumpulkan tugas latihan, dan ikuti kuis evaluasi.' }}
            </p>
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 mt-3 pt-3 border-t border-slate-100">
                <span>👨‍🏫 Pengajar: <b class="text-slate-800">{{ $kelas->guru->name ?? 'Guru RuangKelas' }}</b></span>
                <span>&bull;</span>
                <span>📚 {{ $kelas->chapters->count() }} Bab</span>
                <span>&bull;</span>
                <span>📝 {{ $allAssignments->count() }} Tugas</span>
                <span>&bull;</span>
                <span>🎯 {{ $kelas->chapters->flatMap->quizzes->count() }} Kuis</span>
            </div>

            <!-- Progres Bar Pembelajaran -->
            <div class="mt-6 pt-6 border-t border-slate-100 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700">Progres Belajar Materi</span>
                    <span class="font-black text-emerald-600 text-sm">{{ $progresPersen }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progresPersen }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>{{ $completedCount }} dari {{ $allMaterialsCount }} materi selesai dipelajari</span>
                    <span>Tugas: {{ $mySubmissions->count() }}/{{ $allAssignments->count() }} terkumpul</span>
                </div>
            </div>
        </div>

        <!-- Tab Navigasi Terintegrasi -->
        <div class="flex items-center gap-2 border-b border-slate-200">
            <button wire:click="$set('activeTab', 'silabus')" class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'silabus' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <span>📖</span>
                <span>Silabus & Materi</span>
            </button>
            <button wire:click="$set('activeTab', 'tugas')" class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'tugas' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <span>📝</span>
                <span>Tugas Latihan</span>
                @if($allAssignments->count() > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ $mySubmissions->count() === $allAssignments->count() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                        {{ $mySubmissions->count() }}/{{ $allAssignments->count() }}
                    </span>
                @endif
            </button>
            <button wire:click="$set('activeTab', 'nilai')" class="pb-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'nilai' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                <span>📊</span>
                <span>Buku Nilai Saya</span>
            </button>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 1: SILABUS & MATERI                                      -->
        <!-- ============================================================ -->
        @if($activeTab === 'silabus')
            <div class="space-y-5">
                @if($kelas->chapters->isEmpty())
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-xs">
                        <div class="text-4xl mb-2">📖</div>
                        <h3 class="font-bold text-slate-800 text-sm">Belum ada materi pembelajaran</h3>
                        <p class="text-xs text-slate-400 mt-1">Gurumu sedang menyiapkan bahan ajar untuk kelas ini.</p>
                    </div>
                @else
                    @foreach($kelas->chapters as $index => $chapter)
                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                            <!-- Chapter Header -->
                            <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="h-7 w-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-xs">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-sm sm:text-base">{{ $chapter->judul }}</h3>
                                        @if($chapter->deskripsi)
                                            <p class="text-xs text-slate-500">{{ $chapter->deskripsi }}</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs text-slate-400 hidden sm:inline">
                                    {{ $chapter->materials->count() }} Materi &bull; {{ $chapter->assignments->count() }} Tugas &bull; {{ $chapter->quizzes->count() }} Kuis
                                </span>
                            </div>

                            <!-- Chapter Items -->
                            <div class="p-5 divide-y divide-slate-100">
                                @if($chapter->materials->isEmpty() && $chapter->assignments->isEmpty() && $chapter->quizzes->isEmpty())
                                    <p class="text-xs text-slate-400 italic text-center py-3">Belum ada konten di bab ini.</p>
                                @endif

                                <!-- 1. Materi Belajar -->
                                @foreach($chapter->materials as $mat)
                                    @php
                                        $isDone = in_array($mat->id, $completedMaterialIds);
                                    @endphp
                                    <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 {{ $isDone ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
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
                                                    <h4 class="font-semibold text-xs sm:text-sm text-slate-800 {{ $isDone ? 'line-through text-slate-400' : '' }}">
                                                        {{ $mat->judul }}
                                                    </h4>
                                                    @if($isDone)
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            Selesai
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                                    <span class="uppercase font-bold text-emerald-600">{{ $mat->tipe }}</span>
                                                    <span>&bull;</span>
                                                    <span>⏱️ {{ $mat->durasi_menit ?: 10 }} Menit</span>
                                                </div>
                                            </div>
                                        </div>

                                        <a href="{{ route('siswa.materi.show', $mat->id) }}" wire:navigate class="shrink-0 text-xs font-bold px-3.5 py-1.5 rounded-lg transition {{ $isDone ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' }}">
                                            {{ $isDone ? 'Ulas Lagi' : 'Pelajari &rarr;' }}
                                        </a>
                                    </div>
                                @endforeach

                                <!-- 2. Tugas Latihan Terintegrasi di Bab -->
                                @foreach($chapter->assignments as $asg)
                                    @php
                                        $sub = $mySubmissions->get($asg->id);
                                    @endphp
                                    <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4 bg-blue-50/30 -mx-5 px-5 my-1">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 {{ $sub ? ($sub->status === 'graded' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') : 'bg-amber-100 text-amber-700' }}">
                                                📝
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-bold text-xs sm:text-sm text-slate-900">
                                                        {{ $asg->judul }}
                                                    </h4>
                                                    @if($sub)
                                                        @if($sub->status === 'graded')
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800">
                                                                Nilai: {{ $sub->nilai }}/100
                                                            </span>
                                                        @elseif($sub->status === 'late')
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                                Terkumpul (Terlambat)
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                                                Terkumpul (Menunggu Penilaian)
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                            Belum Dikumpulkan
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                                                    <span>Tenggat: {{ $asg->deadline ? $asg->deadline->translatedFormat('d M Y, H:i') : 'Tanpa Batas Waktu' }}</span>
                                                    @if($asg->poin_maksimal)
                                                        <span>&bull;</span>
                                                        <span>Maks {{ $asg->poin_maksimal }} Poin</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <button wire:click="openSubmitModal({{ $asg->id }})" class="shrink-0 text-xs font-bold px-3.5 py-1.5 rounded-lg transition {{ $sub ? 'bg-blue-100 hover:bg-blue-200 text-blue-800' : 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs' }}">
                                            {{ $sub ? ($sub->status === 'graded' ? 'Lihat Jawaban' : 'Ubah Jawaban') : 'Kumpulkan &rarr;' }}
                                        </button>
                                    </div>
                                @endforeach

                                <!-- 3. Kuis Evaluasi -->
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
                                                    <h4 class="font-bold text-xs sm:text-sm text-slate-900">
                                                        {{ $quiz->judul }}
                                                    </h4>
                                                    @if($attempt)
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isPassed ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                                            Nilai: {{ $attempt->skor }} (KKM: {{ $quiz->kkm }}) {{ $isPassed ? '✓ Lulus' : 'Belum Lulus' }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
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
        @endif

        <!-- ============================================================ -->
        <!-- TAB 2: DAFTAR TUGAS LATIHAN                                  -->
        <!-- ============================================================ -->
        @if($activeTab === 'tugas')
            <div class="space-y-4">
                @if($allAssignments->isEmpty())
                    <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-xs">
                        <div class="text-4xl mb-2">🎉</div>
                        <h3 class="font-bold text-slate-800 text-sm">Tidak ada tugas aktif</h3>
                        <p class="text-xs text-slate-400 mt-1">Gurumu belum memberikan tugas latihan untuk kelas ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($allAssignments as $asg)
                            @php
                                $sub = $mySubmissions->get($asg->id);
                            @endphp
                            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs hover:shadow-sm transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span class="px-2 py-0.5 rounded font-bold bg-slate-100 text-slate-600">
                                                Bab: {{ $asg->chapter->judul ?? '-' }}
                                            </span>
                                            @if($sub)
                                                @if($sub->status === 'graded')
                                                    <span class="px-2 py-0.5 rounded font-black bg-emerald-100 text-emerald-800">
                                                        Nilai: {{ $sub->nilai }}/100
                                                    </span>
                                                @elseif($sub->status === 'late')
                                                    <span class="px-2 py-0.5 rounded font-bold bg-amber-100 text-amber-800">
                                                        Terkumpul (Terlambat)
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded font-bold bg-blue-100 text-blue-800">
                                                        Sudah Dikumpulkan
                                                    </span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded font-bold bg-amber-100 text-amber-800">
                                                    Belum Mengumpulkan
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="text-base font-bold text-slate-900">{{ $asg->judul }}</h3>

                                        @if($asg->deskripsi)
                                            <p class="text-xs text-slate-600 leading-relaxed max-w-2xl">{!! nl2br(e($asg->deskripsi)) !!}</p>
                                        @endif

                                        <!-- Lampiran Soal dari Guru jika ada -->
                                        @if($asg->file_lampiran || $asg->url_referensi)
                                            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                                                <span class="text-slate-400 font-semibold">Lampiran Guru:</span>
                                                @if($asg->file_lampiran)
                                                    <a href="{{ asset('storage/' . $asg->file_lampiran) }}" target="_blank" download class="inline-flex items-center gap-1 font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2.5 py-1 rounded-lg">
                                                        <span>📎 Berkas Soal</span>
                                                    </a>
                                                @endif
                                                @if($asg->url_referensi)
                                                    <a href="{{ $asg->url_referensi }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-emerald-600 hover:text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg">
                                                        <span>🔗 Tautan Panduan &nearr;</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Feedback dari Guru jika sudah dinilai -->
                                        @if($sub && $sub->status === 'graded' && $sub->catatan_guru)
                                            <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200/80 text-xs space-y-1">
                                                <div class="font-bold text-emerald-900 flex items-center gap-1">
                                                    <span>💬 Catatan Umpan Balik Guru:</span>
                                                </div>
                                                <p class="text-emerald-800 italic">"{{ $sub->catatan_guru }}"</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3 shrink-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                        <div class="text-right text-xs text-slate-400">
                                            <div>Tenggat:</div>
                                            <b class="text-slate-700">{{ $asg->deadline ? $asg->deadline->translatedFormat('d M Y, H:i') : 'Tanpa Batas' }}</b>
                                        </div>

                                        <button wire:click="openSubmitModal({{ $asg->id }})" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ $sub ? ($sub->status === 'graded' ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-blue-600 text-white hover:bg-blue-700') : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                                            {{ $sub ? ($sub->status === 'graded' ? 'Lihat Jawaban' : 'Perbarui Pengumpulan') : 'Kumpulkan Tugas' }}
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
                <!-- Ringkasan Rata-rata -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rata-rata Tugas</span>
                        <div class="text-2xl font-black text-blue-600 mt-1">
                            {{ $avgAssignment !== null ? $avgAssignment : '-' }}
                            @if($avgAssignment !== null)<span class="text-xs text-slate-400 font-normal">/100</span>@endif
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Dari tugas yang sudah dinilai guru</p>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rata-rata Kuis</span>
                        <div class="text-2xl font-black text-purple-600 mt-1">
                            {{ $avgQuiz !== null ? $avgQuiz : '-' }}
                            @if($avgQuiz !== null)<span class="text-xs text-slate-400 font-normal">/100</span>@endif
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Skor kuis terbaikmu</p>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Materi Terselesaikan</span>
                        <div class="text-2xl font-black text-emerald-600 mt-1">
                            {{ $progresPersen }}%
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $completedCount }} dari {{ $allMaterialsCount }} materi tuntas</p>
                    </div>
                </div>

                <!-- Rekap Nilai Tugas -->
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Rekapitulasi Nilai Tugas</h3>
                        <span class="text-xs text-slate-400">{{ $allAssignments->count() }} Tugas Total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-6 py-3">Nama Tugas & Bab</th>
                                    <th class="px-6 py-3">Waktu Kumpul</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Nilai</th>
                                    <th class="px-6 py-3">Catatan Guru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($allAssignments as $asg)
                                    @php
                                        $sub = $mySubmissions->get($asg->id);
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800">{{ $asg->judul }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $asg->chapter->judul ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            {{ $sub && $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y H:i') : '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($sub)
                                                @if($sub->status === 'graded')
                                                    <span class="px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800">Sudah Dinilai</span>
                                                @elseif($sub->status === 'late')
                                                    <span class="px-2 py-0.5 rounded font-bold bg-amber-100 text-amber-800">Terkumpul Terlambat</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded font-bold bg-blue-100 text-blue-800">Menunggu Penilaian</span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded font-bold bg-rose-100 text-rose-800">Belum Kumpul</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-black text-sm">
                                            @if($sub && $sub->status === 'graded')
                                                <span class="{{ $sub->nilai >= 75 ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    {{ $sub->nilai }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 font-normal">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 max-w-xs truncate">
                                            {{ $sub?->catatan_guru ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">Belum ada tugas latihan di kelas ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Rekap Nilai Kuis -->
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-900 text-sm">Rekapitulasi Nilai Kuis</h3>
                        <span class="text-xs text-slate-400">{{ $kelas->chapters->flatMap->quizzes->count() }} Kuis Total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-6 py-3">Nama Kuis & Bab</th>
                                    <th class="px-6 py-3">KKM</th>
                                    <th class="px-6 py-3">Skor Terbaik</th>
                                    <th class="px-6 py-3">Status Kelulusan</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($kelas->chapters->flatMap->quizzes as $quiz)
                                    @php
                                        $att = $quizAttempts->get($quiz->id)?->sortByDesc('skor')->first();
                                        $passed = $att && $att->skor >= $quiz->kkm;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800">{{ $quiz->judul }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $quiz->chapter->judul ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-slate-600">{{ $quiz->kkm }}</td>
                                        <td class="px-6 py-4 font-black text-sm">
                                            @if($att)
                                                <span class="{{ $passed ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    {{ $att->skor }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 font-normal">Belum Dikerjakan</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($att)
                                                @if($passed)
                                                    <span class="px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800">Lulus KKM</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded font-bold bg-amber-100 text-amber-800">Remedial / Belum Lulus</span>
                                                @endif
                                            @else
                                                <span class="px-2 py-0.5 rounded font-bold bg-slate-100 text-slate-600">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('siswa.kuis.show', $quiz->id) }}" wire:navigate class="font-bold text-purple-600 hover:text-purple-800 underline">
                                                {{ $att ? 'Kerjakan Ulang' : 'Mulai' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">Belum ada kuis evaluasi di kelas ini.</td>
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
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Form Pengumpulan Tugas</span>
                        <h3 class="text-lg font-black text-slate-900">{{ $selectedAssignment->judul }}</h3>
                    </div>
                    <button wire:click="$set('showSubmitModal', false)" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
                </div>

                <!-- Informasi Soal Tugas -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                    <div class="text-slate-700 leading-relaxed font-medium">
                        {!! nl2br(e($selectedAssignment->deskripsi ?: 'Tidak ada deskripsi tambahan.')) !!}
                    </div>
                    @if($selectedAssignment->file_lampiran)
                        <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-slate-500">Berkas Lampiran Soal:</span>
                            <a href="{{ asset('storage/' . $selectedAssignment->file_lampiran) }}" target="_blank" download class="font-bold text-blue-600 hover:underline">
                                ⬇️ Unduh Berkas Soal
                            </a>
                        </div>
                    @endif
                </div>

                @if($existingSub && $existingSub->status === 'graded')
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-emerald-900">Nilai yang Diperoleh:</span>
                            <span class="text-xl font-black text-emerald-700">{{ $existingSub->nilai }} / 100</span>
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
                        <input type="file" wire:model="fileJawaban" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl p-1.5">
                        <div wire:loading wire:target="fileJawaban" class="text-[11px] text-blue-600 font-bold mt-1">Mengunggah berkas...</div>
                        @error('fileJawaban') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror

                        @if($existingSub && $existingSub->file_jawaban && !$fileJawaban)
                            <div class="mt-2 text-xs text-slate-500 flex items-center gap-1.5">
                                <span>Berkas yang sudah dikirim:</span>
                                <a href="{{ asset('storage/' . $existingSub->file_jawaban) }}" target="_blank" class="font-bold text-blue-600 hover:underline">
                                    Lihat Berkas Terkirim &nearr;
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Input Link Tugas Eksternal (Opsional) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Atau Tautan Jawaban Eksternal (Google Drive / GitHub / YouTube / dll)</label>
                        <input type="url" wire:model="linkTugas" placeholder="https://drive.google.com/..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        @error('linkTugas') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Catatan / Jawaban Teks -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Jawaban Tambahan / Uraian Siswa</label>
                        <textarea wire:model="catatanSiswa" rows="3" placeholder="Tuliskan keterangan jawabanmu di sini..." class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
                        @error('catatanSiswa') <span class="text-rose-500 text-[11px] font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="$set('showSubmitModal', false)" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                            Tutup
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2">
                            <span>🚀</span>
                            <span>{{ $existingSub ? 'Perbarui Pengumpulan' : 'Kirimkan Tugas Sekarang' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
