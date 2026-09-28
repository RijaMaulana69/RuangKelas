<?php

use App\Models\Kelas;
use App\Models\Chapter;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Enrollment;
use App\Models\Progress;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component {
    public int $kelasId;
    public string $activeTab = 'kurikulum'; // 'kurikulum' atau 'siswa'

    // State Tambah / Edit Chapter
    public bool $showChapterModal = false;
    public ?int $chapterId = null;
    #[Validate('required|string|max:150')]
    public string $chapterJudul = '';
    #[Validate('nullable|string')]
    public string $chapterDeskripsi = '';

    // State Tambah / Edit Material
    public bool $showMaterialModal = false;
    public ?int $targetChapterId = null;
    public ?int $materialId = null;
    #[Validate('required|string|max:150')]
    public string $materialJudul = '';
    #[Validate('required|in:teks,video,pdf,link')]
    public string $materialTipe = 'teks';
    public string $materialKonten = '';
    public string $materialUrl = '';
    public ?int $materialDurasi = 15;

    // State Tambah / Edit Quiz
    public bool $showQuizModal = false;
    public ?int $quizId = null;
    #[Validate('required|string|max:150')]
    public string $quizJudul = '';
    public string $quizDeskripsi = '';
    public int $quizDurasi = 30;
    public int $quizKkm = 70;
    public bool $quizAcak = false;

    // State Kelola Soal Quiz
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
        $kelasModel = Kelas::where('guru_id', auth()->id())->findOrFail($this->kelasId);
    }

    // CHAPTER ACTIONS
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
        session()->flash('success', 'Bab beserta materi dan kuisnya telah dihapus.');
    }

    // MATERIAL ACTIONS
    public function openCreateMaterial(int $chapterId): void
    {
        $this->targetChapterId = $chapterId;
        $this->reset(['materialId', 'materialJudul', 'materialKonten', 'materialUrl']);
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
        $this->showMaterialModal = true;
    }

    public function simpanMaterial(): void
    {
        $this->validate([
            'materialJudul' => 'required|string|max:150',
            'materialTipe' => 'required|in:teks,video,pdf,link',
        ]);

        if ($this->materialId) {
            $mat = Material::findOrFail($this->materialId);
            $mat->update([
                'judul' => $this->materialJudul,
                'tipe' => $this->materialTipe,
                'konten' => $this->materialKonten,
                'url' => $this->materialUrl,
                'durasi_menit' => $this->materialDurasi,
            ]);
            session()->flash('success', 'Materi berhasil diperbarui!');
        } else {
            $lastOrder = Material::where('chapter_id', $this->targetChapterId)->max('urutan') ?? 0;
            Material::create([
                'chapter_id' => $this->targetChapterId,
                'judul' => $this->materialJudul,
                'tipe' => $this->materialTipe,
                'konten' => $this->materialKonten,
                'url' => $this->materialUrl,
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

    // QUIZ ACTIONS
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

    // QUESTION ACTIONS
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

    public function with(): array
    {
        $kelas = Kelas::where('guru_id', auth()->id())
            ->with([
                'chapters.materials',
                'chapters.quizzes.questions.options',
                'siswa',
            ])
            ->findOrFail($this->kelasId);

        // Hitung total materi untuk perhitungan progres siswa
        $allMaterialIds = Material::whereHas('chapter', function ($q) {
            $q->where('class_id', $this->kelasId);
        })->pluck('id');

        $totalMaterialsCount = $allMaterialIds->count();

        // Hitung progres tiap siswa
        $siswaList = $kelas->siswa->map(function ($s) use ($allMaterialIds, $totalMaterialsCount) {
            $completedCount = Progress::where('user_id', $s->id)
                ->whereIn('material_id', $allMaterialIds)
                ->where('status_selesai', true)
                ->count();

            $persen = $totalMaterialsCount > 0 
                ? round(($completedCount / $totalMaterialsCount) * 100) 
                : 0;

            $s->progres_persen = $persen;
            $s->completed_materials = $completedCount;
            return $s;
        });

        $activeQuiz = $this->selectedQuizId 
            ? Quiz::with('questions.options')->find($this->selectedQuizId) 
            : null;

        return [
            'kelas' => $kelas,
            'siswaList' => $siswaList,
            'totalMaterialsCount' => $totalMaterialsCount,
            'activeQuiz' => $activeQuiz,
        ];
    }
}; ?>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Flash Alert -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 flex items-center justify-between text-sm shadow-sm transition">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
            </div>
        @endif

        <!-- Header Info Kelas -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <a href="{{ route('guru.kelas.index') }}" wire:navigate class="text-xs font-semibold text-gray-500 hover:text-indigo-600 flex items-center gap-1">
                            &larr; Kembali ke Kelas
                        </a>
                        <span class="text-gray-300">&bull;</span>
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700">
                            {{ $kelas->jenjang }} &bull; {{ $kelas->mapel }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                        {{ $kelas->nama }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1 max-w-2xl">
                        {{ $kelas->deskripsi ?: 'Tidak ada deskripsi' }}
                    </p>
                </div>

                <!-- Badge Kode Kelas Besar untuk Siswa -->
                <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 flex items-center gap-4 shrink-0" x-data="{ copied: false }">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-500">Kode Gabung Siswa</p>
                        <p class="text-2xl font-mono font-extrabold text-indigo-950 tracking-widest">{{ $kelas->kode_kelas }}</p>
                    </div>
                    <button @click="navigator.clipboard.writeText('{{ $kelas->kode_kelas }}'); copied = true; setTimeout(() => copied = false, 2500)" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-xl shadow-xs transition flex items-center gap-1.5 text-xs font-bold">
                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-show="!copied">Salin Kode</span>
                        <span x-show="copied" style="display: none;">Tersalin! ✓</span>
                    </button>
                </div>
            </div>

            <!-- Tabs Navigasi -->
            <div class="flex items-center gap-8 mt-8 border-b border-gray-100 text-sm font-semibold">
                <button wire:click="$set('activeTab', 'kurikulum')" class="pb-3 border-b-2 transition {{ $activeTab === 'kurikulum' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    📚 Bab & Materi Pembelajaran ({{ $kelas->chapters->count() }})
                </button>
                <button wire:click="$set('activeTab', 'siswa')" class="pb-3 border-b-2 transition {{ $activeTab === 'siswa' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    👥 Siswa Terdaftar ({{ $siswaList->count() }})
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: KURIKULUM & MATERI                   -->
        <!-- ========================================== -->
        @if($activeTab === 'kurikulum')
            <div class="space-y-6">
                <!-- Action Header Bab -->
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Struktur Bab & Kurikulum</h2>
                        <p class="text-xs text-gray-500">Urutkan bab, upload video/teks, dan buat kuis latihan untuk siswa</p>
                    </div>
                    <button wire:click="openCreateChapter" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-xl text-xs shadow-sm transition">
                        + Tambah Bab Baru
                    </button>
                </div>

                @if($kelas->chapters->isEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                        <div class="h-16 w-16 mx-auto rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center mb-3">
                            📖
                        </div>
                        <h3 class="text-base font-bold text-gray-800">Belum ada bab materi</h3>
                        <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-4">Tambahkan bab pertama (misal: "Bab 1: Pengantar") untuk mulai memasukkan video, teks, atau kuis.</p>
                        <button wire:click="openCreateChapter" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-xl text-xs shadow-sm transition">
                            + Buat Bab Pertama
                        </button>
                    </div>
                @else
                    <div class="space-y-5">
                        @foreach($kelas->chapters as $index => $chapter)
                            <div class="bg-white rounded-2xl border border-gray-200/90 shadow-2xs overflow-hidden">
                                <!-- Chapter Header -->
                                <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-7 w-7 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">
                                            {{ $index + 1 }}
                                        </span>
                                        <div>
                                            <h3 class="font-bold text-gray-900 text-base">{{ $chapter->judul }}</h3>
                                            @if($chapter->deskripsi)
                                                <p class="text-xs text-gray-500">{{ $chapter->deskripsi }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <button wire:click="openCreateMaterial({{ $chapter->id }})" class="inline-flex items-center gap-1 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition">
                                            + Materi
                                        </button>
                                        <button wire:click="openCreateQuiz({{ $chapter->id }})" class="inline-flex items-center gap-1 bg-white hover:bg-purple-50 text-purple-700 border border-purple-200 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition">
                                            + Kuis
                                        </button>
                                        <button wire:click="openEditChapter({{ $chapter->id }})" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-200 transition" title="Edit Bab">
                                            ✏️
                                        </button>
                                        <button wire:confirm="Hapus bab ini beserta semua materi di dalamnya?" wire:click="hapusChapter({{ $chapter->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-200 transition" title="Hapus Bab">
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                <!-- Chapter Content (Materials & Quizzes) -->
                                <div class="p-6 space-y-3">
                                    @if($chapter->materials->isEmpty() && $chapter->quizzes->isEmpty())
                                        <p class="text-xs text-gray-400 italic text-center py-4">Belum ada materi atau kuis di bab ini. Klik tombol di atas untuk menambah.</p>
                                    @endif

                                    <!-- List Materi -->
                                    @foreach($chapter->materials as $mat)
                                        <div class="flex items-center justify-between p-3.5 rounded-xl border border-gray-100 hover:border-indigo-100 hover:bg-indigo-50/20 transition">
                                            <div class="flex items-center gap-3">
                                                <span class="text-lg">
                                                    @if($mat->tipe === 'video') 🎥
                                                    @elseif($mat->tipe === 'pdf') 📄
                                                    @else 📝
                                                    @endif
                                                </span>
                                                <div>
                                                    <h4 class="font-semibold text-sm text-gray-800">{{ $mat->judul }}</h4>
                                                    <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                                                        <span class="uppercase font-bold text-indigo-600">{{ $mat->tipe }}</span>
                                                        <span>&bull;</span>
                                                        <span>⏱️ {{ $mat->durasi_menit ?: 10 }} Menit</span>
                                                        @if($mat->url)
                                                            <span>&bull;</span>
                                                            <a href="{{ $mat->url }}" target="_blank" class="text-indigo-500 hover:underline">Lihat Tautan &nearr;</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button wire:click="openEditMaterial({{ $mat->id }})" class="text-xs text-gray-500 hover:text-indigo-600 font-medium">Edit</button>
                                                <span class="text-gray-300">&bull;</span>
                                                <button wire:confirm="Hapus materi ini?" wire:click="hapusMaterial({{ $mat->id }})" class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- List Kuis -->
                                    @foreach($chapter->quizzes as $quiz)
                                        <div class="flex items-center justify-between p-3.5 rounded-xl border border-purple-100 bg-purple-50/30 hover:bg-purple-50/60 transition">
                                            <div class="flex items-center gap-3">
                                                <span class="text-lg">🎯</span>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="font-bold text-sm text-gray-900">{{ $quiz->judul }}</h4>
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800">
                                                            {{ $quiz->questions->count() }} Soal
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                                                        <span>⏱️ {{ $quiz->durasi_menit }} Menit</span>
                                                        <span>&bull;</span>
                                                        <span>KKM: {{ $quiz->kkm }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <button wire:click="openQuestions({{ $quiz->id }})" class="inline-flex items-center gap-1 bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-2xs transition">
                                                    ✍️ Kelola Soal ({{ $quiz->questions->count() }})
                                                </button>
                                                <button wire:click="openEditQuiz({{ $quiz->id }})" class="text-xs text-gray-500 hover:text-purple-600 font-medium">Edit</button>
                                                <span class="text-gray-300">&bull;</span>
                                                <button wire:confirm="Hapus kuis ini?" wire:click="hapusQuiz({{ $quiz->id }})" class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- ========================================== -->
        <!-- TAB 2: DAFTAR SISWA TERDAFTAR               -->
        <!-- ========================================== -->
        @if($activeTab === 'siswa')
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Siswa yang Bergabung</h2>
                        <p class="text-xs text-gray-500">Pantau kehadiran dan progres penyelesaian materi setiap siswa</p>
                    </div>
                    <div class="text-xs text-gray-500">
                        Total: <b>{{ $siswaList->count() }}</b> Siswa
                    </div>
                </div>

                @if($siswaList->isEmpty())
                    <div class="text-center py-12 px-4 border border-dashed border-gray-200 rounded-xl">
                        <div class="text-3xl mb-2">🎒</div>
                        <h3 class="font-bold text-gray-800 text-sm">Belum ada siswa yang bergabung</h3>
                        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                            Bagikan kode kelas <b>{{ $kelas->kode_kelas }}</b> kepada siswa Anda agar mereka dapat bergabung melalui akun siswa mereka.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider border-b border-gray-100">
                                <tr>
                                    <th class="py-3 px-4">Nama Siswa</th>
                                    <th class="py-3 px-4">Email</th>
                                    <th class="py-3 px-4">Tanggal Gabung</th>
                                    <th class="py-3 px-4">Progres Materi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($siswaList as $siswa)
                                    <tr class="hover:bg-gray-50/80 transition">
                                        <td class="py-3 px-4 font-semibold text-gray-900 flex items-center gap-2">
                                            <div class="h-7 w-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[11px]">
                                                {{ substr($siswa->name, 0, 2) }}
                                            </div>
                                            <span>{{ $siswa->name }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-gray-500">{{ $siswa->email }}</td>
                                        <td class="py-3 px-4 text-gray-500">
                                            {{ $siswa->pivot->tanggal_gabung ? \Carbon\Carbon::parse($siswa->pivot->tanggal_gabung)->format('d M Y') : '-' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-32 bg-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $siswa->progres_persen }}%"></div>
                                                </div>
                                                <span class="font-bold text-gray-700">{{ $siswa->progres_persen }}%</span>
                                                <span class="text-[10px] text-gray-400">({{ $siswa->completed_materials }}/{{ $totalMaterialsCount }})</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        <!-- ========================================== -->
        <!-- MODAL 1: TAMBAH / EDIT CHAPTER             -->
        <!-- ========================================== -->
        @if($showChapterModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        {{ $chapterId ? 'Edit Bab' : 'Tambah Bab Baru' }}
                    </h3>

                    <form wire:submit="simpanChapter" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Judul Bab *</label>
                            <input type="text" wire:model="chapterJudul" placeholder="Contoh: Bab 1: Aljabar Dasar" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            @error('chapterJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Deskripsi Singkat</label>
                            <textarea wire:model="chapterDeskripsi" rows="3" placeholder="Ringkasan topik pembahasan bab ini..." class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="$set('showChapterModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- MODAL 2: TAMBAH / EDIT MATERIAL            -->
        <!-- ========================================== -->
        @if($showMaterialModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        {{ $materialId ? 'Edit Materi' : 'Tambah Materi Baru' }}
                    </h3>

                    <form wire:submit="simpanMaterial" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Judul Materi *</label>
                            <input type="text" wire:model="materialJudul" placeholder="Contoh: Pengantar Rumus abc" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            @error('materialJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Tipe Materi *</label>
                                <select wire:model.live="materialTipe" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="teks">📝 Teks / Catatan</option>
                                    <option value="video">🎥 Video (YouTube Link)</option>
                                    <option value="link">🔗 Tautan Luar</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Estimasi Durasi (Menit)</label>
                                <input type="number" wire:model="materialDurasi" min="1" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        @if($materialTipe === 'video' || $materialTipe === 'link')
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">URL / Link Video YouTube *</label>
                                <input type="url" wire:model="materialUrl" placeholder="https://www.youtube.com/watch?v=..." class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Isi Konten / Penjelasan Bacaan</label>
                            <textarea wire:model="materialKonten" rows="5" placeholder="Tuliskan materi pelajaran lengkap di sini (mendukung teks bebas/HTML)..." class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-sans"></textarea>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="$set('showMaterialModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">Simpan Materi</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- MODAL 3: TAMBAH / EDIT QUIZ                -->
        <!-- ========================================== -->
        @if($showQuizModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        {{ $quizId ? 'Edit Kuis' : 'Buat Kuis Baru' }}
                    </h3>

                    <form wire:submit="simpanQuiz" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Judul Kuis *</label>
                            <input type="text" wire:model="quizJudul" placeholder="Contoh: Kuis Pemahaman Bab 1" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            @error('quizJudul') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Durasi (Menit)</label>
                                <input type="number" wire:model="quizDurasi" min="1" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Batas Lulus (KKM)</label>
                                <input type="number" wire:model="quizKkm" min="0" max="100" class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Petunjuk / Deskripsi</label>
                            <textarea wire:model="quizDeskripsi" rows="2" placeholder="Petunjuk pengerjaan soal bagi siswa..." class="w-full text-sm rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button type="button" wire:click="$set('showQuizModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white shadow-sm">Simpan Kuis</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- MODAL 4: KELOLA SOAL-SOAL KUIS             -->
        <!-- ========================================== -->
        @if($showQuestionModal && $activeQuiz)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-purple-600">Bank Soal Kuis</span>
                            <h3 class="text-xl font-bold text-gray-900">{{ $activeQuiz->judul }}</h3>
                        </div>
                        <button wire:click="$set('showQuestionModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                    </div>

                    @if (session('soal_success'))
                        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-800 text-xs">
                            {{ session('soal_success') }}
                        </div>
                    @endif

                    <!-- Form Tambah Soal Baru -->
                    <div class="bg-purple-50/40 rounded-2xl p-5 border border-purple-100 mb-6">
                        <h4 class="font-bold text-sm text-purple-900 mb-3">+ Buat Soal Pilihan Ganda Baru</h4>
                        <form wire:submit="simpanSoal" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Pertanyaan *</label>
                                <textarea wire:model="soalTeks" rows="2" placeholder="Tuliskan butir pertanyaan di sini..." class="w-full text-xs rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500" required></textarea>
                            </div>

                            <!-- Opsi Pilihan Ganda -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase text-gray-700">Pilihan Jawaban (Pilih radio untuk Kunci Jawaban Benar) *</label>
                                @foreach($soalOptions as $idx => $opt)
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="jawaban_benar" wire:click="setJawabanBenar({{ $idx }})" {{ $opt['is_benar'] ? 'checked' : '' }} class="h-4 w-4 text-purple-600 focus:ring-purple-500 cursor-pointer">
                                        <span class="font-bold text-xs w-5 text-gray-600">{{ $opt['label'] }}.</span>
                                        <input type="text" wire:model="soalOptions.{{ $idx }}.teks" placeholder="Pilihan jawaban {{ $opt['label'] }}" class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 {{ $opt['is_benar'] ? 'bg-purple-50/50 border-purple-300 font-semibold' : '' }}" required>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Bobot Nilai</label>
                                    <input type="number" wire:model="soalBobot" min="1" class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Penjelasan Pembahasan (Opsional)</label>
                                    <input type="text" wire:model="soalPenjelasan" placeholder="Penjelasan jawaban benar..." class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-xs transition">
                                    + Tambah ke Soal Kuis
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Daftar Soal Yang Sudah Ada -->
                    <div class="space-y-4">
                        <h4 class="font-bold text-sm text-gray-800">Daftar Soal Terdaftar ({{ $activeQuiz->questions->count() }})</h4>
                        @if($activeQuiz->questions->isEmpty())
                            <p class="text-xs text-gray-400 italic text-center py-4">Belum ada soal pada kuis ini.</p>
                        @else
                            @foreach($activeQuiz->questions as $qIndex => $question)
                                <div class="p-4 rounded-xl border border-gray-200/90 bg-white space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="font-medium text-xs text-gray-900">
                                            <b>{{ $qIndex + 1 }}.</b> {{ $question->pertanyaan }}
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600">
                                                Bobot: {{ $question->bobot }}
                                            </span>
                                            <button wire:click="hapusSoal({{ $question->id }})" class="text-xs text-red-500 hover:text-red-700 font-bold">&times;</button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 pl-4 pt-1">
                                        @foreach($question->options as $op)
                                            <div class="text-xs flex items-center gap-1.5 {{ $op->is_benar ? 'text-green-700 font-bold bg-green-50 px-2 py-1 rounded' : 'text-gray-600' }}">
                                                <span>{{ $op->label }}.</span>
                                                <span>{{ $op->teks }}</span>
                                                @if($op->is_benar)
                                                    <span class="text-[10px] bg-green-200 text-green-800 px-1 rounded ml-auto">Kunci ✓</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
