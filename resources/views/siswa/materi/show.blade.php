<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-lg sm:text-xl text-slate-800 leading-tight">
                    Materi Pembelajaran
                </h2>
                <p class="text-xs text-slate-500">Pelajari materi dan tandai setelah selesai dipahami</p>
            </div>
        </div>
    </x-slot>

    <livewire:siswa.materi-view :material="$material" />
</x-app-layout>

