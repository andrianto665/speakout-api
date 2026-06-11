<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔄 Mengembalikan quiz ke format text asli...\n\n";

// Hapus soal Duolingo dari quiz asli (ID 1-9)
$duolingoQuestions = DB::table('quiz_questions')
    ->whereIn('quiz_id', [1, 3, 4, 5, 6, 7, 8, 9])
    ->whereIn('question_type', ['image', 'translation', 'fillblank'])
    ->get();

echo "📋 Ditemukan {$duolingoQuestions->count()} soal Duolingo yang akan dihapus\n\n";

$deleted = DB::table('quiz_questions')
    ->whereIn('quiz_id', [1, 3, 4, 5, 6, 7, 8, 9])
    ->whereIn('question_type', ['image', 'translation', 'fillblank'])
    ->delete();

echo "✅ Berhasil menghapus {$deleted} soal Duolingo\n\n";

// Update urutan soal text agar mulai dari 1
$quizzes = [1, 3, 4, 5, 6, 7, 8, 9];

foreach ($quizzes as $quizId) {
    $textQuestions = DB::table('quiz_questions')
        ->where('quiz_id', $quizId)
        ->where('question_type', 'text')
        ->orderBy('order')
        ->get();
    
    $newOrder = 1;
    foreach ($textQuestions as $q) {
        DB::table('quiz_questions')
            ->where('id', $q->id)
            ->update(['order' => $newOrder]);
        $newOrder++;
    }
    
    $count = $textQuestions->count();
    echo "✅ Quiz {$quizId}: {$count} soal text (order 1-{$count})\n";
}

echo "\n📊 VERIFIKASI:\n";
echo str_repeat("=", 80) . "\n";

$quizInfo = DB::table('quizzes')
    ->join('meetings', 'quizzes.meeting_id', '=', 'meetings.id')
    ->join('courses', 'meetings.course_id', '=', 'courses.id')
    ->whereIn('quizzes.id', [1, 3, 4, 5, 6, 7, 8, 9])
    ->select('quizzes.id as quiz_id', 'quizzes.title as quiz_title', 
             'courses.title as course_title', 'meetings.order_number')
    ->orderBy('courses.id')
    ->orderBy('meetings.order_number')
    ->get();

$currentCourse = '';
foreach ($quizInfo as $quiz) {
    if ($quiz->course_title !== $currentCourse) {
        $currentCourse = $quiz->course_title;
        echo "\n📚 {$currentCourse}\n";
    }
    
    $total = DB::table('quiz_questions')->where('quiz_id', $quiz->quiz_id)->count();
    $duoCount = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->quiz_id)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->count();
    
    echo "   📝 Order {$quiz->order_number}: {$quiz->quiz_title} ({$total} soal text)\n";
}

echo "\n✅ Quiz asli sudah dikembalikan ke format text!\n";
echo "\n💡 Quiz Duolingo-style baru tetap ada (Quiz ID 17-24)\n";