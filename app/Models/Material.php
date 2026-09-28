<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'judul',
        'tipe',
        'konten',
        'file_path',
        'url',
        'durasi_menit',
        'urutan',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class, 'material_id');
    }

    /**
     * Cek apakah materi sudah diselesaikan oleh user tertentu
     */
    public function isSelesaiByUser(int $userId): bool
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('status_selesai', true)
            ->exists();
    }
}
