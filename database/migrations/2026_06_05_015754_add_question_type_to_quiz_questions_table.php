<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            // Tipe soal: text, image, translation, fillblank
            $table->string('question_type')->default('text')->after('quiz_id');
            
            // Untuk soal image: emoji atau URL gambar
            $table->string('image_emoji')->nullable()->after('question_type');
            $table->string('image_url')->nullable()->after('image_emoji');
            
            // Untuk soal translation: karakter avatar
            $table->string('character_avatar')->nullable()->after('image_url');
            
            // Untuk soal fillblank: kalimat dengan ___
            $table->text('sentence_template')->nullable()->after('character_avatar');
            
            // Untuk soal audio
            $table->string('audio_text')->nullable()->after('sentence_template');
            $table->string('audio_url')->nullable()->after('audio_text');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn([
                'question_type',
                'image_emoji',
                'image_url',
                'character_avatar',
                'sentence_template',
                'audio_text',
                'audio_url'
            ]);
        });
    }
};