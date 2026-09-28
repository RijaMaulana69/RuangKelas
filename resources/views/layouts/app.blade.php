<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RuangKelas') }} - Platform Pembelajaran Interaktif</title>

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-slate-50/70 min-h-full flex flex-col selection:bg-indigo-600 selection:text-white">
        <div class="min-h-screen flex flex-col pb-20 md:pb-0">
            <!-- Top Navigation (Desktop & Mobile Header) -->
            <livewire:layout.navigation />

            <!-- Page Heading Banner jika ada -->
            @if (isset($header))
                <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-16 z-40">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Main Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

        <!-- ============================================================ -->
        <!-- MOBILE BOTTOM NAVIGATION BAR (Khusus Smartphone & Layar Kecil) -->
        <!-- Memberikan sensasi pengalaman layaknya Mobile App            -->
        <!-- ============================================================ -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-xl border-t border-slate-200/80 shadow-lg px-4 py-2 flex items-center justify-around">
            <!-- Dashboard Link -->
            @php
                $isGuru = auth()->check() && auth()->user()->hasRole('guru');
                $isSiswa = auth()->check() && auth()->user()->hasRole('siswa');
                $isDashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard') || request()->routeIs('siswa.dashboard');
                $isKelasActive = request()->routeIs('guru.kelas.*') || request()->routeIs('siswa.kelas.*');
                $isProfileActive = request()->routeIs('profile');
            @endphp

            <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isDashActive ? ($isGuru ? 'text-indigo-600 font-bold' : 'text-emerald-600 font-bold') : 'text-slate-400 hover:text-slate-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isDashActive ? '2.5' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] tracking-tight">Beranda</span>
            </a>

            <!-- Kelas Link -->
            @if($isGuru)
                <a href="{{ route('guru.kelas.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isKelasActive ? 'text-indigo-600 font-bold' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.5' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="text-[10px] tracking-tight">Kelas Diajar</span>
                </a>
            @elseif($isSiswa)
                <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isKelasActive ? 'text-emerald-600 font-bold' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.5' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="text-[10px] tracking-tight">Kelas Saya</span>
                </a>
            @endif

            <!-- Profile Link -->
            <a href="{{ route('profile') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isProfileActive ? 'text-indigo-600 font-bold' : 'text-slate-400 hover:text-slate-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isProfileActive ? '2.5' : '1.8' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[10px] tracking-tight">Profil</span>
            </a>
        </nav>
    </body>
</html>
