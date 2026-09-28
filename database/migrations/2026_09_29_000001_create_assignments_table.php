<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('judul');
            $table->longText('deskripsi')->nullable();
            $table->string('file_lampiran')->nullable(); // File PDF soal/petunjuk guru
            $table->string('url_referensi')->nullable(); // Link referensi tugas
            $table->dateTime('deadline')->nullable(); // Batas waktu pengumpulan
            $table->integer('poin_maksimal')->default(100);
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
