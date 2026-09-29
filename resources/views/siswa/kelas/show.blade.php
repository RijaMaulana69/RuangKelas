<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                Materi Belajar
            </h2>
            <p class="text-xs text-slate-500">Ikuti materi per bab dan kerjakan kuis latihan</p>
        </div>
    </x-slot>

    <livewire:siswa.kelas-detail :kelas="$kelas" />
</x-app-layout>
