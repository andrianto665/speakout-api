<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->integer('score')->comment('Skor akhir dalam persen (0-100)');
            $table->boolean('passed')->comment('Apakah memenuhi passing_score?');
            // Jawaban user: {"1": "A", "2": "C", ...} (key = question_id, value = jawaban)
            $table->json('answers');
            $table->integer('attempt_number')->default(1);
            $table->timestamps();
            
            // Index untuk cek attempt terakhir user
            $table->index(['user_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};