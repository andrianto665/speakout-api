<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;  // ✅ Import base Controller
use App\Models\Course;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Get all teachers (from courses instructors)
     */
    public function index()
    {
        // Ambil semua instructor yang unique dari courses
        $teachers = Course::select('instructor')
            ->distinct()
            ->get()
            ->map(function($course) {
                return [
                    'name' => $course->instructor,
                    'courses_count' => Course::where('instructor', $course->instructor)->count(),
                    'total_students' => 0, // Bisa ditambahkan nanti
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    /**
     * Get teacher detail with courses
     */
    public function show($name)
    {
        $courses = Course::where('instructor', $name)->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'name' => $name,
                'courses' => $courses,
                'total_courses' => $courses->count()
            ]
        ]);
    }
}