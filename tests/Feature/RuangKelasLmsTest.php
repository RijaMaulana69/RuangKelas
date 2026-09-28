<?php

namespace Tests\Feature;

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
use Livewire\Volt\Volt;
use Tests\TestCase;

class RuangKelasLmsTest extends TestCase
{
    protected User $guru;
    protected User $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create(['name' => 'Pak Guru Budi']);
        $this->guru->assignRole('guru');

        $this->siswa = User::factory()->create(['name' => 'Siswa Ani']);
        $this->siswa->assignRole('siswa');
    }

    public function test_login_redirects_guru_to_guru_dashboard(): void
    {
        $response = $this->actingAs($this->guru)->get('/dashboard');
        $response->assertRedirect(route('guru.dashboard'));
    }

    public function test_login_redirects_siswa_to_siswa_dashboard(): void
    {
        $response = $this->actingAs($this->siswa)->get('/dashboard');
        $response->assertRedirect(route('siswa.dashboard'));
    }

    public function test_guru_can_create_class_with_auto_generated_code(): void
    {
        $this->actingAs($this->guru);

        Volt::test('guru.kelas-index')
            ->set('nama', 'Fisika Kuantum XI')
            ->set('mapel', 'Fisika')
            ->set('jenjang', 'SMA')
            ->set('deskripsi', 'Pengenalan mekanika kuantum')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('classes', [
            'guru_id' => $this->guru->id,
            'nama' => 'Fisika Kuantum XI',
            'mapel' => 'Fisika',
            'jenjang' => 'SMA',
        ]);

        $kelas = Kelas::where('nama', 'Fisika Kuantum XI')->first();
        $this->assertNotNull($kelas->kode_kelas);
        $this->assertEquals(6, strlen($kelas->kode_kelas));
    }

    public function test_siswa_can_join_class_using_code(): void
    {
        $kelas = Kelas::create([
            'guru_id' => $this->guru->id,
            'nama' => 'Kimia Organik',
            'mapel' => 'Kimia',
            'jenjang' => 'SMA',
            'kode_kelas' => 'KIM10X',
        ]);

        $this->actingAs($this->siswa);

        Volt::test('siswa.dashboard')
            ->set('kodeKelas', 'KIM10X')
            ->call('gabungKelas')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->siswa->id,
            'class_id' => $kelas->id,
        ]);
    }

    public function test_guru_can_add_chapter_and_material(): void
    {
        $kelas = Kelas::create([
            'guru_id' => $this->guru->id,
            'nama' => 'Biologi Sel',
            'mapel' => 'Biologi',
            'jenjang' => 'SMA',
        ]);

        $this->actingAs($this->guru);

        Volt::test('guru.kelas-detail', ['kelas' => $kelas->id])
            ->set('chapterJudul', 'Bab 1: Membran Sel')
            ->call('simpanChapter')
            ->assertHasNoErrors();

        $chapter = Chapter::where('class_id', $kelas->id)->first();
        $this->assertNotNull($chapter);

        Volt::test('guru.kelas-detail', ['kelas' => $kelas->id])
            ->set('targetChapterId', $chapter->id)
            ->set('materialJudul', 'Struktur Fosfolipid Bilayer')
            ->set('materialTipe', 'teks')
            ->set('materialKonten', 'Penjelasan detail membran sel...')
            ->call('simpanMaterial')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materials', [
            'chapter_id' => $chapter->id,
            'judul' => 'Struktur Fosfolipid Bilayer',
        ]);
    }

    public function test_siswa_can_view_material_and_mark_as_completed(): void
    {
        $kelas = Kelas::create([
            'guru_id' => $this->guru->id,
            'nama' => 'Matematika Aljabar',
            'mapel' => 'Matematika',
            'jenjang' => 'SMA',
        ]);

        Enrollment::create([
            'user_id' => $this->siswa->id,
            'class_id' => $kelas->id,
        ]);

        $chapter = Chapter::create([
            'class_id' => $kelas->id,
            'judul' => 'Bab 1',
        ]);

        $material = Material::create([
            'chapter_id' => $chapter->id,
            'judul' => 'Operasi Matriks',
            'tipe' => 'teks',
            'konten' => 'Penjumlahan dan perkalian matriks',
        ]);

        $this->actingAs($this->siswa);

        Volt::test('siswa.materi-view', ['material' => $material->id])
            ->call('toggleSelesai')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('progress', [
            'user_id' => $this->siswa->id,
            'material_id' => $material->id,
            'status_selesai' => true,
        ]);
    }

    public function test_siswa_can_take_quiz_and_score_is_calculated_correctly(): void
    {
        $kelas = Kelas::create([
            'guru_id' => $this->guru->id,
            'nama' => 'Sejarah Indonesia',
            'mapel' => 'Sejarah',
            'jenjang' => 'SMA',
        ]);

        Enrollment::create([
            'user_id' => $this->siswa->id,
            'class_id' => $kelas->id,
        ]);

        $chapter = Chapter::create([
            'class_id' => $kelas->id,
            'judul' => 'Bab 1 Kemerdekaan',
        ]);

        $quiz = Quiz::create([
            'chapter_id' => $chapter->id,
            'judul' => 'Kuis Proklamasi',
            'kkm' => 70,
        ]);

        $soal = Question::create([
            'quiz_id' => $quiz->id,
            'pertanyaan' => 'Kapan Indonesia merdeka?',
            'bobot' => 100,
        ]);

        $optSalah = QuestionOption::create([
            'question_id' => $soal->id,
            'label' => 'A',
            'teks' => '17 Agustus 1944',
            'is_benar' => false,
        ]);

        $optBenar = QuestionOption::create([
            'question_id' => $soal->id,
            'label' => 'B',
            'teks' => '17 Agustus 1945',
            'is_benar' => true,
        ]);

        $this->actingAs($this->siswa);

        // Siswa menjawab pilihan benar
        Volt::test('siswa.kuis-kerjakan', ['quiz' => $quiz->id])
            ->set('jawaban.' . $soal->id, $optBenar->id)
            ->call('kumpulkanJawaban')
            ->assertHasNoErrors();

        $attempt = QuizAttempt::where('user_id', $this->siswa->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertEquals(100.0, $attempt->skor);
        $this->assertEquals(1, $attempt->total_benar);
        $this->assertEquals(0, $attempt->total_salah);
    }
}
