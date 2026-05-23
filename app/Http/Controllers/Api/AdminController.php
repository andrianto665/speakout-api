<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;

class AdminController extends Controller
{
    /**
     * GET /api/admin/stats
     */
    public function getStats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_courses' => Course::count(),
            'total_enrollments' => Enrollment::count(),
            'completed_courses' => Enrollment::where('progress', 100)->count(),
        ]);
    }

    /**
     * GET /api/admin/courses
     */
    public function index()
    {
        $courses = Course::withCount('lessons')->get();
        return response()->json($courses);
    }

    /**
     * POST /api/admin/courses
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructor' => 'required|string|max:255',
        ]);

        $course = Course::create($validated);
        return response()->json($course, 201);
    }

    /**
     * GET /api/admin/courses/{course}
     */
    public function show(Course $course)
    {
        return response()->json($course->load('lessons'));
    }

    /**
     * PUT /api/admin/courses/{course}
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'instructor' => 'sometimes|required|string|max:255',
        ]);

        $course->update($validated);
        return response()->json($course);
    }

    /**
     * DELETE /api/admin/courses/{course}
     */
    public function destroy(Course $course)
    {
        $course->delete();
        return response()->json(null, 204);
    }

    /**
     * GET /api/admin/users
     */
    public function getUsers()
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();
        return response()->json($users);
    }

    /**
     * DELETE /api/admin/users/{user}
     */
    public function deleteUser(User $user)
    {
        // Cegah admin menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }
        
        $user->delete();
        return response()->json(null, 204);
    }
}