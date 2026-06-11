<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Memperbaiki urutan soal Duolingo-style...\n\n";

$quizzes = DB::table('quizzes')->get();

foreach ($quizzes as $quiz) {
    echo "📝 Quiz ID: {$quiz->id} - {$quiz->title}\n";
    
    // Hitung jumlah soal text (legacy)
    $textCount = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->id)
        ->where('question_type', 'text')
        ->count();
    
    echo "   → Soal text: {$textCount}\n";
    
    // Pindahkan soal Duolingo ke awal (order 1, 2, 3)
    $duoQuestions = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->id)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->orderBy('order')
        ->get();
    
    $newOrder = 1;
    foreach ($duoQuestions as $q) {
        DB::table('quiz_questions')
            ->where('id', $q->id)
            ->update(['order' => $newOrder]);
        echo "   → Soal {$q->question_type} (ID: {$q->id}) dipindah ke order {$newOrder}\n";
        $newOrder++;
    }
    
    // Geser soal text ke belakang (mulai dari order 100)
    $textQuestions = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->id)
        ->where('question_type', 'text')
        ->orderBy('order')
        ->get();
    
    $newOrder = 100;
    foreach ($textQuestions as $q) {
        DB::table('quiz_questions')
            ->where('id', $q->id)
            ->update(['order' => $newOrder]);
        $newOrder++;
    }
    
    echo "   → Soal text dipindah ke order 100+\n\n";
}

echo "✅ Done!\n\n";

// Verifikasi
echo "📊 VERIFIKASI URUTAN BARU:\n";
echo str_repeat("=", 80) . "\n";

foreach ($quizzes as $quiz) {
    $questions = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->id)
        ->orderBy('order')
        ->get();
    
    echo "\nQuiz {$quiz->id} - {$quiz->title}:\n";
    foreach ($questions->take(5) as $q) {
        $type = $q->question_type === 'text' ? '📝' : 
                ($q->question_type === 'image' ? '🖼️' : 
                ($q->question_type === 'translation' ? '🗣️' : '✏️'));
        echo "  Order {$q->order}: {$type} {$q->question}\n";
    }
    if ($questions->count() > 5) {
        echo "  ... dan " . ($questions->count() - 5) . " soal lainnya\n";
    }
}