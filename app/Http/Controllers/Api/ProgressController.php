<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enrollment;

class ProgressController extends Controller
{
    // GET api/courses/{id}/progress
    public function getProgress($courseId)
    {
        $enrollment = Enrollment::where('user_id', auth()->id())
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'course_id'    => (int) $courseId,
                'progress'     => 0,
                'is_completed' => false
            ]);
        }

        return response()->json([
            'course_id'    => $enrollment->course_id,
            'progress'     => $enrollment->progress,
            'is_completed' => (bool) $enrollment->is_completed
        ]);
    }

    // POST api/courses/{id}/progress
    public function updateProgress(Request $request, $courseId)
    {
        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);

        $enrollment = Enrollment::updateOrCreate(
            [
                'user_id'   => auth()->id(),
                'course_id' => $courseId
            ],
            [
                'progress'     => $request->progress,
                'is_completed' => $request->progress >= 100
            ]
        );

        return response()->json([
            'message' => 'Progress berhasil diupdate',
            'data'    => [
                'course_id'    => $enrollment->course_id,
                'progress'     => $enrollment->progress,
                'is_completed' => (bool) $enrollment->is_completed
            ]
        ], 200);
    }
}