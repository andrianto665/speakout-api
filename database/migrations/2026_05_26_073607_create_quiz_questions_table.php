<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            // Tipe soal: multiple_choice, true_false (bisa ditambah nanti)
            $table->string('type')->default('multiple_choice');
            // Opsi jawaban disimpan sebagai JSON array: ["A. Jakarta", "B. Bandung", ...]
            $table->json('options');
            // Kunci jawaban: "A" atau "B" (disimpan di backend, tidak dikirim ke frontend saat quiz)
            $table->string('correct_answer');
            $table->integer('points')->default(1);
            $table->integer('order')->default(0)->comment('Urutan tampil soal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};