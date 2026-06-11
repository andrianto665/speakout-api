<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🎨 Menambahkan soal Duolingo baru ke quiz...\n\n";

// Data soal tambahan untuk setiap quiz Duolingo
$additionalQuestions = [
    17 => [ // Quiz: Family Basics
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "kakak laki-laki"?',
            'emoji' => '👦',
            'options' => [
                ['emoji' => '👧', 'label' => 'sister', 'correct' => false],
                ['emoji' => '👦', 'label' => 'brother', 'correct' => true],
                ['emoji' => '👴', 'label' => 'grandfather', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "sibling"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'saudara kandung', 'correct' => true],
                ['text' => 'teman', 'correct' => false],
                ['text' => 'tetangga', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'My sister is a girl.',
            'template' => 'My brother is a ___.',
            'options' => [
                ['text' => 'boy', 'correct' => true],
                ['text' => 'girl', 'correct' => false],
                ['text' => 'baby', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "paman"?',
            'emoji' => '👨',
            'options' => [
                ['emoji' => '👩', 'label' => 'aunt', 'correct' => false],
                ['emoji' => '👨', 'label' => 'uncle', 'correct' => true],
                ['emoji' => '👵', 'label' => 'grandmother', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "cousin"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'sepupu', 'correct' => true],
                ['text' => 'keponakan', 'correct' => false],
                ['text' => 'paman', 'correct' => false],
            ],
        ],
    ],
    18 => [ // Quiz: Daily Activities
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "tidur"?',
            'emoji' => '😴',
            'options' => [
                ['emoji' => '😴', 'label' => 'sleep', 'correct' => true],
                ['emoji' => '🍽️', 'label' => 'eat', 'correct' => false],
                ['emoji' => '📚', 'label' => 'study', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "breakfast"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'sarapan', 'correct' => true],
                ['text' => 'makan siang', 'correct' => false],
                ['text' => 'makan malam', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'I wake up early.',
            'template' => 'She ___ up late.',
            'options' => [
                ['text' => 'wakes', 'correct' => true],
                ['text' => 'wake', 'correct' => false],
                ['text' => 'waking', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "mandi"?',
            'emoji' => '🚿',
            'options' => [
                ['emoji' => '🚿', 'label' => 'shower', 'correct' => true],
                ['emoji' => '🏃', 'label' => 'run', 'correct' => false],
                ['emoji' => '🍳', 'label' => 'cook', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "evening"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'sore/malam', 'correct' => true],
                ['text' => 'pagi', 'correct' => false],
                ['text' => 'siang', 'correct' => false],
            ],
        ],
    ],
    19 => [ // Quiz: Community Basics
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "guru"?',
            'emoji' => '👨‍🏫',
            'options' => [
                ['emoji' => '👨‍', 'label' => 'doctor', 'correct' => false],
                ['emoji' => '👨‍🏫', 'label' => 'teacher', 'correct' => true],
                ['emoji' => '👮', 'label' => 'police', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "citizen"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'warga negara', 'correct' => true],
                ['text' => 'turis', 'correct' => false],
                ['text' => 'pendatang', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'We live in a community.',
            'template' => 'They ___ in a village.',
            'options' => [
                ['text' => 'live', 'correct' => true],
                ['text' => 'lives', 'correct' => false],
                ['text' => 'living', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "dokter"?',
            'emoji' => '👨‍',
            'options' => [
                ['emoji' => '👨‍', 'label' => 'doctor', 'correct' => true],
                ['emoji' => '👨‍🍳', 'label' => 'chef', 'correct' => false],
                ['emoji' => '👨‍🔧', 'label' => 'mechanic', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "village"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'desa', 'correct' => true],
                ['text' => 'kota', 'correct' => false],
                ['text' => 'negara', 'correct' => false],
            ],
        ],
    ],
    20 => [ // Quiz: Social Skills
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "mendengar"?',
            'emoji' => '👂',
            'options' => [
                ['emoji' => '👁️', 'label' => 'see', 'correct' => false],
                ['emoji' => '👂', 'label' => 'listen', 'correct' => true],
                ['emoji' => '👃', 'label' => 'smell', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "conversation"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'percakapan', 'correct' => true],
                ['text' => 'presentasi', 'correct' => false],
                ['text' => 'pidato', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'I speak English.',
            'template' => 'She ___ French.',
            'options' => [
                ['text' => 'speaks', 'correct' => true],
                ['text' => 'speak', 'correct' => false],
                ['text' => 'speaking', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "melihat"?',
            'emoji' => '👁️',
            'options' => [
                ['emoji' => '👁️', 'label' => 'see', 'correct' => true],
                ['emoji' => '👂', 'label' => 'hear', 'correct' => false],
                ['emoji' => '👄', 'label' => 'talk', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "greeting"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'salam', 'correct' => true],
                ['text' => 'pertanyaan', 'correct' => false],
                ['text' => 'jawaban', 'correct' => false],
            ],
        ],
    ],
    21 => [ // Quiz: Business Basics
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "komputer"?',
            'emoji' => '💻',
            'options' => [
                ['emoji' => '📱', 'label' => 'phone', 'correct' => false],
                ['emoji' => '💻', 'label' => 'computer', 'correct' => true],
                ['emoji' => '📺', 'label' => 'television', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "salary"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'gaji', 'correct' => true],
                ['text' => 'bonus', 'correct' => false],
                ['text' => 'tunjangan', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'I attend meetings.',
            'template' => 'She ___ conferences.',
            'options' => [
                ['text' => 'attends', 'correct' => true],
                ['text' => 'attend', 'correct' => false],
                ['text' => 'attending', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "telepon"?',
            'emoji' => '📱',
            'options' => [
                ['emoji' => '📱', 'label' => 'phone', 'correct' => true],
                ['emoji' => '💻', 'label' => 'laptop', 'correct' => false],
                ['emoji' => '⌚', 'label' => 'watch', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "promotion"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'promosi/kenaikan', 'correct' => true],
                ['text' => 'pemecatan', 'correct' => false],
                ['text' => 'mutasi', 'correct' => false],
            ],
        ],
    ],
    22 => [ // Quiz: Professional Skills
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "dokumen"?',
            'emoji' => '📄',
            'options' => [
                ['emoji' => '📄', 'label' => 'document', 'correct' => true],
                ['emoji' => '📊', 'label' => 'chart', 'correct' => false],
                ['emoji' => '📈', 'label' => 'graph', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "project"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'proyek', 'correct' => true],
                ['text' => 'liburan', 'correct' => false],
                ['text' => 'rapat', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'I manage a team.',
            'template' => 'She ___ a department.',
            'options' => [
                ['text' => 'manages', 'correct' => true],
                ['text' => 'manage', 'correct' => false],
                ['text' => 'managing', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "buku catatan"?',
            'emoji' => '📓',
            'options' => [
                ['emoji' => '📓', 'label' => 'notebook', 'correct' => true],
                ['emoji' => '📕', 'label' => 'book', 'correct' => false],
                ['emoji' => '📰', 'label' => 'newspaper', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "resume"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'daftar riwayat hidup', 'correct' => true],
                ['text' => 'surat lamaran', 'correct' => false],
                ['text' => 'kontrak kerja', 'correct' => false],
            ],
        ],
    ],
    23 => [ // Quiz: Family Words (2023)
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "bibi"?',
            'emoji' => '👩',
            'options' => [
                ['emoji' => '👩', 'label' => 'aunt', 'correct' => true],
                ['emoji' => '👨', 'label' => 'uncle', 'correct' => false],
                ['emoji' => '👵', 'label' => 'grandmother', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "nephew"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'keponakan laki-laki', 'correct' => true],
                ['text' => 'keponakan perempuan', 'correct' => false],
                ['text' => 'sepupu', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'My uncle is tall.',
            'template' => 'My aunt is ___.',
            'options' => [
                ['text' => 'short', 'correct' => true],
                ['text' => 'tall', 'correct' => false],
                ['text' => 'big', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "keponakan"?',
            'emoji' => '🧒',
            'options' => [
                ['emoji' => '🧒', 'label' => 'nephew', 'correct' => true],
                ['emoji' => '👨', 'label' => 'father', 'correct' => false],
                ['emoji' => '👩', 'label' => 'mother', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "niece"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'keponakan perempuan', 'correct' => true],
                ['text' => 'keponakan laki-laki', 'correct' => false],
                ['text' => 'anak perempuan', 'correct' => false],
            ],
        ],
    ],
    24 => [ // Quiz: Family Activities (2023)
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "memasak"?',
            'emoji' => '🍳',
            'options' => [
                ['emoji' => '🍳', 'label' => 'cook', 'correct' => true],
                ['emoji' => '📖', 'label' => 'read', 'correct' => false],
                ['emoji' => '🎵', 'label' => 'sing', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "together"?',
            'avatar' => '👩',
            'options' => [
                ['text' => 'bersama-sama', 'correct' => true],
                ['text' => 'sendirian', 'correct' => false],
                ['text' => 'terpisah', 'correct' => false],
            ],
        ],
        [
            'type' => 'fillblank',
            'question' => 'Lengkapi kalimatnya',
            'avatar' => '🐻',
            'audio' => 'We eat dinner together.',
            'template' => 'They ___ lunch together.',
            'options' => [
                ['text' => 'eat', 'correct' => true],
                ['text' => 'eats', 'correct' => false],
                ['text' => 'eating', 'correct' => false],
            ],
        ],
        [
            'type' => 'image',
            'question' => 'Mana yang artinya "membaca"?',
            'emoji' => '📖',
            'options' => [
                ['emoji' => '📖', 'label' => 'read', 'correct' => true],
                ['emoji' => '✍️', 'label' => 'write', 'correct' => false],
                ['emoji' => '🎨', 'label' => 'draw', 'correct' => false],
            ],
        ],
        [
            'type' => 'translation',
            'question' => 'Apa arti dari "celebration"?',
            'avatar' => '👨',
            'options' => [
                ['text' => 'perayaan', 'correct' => true],
                ['text' => 'pertandingan', 'correct' => false],
                ['text' => 'pertemuan', 'correct' => false],
            ],
        ],
    ],
];

$totalAdded = 0;

foreach ($additionalQuestions as $quizId => $questions) {
    echo "📝 Quiz ID: {$quizId}\n";
    
    // Get current max order
    $maxOrder = DB::table('quiz_questions')
        ->where('quiz_id', $quizId)
        ->max('order') ?? 0;
    
    $currentOrder = $maxOrder + 1;
    
    foreach ($questions as $q) {
        $data = [
            'quiz_id' => $quizId,
            'question' => $q['question'],
            'type' => 'multiple_choice',
            'question_type' => $q['type'],
            'points' => 10,
            'order' => $currentOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        if ($q['type'] === 'image') {
            $data['image_emoji'] = $q['emoji'];
            $data['options'] = json_encode($q['options']);
            $correctLabel = collect($q['options'])->firstWhere('correct', true)['label'];
            $data['correct_answer'] = json_encode(['label' => $correctLabel]);
        } 
        elseif ($q['type'] === 'translation') {
            $data['character_avatar'] = $q['avatar'];
            $data['options'] = json_encode($q['options']);
            $correctText = collect($q['options'])->firstWhere('correct', true)['text'];
            $data['correct_answer'] = json_encode(['text' => $correctText]);
        } 
        elseif ($q['type'] === 'fillblank') {
            $data['character_avatar'] = $q['avatar'];
            $data['audio_text'] = $q['audio'];
            $data['sentence_template'] = $q['template'];
            $data['options'] = json_encode($q['options']);
            $correctText = collect($q['options'])->firstWhere('correct', true)['text'];
            $data['correct_answer'] = json_encode(['text' => $correctText]);
        }
        
        DB::table('quiz_questions')->insert($data);
        $currentOrder++;
        $totalAdded++;
    }
    
    echo "   ✅ Tambah 5 soal Duolingo baru\n";
}

echo "\n🎉 SELESAI! Total {$totalAdded} soal Duolingo baru ditambahkan!\n\n";

// Verifikasi
echo "📊 VERIFIKASI:\n";
echo str_repeat("=", 80) . "\n";

foreach (array_keys($additionalQuestions) as $quizId) {
    $quiz = DB::table('quizzes')->where('id', $quizId)->first();
    $total = DB::table('quiz_questions')->where('quiz_id', $quizId)->count();
    $duoCount = DB::table('quiz_questions')
        ->where('quiz_id', $quizId)
        ->whereIn('question_type', ['image', 'translation', 'fillblank'])
        ->count();
    
    echo "\n🎨 Quiz ID {$quizId}: {$quiz->title}\n";
    echo "   Total soal: {$total} (Duolingo: {$duoCount})\n";
}

echo "\n✅ Done!\n";