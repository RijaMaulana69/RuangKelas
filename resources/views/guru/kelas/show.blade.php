<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                Kelola Materi
            </h2>
            <p class="text-xs text-slate-500">Susun kurikulum bab, upload materi pembelajaran, dan kelola kuis</p>
        </div>
    </x-slot>

    <livewire:guru.kelas-detail :kelas="$kelas" />
</x-app-layout>
