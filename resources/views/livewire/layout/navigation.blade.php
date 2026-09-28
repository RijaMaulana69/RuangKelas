<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white/95 backdrop-blur-xl border-b border-slate-200/80 shadow-xs sticky top-0 z-50 transition">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-4 sm:px-8 lg:px-12">
        <div class="flex justify-between items-center min-h-[76px] py-2">
            <div class="flex items-center gap-8">
                <!-- Logo Brand -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group">
                        <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-300/60 group-hover:scale-105 transition transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.993 10.993 0 01-1.25.949 1 1 0 101.414 1.414c.484-.484.97-1.002 1.428-1.547L10 10.155l3.158-1.29a17.07 17.07 0 001.428 1.547 1 1 0 101.414-1.414 10.993 10.993 0 01-1.25-.949l2.644-1.13a1 1 0 000-1.84l-7-3z" />
                                <path d="M4.32 10.874A11.003 11.003 0 0010 13c2.478 0 4.67-.818 6.42-2.126l.83.356a1 1 0 01.598.924v3.846a1 1 0 01-.598.924l-7 3a1 1 0 01-.804 0l-7-3a1 1 0 01-.598-.924v-3.846a1 1 0 01.598-.924l.872-.376z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-black text-xl sm:text-2xl text-slate-900 tracking-tight leading-none">
                                Ruang<span class="text-indigo-600">Kelas</span>
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase mt-0.5">
                                Belajar & Mengajar
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links (Pill Style Modern) -->
                <div class="hidden sm:flex items-center gap-1.5 bg-slate-100/80 p-1.5 rounded-2xl border border-slate-200/60 text-sm font-bold">
                    <a href="{{ route('dashboard') }}" wire:navigate class="px-4 py-2 rounded-xl transition {{ request()->routeIs('dashboard') || request()->routeIs('guru.dashboard') || request()->routeIs('siswa.dashboard') ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-indigo-600' }}">
                        Beranda
                    </a>

                    @if(auth()->user()->hasRole('guru'))
                        <a href="{{ route('guru.kelas.index') }}" wire:navigate class="px-4 py-2 rounded-xl transition {{ request()->routeIs('guru.kelas.*') ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-600 hover:text-indigo-600' }}">
                            Kelola Kelas
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('siswa'))
                        <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="px-4 py-2 rounded-xl transition {{ request()->routeIs('siswa.kelas.*') ? 'bg-white text-emerald-600 shadow-xs' : 'text-slate-600 hover:text-emerald-600' }}">
                            Kelas Saya
                        </a>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:gap-3 sm:ms-6">
                <!-- Role Badge -->
                @if(auth()->user()->hasRole('guru'))
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                        👨‍🏫 Akun Guru
                    </span>
                @elseif(auth()->user()->hasRole('siswa'))
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                        🎒 Akun Siswa
                    </span>
                @endif

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 px-3.5 py-2 border border-slate-200 text-sm font-bold rounded-2xl text-slate-700 bg-white hover:text-slate-900 hover:bg-slate-50 focus:outline-none transition shadow-2xs">
                            <div class="h-8 w-8 rounded-xl bg-gradient-to-tr from-slate-700 to-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </div>
                            <span class="truncate max-w-[130px]">{{ auth()->user()->name }}</span>

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

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link class="text-red-600 hover:bg-red-50 font-semibold text-xs py-2.5">
                                🚪 Keluar (Logout)
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger Button for Small Screens -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2.5 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white border-t border-slate-100 px-4 py-4 space-y-3">
        <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-100">
            <div>
                <div class="font-bold text-sm text-slate-900">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-400">{{ auth()->user()->email }}</div>
            </div>
            <div>
                @if(auth()->user()->hasRole('guru'))
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-700">Guru</span>
                @elseif(auth()->user()->hasRole('siswa'))
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700">Siswa</span>
                @endif
            </div>
        </div>

        <div class="flex flex-col space-y-1 font-bold text-sm text-slate-700">
            <a href="{{ route('dashboard') }}" wire:navigate class="p-3 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition">
                🏠 Beranda (Dashboard)
            </a>

            @if(auth()->user()->hasRole('guru'))
                <a href="{{ route('guru.kelas.index') }}" wire:navigate class="p-3 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition">
                    📚 Kelola Kelas
                </a>
            @endif

            @if(auth()->user()->hasRole('siswa'))
                <a href="{{ route('siswa.kelas.index') }}" wire:navigate class="p-3 rounded-xl hover:bg-emerald-50 hover:text-emerald-600 transition">
                    🎒 Kelas Saya
                </a>
            @endif

            <a href="{{ route('profile') }}" wire:navigate class="p-3 rounded-xl hover:bg-slate-100 transition">
                👤 Profil Akun
            </a>

            <button wire:click="logout" class="w-full text-left p-3 rounded-xl text-red-600 hover:bg-red-50 transition">
                🚪 Keluar (Logout)
            </button>
        </div>
    </div>
</nav>
