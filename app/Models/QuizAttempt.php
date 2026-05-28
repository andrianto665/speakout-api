<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id', 'quiz_id', 'score', 'passed', 'answers', 'attempt_number'
    ];

    protected $casts = [
        'score' => 'integer',
        'passed' => 'boolean',
        'answers' => 'array',      // JSON jawaban user
        'attempt_number' => 'integer',
    ];

    // 🔗 Relasi ke User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Relasi ke Kuis yang dikerjakan
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}