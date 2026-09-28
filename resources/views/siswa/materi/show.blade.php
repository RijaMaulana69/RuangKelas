<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                📑
            </div>
            <div>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    Materi Pembelajaran
                </h2>
                <p class="text-xs text-gray-500">Pelajari materi dan tandai setelah selesai</p>
            </div>
        </div>
    </x-slot>

    <livewire:siswa.materi-view :material="$material" />
</x-app-layout>
