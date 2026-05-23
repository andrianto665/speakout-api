<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enrollment;

class ProgressController extends Controller
{
    public function updateProgress(Request $request, $courseId)
    {
        // Validasi data progress
        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);

        // Update atau buat data enrollment
        $enrollment = Enrollment::updateOrCreate(
            ['user_id' => auth()->id(), 'course_id' => $courseId],
            [
                'progress' => $request->progress,
                'is_completed' => $request->progress >= 100
            ]
        );

        return response()->json([
            'message' => 'Progress berhasil diupdate',
            'data' => $enrollment
        ], 200);
    }
}