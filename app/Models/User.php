<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'foto', 'sekolah'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Kelas yang dibuat oleh Guru ini
     */
    public function classesCreated(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    /**
     * Relasi pendaftaran kelas untuk Siswa ini
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }

    /**
     * Daftar kelas yang diikuti oleh Siswa ini
     */
    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'enrollments', 'user_id', 'class_id')
            ->withPivot('tanggal_gabung')
            ->withTimestamps();
    }

    /**
     * Riwayat pengerjaan kuis siswa
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'user_id');
    }

    /**
     * Progres belajar siswa
     */
    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class, 'user_id');
    }
}
