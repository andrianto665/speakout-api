<?php
/**
 * Course Progress Controller
 * 
 * Handles progress tracking and auto-completion logic for courses.
 * 
 * @package App\Http\Controllers\Api
 * @author SpeakOut Team
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CourseProgressController extends Controller
{
    /**
     * Get progress percentage for a course
     * 
     * GET /api/courses/{courseId}/progress
     * 
     * @param  int  $courseId
     * @return JsonResponse
     */
    public function getProgress(int $courseId): JsonResponse
    {
        $user = Auth::user();
        
        // Get all meetings for this course (FLAT structure)
        $meetings = Meeting::where('course_id', $courseId)->get();
        
        if ($meetings->isEmpty()) {
            return response()->json([
                'progress' => 0,
                'total_lessons' => 0,
                'completed_lessons' => 0,
                'is_completed' => false
            ]);
        }
        
        // Get completed meeting IDs for this user
        $completedIds = UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $meetings->pluck('id'))
            ->pluck('meeting_id');
        
        // Calculate progress (only lessons with content, exclude meeting headers)
        $lessons = $meetings->filter(fn($m) => $m->content !== null);
        $completedLessons = $lessons->filter(fn($m) => $completedIds->contains($m->id));
        
        $total = $lessons->count();
        $completed = $completedLessons->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        // ✅ Check if course is fully completed
        $isCompleted = $completed >= $total;
        
        return response()->json([
            'progress' => $progress,
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            // ✅ NEW: Fields for frontend badge & admin analytics
            'is_completed' => $isCompleted,
            'course_completed_at' => $isCompleted 
                ? Enrollment::where('user_id', $user->id)
                    ->where('course_id', $courseId)
                    ->value('completed_at') 
                : null
        ]);
    }
    
    /**
     * Update progress for a specific meeting/lesson
     * 
     * POST /api/courses/{courseId}/progress
     * 
     * @param  Request  $request
     * @param  int  $courseId
     * @return JsonResponse
     */
    public function updateProgress(Request $request, int $courseId): JsonResponse
    {
        // Validate input (snake_case keys for Laravel)
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'is_completed' => 'required|boolean'
        ]);
        
        $user = Auth::user();
        
        // Update or create progress record
        UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'meeting_id' => $validated['meeting_id']],
            [
                'is_completed' => $validated['is_completed'],
                'completed_at' => $validated['is_completed'] ? now() : null
            ]
        );
        
        // Return updated course progress
        return $this->getProgress($courseId);
    }
    
    /**
 * Check if course is fully completed after lesson update
 * 
 * Auto-updates enrollment.completed_at & generates certificate if all lessons are done.
 * 
 * POST /api/courses/{courseId}/check-completion
 * 
 * @param  int  $courseId
 * @return JsonResponse
 */
public function checkCourseCompletion(int $courseId): JsonResponse
{
    try {
        $user = Auth::user();
        
        // 1. Get ALL lesson IDs for this course (only items with actual content)
        $lessonIds = \App\Models\Meeting::where('course_id', $courseId)
            ->whereNotNull('content')  // Only lessons, not meeting headers
            ->pluck('id')
            ->toArray();
        
        // Handle courses with no lessons
        if (empty($lessonIds)) {
            // Auto-complete if no lessons exist
            \App\Models\Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                ['completed_at' => now()]
            );
            
            // Generate certificate if not exists
            $this->generateCertificateIfMissing($user->id, $courseId);
            
            return response()->json([
                'course_completed' => true,
                'progress' => 100,
                'total_lessons' => 0,
                'completed_lessons' => 0,
                'message' => '🎉 Course completed! Certificate earned!'
            ]);
        }
        
        // 2. Get completed lesson IDs for this user
        $completedIds = \App\Models\UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessonIds)
            ->pluck('meeting_id')
            ->toArray();
        
        // 3. Calculate progress
        $total = count($lessonIds);
        $completed = count($completedIds);
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        $isCompleted = $completed >= $total;
        
        // 4. If all lessons completed, mark enrollment & generate certificate
        if ($isCompleted) {
            // Update enrollment
            \App\Models\Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                ['completed_at' => now()]
            );
            
            // Generate certificate if not exists yet
            $this->generateCertificateIfMissing($user->id, $courseId);
            
            return response()->json([
                'course_completed' => true,
                'progress' => 100,
                'total_lessons' => $total,
                'completed_lessons' => $completed,
                'message' => '🎉 Course completed! Certificate earned!'
            ]);
        }
        
        // Course not yet completed
        return response()->json([
            'course_completed' => false,
            'progress' => $progress,
            'total_lessons' => $total,
            'completed_lessons' => $completed
        ]);
        
    } catch (\Exception $e) {
        \Log::error('CourseProgressController@checkCourseCompletion: ' . $e->getMessage(), [
            'user_id' => Auth::id() ?? null,
            'course_id' => $courseId
        ]);
        
        return response()->json([
            'message' => 'Failed to check completion',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

/**
 * Helper: Generate certificate if not exists yet
 */
private function generateCertificateIfMissing(int $userId, int $courseId): void
{
    // Check if certificate already exists
    $exists = \App\Models\Certificate::where('user_id', $userId)
        ->where('course_id', $courseId)
        ->exists();
    
    if (!$exists) {
        try {
            $user = \App\Models\User::find($userId);
            $course = \App\Models\Course::find($courseId);
            
            if ($user && $course) {
                $service = new \App\Services\CertificateService();
                $service->generateCertificate($user, $course);
                
                \Log::info("Certificate generated", [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'certificate_number' => $service->generateCertificate($user, $course)->certificate_number ?? 'unknown'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to generate certificate: " . $e->getMessage());
            // Don't throw - certificate is optional, don't break the flow
        }
    }
}
}