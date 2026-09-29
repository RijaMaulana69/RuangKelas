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
            $this->isSelesai = (bool) $progress->status_selesai;
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
            session()->flash('success', 'Materi berhasil ditandai sudah dipelajari.');
        } else {
            session()->flash('success', 'Status materi diperbarui menjadi belum selesai.');
        }
    }

    public function with(): array
    {
        $material = Material::with(['chapter.kelas', 'chapter.materials'])->findOrFail($this->materialId);

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
            'embedUrl' => $embedUrl,
        ];
    }
}; ?>

<div class="py-5 sm:py-6 pb-20 md:pb-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-4">
        <!-- Flash Alert -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs sm:text-sm shadow-2xs transition">
                <div class="flex items-center gap-2 font-medium">
                    <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 text-base leading-none">&times;</button>
            </div>
        @endif

        <!-- Navigasi & Status Dinamis -->
        <div class="flex items-center justify-between">
            <a href="{{ route('siswa.kelas.show', $material->chapter->class_id) }}" wire:navigate 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali</span>
            </a>

            <!-- Badge Status Minimalis Dinamis -->
            <button wire:click="toggleSelesai" wire:loading.attr="disabled" type="button" 
                    title="{{ $isSelesai ? 'Klik untuk tandai belum selesai' : 'Klik untuk tandai sudah dipelajari' }}"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition transform active:scale-95 cursor-pointer {{ $isSelesai ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $isSelesai ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                <span>{{ $isSelesai ? 'Sudah Dipelajari' : 'Belum Dipelajari' }}</span>
            </button>
        </div>

        <!-- Card Materi Pembelajaran -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/90 shadow-2xs space-y-4">
            <!-- Header Judul -->
            <div class="border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-1 font-medium">
                    <span>{{ $material->chapter->judul }}</span>
                    <span>&bull;</span>
                    <span class="uppercase font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded text-[10px]">{{ $material->tipe }}</span>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight leading-snug">
                    {{ $material->judul }}
                </h1>
            </div>

            <!-- Catatan Pengantar Materi -->
            @if(!empty($material->konten))
                <div class="{{ $material->tipe === 'teks' ? 'prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed' : 'text-xs sm:text-sm text-slate-600 leading-relaxed' }}">
                    {!! $material->konten !!}
                </div>
            @endif

            <!-- Tampilan Video YouTube Responsif -->
            @if($material->tipe === 'video' && $embedUrl)
                <div class="rounded-xl overflow-hidden aspect-video bg-black shadow-2xs border border-slate-200">
                    <iframe src="{{ $embedUrl }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @endif

            <!-- Tampilan Dokumen PDF Ringkas & Profesional -->
            @if($material->tipe === 'pdf')
                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0 shadow-2xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-800 text-xs sm:text-sm truncate">{{ $material->judul }}</h4>
                            <p class="text-[11px] text-slate-400 font-medium">Dokumen Modul PDF</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($material->file_path)
                            <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" download class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition transform active:scale-95">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span>Unduh PDF</span>
                            </a>
                            <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition" title="Buka di Tab Baru">
                                <span>Buka Tab Baru</span>
                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        @elseif($material->url)
                            <a href="{{ $material->url }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition transform active:scale-95">
                                <span>Buka Dokumen</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Tampilan Link Luar -->
            @if($material->tipe === 'link' && !empty($material->url))
                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 shadow-2xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-800 text-xs sm:text-sm truncate">{{ $material->judul }}</h4>
                            <p class="text-[11px] text-slate-400 font-medium">Tautan Referensi Eksternal</p>
                        </div>
                    </div>
                    <a href="{{ $material->url }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition shrink-0">
                        <span>Buka Sumber</span>
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>
                </div>
            @endif

            <!-- Action Bar Bawah -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-start">
                <button wire:click="toggleSelesai" wire:loading.attr="disabled" type="button" 
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-bold text-xs sm:text-sm transition transform active:scale-95 cursor-pointer shadow-2xs {{ $isSelesai ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                    @if($isSelesai)
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        <span>Tandai Belum Selesai</span>
                    @else
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        <span>Tandai Sudah Dipelajari</span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Action Bar -->
    <div class="sm:hidden fixed bottom-14 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-2.5 shadow-lg flex items-center">
        <button wire:click="toggleSelesai" wire:loading.attr="disabled" type="button" 
                class="w-full py-2.5 px-3 rounded-xl font-bold text-xs transition transform active:scale-95 flex items-center justify-center gap-2 cursor-pointer {{ $isSelesai ? 'bg-slate-100 text-slate-700 border border-slate-200' : 'bg-emerald-600 text-white' }}">
            @if($isSelesai)
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                <span>Tandai Belum Selesai</span>
            @else
                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <span>Tandai Sudah Dipelajari</span>
            @endif
        </button>
    </div>
</div>
