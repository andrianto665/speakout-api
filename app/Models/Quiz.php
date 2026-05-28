<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'meeting_id', 'title', 'passing_score', 'time_limit',
        'max_attempts', 'shuffle_options', 'show_results_immediately'
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'shuffle_options' => 'boolean',
        'show_results_immediately' => 'boolean',
    ];

    // 🔗 Relasi ke Lesson/Meeting (1 Kuis = 1 Lesson)
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    // 🔗 Relasi ke Soal-soal (1 Kuis punya banyak Soal)
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    // 🔗 Relasi ke Percobaan User (1 Kuis dikerjakan banyak user / berkali-kali)
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}