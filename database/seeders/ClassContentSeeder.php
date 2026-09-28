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
                'jenjang' => 'Kelas 10',
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
                'jenjang' => 'Kelas 10',
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

        $mat3 = Material::firstOrCreate([
            'chapter_id' => $chapter1->id,
            'urutan' => 3,
        ], [
            'judul' => 'Modul Lengkap: Panduan Aljabar & PLSV (PDF)',
            'tipe' => 'pdf',
            'file_path' => 'materials/modul-persamaan-linear.pdf',
            'durasi_menit' => 25,
            'konten' => 'Silakan pelajari dokumen modul resmi di bawah ini. Anda dapat membaca langsung di layar atau mengunduhnya untuk dicetak.',
        ]);

        $mat4 = Material::firstOrCreate([
            'chapter_id' => $chapter1->id,
            'urutan' => 4,
        ], [
            'judul' => 'Tautan Referensi Interaktif GeoGebra Persamaan',
            'tipe' => 'link',
            'url' => 'https://www.geogebra.org',
            'durasi_menit' => 15,
            'konten' => 'Eksplorasi grafik kurva persamaan linear dan titik potong sumbu menggunakan kalkulator grafik online GeoGebra.',
        ]);

        // 5. Buat Tugas Latihan pada Bab 1
        $tugas1 = \App\Models\Assignment::firstOrCreate([
            'chapter_id' => $chapter1->id,
            'judul' => 'Tugas 1: Analisis Model Matematika dan PLSV Nyata',
        ], [
            'deskripsi' => "Kerjakan soal studi kasus nomor 1 sampai 5 pada modul. Selesaikan menggunakan metode aljabar formal dan sertakan langkah pembuktian.\n\nUnggah dalam format PDF/Foto tulisan rapi atau cantumkan tautan Google Drive jawaban Anda.",
            'file_lampiran' => 'materials/modul-persamaan-linear.pdf',
            'deadline' => now()->addDays(5),
            'poin_maksimal' => 100,
        ]);

        // Buat Tugas di Bab 2
        $tugas2 = \App\Models\Assignment::firstOrCreate([
            'chapter_id' => $chapter2->id,
            'judul' => 'Tugas 2: Penyelesaian SPLTV dengan Metode Gabungan',
        ], [
            'deskripsi' => "Selesaikan sistem persamaan 3 variabel yang tertera pada lembar kerja bab 2. Tentukan himpunan penyelesaian HP = {(x, y, z)}.",
            'deadline' => now()->addDays(7),
            'poin_maksimal' => 100,
        ]);

        // Submisi Tugas 1 dari siswa demo
        \App\Models\AssignmentSubmission::firstOrCreate([
            'assignment_id' => $tugas1->id,
            'user_id' => $siswa->id,
        ], [
            'file_jawaban' => null,
            'link_tugas' => 'https://drive.google.com/ruangkelas/jawaban-siswa-demo',
            'catatan_siswa' => 'Berikut jawaban tugas 1 analisis PLSV saya Pak Guru. Terima kasih banyak.',
            'nilai' => 88,
            'catatan_guru' => 'Luar biasa! Langkah pembuktian nomor 1-4 sangat runtut dan tepat. Perhatikan ketelitian tanda minus di nomor 5.',
            'status' => 'graded',
            'submitted_at' => now()->subDay(),
            'graded_at' => now()->subHours(5),
        ]);

        // 6. Buat Kuis pada Bab 1
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

        // 7. Buat Soal-soal Kuis
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

        // 8. Buat Riwayat Progres Belajar Siswa Demo
        Progress::firstOrCreate([
            'user_id' => $siswa->id,
            'material_id' => $mat1->id,
        ], [
            'status_selesai' => true,
            'selesai_at' => now(),
        ]);

        // Rekap Kuis Attempt Siswa Demo
        \App\Models\QuizAttempt::firstOrCreate([
            'quiz_id' => $quiz->id,
            'user_id' => $siswa->id,
        ], [
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
            'skor' => 100,
            'total_benar' => 2,
            'total_salah' => 0,
            'status' => 'selesai',
        ]);
    }
}
