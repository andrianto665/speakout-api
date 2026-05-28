<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'question', 'type', 'options', 'correct_answer', 'points', 'order'
    ];

    protected $casts = [
        'options' => 'array',      // JSON di DB otomatis jadi PHP array
        'points' => 'integer',
        'order' => 'integer',
    ];

    // 🔗 Relasi ke Kuis induknya
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}