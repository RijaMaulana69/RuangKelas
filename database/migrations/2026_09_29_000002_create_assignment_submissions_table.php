<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_jawaban')->nullable(); // File upload jawaban siswa
            $table->longText('catatan_siswa')->nullable(); // Teks jawaban / penjelasan siswa
            $table->string('link_tugas')->nullable(); // Link Google Drive/GitHub tugas siswa
            $table->enum('status', ['submitted', 'graded', 'late'])->default('submitted');
            $table->dateTime('submitted_at');
            $table->integer('nilai')->nullable(); // Nilai 0 - 100
            $table->text('catatan_guru')->nullable(); // Feedback / evaluasi dari guru
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            // 1 Siswa hanya boleh submit 1 kali per tugas (bisa re-upload / edit)
            $table->unique(['assignment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
