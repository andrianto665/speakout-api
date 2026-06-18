<?php
/**
 * User Controller - Handle user dashboard, enrollments, progress & profile
 * 
 * @package App\Http\Controllers\Api
 * @author SpeakOut Team
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\UserProgress;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
        return response()->json([
            'message' => 'Self-enrollment sudah dimatikan. Akses course diberikan oleh admin setelah pembayaran dikonfirmasi.'
        ], 403);
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
                'available_courses' => []
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
    // ✅ NEW: PROFILE METHODS
    // =========================================================================
    
    /**
     * Get user profile with stats
     * 
     * GET /api/user/profile
     */
    public function profile(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Stats berbeda untuk admin vs student
            $stats = [];
            if ($user->role === 'admin') {
                // Admin stats
                $stats = [
                    'total_users' => User::count(),
                    'total_courses' => Course::count(),
                    'total_enrollments' => Enrollment::count(),
                    'completed_courses' => Enrollment::whereNotNull('completed_at')->count(),
                ];
            } else {
                // Student stats
                $enrolledCount = $user->enrolledCourses()->count();
                $completedCount = Enrollment::where('user_id', $user->id)
                    ->whereNotNull('completed_at')
                    ->count();
                $inProgressCount = $enrolledCount - $completedCount;
                $certificatesCount = Certificate::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->count();
                
                $stats = [
                    'total_enrolled' => $enrolledCount,
                    'in_progress' => $inProgressCount,
                    'total_completed' => $completedCount,
                    'certificates' => $certificatesCount,
                ];
            }
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ],
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('UserController@profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Update user profile
     * 
     * PUT /api/user/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'nullable|string|max:20',
            ]);
            
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('UserController@updateProfile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Change user password
     * 
     * POST /api/user/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            $user = Auth::user();
            
            // Verifikasi password lama
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini salah'
                ], 422);
            }
            
            // Update password
            $user->update([
                'password' => Hash::make($request->password)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diperbarui'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('UserController@changePassword: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Delete user account
     * 
     * DELETE /api/user/account
     */
    public function deleteAccount(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Jangan izinkan admin menghapus akun sendiri jika hanya ada 1 admin
            if ($user->role === 'admin') {
                $adminCount = User::where('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat menghapus akun. Harus ada minimal 1 admin.'
                    ], 403);
                }
            }
            
            // Hapus semua data terkait
            $user->enrolledCourses()->detach();
            $user->certificates()->delete();
            $user->quizAttempts()->delete();
            $user->progress()->delete();
            
            // Hapus user
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('UserController@deleteAccount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    

    
    /**
     * Format course data with progress calculation
     * ✅ FIXED: Added category, level, duration
     */
    private function formatCourseWithProgress($course, $user): array
    {
        $meetings = $course->meetings;
        $lessons = $meetings->filter(fn($m) => $m->isLesson());
        
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
            'category' => $course->category,
            'level' => $course->level,
            'duration' => $course->duration,
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
        $lessons = $meetings->filter(fn($m) => $m->isLesson());
        
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
            'description' => $course->description,
            'instructor' => $course->instructor,
            'thumbnail' => $course->thumbnail,
            'category' => $course->category,
            'level' => $course->level,
            'duration' => $course->duration,
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
                'courses.category',
                'courses.level',
                'courses.duration'
            )
            ->limit(6)
            ->get();
    }
    /**
 * Hitung progress course berdasarkan UserProgress
 */
private function calculateProgress($course, $user): int|float
{
    $course->loadMissing(['meetings']);
    $lessons = $course->meetings->filter(fn($m) => $m->isLesson());
    $total = $lessons->count();
    if ($total === 0) return 0;
    $completed = UserProgress::where('user_id', $user->id)
        ->where('is_completed', true)
        ->whereIn('meeting_id', $lessons->pluck('id'))
        ->count();
    return round(($completed / $total) * 100);
}
    
    /**
     * GET: Ambil gradebook user dengan nilai quiz
     * 
     * Endpoint: GET /api/user/gradebook
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
                    // ✅ SESUDAH
                    'overall_progress' => $this->calculateProgress($course, $user),
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