<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Kelas;
use App\Models\Material;
use App\Models\Progress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassContentSeeder extends Seeder
{
    public function run(): void
    {
        $guru = User::where('email', 'guru@ruangkelas.test')->first();
        $siswa = User::where('email', 'siswa@ruangkelas.test')->first();

        if (!$guru || !$siswa) {
            return;
        }

        // 1. Buat Kelas Demo
        $kelas = Kelas::firstOrCreate(
            ['kode_kelas' => 'MTK10A'],
            [
                'guru_id' => $guru->id,
                'nama' => 'Matematika Dasar Kelas 10',
                'mapel' => 'Matematika',
                'jenjang' => 'SMA',
                'deskripsi' => 'Mempelajari konsep aljabar, persamaan linear, trigonometri, dan fungsi matematika tingkat SMA kelas 10.',
                'aktif' => true,
            ]
        );

        // Kelas kedua: IPA Biologi
        $kelasBio = Kelas::firstOrCreate(
            ['kode_kelas' => 'BIO10B'],
            [
                'guru_id' => $guru->id,
                'nama' => 'Biologi Sel & Jaringan',
                'mapel' => 'Biologi',
                'jenjang' => 'SMA',
                'deskripsi' => 'Pengenalan struktur sel eukariotik, prokariotik, dan jaringan tumbuhan serta hewan.',
                'aktif' => true,
            ]
        );

        // 2. Daftarkan siswa demo ke kelas MTK10A
        Enrollment::firstOrCreate([
            'user_id' => $siswa->id,
            'class_id' => $kelas->id,
        ], [
            'tanggal_gabung' => now(),
        ]);

        // 3. Buat Bab (Chapter) pada Kelas Matematika
        $chapter1 = Chapter::firstOrCreate([
            'class_id' => $kelas->id,
            'urutan' => 1,
        ], [
            'judul' => 'Bab 1: Persamaan dan Pertidaksamaan Linear',
            'deskripsi' => 'Mempelajari bentuk umum persamaan linear satu variabel dan teknik penyelesaiannya.',
        ]);

        $chapter2 = Chapter::firstOrCreate([
            'class_id' => $kelas->id,
            'urutan' => 2,
        ], [
            'judul' => 'Bab 2: Sistem Persamaan Linear Tiga Variabel (SPLTV)',
            'deskripsi' => 'Metode substitusi, eliminasi, dan gabungan dalam memecahkan SPLTV.',
        ]);

        // 4. Buat Materi pada Bab 1
        $mat1 = Material::firstOrCreate([
            'chapter_id' => $chapter1->id,
            'urutan' => 1,
        ], [
            'judul' => 'Pengantar Persamaan Linear Satu Variabel',
            'tipe' => 'teks',
            'konten' => "<h3>Apa itu Persamaan Linear Satu Variabel?</h3><p>Persamaan linear satu variabel (PLSV) adalah kalimat terbuka yang dihubungkan dengan tanda sama dengan (=) dan hanya memiliki satu variabel berpangkat satu.</p><p><b>Bentuk umum:</b> <code>ax + b = c</code> dengan a ≠ 0.</p><p><b>Contoh:</b> 2x + 4 = 10 -> 2x = 6 -> x = 3.</p>",
            'durasi_menit' => 15,
        ]);

        $mat2 = Material::firstOrCreate([
            'chapter_id' => $chapter1->id,
            'urutan' => 2,
        ], [
            'judul' => 'Video Penjelasan Penyelesaian PLSV',
            'tipe' => 'video',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'durasi_menit' => 10,
        ]);

        // 5. Buat Kuis pada Bab 1
        $quiz = Quiz::firstOrCreate([
            'chapter_id' => $chapter1->id,
            'urutan' => 1,
        ], [
            'judul' => 'Kuis Latihan Pemahaman Bab 1',
            'deskripsi' => 'Uji pemahaman dasar mengenai persamaan linear satu variabel. Nilai KKM 70.',
            'durasi_menit' => 20,
            'kkm' => 70,
            'acak_soal' => false,
        ]);

        // 6. Buat Soal-soal Kuis
        $soal1 = Question::firstOrCreate([
            'quiz_id' => $quiz->id,
            'urutan' => 1,
        ], [
            'pertanyaan' => 'Berapakah nilai x dari persamaan 3x + 9 = 24?',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => '3x = 24 - 9 = 15 => x = 15 / 3 = 5.',
        ]);

        QuestionOption::firstOrCreate(['question_id' => $soal1->id, 'label' => 'A'], ['teks' => '3', 'is_benar' => false]);
        QuestionOption::firstOrCreate(['question_id' => $soal1->id, 'label' => 'B'], ['teks' => '5', 'is_benar' => true]);
        QuestionOption::firstOrCreate(['question_id' => $soal1->id, 'label' => 'C'], ['teks' => '7', 'is_benar' => false]);
        QuestionOption::firstOrCreate(['question_id' => $soal1->id, 'label' => 'D'], ['teks' => '8', 'is_benar' => false]);

        $soal2 = Question::firstOrCreate([
            'quiz_id' => $quiz->id,
            'urutan' => 2,
        ], [
            'pertanyaan' => 'Jika 2(x - 3) = 14, maka nilai dari x + 2 adalah...',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => '2x - 6 = 14 => 2x = 20 => x = 10. Nilai x + 2 = 10 + 2 = 12.',
        ]);

        QuestionOption::firstOrCreate(['question_id' => $soal2->id, 'label' => 'A'], ['teks' => '10', 'is_benar' => false]);
        QuestionOption::firstOrCreate(['question_id' => $soal2->id, 'label' => 'B'], ['teks' => '11', 'is_benar' => false]);
        QuestionOption::firstOrCreate(['question_id' => $soal2->id, 'label' => 'C'], ['teks' => '12', 'is_benar' => true]);
        QuestionOption::firstOrCreate(['question_id' => $soal2->id, 'label' => 'D'], ['teks' => '14', 'is_benar' => false]);

        // 7. Buat Riwayat Progres Belajar Siswa Demo
        Progress::firstOrCreate([
            'user_id' => $siswa->id,
            'material_id' => $mat1->id,
        ], [
            'status_selesai' => true,
            'selesai_at' => now(),
        ]);
    }
}
