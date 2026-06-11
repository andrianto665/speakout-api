<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🚀 Generating Duolingo-style questions with course-specific variations...\n\n";

// ✅ Ambil semua quiz yang ada
$quizzes = DB::table('quizzes')
    ->join('meetings', 'quizzes.meeting_id', '=', 'meetings.id')
    ->join('courses', 'meetings.course_id', '=', 'courses.id')
    ->select('quizzes.id as quiz_id', 'courses.id as course_id', 'courses.title as course_title')
    ->get();

// ✅ Variasi soal berdasarkan course
$questionSets = [
    2 => [ // English For Family
        'image' => [
            'question' => 'Mana yang artinya "ibu"?',
            'image_emoji' => '👩',
            'options' => [
                ['emoji' => '👨', 'label' => 'father', 'correct' => false],
                ['emoji' => '👩', 'label' => 'mother', 'correct' => true],
                ['emoji' => '👶', 'label' => 'baby', 'correct' => false],
            ],
        ],
        'translation' => [
            'question' => 'Pilih arti yang benar',
            'character_avatar' => '👨',
            'options' => [
                ['text' => 'paman', 'correct' => false],
                ['text' => 'kakek', 'correct' => false],
                ['text' => 'ayah', 'correct' => true],
            ],
        ],
        'fillblank' => [
            'question' => 'Ikuti polanya',
            'character_avatar' => '🐻',
            'audio_text' => 'My father is a man.',
            'sentence_template' => 'My mother is a ___.',
            'options' => [
                ['text' => 'woman', 'correct' => true],
                ['text' => 'man', 'correct' => false],
                ['text' => 'child', 'correct' => false],
            ],
        ],
    ],
    6 => [ // English For Society
        'image' => [
            'question' => 'Mana yang artinya "teman"?',
            'image_emoji' => '👫',
            'options' => [
                ['emoji' => '👨‍💼', 'label' => 'boss', 'correct' => false],
                ['emoji' => '👫', 'label' => 'friend', 'correct' => true],
                ['emoji' => '👨‍🏫', 'label' => 'teacher', 'correct' => false],
            ],
        ],
        'translation' => [
            'question' => 'Pilih arti yang benar',
            'character_avatar' => '👩',
            'options' => [
                ['text' => 'musuh', 'correct' => false],
                ['text' => 'tetangga', 'correct' => true],
                ['text' => 'stranger', 'correct' => false],
            ],
        ],
        'fillblank' => [
            'question' => 'Ikuti polanya',
            'character_avatar' => '🐻',
            'audio_text' => 'I have many friends.',
            'sentence_template' => 'She has many ___.',
            'options' => [
                ['text' => 'friends', 'correct' => true],
                ['text' => 'friend', 'correct' => false],
                ['text' => 'enemy', 'correct' => false],
            ],
        ],
    ],
    7 => [ // English For Professional
        'image' => [
            'question' => 'Mana yang artinya "kantor"?',
            'image_emoji' => '🏢',
            'options' => [
                ['emoji' => '🏠', 'label' => 'home', 'correct' => false],
                ['emoji' => '🏢', 'label' => 'office', 'correct' => true],
                ['emoji' => '🏫', 'label' => 'school', 'correct' => false],
            ],
        ],
        'translation' => [
            'question' => 'Pilih arti yang benar',
            'character_avatar' => '👨‍💼',
            'options' => [
                ['text' => 'karyawan', 'correct' => true],
                ['text' => 'bos', 'correct' => false],
                ['text' => 'manajer', 'correct' => false],
            ],
        ],
        'fillblank' => [
            'question' => 'Ikuti polanya',
            'character_avatar' => '🐻',
            'audio_text' => 'I work in an office.',
            'sentence_template' => 'She ___ in a hospital.',
            'options' => [
                ['text' => 'works', 'correct' => true],
                ['text' => 'work', 'correct' => false],
                ['text' => 'working', 'correct' => false],
            ],
        ],
    ],
    8 => [ // English For Family 2023 (Beta)
        'image' => [
            'question' => 'Mana yang artinya "adik"?',
            'image_emoji' => '👶',
            'options' => [
                ['emoji' => '👨', 'label' => 'brother', 'correct' => false],
                ['emoji' => '👩', 'label' => 'sister', 'correct' => false],
                ['emoji' => '👶', 'label' => 'baby', 'correct' => true],
            ],
        ],
        'translation' => [
            'question' => 'Pilih arti yang benar',
            'character_avatar' => '👨',
            'options' => [
                ['text' => 'kakek', 'correct' => false],
                ['text' => 'nenek', 'correct' => true],
                ['text' => 'paman', 'correct' => false],
            ],
        ],
        'fillblank' => [
            'question' => 'Ikuti polanya',
            'character_avatar' => '🐻',
            'audio_text' => 'My brother is tall.',
            'sentence_template' => 'My sister is ___.',
            'options' => [
                ['text' => 'short', 'correct' => true],
                ['text' => 'tall', 'correct' => false],
                ['text' => 'big', 'correct' => false],
            ],
        ],
    ],
];

foreach ($quizzes as $quiz) {
    $courseId = $quiz->course_id;
    $quizId = $quiz->quiz_id;
    $courseTitle = $quiz->course_title;
    
    echo "📝 Processing: {$courseTitle} (Quiz ID: {$quizId})\n";
    
    // Cek apakah sudah ada soal Duolingo-style
    $existingDuo = DB::table('quiz_questions')
        ->where('quiz_id', $quizId)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->count();
    
    if ($existingDuo >= 3) {
        echo "   ✅ Soal Duolingo-style sudah lengkap ({$existingDuo} soal)\n\n";
        continue;
    }
    
    // Hapus jika ada sebagian
    if ($existingDuo > 0) {
        DB::table('quiz_questions')
            ->where('quiz_id', $quizId)
            ->whereIn('question_type', ['image', 'translation', 'fillblank'])
            ->delete();
        echo "   🗑️  Removed {$existingDuo} old questions\n";
    }
    
    // Ambil soal berdasarkan course
    $questions = $questionSets[$courseId] ?? $questionSets[2]; // Default ke Family
    
    // Tambah soal IMAGE
    DB::table('quiz_questions')->insert([
        'quiz_id' => $quizId,
        'question' => $questions['image']['question'],
        'type' => 'multiple_choice',
        'question_type' => 'image',
        'image_emoji' => $questions['image']['image_emoji'],
        'options' => json_encode($questions['image']['options']),
        'correct_answer' => json_encode(['label' => collect($questions['image']['options'])->firstWhere('correct', true)['label']]),
        'points' => 10,
        'order' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // Tambah soal TRANSLATION
    DB::table('quiz_questions')->insert([
        'quiz_id' => $quizId,
        'question' => $questions['translation']['question'],
        'type' => 'multiple_choice',
        'question_type' => 'translation',
        'character_avatar' => $questions['translation']['character_avatar'],
        'options' => json_encode($questions['translation']['options']),
        'correct_answer' => json_encode(['text' => collect($questions['translation']['options'])->firstWhere('correct', true)['text']]),
        'points' => 10,
        'order' => 101,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // Tambah soal FILLBLANK
    DB::table('quiz_questions')->insert([
        'quiz_id' => $quizId,
        'question' => $questions['fillblank']['question'],
        'type' => 'multiple_choice',
        'question_type' => 'fillblank',
        'character_avatar' => $questions['fillblank']['character_avatar'],
        'audio_text' => $questions['fillblank']['audio_text'],
        'sentence_template' => $questions['fillblank']['sentence_template'],
        'options' => json_encode($questions['fillblank']['options']),
        'correct_answer' => json_encode(['text' => collect($questions['fillblank']['options'])->firstWhere('correct', true)['text']]),
        'points' => 10,
        'order' => 102,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "   ✅ 3 soal Duolingo-style ditambahkan\n\n";
}

echo "🎉 Done!\n\n";

// Verifikasi
echo "📊 VERIFIKASI:\n";
echo str_repeat("=", 80) . "\n";

foreach ($quizzes as $quiz) {
    $total = DB::table('quiz_questions')->where('quiz_id', $quiz->quiz_id)->count();
    $duoCount = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->quiz_id)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->count();
    
    // Ambil 1 soal image untuk verifikasi
    $imageQ = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->quiz_id)
        ->where('question_type', 'image')
        ->first();
    
    echo "\nQuiz {$quiz->quiz_id}: {$quiz->course_title}\n";
    echo "  Total soal: {$total} (Duolingo: {$duoCount})\n";
    if ($imageQ) {
        echo "  Soal image: {$imageQ->question}\n";
    }
}