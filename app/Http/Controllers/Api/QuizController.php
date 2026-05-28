<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * GET: Ambil soal kuis (tanpa kunci jawaban)
     */
    public function show($quizId): JsonResponse
    {
        try {
            $quiz = Quiz::with(['questions' => function ($query) {
                $query->select('id', 'quiz_id', 'question', 'type', 'options', 'points', 'order');
            }])->findOrFail($quizId);

            // Parse options safely dari JSON string ke array
            foreach ($quiz->questions as $q) {
                if (is_string($q->options ?? null)) {
                    $decoded = @json_decode($q->options, true);
                    if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                        $q->options = $decoded;
                    }
                }
            }

            return response()->json($quiz);
        } catch (\Throwable $e) {
            Log::error('Quiz show error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load quiz'], 500);
        }
    }

    /**
     * POST: Submit jawaban → Auto-Grade → Update Progress
     */
    public function submit(Request $request, $quizId): JsonResponse
    {
        try {
            // 🔍 DEBUG LOG: Cek authentication & headers
            $user = Auth::user();
            $hasAuthHeader = $request->headers->has('Authorization');
            $authHeader = $request->headers->get('Authorization');
            
            Log::info('Quiz submit debug', [
                'quiz_id' => $quizId,
                'has_auth_header' => $hasAuthHeader,
                'auth_header_preview' => $authHeader ? substr($authHeader, 0, 30) . '...' : null,
                'auth_user' => $user ? ['id' => $user->id, 'name' => $user->name] : null,
            ]);
            
            // 1. Cek authentication
            if (!$user || !isset($user->id)) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // 2. Cek quiz ada
            $quiz = Quiz::find($quizId);
            if (!$quiz || !isset($quiz->id)) {
                return response()->json(['message' => 'Quiz not found'], 404);
            }

            // 3. Validasi answers
            $answers = $request->input('answers', []);
            if (!is_array($answers)) {
                return response()->json(['message' => 'Invalid answers format'], 400);
            }

            // 4. Cek batas attempt
            $maxAttempts = $quiz->max_attempts ?? 0;
            if ($maxAttempts > 0) {
                $count = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->count();
                if ($count >= $maxAttempts) {
                    return response()->json(['message' => 'Max attempts reached'], 403);
                }
            }

            // 5. Ambil soal untuk grading
            $questions = QuizQuestion::where('quiz_id', $quiz->id)->get();
            if ($questions->isEmpty()) {
                return response()->json(['message' => 'No questions found', 'score' => 0, 'passed' => false], 400);
            }

            // 6. Grading - ULTRA SAFE dengan null checks
            $totalPoints = 0;
            $earnedPoints = 0;

            foreach ($questions as $q) {
                $qid = $q->id ?? null;
                $correct = $q->correct_answer ?? null;
                $points = $q->points ?? 1;

                if ($qid === null) continue; // Skip invalid question

                $totalPoints += $points;
                $userAns = $answers[$qid] ?? null;

                if ($userAns === $correct) {
                    $earnedPoints += $points;
                }
            }

            $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
            $passingScore = $quiz->passing_score ?? 70;
            $passed = $score >= $passingScore;

            // 7. Simpan attempt - ✅ FIX: Hitung attempt_number dengan lebih aman
            $previousAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count();
            
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $score,
                'passed' => $passed,
                'answers' => json_encode($answers), // ✅ Encode ke JSON string untuk konsistensi
                'attempt_number' => $previousAttempts + 1,
            ]);

            // 8. Update progress - PAKAI meeting_id LANGSUNG (paling aman!)
                    // 8. Update progress - DEBUG VERSION
        $meetingId = $quiz->meeting_id ?? null;
        
        // Fallback ambil dari DB jika model belum load
        if (!$meetingId) {
            $meetingId = DB::table('quizzes')->where('id', $quiz->id)->value('meeting_id');
        }

        Log::info('🔍 DEBUG: Progress Save Check', [
            'passed' => $passed,
            'meeting_id' => $meetingId,
            'user_id' => $user->id,
        ]);

        if ($passed && $meetingId) {
            DB::table('user_progress')->updateOrInsert(
                ['user_id' => $user->id, 'meeting_id' => $meetingId],
                [
                    'is_completed' => 1,
                    'completed_at' => now(),
                    'updated_at' => now()
                ]
            );
            Log::info('✅ SUCCESS: Progress saved to DB');
        } else {
            Log::warning('⚠️ SKIPPED: Progress save skipped (passed=' . ($passed ? 'true' : 'false') . ', meetingId=' . ($meetingId ?? 'null') . ')');
        }

            // 9. Return hasil - ✅ Tambah field course_id untuk frontend
            return response()->json([
                'success' => true, // ✅ Tambah field ini agar frontend mudah cek
                'message' => $passed 
                    ? '🎉 Selamat! Kamu lulus kuis ini.' 
                    : '❌ Skor belum memenuhi batas kelulusan. Coba lagi!',
                'score' => $score,
                'passed' => $passed,
                'passing_score' => $passingScore,
                'course_id' => $quiz->meeting->course_id ?? null, // ✅ Tambah untuk frontend
                'meeting_id' => $meetingId, // ✅ Tambah untuk frontend
                'attempt' => [
                    'id' => $attempt->id ?? null,
                    'attempt_number' => $attempt->attempt_number ?? 1,
                    'score' => $attempt->score ?? 0,
                    'passed' => $attempt->passed ?? false,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Quiz submit error: ' . $e->getMessage() . ' at line ' . $e->getLine(), [
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error processing quiz',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}