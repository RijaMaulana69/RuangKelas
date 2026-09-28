<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Redirect /dashboard ke dashboard sesuai role
Route::middleware(['auth', 'verified'])->get('dashboard', function () {
    if (auth()->user()->hasRole('guru')) {
        return redirect()->route('guru.dashboard');
    }
    return redirect()->route('siswa.dashboard');
})->name('dashboard');

// ============================================================
// GRUP ROUTE GURU
// ============================================================
Route::middleware(['auth', 'verified', 'role:guru'])
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        Route::view('dashboard', 'guru.dashboard')->name('dashboard');
        Route::view('kelas', 'guru.kelas.index')->name('kelas.index');
        Route::get('kelas/{kelas}', function ($kelas) {
            return view('guru.kelas.show', ['kelas' => (int) $kelas]);
        })->name('kelas.show');
    });

// ============================================================
// GRUP ROUTE SISWA
// ============================================================
Route::middleware(['auth', 'verified', 'role:siswa'])
    ->prefix('siswa')
    ->name('siswa.')
    ->group(function () {
        Route::view('dashboard', 'siswa.dashboard')->name('dashboard');
        Route::view('kelas', 'siswa.kelas.index')->name('kelas.index');
        Route::get('kelas/{kelas}', function ($kelas) {
            return view('siswa.kelas.show', ['kelas' => (int) $kelas]);
        })->name('kelas.show');
        Route::get('materi/{material}', function ($material) {
            return view('siswa.materi.show', ['material' => (int) $material]);
        })->name('materi.show');
        Route::get('kuis/{quiz}', function ($quiz) {
            return view('siswa.kuis.show', ['quiz' => (int) $quiz]);
        })->name('kuis.show');
    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
