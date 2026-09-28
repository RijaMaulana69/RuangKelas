<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'siswa'; // default role

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', 'in:guru,siswa'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['role']);

        event(new Registered($user = User::create($validated)));

        // Assign role yang dipilih
        $user->assignRole($this->role);

        Auth::login($user);

        // Arahkan ke dashboard sesuai role
        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<div x-data="{ showPassword: false, showPasswordConfirm: false }" class="w-full space-y-4">

    <!-- ============================================================ -->
    <!-- CARD UTAMA REGISTER (SELARAS DENGAN HALAMAN LOGIN)          -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 shadow-xl shadow-indigo-950/5 border border-slate-200/90 space-y-5">
        
        <!-- Header Judul: Rata Tengah -->
        <div class="text-center space-y-1">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Buat Akun Baru
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 font-normal">
                Lengkapi formulir di bawah untuk mulai belajar atau mengajar
            </p>
        </div>

        <form wire:submit="register" class="space-y-4">
            
            <!-- Pemilihan Role: Clean Sederhana Teks Aja (Label: Pilih Role) -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold text-slate-700">
                    Pilih Role
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <!-- Pilihan Siswa -->
                    <label class="relative flex cursor-pointer rounded-xl border py-2.5 px-3 transition text-center items-center justify-center font-bold text-xs sm:text-sm select-none {{ $role === 'siswa' ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-100 shadow-2xs' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600' }}">
                        <input wire:model.live="role" type="radio" name="role" value="siswa" class="sr-only">
                        <span>Siswa</span>
                    </label>

                    <!-- Pilihan Guru -->
                    <label class="relative flex cursor-pointer rounded-xl border py-2.5 px-3 transition text-center items-center justify-center font-bold text-xs sm:text-sm select-none {{ $role === 'guru' ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-100 shadow-2xs' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600' }}">
                        <input wire:model.live="role" type="radio" name="role" value="guru" class="sr-only">
                        <span>Guru</span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('role')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Nama Lengkap -->
            <div class="space-y-1.5">
                <label for="name" class="block text-xs sm:text-sm font-bold text-slate-700">
                    Nama Lengkap
                </label>
                <input wire:model="name"
                       id="name"
                       type="text"
                       name="name"
                       required
                       autofocus
                       autocomplete="name"
                       placeholder="Masukkan nama lengkap"
                       class="w-full px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition outline-none bg-white">
                <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs sm:text-sm font-bold text-slate-700">
                    Email
                </label>
                <input wire:model="email"
                       id="email"
                       type="email"
                       name="email"
                       required
                       autocomplete="username"
                       placeholder="nama@email.com"
                       class="w-full px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition outline-none bg-white">
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Kata Sandi -->
            <div class="space-y-1.5">
                <label for="password" class="block text-xs sm:text-sm font-bold text-slate-700">
                    Kata Sandi
                </label>
                <div class="relative flex items-center">
                    <input wire:model="password"
                           id="password"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           required
                           autocomplete="new-password"
                           placeholder="Minimal 8 karakter"
                           class="w-full pl-3.5 sm:pl-4 pr-11 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition outline-none bg-white">

                    <!-- Tombol Show/Hide Password Clean & Responsive -->
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center justify-center text-slate-400 hover:text-indigo-600 focus:outline-none transition active:scale-90 cursor-pointer"
                            :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Toggle kata sandi"
                            tabindex="-1">
                        <svg x-show="!showPassword" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg x-show="showPassword" class="w-5 h-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                            <line x1="2" y1="2" x2="22" y2="22"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Konfirmasi Kata Sandi -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-xs sm:text-sm font-bold text-slate-700">
                    Konfirmasi Kata Sandi
                </label>
                <div class="relative flex items-center">
                    <input wire:model="password_confirmation"
                           id="password_confirmation"
                           :type="showPasswordConfirm ? 'text' : 'password'"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           placeholder="Ulangi kata sandi"
                           class="w-full pl-3.5 sm:pl-4 pr-11 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-slate-900 text-xs sm:text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition outline-none bg-white">

                    <!-- Tombol Show/Hide Password Konfirmasi Clean & Responsive -->
                    <button type="button"
                            @click="showPasswordConfirm = !showPasswordConfirm"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center justify-center text-slate-400 hover:text-indigo-600 focus:outline-none transition active:scale-90 cursor-pointer"
                            :title="showPasswordConfirm ? 'Sembunyikan konfirmasi kata sandi' : 'Tampilkan konfirmasi kata sandi'"
                            aria-label="Toggle konfirmasi kata sandi"
                            tabindex="-1">
                        <svg x-show="!showPasswordConfirm" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg x-show="showPasswordConfirm" class="w-5 h-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                            <line x1="2" y1="2" x2="22" y2="22"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Tombol Aksi: Cukup "Daftar" -->
            <div class="pt-2">
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="register"
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm sm:text-base py-3 px-5 rounded-xl sm:rounded-2xl shadow-lg shadow-indigo-200/80 hover:shadow-indigo-300 transition-all transform active:scale-[0.98] disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer">
                    <svg wire:loading wire:target="register" xmlns="http://www.w3.org/2000/svg" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span wire:loading.remove wire:target="register">Daftar</span>
                    <span wire:loading wire:target="register">Memproses...</span>
                </button>
            </div>
        </form>

        <!-- Link Masuk Akun yang Sudah Ada -->
        <div class="pt-3 border-t border-slate-100 text-center text-xs sm:text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}"
               wire:navigate
               class="font-bold text-indigo-600 hover:text-indigo-800 hover:underline ms-1 transition">
                Masuk
            </a>
        </div>

    </div>

    <!-- Link Beranda (Centered & Clean) -->
    <div class="text-center pt-1">
        <a href="/" wire:navigate class="text-xs text-slate-500 hover:text-slate-800 transition inline-flex items-center gap-1 py-1 px-3 rounded-lg hover:bg-white/60">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
    </div>

</div>
