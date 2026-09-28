<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->string('judul');
            $table->enum('tipe', ['video', 'pdf', 'teks', 'link'])->default('teks');
            $table->longText('konten')->nullable();
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->integer('durasi_menit')->nullable();
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
