<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Get all teachers
     */
    public function index()
    {
        $teachers = Course::select('instructor')
            ->distinct()
            ->get()
            ->map(function($course) {
                return [
                    'name' => $course->instructor,
                    'courses_count' => Course::where('instructor', $course->instructor)->count(),
                    'total_students' => 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    /**
     * Get teacher detail
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