<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'guru_id',
        'nama',
        'mapel',
        'jenjang',
        'deskripsi',
        'kode_kelas',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * Boot helper untuk generate kode_kelas unik otomatis jika belum ada
     */
    protected static function booted(): void
    {
        static::creating(function ($kelas) {
            if (empty($kelas->kode_kelas)) {
                $kelas->kode_kelas = self::generateKodeKelas();
            }
        });
    }

    public static function generateKodeKelas(): string
    {
        do {
            $kode = strtoupper(Str::random(6));
        } while (self::where('kode_kelas', $kode)->exists());

        return $kode;
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'class_id', 'user_id')
            ->withPivot('tanggal_gabung')
            ->withTimestamps();
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'class_id')->orderBy('urutan');
    }
}
