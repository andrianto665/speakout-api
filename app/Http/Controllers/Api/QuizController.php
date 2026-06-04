<?php
/**
 * Quiz Controller
 * 
 * Handles quiz display and submission for users.
 * 
 * @package App\Http\Controllers\Api
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * GET: Ambil soal kuis (tanpa kunci jawaban) - DEBUG VERSION
     * 
     * Endpoint: GET /api/quizzes/{quizId}
     * 
     * @param  int  $quizId
     * @return JsonResponse
     */
    public function show($quizId): JsonResponse
{
    try {
        Log::info('🔍 QuizController@show called', [
            'requested_quiz_id' => $quizId,
            'user_id' => Auth::id(),
        ]);

        // ✅ Load quiz dengan relationships - PERBAIKAN
        $quiz = Quiz::with([
            'questions' => function ($query) {
                $query->orderBy('order', 'asc')
                      ->orderBy('id', 'asc'); // ✅ Secondary sort by ID
            },
            'meeting',
            'meeting.course'
        ])->findOrFail($quizId);

        // ✅ Parse options: JSON string → array
        foreach ($quiz->questions as $q) {
            if (is_string($q->options ?? null)) {
                $decoded = @json_decode($q->options, true);
                if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                    $q->options = $decoded;
                }
            }
            // ✅ Hapus correct_answer dari response (security)
            unset($q->correct_answer);
        }

        // ✅ Tambahkan info course dan meeting ke response
        $responseData = $quiz->toArray();
        $responseData['course_id'] = $quiz->meeting?->course_id;
        $responseData['course_title'] = $quiz->meeting?->course?->title;
        $responseData['total_questions'] = $quiz->questions->count();

        Log::info('📤 Quiz loaded successfully', [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'course_id' => $responseData['course_id'],
            'questions_count' => $responseData['total_questions'],
        ]);

        return response()->json($responseData);
        
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Quiz not found',
            'debug' => config('app.debug') ? 'Quiz ID ' . $quizId . ' does not exist' : null
        ], 404);
        
    } catch (\Throwable $e) {
        Log::error('❌ QuizController@show error', [
            'quiz_id' => $quizId,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'message' => 'Failed to load quiz',
            'debug' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    /**
     * POST: Submit jawaban → Auto-Grade → Update Progress → Auto-Check Certificate
     * 
     * Endpoint: POST /api/quizzes/{quizId}/submit
     * 
     * @param  Request  $request
     * @param  int  $quizId
     * @return JsonResponse
     */
    public function submit(Request $request, $quizId): JsonResponse
    {
        try {
            // 🔍 DEBUG: Cek authentication & headers
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

            // 7. Simpan attempt
            $previousAttempts = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count();
            
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $score,
                'passed' => $passed,
                'answers' => json_encode($answers),
                'attempt_number' => $previousAttempts + 1,
            ]);

            // 8. Update progress
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

            // ✅ AUTO-GENERATE CERTIFICATE CHECK
            $courseId = $quiz->meeting->course_id ?? null;
            if ($courseId) {
                $this->checkAndGenerateCertificate($user->id, $courseId);
            }

            // 9. Return hasil
            return response()->json([
                'success' => true,
                'message' => $passed 
                    ? '🎉 Selamat! Kamu lulus kuis ini.' 
                    : '❌ Skor belum memenuhi batas kelulusan. Coba lagi!',
                'score' => $score,
                'passed' => $passed,
                'passing_score' => $passingScore,
                'course_id' => $courseId,
                'meeting_id' => $meetingId,
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

    /**
 * HELPER: Auto-Generate Certificate setelah course completed
 */
private function checkAndGenerateCertificate(int $userId, int $courseId): void
{
    try {
        // 1. Get all lesson IDs for this course
        $lessonIds = Meeting::where('course_id', $courseId)
            ->where(function($q) {
                $q->whereNotNull('content')
                  ->orWhereIn('type', ['quiz', 'final', 'test'])
                  ->orWhere('has_test', 1)
                  ->orWhere('is_final_test', 1);
            })
            ->pluck('id')
            ->toArray();
        
        if (empty($lessonIds)) {
            Log::warning('⚠️ No lessons found for course', ['course_id' => $courseId]);
            return;
        }
        
        // 2. Get completed lesson IDs for this user
        $completedIds = UserProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessonIds)
            ->pluck('meeting_id')
            ->toArray();
        
        // 3. Check if ALL lessons completed
        $totalLessons = count($lessonIds);
        $completedLessons = count($completedIds);
        
        Log::info('🔍 Certificate check', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
        ]);
        
        if ($completedLessons >= $totalLessons) {
            
            // 4. Update enrollment completed_at
            Enrollment::updateOrCreate(
                ['user_id' => $userId, 'course_id' => $courseId],
                ['completed_at' => now()]
            );
            
            // 5. ✅ PERBAIKAN: Generate certificate record if not exists
            $certExists = Certificate::where('user_id', $userId)
                ->where('course_id', $courseId)  // ✅ Pakai koma, bukan =>
                ->exists();
            
            if (!$certExists) {
                Certificate::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'certificate_number' => Certificate::generateCertificateNumber(),
                    'file_path' => 'generated_on_demand.pdf',
                    'verification_code' => Certificate::generateVerificationCode(),
                    'issued_at' => now(),
                ]);
                
                Log::info("🎉 Certificate auto-generated", [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ]);
            }
        }
        
    } catch (\Exception $e) {
        Log::error("❌ Auto-certificate check failed", [
            'user_id' => $userId,
            'course_id' => $courseId,
            'error' => $e->getMessage(),
        ]);
    }
}
}