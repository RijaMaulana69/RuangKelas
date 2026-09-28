<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RuangKelas') }} - Masuk / Daftar</title>

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-[#FAF9FD] min-h-full selection:bg-indigo-500 selection:text-white relative overflow-x-hidden">
        
        <!-- Soft Ambient Background Glows -->
        <div class="fixed -top-32 -left-32 w-80 h-80 bg-indigo-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="fixed top-1/4 -right-32 w-80 h-80 bg-purple-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="fixed -bottom-32 left-1/4 w-80 h-80 bg-amber-100/50 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Main Wrapper Terpusat -->
        <div class="min-h-screen flex flex-col justify-center items-center py-10 sm:py-12 px-4 sm:px-6 relative z-10">
            
            <!-- Logo RuangKelas: Terpusat di Atas Card -->
            <div class="text-center mb-6">
                <a href="/" wire:navigate class="inline-flex items-center gap-2.5 group">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 flex items-center justify-center text-white shadow-md shadow-indigo-200 group-hover:scale-105 transition transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                            <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                        </svg>
                    </div>
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Ruang<span class="text-indigo-600">Kelas</span>
                    </span>
                </a>
            </div>

            <!-- Card Container -->
            <main class="w-full max-w-md sm:max-w-md">
                {{ $slot }}
            </main>

            <!-- Footer Mini -->
            <footer class="w-full max-w-md text-center pt-6 text-xs text-slate-400">
                &copy; {{ date('Y') }} RuangKelas &bull; Platform Belajar & Mengajar Indonesia
            </footer>

        </div>
    </body>
</html>
