<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Get all teachers (from courses instructors)
     */
    public function index()
    {
        try {
            // Ambil semua instructor yang unique dari courses
            $teachers = Course::select('instructor')
                ->distinct()
                ->get()
                ->map(function($course, $index) {
                    return [
                        'id' => $index + 1,
                        'name' => $course->instructor,
                        'role' => 'Tutor SpeakOut',
                        'photo' => null, // Bisa ditambahkan field photo di courses table
                        'expertise' => $this->getExpertiseByIndex($index),
                        'courses_count' => Course::where('instructor', $course->instructor)->count(),
                        'total_students' => 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $teachers
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load teachers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper untuk assign expertise berdasarkan index
     */
    private function getExpertiseByIndex($index)
    {
        $expertiseList = [
            ['Speaking', 'Grammar', 'TOEFL'],
            ['Conversation', 'Writing', 'Business'],
            ['Pronunciation', 'Listening', 'IELTS'],
            ['Public Speaking', 'Debate', 'Academic'],
        ];
        
        return $expertiseList[$index % count($expertiseList)];
    }

    /**
     * Get teacher detail with courses
     */
    public function show($name)
    {
        try {
            $courses = Course::where('instructor', $name)->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => $name,
                    'role' => 'Tutor SpeakOut',
                    'photo' => null,
                    'expertise' => ['Speaking', 'Grammar', 'TOEFL'],
                    'courses' => $courses,
                    'total_courses' => $courses->count(),
                    'courses_count' => $courses->count(),
                    'total_students' => 0
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }
    }
}