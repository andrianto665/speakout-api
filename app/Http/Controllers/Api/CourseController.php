<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Meeting;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    public function index()
{
    try {
        $courses = Course::withCount(['meetings as total_lessons' => function($q) {
            $q->whereNotNull('content');
        }])
        ->select(
            'id', 'title', 'description', 'instructor', 'thumbnail', 
            'category', 'level', 'price', 'duration',
            'created_at', 'updated_at'
        )
        ->get()
        ->map(function($course) {
            // Pastikan total_lessons adalah integer
            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'instructor' => $course->instructor,
                'thumbnail' => $course->thumbnail,
                'category' => $course->category,
                'level' => $course->level,
                'price' => $course->price ?? 0,
                'duration' => $course->duration,
                'total_lessons' => (int) ($course->total_lessons ?? 0),
                'is_enrolled' => false,
                'is_completed' => false,
                'progress' => 0,
                'completed_lessons' => 0,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
            ];
        });
        
        return response()->json($courses);
        
    } catch (\Exception $e) {
        Log::error('CourseController@index error: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to load courses'], 500);
    }
}

    public function show($id)
    {
        try {
            $course = Course::with(['meetings' => function($q) {
                $q->orderBy('order_number', 'asc');
            }])->findOrFail($id);

            return response()->json($course);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Course not found'], 404);
            
        } catch (\Exception $e) {
            Log::error('CourseController@show error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load course'], 500);
        }
    }

    public function getUserProgress($courseId)
    {
        try {
            $userId = Auth::id();
            
            $meetings = Meeting::where('course_id', $courseId)
                ->whereNotNull('content')
                ->get();
            
            $completedMeetings = UserProgress::where('user_id', $userId)
                ->where('is_completed', 1)
                ->whereIn('meeting_id', $meetings->pluck('id'))
                ->count();
            
            $totalMeetings = $meetings->count();
            $progress = $totalMeetings > 0 ? round(($completedMeetings / $totalMeetings) * 100) : 0;
            
            return response()->json([
                'course_id' => $courseId,
                'progress' => $progress,
                'completed_lessons' => $completedMeetings,
                'total_lessons' => $totalMeetings,
            ]);
            
        } catch (\Exception $e) {
            Log::error('CourseController@getUserProgress error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load progress'], 500);
        }
    }

    public function updateProgress(Request $request, $courseId)
    {
        try {
            $userId = Auth::id();
            
            $validated = $request->validate([
                'meeting_id' => 'required|exists:meetings,id',
                'is_completed' => 'required|boolean',
            ]);
            
            UserProgress::updateOrCreate(
                [
                    'user_id' => $userId,
                    'meeting_id' => $validated['meeting_id'],
                ],
                [
                    'is_completed' => $validated['is_completed'],
                    'completed_at' => $validated['is_completed'] ? now() : null,
                ]
            );
            
            $meetings = Meeting::where('course_id', $courseId)
                ->whereNotNull('content')
                ->get();
            
            $completedMeetings = UserProgress::where('user_id', $userId)
                ->where('is_completed', 1)
                ->whereIn('meeting_id', $meetings->pluck('id'))
                ->count();
            
            $totalMeetings = $meetings->count();
            $progress = $totalMeetings > 0 ? round(($completedMeetings / $totalMeetings) * 100) : 0;
            
            return response()->json([
                'success' => true,
                'progress' => $progress,
                'completed_lessons' => $completedMeetings,
                'total_lessons' => $totalMeetings,
            ]);
            
        } catch (\Exception $e) {
            Log::error('CourseController@updateProgress error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update progress'], 500);
        }
    }
}