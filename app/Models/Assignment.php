<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'judul',
        'deskripsi',
        'file_lampiran',
        'url_referensi',
        'deadline',
        'poin_maksimal',
        'urutan',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    public function submissionByUser(int $userId): HasOne
    {
        return $this->hasOne(AssignmentSubmission::class, 'assignment_id')->where('user_id', $userId);
    }

    public function isSubmittedByUser(int $userId): bool
    {
        return $this->submissions()->where('user_id', $userId)->exists();
    }

    public function getSubmissionForUser(int $userId): ?AssignmentSubmission
    {
        return $this->submissions()->where('user_id', $userId)->first();
    }

    public function isLewatDeadline(): bool
    {
        return $this->deadline && now()->greaterThan($this->deadline);
    }
}
