<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'points',
        'order',
        // ✅ Field baru untuk Duolingo-style
        'question_type',        // text, image, translation, fillblank
        'image_emoji',          // 🥛  🍬
        'image_url',            // URL gambar (optional)
        'character_avatar',     // 👨 🐻
        'sentence_template',    // "Tea ___ sugar."
        'audio_text',           // "Tea with milk."
        'audio_url',            // URL audio file
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}