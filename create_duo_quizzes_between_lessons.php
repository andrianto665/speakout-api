<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎨 Membuat quiz Duolingo-style baru di setiap section...\n\n";

// Struktur quiz untuk setiap course
$courseQuizzes = [
    2 => [ // English For Family
        'course_title' => 'English For Family',
        'sections' => [
            [
                'section_name' => 'Family 1',
                'quiz_title' => 'Quiz: Family Basics',
                'order_number' => 3, // Setelah Lesson 1 & 2 (order 1 & 2)
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "ibu"?',
                        'emoji' => '👩',
                        'options' => [
                            ['emoji' => '👨', 'label' => 'father', 'correct' => false],
                            ['emoji' => '👩', 'label' => 'mother', 'correct' => true],
                            ['emoji' => '👶', 'label' => 'baby', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "grandmother"?',
                        'avatar' => '👨',
                        'options' => [
                            ['text' => 'kakek', 'correct' => false],
                            ['text' => 'nenek', 'correct' => true],
                            ['text' => 'paman', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'My father is a man.',
                        'template' => 'My mother is a ___.',
                        'options' => [
                            ['text' => 'woman', 'correct' => true],
                            ['text' => 'man', 'correct' => false],
                            ['text' => 'child', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Ayah"?', 'options' => ['Father', 'Mother', 'Brother', 'Sister'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Kakak"?', 'options' => ['Older sibling', 'Younger sibling', 'Baby', 'Child'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Adik"?', 'options' => ['Younger sibling', 'Older sibling', 'Baby', 'Child'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Kakek"?', 'options' => ['Grandfather', 'Grandmother', 'Uncle', 'Aunt'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Nenek"?', 'options' => ['Grandmother', 'Grandfather', 'Uncle', 'Aunt'], 'correct' => 0],
                    ],
                ],
            ],
            [
                'section_name' => 'Family 2',
                'quiz_title' => 'Quiz: Daily Activities',
                'order_number' => 7, // Setelah Lesson 1 & 2 section 2 (order 5 & 6)
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "makan"?',
                        'emoji' => '🍽️',
                        'options' => [
                            ['emoji' => '🍽️', 'label' => 'eat', 'correct' => true],
                            ['emoji' => '🏃', 'label' => 'run', 'correct' => false],
                            ['emoji' => '😴', 'label' => 'sleep', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "wake up"?',
                        'avatar' => '👩',
                        'options' => [
                            ['text' => 'tidur', 'correct' => false],
                            ['text' => 'bangun', 'correct' => true],
                            ['text' => 'makan', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'I eat breakfast.',
                        'template' => 'She ___ lunch.',
                        'options' => [
                            ['text' => 'eats', 'correct' => true],
                            ['text' => 'eat', 'correct' => false],
                            ['text' => 'eating', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Tidur"?', 'options' => ['Sleep', 'Eat', 'Run', 'Walk'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Makan siang"?', 'options' => ['Breakfast', 'Lunch', 'Dinner', 'Snack'], 'correct' => 1],
                        ['question' => 'Apa bahasa Inggris dari "Pagi"?', 'options' => ['Morning', 'Afternoon', 'Evening', 'Night'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Malam"?', 'options' => ['Morning', 'Afternoon', 'Evening', 'Night'], 'correct' => 3],
                        ['question' => 'Apa bahasa Inggris dari "Minum"?', 'options' => ['Eat', 'Drink', 'Sleep', 'Run'], 'correct' => 1],
                    ],
                ],
            ],
        ],
    ],
    6 => [ // English For Society
        'course_title' => 'English For Society',
        'sections' => [
            [
                'section_name' => 'Society 1',
                'quiz_title' => 'Quiz: Community Basics',
                'order_number' => 3,
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "teman"?',
                        'emoji' => '👫',
                        'options' => [
                            ['emoji' => '👨‍💼', 'label' => 'boss', 'correct' => false],
                            ['emoji' => '👫', 'label' => 'friend', 'correct' => true],
                            ['emoji' => '👨‍🏫', 'label' => 'teacher', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "neighbor"?',
                        'avatar' => '👩',
                        'options' => [
                            ['text' => 'teman', 'correct' => false],
                            ['text' => 'tetangga', 'correct' => true],
                            ['text' => 'musuh', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'I have many friends.',
                        'template' => 'She has many ___.',
                        'options' => [
                            ['text' => 'friends', 'correct' => true],
                            ['text' => 'friend', 'correct' => false],
                            ['text' => 'enemy', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Komunitas"?', 'options' => ['Community', 'Society', 'Group', 'Team'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Tetangga"?', 'options' => ['Friend', 'Neighbor', 'Stranger', 'Colleague'], 'correct' => 1],
                        ['question' => 'Apa bahasa Inggris dari "Pertemanan"?', 'options' => ['Friendship', 'Relationship', 'Partnership', 'Connection'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Bertemu"?', 'options' => ['Meet', 'See', 'Visit', 'Greet'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Bergaul"?', 'options' => ['Socialize', 'Meet', 'Talk', 'Speak'], 'correct' => 0],
                    ],
                ],
            ],
            [
                'section_name' => 'Society 2',
                'quiz_title' => 'Quiz: Social Skills',
                'order_number' => 7,
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "berbicara"?',
                        'emoji' => '🗣️',
                        'options' => [
                            ['emoji' => '🗣️', 'label' => 'speak', 'correct' => true],
                            ['emoji' => '👂', 'label' => 'listen', 'correct' => false],
                            ['emoji' => '👀', 'label' => 'watch', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "introduce"?',
                        'avatar' => '👨',
                        'options' => [
                            ['text' => 'memperkenalkan', 'correct' => true],
                            ['text' => 'mengenal', 'correct' => false],
                            ['text' => 'bertemu', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'We work together.',
                        'template' => 'They ___ together.',
                        'options' => [
                            ['text' => 'work', 'correct' => true],
                            ['text' => 'works', 'correct' => false],
                            ['text' => 'working', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Presentasi"?', 'options' => ['Presentation', 'Meeting', 'Discussion', 'Seminar'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Diskusi"?', 'options' => ['Talk', 'Discussion', 'Conversation', 'Chat'], 'correct' => 1],
                        ['question' => 'Apa bahasa Inggris dari "Kerja sama"?', 'options' => ['Cooperation', 'Competition', 'Collaboration', 'Teamwork'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Rapat"?', 'options' => ['Meeting', 'Discussion', 'Conference', 'Session'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Acara sosial"?', 'options' => ['Social event', 'Party', 'Gathering', 'Meeting'], 'correct' => 0],
                    ],
                ],
            ],
        ],
    ],
    7 => [ // English For Professional
        'course_title' => 'English For Professional',
        'sections' => [
            [
                'section_name' => 'Professional 1',
                'quiz_title' => 'Quiz: Business Basics',
                'order_number' => 3,
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "kantor"?',
                        'emoji' => '🏢',
                        'options' => [
                            ['emoji' => '🏠', 'label' => 'home', 'correct' => false],
                            ['emoji' => '🏢', 'label' => 'office', 'correct' => true],
                            ['emoji' => '🏫', 'label' => 'school', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "employee"?',
                        'avatar' => '👨‍',
                        'options' => [
                            ['text' => 'karyawan', 'correct' => true],
                            ['text' => 'bos', 'correct' => false],
                            ['text' => 'manajer', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'I work in an office.',
                        'template' => 'She ___ in a hospital.',
                        'options' => [
                            ['text' => 'works', 'correct' => true],
                            ['text' => 'work', 'correct' => false],
                            ['text' => 'working', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Karir"?', 'options' => ['Career', 'Job', 'Work', 'Position'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Rapat"?', 'options' => ['Meeting', 'Discussion', 'Conference', 'Session'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Email"?', 'options' => ['Email', 'Letter', 'Message', 'Mail'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Presentasi"?', 'options' => ['Presentation', 'Meeting', 'Discussion', 'Seminar'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Profesional"?', 'options' => ['Professional', 'Expert', 'Specialist', 'Worker'], 'correct' => 0],
                    ],
                ],
            ],
            [
                'section_name' => 'Professional 2',
                'quiz_title' => 'Quiz: Professional Skills',
                'order_number' => 7,
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "presentasi"?',
                        'emoji' => '📊',
                        'options' => [
                            ['emoji' => '📊', 'label' => 'presentation', 'correct' => true],
                            ['emoji' => '📝', 'label' => 'report', 'correct' => false],
                            ['emoji' => '💼', 'label' => 'briefcase', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "deadline"?',
                        'avatar' => '👨‍',
                        'options' => [
                            ['text' => 'batas waktu', 'correct' => true],
                            ['text' => 'jadwal', 'correct' => false],
                            ['text' => 'rencana', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'I write reports.',
                        'template' => 'She ___ emails.',
                        'options' => [
                            ['text' => 'writes', 'correct' => true],
                            ['text' => 'write', 'correct' => false],
                            ['text' => 'writing', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Negosiasi"?', 'options' => ['Negotiation', 'Discussion', 'Meeting', 'Talk'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Manajer"?', 'options' => ['Manager', 'Director', 'Supervisor', 'Leader'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Laporan"?', 'options' => ['Report', 'Document', 'File', 'Record'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Jadwal"?', 'options' => ['Schedule', 'Plan', 'Timetable', 'Agenda'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Target"?', 'options' => ['Target', 'Goal', 'Objective', 'Aim'], 'correct' => 0],
                    ],
                ],
            ],
        ],
    ],
    8 => [ // English For Family 2023 (Beta)
        'course_title' => 'English For Family 2023 (Beta)',
        'sections' => [
            [
                'section_name' => 'Family 1',
                'quiz_title' => 'Quiz: Family Words',
                'order_number' => 3,
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "adik"?',
                        'emoji' => '👶',
                        'options' => [
                            ['emoji' => '👨', 'label' => 'brother', 'correct' => false],
                            ['emoji' => '👩', 'label' => 'sister', 'correct' => false],
                            ['emoji' => '👶', 'label' => 'baby', 'correct' => true],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "grandfather"?',
                        'avatar' => '👨',
                        'options' => [
                            ['text' => 'kakek', 'correct' => true],
                            ['text' => 'nenek', 'correct' => false],
                            ['text' => 'paman', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'My brother is tall.',
                        'template' => 'My sister is ___.',
                        'options' => [
                            ['text' => 'short', 'correct' => true],
                            ['text' => 'tall', 'correct' => false],
                            ['text' => 'big', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Keluarga"?', 'options' => ['Family', 'Relative', 'Member', 'Household'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Orang tua"?', 'options' => ['Parent', 'Father', 'Mother', 'Guardian'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Saudara"?', 'options' => ['Sibling', 'Brother', 'Sister', 'Relative'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Paman"?', 'options' => ['Uncle', 'Aunt', 'Cousin', 'Nephew'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Bibi"?', 'options' => ['Aunt', 'Uncle', 'Cousin', 'Niece'], 'correct' => 0],
                    ],
                ],
            ],
            [
                'section_name' => 'Family 2',
                'quiz_title' => 'Quiz: Family Activities',
                'order_number' => 7,
                'questions' => [
                    'image' => [
                        'question' => 'Mana yang artinya "bermain"?',
                        'emoji' => '🎮',
                        'options' => [
                            ['emoji' => '🎮', 'label' => 'play', 'correct' => true],
                            ['emoji' => '📚', 'label' => 'study', 'correct' => false],
                            ['emoji' => '🏃', 'label' => 'run', 'correct' => false],
                        ],
                    ],
                    'translation' => [
                        'question' => 'Apa arti dari "celebrate"?',
                        'avatar' => '👩',
                        'options' => [
                            ['text' => 'merayakan', 'correct' => true],
                            ['text' => 'mengundang', 'correct' => false],
                            ['text' => 'memberi', 'correct' => false],
                        ],
                    ],
                    'fillblank' => [
                        'question' => 'Ikuti polanya',
                        'avatar' => '🐻',
                        'audio' => 'We play games.',
                        'template' => 'They ___ football.',
                        'options' => [
                            ['text' => 'play', 'correct' => true],
                            ['text' => 'plays', 'correct' => false],
                            ['text' => 'playing', 'correct' => false],
                        ],
                    ],
                    'text' => [
                        ['question' => 'Apa bahasa Inggris dari "Liburan"?', 'options' => ['Vacation', 'Holiday', 'Break', 'Trip'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Pesta"?', 'options' => ['Party', 'Celebration', 'Event', 'Gathering'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Makan malam"?', 'options' => ['Breakfast', 'Lunch', 'Dinner', 'Snack'], 'correct' => 2],
                        ['question' => 'Apa bahasa Inggris dari "Bersama"?', 'options' => ['Together', 'With', 'Along', 'Accompany'], 'correct' => 0],
                        ['question' => 'Apa bahasa Inggris dari "Kegiatan"?', 'options' => ['Activity', 'Action', 'Event', 'Task'], 'correct' => 0],
                    ],
                ],
            ],
        ],
    ],
];

$totalCreated = 0;

foreach ($courseQuizzes as $courseId => $courseData) {
    echo "\n📚 {$courseData['course_title']} (ID: {$courseId})\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($courseData['sections'] as $section) {
        // 1. Cek apakah meeting sudah ada
        $existingMeeting = DB::table('meetings')
            ->where('course_id', $courseId)
            ->where('order_number', $section['order_number'])
            ->where('title', 'LIKE', "%{$section['quiz_title']}%")
            ->first();
        
        if ($existingMeeting) {
            echo "   ⚠️  Quiz '{$section['quiz_title']}' sudah ada (ID: {$existingMeeting->quiz_id})\n";
            continue;
        }
        
        // 2. Buat meeting baru
        $meetingId = DB::table('meetings')->insertGetId([
            'course_id' => $courseId,
            'order_number' => $section['order_number'],
            'title' => $section['quiz_title'],
            'content' => null,
            'type' => 'test',
            'has_test' => 0,
            'is_final_test' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 3. Buat quiz baru
        $quizId = DB::table('quizzes')->insertGetId([
            'meeting_id' => $meetingId,
            'title' => $section['quiz_title'],
            'passing_score' => 70,
            'time_limit' => 60,
            'max_attempts' => 999,
            'shuffle_options' => 0,
            'show_results_immediately' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 4. Update meeting dengan quiz_id
        DB::table('meetings')->where('id', $meetingId)->update(['quiz_id' => $quizId]);
        
        // 5. Tambah soal Duolingo (order 1-3)
        $correctLabel = collect($section['questions']['image']['options'])->firstWhere('correct', true)['label'];
        DB::table('quiz_questions')->insert([
            'quiz_id' => $quizId,
            'question' => $section['questions']['image']['question'],
            'type' => 'multiple_choice',
            'question_type' => 'image',
            'image_emoji' => $section['questions']['image']['emoji'],
            'options' => json_encode($section['questions']['image']['options']),
            'correct_answer' => json_encode(['label' => $correctLabel]),
            'points' => 10,
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $correctText = collect($section['questions']['translation']['options'])->firstWhere('correct', true)['text'];
        DB::table('quiz_questions')->insert([
            'quiz_id' => $quizId,
            'question' => $section['questions']['translation']['question'],
            'type' => 'multiple_choice',
            'question_type' => 'translation',
            'character_avatar' => $section['questions']['translation']['avatar'],
            'options' => json_encode($section['questions']['translation']['options']),
            'correct_answer' => json_encode(['text' => $correctText]),
            'points' => 10,
            'order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $correctFillText = collect($section['questions']['fillblank']['options'])->firstWhere('correct', true)['text'];
        DB::table('quiz_questions')->insert([
            'quiz_id' => $quizId,
            'question' => $section['questions']['fillblank']['question'],
            'type' => 'multiple_choice',
            'question_type' => 'fillblank',
            'character_avatar' => $section['questions']['fillblank']['avatar'],
            'audio_text' => $section['questions']['fillblank']['audio'],
            'sentence_template' => $section['questions']['fillblank']['template'],
            'options' => json_encode($section['questions']['fillblank']['options']),
            'correct_answer' => json_encode(['text' => $correctFillText]),
            'points' => 10,
            'order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 6. Tambah soal text (order 100-104)
        foreach ($section['questions']['text'] as $index => $textQ) {
            $correctOption = $textQ['options'][$textQ['correct']];
            
            DB::table('quiz_questions')->insert([
                'quiz_id' => $quizId,
                'question' => $textQ['question'],
                'type' => 'multiple_choice',
                'question_type' => 'text',
                'options' => json_encode($textQ['options']),
                'correct_answer' => json_encode(['text' => $correctOption]),
                'points' => 10,
                'order' => 100 + $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $totalCreated++;
        echo "   ✅ {$section['quiz_title']} (Meeting: {$meetingId}, Quiz: {$quizId}) - 8 soal\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "🎉 SELESAI! Total {$totalCreated} quiz Duolingo-style berhasil dibuat!\n";
echo str_repeat("=", 80) . "\n\n";

// Verifikasi akhir
echo "📊 VERIFIKASI AKHIR:\n";
$quizzes = DB::table('quizzes')
    ->join('meetings', 'quizzes.meeting_id', '=', 'meetings.id')
    ->join('courses', 'meetings.course_id', '=', 'courses.id')
    ->select('quizzes.id as quiz_id', 'quizzes.title as quiz_title', 
             'courses.title as course_title', 'meetings.order_number')
    ->orderBy('courses.id')
    ->orderBy('meetings.order_number')
    ->get();

$currentCourse = '';
foreach ($quizzes as $quiz) {
    if ($quiz->course_title !== $currentCourse) {
        $currentCourse = $quiz->course_title;
        echo "\n📚 {$currentCourse}\n";
    }
    
    $total = DB::table('quiz_questions')->where('quiz_id', $quiz->quiz_id)->count();
    $duoCount = DB::table('quiz_questions')
        ->where('quiz_id', $quiz->quiz_id)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->count();
    
    $marker = ($duoCount >= 3) ? '🎨' : '📝';
    echo "   {$marker} Order {$quiz->order_number}: {$quiz->quiz_title} ({$total} soal, {$duoCount} Duolingo)\n";
}

echo "\n✅ Done!\n";