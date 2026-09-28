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

<div class="w-full">
    <!-- ============================================================ -->
    <!-- CARD UTAMA LOGIN (CLEAN & TIDAK KOTAK KAKU)                  -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 space-y-5">
        
        <!-- Header -->
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                Masuk ke Akun
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">
                Masukkan email dan kata sandi Anda untuk melanjutkan.
            </p>
        </div>

        <!-- Status Sesi Flash -->
        <x-auth-session-status class="mb-2" :status="session('status')" />

        <!-- Feedback Akun Demo Terpilih -->
        @if($demoSelected)
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 flex items-center justify-between animate-fadeIn">
                <span>Akun demo <b>{{ $demoSelected === 'guru' ? 'Guru' : 'Siswa' }}</b> telah diisikan.</span>
                <span class="text-emerald-600 font-semibold">Siap masuk</span>
            </div>
        @endif

        <!-- FORM LOGIN PROFESIONAL -->
        <form wire:submit="login" class="space-y-4">
            
            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs sm:text-sm font-medium text-slate-700">
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
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition outline-none">
                <x-input-error :messages="$errors->get('form.email')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Password dengan Hide/Show Dinamis -->
            <div x-data="{
                     showPassword: false,
                     passwordInput: '',
                     get hasPassword() {
                         return (this.passwordInput && this.passwordInput.length > 0) || Boolean($wire.form.password);
                     }
                 }"
                 class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs sm:text-sm font-medium text-slate-700">
                        Kata Sandi
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800 transition">
                            Lupa kata sandi?
                        </a>
                    @endif
                </div>

                <div class="relative flex items-center">
                    <input wire:model.live="form.password"
                           x-model="passwordInput"
                           id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="Masukkan kata sandi"
                           class="w-full pl-3.5 pr-11 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition outline-none">

                    <!-- Tombol Hide/Show Password Dinamis (Otomatis Muncul/Hilang secara Halus) -->
                    <button x-show="hasPassword"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-75"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-75"
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition focus:outline-none cursor-pointer"
                            :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            tabindex="-1"
                            style="display: none;">
                        <!-- Icon Mata Terbuka -->
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <!-- Icon Mata Dicoret -->
                        <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('form.password')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Ingat Saya -->
            <div class="flex items-center pt-0.5">
                <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                    <input wire:model="form.remember"
                           id="remember"
                           type="checkbox"
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-2xs focus:ring-indigo-500 transition">
                    <span class="ms-2 text-xs sm:text-sm text-slate-600">Ingat saya</span>
                </label>
            </div>

            <!-- Tombol Masuk Sederhana -->
            <div class="pt-2">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm py-2.5 px-4 rounded-xl shadow-xs transition active:scale-[0.99] disabled:opacity-75 disabled:cursor-not-allowed">
                    <svg wire:loading xmlns="http://www.w3.org/2000/svg" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span wire:loading.remove>Masuk</span>
                    <span wire:loading>Memproses...</span>
                </button>
            </div>
        </form>

        <!-- Link Daftar Akun Baru Sederhana -->
        <div class="pt-4 border-t border-slate-100 text-center text-xs sm:text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}"
               wire:navigate
               class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline ms-1 transition">
                Daftar
            </a>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- GRID AKUN DEMO DI BAWAH CARD LOGIN (SEDERHANA & CLEAN)       -->
    <!-- ============================================================ -->
    <div class="mt-4 bg-white/70 backdrop-blur-md rounded-2xl p-4 border border-slate-200/70 shadow-2xs space-y-2.5">
        <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
            <span>Akun Uji Coba (Demo)</span>
            <span class="text-[11px] text-slate-400">Klik untuk isi otomatis</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <!-- Demo Guru -->
            <button type="button"
                    wire:click="fillDemo('guru')"
                    class="flex items-center justify-between p-2.5 rounded-xl border transition text-left {{ $demoSelected === 'guru' ? 'bg-indigo-50/80 border-indigo-300' : 'bg-slate-50/80 hover:bg-slate-100 border-slate-200' }}">
                <div>
                    <p class="text-xs font-semibold text-slate-800">Akun Guru</p>
                    <p class="text-[11px] text-slate-500 font-mono">guru@ruangkelas.test</p>
                </div>
                <span class="text-xs font-semibold text-indigo-600">Gunakan</span>
            </button>

            <!-- Demo Siswa -->
            <button type="button"
                    wire:click="fillDemo('siswa')"
                    class="flex items-center justify-between p-2.5 rounded-xl border transition text-left {{ $demoSelected === 'siswa' ? 'bg-emerald-50/80 border-emerald-300' : 'bg-slate-50/80 hover:bg-slate-100 border-slate-200' }}">
                <div>
                    <p class="text-xs font-semibold text-slate-800">Akun Siswa</p>
                    <p class="text-[11px] text-slate-500 font-mono">siswa@ruangkelas.test</p>
                </div>
                <span class="text-xs font-semibold text-emerald-600">Gunakan</span>
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- LINK BERANDA DI BAWAH GRID (SEDERHANA & CLEAN)              -->
    <!-- ============================================================ -->
    <div class="mt-5 text-center">
        <a href="/" wire:navigate class="text-xs text-slate-500 hover:text-slate-800 transition inline-flex items-center gap-1.5 py-1 px-3 rounded-full hover:bg-slate-200/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
    </div>
</div>
