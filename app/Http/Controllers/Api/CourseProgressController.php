<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseProgressController extends Controller
{
    /**
     * GET: Get progress percentage for a course
     * Endpoint: GET /api/courses/{courseId}/progress
     */
    public function getProgress($courseId)
    {
        $user = Auth::user();
        
        // Get all meetings for this course (FLAT structure)
        $meetings = Meeting::where('course_id', $courseId)->get();
        
        if ($meetings->isEmpty()) {
            return response()->json(['progress' => 0, 'total' => 0, 'completed' => 0]);
        }
        
        // Get completed meetings for this user
        $completedMeetingIds = UserProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $meetings->pluck('id'))
            ->pluck('meeting_id');
        
        // Hitung progress (hanya lesson yang dihitung, bukan meeting header)
        $lessons = $meetings->filter(fn($m) => $m->content !== null); // Hanya lesson yang punya content
        $completedLessons = $lessons->filter(fn($m) => $completedMeetingIds->contains($m->id));
        
        $total = $lessons->count();
        $completed = $completedLessons->count();
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        return response()->json([
            'progress' => $progress,
            'total_lessons' => $total,
            'completed_lessons' => $completed
        ]);
    }
    
    /**
     * POST: Update progress for a specific meeting/lesson
     * Endpoint: POST /api/courses/{courseId}/progress
     */
   public function updateProgress(Request $request, $courseId)
{
    // ✅ Validasi yang menerima meeting_id + is_completed
    $request->validate([
        'meeting_id' => 'required|exists:meetings,id',
        'is_completed' => 'required|boolean'  // ← boolean, bukan progress
    ]);
    
    $user = Auth::user();
    $meetingId = $request->meeting_id;
    $isCompleted = $request->is_completed;
    
    // Update or create progress record
    $progress = \App\Models\UserProgress::updateOrCreate(
        ['user_id' => $user->id, 'meeting_id' => $meetingId],
        [
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null
        ]
    );
    
    // Return updated course progress
    return $this->getProgress($courseId);
}
}