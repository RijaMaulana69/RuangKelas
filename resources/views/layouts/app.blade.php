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
    <body class="font-sans antialiased text-slate-800 bg-slate-50/70 min-h-full selection:bg-indigo-600 selection:text-white">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50/70 flex">
            <!-- 1. SIDEBAR NAVIGASI (DESKTOP & MOBILE DRAWER) -->
            @include('layouts.sidebar')

            <!-- 2. WRAPPER KONTEN UTAMA -->
            <div class="flex-1 flex flex-col min-w-0 min-h-screen pb-20 md:pb-0">
                
                <!-- TOP HEADER APP BAR (RAPIH, BERSIH, TERINTEGRASI) -->
                <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 min-h-[74px] px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-2xs">
                    <!-- Sisi Kiri: Tombol Hamburger Mobile + Header Judul Halaman -->
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <!-- Hamburger Button (Mobile / Tablet < lg) -->
                        <button @click="sidebarOpen = true" 
                                class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 focus:outline-none transition shrink-0"
                                title="Buka Navigasi Sidebar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <!-- Judul Halaman dari $header -->
                        @if (isset($header))
                            <div class="min-w-0">
                                {{ $header }}
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                    🎓
                                </div>
                                <div>
                                    <h2 class="font-bold text-lg sm:text-xl text-slate-800 leading-tight">RuangKelas</h2>
                                    <p class="text-xs text-slate-400">Platform Pembelajaran Interaktif</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Sisi Kanan: Role Badge & Dropdown Profil Pengguna -->
                    <div class="flex items-center gap-3 shrink-0 ml-4">
                        <!-- Role Badge -->
                        @if(auth()->user()->hasRole('guru'))
                            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                                👨‍🏫 Akun Guru
                            </span>
                        @elseif(auth()->user()->hasRole('siswa'))
                            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                🎒 Akun Siswa
                            </span>
                        @endif

                        <!-- User Profile Dropdown -->
                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-2.5 px-3 py-1.5 sm:px-3.5 sm:py-2 border border-slate-200 text-sm font-bold rounded-2xl text-slate-700 bg-white hover:text-slate-900 hover:bg-slate-50 focus:outline-none transition shadow-2xs">
                                    <div class="h-8 w-8 rounded-xl {{ auth()->user()->hasRole('guru') ? 'bg-gradient-to-tr from-indigo-600 to-purple-600' : 'bg-gradient-to-tr from-emerald-600 to-teal-600' }} text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                                        {{ substr(auth()->user()->name, 0, 2) }}
                                    </div>
                                    <span class="hidden md:inline-block truncate max-w-[130px]">{{ auth()->user()->name }}</span>
                                    <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-2 border-b border-slate-100 text-xs">
                                    <p class="font-bold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="text-slate-400 truncate">{{ auth()->user()->email }}</p>
                                </div>

                                <x-dropdown-link :href="route('profile')" wire:navigate class="font-semibold text-xs py-2.5">
                                    👤 Profil Akun
                                </x-dropdown-link>

                                <!-- Logout Action -->
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full text-start block px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 focus:outline-none transition">
                                        🚪 Keluar (Logout)
                                    </button>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <!-- 3. KONTEN HALAMAN -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- MOBILE BOTTOM NAVIGATION BAR (Khusus Smartphone & Layar Kecil) -->
        <!-- ============================================================ -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/90 backdrop-blur-xl border-t border-slate-200/80 shadow-lg px-4 py-2 flex items-center justify-around">
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
