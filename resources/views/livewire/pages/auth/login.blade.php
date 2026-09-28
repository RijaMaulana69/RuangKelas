<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public string $demoSelected = '';

    /**
     * Mengisi form login dengan akun demo
     */
    public function fillDemo(string $role): void
    {
        if ($role === 'guru') {
            $this->form->email = 'guru@ruangkelas.test';
            $this->form->password = 'password';
            $this->demoSelected = 'guru';
        } elseif ($role === 'siswa') {
            $this->form->email = 'siswa@ruangkelas.test';
            $this->form->password = 'password';
            $this->demoSelected = 'siswa';
        }
    }

    /**
     * Mengosongkan form login jika ingin mengetik manual
     */
    public function clearDemo(): void
    {
        $this->form->email = '';
        $this->form->password = '';
        $this->demoSelected = '';
    }

    /**
     * Handle proses login
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();

        if ($user->hasRole('guru')) {
            $this->redirectRoute('guru.dashboard', navigate: true);
        } elseif ($user->hasRole('siswa')) {
            $this->redirectRoute('siswa.dashboard', navigate: true);
        } else {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
        }
    }
}; ?>

<div class="w-full space-y-4">

    <!-- ============================================================ -->
    <!-- CARD UTAMA LOGIN (CLEAN, MODERN, RESPONSIVE & PRESISI)       -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 shadow-xl shadow-indigo-950/5 border border-slate-200/90 space-y-5">
        
        <!-- Header Judul: Selamat Datang (Disimpan di Tengah) -->
        <div class="text-center space-y-1">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Selamat Datang
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 font-normal">
                Masuk untuk mengakses ruang kelas Anda
            </p>
        </div>

        <!-- Status Sesi Flash -->
        <x-auth-session-status class="mb-2" :status="session('status')" />


        <!-- FORM LOGIN -->
        <form wire:submit="login" class="space-y-4">
            
            <!-- Input Email dengan Label di Atas -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs sm:text-sm font-bold text-slate-700">
                    Email
                </label>
                <input wire:model="form.email"
                       id="email"
                       type="email"
                       name="email"
                       required
                       autofocus
                       autocomplete="username"
                       placeholder="nama@email.com"
                       class="w-full px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition outline-none bg-white">
                <x-input-error :messages="$errors->get('form.email')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Input Kata Sandi dengan Label di Atas & Show/Hide Clean -->
            <div x-data="{ showPassword: false }" class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs sm:text-sm font-bold text-slate-700">
                        Kata Sandi
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                            Lupa kata sandi?
                        </a>
                    @endif
                </div>

                <div class="relative flex items-center">
                    <input wire:model="form.password"
                           id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="Masukkan kata sandi"
                           class="w-full pl-3.5 sm:pl-4 pr-11 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition outline-none bg-white">

                    <!-- Tombol Show/Hide Password Clean, Presisi & Responsive -->
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center justify-center text-slate-400 hover:text-indigo-600 focus:outline-none transition active:scale-90 cursor-pointer"
                            :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Toggle kata sandi"
                            tabindex="-1">
                        <!-- Icon Eye (Buka) -->
                        <svg x-show="!showPassword" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>

                        <!-- Icon Eye Off (Tutup / Coret Bersih) -->
                        <svg x-show="showPassword" class="w-5 h-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                            <line x1="2" y1="2" x2="22" y2="22"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('form.password')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Checkbox: Ingatkan Saya -->
            <div class="flex items-center pt-0.5">
                <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                    <input wire:model="form.remember"
                           id="remember"
                           type="checkbox"
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-2xs focus:ring-indigo-500 focus:ring-offset-0 transition cursor-pointer">
                    <span class="ms-2 text-xs sm:text-sm text-slate-600 font-medium">Ingatkan saya</span>
                </label>
            </div>

            <!-- Tombol Aksi: Cukup "Masuk" -->
            <div class="pt-1.5">
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="login"
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm sm:text-base py-3 px-5 rounded-xl sm:rounded-2xl shadow-lg shadow-indigo-200/80 hover:shadow-indigo-300 transition-all transform active:scale-[0.98] disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer">
                    <svg wire:loading wire:target="login" xmlns="http://www.w3.org/2000/svg" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login">Memproses...</span>
                </button>
            </div>
        </form>

        <!-- Link Registrasi Akun -->
        <div class="pt-3 border-t border-slate-100 text-center text-xs sm:text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}"
               wire:navigate
               class="font-bold text-indigo-600 hover:text-indigo-800 hover:underline ms-1 transition">
                Daftar
            </a>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- GRID DEMO BERSIH & TAMPIL AKUN + PASSWORD                    -->
    <!-- ============================================================ -->
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-3.5 sm:p-4 border border-slate-200/90 shadow-xs space-y-2.5">
        <div class="flex items-center justify-between text-[11px] text-slate-500 font-semibold px-0.5">
            <span>Akun Demo</span>
            <span class="text-slate-400 font-normal">Klik untuk isi otomatis</span>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <!-- Demo Siswa -->
            <button type="button"
                    wire:click="fillDemo('siswa')"
                    class="p-3 rounded-xl border text-left transition-all cursor-pointer group {{ $demoSelected === 'siswa' ? 'bg-indigo-50/90 border-indigo-300 ring-2 ring-indigo-200/70 shadow-xs' : 'bg-slate-50/80 hover:bg-slate-100 border-slate-200/80' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full {{ $demoSelected === 'siswa' ? 'bg-emerald-500' : 'bg-slate-300 group-hover:bg-slate-400' }}"></span>
                        <span class="text-xs font-bold text-slate-900">Siswa</span>
                    </div>
                    @if($demoSelected === 'siswa')
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/90 px-2 py-0.5 rounded-full">Aktif</span>
                    @endif
                </div>
                <div class="space-y-0.5 text-[11px] font-mono text-slate-600">
                    <p class="truncate"><span class="text-slate-400 font-sans">Email:</span> siswa@ruangkelas.test</p>
                    <p><span class="text-slate-400 font-sans">Sandi:</span> password</p>
                </div>
            </button>

            <!-- Demo Guru -->
            <button type="button"
                    wire:click="fillDemo('guru')"
                    class="p-3 rounded-xl border text-left transition-all cursor-pointer group {{ $demoSelected === 'guru' ? 'bg-indigo-50/90 border-indigo-300 ring-2 ring-indigo-200/70 shadow-xs' : 'bg-slate-50/80 hover:bg-slate-100 border-slate-200/80' }}">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full {{ $demoSelected === 'guru' ? 'bg-indigo-600' : 'bg-slate-300 group-hover:bg-slate-400' }}"></span>
                        <span class="text-xs font-bold text-slate-900">Guru</span>
                    </div>
                    @if($demoSelected === 'guru')
                        <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100/90 px-2 py-0.5 rounded-full">Aktif</span>
                    @endif
                </div>
                <div class="space-y-0.5 text-[11px] font-mono text-slate-600">
                    <p class="truncate"><span class="text-slate-400 font-sans">Email:</span> guru@ruangkelas.test</p>
                    <p><span class="text-slate-400 font-sans">Sandi:</span> password</p>
                </div>
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- LINK BERANDA (CENTERED & CLEAN - TANPA TEKS TERKONEKSI AMAN) -->
    <!-- ============================================================ -->
    <div class="text-center pt-1">
        <a href="/" wire:navigate class="text-xs text-slate-500 hover:text-slate-800 transition inline-flex items-center gap-1 py-1 px-3 rounded-lg hover:bg-white/60">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
    </div>

</div>
