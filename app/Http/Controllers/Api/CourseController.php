<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Meeting;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of courses (public endpoint)
     * GET /api/courses
     */
    public function index()
{
    $courses = Course::withCount(['meetings as total_lessons' => fn($q) => $q->whereNotNull('content')])
        ->select(
            'id', 
            'title', 
            'description', 
            'instructor', 
            'thumbnail', 
            'category',      // ✅ TAMBAHKAN
            'level',         // ✅ TAMBAHKAN
            'price',         // ✅ TAMBAHKAN
            'duration',      // ✅ TAMBAHKAN
            'created_at',
            'updated_at'
        )
        ->get();
    
    return response()->json($courses);
}

    /**
     * Display course details with meetings (requires auth for enrolled content)
     * GET /api/courses/{id}
     */
    public function show($courseId)
    {
        // ✅ Eager load relasi quiz agar tidak N+1 query
        $course = Course::with(['meetings.quiz'])->findOrFail($courseId);
        
        return response()->json([
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'instructor' => $course->instructor,
            'thumbnail' => $course->thumbnail,
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
            'meetings' => $course->meetings->map(function($meeting) {
                return [
                    'id' => $meeting->id,
                    'course_id' => $meeting->course_id,
                    'order_number' => $meeting->order_number,
                    'title' => $meeting->title,
                    'type' => $meeting->type,
                    'content' => $meeting->content,
                    'has_test' => $meeting->has_test,
                    'is_final_test' => $meeting->is_final_test,
                    'created_at' => $meeting->created_at,
                    'updated_at' => $meeting->updated_at,
                    // ✅ TAMBAHKAN INI: quiz_id agar frontend bisa render quiz UI
                    'quiz_id' => $meeting->quiz ? $meeting->quiz->id : null,
                ];
            }),
        ]);
    }

    /**
     * Update progress for a specific meeting/lesson
     * POST /api/courses/{courseId}/progress
     * 
     * ✅ FIXED: Use UserProgress model & user_progress table
     */
    public function updateProgress(Request $request, $courseId)
    {
        // ✅ Validate input
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'is_completed' => 'required|boolean',
        ]);

        $userId = Auth::id();
        $meetingId = $validated['meeting_id'];
        $isCompleted = $validated['is_completed'];

        // ✅ Use UserProgress model (tabel: user_progress)
        $progress = UserProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'meeting_id' => $meetingId,
            ],
            [
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
                'updated_at' => now(),
            ]
        );

        // ✅ Calculate course completion percentage
        $courseProgress = $this->calculateCourseProgress($userId, $courseId);

        return response()->json([
            'success' => true,
            'message' => $isCompleted ? 'Progress updated: Completed' : 'Progress updated: Incomplete',
            'progress' => $progress,
            'course_progress' => $courseProgress,
        ]);
    }

    /**
     * Check if course is completed (100% progress)
     * POST /api/courses/{courseId}/check-completion
     */
    public function checkCompletion($courseId)
    {
        $userId = Auth::id();

        $courseProgress = $this->calculateCourseProgress($userId, $courseId);

        return response()->json([
            'course_id' => $courseId,
            'user_id' => $userId,
            'progress_percentage' => $courseProgress['percentage'],
            'completed_lessons' => $courseProgress['completed'],
            'total_lessons' => $courseProgress['total'],
            'course_completed' => $courseProgress['percentage'] >= 100,
        ]);
    }

    /**
     * Helper: Calculate course progress percentage
     * @private
     */
    private function calculateCourseProgress($userId, $courseId)
    {
        // Count total meetings for this course (excluding quiz headers with content=null)
        $totalMeetings = Meeting::where('course_id', $courseId)
            ->where(function($query) {
                $query->where('type', '!=', 'quiz')
                      ->orWhere('content', '!=', null);
            })
            ->count();

        // Count completed meetings for this user & course
        $completedMeetings = UserProgress::where('user_id', $userId)
            ->where('is_completed', 1)
            ->whereHas('meeting', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->count();

        $percentage = $totalMeetings > 0 
            ? round(($completedMeetings / $totalMeetings) * 100) 
            : 0;

        return [
            'total' => $totalMeetings,
            'completed' => $completedMeetings,
            'percentage' => min($percentage, 100), // Cap at 100%
        ];
    }

    /**
     * Get user's progress for a specific course
     * GET /api/courses/{courseId}/user-progress
     */
    public function getUserProgress($courseId)
    {
        $userId = Auth::id();

        $progress = UserProgress::where('user_id', $userId)
            ->whereHas('meeting', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->with('meeting')
            ->get()
            ->map(function($p) {
                return [
                    'meeting_id' => $p->meeting_id,
                    'meeting_title' => $p->meeting->title ?? null,
                    'is_completed' => $p->is_completed,
                    'completed_at' => $p->completed_at,
                ];
            });

        $courseProgress = $this->calculateCourseProgress($userId, $courseId);

        return response()->json([
            'course_id' => $courseId,
            'progress_percentage' => $courseProgress['percentage'],
            'completed_lessons' => $courseProgress['completed'],
            'total_lessons' => $courseProgress['total'],
            'details' => $progress,
        ]);
    }
}