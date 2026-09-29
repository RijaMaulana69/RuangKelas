<!-- ============================================================ -->
<!-- SIDEBAR NAVIGATION RUANGKELAS (Desktop Only, lg+)           -->
<!-- ============================================================ -->

<aside class="hidden lg:flex w-64 xl:w-72 bg-slate-900 border-r border-slate-800 flex-col h-[calc(100vh-70px)] sticky top-[70px] shrink-0 text-slate-200">
    
    <!-- Navigasi Menu Sidebar -->
    <div class="flex-1 overflow-y-auto px-3.5 py-6 space-y-6">
        @php
            $isGuru = auth()->user()->hasRole('guru');
            $isSiswa = auth()->user()->hasRole('siswa');
            $isDashActive = request()->routeIs('dashboard') || request()->routeIs('guru.dashboard') || request()->routeIs('siswa.dashboard');
            $isKelasActive = request()->routeIs('guru.kelas.*') || request()->routeIs('siswa.kelas.*');
            $isProfileActive = request()->routeIs('profile');
        @endphp

        <!-- Group: Navigasi Utama -->
        <div>
            <span class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-2">
                Menu Utama
            </span>
            <div class="space-y-1">
                <!-- Beranda -->
                <a href="{{ route('dashboard') }}" 
                   wire:navigate 
                   class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isDashActive ? ($isGuru ? 'bg-indigo-600 text-white shadow-md shadow-indigo-950/80 border border-indigo-500/40' : 'bg-emerald-600 text-white shadow-md shadow-emerald-950/80 border border-emerald-500/40') : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isDashActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isDashActive ? '2.3' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Beranda</span>
                </a>

                <!-- Kelola Kelas (Khusus Guru) -->
                @if($isGuru)
                    <a href="{{ route('guru.kelas.index') }}" 
                       wire:navigate 
                       class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isKelasActive ? 'bg-indigo-600 text-white shadow-md shadow-indigo-950/80 border border-indigo-500/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isKelasActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Kelola Kelas</span>
                    </a>
                @endif

                <!-- Kelas Saya (Khusus Siswa) -->
                @if($isSiswa)
                    <a href="{{ route('siswa.kelas.index') }}" 
                       wire:navigate 
                       class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isKelasActive ? 'bg-emerald-600 text-white shadow-md shadow-emerald-950/80 border border-emerald-500/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isKelasActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isKelasActive ? '2.3' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Kelas Saya</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Group: Pengaturan Akun -->
        <div>
            <span class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-2">
                Akun & Preferensi
            </span>
            <div class="space-y-1">
                <a href="{{ route('profile') }}" 
                   wire:navigate 
                   class="group flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm transition-all duration-150 {{ $isProfileActive ? ($isGuru ? 'bg-indigo-600 text-white shadow-md shadow-indigo-950/80' : 'bg-emerald-600 text-white shadow-md shadow-emerald-950/80') : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $isProfileActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isProfileActive ? '2.3' : '1.8' }}" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Profil Akun</span>
                </a>
            </div>
        </div>
    </div>
</aside>
