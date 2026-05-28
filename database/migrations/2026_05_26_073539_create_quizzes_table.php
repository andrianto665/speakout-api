<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // 1 Kuis = 1 Lesson/Meeting (Udemy style: quiz is a type of lecture)
            $table->foreignId('meeting_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->integer('passing_score')->default(70)->comment('Persentase minimal lulus (0-100)');
            $table->integer('time_limit')->nullable()->comment('Dalam menit. Null = unlimited');
            $table->integer('max_attempts')->default(0)->comment('0 = unlimited retry');
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};