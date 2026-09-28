<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles dan permissions sesuai matriks PRD bagian 3.2.
     */
    public function run(): void
    {
        // Reset cache Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =========================================================
        // Daftar Permission (granular, berbasis resource.aksi)
        // =========================================================
        $permissions = [
            // Kelas
            'kelas.buat',
            'kelas.lihat',
            'kelas.edit',
            'kelas.hapus',
            'kelas.kelola-siswa',       // keluarkan/blokir siswa

            // Materi & Bab
            'materi.buat',
            'materi.lihat',
            'materi.edit',
            'materi.hapus',

            // Kuis & Soal
            'kuis.buat',
            'kuis.lihat',
            'kuis.edit',
            'kuis.hapus',
            'kuis.kerjakan',            // siswa mengerjakan kuis

            // Siswa & Nilai
            'siswa.lihat',              // guru lihat daftar siswa di kelasnya
            'nilai.lihat-semua',        // guru lihat nilai semua siswa
            'nilai.beri',               // guru beri/ubah nilai
            'nilai.lihat-sendiri',      // siswa lihat nilai sendiri

            // Kelas - siswa
            'kelas.gabung',             // siswa gabung via kode kelas
            'kelas.ikuti',              // akses materi kelas yang diikuti

            // Progres
            'progres.lihat-sendiri',    // siswa lihat progres sendiri
            'progres.tandai-selesai',   // siswa tandai materi selesai

            // Profil
            'profil.edit',              // semua role bisa edit profil sendiri

            // Pengumuman
            'pengumuman.buat',
            'pengumuman.lihat',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // =========================================================
        // Buat Role
        // =========================================================
        $roleGuru = Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
        $roleSiswa = Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

        // =========================================================
        // Assign Permission ke Role GURU
        // Sesuai matriks PRD 3.2
        // =========================================================
        $roleGuru->syncPermissions([
            'kelas.buat',
            'kelas.lihat',
            'kelas.edit',
            'kelas.hapus',
            'kelas.kelola-siswa',
            'materi.buat',
            'materi.lihat',
            'materi.edit',
            'materi.hapus',
            'kuis.buat',
            'kuis.lihat',
            'kuis.edit',
            'kuis.hapus',
            'siswa.lihat',
            'nilai.lihat-semua',
            'nilai.beri',
            'kelas.ikuti',              // guru juga bisa akses materi kelas yang dia buat
            'profil.edit',
            'pengumuman.buat',
            'pengumuman.lihat',
        ]);

        // =========================================================
        // Assign Permission ke Role SISWA
        // Sesuai matriks PRD 3.2
        // =========================================================
        $roleSiswa->syncPermissions([
            'kelas.gabung',
            'kelas.ikuti',
            'kelas.lihat',
            'materi.lihat',
            'kuis.lihat',
            'kuis.kerjakan',
            'nilai.lihat-sendiri',
            'progres.lihat-sendiri',
            'progres.tandai-selesai',
            'profil.edit',
            'pengumuman.lihat',
        ]);

        $this->command->info('✅ Role dan Permission berhasil dibuat:');
        $this->command->info('   - Role "guru" dengan ' . $roleGuru->permissions->count() . ' permission');
        $this->command->info('   - Role "siswa" dengan ' . $roleSiswa->permissions->count() . ' permission');
    }
}
