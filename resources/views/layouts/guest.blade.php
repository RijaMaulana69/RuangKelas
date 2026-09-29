<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RuangKelas') }} — Masuk</title>
        <meta name="description" content="Masuk ke portal RuangKelas untuk mulai belajar atau mengajar.">

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 min-h-screen selection:bg-indigo-600 selection:text-white relative overflow-x-hidden flex flex-col justify-between">
        
        <!-- Fixed Seamless Background Gradient (Menutupi seluruh layar & tidak terpotong saat scroll) -->
        <div class="fixed inset-0 bg-gradient-to-br from-indigo-100/90 via-slate-100/80 to-purple-100/70 pointer-events-none -z-20"></div>

        <!-- Ambient Decorative Glows (Selaras dengan Tema Indigo RuangKelas) -->
        <div class="fixed -top-20 -left-20 w-80 sm:w-[450px] h-80 sm:h-[450px] bg-indigo-300/35 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="fixed top-1/3 -right-20 w-80 sm:w-[450px] h-80 sm:h-[450px] bg-purple-300/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="fixed -bottom-20 left-1/4 w-80 sm:w-[450px] h-80 sm:h-[450px] bg-indigo-200/40 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <!-- Main Wrapper Terpusat & Responsive -->
        <div class="w-full flex-1 flex flex-col justify-center items-center py-6 sm:py-12 px-4 sm:px-6 relative z-10">
            
            <!-- Logo RuangKelas (Selaras dengan Header Landing Page) -->
            <div class="text-center mb-5 sm:mb-7">
                <a href="/" wire:navigate class="inline-flex items-center gap-2 sm:gap-2.5 group">
                    <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-200 group-hover:scale-105 group-hover:shadow-indigo-300 transition-all duration-300 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-5.5 sm:w-5.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                            <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                        </svg>
                    </div>
                    <span class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                        Ruang<span class="text-indigo-600">Kelas</span>
                    </span>
                </a>
            </div>

            <!-- Card Container -->
            <main class="w-full max-w-[440px]">
                {{ $slot }}
            </main>

        </div>

        <!-- Footer Mini -->
        <footer class="w-full text-center py-4 px-4 text-[11px] sm:text-xs text-slate-400 border-t border-slate-200/50">
            &copy; {{ date('Y') }} RuangKelas &bull; Platform Belajar & Mengajar
        </footer>
    </body>
</html>
