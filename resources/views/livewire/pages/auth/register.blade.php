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

<div x-data="{ showPassword: false, showPasswordConfirm: false }" class="w-full">
    <!-- Card Register Clean & Modern -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 space-y-6">
        
        <!-- Header -->
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                Buat Akun Baru
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">
                Pilih peran Anda dan lengkapi formulir pendaftaran.
            </p>
        </div>

        <form wire:submit="register" class="space-y-4">
            <!-- Pilihan Role: Siswa atau Guru -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-medium text-slate-700">
                    Daftar Sebagai
                </label>
                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Pilihan Siswa -->
                    <label class="relative flex cursor-pointer rounded-xl border p-3 transition text-center items-center justify-center {{ $role === 'siswa' ? 'border-emerald-600 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:bg-slate-50' }}">
                        <input wire:model.live="role" type="radio" name="role" value="siswa" class="sr-only" id="role-siswa">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🎒</span>
                            <span class="text-xs sm:text-sm font-semibold {{ $role === 'siswa' ? 'text-emerald-800' : 'text-slate-700' }}">Siswa</span>
                        </div>
                    </label>

                    <!-- Pilihan Guru -->
                    <label class="relative flex cursor-pointer rounded-xl border p-3 transition text-center items-center justify-center {{ $role === 'guru' ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:bg-slate-50' }}">
                        <input wire:model.live="role" type="radio" name="role" value="guru" class="sr-only" id="role-guru">
                        <div class="flex items-center gap-2">
                            <span class="text-base">👨‍🏫</span>
                            <span class="text-xs sm:text-sm font-semibold {{ $role === 'guru' ? 'text-indigo-800' : 'text-slate-700' }}">Guru</span>
                        </div>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('role')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Nama Lengkap -->
            <div class="space-y-1.5">
                <label for="name" class="block text-xs sm:text-sm font-medium text-slate-700">
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
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition outline-none">
                <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Email -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs sm:text-sm font-medium text-slate-700">
                    Email
                </label>
                <input wire:model="email"
                       id="email"
                       type="email"
                       name="email"
                       required
                       autocomplete="username"
                       placeholder="nama@email.com"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition outline-none">
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <label for="password" class="block text-xs sm:text-sm font-medium text-slate-700">
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
                           class="w-full pl-3.5 pr-11 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition outline-none">

                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-0 pr-3.5 flex items-center justify-center text-slate-400 hover:text-slate-600 transition focus:outline-none"
                            tabindex="-1">
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Konfirmasi Password -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-xs sm:text-sm font-medium text-slate-700">
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
                           class="w-full pl-3.5 pr-11 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder:text-slate-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition outline-none">

                    <button type="button"
                            @click="showPasswordConfirm = !showPasswordConfirm"
                            class="absolute right-0 pr-3.5 flex items-center justify-center text-slate-400 hover:text-slate-600 transition focus:outline-none"
                            tabindex="-1">
                        <svg x-show="!showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-rose-600" />
            </div>

            <!-- Tombol Daftar Sederhana -->
            <div class="pt-2">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm py-3 px-4 rounded-xl shadow-xs transition active:scale-[0.99] disabled:opacity-75 disabled:cursor-not-allowed">
                    <svg wire:loading xmlns="http://www.w3.org/2000/svg" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span wire:loading.remove>Daftar</span>
                    <span wire:loading>Memproses...</span>
                </button>
            </div>
        </form>

        <!-- Footer Link Masuk Sederhana -->
        <div class="pt-4 border-t border-slate-100 text-center text-xs sm:text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}"
               wire:navigate
               class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline ms-1 transition">
                Masuk
            </a>
        </div>

    </div>

    <!-- Link Beranda di bawah Card Register -->
    <div class="mt-5 text-center">
        <a href="/" wire:navigate class="text-xs text-slate-500 hover:text-slate-800 transition inline-flex items-center gap-1.5 py-1 px-3 rounded-full hover:bg-slate-200/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
    </div>
</div>
