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

        <title>RuangKelas — Belajar Asik, Mengajar Nggak Pakai Ribet</title>
        <meta name="description" content="Tempat belajar online yang ringan, terstruktur per bab, dan bisa diakses dari HP, tablet, maupun laptop tanpa perlu download aplikasi.">

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-white selection:bg-indigo-600 selection:text-white relative overflow-x-hidden">

        <!-- ============================================================ -->
        <!-- HEADER FULL-WIDTH LEBIH KE POJOK SESUAI UKURAN LAYAR         -->
        <!-- Menggunakan w-full dan px-4 sm:px-8 lg:px-12 agar pas tepi   -->
        <!-- ============================================================ -->
        <header x-data="{ mobileOpen: false }" class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80 transition-all">
            <div class="w-full px-4 sm:px-8 lg:px-12 flex items-center justify-between min-h-[76px] py-2.5">
                
                <!-- 1. POJOK KIRI: LOGO BRAND -->
                <a href="/" class="flex items-center gap-3 group shrink-0">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-300/50 group-hover:scale-105 transition-transform duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                            <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-2xl font-black text-slate-900 tracking-tight block leading-tight">
                            Ruang<span class="text-indigo-600">Kelas</span>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-400 block tracking-wide">
                            Tempat Belajar Online Ringan
                        </span>
                    </div>
                </a>

                <!-- 2. POJOK KANAN: TOMBOL AKSI -->
                <div class="flex items-center gap-3 shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-sm px-5 sm:px-6 py-2.5 rounded-2xl shadow-md shadow-indigo-200 transition transform active:scale-95">
                            <span>Buka Kelas Saya</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs sm:text-sm px-5 sm:px-6 py-2 sm:py-2.5 rounded-2xl shadow-md shadow-indigo-200 transition transform active:scale-95">
                            <span>Masuk</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- ============================================================ -->
        <!-- HERO SECTION: CLEAN, WARM, HUMANIZE                         -->
        <!-- ============================================================ -->
        <section class="pt-10 pb-16 sm:pt-16 sm:pb-24 overflow-hidden">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                    
                    <!-- Left Hero Content -->
                    <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                        <!-- Friendly Badge -->
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100/80 shadow-2xs">
                            <span class="text-sm">🎈</span>
                            <span>Belajar Santai, Nilai Maksimal Tanpa Ribet</span>
                        </div>

                        <!-- Hero Title -->
                        <h1 class="text-3xl sm:text-5xl lg:text-[54px] font-black text-slate-900 tracking-tight leading-[1.18]">
                            Tempat Belajar yang Nyaman, <br class="hidden sm:inline">
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500">
                                Tanpa Bikin Pusing.
                            </span>
                        </h1>

                        <!-- Humanized Subtitle -->
                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                            Nggak perlu install aplikasi yang bikin memori HP cepat penuh. Cukup buka lewat browser di HP Android, iPhone, tablet, atau laptop. Materi tersusun rapi per bab, video jelas, dan kuisnya bikin belajar berasa seru!
                        </p>

                        <!-- CTA Actions -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5 pt-2">
                            <a href="{{ route('register') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm sm:text-base px-8 py-4 rounded-2xl shadow-xl shadow-indigo-200 transition transform active:scale-95">
                                🎒 Gabung Sebagai Siswa
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            <a href="{{ route('register') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm sm:text-base px-7 py-4 rounded-2xl transition">
                                👨‍🏫 Saya Ingin Mengajar
                            </a>
                        </div>

                        <!-- Micro Trust Highlights -->
                        <div class="pt-6 border-t border-slate-100 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs font-semibold text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <span class="text-green-500 font-bold">✓</span> Langsung Pakai Tanpa Install
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="text-green-500 font-bold">✓</span> Ringan di HP RAM Kecil
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="text-green-500 font-bold">✓</span> Masuk Kelas Pakai Kode 6 Digit
                            </span>
                        </div>
                    </div>

                    <!-- Right Mockup Preview -->
                    <div class="lg:col-span-5 relative">
                        <div class="bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-3xl p-1 shadow-2xl shadow-indigo-200">
                            <div class="bg-white rounded-[22px] p-6 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-10 w-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base">
                                            📐
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-900 leading-snug">Matematika Dasar X</h4>
                                            <p class="text-xs text-slate-400">Pak Guru Budi &bull; SMA</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        MTK10A
                                    </span>
                                </div>

                                <!-- Progress Card -->
                                <div class="bg-emerald-50/70 rounded-2xl p-3.5 border border-emerald-100 space-y-1.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-emerald-950">Kemajuan Belajarmu</span>
                                        <span class="font-extrabold text-emerald-600">75%</span>
                                    </div>
                                    <div class="w-full bg-emerald-200/60 rounded-full h-2 overflow-hidden">
                                        <div class="bg-emerald-600 h-2 rounded-full w-3/4"></div>
                                    </div>
                                    <p class="text-[11px] text-emerald-700">Tinggal 1 materi lagi menuju bab berikutnya!</p>
                                </div>

                                <!-- Sample List -->
                                <div class="space-y-2 text-xs">
                                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-green-200 bg-green-50/40">
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600 font-bold">✓</span>
                                            <span class="font-medium text-slate-700">1. Konsep Dasar Persamaan</span>
                                        </div>
                                        <span class="text-[10px] text-green-700 font-bold">Selesai</span>
                                    </div>

                                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-green-200 bg-green-50/40">
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600 font-bold">✓</span>
                                            <span class="font-medium text-slate-700">2. Video Contoh Soal PLSV</span>
                                        </div>
                                        <span class="text-[10px] text-green-700 font-bold">Selesai</span>
                                    </div>

                                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-purple-200 bg-purple-50/40">
                                        <div class="flex items-center gap-2">
                                            <span>🎯</span>
                                            <span class="font-bold text-purple-900">3. Kuis Latihan Pemahaman</span>
                                        </div>
                                        <span class="text-[10px] bg-purple-600 text-white font-bold px-2 py-0.5 rounded">Skor: 100</span>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                                    <span>📱 Nyaman di Layar HP</span>
                                    <span class="text-indigo-600 font-bold">Demo Siap Coba ✓</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- KENAPA RUANGKELAS: HUMANIZE REASONS                          -->
        <!-- ============================================================ -->
        <section id="kenapa-kami" class="py-16 sm:py-20 bg-slate-50 border-y border-slate-100">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14 space-y-2">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Kenapa RuangKelas?</span>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                        Dibuat untuk Menyederhanakan Hari Belajarmu
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600">
                        Kami percaya belajar itu harusnya bikin mengerti, bukan bikin pusing dengan aplikasi rumit.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                    <!-- Point 1 -->
                    <div class="bg-white rounded-3xl p-7 border border-slate-200/80 shadow-xs space-y-3">
                        <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                            📦
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Nol Unduhan, Bebas Memori</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            HP kentang atau memori tinggal sedikit? Tenang saja, RuangKelas dibuka langsung lewat browser. Nggak makan penyimpanan sama sekali.
                        </p>
                    </div>

                    <!-- Point 2 -->
                    <div class="bg-white rounded-3xl p-7 border border-slate-200/80 shadow-xs space-y-3">
                        <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold">
                            🗺️
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Alur Belajar Jelas Per Bab</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Materi nggak berserakan. Guru menyusun langkah demi langkah dari Bab 1, video penjelasan, hingga rangkuman teks yang enak dibaca.
                        </p>
                    </div>

                    <!-- Point 3 -->
                    <div class="bg-white rounded-3xl p-7 border border-slate-200/80 shadow-xs space-y-3">
                        <div class="h-12 w-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl font-bold">
                            💡
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Kuis Instan dengan Pembahasan</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Langsung tahu nilai dan jawaban yang benar tanpa menunggu besok. Kalau salah, ada pembahasannya biar langsung paham!
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- CARA PAKAI: 3 LANGKAH MUDAH                                 -->
        <!-- ============================================================ -->
        <section id="cara-kerja" class="py-16 sm:py-20 bg-white">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-14 space-y-2">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Cepat & Gampang</span>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">
                        Cara Mulai dalam 3 Langkah
                    </h2>
                    <p class="text-sm text-slate-600">
                        Nggak butuh panduan tebal. Ikuti tiga langkah sederhana ini:
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                    <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-indigo-200">
                            1
                        </div>
                        <h3 class="font-bold text-slate-900 text-base">Buat Akun Gratis</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Daftar dalam waktu kurang dari semenit. Pilih apakah kamu Siswa yang mau belajar, atau Guru yang mau membagikan materi.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="h-10 w-10 rounded-xl bg-purple-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-purple-200">
                            2
                        </div>
                        <h3 class="font-bold text-slate-900 text-base">Ketik Kode Kelas</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Minta 6 kode huruf dari gurumu (misalnya: <code class="font-bold text-indigo-600">MTK10A</code>), masukkan ke kotak gabung, dan kamu langsung terdaftar!
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="h-10 w-10 rounded-xl bg-emerald-600 text-white font-black flex items-center justify-center text-sm shadow-md shadow-emerald-200">
                            3
                        </div>
                        <h3 class="font-bold text-slate-900 text-base">Mulai Belajar & Kuis</h3>
                        <p class="text-xs sm:text-sm text-slate-600">
                            Buka materi, tonton video, tandai selesai setelah paham, lalu coba kuis latihan untuk membuktikan kemampuanmu!
                        </p>
                    </div>
                </div>
            </div>
        </section>


        <!-- ============================================================ -->
        <!-- FAQ / TANYA JAWAB (HUMANIZE)                                -->
        <!-- ============================================================ -->
        <section id="faq" class="py-16 sm:py-20 bg-white">
            <div class="w-full px-4 sm:px-8 lg:px-12 max-w-4xl mx-auto space-y-10">
                <div class="text-center space-y-2">
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Tanya Jawab Santai</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Hal yang Sering Ditanyakan</h2>
                </div>

                <div class="space-y-3.5" x-data="{ open: null }">
                    <div class="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/60">
                        <button @click="open = (open === 1 ? null : 1)" class="w-full text-left font-bold text-slate-900 text-sm sm:text-base flex items-center justify-between gap-4">
                            <span>Beneran nggak perlu install aplikasi dari Play Store?</span>
                            <span class="text-indigo-600 text-lg font-bold" x-text="open === 1 ? '−' : '+'">+</span>
                        </button>
                        <div x-show="open === 1" x-collapse class="mt-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed pt-2 border-t border-slate-200/60">
                            Iya, beneran! RuangKelas dibuat berbasis website modern. Kamu cukup buka Chrome atau Safari di HP-mu, masukkan alamat website, dan semua fitur bisa langsung dipakai tanpa download APK atau makan memori HP.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/60">
                        <button @click="open = (open === 2 ? null : 2)" class="w-full text-left font-bold text-slate-900 text-sm sm:text-base flex items-center justify-between gap-4">
                            <span>Bisa dibuka di iPhone atau komputer sekolah?</span>
                            <span class="text-indigo-600 text-lg font-bold" x-text="open === 2 ? '−' : '+'">+</span>
                        </button>
                        <div x-show="open === 2" x-collapse class="mt-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed pt-2 border-t border-slate-200/60">
                            Bisa banget. RuangKelas otomatis menyesuaikan ukuran layar dari HP Android, iPhone, iPad, laptop Windows, sampai Mac dan Chromebook.
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/60">
                        <button @click="open = (open === 3 ? null : 3)" class="w-full text-left font-bold text-slate-900 text-sm sm:text-base flex items-center justify-between gap-4">
                            <span>Gimana caranya siswa ikut ke kelas saya?</span>
                            <span class="text-indigo-600 text-lg font-bold" x-text="open === 3 ? '−' : '+'">+</span>
                        </button>
                        <div x-show="open === 3" x-collapse class="mt-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed pt-2 border-t border-slate-200/60">
                            Saat guru membuat kelas, sistem otomatis menghasilkan 6 huruf kode unik (misal: <code>MTK10A</code>). Guru tinggal salin kode itu dan bagikan ke grup WhatsApp siswa. Siswa tinggal masukkan kode itu di akun mereka.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- FOOTER                                                      -->
        <!-- ============================================================ -->
        <footer class="py-10 bg-slate-950 text-slate-400 text-xs border-t border-slate-900">
            <div class="w-full px-4 sm:px-8 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-white text-sm">RuangKelas</span>
                    <span>&bull; Belajar Asik di Semua Perangkat</span>
                </div>
                <p class="text-slate-500 text-center sm:text-right">
                    &copy; {{ date('Y') }} RuangKelas. Dirancang ringan untuk siswa & guru Indonesia.
                </p>
            </div>
        </footer>

    </body>
</html>
