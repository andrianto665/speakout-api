<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Fixing quiz structure...\n\n";

// ============================================
// PART 1: Hapus soal text dari quiz Duolingo baru (ID 17-24)
// ============================================
echo " PART 1: Menghapus soal text dari quiz Duolingo baru...\n\n";

$duoQuizIds = [17, 18, 19, 20, 21, 22, 23, 24];

foreach ($duoQuizIds as $quizId) {
    $textCount = DB::table('quiz_questions')
        ->where('quiz_id', $quizId)
        ->where('question_type', 'text')
        ->count();
    
    if ($textCount > 0) {
        DB::table('quiz_questions')
            ->where('quiz_id', $quizId)
            ->where('question_type', 'text')
            ->delete();
        
        echo "   ✅ Quiz {$quizId}: Hapus {$textCount} soal text\n";
    } else {
        echo "   ⏭️  Quiz {$quizId}: Tidak ada soal text untuk dihapus\n";
    }
}

echo "\n";

// ============================================
// PART 2: Verifikasi struktur quiz
// ============================================
echo "📊 PART 2: Verifikasi struktur quiz...\n\n";

$allQuizzes = DB::table('quizzes')
    ->join('meetings', 'quizzes.meeting_id', '=', 'meetings.id')
    ->join('courses', 'meetings.course_id', '=', 'courses.id')
    ->select('quizzes.id as quiz_id', 'quizzes.title as quiz_title', 
             'courses.title as course_title', 'meetings.order_number')
    ->orderBy('courses.id')
    ->orderBy('meetings.order_number')
    ->get();

$currentCourse = '';
foreach ($allQuizzes as $quiz) {
    if ($quiz->course_title !== $currentCourse) {
        $currentCourse = $quiz->course_title;
        echo "\n📚 {$currentCourse}\n";
    }
    
    $total = DB::table('quiz_questions')->where('quiz_id', $quiz->quiz_id)->count();
    $duoCount = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->quiz_id)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->count();
    $textCount = $total - $duoCount;
    
    // ✅ FIX: Tambahkan tanda kurung untuk ternary operator
    if ($duoCount > 0 && $textCount == 0) {
        $type = '🎨 Duolingo Only';
    } elseif ($duoCount > 0 && $textCount > 0) {
        $type = '⚠️ Mixed';
    } else {
        $type = '📝 Text Only';
    }
    
    echo "   {$type} Order {$quiz->order_number}: {$quiz->quiz_title} (Total: {$total}, Duo: {$duoCount}, Text: {$textCount})\n";
}

echo "\n✅ Done!\n";