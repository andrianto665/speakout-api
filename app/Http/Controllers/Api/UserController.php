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
     * 
     * @return JsonResponse
     */
    public function getEnrolledCourses(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get enrolled courses with ordered meetings
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
     * 
     * @param  Request  $request
     * @param  int  $courseId
     * @return JsonResponse
     */
    public function enroll(Request $request, int $courseId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate course exists
            $course = Course::findOrFail($courseId);
            
            // Check if already enrolled
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
            
            // Create new enrollment
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
     * Get dashboard summary (stats + courses)
     * 
     * GET /api/user/dashboard
     * 
     * @return JsonResponse
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
     * Format course data with progress calculation
     * 
     * @param  \App\Models\Course  $course
     * @param  \App\Models\User  $user
     * @return array
     */
    private function formatCourseWithProgress($course, $user): array
    {
        $meetings = $course->meetings;
        
        // Filter only lessons (items with content), exclude meeting headers
        $lessons = $meetings->filter(fn ($m) => $m->content !== null);
        
        // Get completed lesson IDs for this user
        $completedIds = UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessons->pluck('id'))
            ->pluck('meeting_id');
        
        // Calculate progress
        $total = $lessons->count();
        $completed = $lessons->filter(fn ($m) => $completedIds->contains($m->id))->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        // Find next incomplete lesson (or last lesson if all completed)
        $nextLesson = $lessons->first(fn ($m) => !$completedIds->contains($m->id)) 
            ?? $lessons->last();
        
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'instructor' => $course->instructor,
            'thumbnail' => $course->thumbnail,
            'enrolled_at' => $course->pivot->enrolled_at,
            'progress' => $progress,
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'last_lesson' => $nextLesson ? [
                'id' => $nextLesson->id,
                'title' => $nextLesson->title,
                'type' => $nextLesson->type,
                'content' => $nextLesson->content
            ] : null
        ];
    }
    
    /**
     * Get user statistics for dashboard
     * 
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getUserStats($user): array
    {
        return [
            'total_enrolled' => $user->enrolledCourses()->count(),
            'total_completed' => Enrollment::where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->count(),
            'in_progress' => $user->enrolledCourses()
                ->whereDoesntHave('enrollments', fn ($q) => $q->whereNotNull('completed_at'))
                ->count()
        ];
    }
    
    /**
     * Get in-progress courses with progress percentage
     * 
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    private function getInProgressCourses($user)
    {
        return $user->enrolledCourses()
            ->with(['meetings' => fn ($q) => $q->orderBy('order_number')])
            ->get()
            ->map(fn ($course) => $this->formatCourseSummary($course, $user));
    }
    
    /**
     * Format course for dashboard summary (lightweight version)
     * 
     * @param  \App\Models\Course  $course
     * @param  \App\Models\User  $user
     * @return array
     */
    private function formatCourseSummary($course, $user): array
    {
        $lessons = $course->meetings->filter(fn ($m) => $m->content !== null);
        
        $completed = UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessons->pluck('id'))
            ->count();
        
        $total = $lessons->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        return [
            'id' => $course->id,
            'title' => $course->title,
            'progress' => $progress,
            'thumbnail' => $course->thumbnail,
            'total_lessons' => $total,
            'completed_lessons' => $completed
        ];
    }
    
    /**
     * Get available courses for enrollment (not yet enrolled)
     * 
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    private function getAvailableCourses($user)
    {
        $enrolledIds = $user->enrolledCourses()->pluck('courses.id');
        
        return Course::whereNotIn('courses.id', $enrolledIds)
        ->select('courses.id', 'courses.title', 'courses.description', 'courses.instructor', 'courses.thumbnail')
        ->limit(3)
        ->get();
    }
}