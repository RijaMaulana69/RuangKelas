<!-- ============================================================ -->
<!-- SIDEBAR NAVIGATION RUANGKELAS                                -->
<!-- Mendukung Desktop (Static Sticky) & Mobile (Slide-over Drawer)-->
<!-- ============================================================ -->

<!-- Mobile Backdrop Overlay -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false" 
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
     style="display: none;"></div>

<!-- Sidebar Container -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-50 w-64 xl:w-72 bg-white border-r border-slate-200/80 flex flex-col transition-transform duration-300 ease-in-out lg:static lg:h-screen lg:sticky lg:top-0 shrink-0">
    
    <!-- 1. Header Sidebar: Brand Logo -->
    <div class="h-[74px] px-6 flex items-center justify-between border-b border-slate-100 shrink-0">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group">
            <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 flex items-center justify-center text-white shadow-md shadow-indigo-200 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                    <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                </svg>
            </div>
            <div>
                <span class="font-black text-xl text-slate-900 tracking-tight leading-none">
                    Ruang<span class="text-indigo-600">Kelas</span>
                </span>
                <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block mt-0.5">
                    Belajar & Mengajar
                </span>
            </div>
        </a>

        <!-- Tombol Tutup Sidebar di Mobile -->
        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- 2. Profil Singkat Pengguna -->
    <div class="px-5 py-4 border-b border-slate-100/80 bg-slate-50/50">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl {{ auth()->user()->hasRole('guru') ? 'bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-sm shadow-indigo-200' : 'bg-gradient-to-tr from-emerald-600 to-teal-600 text-white shadow-sm shadow-emerald-200' }} flex items-center justify-center font-bold text-xs uppercase shrink-0">
                {{ substr(auth()->user()->name, 0, 2) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-slate-900 truncate leading-tight">{{ auth()->user()->name }}</p>
                <div class="mt-1">
                    @if(auth()->user()->hasRole('guru'))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100/70 text-indigo-700">
                            👨‍🏫 Guru Pengajar
                        </span>
                    @elseif(auth()->user()->hasRole('siswa'))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100/70 text-emerald-700">
                            🎒 Siswa Terdaftar
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Navigasi Menu Sidebar (Scrollable) -->
    <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">
        @php
            $isGuru = auth()->user()->hasRole('guru');
            $isSiswa = auth()->user()->hasRole('siswa');
            $isDashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard') || request()->routeIs('siswa.dashboard');
            $isKelasActive = request()->routeIs('guru.kelas.*') || request()->routeIs('siswa.kelas.*');
            $isProfileActive = request()->routeIs('profile');
        @endphp

        <!-- Group: Navigasi Utama -->
        <div>
            <span class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">
                Menu Utama
            </span>
            <div class="space-y-1">
                <!-- Beranda -->
                <a href="{{ route('dashboard') }}" 
                   wire:navigate 
                   @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isDashActive ? ($isGuru ? 'bg-indigo-50 text-indigo-600 shadow-2xs' : 'bg-emerald-50 text-emerald-600 shadow-2xs') : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isDashActive ? ($isGuru ? 'text-indigo-600' : 'text-emerald-600') : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isDashActive ? '2.3' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Beranda</span>
                </a>

                <!-- Kelola Kelas (Khusus Guru) -->
                @if($isGuru)
                    <a href="{{ route('guru.kelas.index') }}" 
                       wire:navigate 
                       @click="sidebarOpen = false"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isKelasActive ? 'bg-indigo-50 text-indigo-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isKelasActive ? 'text-indigo-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Kelola Kelas</span>
                    </a>
                @endif

                <!-- Kelas Saya (Khusus Siswa) -->
                @if($isSiswa)
                    <a href="{{ route('siswa.kelas.index') }}" 
                       wire:navigate 
                       @click="sidebarOpen = false"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isKelasActive ? 'bg-emerald-50 text-emerald-600 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isKelasActive ? 'text-emerald-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Kelas Saya</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Group: Pengaturan Akun -->
        <div>
            <span class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">
                Akun & Preferensi
            </span>
            <div class="space-y-1">
                <a href="{{ route('profile') }}" 
                   wire:navigate 
                   @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isProfileActive ? ($isGuru ? 'bg-indigo-50 text-indigo-600 shadow-2xs' : 'bg-emerald-50 text-emerald-600 shadow-2xs') : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isProfileActive ? ($isGuru ? 'text-indigo-600' : 'text-emerald-600') : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isProfileActive ? '2.3' : '1.8' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Profil Akun</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4. Footer Sidebar: Logout & Copyright -->
    <div class="p-3.5 border-t border-slate-100 bg-slate-50/50 space-y-2">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Keluar (Logout)</span>
            </button>
        </form>

        <div class="px-3 pt-1 text-[11px] text-slate-400 font-medium text-center">
            RuangKelas LMS &copy; 2026
        </div>
    </div>
</aside>
