<?php

use App\Models\Kelas;
use App\Models\Chapter;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\Progress;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithFileUploads;

    public int $kelasId;
    public string $activeTab = 'kurikulum'; // 'kurikulum', 'siswa', atau 'nilai'

    // ============================================================
    // STATE BAB (CHAPTER)
    // ============================================================
    public bool $showChapterModal = false;
    public ?int $chapterId = null;
    #[Validate('required|string|max:150')]
    public string $chapterJudul = '';
    #[Validate('nullable|string')]
    public string $chapterDeskripsi = '';

    // ============================================================
    // STATE MATERI (MATERIAL - PDF & LINK)
    // ============================================================
    public bool $showMaterialModal = false;
    public ?int $targetChapterId = null;
    public ?int $materialId = null;
    #[Validate('required|string|max:150')]
    public string $materialJudul = '';
    #[Validate('required|in:teks,video,pdf,link')]
    public string $materialTipe = 'teks';
    public string $materialKonten = '';
    public string $materialUrl = '';
    public $materialFile = null;
    public ?int $materialDurasi = 15;

    // ============================================================
    // STATE TUGAS LATIHAN (ASSIGNMENT)
    // ============================================================
    public bool $showAssignmentModal = false;
    public ?int $assignmentId = null;
    #[Validate('required|string|max:150')]
    public string $assignmentJudul = '';
    public string $assignmentDeskripsi = '';
    public $assignmentFile = null;
    public string $assignmentUrl = '';
    public ?string $assignmentDeadline = null;
    public int $assignmentPoin = 100;

    // ============================================================
    // STATE PENILAIAN TUGAS (GRADING MODAL)
    // ============================================================
    public bool $showGradingModal = false;
    public ?int $selectedAssignmentId = null;
    public ?Assignment $selectedAssignment = null;
    public ?int $gradingSubmissionId = null;
    public ?int $inputNilai = null;
    public string $inputCatatanGuru = '';

    // ============================================================
    // STATE KUIS (QUIZ)
    // ============================================================
    public bool $showQuizModal = false;
    public ?int $quizId = null;
    #[Validate('required|string|max:150')]
    public string $quizJudul = '';
    public string $quizDeskripsi = '';
    public int $quizDurasi = 30;
    public int $quizKkm = 70;
    public bool $quizAcak = false;

    // ============================================================
    // STATE SOAL KUIS (QUESTIONS)
    // ============================================================
    public bool $showQuestionModal = false;
    public ?int $selectedQuizId = null;
    public string $selectedQuizJudul = '';
    public ?int $questionId = null;
    public string $soalTeks = '';
    public int $soalBobot = 10;
    public string $soalPenjelasan = '';
    public array $soalOptions = [
        ['label' => 'A', 'teks' => '', 'is_benar' => true],
        ['label' => 'B', 'teks' => '', 'is_benar' => false],
        ['label' => 'C', 'teks' => '', 'is_benar' => false],
        ['label' => 'D', 'teks' => '', 'is_benar' => false],
    ];

    public function mount(int $kelas): void
    {
        $this->kelasId = $kelas;
        // Pastikan kelas milik guru ini
        Kelas::where('guru_id', auth()->id())->findOrFail($this->kelasId);
    }

    // ------------------------------------------------------------
    // CHAPTER ACTIONS
    // ------------------------------------------------------------
    public function openCreateChapter(): void
    {
        $this->reset(['chapterId', 'chapterJudul', 'chapterDeskripsi']);
        $this->showChapterModal = true;
    }

    public function openEditChapter(int $id): void
    {
        $chapter = Chapter::where('class_id', $this->kelasId)->findOrFail($id);
        $this->chapterId = $chapter->id;
        $this->chapterJudul = $chapter->judul;
        $this->chapterDeskripsi = $chapter->deskripsi ?? '';
        $this->showChapterModal = true;
    }

    public function simpanChapter(): void
    {
        $this->validate([
            'chapterJudul' => 'required|string|max:150',
            'chapterDeskripsi' => 'nullable|string',
        ]);

        if ($this->chapterId) {
            $chapter = Chapter::where('class_id', $this->kelasId)->findOrFail($this->chapterId);
            $chapter->update([
                'judul' => $this->chapterJudul,
                'deskripsi' => $this->chapterDeskripsi,
            ]);
            session()->flash('success', 'Bab berhasil diperbarui!');
        } else {
            $lastOrder = Chapter::where('class_id', $this->kelasId)->max('urutan') ?? 0;
            Chapter::create([
                'class_id' => $this->kelasId,
                'judul' => $this->chapterJudul,
                'deskripsi' => $this->chapterDeskripsi,
                'urutan' => $lastOrder + 1,
            ]);
            session()->flash('success', 'Bab baru berhasil ditambahkan!');
        }

        $this->showChapterModal = false;
    }

    public function hapusChapter(int $id): void
    {
        $chapter = Chapter::where('class_id', $this->kelasId)->findOrFail($id);
        $chapter->delete();
        session()->flash('success', 'Bab beserta materi, tugas, dan kuisnya telah dihapus.');
    }

    // ------------------------------------------------------------
    // MATERIAL ACTIONS (PDF & LINK)
    // ------------------------------------------------------------
    public function openCreateMaterial(int $chapterId): void
    {
        $this->targetChapterId = $chapterId;
        $this->reset(['materialId', 'materialJudul', 'materialKonten', 'materialUrl', 'materialFile']);
        $this->materialTipe = 'teks';
        $this->materialDurasi = 15;
        $this->showMaterialModal = true;
    }

    public function openEditMaterial(int $id): void
    {
        $material = Material::findOrFail($id);
        $this->materialId = $material->id;
        $this->targetChapterId = $material->chapter_id;
        $this->materialJudul = $material->judul;
        $this->materialTipe = $material->tipe;
        $this->materialKonten = $material->konten ?? '';
        $this->materialUrl = $material->url ?? '';
        $this->materialDurasi = $material->durasi_menit ?? 15;
        $this->materialFile = null;
        $this->showMaterialModal = true;
    }

    public function simpanMaterial(): void
    {
        $this->validate([
            'materialJudul' => 'required|string|max:150',
            'materialTipe' => 'required|in:teks,video,pdf,link',
            'materialFile' => 'nullable|file|mimes:pdf|max:20480', // max 20MB PDF
        ]);

        $filePath = null;
        if ($this->materialFile) {
            $filePath = $this->materialFile->store('materials', 'public');
        }

        if ($this->materialId) {
            $mat = Material::findOrFail($this->materialId);
            $updateData = [
                'judul' => $this->materialJudul,
                'tipe' => $this->materialTipe,
                'konten' => $this->materialKonten,
                'url' => $this->materialUrl,
                'durasi_menit' => $this->materialDurasi,
            ];
            if ($filePath) {
                $updateData['file_path'] = $filePath;
            }
            $mat->update($updateData);
            session()->flash('success', 'Materi berhasil diperbarui!');
        } else {
            $lastOrder = Material::where('chapter_id', $this->targetChapterId)->max('urutan') ?? 0;
            Material::create([
                'chapter_id' => $this->targetChapterId,
                'judul' => $this->materialJudul,
                'tipe' => $this->materialTipe,
                'konten' => $this->materialKonten,
                'url' => $this->materialUrl,
                'file_path' => $filePath,
                'durasi_menit' => $this->materialDurasi,
                'urutan' => $lastOrder + 1,
            ]);
            session()->flash('success', 'Materi baru berhasil ditambahkan!');
        }

        $this->showMaterialModal = false;
    }

    public function hapusMaterial(int $id): void
    {
        $mat = Material::findOrFail($id);
        $mat->delete();
        session()->flash('success', 'Materi telah dihapus.');
    }

    // ------------------------------------------------------------
    // ASSIGNMENT ACTIONS (TUGAS LATIHAN)
    // ------------------------------------------------------------
    public function openCreateAssignment(int $chapterId): void
    {
        $this->targetChapterId = $chapterId;
        $this->reset(['assignmentId', 'assignmentJudul', 'assignmentDeskripsi', 'assignmentFile', 'assignmentUrl', 'assignmentDeadline']);
        $this->assignmentPoin = 100;
        $this->showAssignmentModal = true;
    }

    public function openEditAssignment(int $id): void
    {
        $asg = Assignment::findOrFail($id);
        $this->assignmentId = $asg->id;
        $this->targetChapterId = $asg->chapter_id;
        $this->assignmentJudul = $asg->judul;
        $this->assignmentDeskripsi = $asg->deskripsi ?? '';
        $this->assignmentUrl = $asg->url_referensi ?? '';
        $this->assignmentDeadline = $asg->deadline ? $asg->deadline->format('Y-m-d\TH:i') : null;
        $this->assignmentPoin = $asg->poin_maksimal ?? 100;
        $this->assignmentFile = null;
        $this->showAssignmentModal = true;
    }

    public function simpanAssignment(): void
    {
        $this->validate([
            'assignmentJudul' => 'required|string|max:150',
            'assignmentPoin' => 'required|integer|min:1|max:100',
            'assignmentFile' => 'nullable|file|max:20480',
        ]);

        $filePath = null;
        if ($this->assignmentFile) {
            $filePath = $this->assignmentFile->store('assignments', 'public');
        }

        if ($this->assignmentId) {
            $asg = Assignment::findOrFail($this->assignmentId);
            $data = [
                'judul' => $this->assignmentJudul,
                'deskripsi' => $this->assignmentDeskripsi,
                'url_referensi' => $this->assignmentUrl,
                'deadline' => $this->assignmentDeadline ? date('Y-m-d H:i:s', strtotime($this->assignmentDeadline)) : null,
                'poin_maksimal' => $this->assignmentPoin,
            ];
            if ($filePath) {
                $data['file_lampiran'] = $filePath;
            }
            $asg->update($data);
            session()->flash('success', 'Tugas latihan berhasil diperbarui!');
        } else {
            $lastOrder = Assignment::where('chapter_id', $this->targetChapterId)->max('urutan') ?? 0;
            Assignment::create([
                'chapter_id' => $this->targetChapterId,
                'judul' => $this->assignmentJudul,
                'deskripsi' => $this->assignmentDeskripsi,
                'file_lampiran' => $filePath,
                'url_referensi' => $this->assignmentUrl,
                'deadline' => $this->assignmentDeadline ? date('Y-m-d H:i:s', strtotime($this->assignmentDeadline)) : null,
                'poin_maksimal' => $this->assignmentPoin,
                'urutan' => $lastOrder + 1,
            ]);
            session()->flash('success', 'Tugas latihan baru berhasil ditambahkan!');
        }

        $this->showAssignmentModal = false;
    }

    public function hapusAssignment(int $id): void
    {
        $asg = Assignment::findOrFail($id);
        $asg->delete();
        session()->flash('success', 'Tugas latihan telah dihapus.');
    }

    // ------------------------------------------------------------
    // PENILAIAN TUGAS (GRADING SUBMISSIONS)
    // ------------------------------------------------------------
    public function openGrading(int $assignmentId): void
    {
        $this->selectedAssignmentId = $assignmentId;
        $this->selectedAssignment = Assignment::with(['submissions.user'])->findOrFail($assignmentId);
        $this->showGradingModal = true;
    }

    public function pilihSubmission(int $submissionId): void
    {
        $sub = AssignmentSubmission::findOrFail($submissionId);
        $this->gradingSubmissionId = $sub->id;
        $this->inputNilai = $sub->nilai;
        $this->inputCatatanGuru = $sub->catatan_guru ?? '';
    }

    public function simpanNilai(): void
    {
        $this->validate([
            'inputNilai' => 'required|integer|min:0|max:100',
            'inputCatatanGuru' => 'nullable|string',
        ]);

        $sub = AssignmentSubmission::findOrFail($this->gradingSubmissionId);
        $sub->update([
            'nilai' => $this->inputNilai,
            'catatan_guru' => $this->inputCatatanGuru,
            'status' => 'graded',
            'graded_at' => now(),
        ]);

        // Refresh selected assignment
        $this->selectedAssignment = Assignment::with(['submissions.user'])->findOrFail($this->selectedAssignmentId);
        $this->reset(['gradingSubmissionId', 'inputNilai', 'inputCatatanGuru']);
        session()->flash('grading_success', 'Nilai dan evaluasi tugas berhasil disimpan!');
    }

    // ------------------------------------------------------------
    // QUIZ ACTIONS
    // ------------------------------------------------------------
    public function openCreateQuiz(int $chapterId): void
    {
        $this->targetChapterId = $chapterId;
        $this->reset(['quizId', 'quizJudul', 'quizDeskripsi']);
        $this->quizDurasi = 30;
        $this->quizKkm = 70;
        $this->quizAcak = false;
        $this->showQuizModal = true;
    }

    public function openEditQuiz(int $id): void
    {
        $quiz = Quiz::findOrFail($id);
        $this->quizId = $quiz->id;
        $this->targetChapterId = $quiz->chapter_id;
        $this->quizJudul = $quiz->judul;
        $this->quizDeskripsi = $quiz->deskripsi ?? '';
        $this->quizDurasi = $quiz->durasi_menit;
        $this->quizKkm = $quiz->kkm;
        $this->quizAcak = $quiz->acak_soal;
        $this->showQuizModal = true;
    }

    public function simpanQuiz(): void
    {
        $this->validate([
            'quizJudul' => 'required|string|max:150',
            'quizDurasi' => 'required|integer|min:1',
            'quizKkm' => 'required|integer|min:0|max:100',
        ]);

        if ($this->quizId) {
            $q = Quiz::findOrFail($this->quizId);
            $q->update([
                'judul' => $this->quizJudul,
                'deskripsi' => $this->quizDeskripsi,
                'durasi_menit' => $this->quizDurasi,
                'kkm' => $this->quizKkm,
                'acak_soal' => $this->quizAcak,
            ]);
            session()->flash('success', 'Kuis berhasil diperbarui!');
        } else {
            $lastOrder = Quiz::where('chapter_id', $this->targetChapterId)->max('urutan') ?? 0;
            Quiz::create([
                'chapter_id' => $this->targetChapterId,
                'judul' => $this->quizJudul,
                'deskripsi' => $this->quizDeskripsi,
                'durasi_menit' => $this->quizDurasi,
                'kkm' => $this->quizKkm,
                'acak_soal' => $this->quizAcak,
                'urutan' => $lastOrder + 1,
            ]);
            session()->flash('success', 'Kuis baru berhasil ditambahkan!');
        }

        $this->showQuizModal = false;
    }

    public function hapusQuiz(int $id): void
    {
        $q = Quiz::findOrFail($id);
        $q->delete();
        session()->flash('success', 'Kuis telah dihapus.');
    }

    // ------------------------------------------------------------
    // QUESTION ACTIONS (SOAL KUIS)
    // ------------------------------------------------------------
    public function openQuestions(int $quizId): void
    {
        $quiz = Quiz::with('questions.options')->findOrFail($quizId);
        $this->selectedQuizId = $quiz->id;
        $this->selectedQuizJudul = $quiz->judul;
        $this->resetSoalForm();
        $this->showQuestionModal = true;
    }

    public function resetSoalForm(): void
    {
        $this->reset(['questionId', 'soalTeks', 'soalPenjelasan']);
        $this->soalBobot = 10;
        $this->soalOptions = [
            ['label' => 'A', 'teks' => '', 'is_benar' => true],
            ['label' => 'B', 'teks' => '', 'is_benar' => false],
            ['label' => 'C', 'teks' => '', 'is_benar' => false],
            ['label' => 'D', 'teks' => '', 'is_benar' => false],
        ];
    }

    public function setJawabanBenar(int $index): void
    {
        foreach ($this->soalOptions as $i => &$opt) {
            $opt['is_benar'] = ($i === $index);
        }
    }

    public function simpanSoal(): void
    {
        $this->validate([
            'soalTeks' => 'required|string',
            'soalBobot' => 'required|integer|min:1',
            'soalOptions.0.teks' => 'required|string',
            'soalOptions.1.teks' => 'required|string',
        ]);

        $lastOrder = Question::where('quiz_id', $this->selectedQuizId)->max('urutan') ?? 0;

        $question = Question::create([
            'quiz_id' => $this->selectedQuizId,
            'pertanyaan' => $this->soalTeks,
            'tipe' => 'pilihan_ganda',
            'bobot' => $this->soalBobot,
            'penjelasan' => $this->soalPenjelasan,
            'urutan' => $lastOrder + 1,
        ]);

        foreach ($this->soalOptions as $opt) {
            if (!empty(trim($opt['teks']))) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'label' => $opt['label'],
                    'teks' => $opt['teks'],
                    'is_benar' => $opt['is_benar'],
                ]);
            }
        }

        $this->resetSoalForm();
        session()->flash('soal_success', 'Soal berhasil ditambahkan!');
    }

    public function hapusSoal(int $id): void
    {
        $q = Question::findOrFail($id);
        $q->delete();
        session()->flash('soal_success', 'Soal berhasil dihapus.');
    }

    // ------------------------------------------------------------
    // KELUARKAN SISWA (STUDENT MANAGEMENT)
    // ------------------------------------------------------------
    public function keluarkanSiswa(int $userId): void
    {
        Enrollment::where('class_id', $this->kelasId)
            ->where('user_id', $userId)
            ->delete();

        session()->flash('success', 'Siswa berhasil dikeluarkan dari kelas.');
    }

    public function with(): array
    {
        $kelas = Kelas::where('guru_id', auth()->id())
            ->with([
                'chapters.materials',
                'chapters.assignments.submissions',
                'chapters.quizzes.questions.options',
                'chapters.quizzes.attempts',
                'siswa',
            ])
            ->findOrFail($this->kelasId);

        // Kumpulan semua tugas dan kuis untuk Gradebook
        $allAssignments = $kelas->chapters->flatMap->assignments;
        $allQuizzes = $kelas->chapters->flatMap->quizzes;
        $allMaterialIds = $kelas->chapters->flatMap->materials->pluck('id');
        $totalMaterialsCount = $allMaterialIds->count();

        // Hitung statistik tiap siswa
        $siswaList = $kelas->siswa->map(function ($s) use ($allMaterialIds, $totalMaterialsCount, $allAssignments, $allQuizzes) {
            // Progres materi
            $completedCount = Progress::where('user_id', $s->id)
                ->whereIn('material_id', $allMaterialIds)
                ->where('status_selesai', true)
                ->count();

            $s->progres_persen = $totalMaterialsCount > 0 
                ? round(($completedCount / $totalMaterialsCount) * 100) 
                : 0;
            $s->completed_materials = $completedCount;

            // Tugas yang dikerjakan siswa
            $subList = AssignmentSubmission::where('user_id', $s->id)
                ->whereIn('assignment_id', $allAssignments->pluck('id'))
                ->get();
            $s->tugas_selesai = $subList->where('status', 'graded')->count();
            $s->tugas_dikumpulkan = $subList->count();
            $s->rata_rata_tugas = $subList->whereNotNull('nilai')->avg('nilai') ?: 0;

            // Kuis yang dikerjakan siswa
            $quizAttempts = \App\Models\QuizAttempt::where('user_id', $s->id)
                ->whereIn('quiz_id', $allQuizzes->pluck('id'))
                ->where('status', 'selesai')
                ->get();
            $s->kuis_selesai = $quizAttempts->unique('quiz_id')->count();
            $s->rata_rata_kuis = $quizAttempts->avg('skor') ?: 0;

            // Rata-rata akhir gabungan
            $nilaiKomponen = array_filter([$s->rata_rata_tugas, $s->rata_rata_kuis]);
            $s->nilai_akhir = count($nilaiKomponen) > 0 ? round(array_sum($nilaiKomponen) / count($nilaiKomponen)) : 0;

            return $s;
        });

        $activeQuiz = $this->selectedQuizId 
            ? Quiz::with('questions.options')->find($this->selectedQuizId) 
            : null;

        return [
            'kelas' => $kelas,
            'siswaList' => $siswaList,
            'allAssignments' => $allAssignments,
            'allQuizzes' => $allQuizzes,
            'totalMaterialsCount' => $totalMaterialsCount,
            'activeQuiz' => $activeQuiz,
        ];
    }
}; ?>

<div class="py-6 sm:py-8 space-y-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Breadcrumb Navigasi Ramah Guru -->
        <div class="flex items-center justify-between gap-4">
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="{{ route('guru.kelas.index') }}" wire:navigate class="inline-flex items-center gap-1.5 hover:text-indigo-600 transition font-bold">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kelola Kelas</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-800 font-bold truncate max-w-xs sm:max-w-md">{{ $kelas->nama }}</span>
            </nav>

            <span class="text-xs text-slate-400 hidden sm:inline">ID Kelas: #{{ $kelas->id }}</span>
        </div>

        <!-- Flash Alert Notifikasi -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm shadow-xs transition">
                <div class="flex items-center gap-2 font-bold">
                    <span class="text-emerald-600 text-base">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-lg cursor-pointer">&times;</button>
            </div>
        @endif

        <!-- Banner Ringkasan Kelas: Bersih, Rapi & Elegan -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs relative overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                            {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                        </span>
                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold {{ $kelas->aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700' }}">
                            {{ $kelas->aktif ? '● Kelas Aktif' : 'Non-aktif' }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight">
                        {{ $kelas->nama }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-xl">
                        {{ $kelas->deskripsi ?: 'Kelola materi pembelajaran, tugas latihan siswa, kuis evaluasi, dan pantau rapor nilai dalam satu antarmuka praktis.' }}
                    </p>
                </div>

                <!-- Kode Akses Siswa & Tombol Buat Bab -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                    <!-- Kartu Kode Akses Interaktif -->
                    <div x-data="{ copied: false }" class="p-3 bg-slate-50 border border-slate-200/90 rounded-2xl flex items-center justify-between sm:justify-start gap-4 shadow-2xs">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Kode Masuk Siswa</span>
                            <span class="text-base sm:text-lg font-mono font-black text-indigo-600 tracking-wider select-all">{{ $kelas->kode_kelas }}</span>
                        </div>
                        <button @click="navigator.clipboard.writeText('{{ $kelas->kode_kelas }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-white rounded-xl transition shadow-2xs cursor-pointer flex items-center gap-1" 
                                title="Salin Kode Akses Kelas">
                            <span x-show="!copied">📋</span>
                            <span x-show="copied" class="text-xs text-emerald-600 font-bold" style="display: none;">✓ Disalin</span>
                        </button>
                    </div>

                    <button wire:click="openCreateChapter" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold px-5 py-3 rounded-2xl text-xs sm:text-sm shadow-sm shadow-indigo-600/20 transition active:scale-95 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Bab Baru</span>
                    </button>
                </div>
            </div>

            <!-- Ringkasan Statistik Kelas (Visual Bersih & Proporsional) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-6 mt-6 border-t border-slate-100">
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3 flex items-center gap-3">
                    <span class="text-2xl">👥</span>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-slate-400">Siswa Terdaftar</div>
                        <div class="text-base font-black text-slate-800">{{ $siswaList->count() }} Siswa</div>
                    </div>
                </div>
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3 flex items-center gap-3">
                    <span class="text-2xl">📖</span>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-slate-400">Bab Pembelajaran</div>
                        <div class="text-base font-black text-slate-800">{{ $kelas->chapters->count() }} Bab</div>
                    </div>
                </div>
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3 flex items-center gap-3">
                    <span class="text-2xl">📝</span>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-slate-400">Tugas Latihan</div>
                        <div class="text-base font-black text-slate-800">{{ $allAssignments->count() }} Tugas</div>
                    </div>
                </div>
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3 flex items-center gap-3">
                    <span class="text-2xl">🎯</span>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-slate-400">Kuis Evaluasi</div>
                        <div class="text-base font-black text-slate-800">{{ $allQuizzes->count() }} Kuis</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segmented Tab Navigasi: Sangat Mudah Berpindah Antar Fitur -->
        <div class="bg-slate-100/90 p-1.5 rounded-2xl inline-flex flex-wrap items-center gap-1 text-xs sm:text-sm font-bold border border-slate-200/60 shadow-2xs">
            <button wire:click="$set('activeTab', 'kurikulum')" 
                    class="px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'kurikulum' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span>📚</span>
                <span>Materi & Aktivitas</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'kurikulum' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'bg-slate-200 text-slate-600' }}">{{ $kelas->chapters->count() }} Bab</span>
            </button>
            <button wire:click="$set('activeTab', 'siswa')" 
                    class="px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'siswa' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span>👥</span>
                <span>Daftar Siswa</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $activeTab === 'siswa' ? 'bg-indigo-50 text-indigo-700 font-bold' : 'bg-slate-200 text-slate-600' }}">{{ $siswaList->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'nilai')" 
                    class="px-4 py-2 rounded-xl transition flex items-center gap-2 cursor-pointer {{ $activeTab === 'nilai' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                <span>📊</span>
                <span>Buku Nilai (Gradebook)</span>
            </button>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 1: KURIKULUM & MATERI (BAB, MATERI PDF/LINK, TUGAS, KUIS) -->
        <!-- ============================================================ -->
        @if ($activeTab === 'kurikulum')
            <div class="space-y-6">
                @forelse ($kelas->chapters as $index => $chapter)
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden transition hover:border-slate-300">
                        <!-- Header Bab -->
                        <div class="p-5 sm:p-6 bg-slate-50/70 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start sm:items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-sm shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base sm:text-lg text-slate-900 leading-tight">
                                        {{ $chapter->judul }}
                                    </h3>
                                    @if ($chapter->deskripsi)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $chapter->deskripsi }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Tombol Aksi Tambah di Bab (Sangat Jelas & Ramah Guru) -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button wire:click="openCreateMaterial({{ $chapter->id }})" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-indigo-200 text-xs font-bold text-indigo-700 hover:bg-indigo-50 shadow-2xs transition active:scale-95 cursor-pointer" title="Tambah Modul PDF, Video YouTube, Bacaan, atau Tautan Luar">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    <span>Materi</span>
                                </button>
                                <button wire:click="openCreateAssignment({{ $chapter->id }})" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-amber-200 text-xs font-bold text-amber-800 hover:bg-amber-50 shadow-2xs transition active:scale-95 cursor-pointer" title="Tambah Tugas Latihan dengan Batas Waktu & Poin">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    <span>Tugas</span>
                                </button>
                                <button wire:click="openCreateQuiz({{ $chapter->id }})" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-purple-200 text-xs font-bold text-purple-800 hover:bg-purple-50 shadow-2xs transition active:scale-95 cursor-pointer" title="Tambah Kuis Pilihan Ganda & Waktu Pengerjaan">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    <span>Kuis</span>
                                </button>

                                <div class="h-5 w-px bg-slate-200 mx-1"></div>

                                <button wire:click="openEditChapter({{ $chapter->id }})" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 rounded-xl transition cursor-pointer" title="Ubah Nama Bab">
                                    ✏️
                                </button>
                                <button wire:click="hapusChapter({{ $chapter->id }})" wire:confirm="Yakin ingin menghapus bab ini beserta seluruh materi, tugas, dan kuis di dalamnya?" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer" title="Hapus Bab">
                                    🗑️
                                </button>
                            </div>
                        </div>

                        <!-- Isi Konten Bab: Materi, Tugas, & Kuis -->
                        <div class="p-5 sm:p-6 space-y-4">
                            
                            <!-- 1. List Materi Pembelajaran -->
                            @if ($chapter->materials->count() > 0)
                                <div class="space-y-2">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Materi Bacaan & Media</span>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($chapter->materials as $mat)
                                            <div class="p-3.5 rounded-2xl border border-slate-200/70 hover:border-indigo-200 bg-white flex items-center justify-between gap-3 transition group">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="h-9 w-9 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 {{ $mat->tipe === 'pdf' ? 'bg-rose-50 text-rose-600' : ($mat->tipe === 'video' ? 'bg-red-50 text-red-600' : ($mat->tipe === 'link' ? 'bg-blue-50 text-blue-600' : 'bg-indigo-50 text-indigo-600')) }}">
                                                        @if($mat->tipe === 'pdf') 📕 @elseif($mat->tipe === 'video') 🎥 @elseif($mat->tipe === 'link') 🔗 @else 📄 @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-xs sm:text-sm text-slate-800 truncate group-hover:text-indigo-600 transition">{{ $mat->judul }}</p>
                                                        <span class="text-[10px] uppercase font-bold text-slate-400">{{ $mat->tipe }} &bull; {{ $mat->durasi_menit ?: 15 }} menit</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button wire:click="openEditMaterial({{ $mat->id }})" class="p-1 text-slate-400 hover:text-slate-700 rounded transition" title="Edit">✏️</button>
                                                    <button wire:click="hapusMaterial({{ $mat->id }})" wire:confirm="Hapus materi ini?" class="p-1 text-slate-400 hover:text-red-600 rounded transition" title="Hapus">🗑️</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- 2. List Tugas Latihan -->
                            @if ($chapter->assignments->count() > 0)
                                <div class="space-y-2 pt-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 flex items-center gap-1">
                                            <span>📝</span>
                                            <span>Tugas Latihan & Pengumpulan</span>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($chapter->assignments as $asg)
                                            @php
                                                $subCount = $asg->submissions->count();
                                                $gradedCount = $asg->submissions->where('status', 'graded')->count();
                                                $ungradedCount = $subCount - $gradedCount;
                                            @endphp
                                            <div class="p-4 rounded-2xl border border-amber-200/90 bg-amber-50/50 hover:bg-amber-50/80 transition flex flex-col justify-between gap-3 shadow-2xs">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm shrink-0">
                                                            📝
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="font-bold text-xs sm:text-sm text-slate-900 truncate">{{ $asg->judul }}</p>
                                                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-500 mt-0.5">
                                                                <span class="font-bold {{ $ungradedCount > 0 ? 'text-amber-800' : 'text-slate-500' }}">
                                                                    {{ $subCount }} Siswa Mengumpulkan
                                                                </span>
                                                                @if($asg->deadline)
                                                                    <span>&bull;</span>
                                                                    <span class="{{ $asg->isLewatDeadline() ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                                                        Tenggat: {{ $asg->deadline->translatedFormat('d M H:i') }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <button wire:click="openEditAssignment({{ $asg->id }})" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-white transition" title="Edit Tugas">✏️</button>
                                                        <button wire:click="hapusAssignment({{ $asg->id }})" wire:confirm="Hapus tugas latihan ini?" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Hapus">🗑️</button>
                                                    </div>
                                                </div>

                                                <!-- Action Penilaian Bar -->
                                                <div class="pt-2 border-t border-amber-200/70 flex items-center justify-between">
                                                    <div class="text-[11px] font-semibold text-amber-900">
                                                        @if($ungradedCount > 0)
                                                            <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-950 font-bold text-[10px]">
                                                                {{ $ungradedCount }} Perlu Dinilai
                                                            </span>
                                                        @elseif($subCount > 0)
                                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                                ✓ Semua Dinilai
                                                            </span>
                                                        @else
                                                            <span class="text-slate-400 text-[10px]">Menunggu pengumpulan siswa</span>
                                                        @endif
                                                    </div>

                                                    <button wire:click="openGrading({{ $asg->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                                                        <span>🔍 Periksa Nilai ({{ $subCount }})</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- 3. List Kuis Interaktif -->
                            @if ($chapter->quizzes->count() > 0)
                                <div class="space-y-2 pt-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-700 flex items-center gap-1">
                                            <span>🎯</span>
                                            <span>Kuis Interaktif Pilihan Ganda</span>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($chapter->quizzes as $quiz)
                                            <div class="p-4 rounded-2xl border border-purple-200/90 bg-purple-50/50 hover:bg-purple-50/80 transition flex flex-col justify-between gap-3 shadow-2xs">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <div class="h-9 w-9 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-sm shrink-0">
                                                            🎯
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="font-bold text-xs sm:text-sm text-slate-900 truncate">{{ $quiz->judul }}</p>
                                                            <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-500 mt-0.5">
                                                                <span class="font-bold text-purple-700">{{ $quiz->questions->count() }} Soal</span>
                                                                <span>&bull;</span>
                                                                <span>⏱️ {{ $quiz->durasi_menit }} Menit</span>
                                                                <span>&bull;</span>
                                                                <span>KKM: {{ $quiz->kkm }}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <button wire:click="openEditQuiz({{ $quiz->id }})" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-white transition" title="Edit Kuis">✏️</button>
                                                        <button wire:click="hapusQuiz({{ $quiz->id }})" wire:confirm="Hapus kuis ini?" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Hapus">🗑️</button>
                                                    </div>
                                                </div>

                                                <!-- Action Kelola Soal Bar -->
                                                <div class="pt-2 border-t border-purple-200/70 flex items-center justify-between">
                                                    <div class="text-[11px] text-slate-500">
                                                        <span>{{ $quiz->questions->count() > 0 ? 'Siap dikerjakan siswa' : 'Belum ada butir soal' }}</span>
                                                    </div>

                                                    <button wire:click="openQuestions({{ $quiz->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                                                        <span>⚙️ Kelola Soal ({{ $quiz->questions->count() }})</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($chapter->materials->count() === 0 && $chapter->assignments->count() === 0 && $chapter->quizzes->count() === 0)
                                <div class="p-6 rounded-2xl bg-slate-50 text-center text-xs text-slate-400">
                                    Bab ini masih kosong. Klik tombol di kanan atas bab untuk menambahkan materi (PDF/link), tugas latihan, atau kuis.
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 bg-white rounded-3xl border border-slate-200 text-center space-y-3">
                        <span class="text-4xl block">📚</span>
                        <h3 class="font-bold text-slate-800 text-base">Belum Ada Bab Pembelajaran</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Mulai susun kurikulum kelas Anda dengan menambahkan Bab pertama.</p>
                        <button wire:click="openCreateChapter" class="mt-2 inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-xs transition">
                            + Tambah Bab Pertama
                        </button>
                    </div>
                @endforelse
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- TAB 2: DAFTAR SISWA & MANAJEMEN ANGGOTA                      -->
        <!-- ============================================================ -->
        @if ($activeTab === 'siswa')
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Daftar Siswa di Kelas Ini</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Pantau siswa yang bergabung dan tingkat progres belajarnya</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 text-indigo-700">
                        {{ $siswaList->count() }} Siswa Terdaftar
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-slate-50 text-slate-500 font-extrabold text-[11px] uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3.5 px-6">Siswa</th>
                                <th class="py-3.5 px-4">Bergabung</th>
                                <th class="py-3.5 px-4">Progres Materi</th>
                                <th class="py-3.5 px-4">Tugas Selesai</th>
                                <th class="py-3.5 px-4">Kuis Selesai</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            @forelse ($siswaList as $s)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 text-white font-bold flex items-center justify-center text-xs uppercase shadow-2xs shrink-0">
                                                {{ substr($s->name, 0, 2) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-900 truncate">{{ $s->name }}</p>
                                                <p class="text-xs text-slate-400 truncate">{{ $s->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-xs text-slate-500">
                                        {{ $s->pivot->tanggal_gabung ? date('d M Y', strtotime($s->pivot->tanggal_gabung)) : '-' }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="space-y-1 w-32">
                                            <div class="flex justify-between text-[11px] font-bold">
                                                <span>{{ $s->completed_materials }}/{{ $totalMaterialsCount }}</span>
                                                <span class="text-indigo-600">{{ $s->progres_persen }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $s->progres_persen }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-xs font-bold text-amber-700">
                                        {{ $s->tugas_selesai }} / {{ $allAssignments->count() }} Tugas
                                    </td>
                                    <td class="py-4 px-4 text-xs font-bold text-purple-700">
                                        {{ $s->kuis_selesai }} / {{ $allQuizzes->count() }} Kuis
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button wire:click="keluarkanSiswa({{ $s->id }})" wire:confirm="Yakin ingin mengeluarkan siswa ini dari kelas?" class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-red-600 hover:bg-red-50 transition border border-transparent hover:border-red-200">
                                            Keluarkan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                        Belum ada siswa yang bergabung. Bagikan kode kelas <b class="text-indigo-600 font-mono">{{ $kelas->kode_kelas }}</b> ke siswa Anda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ============================================================ -->
        <!-- TAB 3: BUKU NILAI KELAS (GRADEBOOK MATRIKS)                  -->
        <!-- ============================================================ -->
        @if ($activeTab === 'nilai')
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Rekapitulasi Nilai Kelas (Gradebook)</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Daftar nilai tugas latihan dan kuis seluruh siswa</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-extrabold text-[11px] uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 sticky left-0 bg-slate-50 z-10 shadow-xs">Nama Siswa</th>
                                <th class="py-3 px-3 text-center">Rata-Rata Akhir</th>
                                <th class="py-3 px-3 text-center">Rata Tugas</th>
                                <th class="py-3 px-3 text-center">Rata Kuis</th>
                                @foreach($allAssignments as $asg)
                                    <th class="py-3 px-3 text-center text-amber-700 bg-amber-50/50" title="{{ $asg->judul }}">
                                        {{ Str::limit($asg->judul, 12) }}
                                    </th>
                                @endforeach
                                @foreach($allQuizzes as $qz)
                                    <th class="py-3 px-3 text-center text-purple-700 bg-purple-50/50" title="{{ $qz->judul }}">
                                        {{ Str::limit($qz->judul, 12) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($siswaList as $s)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 px-4 font-bold text-slate-900 sticky left-0 bg-white z-10 whitespace-nowrap shadow-xs">
                                        {{ $s->name }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-1 rounded-lg font-black text-xs {{ $s->nilai_akhir >= 75 ? 'bg-emerald-100 text-emerald-800' : ($s->nilai_akhir > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500') }}">
                                            {{ $s->nilai_akhir }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-amber-800">
                                        {{ round($s->rata_rata_tugas) }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-purple-800">
                                        {{ round($s->rata_rata_kuis) }}
                                    </td>

                                    <!-- Nilai per Tugas -->
                                    @foreach($allAssignments as $asg)
                                        @php
                                            $sub = $asg->submissions->firstWhere('user_id', $s->id);
                                        @endphp
                                        <td class="py-3 px-3 text-center">
                                            @if($sub && $sub->nilai !== null)
                                                <span class="font-bold {{ $sub->nilai >= 75 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $sub->nilai }}</span>
                                            @elseif($sub)
                                                <span class="text-[10px] text-amber-600 font-semibold">Terkumpul</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Nilai per Kuis -->
                                    @foreach($allQuizzes as $qz)
                                        @php
                                            $attempt = $qz->attempts->where('user_id', $s->id)->where('status', 'selesai')->first();
                                        @endphp
                                        <td class="py-3 px-3 text-center">
                                            @if($attempt)
                                                <span class="font-bold {{ $attempt->skor >= $qz->kkm ? 'text-emerald-700' : 'text-rose-600' }}">{{ $attempt->skor }}</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + $allAssignments->count() + $allQuizzes->count() }}" class="py-6 text-center text-slate-400 text-xs">
                                        Belum ada data nilai siswa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    <!-- ============================================================ -->
    <!-- MODAL 1: TAMBAH / EDIT BAB                                   -->
    <!-- ============================================================ -->
    @if ($showChapterModal)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-black text-lg text-slate-900">{{ $chapterId ? 'Edit Bab' : 'Tambah Bab Baru' }}</h3>
                    <button wire:click="$set('showChapterModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form wire:submit.prevent="simpanChapter" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Bab <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="chapterJudul" placeholder="Contoh: Bab 1 - Pengenalan Aljabar" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3">
                        @error('chapterJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Bab (Opsional)</label>
                        <textarea wire:model="chapterDeskripsi" rows="3" placeholder="Jelaskan ringkasan materi di bab ini..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showChapterModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">Simpan Bab</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 2: TAMBAH / EDIT MATERI (PDF / LINK / VIDEO / TEKS)   -->
    <!-- ============================================================ -->
    @if ($showMaterialModal)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-black text-lg text-slate-900">{{ $materialId ? 'Edit Materi' : 'Tambah Materi Baru' }}</h3>
                    <button wire:click="$set('showMaterialModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form wire:submit.prevent="simpanMaterial" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Materi <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="materialJudul" placeholder="Contoh: Modul 1.1 Persamaan Linier" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3">
                        @error('materialJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Pemilih Tipe Materi Visual & Ramah Guru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Format Bahan Ajar</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="button" wire:click="$set('materialTipe', 'pdf')" class="p-3 rounded-2xl border text-center transition flex flex-col items-center gap-1.5 cursor-pointer {{ $materialTipe === 'pdf' ? 'bg-rose-50 border-rose-400 text-rose-800 ring-2 ring-rose-200' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white' }}">
                                <span class="text-2xl">📕</span>
                                <span class="text-xs font-bold">Modul PDF</span>
                            </button>
                            <button type="button" wire:click="$set('materialTipe', 'video')" class="p-3 rounded-2xl border text-center transition flex flex-col items-center gap-1.5 cursor-pointer {{ $materialTipe === 'video' ? 'bg-red-50 border-red-400 text-red-800 ring-2 ring-red-200' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white' }}">
                                <span class="text-2xl">🎥</span>
                                <span class="text-xs font-bold">Video YouTube</span>
                            </button>
                            <button type="button" wire:click="$set('materialTipe', 'teks')" class="p-3 rounded-2xl border text-center transition flex flex-col items-center gap-1.5 cursor-pointer {{ $materialTipe === 'teks' ? 'bg-indigo-50 border-indigo-400 text-indigo-800 ring-2 ring-indigo-200' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white' }}">
                                <span class="text-2xl">📄</span>
                                <span class="text-xs font-bold">Bacaan Teks</span>
                            </button>
                            <button type="button" wire:click="$set('materialTipe', 'link')" class="p-3 rounded-2xl border text-center transition flex flex-col items-center gap-1.5 cursor-pointer {{ $materialTipe === 'link' ? 'bg-blue-50 border-blue-400 text-blue-800 ring-2 ring-blue-200' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white' }}">
                                <span class="text-2xl">🔗</span>
                                <span class="text-xs font-bold">Tautan Luar</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Waktu Belajar (Menit)</label>
                            <input type="number" wire:model="materialDurasi" min="1" placeholder="15" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-2.5">
                        </div>
                    </div>

                    <!-- 1. Form Khusus PDF -->
                    @if ($materialTipe === 'pdf')
                        <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200/80 space-y-3">
                            <div class="flex items-center gap-2 text-rose-950 font-bold text-xs">
                                <span>📕</span>
                                <span>Unggah Berkas PDF (Maksimal 20 MB)</span>
                            </div>
                            <input type="file" wire:model="materialFile" accept=".pdf" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-600 file:text-white hover:file:bg-rose-700 cursor-pointer">
                            <span wire:loading wire:target="materialFile" class="text-xs text-rose-600 font-bold block">Mengunggah file PDF...</span>
                            @error('materialFile') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror

                            <div class="pt-2 border-t border-rose-200/60">
                                <label class="block text-[11px] font-bold text-rose-900 mb-1">Atau Gunakan Tautan PDF Online (Google Drive / Cloud)</label>
                                <input type="url" wire:model="materialUrl" placeholder="https://..." class="w-full text-xs rounded-xl border border-rose-200 bg-white p-2.5">
                            </div>
                        </div>
                    @endif

                    <!-- 2. Form Khusus Video YouTube -->
                    @if ($materialTipe === 'video')
                        <div class="p-4 rounded-2xl bg-red-50/70 border border-red-200/80 space-y-2">
                            <label class="block text-xs font-bold text-red-950">Tautan Video YouTube</label>
                            <input type="url" wire:model="materialUrl" placeholder="https://www.youtube.com/watch?v=..." class="w-full text-xs rounded-xl border border-red-200 bg-white p-2.5">
                            <p class="text-[11px] text-red-800">Siswa dapat langsung memutar video ini langsung di dalam aplikasi RuangKelas.</p>
                        </div>
                    @endif

                    <!-- 3. Form Khusus Link Eksternal -->
                    @if ($materialTipe === 'link')
                        <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 space-y-2">
                            <label class="block text-xs font-bold text-blue-950">Alamat Tautan Sumber Belajar (URL)</label>
                            <input type="url" wire:model="materialUrl" placeholder="https://..." class="w-full text-xs rounded-xl border border-blue-200 bg-white p-2.5">
                            <p class="text-[11px] text-blue-800">Tautan modul digital, jurnal, atau website interaktif (misal GeoGebra, Wikipedia, Google Docs).</p>
                        </div>
                    @endif

                    <!-- Penjelasan / Konten Bacaan Teks -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Rangkuman / Catatan Penjelasan Materi</label>
                        <textarea wire:model="materialKonten" rows="4" placeholder="Tuliskan rangkuman, instruksi membaca, atau catatan penting untuk siswa..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showMaterialModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">Simpan Materi</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 3: TAMBAH / EDIT TUGAS LATIHAN (ASSIGNMENT)            -->
    <!-- ============================================================ -->
    @if ($showAssignmentModal)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-black text-lg text-slate-900">{{ $assignmentId ? 'Edit Tugas Latihan' : 'Tambah Tugas Latihan Baru' }}</h3>
                    <button wire:click="$set('showAssignmentModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form wire:submit.prevent="simpanAssignment" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Tugas <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="assignmentJudul" placeholder="Contoh: Latihan Soal Bab 1 Halaman 45" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3">
                        @error('assignmentJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tenggat Waktu (Deadline)</label>
                            <input type="datetime-local" wire:model="assignmentDeadline" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Poin Maksimal</label>
                            <input type="number" wire:model="assignmentPoin" min="1" max="100" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Petunjuk Pengerjaan Tugas</label>
                        <textarea wire:model="assignmentDeskripsi" rows="4" placeholder="Tuliskan instruksi tugas secara rinci untuk siswa..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3"></textarea>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 space-y-2">
                        <label class="block text-xs font-bold text-amber-900">Lampiran Soal / Lembar Kerja (Opsional)</label>
                        <input type="file" wire:model="assignmentFile" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-600 file:text-white hover:file:bg-amber-700">
                        <span wire:loading wire:target="assignmentFile" class="text-xs text-amber-600 font-bold block">Mengunggah file...</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Link Referensi Tugas (Opsional)</label>
                        <input type="url" wire:model="assignmentUrl" placeholder="https://drive.google.com/..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showAssignmentModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-700 shadow-sm transition">Simpan Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 4: PERIKSA & BERI NILAI TUGAS (GRADING MODAL)          -->
    <!-- ============================================================ -->
    @if ($showGradingModal && $selectedAssignment)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wider block">Penilaian Tugas</span>
                        <h3 class="font-black text-xl text-slate-900">{{ $selectedAssignment->judul }}</h3>
                    </div>
                    <button wire:click="$set('showGradingModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                @if(session('grading_success'))
                    <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200">
                        ✓ {{ session('grading_success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Kolom Kiri: Daftar Pengumpulan Siswa -->
                    <div class="md:col-span-1 border-r border-slate-100 pr-0 md:pr-4 space-y-2">
                        <span class="text-xs font-extrabold uppercase text-slate-400 block mb-2">Jawaban Siswa ({{ $selectedAssignment->submissions->count() }})</span>
                        <div class="space-y-1.5 max-h-96 overflow-y-auto">
                            @forelse($selectedAssignment->submissions as $sub)
                                <div wire:click="pilihSubmission({{ $sub->id }})" class="p-3 rounded-2xl border cursor-pointer transition {{ $gradingSubmissionId === $sub->id ? 'bg-amber-50 border-amber-300' : 'bg-white border-slate-200 hover:bg-slate-50' }}">
                                    <div class="flex items-center justify-between">
                                        <p class="font-bold text-xs text-slate-800">{{ $sub->user->name }}</p>
                                        @if($sub->nilai !== null)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-800">{{ $sub->nilai }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">Belum Dinilai</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-slate-400 block mt-1">Kirim: {{ $sub->submitted_at->format('d M H:i') }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 py-4 text-center">Belum ada siswa yang mengumpulkan tugas ini.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Kolom Kanan: Detail & Form Input Nilai -->
                    <div class="md:col-span-2 space-y-4">
                        @if($gradingSubmissionId)
                            @php
                                $currentSub = $selectedAssignment->submissions->firstWhere('id', $gradingSubmissionId);
                            @endphp
                            @if($currentSub)
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-900">{{ $currentSub->user->name }}</h4>
                                            <span class="text-xs text-slate-400">{{ $currentSub->user->email }}</span>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-500">Dikirim: {{ $currentSub->submitted_at->format('d M Y H:i') }}</span>
                                    </div>

                                    @if($currentSub->catatan_siswa)
                                        <div class="space-y-1">
                                            <span class="text-[11px] font-bold text-slate-500">Catatan / Jawaban Siswa:</span>
                                            <p class="text-xs text-slate-800 bg-white p-3 rounded-xl border border-slate-200 whitespace-pre-line">{{ $currentSub->catatan_siswa }}</p>
                                        </div>
                                    @endif

                                    @if($currentSub->link_tugas)
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-500">Link Tugas:</span>
                                            <a href="{{ $currentSub->link_tugas }}" target="_blank" class="text-xs font-bold text-indigo-600 hover:underline">
                                                Buka Tautan Tugas &nearr;
                                            </a>
                                        </div>
                                    @endif

                                    @if($currentSub->file_jawaban)
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-500">File Jawaban:</span>
                                            <a href="{{ asset('storage/' . $currentSub->file_jawaban) }}" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-xs hover:bg-indigo-700 transition">
                                                ⬇ Unduh File Jawaban
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <!-- Form Penilaian -->
                                <form wire:submit.prevent="simpanNilai" class="space-y-4 pt-2">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Beri Nilai (Skor 0 - 100) <span class="text-red-500">*</span></label>
                                        <input type="number" wire:model="inputNilai" min="0" max="100" placeholder="Contoh: 90" class="w-full sm:w-48 text-lg font-black rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3">
                                        @error('inputNilai') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Evaluasi Guru untuk Siswa</label>
                                        <textarea wire:model="inputCatatanGuru" rows="3" placeholder="Contoh: Jawaban sangat rapi dan tepat, pertahankan prestasimu!" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-amber-500 p-3"></textarea>
                                    </div>

                                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition">
                                        ✓ Simpan Nilai & Feedback
                                    </button>
                                </form>
                            @endif
                        @else
                            <div class="h-64 flex flex-col items-center justify-center text-center p-6 bg-slate-50 rounded-2xl text-slate-400">
                                <span class="text-3xl block mb-2">👈</span>
                                <p class="text-xs font-semibold">Pilih salah satu siswa dari daftar di sebelah kiri untuk melihat jawaban dan memberikan nilai.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 5: TAMBAH / EDIT KUIS                                  -->
    <!-- ============================================================ -->
    @if ($showQuizModal)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-black text-lg text-slate-900">{{ $quizId ? 'Edit Kuis' : 'Tambah Kuis Baru' }}</h3>
                    <button wire:click="$set('showQuizModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form wire:submit.prevent="simpanQuiz" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Kuis <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="quizJudul" placeholder="Contoh: Kuis Harian 1 - Operasi Aljabar" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-3">
                        @error('quizJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Durasi Pengerjaan (Menit)</label>
                            <input type="number" wire:model="quizDurasi" min="1" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-3">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nilai KKM (Kelulusan)</label>
                            <input type="number" wire:model="quizKkm" min="0" max="100" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-3">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi / Petunjuk Kuis</label>
                        <textarea wire:model="quizDeskripsi" rows="3" placeholder="Jelaskan petunjuk kuis untuk siswa..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-3"></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="quizAcak" wire:model="quizAcak" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                        <label for="quizAcak" class="text-xs font-bold text-slate-700">Acak urutan soal untuk tiap siswa</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showQuizModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-purple-600 text-white hover:bg-purple-700 shadow-sm transition">Simpan Kuis</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 6: KELOLA SOAL KUIS (QUESTIONS & OPTIONS)              -->
    <!-- ============================================================ -->
    @if ($showQuestionModal && $selectedQuizId)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-xs font-bold text-purple-700 uppercase tracking-wider block">Kelola Soal Kuis</span>
                        <h3 class="font-black text-xl text-slate-900">{{ $selectedQuizJudul }}</h3>
                    </div>
                    <button wire:click="$set('showQuestionModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                @if(session('soal_success'))
                    <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200">
                        ✓ {{ session('soal_success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Form Tambah Soal -->
                    <form wire:submit.prevent="simpanSoal" class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-200">
                        <h4 class="font-black text-sm text-slate-900">Form Soal Baru</h4>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pertanyaan <span class="text-red-500">*</span></label>
                            <textarea wire:model="soalTeks" rows="3" placeholder="Tuliskan butir soal di sini..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-purple-500 p-3"></textarea>
                            @error('soalTeks') <span class="text-xs text-red-500 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Bobot Poin</label>
                            <input type="number" wire:model="soalBobot" min="1" class="w-24 text-xs font-bold rounded-xl border border-slate-200 p-2.5">
                        </div>

                        <!-- Opsi Pilihan Ganda -->
                        <div class="space-y-2 pt-2">
                            <label class="block text-xs font-bold text-slate-700">Pilihan Jawaban (Klik radio untuk menandai kunci benar):</label>
                            @foreach ($soalOptions as $idx => $opt)
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="kunci_benar" wire:click="setJawabanBenar({{ $idx }})" {{ $opt['is_benar'] ? 'checked' : '' }} class="h-4 w-4 text-purple-600 focus:ring-purple-500 cursor-pointer" title="Jadikan Kunci Benar">
                                    <span class="font-bold text-xs w-4 text-slate-500">{{ $opt['label'] }}.</span>
                                    <input type="text" wire:model="soalOptions.{{ $idx }}.teks" placeholder="Pilihan {{ $opt['label'] }}" class="flex-1 text-xs rounded-xl border border-slate-200 p-2.5 {{ $opt['is_benar'] ? 'border-purple-400 bg-purple-50/40 font-bold' : '' }}">
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                            + Tambahkan Butir Soal
                        </button>
                    </form>

                    <!-- Daftar Soal Terdaftar -->
                    <div class="space-y-3">
                        <span class="text-xs font-extrabold uppercase text-slate-400 block">Daftar Soal Tersimpan ({{ $activeQuiz ? $activeQuiz->questions->count() : 0 }})</span>
                        <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                            @if ($activeQuiz && $activeQuiz->questions->count() > 0)
                                @foreach ($activeQuiz->questions as $i => $q)
                                    <div class="p-4 bg-white rounded-2xl border border-slate-200 space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="font-bold text-xs text-slate-900 leading-relaxed">
                                                <span class="text-purple-600 font-black">{{ $i + 1 }}.</span> {{ $q->pertanyaan }}
                                            </p>
                                            <button wire:click="hapusSoal({{ $q->id }})" wire:confirm="Hapus butir soal ini?" class="text-slate-400 hover:text-red-600 text-xs shrink-0" title="Hapus Soal">🗑️</button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5 pt-1 text-[11px]">
                                            @foreach ($q->options as $o)
                                                <div class="p-1.5 rounded-lg {{ $o->is_benar ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200' : 'bg-slate-50 text-slate-600' }}">
                                                    {{ $o->label }}. {{ $o->teks }} {{ $o->is_benar ? '✓' : '' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-xs text-slate-400 text-center py-8">Belum ada soal pada kuis ini. Silakan input soal di form sebelah kiri.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
