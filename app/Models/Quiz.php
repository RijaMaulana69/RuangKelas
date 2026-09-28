<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'judul',
        'deskripsi',
        'durasi_menit',
        'kkm',
        'acak_soal',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'acak_soal' => 'boolean',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'quiz_id')->orderBy('urutan');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }

    public function userBestAttempt(int $userId)
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->where('status', 'selesai')
            ->orderByDesc('skor')
            ->first();
    }
}
