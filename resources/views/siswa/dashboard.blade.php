<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 sm:gap-3.5">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold shadow-sm shadow-indigo-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-lg sm:text-xl text-slate-900 leading-tight">
                    Ruang Belajar Siswa
                </h2>
            </div>
        </div>
    </x-slot>

    <livewire:siswa.dashboard />
</x-app-layout>
