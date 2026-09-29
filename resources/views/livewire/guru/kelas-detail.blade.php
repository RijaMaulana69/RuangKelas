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
    // STATE Tugas (ASSIGNMENT)
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
    // STATE KUIS (QUIZ) & KELOLA SOAL
    // ============================================================
    public bool $showQuizModal = false;
    public string $quizTab = 'info'; // 'info' atau 'soal'
    public ?int $quizId = null;
    #[Validate('required|string|max:150')]
    public string $quizJudul = '';
    public string $quizDeskripsi = '';
    public int $quizDurasi = 30;
    public int $quizKkm = 70;
    public bool $quizAcak = false;

    // STATE SOAL (QUESTIONS)
    public ?int $questionId = null;
    public string $soalTipe = 'pilihan_ganda'; // 'pilihan_ganda' atau 'essay'
    public string $soalTeks = '';
    public int $soalBobot = 10;
    public string $soalPenjelasan = '';
    public array $soalOptions = [
        ['label' => 'A', 'teks' => '', 'is_benar' => true],
        ['label' => 'B', 'teks' => '', 'is_benar' => false],
        ['label' => 'C', 'teks' => '', 'is_benar' => false],
        ['label' => 'D', 'teks' => '', 'is_benar' => false],
    ];

    // ============================================================
    // STATE HASIL SISWA (QUIZ RESULTS MODAL)
    // ============================================================
    public bool $showQuizResultsModal = false;
    public ?int $selectedQuizId = null;
    public string $selectedQuizJudul = '';

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
        $this->dispatch('notify', message: 'Bab berhasil dihapus');
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
        $this->dispatch('notify', message: 'Materi berhasil dihapus');
    }

    // ------------------------------------------------------------
    // ASSIGNMENT ACTIONS (Tugas)
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
            session()->flash('success', 'Tugas berhasil diperbarui!');
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
            session()->flash('success', 'Tugas baru berhasil ditambahkan!');
        }

        $this->showAssignmentModal = false;
    }

    public function hapusAssignment(int $id): void
    {
        $asg = Assignment::findOrFail($id);
        $asg->delete();
        session()->flash('success', 'Tugas telah dihapus.');
        $this->dispatch('notify', message: 'Tugas berhasil dihapus');
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
        $maxPoin = $this->selectedAssignment ? $this->selectedAssignment->poin_maksimal : 100;
        $this->validate([
            'inputNilai' => "required|integer|min:0|max:{$maxPoin}",
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
    // QUIZ ACTIONS (TAMBAH / EDIT KUIS & KELOLA SOAL)
    // ------------------------------------------------------------
    public function openCreateQuiz(int $chapterId): void
    {
        $this->targetChapterId = $chapterId;
        $this->reset(['quizId', 'quizJudul', 'quizDeskripsi']);
        $this->quizDurasi = 30;
        $this->quizKkm = 70;
        $this->quizAcak = false;
        $this->quizTab = 'info';
        $this->resetSoalForm();
        $this->showQuizModal = true;
    }

    public function openEditQuiz(int $id, string $tab = 'info'): void
    {
        $quiz = Quiz::with('questions.options')->findOrFail($id);
        $this->quizId = $quiz->id;
        $this->targetChapterId = $quiz->chapter_id;
        $this->quizJudul = $quiz->judul;
        $this->quizDeskripsi = $quiz->deskripsi ?? '';
        $this->quizDurasi = $quiz->durasi_menit;
        $this->quizKkm = $quiz->kkm;
        $this->quizAcak = $quiz->acak_soal;
        $this->quizTab = in_array($tab, ['info', 'soal']) ? $tab : 'info';
        $this->resetSoalForm();
        $this->showQuizModal = true;
    }

    public function switchQuizTab(string $tab): void
    {
        if ($tab === 'soal' && !$this->quizId) {
            // Jika kuis baru belum disimpan, simpan data pengaturan terlebih dahulu
            $this->simpanQuiz(true);
            return;
        }
        $this->quizTab = in_array($tab, ['info', 'soal']) ? $tab : 'info';
    }

    public function simpanQuiz(bool $lanjutKeSoal = false): void
    {
        $this->validate([
            'quizJudul' => 'required|string|max:150',
            'quizDurasi' => 'required|integer|min:1',
            'quizKkm' => 'required|integer|min:0|max:100',
        ], [
            'quizJudul.required' => 'Judul kuis wajib diisi.',
            'quizDurasi.required' => 'Durasi pengerjaan wajib diisi.',
            'quizDurasi.min' => 'Durasi minimal 1 menit.',
            'quizKkm.required' => 'Nilai KKM kelulusan wajib diisi.',
            'quizKkm.min' => 'Nilai KKM minimal 0.',
            'quizKkm.max' => 'Nilai KKM maksimal 100.',
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

            if ($lanjutKeSoal) {
                $this->quizTab = 'soal';
                session()->flash('quiz_success', 'Pengaturan kuis tersimpan! Silakan kelola butir soal di bawah.');
            } else {
                $this->showQuizModal = false;
                session()->flash('success', 'Pengaturan kuis berhasil diperbarui!');
            }
        } else {
            $lastOrder = Quiz::where('chapter_id', $this->targetChapterId)->max('urutan') ?? 0;
            $newQuiz = Quiz::create([
                'chapter_id' => $this->targetChapterId,
                'judul' => $this->quizJudul,
                'deskripsi' => $this->quizDeskripsi,
                'durasi_menit' => $this->quizDurasi,
                'kkm' => $this->quizKkm,
                'acak_soal' => $this->quizAcak,
                'urutan' => $lastOrder + 1,
            ]);

            $this->quizId = $newQuiz->id;

            if ($lanjutKeSoal) {
                $this->quizTab = 'soal';
                session()->flash('quiz_success', 'Kuis berhasil dibuat! Silakan mulai menambahkan butir soal.');
            } else {
                $this->showQuizModal = false;
                session()->flash('success', 'Kuis baru berhasil ditambahkan!');
            }
        }
    }

    public function tutupQuizModal(): void
    {
        $this->showQuizModal = false;
        $this->reset(['quizId', 'quizJudul', 'quizDeskripsi', 'targetChapterId']);
        $this->resetSoalForm();
    }

    public function selesaiKuis(): void
    {
        $this->tutupQuizModal();
        session()->flash('success', 'Kuis dan soal berhasil disimpan!');
    }

    public function hapusQuiz(int $id): void
    {
        $q = Quiz::findOrFail($id);
        $q->delete();
        session()->flash('success', 'Kuis telah dihapus.');
        $this->dispatch('notify', message: 'Kuis berhasil dihapus');
    }

    // ------------------------------------------------------------
    // SOAL KUIS ACTIONS (DI DALAM MODAL TAMBAH / EDIT KUIS)
    // ------------------------------------------------------------
    public function resetSoalForm(): void
    {
        $this->reset(['questionId', 'soalTeks', 'soalPenjelasan']);
        $this->soalTipe = 'pilihan_ganda';
        $this->soalBobot = 10;
        $this->soalOptions = [
            ['label' => 'A', 'teks' => '', 'is_benar' => true],
            ['label' => 'B', 'teks' => '', 'is_benar' => false],
            ['label' => 'C', 'teks' => '', 'is_benar' => false],
            ['label' => 'D', 'teks' => '', 'is_benar' => false],
        ];
    }

    public function setSoalTipe(string $tipe): void
    {
        $this->soalTipe = in_array($tipe, ['pilihan_ganda', 'essay']) ? $tipe : 'pilihan_ganda';
    }

    public function editSoal(int $id): void
    {
        $q = Question::with('options')->findOrFail($id);
        $this->questionId = $q->id;
        $this->soalTipe = $q->tipe ?? 'pilihan_ganda';
        $this->soalTeks = $q->pertanyaan;
        $this->soalBobot = $q->bobot;
        $this->soalPenjelasan = '';

        $loadedOptions = [];
        $labels = ['A', 'B', 'C', 'D'];
        $hasBenar = false;
        foreach ($labels as $idx => $lbl) {
            $opt = $q->options->firstWhere('label', $lbl);
            $isBenar = $opt ? (bool)$opt->is_benar : false;
            if ($isBenar) {
                $hasBenar = true;
            }
            $loadedOptions[] = [
                'label' => $lbl,
                'teks' => $opt ? $opt->teks : '',
                'is_benar' => $isBenar,
            ];
        }
        if (!$hasBenar && count($loadedOptions) > 0) {
            $loadedOptions[0]['is_benar'] = true;
        }
        $this->soalOptions = $loadedOptions;
    }

    public function setJawabanBenar(int $index): void
    {
        foreach ($this->soalOptions as $i => &$opt) {
            $opt['is_benar'] = ($i === $index);
        }
    }

    public function simpanSoal(): void
    {
        if ($this->soalTipe === 'pilihan_ganda') {
            $this->validate([
                'soalTeks' => 'required|string',
                'soalBobot' => 'required|integer|min:1',
                'soalOptions.0.teks' => 'required|string',
                'soalOptions.1.teks' => 'required|string',
            ], [
                'soalTeks.required' => 'Pertanyaan soal wajib diisi.',
                'soalBobot.required' => 'Bobot poin wajib diisi.',
                'soalBobot.min' => 'Bobot poin minimal 1.',
                'soalOptions.0.teks.required' => 'Pilihan jawaban A wajib diisi.',
                'soalOptions.1.teks.required' => 'Pilihan jawaban B wajib diisi.',
            ]);
        } else {
            $this->validate([
                'soalTeks' => 'required|string',
                'soalBobot' => 'required|integer|min:1',
            ], [
                'soalTeks.required' => 'Pertanyaan soal esai wajib diisi.',
                'soalBobot.required' => 'Bobot poin wajib diisi.',
                'soalBobot.min' => 'Bobot poin minimal 1.',
            ]);
        }

        if (!$this->quizId) {
            return;
        }

        if ($this->questionId) {
            $question = Question::findOrFail($this->questionId);
            $question->update([
                'tipe' => $this->soalTipe,
                'pertanyaan' => $this->soalTeks,
                'bobot' => $this->soalBobot,
                'penjelasan' => null,
            ]);

            if ($this->soalTipe === 'pilihan_ganda') {
                foreach ($this->soalOptions as $opt) {
                    if (!empty(trim($opt['teks']))) {
                        QuestionOption::updateOrCreate(
                            ['question_id' => $question->id, 'label' => $opt['label']],
                            ['teks' => $opt['teks'], 'is_benar' => $opt['is_benar']]
                        );
                    } else {
                        QuestionOption::where('question_id', $question->id)
                            ->where('label', $opt['label'])
                            ->delete();
                    }
                }
            } else {
                QuestionOption::where('question_id', $question->id)->delete();
            }

            $this->resetSoalForm();
            session()->flash('soal_success', 'Butir soal berhasil diperbarui!');
        } else {
            $lastOrder = Question::where('quiz_id', $this->quizId)->max('urutan') ?? 0;

            $question = Question::create([
                'quiz_id' => $this->quizId,
                'tipe' => $this->soalTipe,
                'pertanyaan' => $this->soalTeks,
                'bobot' => $this->soalBobot,
                'penjelasan' => null,
                'urutan' => $lastOrder + 1,
            ]);

            if ($this->soalTipe === 'pilihan_ganda') {
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
            }

            $this->resetSoalForm();
            session()->flash('soal_success', 'Soal baru berhasil ditambahkan!');
        }
    }

    public function hapusSoal(int $id): void
    {
        $q = Question::findOrFail($id);
        $q->delete();
        if ($this->questionId === $id) {
            $this->resetSoalForm();
        }
        session()->flash('soal_success', 'Soal berhasil dihapus.');
        $this->dispatch('notify', message: 'Soal kuis berhasil dihapus');
    }

    // ------------------------------------------------------------
    // HASIL SISWA ACTIONS (MODAL HASIL SISWA SAJA)
    // ------------------------------------------------------------
    public function openQuizResults(int $quizId): void
    {
        $quiz = Quiz::with(['questions.options', 'attempts.user'])->findOrFail($quizId);
        $this->selectedQuizId = $quiz->id;
        $this->selectedQuizJudul = $quiz->judul;
        $this->showQuizResultsModal = true;
    }

    public function hapusAttempt(int $attemptId): void
    {
        $att = \App\Models\QuizAttempt::findOrFail($attemptId);
        $att->delete();
        session()->flash('hasil_success', 'Percobaan pengerjaan kuis siswa berhasil direset.');
        $this->dispatch('notify', message: 'Percobaan kuis siswa berhasil direset');
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
        $this->dispatch('notify', message: 'Siswa berhasil dikeluarkan');
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

        $activeEditingQuiz = $this->quizId 
            ? Quiz::with('questions.options')->find($this->quizId) 
            : null;

        $activeResultsQuiz = $this->selectedQuizId 
            ? Quiz::with(['questions.options', 'attempts.user'])->find($this->selectedQuizId) 
            : null;

        return [
            'kelas' => $kelas,
            'siswaList' => $siswaList,
            'allAssignments' => $allAssignments,
            'allQuizzes' => $allQuizzes,
            'totalMaterialsCount' => $totalMaterialsCount,
            'activeQuiz' => $activeEditingQuiz,
            'activeEditingQuiz' => $activeEditingQuiz,
            'activeResultsQuiz' => $activeResultsQuiz,
        ];
    }
}; ?>

<div class="space-y-6 pb-20 md:pb-8"
     x-data="{
         deleteModal: false,
         deleteTitle: '',
         deleteMessage: '',
         deleteAction: null,
         confirmDelete(title, message, callback) {
             this.deleteTitle = title;
             this.deleteMessage = message || 'Tindakan ini tidak dapat dibatalkan.';
             this.deleteAction = callback;
             this.deleteModal = true;
         },
         doDelete() {
             if (this.deleteAction) {
                 this.deleteAction();
             }
             this.deleteModal = false;
         }
     }">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Flash Alert Notifikasi -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm shadow-xs">
                <div class="flex items-center gap-2 font-bold">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 p-1 rounded-lg cursor-pointer shrink-0">&times;</button>
            </div>
        @endif

        <!-- Header Kelas & Navigasi Terpadu (Sederhana, Bersih & Profesional) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Info Kelas & Aksi Utama -->
            <div class="p-5 sm:p-6 pb-4 sm:pb-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1.5 min-w-0">
                        <div>
                            <a href="{{ route('guru.kelas.index') }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 hover:text-slate-900 text-xs font-bold shadow-xs transition group">
                                <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-700 transition-transform group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                <span>Kembali</span>
                            </a>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ $kelas->nama }}</h1>
                        @if($kelas->deskripsi)
                            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-2xl line-clamp-2">{{ $kelas->deskripsi }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2.5 pt-1 text-xs text-slate-500 font-medium">
                            <span class="font-bold text-slate-700">{{ $kelas->chapters->count() }} Bab</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-bold text-slate-700">{{ $allAssignments->count() }} Tugas</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-bold text-slate-700">{{ $allQuizzes->count() }} Kuis</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-bold text-slate-700">{{ $siswaList->count() }} Siswa</span>
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
            <div class="px-3 sm:px-6 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between gap-2 sm:gap-3">
                <nav class="flex items-center gap-1 sm:gap-2 -mb-px overflow-x-auto no-scrollbar py-0.5">
                    <button wire:click="$set('activeTab', 'kurikulum')"
                            class="shrink-0 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition cursor-pointer {{ $activeTab === 'kurikulum' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        Kurikulum
                    </button>
                    <button wire:click="$set('activeTab', 'siswa')"
                            class="shrink-0 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'siswa' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        <span>Siswa</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'siswa' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $siswaList->count() }}
                        </span>
                    </button>
                    <button wire:click="$set('activeTab', 'nilai')"
                            class="shrink-0 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition cursor-pointer {{ $activeTab === 'nilai' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        Penilaian Siswa
                    </button>
                </nav>

                @if ($activeTab === 'kurikulum')
                    <div class="py-2 shrink-0">
                        <button wire:click="openCreateChapter" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-xl text-xs shadow-xs transition active:scale-95 cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span class="hidden sm:inline">Tambah Bab</span>
                            <span class="sm:hidden">Bab</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 1: KURIKULUM & MATERI (BAB, MATERI PDF/LINK, TUGAS, KUIS) -->
        <!-- ============================================================ -->
        @if ($activeTab === 'kurikulum')
            <div class="space-y-6">
                @forelse ($kelas->chapters as $index => $chapter)
                    <div x-data="{ open: true }" class="bg-white rounded-2xl border-2 border-slate-200/90 shadow-xs overflow-hidden transition-all">
                        <!-- Header Bab (Clickable to collapse) -->
                        <div class="p-4 sm:px-5 sm:py-4 flex flex-col md:flex-row md:items-center justify-between gap-3 cursor-pointer select-none bg-white hover:bg-slate-50/70 transition" @click="open = !open">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500 font-medium">
                                        {{ $chapter->materials->count() }} Materi &bull; {{ $chapter->assignments->count() }} Tugas &bull; {{ $chapter->quizzes->count() }} Kuis
                                    </span>
                                </div>
                                <h3 class="font-bold text-sm sm:text-base text-slate-900 break-words md:truncate mt-0.5">{{ $chapter->judul }}</h3>
                                @if ($chapter->deskripsi)
                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $chapter->deskripsi }}</p>
                                @endif
                            </div>

                            <!-- Aksi Bab di Sisi Kanan: Edit Bab, Hapus Bab & Panah Dropdown -->
                            <div class="flex items-center justify-between md:justify-end gap-2 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                <div class="flex items-center gap-1.5">
                                    <button @click.stop wire:click="openEditChapter({{ $chapter->id }})" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition cursor-pointer" 
                                            title="Edit Bab">
                                        <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        <span>Edit</span>
                                    </button>

                                    <button type="button" @click.stop="confirmDelete('Hapus Bab Ini?', 'Bab beserta seluruh materi, tugas, dan kuis di dalamnya akan dihapus.', () => $wire.hapusChapter({{ $chapter->id }}))" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer" 
                                            title="Hapus Bab">
                                        <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </button>
                                </div>

                                <div class="p-1 rounded-lg text-slate-400 group-hover:text-slate-600">
                                    <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Konten Bab (Collapsible) dengan Latar Kontras agar Tidak Menyatu -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="border-t-2 border-slate-100 bg-slate-50/70 p-3 sm:p-5 space-y-3">
                            
                            <!-- Header Isi Bab: Ringkasan Item & Tombol Aksi Tambah di Sebelah Kanan -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-slate-200/80">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Isi Pembelajaran</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-slate-600 border border-slate-200">
                                        {{ $chapter->materials->count() + $chapter->assignments->count() + $chapter->quizzes->count() }} Item
                                    </span>
                                </div>

                                <!-- Tombol Aksi Tambah di Kanan: Sederhana & Bersih -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button wire:click="openCreateMaterial({{ $chapter->id }})" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 shadow-2xs transition active:scale-95 cursor-pointer">
                                        <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        <span>Materi</span>
                                    </button>

                                    <button wire:click="openCreateAssignment({{ $chapter->id }})" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-white hover:bg-amber-50 border border-amber-200 shadow-2xs transition active:scale-95 cursor-pointer">
                                        <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        <span>Tugas</span>
                                    </button>

                                    <button wire:click="openCreateQuiz({{ $chapter->id }})" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-purple-800 bg-white hover:bg-purple-50 border border-purple-200 shadow-2xs transition active:scale-95 cursor-pointer">
                                        <svg class="h-3.5 w-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        <span>Kuis</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Daftar Konten Pembelajaran Bab (Materi, Tugas, Kuis) -->
                            <div class="space-y-2">
                                <!-- 1. List Materi Pembelajaran -->
                                @foreach ($chapter->materials as $mat)
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-3 p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200/90 hover:border-indigo-300 shadow-2xs transition">
                                        <div class="min-w-0 flex-1">
                                            <button wire:click="openEditMaterial({{ $mat->id }})" class="font-bold text-xs sm:text-sm text-slate-800 hover:text-indigo-600 transition text-left cursor-pointer break-words md:truncate block w-full" title="Klik untuk edit materi">
                                                {{ $mat->judul }}
                                            </button>
                                            <p class="text-[11px] font-bold mt-0.5 uppercase tracking-wide text-indigo-600">
                                                Materi <span class="text-slate-300 font-normal">&bull;</span> <span class="{{ $mat->tipe === 'video' ? 'text-sky-600' : ($mat->tipe === 'pdf' ? 'text-rose-600' : 'text-indigo-500') }} font-semibold">{{ $mat->tipe }}</span>
                                            </p>
                                        </div>
                                        <!-- Aksi CRUD Sebelah Kanan (Responsive di Mobile) -->
                                        <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                            <button wire:click="openEditMaterial({{ $mat->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition cursor-pointer" title="Edit Materi">
                                                <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" @click="confirmDelete('Hapus Materi Ini?', 'Materi pembelajaran ini akan dihapus dari bab.', () => $wire.hapusMaterial({{ $mat->id }}))" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer" title="Hapus Materi">
                                                <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- 2. List Tugas -->
                                @foreach ($chapter->assignments as $asg)
                                    @php
                                         $subCount = $asg->submissions->count();
                                        $gradedCount = $asg->submissions->where('status', 'graded')->count();
                                        $ungradedCount = $subCount - $gradedCount;
                                    @endphp
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-3 p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200/90 hover:border-amber-300 shadow-2xs transition">
                                        <div class="min-w-0 flex-1">
                                            <button wire:click="openGrading({{ $asg->id }})" class="font-bold text-xs sm:text-sm text-slate-800 hover:text-amber-700 transition text-left cursor-pointer break-words md:truncate block w-full" title="Periksa tugas siswa">
                                                {{ $asg->judul }}
                                            </button>
                                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-[11px] text-slate-500 mt-0.5">
                                                <span class="text-amber-600 font-bold uppercase tracking-wide">Tugas</span>
                                                <span class="text-slate-300 font-normal">&bull;</span>
                                                <span class="font-bold {{ $ungradedCount > 0 ? 'text-amber-800' : 'text-slate-600' }}">{{ $subCount }} dikumpulkan</span>
                                                @if($asg->deadline)
                                                    <span>&bull;</span>
                                                    <span class="{{ $asg->isLewatDeadline() ? 'text-rose-600 font-bold' : '' }}">Tenggat: {{ $asg->deadline->translatedFormat('d M H:i') }}</span>
                                                @endif
                                                @if($ungradedCount > 0)
                                                    <span class="px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-900 font-bold text-[10px]">{{ $ungradedCount }} perlu dinilai</span>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- Aksi CRUD Sebelah Kanan (Responsive di Mobile) -->
                                        <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 flex-wrap">
                                            <button wire:click="openGrading({{ $asg->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 transition cursor-pointer" title="Periksa & Beri Nilai">
                                                <svg class="h-3.5 w-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Nilai ({{ $subCount }})</span>
                                            </button>
                                            <button wire:click="openEditAssignment({{ $asg->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition cursor-pointer" title="Edit Tugas">
                                                <svg class="h-3.5 w-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" @click="confirmDelete('Hapus Tugas Ini?', 'Tugas beserta riwayat pengumpulan siswa akan dihapus.', () => $wire.hapusAssignment({{ $asg->id }}))" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer" title="Hapus Tugas">
                                                <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- 3. List Kuis -->
                                @foreach ($chapter->quizzes as $quiz)
                                    @php
                                        $completedAttempts = $quiz->attempts->where('status', 'selesai');
                                        $attemptCount = $completedAttempts->count();
                                    @endphp
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-3 p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200/90 hover:border-purple-300 shadow-2xs transition">
                                        <div class="min-w-0 flex-1">
                                            <button wire:click="openEditQuiz({{ $quiz->id }})" class="font-bold text-xs sm:text-sm text-slate-800 hover:text-purple-700 transition text-left cursor-pointer break-words md:truncate block w-full" title="Edit kuis & kelola soal">
                                                {{ $quiz->judul }}
                                            </button>
                                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-[11px] text-slate-500 mt-0.5">
                                                <span class="text-purple-600 font-bold uppercase tracking-wide">Kuis</span>
                                                <span class="text-slate-300 font-normal">&bull;</span>
                                                <span class="font-bold text-purple-700">{{ $quiz->questions->count() }} Soal</span>
                                                <span>&bull;</span>
                                                <span>⏱️ {{ $quiz->durasi_menit }} mnt</span>
                                                <span>&bull;</span>
                                                <span>KKM: <b class="text-slate-700">{{ $quiz->kkm }}</b></span>
                                            </div>
                                        </div>
                                        <!-- Aksi CRUD Sebelah Kanan (Responsive di Mobile) -->
                                        <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 flex-wrap">
                                            <button wire:click="openQuizResults({{ $quiz->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 transition cursor-pointer" title="Lihat Siswa yang Sudah Mengerjakan">
                                                <svg class="h-3.5 w-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Hasil ({{ $attemptCount }})</span>
                                            </button>
                                            <button wire:click="openEditQuiz({{ $quiz->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-purple-800 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition cursor-pointer" title="Edit Kuis & Kelola Soal">
                                                <svg class="h-3.5 w-3.5 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" @click="confirmDelete('Hapus Kuis Ini?', 'Kuis beserta riwayat hasil pengerjaan siswa akan dihapus.', () => $wire.hapusQuiz({{ $quiz->id }}))" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer" title="Hapus Kuis">
                                                <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($chapter->materials->count() === 0 && $chapter->assignments->count() === 0 && $chapter->quizzes->count() === 0)
                                <div class="py-6 rounded-xl bg-white text-center space-y-1.5 border border-dashed border-slate-300">
                                    <p class="text-xs text-slate-600 font-bold">Belum ada konten pembelajaran di bab ini</p>
                                    <p class="text-[11px] text-slate-400">Klik tombol <b>+ Materi</b>, <b>+ Tugas</b>, atau <b>+ Kuis</b> di atas untuk mulai menambahkan.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 bg-white rounded-3xl border border-slate-200 text-center space-y-3 shadow-sm">
                        <div class="h-16 w-16 mx-auto rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center border border-indigo-100">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h3 class="font-bold text-slate-800 text-base">Belum Ada Bab</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Mulai susun kurikulum kelas dengan menambahkan Bab pertama.</p>
                        <button wire:click="openCreateChapter" class="mt-2 inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-xs transition cursor-pointer">
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
                <div class="px-6 py-4 sm:py-5 border-b border-slate-100 bg-white">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Daftar Siswa</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pantau siswa yang bergabung dan tingkat progres belajarnya</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 text-center text-slate-400 font-bold">No</span>
                                        <span>Siswa</span>
                                    </div>
                                </th>
                                <th class="py-3 px-4 text-center">Bergabung</th>
                                <th class="py-3 px-4">Progres Materi</th>
                                <th class="py-3 px-4 text-center">Tugas Selesai</th>
                                <th class="py-3 px-4 text-center">Kuis Selesai</th>
                                <th class="py-3 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            @forelse ($siswaList as $s)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-6">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 text-center font-bold text-xs text-slate-400 shrink-0">{{ $loop->iteration }}.</span>
                                            <div class="h-8 w-8 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs uppercase shrink-0">
                                                {{ substr($s->name, 0, 2) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-900 truncate">{{ $s->name }}</p>
                                                <p class="text-[11px] text-slate-400 truncate">{{ $s->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-500 whitespace-nowrap">
                                        {{ $s->pivot->tanggal_gabung ? \Carbon\Carbon::parse($s->pivot->tanggal_gabung)->translatedFormat('d M Y') : '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="space-y-1.5 w-32">
                                            <div class="flex justify-between text-[11px] font-bold">
                                                <span class="text-slate-600 font-semibold">{{ $s->completed_materials }}/{{ $totalMaterialsCount }}</span>
                                                <span class="text-indigo-600">{{ $s->progres_persen }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $s->progres_persen }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold {{ $s->tugas_selesai > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-50 text-slate-500' }}">
                                            {{ $s->tugas_selesai }} / {{ $allAssignments->count() }} Tugas
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold {{ $s->kuis_selesai > 0 ? 'bg-purple-50 text-purple-800 border border-purple-200' : 'bg-slate-50 text-slate-500' }}">
                                            {{ $s->kuis_selesai }} / {{ $allQuizzes->count() }} Kuis
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                        <button type="button" @click="confirmDelete('Keluarkan Siswa?', 'Siswa {{ $s->name }} akan dikeluarkan dari kelas ini.', () => $wire.keluarkanSiswa({{ $s->id }}))"
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:text-rose-700 bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 transition cursor-pointer"
                                            title="Keluarkan dari kelas">
                                            Keluarkan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        <p class="font-bold text-slate-700 text-xs">Belum Ada Siswa yang Bergabung</p>
                                        <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">
                                            Bagikan kode kelas <b class="font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200">{{ $kelas->kode_kelas }}</b> ke siswa Anda.
                                        </p>
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
                <div class="px-6 py-4 sm:py-5 border-b border-slate-100 bg-white">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900">Penilaian Siswa</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pantau akumulasi nilai tugas, hasil kuis, dan rata-rata capaian belajar siswa</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4 sticky left-0 bg-slate-50 z-20 border-r border-slate-200/80 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-6 text-center text-slate-400 font-bold">No</span>
                                        <span>Siswa</span>
                                    </div>
                                </th>
                                <th class="py-2.5 px-3 text-center whitespace-nowrap">Nilai Akhir</th>
                                <th class="py-2.5 px-3 text-center whitespace-nowrap">Rata Tugas</th>
                                <th class="py-2.5 px-3 text-center whitespace-nowrap">Rata Kuis</th>
                                @foreach($allAssignments as $asg)
                                    <th class="py-2.5 px-3 text-center whitespace-nowrap text-amber-800 bg-amber-50/50 border-l border-slate-100 font-semibold" title="{{ $asg->judul }}">
                                        Tugas {{ $loop->iteration }}
                                    </th>
                                @endforeach
                                @foreach($allQuizzes as $qz)
                                    <th class="py-2.5 px-3 text-center whitespace-nowrap text-purple-800 bg-purple-50/50 border-l border-slate-100 font-semibold" title="{{ $qz->judul }}">
                                        Kuis {{ $loop->iteration }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium">
                            @forelse($siswaList as $s)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 sticky left-0 bg-white z-10 whitespace-nowrap border-r border-slate-200/80">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-6 text-center font-bold text-xs text-slate-400 shrink-0">{{ $loop->iteration }}.</span>
                                            <div>
                                                <p class="font-bold text-xs text-slate-900">{{ $s->name }}</p>
                                                <p class="text-[10px] text-slate-400 font-normal">{{ $s->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="font-extrabold text-xs {{ $s->nilai_akhir >= 75 ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ $s->nilai_akhir }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap text-slate-700 font-medium">
                                        {{ round($s->rata_rata_tugas) }}
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap text-slate-700 font-medium">
                                        {{ round($s->rata_rata_kuis) }}
                                    </td>

                                    <!-- Nilai per Tugas -->
                                    @foreach($allAssignments as $asg)
                                        @php
                                            $sub = $asg->submissions->firstWhere('user_id', $s->id);
                                        @endphp
                                        <td class="py-3 px-3 text-center whitespace-nowrap border-l border-slate-100">
                                            @if($sub && $sub->nilai !== null)
                                                <span class="font-bold {{ $sub->nilai >= 75 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $sub->nilai }}</span>
                                            @elseif($sub)
                                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">Terkumpul</span>
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
                                        <td class="py-3 px-3 text-center whitespace-nowrap border-l border-slate-100">
                                            @if($attempt)
                                                <span class="font-bold {{ $attempt->skor >= $qz->kkm ? 'text-emerald-700' : 'text-rose-600' }}">{{ round($attempt->skor) }}</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + $allAssignments->count() + $allQuizzes->count() }}" class="py-12 text-center text-slate-400 text-xs">
                                        <p class="font-bold text-slate-700 text-xs">Belum Ada Data Nilai Siswa</p>
                                        <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">
                                            Nilai tugas dan kuis siswa akan otomatis tercatat di sini.
                                        </p>
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
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-8 space-y-5 shadow-2xl border border-slate-100 my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-black text-lg text-slate-900">{{ $chapterId ? 'Edit Bab' : 'Tambah Bab Baru' }}</h3>
                    <button wire:click="$set('showChapterModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form wire:submit.prevent="simpanChapter" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Bab</label>
                        <input type="text" wire:model="chapterJudul" placeholder="Contoh: Bab 1 - Pengenalan Aljabar" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3">
                        @error('chapterJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Bab (Opsional)</label>
                        <textarea wire:model="chapterDeskripsi" rows="3" placeholder="Jelaskan ringkasan materi di bab ini..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 p-3"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showChapterModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 2: TAMBAH / EDIT MATERI (PDF / LINK / VIDEO / TEKS)   -->
    <!-- ============================================================ -->
    @if ($showMaterialModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-8 space-y-5 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto my-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-black text-lg text-slate-900">{{ $materialId ? 'Edit Materi' : 'Tambah Materi Baru' }}</h3>
                    <button wire:click="$set('showMaterialModal', false)" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form wire:submit.prevent="simpanMaterial" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Materi</label>
                        <input type="text" wire:model="materialJudul" placeholder="Contoh: Modul 1.1 Persamaan Linier" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-3">
                        @error('materialJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Pemilih Format Bahan Ajar Sederhana & Profesional -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Format</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-100/80 p-1.5 rounded-2xl">
                            <button type="button" wire:click="$set('materialTipe', 'pdf')" 
                                    class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer {{ $materialTipe === 'pdf' ? 'bg-white text-rose-700 shadow-xs border border-rose-200' : 'text-slate-600 hover:text-slate-900' }}">
                                <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>PDF</span>
                            </button>
                            <button type="button" wire:click="$set('materialTipe', 'video')" 
                                    class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer {{ $materialTipe === 'video' ? 'bg-white text-red-700 shadow-xs border border-red-200' : 'text-slate-600 hover:text-slate-900' }}">
                                <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                <span>YouTube</span>
                            </button>
                            <button type="button" wire:click="$set('materialTipe', 'teks')" 
                                    class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer {{ $materialTipe === 'teks' ? 'bg-white text-indigo-700 shadow-xs border border-indigo-200' : 'text-slate-600 hover:text-slate-900' }}">
                                <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Text</span>
                            </button>
                            <button type="button" wire:click="$set('materialTipe', 'link')" 
                                    class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer {{ $materialTipe === 'link' ? 'bg-white text-blue-700 shadow-xs border border-blue-200' : 'text-slate-600 hover:text-slate-900' }}">
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <span>Link</span>
                            </button>
                        </div>
                    </div>

                    <!-- 1. Form Khusus PDF -->
                    @if ($materialTipe === 'pdf')
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                            <div class="flex items-center gap-2 text-slate-800 font-bold text-xs">
                                <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Unggah Berkas PDF (Maksimal 20 MB)</span>
                            </div>
                            <input type="file" wire:model="materialFile" accept=".pdf" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-white hover:file:bg-slate-900 cursor-pointer">
                            <span wire:loading wire:target="materialFile" class="text-xs text-indigo-600 font-bold block">Mengunggah file PDF...</span>
                            @error('materialFile') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- 2. Form Khusus Video YouTube Sederhana & Profesional -->
                    @if ($materialTipe === 'video')
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                            <label class="block text-xs font-bold text-slate-800">Tautan Video YouTube</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                </div>
                                <input type="url" wire:model="materialUrl" placeholder="https://www.youtube.com/watch?v=..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 bg-white pl-9 pr-3 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Siswa dapat memutar video ini langsung di dalam aplikasi RuangKelas pada sistem operasi apa saja.</span>
                            </p>
                        </div>
                    @endif

                    <!-- 3. Form Khusus Link Eksternal -->
                    @if ($materialTipe === 'link')
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                            <label class="block text-xs font-bold text-slate-800">Alamat Tautan Sumber Belajar (URL)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </div>
                                <input type="url" wire:model="materialUrl" placeholder="https://..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 bg-white pl-9 pr-3 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <p class="text-[11px] text-slate-500">Tautan modul digital, jurnal, atau web interaktif (Google Docs, GeoGebra, Wikipedia, dsb).</p>
                        </div>
                    @endif

                    <!-- Penjelasan / Konten Bacaan Teks -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
                        <textarea wire:model="materialKonten" rows="4" placeholder="Tuliskan catatan penting atau instruksi untuk siswa..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-3"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showMaterialModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 3: TAMBAH / EDIT Tugas (ASSIGNMENT)                     -->
    <!-- ============================================================ -->
    @if ($showAssignmentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full p-5 sm:p-7 space-y-5 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto my-auto">
                <!-- Header Modal -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="font-black text-lg text-slate-900">{{ $assignmentId ? 'Edit Tugas' : 'Tambah Tugas Baru' }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Kelola penugasan siswa untuk bab ini</p>
                    </div>
                    <button wire:click="$set('showAssignmentModal', false)" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="simpanAssignment" class="space-y-4">
                    <!-- Judul Tugas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Tugas</label>
                        <input type="text" wire:model="assignmentJudul" placeholder="Contoh: Latihan Soal Bab 1" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-3">
                        @error('assignmentJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tenggat & Poin -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tenggat Waktu (Deadline)</label>
                            <input type="datetime-local" wire:model="assignmentDeadline" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Poin Maksimal</label>
                            <input type="number" wire:model="assignmentPoin" min="1" max="100" placeholder="100" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5">
                        </div>
                    </div>

                    <!-- Instruksi Tugas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Instruksi Tugas</label>
                        <textarea wire:model="assignmentDeskripsi" rows="3" placeholder="Tuliskan petunjuk pengerjaan tugas untuk siswa..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-3"></textarea>
                    </div>

                    <!-- Lampiran & Link Referensi Bersih & Profesional -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span>Lampiran Berkas (Opsional)</span>
                            </label>
                            <input type="file" wire:model="assignmentFile" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-white hover:file:bg-slate-900 cursor-pointer">
                            <span wire:loading wire:target="assignmentFile" class="text-xs text-amber-600 font-bold block mt-1">Mengunggah berkas...</span>
                        </div>

                        <div class="pt-2.5 border-t border-slate-200">
                            <label class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <span>Tautan Referensi (Opsional)</span>
                            </label>
                            <input type="url" wire:model="assignmentUrl" placeholder="https://drive.google.com/..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 bg-white p-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showAssignmentModal', false)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition active:scale-95 cursor-pointer">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showGradingModal && $selectedAssignment)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-2.5 sm:p-4">
            <div class="bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-100 max-h-[92vh] flex flex-col overflow-hidden my-auto">

                {{-- Header Modal Penilaian Tugas (Sederhana & Bersih) --}}
                <div class="px-6 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0 bg-white">
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">
                            Penilaian: {{ $selectedAssignment->judul }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            @if($selectedAssignment->deadline)
                                <span class="{{ $selectedAssignment->isLewatDeadline() ? 'text-rose-500 font-bold' : '' }}">
                                    Tenggat: {{ $selectedAssignment->deadline->translatedFormat('d M Y, H:i') }}
                                </span>
                                <span>&bull;</span>
                            @endif
                            <span>Maks {{ $selectedAssignment->poin_maksimal }} Poin</span>
                            <span>&bull;</span>
                            <span>{{ $selectedAssignment->submissions->count() }} Siswa Mengumpulkan</span>
                        </p>
                    </div>

                    <button wire:click="$set('showGradingModal', false)" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer shrink-0" title="Tutup">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Instruksi Tugas (collapsible sederhana) --}}
                @if ($selectedAssignment->deskripsi || $selectedAssignment->file_lampiran || $selectedAssignment->url_referensi)
                    <div x-data="{ open: false }" class="px-6 py-2.5 border-b border-slate-100 bg-slate-50/50 shrink-0 text-xs">
                        <button type="button" @click="open = !open" class="flex items-center justify-between text-slate-700 hover:text-slate-900 font-bold transition cursor-pointer w-full text-left">
                            <span>Instruksi Tugas</span>
                            <span class="text-slate-400 font-medium text-[11px]" x-text="open ? 'Tutup ▲' : 'Lihat ▼'"></span>
                        </button>
                        <div x-show="open" x-cloak class="pt-2.5 space-y-2 text-slate-600 text-xs">
                            @if ($selectedAssignment->deskripsi)
                                <p class="whitespace-pre-line leading-relaxed bg-white p-3 rounded-xl border border-slate-200">{{ $selectedAssignment->deskripsi }}</p>
                            @endif
                            <div class="flex flex-wrap gap-2">
                                @if ($selectedAssignment->file_lampiran)
                                    <a href="{{ asset('storage/' . $selectedAssignment->file_lampiran) }}" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition">
                                        Unduh Berkas Soal
                                    </a>
                                @endif
                                @if ($selectedAssignment->url_referensi)
                                    <a href="{{ $selectedAssignment->url_referensi }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-indigo-600 font-bold hover:bg-slate-50 transition">
                                        Tautan Referensi ↗
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('grading_success'))
                    <div class="mx-6 mt-3 p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200 shrink-0 flex items-center justify-between">
                        <span>✓ {{ session('grading_success') }}</span>
                        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">&times;</button>
                    </div>
                @endif

                {{-- Body: 2 Kolom --}}
                <div class="flex flex-1 overflow-hidden min-h-0">

                    {{-- Kiri: Daftar Siswa yang Mengumpulkan --}}
                    <div class="w-64 sm:w-72 shrink-0 border-r border-slate-200/80 bg-slate-50/40 flex flex-col overflow-hidden">
                        <div class="px-4 py-2.5 border-b border-slate-200/80 flex items-center justify-between shrink-0 bg-white">
                            <span class="text-xs font-bold text-slate-700">Daftar Siswa</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                {{ $selectedAssignment->submissions->count() }}
                            </span>
                        </div>
                        <div class="overflow-y-auto flex-1 p-2 space-y-1.5">
                            @forelse($selectedAssignment->submissions as $sub)
                                <button type="button" wire:click="pilihSubmission({{ $sub->id }})"
                                     class="w-full text-left p-2.5 rounded-xl transition cursor-pointer border {{ $gradingSubmissionId === $sub->id ? 'bg-white border-amber-500 ring-2 ring-amber-500/20 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300' }}">
                                    <div class="flex items-center justify-between gap-1.5">
                                        <p class="font-bold text-xs text-slate-800 truncate">{{ $sub->user->name }}</p>
                                        @if($sub->nilai !== null)
                                            <span class="text-[10px] font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded shrink-0">{{ $sub->nilai }}</span>
                                        @else
                                            <span class="text-[9px] font-bold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded shrink-0">Belum</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $sub->submitted_at->translatedFormat('d M, H:i') }}</p>
                                </button>
                            @empty
                                <div class="text-center py-10 px-3 text-slate-400 text-xs">
                                    <p class="font-bold text-slate-600">Belum Ada Pengumpulan</p>
                                    <p class="text-[10px] mt-0.5">Tugas siswa yang dikumpulkan akan muncul di sini.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Kanan: Detail & Form Penilaian --}}
                    <div class="flex-1 overflow-y-auto p-5 sm:p-6 bg-white">
                        @if($gradingSubmissionId)
                            @php $currentSub = $selectedAssignment->submissions->firstWhere('id', $gradingSubmissionId); @endphp
                            @if($currentSub)
                                <div class="space-y-4">

                                    {{-- Info Siswa --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-900">{{ $currentSub->user->name }}</h4>
                                            <p class="text-xs text-slate-400">{{ $currentSub->user->email }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">Dikumpulkan</p>
                                            <p class="text-xs font-bold text-slate-700">{{ $currentSub->submitted_at->translatedFormat('d M Y, H:i') }}</p>
                                        </div>
                                    </div>

                                    {{-- Berkas Upload Siswa --}}
                                    @if($currentSub->file_jawaban)
                                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Berkas Tugas Siswa</p>
                                                <p class="text-xs text-slate-800 font-mono truncate font-semibold mt-0.5">{{ basename($currentSub->file_jawaban) }}</p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <a href="{{ asset('storage/' . $currentSub->file_jawaban) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition">Lihat</a>
                                                <a href="{{ asset('storage/' . $currentSub->file_jawaban) }}" download class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition">Unduh</a>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Tautan Tugas --}}
                                    @if($currentSub->link_tugas)
                                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tautan Tugas</p>
                                                <p class="text-xs text-indigo-600 truncate font-medium mt-0.5">{{ $currentSub->link_tugas }}</p>
                                            </div>
                                            <a href="{{ $currentSub->link_tugas }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shrink-0">Buka ↗</a>
                                        </div>
                                    @endif

                                    {{-- Catatan Siswa --}}
                                    @if($currentSub->catatan_siswa)
                                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Catatan Siswa</p>
                                            <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">{{ $currentSub->catatan_siswa }}</p>
                                        </div>
                                    @endif

                                    {{-- Form Nilai --}}
                                    <form wire:submit.prevent="simpanNilai" class="pt-3 border-t border-slate-100 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                                    Nilai Skor <span class="text-slate-400 font-normal">(Maks: {{ $selectedAssignment->poin_maksimal }})</span>
                                                </label>
                                                <input type="number" wire:model="inputNilai" min="0" max="{{ $selectedAssignment->poin_maksimal }}" placeholder="0"
                                                    class="w-full text-base font-bold rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5 bg-white text-slate-900">
                                                @error('inputNilai') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                                            </div>
                                            @if($currentSub->nilai !== null)
                                                <div class="text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-2.5 rounded-xl border border-emerald-200 flex items-center gap-1.5">
                                                    <span>✓ Sudah dinilai:</span>
                                                    <span class="text-sm font-black">{{ $currentSub->nilai }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-800 mb-1">Catatan / Evaluasi Guru <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                            <textarea wire:model="inputCatatanGuru" rows="3" placeholder="Tuliskan catatan apresiasi atau evaluasi perbaikan untuk siswa..."
                                                class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5 bg-white text-slate-800"></textarea>
                                        </div>
                                        <div class="flex justify-end gap-2 pt-1">
                                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition cursor-pointer">
                                                Simpan
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            @endif
                        @else
                            <div class="h-full min-h-[280px] flex flex-col items-center justify-center text-center p-6 text-slate-400 space-y-1">
                                <p class="text-xs font-bold text-slate-700">Pilih siswa dari daftar</p>
                                <p class="text-[11px] text-slate-400">Pilih salah satu siswa di sebelah kiri untuk melihat tugas dan memberikan nilai.</p>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Footer Modal Penilaian --}}
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-end text-xs text-slate-600 shrink-0">
                    <button type="button" wire:click="$set('showGradingModal', false)" class="px-4 py-2 rounded-xl font-bold text-slate-700 hover:bg-slate-200/80 bg-white border border-slate-200 transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- MODAL 5: TAMBAH / EDIT KUIS & KELOLA SOAL (SEDERHANA & MUDAH) -->
    <!-- ============================================================ -->
    @if ($showQuizModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-2.5 sm:p-4">
            <div class="bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-100 max-h-[92vh] flex flex-col overflow-hidden my-auto">
                
                {{-- Header Utama Modal (Sederhana & Bersih) --}}
                <div class="px-6 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0 bg-white">
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">
                            {{ $quizJudul ?: 'Kuis' }}
                        </h3>
                        @if($quizId && $activeEditingQuiz)
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                ⏱️ {{ $quizDurasi }} Menit &bull; KKM: {{ $quizKkm }}
                            </p>
                        @endif
                    </div>

                    <button wire:click="tutupQuizModal" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer shrink-0" title="Tutup">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Navigasi Tab Jelas (Full Width, Sangat Mudah Diklik & Dipahami) --}}
                <div class="grid grid-cols-2 border-b border-slate-200 bg-slate-50/80 text-xs font-bold shrink-0">
                    <button type="button" wire:click="switchQuizTab('info')" 
                        class="py-3 px-4 flex items-center justify-center gap-2 border-b-2 transition cursor-pointer {{ $quizTab === 'info' ? 'border-purple-600 text-purple-800 bg-white font-extrabold shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/50' }}">
                        <span>1. Info Kuis</span>
                    </button>
                    <button type="button" wire:click="switchQuizTab('soal')" 
                        class="py-3 px-4 flex items-center justify-center gap-2 border-b-2 transition cursor-pointer {{ $quizTab === 'soal' ? 'border-purple-600 text-purple-800 bg-white font-extrabold shadow-2xs' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100/50' }}">
                        <span>2. Kelola Soal</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $quizTab === 'soal' ? 'bg-purple-100 text-purple-800' : 'bg-slate-200 text-slate-600' }}">
                            {{ $activeEditingQuiz ? $activeEditingQuiz->questions->count() : 0 }} Soal
                        </span>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="flex-1 overflow-y-auto p-5 sm:p-6 {{ $quizTab === 'soal' ? 'bg-slate-50/50' : 'bg-white' }}">
                    
                    {{-- ---------------------------------------------------- --}}
                    {{-- TAB 1: INFORMASI / PENGATURAN KUIS                   --}}
                    {{-- ---------------------------------------------------- --}}
                    @if ($quizTab === 'info')
                        <div class="max-w-xl mx-auto space-y-4">
                            @if(session('quiz_success'))
                                <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200 flex items-center justify-between">
                                    <span>✓ {{ session('quiz_success') }}</span>
                                    <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">&times;</button>
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 mb-1">Judul Kuis</label>
                                    <input type="text" wire:model.live.debounce.300ms="quizJudul" placeholder="Contoh: Kuis Harian 1 — Operasi Aljabar"
                                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 p-3 bg-white text-slate-800 font-medium">
                                    @error('quizJudul') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-1">Durasi Pengerjaan (Menit)</label>
                                        <input type="number" wire:model="quizDurasi" min="1" max="300"
                                            class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 p-2.5 bg-white text-slate-800 font-semibold">
                                        @error('quizDurasi') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-1">Nilai KKM</label>
                                        <input type="number" wire:model="quizKkm" min="0" max="100"
                                            class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 p-2.5 bg-white text-slate-800 font-semibold">
                                        @error('quizKkm') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-800 mb-1">Petunjuk <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <textarea wire:model="quizDeskripsi" rows="3" placeholder="Tuliskan petunjuk pengerjaan kuis untuk siswa..."
                                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 p-3 bg-white text-slate-800"></textarea>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2.5">
                                    <input type="checkbox" id="quizAcak" wire:model="quizAcak" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4 cursor-pointer">
                                    <label for="quizAcak" class="text-xs font-bold text-slate-700 cursor-pointer select-none">
                                        Acak urutan butir soal saat dikerjakan oleh siswa
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ---------------------------------------------------- --}}
                    {{-- TAB 2: KELOLA BUTIR SOAL KUIS (PG & ESAI)            --}}
                    {{-- ---------------------------------------------------- --}}
                    @if ($quizTab === 'soal')
                        @if (!$quizId || !$activeEditingQuiz)
                            <div class="max-w-md mx-auto py-12 text-center space-y-3">
                                <p class="text-sm font-bold text-slate-700">Simpan informasi kuis terlebih dahulu untuk mengelola soal.</p>
                                <button type="button" wire:click="switchQuizTab('info')" class="px-4 py-2 bg-purple-700 text-white rounded-xl text-xs font-bold hover:bg-purple-800 transition cursor-pointer">
                                    ← Kembali ke Info Kuis
                                </button>
                            </div>
                        @else
                            @if(session('soal_success'))
                                <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200 flex items-center justify-between">
                                    <span>✓ {{ session('soal_success') }}</span>
                                    <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">&times;</button>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                                
                                <!-- Kolom Kiri: Form Input / Edit Butir Soal (5 Kolom) -->
                                <div class="lg:col-span-5 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-3.5">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-900">
                                                {{ $questionId ? 'Edit Soal' : 'Tambah Soal' }}
                                            </h4>
                                            <p class="text-[11px] text-slate-400">
                                                {{ $questionId ? 'Perbarui butir pertanyaan' : 'Pilih tipe soal dan tulis pertanyaan' }}
                                            </p>
                                        </div>
                                        @if($questionId)
                                            <button type="button" wire:click="resetSoalForm" class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 cursor-pointer">
                                                ✕ Batal
                                            </button>
                                        @endif
                                    </div>

                                <form wire:submit.prevent="simpanSoal" class="space-y-3">
                                    <!-- Pilihan Tipe Soal: Pilihan Ganda atau Esai -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Tipe Soal</label>
                                        <div class="p-1 rounded-xl bg-slate-100 border border-slate-200/80 grid grid-cols-2 gap-1 text-xs">
                                            <button type="button" wire:click="setSoalTipe('pilihan_ganda')"
                                                class="py-2 px-3 rounded-lg font-bold transition text-center cursor-pointer {{ $soalTipe === 'pilihan_ganda' ? 'bg-white text-purple-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                                Pilihan Ganda
                                            </button>
                                            <button type="button" wire:click="setSoalTipe('essay')"
                                                class="py-2 px-3 rounded-lg font-bold transition text-center cursor-pointer {{ $soalTipe === 'essay' ? 'bg-white text-purple-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                                Esai
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-1">
                                            Pertanyaan Soal
                                        </label>
                                        <textarea wire:model="soalTeks" rows="3" placeholder="{{ $soalTipe === 'essay' ? 'Tuliskan pertanyaan esai di sini...' : 'Tuliskan butir soal pilihan ganda di sini...' }}"
                                            class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 focus:border-purple-600 focus:ring-1 focus:ring-purple-600 p-2.5 bg-white text-slate-800"></textarea>
                                        @error('soalTeks') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-1">Bobot Poin</label>
                                        <input type="number" wire:model="soalBobot" min="1" max="100" class="w-full text-xs font-bold rounded-xl border border-slate-300 p-2 focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                                        @error('soalBobot') <span class="text-xs text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Opsi Pilihan Jawaban A, B, C, D (Khusus Pilihan Ganda) -->
                                    @if ($soalTipe === 'pilihan_ganda')
                                        <div class="space-y-2 pt-1 border-t border-slate-100">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-xs font-bold text-slate-800">Pilihan Jawaban</label>
                                                <span class="text-[10px] text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                    Klik Radio = Kunci Benar
                                                </span>
                                            </div>

                                            @foreach ($soalOptions as $idx => $opt)
                                                <div class="flex items-center gap-2 p-2 rounded-xl border transition {{ $opt['is_benar'] ? 'border-emerald-400 bg-emerald-50/70 font-semibold shadow-2xs' : 'border-slate-200 bg-white' }}">
                                                    <input type="radio" name="kunci_benar_radio" wire:click="setJawabanBenar({{ $idx }})" {{ $opt['is_benar'] ? 'checked' : '' }}
                                                        class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 cursor-pointer shrink-0" title="Pilih Sebagai Kunci Benar">
                                                    <span class="font-extrabold text-xs w-4 text-center shrink-0 {{ $opt['is_benar'] ? 'text-emerald-700' : 'text-slate-500' }}">
                                                        {{ $opt['label'] }}.
                                                    </span>
                                                    <input type="text" wire:model="soalOptions.{{ $idx }}.teks" placeholder="Pilihan {{ $opt['label'] }}"
                                                        class="flex-1 text-xs rounded-lg border border-slate-200 p-1.5 focus:border-purple-500 focus:ring-purple-500 {{ $opt['is_benar'] ? 'bg-white font-bold' : '' }}">
                                                    @if($opt['is_benar'])
                                                        <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded shrink-0">Kunci</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @error('soalOptions.0.teks') <span class="text-xs text-rose-500 block font-semibold">{{ $message }}</span> @enderror
                                            @error('soalOptions.1.teks') <span class="text-xs text-rose-500 block font-semibold">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        <!-- Info Khusus Soal Esai -->
                                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                            <p class="text-[11px] leading-relaxed">
                                                <b>Soal Esai:</b> Siswa akan menjawab pertanyaan ini dalam bentuk uraian teks. Nilai diberikan setelah guru memeriksa jawaban.
                                            </p>
                                        </div>
                                    @endif

                                    <div class="pt-1.5">
                                        <button type="submit" class="w-full py-2.5 px-4 bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center justify-center gap-2">
                                            @if($questionId)
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>Simpan</span>
                                            @else
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                <span>Tambahkan Soal ke Kuis</span>
                                            @endif
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Kolom Kanan: Daftar Butir Soal Tersimpan (7 Kolom) -->
                            <div class="lg:col-span-7 space-y-2.5">
                                <div class="flex items-center justify-between px-1">
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">
                                        Daftar Soal Tersimpan ({{ $activeEditingQuiz->questions->count() }})
                                    </h4>
                                    <span class="text-xs font-bold text-slate-600">
                                        Total: <b class="text-purple-700">{{ $activeEditingQuiz->questions->sum('bobot') }} Poin</b>
                                    </span>
                                </div>

                                <div class="space-y-2.5 max-h-[56vh] overflow-y-auto pr-1">
                                    @forelse ($activeEditingQuiz->questions as $i => $q)
                                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200/90 shadow-2xs space-y-2 hover:border-purple-200 transition {{ $questionId === $q->id ? 'ring-2 ring-purple-500 bg-purple-50/20' : '' }}">
                                            <div class="flex items-start justify-between gap-2.5">
                                                <div class="flex items-start gap-2 min-w-0">
                                                    <span class="h-5 w-5 rounded-md bg-purple-100 text-purple-800 font-extrabold text-[11px] flex items-center justify-center shrink-0 mt-0.5">
                                                        {{ $i + 1 }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5 mb-0.5">
                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase tracking-wider {{ $q->tipe === 'essay' ? 'bg-emerald-100 text-emerald-800' : 'bg-purple-100 text-purple-700' }}">
                                                                {{ $q->tipe === 'essay' ? 'Esai' : 'PG' }}
                                                            </span>
                                                            <span class="text-[10px] font-bold text-slate-500">
                                                                &bull; {{ $q->bobot }} Poin
                                                            </span>
                                                        </div>
                                                        <p class="font-bold text-xs sm:text-sm text-slate-900 leading-snug">
                                                            {{ $q->pertanyaan }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Aksi Edit & Hapus Butir Soal -->
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" wire:click="editSoal({{ $q->id }})" class="p-1.5 rounded-lg text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition cursor-pointer" title="Edit Soal Ini">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                    <button type="button" @click="confirmDelete('Hapus Butir Soal?', 'Soal ini akan dihapus permanen dari kuis.', () => $wire.hapusSoal({{ $q->id }}))" class="p-1.5 rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer" title="Hapus Soal">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Tampilan Konten Khusus PG vs Esai -->
                                            @if ($q->tipe === 'pilihan_ganda')
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pt-0.5 text-xs">
                                                    @foreach ($q->options as $o)
                                                        <div class="px-2 py-1.5 rounded-lg border flex items-center justify-between gap-1.5 {{ $o->is_benar ? 'bg-emerald-50 text-emerald-900 border-emerald-300 font-bold' : 'bg-slate-50/70 text-slate-700 border-slate-200' }}">
                                                            <div class="flex items-center gap-1 min-w-0">
                                                                <span class="w-3.5 font-bold {{ $o->is_benar ? 'text-emerald-700' : 'text-slate-500' }}">{{ $o->label }}.</span>
                                                                <span class="truncate text-[11px]">{{ $o->teks }}</span>
                                                            </div>
                                                            @if($o->is_benar)
                                                                <span class="text-emerald-600 font-extrabold text-xs shrink-0">✓</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200/80 text-[11px] text-slate-500 italic">
                                                    Jawaban berupa uraian teks esai dari siswa
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="py-10 bg-white rounded-2xl border border-dashed border-slate-300 text-center space-y-1.5 p-5">
                                            <div class="h-10 w-10 mx-auto rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-lg">
                                                📝
                                            </div>
                                            <h5 class="font-bold text-slate-800 text-xs">Belum Ada Soal Ditambahkan</h5>
                                            <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                                                Pilih tipe soal (PG / Esai), isi pertanyaan di formulir sebelah kiri, lalu klik <b>Tambahkan Soal ke Kuis</b>.
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                        @endif
                    @endif

                </div>

                {{-- Footer Modal Kuis (Semua Aksi Sejajar di Kanan) --}}
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-2 text-xs text-slate-600 shrink-0">
                    @if ($quizTab === 'soal')
                        <button type="button" wire:click="switchQuizTab('info')" class="px-3.5 py-2 rounded-xl font-bold text-slate-700 hover:bg-slate-200/80 bg-white border border-slate-200 transition cursor-pointer flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            <span>Info Kuis</span>
                        </button>
                    @endif

                    <button type="button" wire:click="tutupQuizModal" class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:bg-slate-200/80 bg-white border border-slate-200 transition cursor-pointer">
                        Batal
                    </button>

                    @if ($quizTab === 'info')
                        @if ($quizId)
                            <button type="button" wire:click="simpanQuiz(false)" class="px-4 py-2 rounded-xl font-bold bg-slate-800 text-white hover:bg-slate-900 transition cursor-pointer">
                                Simpan
                            </button>
                        @endif
                        <button type="button" wire:click="simpanQuiz(true)" class="px-5 py-2 rounded-xl font-bold bg-purple-700 text-white hover:bg-purple-800 shadow-xs transition cursor-pointer flex items-center gap-1.5">
                            <span>{{ $quizId ? 'Lanjut ke Soal' : 'Simpan & Lanjut ke Soal' }}</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    @else
                        <button type="button" wire:click="selesaiKuis" class="px-5 py-2 rounded-xl font-bold bg-purple-700 text-white hover:bg-purple-800 shadow-xs transition cursor-pointer">
                            Simpan
                        </button>
                    @endif
                </div>

            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL 6: HASIL SISWA YANG MENGERJAKAN KUIS                   -->
    <!-- ============================================================ -->
    @if ($showQuizResultsModal && $selectedQuizId && $activeResultsQuiz)
        @php
            $attemptsSelesai = $activeResultsQuiz->attempts->where('status', 'selesai');
            $totalMengerjakan = $attemptsSelesai->count();
            $avgSkor = $totalMengerjakan > 0 ? round($attemptsSelesai->avg('skor')) : 0;
            $totalTuntas = $attemptsSelesai->where('skor', '>=', $activeResultsQuiz->kkm)->count();
            $totalBelumTuntas = $totalMengerjakan - $totalTuntas;
        @endphp

        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex min-h-full items-center justify-center p-2.5 sm:p-4">
            <div class="bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-100 max-h-[92vh] flex flex-col overflow-hidden my-auto">
                
                {{-- Header Modal Hasil Siswa (Sederhana & Bersih) --}}
                <div class="px-6 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0 bg-white">
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 truncate">
                            Hasil Kuis: {{ $activeResultsQuiz->judul }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            ⏱️ {{ $activeResultsQuiz->durasi_menit }} Menit &bull; KKM: {{ $activeResultsQuiz->kkm }} &bull; {{ $activeResultsQuiz->questions->count() }} Soal
                        </p>
                    </div>

                    <button wire:click="$set('showQuizResultsModal', false)" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer shrink-0" title="Tutup">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Konten Hasil Siswa --}}
                <div class="flex-1 overflow-y-auto p-4 sm:p-5 bg-slate-50/50 space-y-4">
                    
                    @if(session('hasil_success'))
                        <div class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold border border-emerald-200 flex items-center justify-between">
                            <span>✓ {{ session('hasil_success') }}</span>
                            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">&times;</button>
                        </div>
                    @endif

                    {{-- Ringkasan Statistik 4 Kotak Bersih --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-medium">Total Mengerjakan</p>
                            <h4 class="text-lg font-bold text-slate-900 mt-0.5">{{ $totalMengerjakan }} Siswa</h4>
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-medium">Rata-Rata Nilai</p>
                            <h4 class="text-lg font-bold text-purple-700 mt-0.5">{{ $avgSkor }}</h4>
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-medium">Tuntas (&ge; {{ $activeResultsQuiz->kkm }})</p>
                            <h4 class="text-lg font-bold text-emerald-700 mt-0.5">{{ $totalTuntas }} Siswa</h4>
                        </div>
                        <div class="bg-white p-3 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 font-medium">Remedial</p>
                            <h4 class="text-lg font-bold text-rose-600 mt-0.5">{{ $totalBelumTuntas }} Siswa</h4>
                        </div>
                    </div>

                    {{-- Tabel Siswa yang Mengerjakan --}}
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">Daftar Pengerjaan Siswa</h4>
                            <span class="text-xs text-slate-500">Standar KKM: <b class="text-slate-800">{{ $activeResultsQuiz->kkm }}</b></span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="py-2.5 px-4">Siswa</th>
                                        <th class="py-2.5 px-3 text-center">Waktu</th>
                                        <th class="py-2.5 px-3 text-center">Akurasi</th>
                                        <th class="py-2.5 px-3 text-center">Skor</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse ($attemptsSelesai as $att)
                                        @php
                                            $isPassed = $att->skor >= $activeResultsQuiz->kkm;
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-7 w-7 rounded-lg bg-purple-100 text-purple-800 font-bold flex items-center justify-center text-xs uppercase shrink-0">
                                                        {{ substr($att->user->name ?? 'S', 0, 2) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-slate-900 truncate">{{ $att->user->name ?? 'Siswa' }}</p>
                                                        <p class="text-[10px] text-slate-400 truncate">{{ $att->user->email ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center text-slate-600">
                                                <p class="font-semibold">{{ $att->completed_at ? $att->completed_at->format('d M Y') : '-' }}</p>
                                                <p class="text-[10px] text-slate-400">{{ $att->completed_at ? $att->completed_at->format('H:i') . ' WIB' : '-' }}</p>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[11px]">
                                                    {{ $att->total_benar ?? 0 }} Benar
                                                </span>
                                                <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded text-[11px] ml-1">
                                                    {{ $att->total_salah ?? 0 }} Salah
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <span class="text-sm font-black {{ $isPassed ? 'text-emerald-700' : 'text-rose-600' }}">
                                                    {{ round($att->skor) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                @if($isPassed)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                        Lulus
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                        Remedial
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <button type="button" @click="confirmDelete('Reset Percobaan Kuis?', 'Hasil pengerjaan kuis siswa ini akan direset sehingga siswa dapat mengerjakan ulang.', () => $wire.hapusAttempt({{ $att->id }}))"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-slate-700 hover:text-rose-700 bg-slate-100 hover:bg-rose-50 border border-slate-200 transition cursor-pointer"
                                                    title="Reset Percobaan">
                                                    Reset
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-8 text-center text-slate-400">
                                                <p class="font-bold text-slate-700 text-xs">Belum Ada Siswa yang Mengerjakan</p>
                                                <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">
                                                    Hasil pengerjaan kuis siswa akan otomatis tercatat di sini.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Footer Modal Hasil Siswa --}}
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-end text-xs text-slate-600 shrink-0">
                    <button type="button" wire:click="$set('showQuizResultsModal', false)" class="px-4 py-2 rounded-xl font-bold text-slate-700 hover:bg-slate-200/80 bg-white border border-slate-200 transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL KONFIRMASI HAPUS (CLEAN, SEDERHANA & PROFESIONAL)      -->
    <!-- ============================================================ -->
    <div x-show="deleteModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         style="display: none;">
        
        <div @click.away="deleteModal = false"
             x-show="deleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 text-center space-y-4">
            
            <!-- Ikon Hapus Lembut -->
            <div class="h-12 w-12 mx-auto rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <!-- Teks Konfirmasi -->
            <div class="space-y-1">
                <h4 class="font-extrabold text-base text-slate-900" x-text="deleteTitle"></h4>
                <p class="text-xs text-slate-500 leading-relaxed max-w-xs mx-auto" x-text="deleteMessage"></p>
            </div>

            <!-- Tombol Batal & Hapus -->
            <div class="flex items-center gap-2.5 pt-2">
                <button type="button" @click="deleteModal = false"
                        class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 active:scale-95 text-slate-700 text-xs font-bold transition cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="doDelete()"
                        class="flex-1 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 active:scale-95 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
