<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                📖
            </div>
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    Kelola Materi & Kuis Kelas
                </h2>
                <p class="text-xs text-gray-500">Susun kurikulum bab, upload materi pembelajaran, dan kelola kuis</p>
            </div>
        </div>
    </x-slot>

    <livewire:guru.kelas-detail :kelas="$kelas" />
</x-app-layout>
