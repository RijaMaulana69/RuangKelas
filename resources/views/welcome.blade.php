<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
        
        <!-- PWA & Mobile Web Meta -->
        <meta name="theme-color" content="#4f46e5">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="RuangKelas">

        <title>RuangKelas — Belajar Asik & Nyaman di Semua Sistem Operasi</title>
        <meta name="description" content="Platform belajar dan mengajar online yang ringan, terstruktur per bab, dan dapat digunakan dengan mulus di HP Android, iOS iPhone, Windows, macOS, maupun Chromebook tanpa perlu instalasi aplikasi.">

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Dynamic Header Glow & Blur Style -->
        <style>
            #main-header.header-scrolled {
                background-color: rgba(238, 242, 255, 0.82) !important;
                backdrop-filter: blur(18px) saturate(180%);
                -webkit-backdrop-filter: blur(18px) saturate(180%);
                border-bottom-color: rgba(199, 210, 254, 0.85) !important;
                box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.16), 0 4px 15px -3px rgba(99, 102, 241, 0.12) !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-slate-800 bg-white selection:bg-indigo-600 selection:text-white relative overflow-x-hidden min-h-screen">

        <!-- ============================================================ -->
        <!-- NAVBAR: MODERN DYNAMIC GLOW (TEMA INDIGO AKTIF & RESPONSIVE) -->
        <!-- ============================================================ -->
        <header id="main-header" class="sticky top-0 z-50 bg-indigo-50/95 border-b border-indigo-100/90 transition-all duration-300">
            <div class="w-full px-4 sm:px-8 lg:px-12 flex items-center justify-between h-14 sm:h-[68px]">
                
                <!-- 1. POJOK KIRI: ICON & TEKS BRAND (VERSI SEBELUMNYA - CLEAN NON-CLICKABLE) -->
                <div class="inline-flex items-center gap-2 sm:gap-2.5 select-none cursor-default">
                    <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-200 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                            <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                        </svg>
                    </div>
                    <span class="text-xl sm:text-2xl lg:text-[25px] font-black tracking-tight text-slate-900 leading-none">
                        Ruang<span class="text-indigo-600">Kelas</span>
                    </span>
                </div>

                <!-- 2. POJOK KANAN: TOMBOL AKSI CLEAN & RESPONSIVE -->
                <div class="flex items-center shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate class="group inline-flex items-center justify-center gap-1.5 sm:gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold text-xs sm:text-sm px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl shadow-xs hover:shadow-md hover:shadow-indigo-200/60 border border-indigo-500/30 transition-all duration-200 transform active:scale-95 touch-manipulation select-none">
                            <span>Buka Kelas</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-200 group-hover:text-white group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="group inline-flex items-center justify-center gap-1.5 sm:gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold text-xs sm:text-sm px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl shadow-xs hover:shadow-md hover:shadow-indigo-200/60 border border-indigo-500/30 transition-all duration-200 transform active:scale-95 touch-manipulation select-none">
                            <span>Masuk</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-200 group-hover:text-white group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- ============================================================ -->
        <!-- HERO SECTION: CLEAN, MODERN & ELEGAN (MOBILE OPTIMIZED)      -->
        <!-- ============================================================ -->
        <section class="pt-8 pb-12 sm:pt-14 sm:pb-20 lg:pt-16 lg:pb-24 overflow-hidden bg-white">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-12 lg:gap-16 items-center">
                    
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-6 text-center lg:text-left space-y-4 sm:space-y-6">

                        <!-- Hero Main Title -->
                        <h1 class="text-2xl sm:text-4xl lg:text-[50px] font-black text-slate-900 tracking-tight leading-[1.2] sm:leading-[1.16]">
                            Belajar Asik & Nyaman, <br class="hidden sm:inline">
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500">
                                di Semua Sistem Operasi.
                            </span>
                        </h1>

                        <!-- Friendly Subtitle -->
                        <p class="text-xs sm:text-base lg:text-lg text-slate-600 leading-relaxed max-w-xl mx-auto lg:mx-0 font-normal">
                            Platform belajar online modern tanpa install APK atau aplikasi yang memberatkan memori. Didesain super ringan, terstruktur per bab, dan otomatis responsif di HP, tablet, maupun laptop.
                        </p>

                        <!-- CTA Actions -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 sm:gap-4 pt-1 sm:pt-2">
                            <a href="{{ route('register') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm sm:text-base px-7 sm:px-8 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 transition-all transform active:scale-95">
                                <span>Mulai Belajar Sekarang</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>

                        <!-- Micro Trust Highlights -->
                        <div class="pt-3 sm:pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-3 sm:gap-5 text-[11px] sm:text-xs text-slate-500 font-medium">
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>100% Web Tanpa Instalasi</span>
                            </div>
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Hemat Kuota & Ringan</span>
                            </div>
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Aman untuk Siswa & Guru</span>
                            </div>
                        </div>

                    </div>

                    <!-- Right Professional Showcase Card (Mobile-Responsive & Wow Aesthetic) -->
                    <div class="lg:col-span-6 w-full">
                        <div class="relative group">
                            
                            <!-- Ambient Multi-Layer Glow Effect -->
                            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-3xl sm:rounded-[36px] opacity-20 blur-2xl group-hover:opacity-30 transition duration-1000 -z-10"></div>
                            <div class="absolute -top-6 -right-6 w-32 h-32 bg-indigo-400/20 rounded-full blur-2xl -z-10"></div>
                            <div class="absolute -bottom-6 -left-6 w-32 h-32 bg-purple-400/20 rounded-full blur-2xl -z-10"></div>

                            <!-- Main Glassmorphism Classroom Showcase Card -->
                            <div class="bg-white/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl p-5 sm:p-7 shadow-2xl shadow-indigo-950/10 border border-slate-200/90 space-y-5 relative overflow-hidden">
                                
                                <!-- Decorative Top Subtle Accent Line -->
                                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                                <!-- Card Header: Classroom Info -->
                                <div class="flex items-center justify-between gap-3 pb-4 border-b border-slate-100">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="h-11 w-11 sm:h-12 sm:w-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-300/50 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 tracking-tight leading-snug">Bahasa Indonesia 9A</h3>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-indigo-50/60 border border-slate-200/90 text-xs shadow-2xs transition-colors">
                                            <span class="text-slate-400 font-semibold text-[11px] uppercase tracking-wider">Kode</span>
                                            <span class="font-mono font-black text-indigo-600 tracking-widest text-xs">BIN9A</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress Overview Card (Stunning Visual Elevation) -->
                                <div class="bg-gradient-to-br from-slate-50/90 via-indigo-50/20 to-purple-50/20 rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-2xs space-y-2.5">
                                    <div class="flex items-center justify-between text-xs sm:text-sm">
                                        <span class="font-bold text-slate-800">Kemajuan Pembelajaran</span>
                                        <span class="inline-flex items-center gap-1 font-bold text-xs sm:text-sm px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs transition-all duration-300" id="hero-progress-text">
                                            <span>67% Selesai</span>
                                        </span>
                                    </div>
                                    
                                    <!-- Dynamic Progress Bar with Sheen -->
                                    <div class="w-full bg-slate-200/70 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-200/50">
                                        <div id="hero-progress-bar" class="bg-gradient-to-r from-indigo-600 to-purple-600 h-1.5 rounded-full transition-all duration-700 shadow-sm" style="width: 67%"></div>
                                    </div>

                                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed font-normal" id="hero-progress-hint">
                                        2 dari 3 materi telah selesai. Klik kuis ke-3 untuk evaluasi bab.
                                    </p>
                                </div>

                                <!-- Lessons List (Modular Interactive Cards) -->
                                <div class="space-y-2.5 text-xs sm:text-sm">
                                    <!-- Lesson 1 -->
                                    <div class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200/80 bg-white hover:border-indigo-200 hover:shadow-xs transition-all duration-200 group/item">
                                        <div class="flex items-center gap-3 min-w-0 pr-2">
                                            <span class="h-6 w-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200/80 shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                            <span class="font-semibold text-slate-800 text-xs sm:text-sm truncate group-hover/item:text-indigo-600 transition-colors">1. Konsep Dasar Persamaan</span>
                                        </div>
                                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-lg shrink-0">Selesai</span>
                                    </div>

                                    <!-- Lesson 2 -->
                                    <div class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200/80 bg-white hover:border-indigo-200 hover:shadow-xs transition-all duration-200 group/item">
                                        <div class="flex items-center gap-3 min-w-0 pr-2">
                                            <span class="h-6 w-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200/80 shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                            <span class="font-semibold text-slate-800 text-xs sm:text-sm truncate group-hover/item:text-indigo-600 transition-colors">2. Video Pembahasan & Latihan</span>
                                        </div>
                                        <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-lg shrink-0">Selesai</span>
                                    </div>

                                    <!-- Lesson 3: Interactive Quiz (Prestigious Highlight) -->
                                    <button type="button"
                                            id="hero-quiz-btn"
                                            class="w-full flex items-center justify-between p-3 sm:p-3.5 rounded-xl sm:rounded-2xl border transition-all duration-300 text-left cursor-pointer group/quiz border-indigo-200 bg-indigo-50/40 hover:bg-indigo-50/80 hover:shadow-xs">
                                        <div class="flex items-center gap-3 min-w-0 pr-2">
                                            <span id="hero-quiz-badge" class="h-6 w-6 rounded-full flex items-center justify-center shrink-0 border bg-indigo-100 text-indigo-600 border-indigo-200 shadow-2xs transition-colors">
                                                <svg id="hero-quiz-check-svg" class="hidden w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span id="hero-quiz-num" class="text-xs font-black text-indigo-600">3</span>
                                            </span>
                                            <span class="font-bold text-slate-900 text-xs sm:text-sm truncate group-hover/quiz:text-indigo-600 transition-colors">3. Kuis Evaluasi Pemahaman Bab</span>
                                        </div>
                                        <span id="hero-quiz-status" class="text-[11px] font-bold px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-all shrink-0">
                                            Mulai Kuis
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- SECTION: KOMPATIBILITAS DI SEMUA SISTEM OPERASI             -->
        <!-- ============================================================ -->
        <section id="kompatibilitas-os" class="py-12 sm:py-20 lg:py-24 bg-slate-50/60 border-y border-slate-200/70">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto space-y-8 sm:space-y-12">
                
                <div class="text-center max-w-2xl mx-auto space-y-2 sm:space-y-3">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                        Dapat Digunakan di Semua Sistem Operasi
                    </h2>
                    <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed font-normal">
                        RuangKelas berbasis web teknologi mutakhir. Tak peduli jenis ponsel, tablet, atau laptop yang Anda miliki, semuanya dapat diakses langsung tanpa hambatan.
                    </p>
                </div>

                <!-- 4 OS Cards Grid (Clean & Modern & Mobile Optimized) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    
                    <!-- 1. Android Card -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 hover:border-emerald-300 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 flex flex-col justify-between space-y-4">
                        <div class="space-y-3 sm:space-y-3.5">
                            <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100/80 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.551 0 .9993.4482.9993.9993.0001.5511-.4483.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5896 8.3526 13.8566 8 12 8s-3.5896.3526-5.1368.9507L4.8409 5.4477a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base">Android</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5">Smartphone & Tablet Android</p>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed font-normal">
                                Cukup buka lewat Chrome atau Edge. Hemat kuota dan RAM, lancar di ponsel spesifikasi minimalis tanpa risiko memori penuh.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-emerald-700 flex items-center gap-1.5">
                            <span>✓</span> Hemat RAM & Baterai
                        </div>
                    </div>

                    <!-- 2. iOS & iPadOS Card -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-300 flex flex-col justify-between space-y-4">
                        <div class="space-y-3 sm:space-y-3.5">
                            <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100/80 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.84c.64-.78 1.08-1.86.96-2.94-1 .04-2.15.66-2.82 1.44-.59.68-1.11 1.77-.97 2.83 1.13.09 2.19-.55 2.83-1.33z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base">iOS & iPadOS</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5">iPhone & iPad (Semua Seri)</p>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed font-normal">
                                Akses via Safari atau Chrome. Navigasi gestur responsif, tampilan tajam di layar Retina, serta nyaman untuk membaca materi.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-indigo-700 flex items-center gap-1.5">
                            <span>✓</span> Tampilan Halus & Responsif
                        </div>
                    </div>

                    <!-- 3. Windows Card -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 hover:border-sky-300 hover:shadow-xl hover:shadow-sky-500/5 transition-all duration-300 flex flex-col justify-between space-y-4">
                        <div class="space-y-3 sm:space-y-3.5">
                            <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100/80 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base">Windows</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5">PC Lab & Laptop Windows</p>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed font-normal">
                                Kompatibel dengan Edge, Chrome, dan Firefox. Sangat pas bagi guru menyusun silabus dan siswa mengerjakan ujian kuis.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-sky-700 flex items-center gap-1.5">
                            <span>✓</span> Nyaman untuk Papan Ketik
                        </div>
                    </div>

                    <!-- 4. macOS & ChromeOS Card -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 hover:border-purple-300 hover:shadow-xl hover:shadow-purple-500/5 transition-all duration-300 flex flex-col justify-between space-y-4">
                        <div class="space-y-3 sm:space-y-3.5">
                            <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100/80 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base">macOS & ChromeOS</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5">MacBook & Chromebook Sekolah</p>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed font-normal">
                                Optimal untuk Chromebook sekolah. Ringan, cepat dimuat, dan langsung berfungsi tanpa memerlukan izin administrator.
                            </p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 text-[11px] font-semibold text-purple-700 flex items-center gap-1.5">
                            <span>✓</span> Siap di Chromebook Sekolah
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ============================================================ -->
        <!-- FITUR UTAMA (CLEAN & MODERN GRID)                           -->
        <!-- ============================================================ -->
        <section id="fitur" class="py-12 sm:py-20 lg:py-24 bg-white">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto space-y-8 sm:space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                        Mengapa Belajar di RuangKelas Lebih Menyenangkan?
                    </h2>
                    <p class="text-xs sm:text-sm md:text-base text-slate-600 font-normal">
                        Dirancang dengan kesederhanaan untuk membantu fokus belajar dan kemudahan guru menyampaikan materi.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                    
                    <!-- Point 1 -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-7 border border-slate-200/80 hover:border-indigo-300 hover:shadow-xl hover:shadow-indigo-500/5 transition-all duration-300 space-y-3 sm:space-y-4">
                        <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100/80 group-hover:scale-105 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Bebas Ruang Penyimpanan</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                            Tidak memerlukan memori internal perangkat. Semua dokumen pelajaran, video penjelasan, dan tugas tersimpan rapi di cloud secara instan.
                        </p>
                    </div>

                    <!-- Point 2 -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-7 border border-slate-200/80 hover:border-emerald-300 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 space-y-3 sm:space-y-4">
                        <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100/80 group-hover:scale-105 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Struktur Bab Materi Rapi</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                            Materi disusun bertingkat dari Bab pendahuluan hingga evaluasi. Siswa dapat memantau indikator persentase pemahaman secara mandiri.
                        </p>
                    </div>

                    <!-- Point 3 -->
                    <div class="group bg-white rounded-2xl p-5 sm:p-7 border border-slate-200/80 hover:border-purple-300 hover:shadow-xl hover:shadow-purple-500/5 transition-all duration-300 space-y-3 sm:space-y-4">
                        <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100/80 group-hover:scale-105 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">Kuis Evaluasi Instan</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                            Pilihan ganda dengan koreksi otomatis. Hasil nilai dan pembahasan jawaban langsung muncul tepat setelah kuis disubmit oleh siswa.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- CARA PAKAI: 3 LANGKAH MUDAH                                 -->
        <!-- ============================================================ -->
        <section id="cara-kerja" class="py-12 sm:py-20 lg:py-24 bg-slate-50/60 border-t border-slate-200/70">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto space-y-8 sm:space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                        Mulai Belajar dalam 3 Langkah Mudah
                    </h2>
                    <p class="text-xs sm:text-sm md:text-base text-slate-600 font-normal">
                        Tidak perlu petunjuk yang rumit, ikuti tiga langkah mudah berikut:
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                    <div class="group p-5 sm:p-7 rounded-2xl bg-white border border-slate-200/80 hover:border-indigo-300 hover:shadow-lg transition-all duration-300 space-y-3 sm:space-y-3.5">
                        <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-indigo-600 text-white font-bold flex items-center justify-center text-xs sm:text-sm shadow-sm shadow-indigo-200">
                            01
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base">Buat Akun Gratis</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                            Daftar dalam waktu kurang dari semenit. Pilih peran apakah Anda Siswa yang ingin belajar atau Guru yang ingin membuat kelas.
                        </p>
                    </div>

                    <div class="group p-5 sm:p-7 rounded-2xl bg-white border border-slate-200/80 hover:border-purple-300 hover:shadow-lg transition-all duration-300 space-y-3 sm:space-y-3.5">
                        <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-purple-600 text-white font-bold flex items-center justify-center text-xs sm:text-sm shadow-sm shadow-purple-200">
                            02
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base">Masukkan Kode Kelas</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                            Cukup masukkan 6 digit kode kelas dari guru (misalnya <code class="font-mono font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">BIN9A</code>) untuk bergabung.
                        </p>
                    </div>

                    <div class="group p-5 sm:p-7 rounded-2xl bg-white border border-slate-200/80 hover:border-emerald-300 hover:shadow-lg transition-all duration-300 space-y-3 sm:space-y-3.5">
                        <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-emerald-600 text-white font-bold flex items-center justify-center text-xs sm:text-sm shadow-sm shadow-emerald-200">
                            03
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base">Belajar & Selesaikan Kuis</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                            Akses materi bacaan, tonton video pembelajaran, dan ukur pemahaman secara instan melalui evaluasi kuis interaktif.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- FAQ / TANYA JAWAB (ACCORDION DINAMIS)                       -->
        <!-- ============================================================ -->
        <section id="faq" class="py-12 sm:py-20 lg:py-24 bg-white border-t border-slate-200/80">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-4xl mx-auto space-y-6 sm:space-y-8">
                <div class="text-center space-y-2">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Hal yang Sering Ditanyakan</h2>
                </div>

                <div class="space-y-3 sm:space-y-3.5" id="faq-accordion">
                    <!-- Pertanyaan 1 -->
                    <div class="faq-item rounded-2xl border border-slate-200 bg-white shadow-2xs hover:border-slate-300 transition-all">
                        <button type="button" class="faq-toggle w-full text-left font-bold text-slate-900 text-xs sm:text-sm md:text-base p-4 sm:p-5 flex items-center justify-between gap-3 sm:gap-4 cursor-pointer focus:outline-none">
                            <span>Apakah benar-benar bisa dibuka di semua sistem operasi tanpa aplikasi?</span>
                            <span class="faq-icon text-indigo-600 text-lg sm:text-xl font-bold w-6 text-center shrink-0">−</span>
                        </button>
                        <div class="faq-content text-xs sm:text-sm text-slate-600 leading-relaxed px-4 sm:px-5 pb-4 sm:pb-5 pt-1 border-t border-slate-100">
                            Ya, 100% benar! RuangKelas dibuat berbasis Web App modern. Anda cukup membuka peramban bawaan seperti Chrome di Android, Safari di iPhone/iPad, ataupun Edge di Windows. Tidak perlu mengunduh file APK atau menginstal aplikasi tambahan.
                        </div>
                    </div>

                    <!-- Pertanyaan 2 -->
                    <div class="faq-item rounded-2xl border border-slate-200 bg-white shadow-2xs hover:border-slate-300 transition-all">
                        <button type="button" class="faq-toggle w-full text-left font-bold text-slate-900 text-xs sm:text-sm md:text-base p-4 sm:p-5 flex items-center justify-between gap-3 sm:gap-4 cursor-pointer focus:outline-none">
                            <span>Bagaimana cara guru membagikan kelas kepada siswa?</span>
                            <span class="faq-icon text-indigo-600 text-lg sm:text-xl font-bold w-6 text-center shrink-0">+</span>
                        </button>
                        <div class="faq-content text-xs sm:text-sm text-slate-600 leading-relaxed px-4 sm:px-5 pb-4 sm:pb-5 pt-1 border-t border-slate-100 hidden">
                            Setiap kali seorang guru membuat kelas baru, sistem secara otomatis menghasilkan kode unik 6-karakter (misal: <code>BIN9A</code>). Guru cukup menyalin dan membagikan kode tersebut ke WhatsApp kelas, lalu siswa mengetikkannya pada menu "Gabung Kelas".
                        </div>
                    </div>

                    <!-- Pertanyaan 3 -->
                    <div class="faq-item rounded-2xl border border-slate-200 bg-white shadow-2xs hover:border-slate-300 transition-all">
                        <button type="button" class="faq-toggle w-full text-left font-bold text-slate-900 text-xs sm:text-sm md:text-base p-4 sm:p-5 flex items-center justify-between gap-3 sm:gap-4 cursor-pointer focus:outline-none">
                            <span>Apakah ada akun demo untuk mencoba fitur sebelum mendaftar?</span>
                            <span class="faq-icon text-indigo-600 text-lg sm:text-xl font-bold w-6 text-center shrink-0">+</span>
                        </button>
                        <div class="faq-content text-xs sm:text-sm text-slate-600 leading-relaxed px-4 sm:px-5 pb-4 sm:pb-5 pt-1 border-t border-slate-100 hidden">
                            Tentu ada! Pada halaman Masuk (Login), kami menyediakan tombol uji coba 1-klik untuk akun Guru maupun Siswa lengkap dengan data kelas dan contoh materi.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- FOOTER MODERN & MINIMALIS (FULL-WIDTH & RESPONSIVE)         -->
        <!-- ============================================================ -->
        <footer class="py-6 sm:py-8 bg-slate-950 text-slate-400 text-xs border-t border-slate-800">
            <div class="w-full px-4 sm:px-8 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 text-center sm:text-left">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1.5 sm:gap-2">
                    <span class="font-extrabold text-white text-xs sm:text-sm">RuangKelas</span>
                    <span class="text-slate-600">&bull;</span>
                    <span class="text-[11px] sm:text-xs">Belajar & Mengajar di Semua Sistem Operasi</span>
                </div>
                <p class="text-slate-500 text-[11px] sm:text-xs text-center sm:text-right">
                    &copy; {{ date('Y') }} RuangKelas. Ringan, cepat, dan ramah untuk pendidikan Indonesia.
                </p>
            </div>
        </footer>

        <!-- SCRIPT INTERAKTIF (FAQ ACCORDION & HERO QUIZ) -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // 1. FAQ ACCORDION DINAMIS (BISA DIKLIK-KLIK)
                const faqItems = document.querySelectorAll('#faq-accordion .faq-item');
                
                faqItems.forEach(item => {
                    const button = item.querySelector('.faq-toggle');
                    const content = item.querySelector('.faq-content');
                    const icon = item.querySelector('.faq-icon');
                    
                    button.addEventListener('click', function () {
                        const isCurrentlyOpen = !content.classList.contains('hidden');
                        
                        // Tutup semua item lain
                        faqItems.forEach(otherItem => {
                            const otherContent = otherItem.querySelector('.faq-content');
                            const otherIcon = otherItem.querySelector('.faq-icon');
                            otherContent.classList.add('hidden');
                            otherIcon.textContent = '+';
                        });
                        
                        // Toggle item yang diklik
                        if (!isCurrentlyOpen) {
                            content.classList.remove('hidden');
                            icon.textContent = '−';
                        }
                    });
                });

                // 2. HERO CLASSROOM SHOWCASE QUIZ INTERACTIVE TOGGLE
                const quizBtn = document.getElementById('hero-quiz-btn');
                if (quizBtn) {
                    let completed = false;
                    quizBtn.addEventListener('click', function () {
                        completed = !completed;
                        const statusText = document.getElementById('hero-quiz-status');
                        const badgeSpan = document.getElementById('hero-quiz-badge');
                        const checkSvg = document.getElementById('hero-quiz-check-svg');
                        const numSpan = document.getElementById('hero-quiz-num');
                        const progressText = document.getElementById('hero-progress-text');
                        const progressBar = document.getElementById('hero-progress-bar');
                        const progressHint = document.getElementById('hero-progress-hint');

                        if (completed) {
                            this.className = 'w-full flex items-center justify-between p-3 sm:p-3.5 rounded-xl sm:rounded-2xl border transition-all duration-300 text-left cursor-pointer group/quiz border-emerald-200/90 bg-emerald-50/40 hover:bg-emerald-50/80 hover:shadow-xs';
                            if (statusText) {
                                statusText.textContent = 'Selesai';
                                statusText.className = 'text-[11px] font-bold px-3 py-1 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-xs transition-all shrink-0';
                            }
                            if (badgeSpan) badgeSpan.className = 'h-6 w-6 rounded-full flex items-center justify-center shrink-0 border bg-emerald-100 text-emerald-600 border-emerald-300 shadow-2xs transition-colors';
                            if (checkSvg) checkSvg.classList.remove('hidden');
                            if (numSpan) numSpan.classList.add('hidden');
                            if (progressText) {
                                progressText.innerHTML = `
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>100% Selesai</span>
                                `;
                                progressText.className = 'inline-flex items-center gap-1 font-bold text-xs sm:text-sm px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs transition-all duration-300';
                            }
                            if (progressBar) {
                                progressBar.className = 'bg-gradient-to-r from-indigo-600 via-purple-600 to-emerald-500 h-1.5 rounded-full transition-all duration-700 shadow-sm';
                                progressBar.style.width = '100%';
                            }
                            if (progressHint) progressHint.textContent = 'Seluruh bab modul dan evaluasi telah berhasil diselesaikan.';
                        } else {
                            this.className = 'w-full flex items-center justify-between p-3 sm:p-3.5 rounded-xl sm:rounded-2xl border transition-all duration-300 text-left cursor-pointer group/quiz border-indigo-200 bg-indigo-50/40 hover:bg-indigo-50/80 hover:shadow-xs';
                            if (statusText) {
                                statusText.textContent = 'Mulai Kuis';
                                statusText.className = 'text-[11px] font-bold px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-all shrink-0';
                            }
                            if (badgeSpan) badgeSpan.className = 'h-6 w-6 rounded-full flex items-center justify-center shrink-0 border bg-indigo-100 text-indigo-600 border-indigo-200 shadow-2xs transition-colors';
                            if (checkSvg) checkSvg.classList.add('hidden');
                            if (numSpan) numSpan.classList.remove('hidden');
                            if (progressText) {
                                progressText.innerHTML = '<span>67% Selesai</span>';
                                progressText.className = 'inline-flex items-center gap-1 font-bold text-xs sm:text-sm px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs transition-all duration-300';
                            }
                            if (progressBar) {
                                progressBar.className = 'bg-gradient-to-r from-indigo-600 to-purple-600 h-1.5 rounded-full transition-all duration-700 shadow-sm';
                                progressBar.style.width = '67%';
                            }
                            if (progressHint) progressHint.textContent = '2 dari 3 materi telah selesai. Klik kuis ke-3 untuk evaluasi bab.';
                        }
                    });
                }

                // 3. HEADER DYNAMIC SCROLL GLOW & TRANSPARENCY
                const mainHeader = document.getElementById('main-header');
                if (mainHeader) {
                    const updateHeaderGlow = () => {
                        if (window.scrollY > 15) {
                            mainHeader.classList.add('header-scrolled');
                        } else {
                            mainHeader.classList.remove('header-scrolled');
                        }
                    };
                    window.addEventListener('scroll', updateHeaderGlow, { passive: true });
                    updateHeaderGlow();
                }
            });
        </script>

    </body>
</html>
