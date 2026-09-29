<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Kelas;
use App\Models\Material;
use App\Models\Progress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassContentSeeder extends Seeder
{
    public function run(): void
    {
        $guru = User::where('email', 'guru@ruangkelas.test')->first();
        $siswaDemo = User::where('email', 'siswa@ruangkelas.test')->first();

        if (!$guru || !$siswaDemo) {
            return;
        }

        // Ambil 10 siswa terdaftar (siswa demo utama + 9 siswa tambahan)
        $daftarEmailSiswa = [
            'siswa@ruangkelas.test',
            'ahmad.fauzi@ruangkelas.test',
            'siti.aisyah@ruangkelas.test',
            'budi.santoso@ruangkelas.test',
            'dewi.lestari@ruangkelas.test',
            'rizky.pratama@ruangkelas.test',
            'nabila.putri@ruangkelas.test',
            'dimas.saputra@ruangkelas.test',
            'anisa.rahmawati@ruangkelas.test',
            'farhan.ramadhan@ruangkelas.test',
        ];
        $allStudents = User::whereIn('email', $daftarEmailSiswa)->get();

        // 1. Buat atau perbarui kelas Matematika Dasar menjadi Bahasa Indonesia Kelas 9A
        $kelas = Kelas::where('kode_kelas', 'MTK10A')
            ->orWhere('kode_kelas', 'BIN9A')
            ->orWhere('nama', 'like', '%Matematika%')
            ->first();

        if ($kelas) {
            $kelas->update([
                'guru_id' => $guru->id,
                'nama' => 'Bahasa Indonesia Kelas 9A',
                'mapel' => 'Bahasa Indonesia',
                'jenjang' => 'Kelas 9',
                'kode_kelas' => 'BIN9A',
                'deskripsi' => 'Pembelajaran Bahasa Indonesia Kelas 9 Semester Ganjil. Mempelajari teks laporan percobaan, pidato persuasif, dan menyusun cerita pendek secara interaktif.',
                'aktif' => true,
            ]);
        } else {
            $kelas = Kelas::create([
                'guru_id' => $guru->id,
                'nama' => 'Bahasa Indonesia Kelas 9A',
                'mapel' => 'Bahasa Indonesia',
                'jenjang' => 'Kelas 9',
                'kode_kelas' => 'BIN9A',
                'deskripsi' => 'Pembelajaran Bahasa Indonesia Kelas 9 Semester Ganjil. Mempelajari teks laporan percobaan, pidato persuasif, dan menyusun cerita pendek secara interaktif.',
                'aktif' => true,
            ]);
        }

        // 2. Daftarkan 10 siswa ke kelas Bahasa Indonesia 9A
        foreach ($allStudents as $student) {
            Enrollment::firstOrCreate([
                'user_id' => $student->id,
                'class_id' => $kelas->id,
            ], [
                'tanggal_gabung' => now()->subDays(14),
            ]);
        }

        // 3. Bersihkan konten bab sebelumnya agar materi Bahasa Indonesia rapi dan segar
        $kelas->chapters()->each(function ($chapter) {
            $chapter->delete();
        });

        // ==========================================
        // BAB 1: MELAPORKAN HASIL PERCOBAAN
        // ==========================================
        $chapter1 = Chapter::create([
            'class_id' => $kelas->id,
            'urutan' => 1,
            'judul' => 'Bab 1: Melaporkan Hasil Percobaan',
            'deskripsi' => 'Memahami fungsi, struktur isi, dan kaidah kebahasaan teks laporan percobaan ilmiah sederhana.',
        ]);

        $mat1_1 = Material::create([
            'chapter_id' => $chapter1->id,
            'urutan' => 1,
            'judul' => 'Mengenal Teks Laporan Percobaan: Ciri, Tujuan, dan Fungsi',
            'tipe' => 'teks',
            'konten' => '<h3>Apa Itu Teks Laporan Percobaan?</h3><p>Teks laporan percobaan adalah teks yang menceritakan tentang percobaan ilmiah yang dilakukan oleh peneliti atau siswa. Teks ini digunakan untuk melaporkan hasil praktikum, karya ilmiah, atau laporan praktis pengujian laboratorium.</p><h4>Ciri-Ciri Utama Teks Laporan Percobaan:</h4><ul><li><b>Objektif:</b> Disusun berdasarkan fakta empiris hasil pengamatan nyata, bukan opini pribadi.</li><li><b>Sistematika Baku:</b> Memiliki urutan runtut dari tujuan, hipotesis, alat & bahan, prosedur kerja, data hasil percobaan, hingga kesimpulan.</li><li><b>Kaidah Bahasa Ilmiah:</b> Menggunakan istilah ilmiah, kalimat pasif atau imperatif dalam langkah kerja, serta kata kerja tindakan.</li></ul>',
            'durasi_menit' => 15,
        ]);

        $mat1_2 = Material::create([
            'chapter_id' => $chapter1->id,
            'urutan' => 2,
            'judul' => 'Struktur Lengkap dan Kaidah Kebahasaan Teks Laporan Percobaan',
            'tipe' => 'teks',
            'konten' => '<h3>Struktur Teks Laporan Percobaan</h3><ol><li><b>Pernyataan Umum (Klasifikasi & Tujuan):</b> Menentukan arah percobaan dan latar belakang masalah.</li><li><b>Alat dan Bahan:</b> Daftar seluruh peralatan dan bahan uji yang dibutuhkan secara terperinci.</li><li><b>Langkah-Langkah Kerja:</b> Prosedur pengujian berurutan langkah demi langkah.</li><li><b>Hasil Percobaan:</b> Data deskriptif, tabel, atau catatan pengamatan hasil reaksi/pengujian.</li><li><b>Simpulan:</b> Intisari jawaban dari hipotesis berdasarkan hasil data percobaan.</li></ol><h4>Unsur Kebahasaan:</h4><p>Menggunakan kata bilangan (numeralia), kalimat perintah terukur (misal: "teteskan 3 tetes larutan"), serta kata hubung waktu dan hubungan sebab-akibat.</p>',
            'durasi_menit' => 20,
        ]);

        $mat1_3 = Material::create([
            'chapter_id' => $chapter1->id,
            'urutan' => 3,
            'judul' => 'Video Telaah Percobaan: Uji Kandungan Nutrisi & Karbohidrat',
            'tipe' => 'video',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'durasi_menit' => 12,
        ]);

        $mat1_4 = Material::create([
            'chapter_id' => $chapter1->id,
            'urutan' => 4,
            'judul' => 'Pedoman Penulisan dan Sistematika Laporan Ilmiah SMP',
            'tipe' => 'link',
            'url' => 'https://kemdikbud.go.id',
            'durasi_menit' => 10,
            'konten' => 'Eksplorasi pedoman baku penyusunan laporan ilmiah remaja, penulisan daftar pustaka, dan penggunaan kaidah ejaan PUEBI / EYD Edisi V.',
        ]);

        $tugas1 = Assignment::create([
            'chapter_id' => $chapter1->id,
            'urutan' => 1,
            'judul' => 'Tugas 1: Menyusun Teks Laporan Percobaan Sederhana di Rumah',
            'deskripsi' => "Pilihlah salah satu percobaan sains sederhana yang aman dilakukan di rumah (contoh: menguji kandungan amilum nasi dengan larutan iodin/betadine, atau percobaan kapilaritas air pada sawi putih).\n\nSusunlah laporan hasil pengamatan tersebut secara lengkap dengan format:\n1. Judul Percobaan\n2. Tujuan Percobaan\n3. Alat dan Bahan\n4. Langkah Kerja Runtut\n5. Tabel / Catatan Hasil Pengamatan\n6. Kesimpulan\n\nUnggah dokumen laporan Anda atau cantumkan tautan Google Drive dokumen tugas Anda.",
            'deadline' => now()->addDays(5),
            'poin_maksimal' => 100,
        ]);

        $kuis1 = Quiz::create([
            'chapter_id' => $chapter1->id,
            'urutan' => 1,
            'judul' => 'Kuis Pemahaman: Struktur & Bahasa Teks Laporan Percobaan',
            'deskripsi' => 'Uji pemahaman Anda mengenai tujuan, struktur baku, dan kaidah bahasa teks laporan percobaan.',
            'durasi_menit' => 20,
            'kkm' => 75,
            'acak_soal' => false,
        ]);

        $soal1_1 = Question::create([
            'quiz_id' => $kuis1->id,
            'urutan' => 1,
            'pertanyaan' => 'Manakah urutan struktur teks laporan percobaan yang paling tepat dan sistematis?',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => 'Urutan baku struktur teks laporan percobaan diawali dari Tujuan, Alat & Bahan, Langkah-langkah kerja, Hasil pengamatan, dan diakhiri dengan Kesimpulan.',
        ]);
        QuestionOption::create(['question_id' => $soal1_1->id, 'label' => 'A', 'teks' => 'Tujuan, Alat & Bahan, Langkah-langkah, Hasil Pengamatan, Kesimpulan', 'is_benar' => true]);
        QuestionOption::create(['question_id' => $soal1_1->id, 'label' => 'B', 'teks' => 'Langkah-langkah, Hasil, Tujuan, Kesimpulan, Alat & Bahan', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal1_1->id, 'label' => 'C', 'teks' => 'Hasil Pengamatan, Kesimpulan, Judul, Alat & Bahan, Tujuan', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal1_1->id, 'label' => 'D', 'teks' => 'Alat & Bahan, Kesimpulan, Pembahasan, Langkah-langkah, Tujuan', 'is_benar' => false]);

        $soal1_2 = Question::create([
            'quiz_id' => $kuis1->id,
            'urutan' => 2,
            'pertanyaan' => 'Perhatikan kutipan berikut: "Campurkan 5 tetes larutan betadine ke dalam air perasan jeruk nipis, kemudian aduk secara perlahan selama 30 detik." Ciri kebahasaan yang dominan pada kalimat tersebut adalah...',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => 'Kata "Campurkan" dan "aduk" merupakan verba perintah (imperatif), sedangkan "5 tetes" dan "30 detik" adalah kata bilangan (numeralia).',
        ]);
        QuestionOption::create(['question_id' => $soal1_2->id, 'label' => 'A', 'teks' => 'Kalimat perintah (imperatif) dan kata bilangan (numeralia)', 'is_benar' => true]);
        QuestionOption::create(['question_id' => $soal1_2->id, 'label' => 'B', 'teks' => 'Kata sifat deskriptif dan majas perbandingan', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal1_2->id, 'label' => 'C', 'teks' => 'Kalimat tanya retoris dan konjungsi kausalitas', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal1_2->id, 'label' => 'D', 'teks' => 'Kata serapan asing dan kalimat pasif', 'is_benar' => false]);

        // ==========================================
        // BAB 2: MENYAMPAIKAN GAGASAN MELALUI PIDATO PERSUASIF
        // ==========================================
        $chapter2 = Chapter::create([
            'class_id' => $kelas->id,
            'urutan' => 2,
            'judul' => 'Bab 2: Menyampaikan Gagasan Melalui Pidato Persuasif',
            'deskripsi' => 'Mempelajari seni mempengaruhi, membujuk, dan meyakinkan audiens dengan argumen etis, logis, dan emosional.',
        ]);

        $mat2_1 = Material::create([
            'chapter_id' => $chapter2->id,
            'urutan' => 1,
            'judul' => 'Hakikat Pidato Persuasif dan Tiga Pilar Persuasi (Etos, Logos, Patos)',
            'tipe' => 'teks',
            'konten' => '<h3>Pengertian Pidato Persuasif</h3><p>Pidato persuasif adalah bentuk komunikasi lisan yang bertujuan untuk meyakinkan, membujuk, atau mengajak pendengar agar tergerak untuk melakukan tindakan tertentu sesuai pesan yang disampaikan pembicara.</p><h4>Tiga Pilar Persuasi menurut Aristoteles:</h4><ul><li><b>Etos (Etika & Kredibilitas):</b> Mengedepankan integritas, kepribadian santun, dan rekam jejak pembicara yang dipercaya.</li><li><b>Logos (Logika & Fakta):</b> Membangun argumentasi kuat menggunakan data, penalaran akal sehat, dan bukti statistik yang terverifikasi.</li><li><b>Patos (Sentuhan Emosi):</b> Menggugah perasaan, simpati, empati, dan kepedulian audiens.</li></ul>',
            'durasi_menit' => 15,
        ]);

        $mat2_2 = Material::create([
            'chapter_id' => $chapter2->id,
            'urutan' => 2,
            'judul' => 'Struktur Teks Pidato Persuasif: Pembukaan, Isi Pidato, dan Penutup',
            'tipe' => 'teks',
            'konten' => '<h3>Struktur Naskah Pidato Persuasif</h3><ol><li><b>Bagian Pembukaan:</b> Salam pembuka, sapaan penghormatan kepada hadirin, ucapan rasa syukur, dan pengantar pokok masalah.</li><li><b>Bagian Isi:</b> Pernyataan posisi pembicara, rangkaian argumen fakta pendukung, dan penguatan ajakan bertindak (persuasi).</li><li><b>Bagian Penutup:</b> Kesimpulan singkat, permohonan maaf atas tutur kata, apresiasi kepada hadirin, dan salam penutup.</li></ol>',
            'durasi_menit' => 20,
        ]);

        $mat2_3 = Material::create([
            'chapter_id' => $chapter2->id,
            'urutan' => 3,
            'judul' => 'Video Retorika: Seni Public Speaking dan Intonasi Berpidato',
            'tipe' => 'video',
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'durasi_menit' => 15,
        ]);

        $tugas2 = Assignment::create([
            'chapter_id' => $chapter2->id,
            'urutan' => 1,
            'judul' => 'Tugas 2: Menulis Naskah Pidato Persuasif Bertema Lingkungan Hidup',
            'deskripsi' => "Tuliskan naskah pidato persuasif sepanjang 3-4 paragraf dengan tema: 'Pengurangan Penggunaan Sampah Plastik Sekali Pakai di Lingkungan Sekolah'.\n\nPastikan memuat:\n1. Pembukaan santun dan ucapan syukur\n2. Rangkaian argumen berbasis data fakta tentang bahaya sampah plastik\n3. Ajakan nyata yang persuasif dan menggugah semangat teman-teman sekelas\n4. Penutup yang berkesan",
            'deadline' => now()->addDays(7),
            'poin_maksimal' => 100,
        ]);

        $kuis2 = Quiz::create([
            'chapter_id' => $chapter2->id,
            'urutan' => 1,
            'judul' => 'Kuis Pemahaman: Kaidah & Retorika Pidato Persuasif',
            'deskripsi' => 'Evaluasi pemahaman konsep pilar persuasi dan sistematika pidato.',
            'durasi_menit' => 15,
            'kkm' => 75,
            'acak_soal' => false,
        ]);

        $soal2_1 = Question::create([
            'quiz_id' => $kuis2->id,
            'urutan' => 1,
            'pertanyaan' => 'Dalam pidato persuasif, pendekatan yang menggunakan bukti data ilmiah, fakta statistik, dan penalaran masuk akal disebut pendekatan...',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => 'Logos merupakan pembuktian argumen berdasarkan logika, nalar, dan data fakta yang sahih.',
        ]);
        QuestionOption::create(['question_id' => $soal2_1->id, 'label' => 'A', 'teks' => 'Logos (Logika)', 'is_benar' => true]);
        QuestionOption::create(['question_id' => $soal2_1->id, 'label' => 'B', 'teks' => 'Etos (Etika)', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal2_1->id, 'label' => 'C', 'teks' => 'Patos (Emosi)', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal2_1->id, 'label' => 'D', 'teks' => 'Kronos (Waktu)', 'is_benar' => false]);

        $soal2_2 = Question::create([
            'quiz_id' => $kuis2->id,
            'urutan' => 2,
            'pertanyaan' => 'Manakah kalimat di bawah ini yang merupakan contoh kalimat persuasif ajakan?',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => 'Kalimat pilihan A menggunakan kata ajakan "Marilah" dan seruan tindakan langsung yang merupakan esensi kalimat persuasif.',
        ]);
        QuestionOption::create(['question_id' => $soal2_2->id, 'label' => 'A', 'teks' => 'Marilah kita mulai memilah sampah plastik dari diri sendiri demi masa depan bumi kita!', 'is_benar' => true]);
        QuestionOption::create(['question_id' => $soal2_2->id, 'label' => 'B', 'teks' => 'Sampah plastik membutuhkan waktu ratusan tahun agar dapat terurai secara alami.', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal2_2->id, 'label' => 'C', 'teks' => 'Indonesia menduduki peringkat negara penghasil limbah di Asia Tenggara.', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal2_2->id, 'label' => 'D', 'teks' => 'Kemarin kami mengadakan kegiatan kerja bakti di sepanjang selokan sekolah.', 'is_benar' => false]);

        // ==========================================
        // BAB 3: MENGEMBANGKAN CERITA PENDEK (CERPEN)
        // ==========================================
        $chapter3 = Chapter::create([
            'class_id' => $kelas->id,
            'urutan' => 3,
            'judul' => 'Bab 3: Mengembangkan Cerita Pendek (Cerpen)',
            'deskripsi' => 'Menemukan nilai kehidupan, menganalisis struktur alur naratif, dan mengeksplorasi kreativitas penulisan cerita pendek.',
        ]);

        $mat3_1 = Material::create([
            'chapter_id' => $chapter3->id,
            'urutan' => 1,
            'judul' => 'Membedah Unsur Pembangun Cerpen: Intrinsik dan Ekstrinsik',
            'tipe' => 'teks',
            'konten' => '<h3>Unsur Intrinsik Cerpen</h3><p>Unsur intrinsik adalah unsur pembangun karya sastra yang berasal dari dalam teks cerita itu sendiri:</p><ul><li><b>Tema:</b> Gagasan pokok atau ide sentral yang mendasari isi cerita.</li><li><b>Tokoh dan Penokohan:</b> Karakter pelaku dalam cerita beserta perwatakannya (protagonis, antagonis, tritagonis).</li><li><b>Alur (Plot):</b> Rangkaian jalinan peristiwa dari orientasi (pengenalan), rangkaian peristiwa, komplikasi (konflik), hingga resolusi.</li><li><b>Latar (Setting):</b> Tempat, waktu, dan suasana terjadinya peristiwa.</li><li><b>Sudut Pandang:</b> Cara pengarang memposisikan dirinya dalam kisah (orang pertama "aku" atau orang ketiga "dia/mereka").</li><li><b>Amanat:</b> Pesan moral yang ingin disampaikan pengarang kepada pembaca.</li></ul>',
            'durasi_menit' => 20,
        ]);

        $mat3_2 = Material::create([
            'chapter_id' => $chapter3->id,
            'urutan' => 2,
            'judul' => 'Teknik Merancang Alur, Membangun Konflik, dan Karakter Tokoh',
            'tipe' => 'teks',
            'konten' => '<h3>Membangun Konflik Cerita yang Menarik</h3><p>Konflik adalah pendorong utama jalannya cerita. Jenis konflik terdiri dari konflik batin (pertentangan moral diri sendiri) dan konflik eksternal (benturan tokoh dengan lingkungan atau tokoh lain).</p><h4>Mencapai Titik Klimaks:</h4><p>Susun ketegangan cerita sedikit demi sedikit hingga mencapai puncak masalah (klimaks), lalu arahkan tokoh menuju penyelesaian masalah (resolusi) yang memuaskan dan berkesan.</p>',
            'durasi_menit' => 15,
        ]);

        $mat3_3 = Material::create([
            'chapter_id' => $chapter3->id,
            'urutan' => 3,
            'judul' => 'Tautan Referensi: Telaah Cerpen Pilihan Sastrawan Indonesia',
            'tipe' => 'link',
            'url' => 'https://badanbahasa.kemdikbud.go.id',
            'durasi_menit' => 10,
            'konten' => 'Apresiasi kumpulan cerpen literasi remaja dan ulasan karya sastrawan Indonesia melalui laman resmi Badan Pengembangan dan Pembinaan Bahasa.',
        ]);

        $tugas3 = Assignment::create([
            'chapter_id' => $chapter3->id,
            'urutan' => 1,
            'judul' => 'Tugas 3: Menyusun Sinopsis dan Rancang Alur Cerita Pendek Remaja',
            'deskripsi' => "Rancanglah draf kerangka cerpen dengan tema: 'Kejujuran dan Persahabatan di Sekolah'.\n\nTentukan:\n1. Judul cerpen dan tokoh utama beserta karakternya\n2. Latar tempat, waktu, dan suasana cerita\n3. Konflik utama yang dihadapi tokoh\n4. Titik klimaks dan resolusi penyelesaian konflik\n5. Amanat atau pesan moral yang ingin disampaikan",
            'deadline' => now()->addDays(10),
            'poin_maksimal' => 100,
        ]);

        $kuis3 = Quiz::create([
            'chapter_id' => $chapter3->id,
            'urutan' => 1,
            'judul' => 'Kuis Pemahaman: Unsur Intrinsik dan Alur Cerita Pendek',
            'deskripsi' => 'Uji kepekaan sastra dan pemahaman unsur pembangun cerita pendek.',
            'durasi_menit' => 15,
            'kkm' => 75,
            'acak_soal' => false,
        ]);

        $soal3_1 = Question::create([
            'quiz_id' => $kuis3->id,
            'urutan' => 1,
            'pertanyaan' => 'Tahapan alur dalam cerpen di mana konflik antartokoh mulai memuncak dan mencapai ketegangan tertinggi disebut tahap...',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => 'Klimaks adalah titik puncak ketegangan dari seluruh rangkaian peristiwa dalam alur naratif.',
        ]);
        QuestionOption::create(['question_id' => $soal3_1->id, 'label' => 'A', 'teks' => 'Klimaks (Puncak Konflik)', 'is_benar' => true]);
        QuestionOption::create(['question_id' => $soal3_1->id, 'label' => 'B', 'teks' => 'Orientasi (Pengenalan)', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal3_1->id, 'label' => 'C', 'teks' => 'Resolusi (Penyelesaian)', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal3_1->id, 'label' => 'D', 'teks' => 'Koda (Amanat Akhir)', 'is_benar' => false]);

        $soal3_2 = Question::create([
            'quiz_id' => $kuis3->id,
            'urutan' => 2,
            'pertanyaan' => 'Jika pengarang menggunakan kata ganti "Aku" atau "Saya" sebagai tokoh utama yang menceritakan kisahnya sendiri, maka sudut pandang yang dipakai adalah...',
            'tipe' => 'pilihan_ganda',
            'bobot' => 50,
            'penjelasan' => 'Penggunaan kata ganti orang pertama ("Aku/Saya") sebagai pelaku sentral cerita menunjukkan sudut pandang orang pertama pelaku utama.',
        ]);
        QuestionOption::create(['question_id' => $soal3_2->id, 'label' => 'A', 'teks' => 'Sudut pandang orang pertama pelaku utama', 'is_benar' => true]);
        QuestionOption::create(['question_id' => $soal3_2->id, 'label' => 'B', 'teks' => 'Sudut pandang orang ketiga serba tahu', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal3_2->id, 'label' => 'C', 'teks' => 'Sudut pandang orang ketiga pengamat', 'is_benar' => false]);
        QuestionOption::create(['question_id' => $soal3_2->id, 'label' => 'D', 'teks' => 'Sudut pandang orang kedua campuran', 'is_benar' => false]);

        // ==========================================
        // SIMULASI PROGRES BELAJAR, TUGAS, & KUIS LENGKAP UNTUK 10 SISWA
        // ==========================================
        $materials = [
            $mat1_1, $mat1_2, $mat1_3, $mat1_4,
            $mat2_1, $mat2_2, $mat2_3,
            $mat3_1, $mat3_2, $mat3_3,
        ];

        $simulasiSiswa = [
            // 1. Siswa Demo Utama (Sinta)
            'siswa@ruangkelas.test' => [
                'materi_selesai' => 7, // 70% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 92, 'catatan_guru' => 'Laporan disusun sangat rapi dan metodis! Data pengamatan disajikan lengkap dengan foto serta analisis kesimpulan yang tepat.'],
                    ['tugas' => $tugas2, 'nilai' => 90, 'catatan_guru' => 'Argumen persuasi lingkungan sangat menggugah dan runtut.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 100],
                    ['kuis' => $kuis2, 'skor' => 90],
                ],
            ],
            // 2. Ahmad Fauzi
            'ahmad.fauzi@ruangkelas.test' => [
                'materi_selesai' => 9, // 90% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 88, 'catatan_guru' => 'Hasil pengamatan sangat baik, analisis langkah kerja runtut.'],
                    ['tugas' => $tugas2, 'nilai' => 85, 'catatan_guru' => 'Penyusunan naskah pidato rapi dan pilihan kata santun.'],
                    ['tugas' => $tugas3, 'nilai' => 90, 'catatan_guru' => 'Sinopsis cerpen menarik dan alur cerita mengalir.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 85],
                    ['kuis' => $kuis2, 'skor' => 80],
                ],
            ],
            // 3. Siti Aisyah (Siswa Berprestasi)
            'siti.aisyah@ruangkelas.test' => [
                'materi_selesai' => 10, // 100% Progres (Tuntas)
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 98, 'catatan_guru' => 'Sistematika sangat sempurna, tabel data terukur rapi dan lengkap.'],
                    ['tugas' => $tugas2, 'nilai' => 95, 'catatan_guru' => 'Gaya retorika persuasif sangat inspiratif dan meyakinkan.'],
                    ['tugas' => $tugas3, 'nilai' => 96, 'catatan_guru' => 'Karakterisasi tokoh cerpen sangat kuat dan hidup.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 100],
                    ['kuis' => $kuis2, 'skor' => 100],
                    ['kuis' => $kuis3, 'skor' => 95],
                ],
            ],
            // 4. Budi Santoso
            'budi.santoso@ruangkelas.test' => [
                'materi_selesai' => 6, // 60% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 82, 'catatan_guru' => 'Cukup baik, perhatikan kaidah tanda baca pada laporan.'],
                    ['tugas' => $tugas2, 'nilai' => 80, 'catatan_guru' => 'Argumen fakta sudah cukup jelas dan runtut.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 80],
                    ['kuis' => $kuis2, 'skor' => 75],
                ],
            ],
            // 5. Dewi Lestari
            'dewi.lestari@ruangkelas.test' => [
                'materi_selesai' => 8, // 80% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 94, 'catatan_guru' => 'Penulisan laporan percobaan sangat terstruktur dan detail.'],
                    ['tugas' => $tugas2, 'nilai' => 90, 'catatan_guru' => 'Pilihan kata baku dan ajakan persuasif efektif.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 95],
                    ['kuis' => $kuis2, 'skor' => 85],
                ],
            ],
            // 6. Rizky Pratama
            'rizky.pratama@ruangkelas.test' => [
                'materi_selesai' => 5, // 50% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 78, 'catatan_guru' => 'Langkah kerja sudah sesuai panduan praktikum sederhana.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 80],
                ],
            ],
            // 7. Nabila Putri
            'nabila.putri@ruangkelas.test' => [
                'materi_selesai' => 8, // 80% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 89, 'catatan_guru' => 'Hasil observasi detail dan penjelasan simpulan akurat.'],
                    ['tugas' => $tugas2, 'nilai' => 92, 'catatan_guru' => 'Pesan moral kepedulian lingkungan tersampaikan dengan sangat baik.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 90],
                    ['kuis' => $kuis2, 'skor' => 90],
                ],
            ],
            // 8. Dimas Saputra
            'dimas.saputra@ruangkelas.test' => [
                'materi_selesai' => 4, // 40% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 76, 'catatan_guru' => 'Tingkatkan kerapian format laporan dan ketelitian tabel data.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 75],
                ],
            ],
            // 9. Anisa Rahmawati
            'anisa.rahmawati@ruangkelas.test' => [
                'materi_selesai' => 9, // 90% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 95, 'catatan_guru' => 'Percobaan sangat kreatif dan analisis ilmiah terukur.'],
                    ['tugas' => $tugas2, 'nilai' => 92, 'catatan_guru' => 'Pidato persuasif sangat berbobot dan bermakna.'],
                    ['tugas' => $tugas3, 'nilai' => 88, 'catatan_guru' => 'Alur dan konflik cerpen menarik untuk dibaca.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 95],
                    ['kuis' => $kuis2, 'skor' => 90],
                ],
            ],
            // 10. Farhan Ramadhan
            'farhan.ramadhan@ruangkelas.test' => [
                'materi_selesai' => 3, // 30% Progres
                'tugas' => [
                    ['tugas' => $tugas1, 'nilai' => 80, 'catatan_guru' => 'Sudah bagus, lanjutkan untuk materi dan kuis di bab berikutnya.'],
                ],
                'kuis' => [
                    ['kuis' => $kuis1, 'skor' => 75],
                ],
            ],
        ];

        foreach ($simulasiSiswa as $email => $data) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                continue;
            }

            // Catat progres materi
            for ($i = 0; $i < $data['materi_selesai']; $i++) {
                if (isset($materials[$i])) {
                    Progress::firstOrCreate([
                        'user_id' => $user->id,
                        'material_id' => $materials[$i]->id,
                    ], [
                        'status_selesai' => true,
                        'selesai_at' => now()->subDays(10 - $i),
                    ]);
                }
            }

            // Catat submisi tugas yang dinilai
            foreach ($data['tugas'] as $tItem) {
                AssignmentSubmission::firstOrCreate([
                    'assignment_id' => $tItem['tugas']->id,
                    'user_id' => $user->id,
                ], [
                    'file_jawaban' => null,
                    'link_tugas' => 'https://drive.google.com/ruangkelas/tugas-' . $user->id,
                    'catatan_siswa' => 'Berikut lembar naskah jawaban tugas saya Pak Guru. Terima kasih banyak.',
                    'nilai' => $tItem['nilai'],
                    'catatan_guru' => $tItem['catatan_guru'],
                    'status' => 'graded',
                    'submitted_at' => now()->subDays(3),
                    'graded_at' => now()->subHours(6),
                ]);
            }

            // Catat riwayat percobaan kuis
            foreach ($data['kuis'] as $kItem) {
                QuizAttempt::firstOrCreate([
                    'quiz_id' => $kItem['kuis']->id,
                    'user_id' => $user->id,
                ], [
                    'started_at' => now()->subHours(6),
                    'completed_at' => now()->subHours(5)->subMinutes(35),
                    'skor' => $kItem['skor'],
                    'total_benar' => 2,
                    'total_salah' => 0,
                    'status' => 'selesai',
                ]);
            }
        }
    }
}
