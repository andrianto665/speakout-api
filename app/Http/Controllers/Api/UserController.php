<?php
/**
 * User Controller - Handle user dashboard, enrollments & progress
 * 
 * @package App\Http\Controllers\Api
 * @author SpeakOut Team
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get courses that user is enrolled in (with progress calculation)
     * 
     * GET /api/user/enrolled-courses
     */
    public function getEnrolledCourses(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $enrolledCourses = $user->enrolledCourses()
                ->with(['meetings' => function ($query) {
                    $query->orderBy('order_number');
                }])
                ->get()
                ->map(fn ($course) => $this->formatCourseWithProgress($course, $user));
            
            return response()->json($enrolledCourses);
            
        } catch (\Exception $e) {
            Log::error('UserController@getEnrolledCourses: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to load enrolled courses',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Enroll user in a course
     * 
     * POST /api/user/enroll/{courseId}
     */
    public function enroll(Request $request, int $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            $course = Course::findOrFail($courseId);
            
            $existing = Enrollment::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->first();
            
            if ($existing) {
                return response()->json([
                    'message' => 'Already enrolled',
                    'enrolled_at' => $existing->enrolled_at,
                    'course' => $course
                ], 200);
            }
            
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'enrolled_at' => now()
            ]);
            
            return response()->json([
                'message' => 'Successfully enrolled',
                'course' => $course,
                'enrolled_at' => $enrollment->enrolled_at
            ], 201);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Course not found'], 404);
        } catch (\Exception $e) {
            Log::error('UserController@enroll: ' . $e->getMessage());
            return response()->json([
                'message' => 'Enrollment failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Get dashboard summary
     * 
     * GET /api/user/dashboard
     */
    public function getDashboardSummary(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'stats' => $this->getUserStats($user),
                'in_progress_courses' => $this->getInProgressCourses($user),
                'available_courses' => $this->getAvailableCourses($user)
            ]);
            
        } catch (\Exception $e) {
            Log::error('UserController@getDashboardSummary: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================
    
    /**
     * ✅ ROBUST: Filter lessons - include content OR quiz/test/final types
     */
    private function isLesson($meeting): bool
    {
        if ($meeting->content !== null && $meeting->content !== '') {
            return true;
        }
        
        $type = strtolower($meeting->type ?? '');
        if (in_array($type, ['quiz', 'final', 'test', 'quiz_assessment', 'assessment'])) {
            return true;
        }
        
        if (!empty($meeting->has_test) || !empty($meeting->is_final_test)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Format course data with progress calculation
     * ✅ FIXED: Added category, level, duration
     */
    private function formatCourseWithProgress($course, $user): array
    {
        $meetings = $course->meetings;
        $lessons = $meetings->filter(fn($m) => $this->isLesson($m));
        
        $lessonIds = $lessons->pluck('id');
        $completedIds = UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessonIds)
            ->pluck('meeting_id');
        
        $total = $lessons->count();
        $completed = $lessons->filter(fn($m) => $completedIds->contains($m->id))->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        $nextLesson = $lessons->first(fn($m) => !$completedIds->contains($m->id)) ?? $lessons->last();
        
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'instructor' => $course->instructor,
            'thumbnail' => $course->thumbnail,
            'category' => $course->category,       // ✅ NEW
            'level' => $course->level,             // ✅ NEW
            'duration' => $course->duration,       // ✅ NEW
            'enrolled_at' => $course->pivot->enrolled_at,
            'progress' => $progress,
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'is_completed' => (int) $progress === 100,
            'last_lesson' => $nextLesson ? [
                'id' => $nextLesson->id,
                'title' => $nextLesson->title,
                'type' => $nextLesson->type,
                'content' => $nextLesson->content
            ] : null
        ];
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats($user): array
    {
        return [
            'total_enrolled' => $user->enrolledCourses()->count(),
            'total_completed' => Enrollment::where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->count(),
            'in_progress' => $user->enrolledCourses()
                ->whereDoesntHave('enrollments', fn($q) => $q->whereNotNull('completed_at'))
                ->count()
        ];
    }
    
    /**
     * Get in-progress courses
     */
    private function getInProgressCourses($user)
    {
        return $user->enrolledCourses()
            ->with(['meetings' => fn($q) => $q->orderBy('order_number')])
            ->get()
            ->map(fn($course) => $this->formatCourseSummary($course, $user));
    }
    
    /**
     * Format course for dashboard summary (lightweight)
     * ✅ FIXED: Added category, level, duration, instructor, description
     */
    private function formatCourseSummary($course, $user): array
    {
        $meetings = $course->meetings;
        $lessons = $meetings->filter(fn($m) => $this->isLesson($m));
        
        $lessonIds = $lessons->pluck('id');
        $completed = UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessonIds)
            ->count();
        
        $total = $lessons->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,       // ✅ NEW
            'instructor' => $course->instructor,         // ✅ NEW
            'thumbnail' => $course->thumbnail,
            'category' => $course->category,             // ✅ NEW
            'level' => $course->level,                   // ✅ NEW
            'duration' => $course->duration,             // ✅ NEW
            'progress' => $progress,
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'is_completed' => (int) $progress === 100,
        ];
    }
    
    /**
     * Get available courses for enrollment
     * ✅ FIXED: Added category, level, duration
     */
    private function getAvailableCourses($user)
    {
        $enrolledIds = $user->enrolledCourses()->pluck('courses.id');
        
        return Course::whereNotIn('courses.id', $enrolledIds)
            ->select(
                'courses.id', 
                'courses.title', 
                'courses.description', 
                'courses.instructor', 
                'courses.thumbnail',
                'courses.category',    // ✅ NEW
                'courses.level',       // ✅ NEW
                'courses.duration'     // ✅ NEW
            )
            ->limit(6)
            ->get();
    }
    /**
 * GET: Ambil gradebook user dengan nilai quiz
 * 
 * Endpoint: GET /api/user/gradebook
 * 
 * @return \Illuminate\Http\JsonResponse
 */
public function getGradebook()
{
    try {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        // 1. Get all enrolled courses for this user
        $enrollments = \App\Models\Enrollment::where('user_id', $user->id)
            ->with(['course' => function($q) {
                $q->select('id', 'title', 'instructor', 'thumbnail');
            }])
            ->get();
        
        $gradebook = [];
        
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            
            if (!$course) continue;
            
            // 2. Get all quizzes for this course
            $quizzes = \App\Models\Quiz::whereHas('meeting', function($q) use ($course) {
                $q->where('course_id', $course->id);
            })->with(['meeting' => function($q) {
                $q->select('id', 'title', 'type');
            }])->get();
            
            $quizResults = [];
            $totalScore = 0;
            $quizCount = 0;
            
            // 3. Get best attempt for each quiz
            foreach ($quizzes as $quiz) {
                $bestAttempt = \App\Models\QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $quiz->id)
                    ->orderBy('score', 'desc')
                    ->first();
                
                if ($bestAttempt) {
                    $quizResults[] = [
                        'quiz_id' => $quiz->id,
                        'quiz_title' => $quiz->title,
                        'quiz_type' => $quiz->meeting->type ?? 'quiz',
                        'score' => $bestAttempt->score,
                        'passed' => $bestAttempt->passed,
                        'attempt_number' => $bestAttempt->attempt_number,
                        'submitted_at' => $bestAttempt->created_at,
                    ];
                    
                    $totalScore += $bestAttempt->score;
                    $quizCount++;
                }
            }
            
            // 4. Calculate average score
            $averageScore = $quizCount > 0 ? round($totalScore / $quizCount) : 0;
            
            // 5. Add to gradebook
            $gradebook[] = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'instructor' => $course->instructor,
                'thumbnail' => $course->thumbnail,
                'enrolled_at' => $enrollment->created_at,
                'completed_at' => $enrollment->completed_at,
                'overall_progress' => $enrollment->completed_at ? 100 : 0,
                'average_score' => $averageScore,
                'total_quizzes' => $quizzes->count(),
                'completed_quizzes' => count($quizResults),
                'quiz_results' => $quizResults,
            ];
        }
        
        return response()->json([
            'success' => true,
            'gradebook' => $gradebook,
        ]);
        
    } catch (\Throwable $e) {
        \Log::error('Gradebook error: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to load gradebook',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
    }
}