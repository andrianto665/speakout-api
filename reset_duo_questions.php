<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🗑️  Menghapus semua soal Duolingo-style lama...\n\n";

// Hapus semua soal Duolingo-style (image, translation, fillblank)
$deleted = DB::table('quiz_questions')
    ->whereIn('question_type', ['image', 'translation', 'fillblank'])
    ->delete();

echo "✅ Berhasil menghapus {$deleted} soal Duolingo-style\n";

// Verifikasi
$remaining = DB::table('quiz_questions')
    ->whereIn('question_type', ['image', 'translation', 'fillblank'])
    ->count();

echo "📊 Sisa soal Duolingo-style: {$remaining}\n";