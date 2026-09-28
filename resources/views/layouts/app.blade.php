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
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50/70 flex flex-col">

            <!-- ============================================================ -->
            <!-- 1. TOP HEADER APP BAR (BERSIH, PROFESIONAL, ELEGAN)          -->
            <!-- ============================================================ -->
            <header class="sticky top-0 z-30 bg-slate-900 border-b border-slate-800 h-[70px] px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-md text-white">
                
                <!-- Sisi Kiri: Hamburger + Brand Logo + Judul Halaman -->
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <!-- Hamburger Button (Mobile / Tablet < lg) -->
                    <button @click="sidebarOpen = true" 
                            class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition shrink-0"
                            title="Buka Navigasi Sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Brand Logo RuangKelas (Sinkron Landing Page) -->
                    <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 sm:gap-2.5 group shrink-0">
                        <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-950 shrink-0 group-hover:scale-105 transition-transform duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                                <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                            </svg>
                        </div>
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-white leading-none">
                            Ruang<span class="text-indigo-400">Kelas</span>
                        </span>
                    </a>

                    <!-- Konteks Judul Halaman -->
                    @if (isset($header))
                        <div class="hidden sm:flex items-center gap-2.5 min-w-0 pl-1 text-slate-500">
                            <span class="text-slate-600 font-light text-base select-none">/</span>
                            <div class="min-w-0 [&_h2]:!text-sm sm:[&_h2]:!text-base [&_h2]:!font-bold [&_h2]:!text-slate-200 [&_p]:!hidden [&_div.shrink-0]:!hidden [&_div.h-8]:!hidden [&_div.h-10]:!hidden">
                                {{ $header }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sisi Kanan: Ikon Notifikasi Sederhana & Profil Pengguna -->
                <div class="flex items-center gap-2.5 sm:gap-3 shrink-0 ml-4">
                    
                    <!-- Ikon Notifikasi Sederhana & Rapi -->
                    <div class="relative" x-data="{ openNotif: false }" @click.away="openNotif = false">
                        <button @click="openNotif = !openNotif" 
                                class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition focus:outline-none"
                                title="Notifikasi">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>

                        <!-- Dropdown Notifikasi Sederhana -->
                        <div x-show="openNotif" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-72 bg-white text-slate-800 rounded-2xl shadow-xl border border-slate-200/90 py-2 z-50 overflow-hidden"
                             style="display: none;">
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <span class="font-bold text-xs text-slate-900">Notifikasi</span>
                            </div>
                            <div class="px-4 py-6 text-center text-xs text-slate-400">
                                Belum ada notifikasi
                            </div>
                        </div>
                    </div>

                    <!-- Pemisah Halus -->
                    <div class="h-5 w-px bg-slate-800 hidden sm:block"></div>

                    <!-- User Profile Dropdown (Interaktif dengan Tombol Keluar) -->
                    <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen" 
                                class="inline-flex items-center gap-2.5 px-2.5 py-1.5 sm:px-3 sm:py-2 border border-slate-800 rounded-2xl text-slate-200 bg-slate-800/80 hover:bg-slate-800 hover:text-white hover:border-slate-700 focus:outline-none transition shadow-2xs">
                            <div class="relative">
                                <div class="h-8 w-8 rounded-xl {{ auth()->user()->hasRole('guru') ? 'bg-indigo-600' : 'bg-emerald-600' }} text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                                    {{ substr(auth()->user()->name, 0, 2) }}
                                </div>
                                <span class="absolute -bottom-0.5 -right-0.5 block h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-slate-900"></span>
                            </div>
                            <span class="hidden md:inline-block font-bold text-sm truncate max-w-[130px]">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="userMenuOpen ? 'rotate-180 text-indigo-400' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Dropdown Box User Profile -->
                        <div x-show="userMenuOpen" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-64 bg-white text-slate-800 rounded-2xl shadow-2xl border border-slate-200 py-2 z-50 overflow-hidden"
                             style="display: none;">
                            <!-- Header Profil -->
                            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
                                <p class="font-extrabold text-sm text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate mt-0.5">{{ auth()->user()->email }}</p>
                            </div>

                            <div class="p-1.5 space-y-0.5">
                                <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profil Akun
                                </a>
                            </div>

                            <!-- Tombol Keluar (Tanpa kurung) -->
                            <div class="p-1.5 pt-1 border-t border-slate-100">
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ============================================================ -->
            <!-- 2. WRAPPER BODY (SIDEBAR DI KIRI + KONTEN DI KANAN)           -->
            <!-- ============================================================ -->
            <div class="flex-1 flex min-h-[calc(100vh-70px)]">
                <!-- SIDEBAR NAVIGASI -->
                @include('layouts.sidebar')

                <!-- AREA KONTEN UTAMA -->
                <main class="flex-1 bg-slate-50/70 p-4 sm:p-6 lg:p-8 min-w-0 overflow-y-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- MOBILE BOTTOM NAVIGATION BAR (Khusus Layar Kecil Smartphone) -->
        <!-- ============================================================ -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-xl border-t border-slate-800 shadow-2xl px-4 py-2 flex items-center justify-around text-slate-300">
            @php
                $isGuru = auth()->check() && auth()->user()->hasRole('guru');
                $isSiswa = auth()->check() && auth()->user()->hasRole('siswa');
                $isDashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard') || request()->routeIs('siswa.dashboard');
                $isKelasActive = request()->routeIs('guru.kelas.*') || request()->routeIs('siswa.kelas.*');
                $isProfileActive = request()->routeIs('profile');
            @endphp

            <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isDashActive ? ($isGuru ? 'text-indigo-400 font-bold' : 'text-emerald-400 font-bold') : 'text-slate-400 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isDashActive ? '2.5' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] tracking-tight">Beranda</span>
            </a>

            <!-- Kelas Link -->
            @if($isGuru)
                <a href="{{ route('guru.kelas.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isKelasActive ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.5' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="text-[10px] tracking-tight">Kelas Diajar</span>
                </a>
            @elseif($isSiswa)
                <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isKelasActive ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.5' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="text-[10px] tracking-tight">Kelas Saya</span>
                </a>
            @endif

            <!-- Profile Link -->
            <a href="{{ route('profile') }}" wire:navigate class="flex flex-col items-center gap-1 transition {{ $isProfileActive ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isProfileActive ? '2.5' : '1.8' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[10px] tracking-tight">Profil</span>
            </a>
        </nav>
    </body>
</html>
