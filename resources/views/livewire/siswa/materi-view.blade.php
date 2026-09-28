<?php

use App\Models\Material;
use App\Models\Progress;
use App\Models\Enrollment;
use Livewire\Volt\Component;

new class extends Component {
    public int $materialId;
    public bool $isSelesai = false;

    public function mount(int $material): void
    {
        $this->materialId = $material;
        $mat = Material::with('chapter.kelas')->findOrFail($this->materialId);

        // Pastikan siswa terdaftar di kelas materi ini
        $isEnrolled = Enrollment::where('user_id', auth()->id())
            ->where('class_id', $mat->chapter->class_id)
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'Anda belum terdaftar di kelas materi ini.');
        }

        $this->isSelesai = Progress::where('user_id', auth()->id())
            ->where('material_id', $this->materialId)
            ->where('status_selesai', true)
            ->exists();
    }

    public function toggleSelesai(): void
    {
        $userId = auth()->id();
        $progress = Progress::where('user_id', $userId)
            ->where('material_id', $this->materialId)
            ->first();

        if ($progress) {
            $progress->status_selesai = !$progress->status_selesai;
            $progress->selesai_at = $progress->status_selesai ? now() : null;
            $progress->save();
            $this->isSelesai = $progress->status_selesai;
        } else {
            Progress::create([
                'user_id' => $userId,
                'material_id' => $this->materialId,
                'status_selesai' => true,
                'selesai_at' => now(),
            ]);
            $this->isSelesai = true;
        }

        if ($this->isSelesai) {
            session()->flash('success', 'Hebat! Kamu telah menyelesaikan materi ini 🎉');
        }
    }

    public function with(): array
    {
        $material = Material::with(['chapter.kelas', 'chapter.materials'])->findOrFail($this->materialId);

        // Ambil materi selanjutnya dalam chapter yang sama jika ada
        $nextMaterial = Material::where('chapter_id', $material->chapter_id)
            ->where('urutan', '>', $material->urutan)
            ->orderBy('urutan')
            ->first();

        // Parse embed URL YouTube jika tipe video
        $embedUrl = null;
        if ($material->tipe === 'video' && !empty($material->url)) {
            $url = $material->url;
            if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
                $embedUrl = 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
            } else {
                $embedUrl = $url;
            }
        }

        return [
            'material' => $material,
            'nextMaterial' => $nextMaterial,
            'embedUrl' => $embedUrl,
        ];
    }
}; ?>

<div class="py-6 sm:py-8 pb-28 md:pb-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Flash Alert -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm shadow-sm transition">
                <div class="flex items-center gap-2 font-semibold">
                    <span class="text-emerald-600 font-bold text-base">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-lg">&times;</button>
            </div>
        @endif

        <!-- Navigasi Breadcrumb Atas -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('siswa.kelas.show', $material->chapter->class_id) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-emerald-600 transition bg-white px-3.5 py-1.5 rounded-xl border border-slate-200/80 shadow-2xs">
                &larr; Silabus: {{ $material->chapter->kelas->nama }}
            </a>

            @if($isSelesai)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                    ✓ Sudah Dipelajari
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                    Sedang Dipelajari
                </span>
            @endif
        </div>

        <!-- Card Materi Pembelajaran -->
        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/80 shadow-sm space-y-6">
            <!-- Header Judul -->
            <div class="border-b border-slate-100 pb-5">
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400 mb-2">
                    <span class="font-semibold text-slate-600">{{ $material->chapter->judul }}</span>
                    <span>&bull;</span>
                    <span class="uppercase font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">{{ $material->tipe }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight">
                    {{ $material->judul }}
                </h1>
            </div>

            <!-- Tampilan Video YouTube Responsif -->
            @if($material->tipe === 'video' && $embedUrl)
                <div class="rounded-2xl overflow-hidden aspect-video bg-black shadow-xl ring-1 ring-slate-900/10">
                    <iframe src="{{ $embedUrl }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @endif

            <!-- Tampilan Dokumen PDF Interaktif -->
            @if($material->tipe === 'pdf')
                <div class="space-y-4">
                    <div class="p-5 sm:p-6 rounded-2xl bg-amber-50/70 border border-amber-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📄</span>
                                <h4 class="font-bold text-amber-950 text-sm">Dokumen Modul / E-Book PDF</h4>
                            </div>
                            <p class="text-xs text-amber-800">Pelajari materi langsung di bawah atau unduh berkas untuk dibaca secara offline.</p>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            @if($material->file_path)
                                <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" download class="w-full sm:w-auto text-center bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                                    <span>⬇️ Unduh PDF</span>
                                </a>
                            @elseif($material->url)
                                <a href="{{ $material->url }}" target="_blank" class="w-full sm:w-auto text-center bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-xs transition flex items-center justify-center gap-2">
                                    <span>Buka PDF di Tab Baru &nearr;</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($material->file_path)
                        <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-inner bg-slate-100">
                            <iframe src="{{ asset('storage/' . $material->file_path) }}#toolbar=1" class="w-full h-[550px] sm:h-[680px]" frameborder="0"></iframe>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tampilan Link Luar -->
            @if($material->tipe === 'link' && !empty($material->url))
                <div class="p-5 sm:p-6 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h4 class="font-bold text-emerald-950 text-sm">Dokumen & Sumber Belajar Eksternal</h4>
                        <p class="text-xs text-emerald-800">Buka tautan ini untuk membaca atau mempelajari materi pendukung.</p>
                    </div>
                    <a href="{{ $material->url }}" target="_blank" class="w-full sm:w-auto text-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-xs transition">
                        Buka Sumber &nearr;
                    </a>
                </div>
            @endif

            <!-- Konten Teks Bacaan -->
            @if(!empty($material->konten))
                <div class="prose prose-slate max-w-none text-slate-700 text-sm sm:text-base leading-relaxed pt-2">
                    {!! $material->konten !!}
                </div>
            @endif

            <!-- Desktop Action Bar -->
            <div class="hidden sm:flex pt-8 border-t border-slate-100 items-center justify-between gap-4">
                <button wire:click="toggleSelesai" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-xs sm:text-sm shadow-sm transition transform active:scale-95 {{ $isSelesai ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                    @if($isSelesai)
                        ✓ Tandai Belum Selesai
                    @else
                        ✅ Saya Sudah Memahami (Tandai Selesai)
                    @endif
                </button>

                @if($nextMaterial)
                    <a href="{{ route('siswa.materi.show', $nextMaterial->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-5 py-3 rounded-2xl font-bold text-xs sm:text-sm bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                        Lanjut Materi: {{ Str::limit($nextMaterial->judul, 25) }} &rarr;
                    </a>
                @else
                    <a href="{{ route('siswa.kelas.show', $material->chapter->class_id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-5 py-3 rounded-2xl font-bold text-xs sm:text-sm bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Kembali ke Silabus &rarr;
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MOBILE STICKY BOTTOM ACTION BAR                             -->
    <!-- Sangat ergonomis untuk jempol pengguna di smartphone HP      -->
    <!-- ============================================================ -->
    <div class="sm:hidden fixed bottom-14 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-slate-200 px-4 py-2.5 shadow-xl flex items-center gap-2">
        <button wire:click="toggleSelesai" class="flex-1 py-3 px-3 rounded-xl font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5 {{ $isSelesai ? 'bg-slate-100 text-slate-700' : 'bg-emerald-600 text-white' }}">
            @if($isSelesai)
                ✓ Selesai
            @else
                ✅ Tandai Selesai
            @endif
        </button>

        @if($nextMaterial)
            <a href="{{ route('siswa.materi.show', $nextMaterial->id) }}" wire:navigate class="py-3 px-3 rounded-xl font-bold text-xs bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0">
                Lanjut &rarr;
            </a>
        @else
            <a href="{{ route('siswa.kelas.show', $material->chapter->class_id) }}" wire:navigate class="py-3 px-3 rounded-xl font-bold text-xs bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                Silabus &rarr;
            </a>
        @endif
    </div>
</div>
