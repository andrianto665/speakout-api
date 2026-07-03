<?php
/**
 * Quiz Controller
 * 
 * Handles quiz display and submission for users.
 * Auto-generates certificate (status: pending) when user completes course.
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
     * GET: Ambil soal kuis (tanpa kunci jawaban)
     * 
     * Endpoint: GET /api/quizzes/{quizId}
     */
    public function show($quizId): JsonResponse
    {
        try {
            Log::info('🔍 QuizController@show called', [
                'requested_quiz_id' => $quizId,
                'user_id' => Auth::id(),
            ]);

            // Load quiz dengan relationships
            $quiz = Quiz::with([
                'questions' => function ($query) {
                    $query->orderBy('order', 'asc')
                          ->orderBy('id', 'asc');
                },
                'meeting',
                'meeting.course'
            ])->findOrFail($quizId);

            // ✅ CEK LOCKING SEQUENTIAL
            if ($quiz->meeting && \App\Services\MeetingLockService::isMeetingLocked(Auth::id(), $quiz->meeting)) {
                return response()->json([
                    'message' => 'Selesaikan pertemuan sebelumnya terlebih dahulu.'
                ], 403);
            }

            // Parse options: JSON string → array & hapus correct_answer (security)
            foreach ($quiz->questions as $q) {
                if (is_string($q->options ?? null)) {
                    $decoded = @json_decode($q->options, true);
                    if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
                        $q->options = $decoded;
                    }
                }
                unset($q->correct_answer);
            }

            // Tambahkan info course dan meeting ke response
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
     */
    public function submit(Request $request, $quizId): JsonResponse
{
    try {
        $user = Auth::user();
        
        if (!$user || !isset($user->id)) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $quiz = Quiz::with('meeting')->find($quizId);
        if (!$quiz || !isset($quiz->id)) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }
        // ✅ CEK LOCKING SEQUENTIAL
        if ($quiz->meeting && \App\Services\MeetingLockService::isMeetingLocked($user->id, $quiz->meeting)) {
            return response()->json(['message' => 'Meeting ini masih terkunci.'], 403);
        }

        $answers = $request->input('answers', []);
        if (!is_array($answers)) {
            return response()->json(['message' => 'Invalid answers format'], 400);
        }

        // Check max attempts
        $maxAttempts = $quiz->max_attempts ?? 0;
        if ($maxAttempts > 0) {
            $count = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->count();
            if ($count >= $maxAttempts) {
                return response()->json(['message' => 'Max attempts reached'], 403);
            }
        }

        // Get questions for grading
        $questions = QuizQuestion::where('quiz_id', $quiz->id)->get();
        if ($questions->isEmpty()) {
            return response()->json([
                'message' => 'No questions found', 
                'score' => 0, 
                'passed' => false
            ], 400);
        }

        // ✅ IMPROVED GRADING LOGIC
        $totalPoints = 0;
        $earnedPoints = 0;
        
        foreach ($questions as $q) {
            $qid = $q->id ?? null;
            $correct = $q->correct_answer ?? null;
            $points = $q->points ?? 1;

            if ($qid === null) continue;

            $totalPoints += $points;
            $userAns = $answers[$qid] ?? null;

            if ($userAns === null) continue;

            // ✅ Normalize correct_answer: handle double-encoded JSON string & object string
            if (is_string($correct)) {
                $maybeDecoded = json_decode($correct, true);
                if ($maybeDecoded !== null && json_last_error() === JSON_ERROR_NONE) {
                    $correct = $maybeDecoded;
                }
            }

            // ✅ Normalize user answer to string
            $userValue = '';
            if (is_string($userAns)) {
                $userValue = $userAns;
            } elseif (is_array($userAns)) {
                $userValue = $userAns['label'] ?? $userAns['text'] ?? '';
            }

            // ✅ Normalize correct answer to string
            $correctValue = '';
            if (is_string($correct)) {
                $correctValue = $correct;
            } elseif (is_array($correct)) {
                $correctValue = $correct['label'] ?? $correct['text'] ?? '';
            }

            // ✅ For text legacy: map letter (A/B/C/D) to option value — apply to BOTH user & correct answer
$options = is_string($q->options) ? json_decode($q->options, true) : $q->options;

if ($q->question_type === 'text' && is_array($options)) {
    if (is_string($userAns) && strlen($userAns) === 1 && ctype_alpha($userAns)) {
        $letterIndex = ord(strtoupper($userAns)) - 65; // A=0, B=1, C=2, D=3
        if (isset($options[$letterIndex])) {
            $userValue = $options[$letterIndex];
        }
    }

    if (is_string($correctValue) && strlen($correctValue) === 1 && ctype_alpha($correctValue)) {
        $letterIndex = ord(strtoupper($correctValue)) - 65;
        if (isset($options[$letterIndex])) {
            $correctValue = $options[$letterIndex];
        }
    }
}

            // ✅ Compare (case-insensitive, trim whitespace)
            if (strtolower(trim($userValue)) === strtolower(trim($correctValue))) {
                $earnedPoints += $points;
            }
        }

        $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $passingScore = $quiz->passing_score ?? 70;
        $passed = $score >= $passingScore;

        // Save attempt
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

        // Update progress if passed
        $meetingId = $quiz->meeting_id ?? null;
        
        if (!$meetingId) {
            $meetingId = DB::table('quizzes')->where('id', $quiz->id)->value('meeting_id');
        }

        if ($passed && $meetingId) {
            DB::table('user_progress')->updateOrInsert(
                ['user_id' => $user->id, 'meeting_id' => $meetingId],
                [
                    'is_completed' => 1,
                    'completed_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Auto-generate certificate if course completed
        $courseId = $quiz->meeting?->course_id;
        if ($courseId) {
            $this->checkAndGenerateCertificate($user->id, $courseId);
        }

        return response()->json([
            'success' => true,
            'message' => $passed 
                ? '🎉 Selamat! Kamu lulus kuis ini.' 
                : '❌ Skor belum memenuhi batas kelulusan. Coba lagi!',
            'score' => $score,
            'passed' => $passed,
            'passing_score' => $passingScore,
            'earned_points' => $earnedPoints,
            'total_points' => $totalPoints,
            'course_id' => $courseId,
            'meeting_id' => $meetingId,
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->score,
                'passed' => $attempt->passed,
            ],
        ]);

    } catch (\Throwable $e) {
        Log::error('❌ Quiz submit error', [
            'quiz_id' => $quizId,
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Server error processing quiz',
            'debug' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    /**
     * ✅ HELPER: Auto-Generate Certificate dengan status PENDING
     * 
     * Dipanggil setelah user complete course (100% progress)
     * Certificate dibuat dengan status 'pending' untuk di-approve admin
     * 
     * @param int $userId
     * @param int $courseId
     * @return void
     */
    private function checkAndGenerateCertificate(int $userId, int $courseId): void
    {
        try {
            // 1. Get all lesson IDs for this course
            $lessonIds = Meeting::where('course_id', $courseId)
                ->get()
                ->filter(fn($m) => $m->isLesson())
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
                // ✅ Pastikan SEMUA quiz di course ini sudah passed (bukan cuma type='final')
                $meetingsWithQuiz = Meeting::where('course_id', $courseId)
                    ->whereHas('quiz')
                    ->with('quiz')
                    ->get();

                foreach ($meetingsWithQuiz as $m) {
                    $q = $m->quiz;
                    if (!$q) continue;

                    $passed = QuizAttempt::where('user_id', $userId)
                        ->where('quiz_id', $q->id)
                        ->where('passed', 1)
                        ->exists();

                    if (!$passed) {
                        Log::info('⏸️ Certificate ditahan, ada quiz yang belum passed', [
                            'user_id' => $userId,
                            'course_id' => $courseId,
                            'unpassed_quiz' => $q->title ?? $m->title,
                        ]);
                        return;
                    }
                }
                Log::info('✅ Course 100% completed - generating certificate', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ]);
                
                // 4. ✅ AUTO-GENERATE CERTIFICATE dengan status PENDING
                // Menggunakan method getOrCreate() untuk mencegah duplikat
                $certificate = Certificate::getOrCreate($userId, $courseId);
                
                // 5. Update enrollment completed_at
                Enrollment::updateOrCreate(
                    ['user_id' => $userId, 'course_id' => $courseId],
                    ['completed_at' => now()]
                );
                
                Log::info("🎉 Certificate auto-generated (status: {$certificate->status})", [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'certificate_id' => $id,
                    'certificate_number' => $certificate->certificate_number,
                ]);
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