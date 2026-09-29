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

        // Buat atau temukan akun demo guru
        $guru = User::firstOrCreate(
            ['email' => 'guru@ruangkelas.test'],
            [
                'name' => 'Rahmat Hidayat, S.Pd.',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (!$guru->hasRole('guru')) {
            $guru->assignRole('guru');
        }

        // Buat atau temukan akun demo siswa utama (akun demo tetap aktif)
        $siswa = User::firstOrCreate(
            ['email' => 'siswa@ruangkelas.test'],
            [
                'name' => 'Siswa',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (!$siswa->hasRole('siswa')) {
            $siswa->assignRole('siswa');
        }

        // 9 Siswa tambahan untuk melengkapi 10 siswa di Kelas 9A
        $daftarSiswaTambahan = [
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad.fauzi@ruangkelas.test'],
            ['name' => 'Siti Aisyah', 'email' => 'siti.aisyah@ruangkelas.test'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@ruangkelas.test'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@ruangkelas.test'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky.pratama@ruangkelas.test'],
            ['name' => 'Nabila Putri', 'email' => 'nabila.putri@ruangkelas.test'],
            ['name' => 'Dimas Saputra', 'email' => 'dimas.saputra@ruangkelas.test'],
            ['name' => 'Anisa Rahmawati', 'email' => 'anisa.rahmawati@ruangkelas.test'],
            ['name' => 'Farhan Ramadhan', 'email' => 'farhan.ramadhan@ruangkelas.test'],
        ];

        foreach ($daftarSiswaTambahan as $item) {
            $s = User::firstOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$s->hasRole('siswa')) {
                $s->assignRole('siswa');
            }
        }

        // Buat data konten kelas & demo materi Bahasa Indonesia Kelas 9A
        $this->call(ClassContentSeeder::class);

        $this->command->info('✅ Akun demo & data awal berhasil diperbarui:');
        $this->command->info('   Guru: guru@ruangkelas.test / password');
        $this->command->info('   Siswa Demo Utama: siswa@ruangkelas.test / password');
        $this->command->info('   Total Siswa Terdaftar: 10 Siswa');
        $this->command->info('   Kode Kelas Demo: BIN9A (Bahasa Indonesia Kelas 9A)');
    }
}
