<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan Role & Permission Seeder terlebih dahulu
        $this->call(RolePermissionSeeder::class);

        // Buat akun demo guru
        $guru = User::factory()->create([
            'name' => 'Rahmat Hidayat, S.Pd.',
            'email' => 'guru@ruangkelas.test',
        ]);
        $guru->assignRole('guru');

        // Buat akun demo siswa
        $siswa = User::factory()->create([
            'name' => 'Siswa',
            'email' => 'siswa@ruangkelas.test',
        ]);
        $siswa->assignRole('siswa');

        // Buat data konten kelas & demo materi
        $this->call(ClassContentSeeder::class);

        $this->command->info('✅ Akun demo & data awal dibuat:');
        $this->command->info('   Guru: guru@ruangkelas.test / password');
        $this->command->info('   Siswa: siswa@ruangkelas.test / password');
        $this->command->info('   Kode Kelas Demo: MTK10A');
    }
}
