<?php
/**
 * Admin Controller
 * 
 * Handles admin dashboard, course management, and user management.
 * 
 * @package App\Http\Controllers\Api
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * GET: Admin dashboard stats
     * Endpoint: GET /api/admin/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            return response()->json([
                'total_users' => User::count(),
                'total_courses' => Course::count(),
                'total_enrollments' => Enrollment::count(),
                'completed_courses' => Enrollment::whereNotNull('completed_at')->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('AdminController@getStats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load stats'], 500);
        }
    }
    
    /**
     * GET: List all courses (for admin management)
     * Endpoint: GET /api/admin/courses
     */
    public function index(): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $courses = Course::withCount(['meetings as total_lessons' => fn($q) => $q->whereNotNull('content')])
                ->select('id', 'title', 'description', 'instructor', 'thumbnail', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json($courses);
            
        } catch (\Exception $e) {
            Log::error('AdminController@index: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load courses'], 500);
        }
    }
    
    /**
     * GET: Show single course details
     * Endpoint: GET /api/admin/courses/{course}
     */
    public function show($course): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $courseModel = Course::with(['meetings' => fn($q) => $q->orderBy('order_number')])
                ->findOrFail($course);
            
            return response()->json($courseModel);
            
        } catch (\Exception $e) {
            Log::error('AdminController@show: ' . $e->getMessage());
            return response()->json(['message' => 'Course not found'], 404);
        }
    }
    
    /**
     * POST: Create new course
     * Endpoint: POST /api/admin/courses
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'instructor' => 'required|string|max:255',
                'thumbnail' => 'nullable|url'
            ]);
            
            // Create course
            $course = Course::create($validated);
            
            return response()->json([
                'message' => 'Course created successfully',
                'course' => $course
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminController@store: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create course'], 500);
        }
    }
    
    /**
     * PUT: Update existing course
     * Endpoint: PUT /api/admin/courses/{course}
     */
    public function update(Request $request, $course): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $courseModel = Course::findOrFail($course);
            
            // Validate input
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'instructor' => 'sometimes|required|string|max:255',
                'thumbnail' => 'nullable|url'
            ]);
            
            // Update course
            $courseModel->update($validated);
            
            return response()->json([
                'message' => 'Course updated successfully',
                'course' => $courseModel
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminController@update: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update course'], 500);
        }
    }
    
    /**
     * DELETE: Delete course
     * Endpoint: DELETE /api/admin/courses/{course}
     */
    public function destroy($course): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $courseModel = Course::findOrFail($course);
            
            // Delete related data first (meetings, enrollments)
            $courseModel->meetings()->delete();
            Enrollment::where('course_id', $courseModel->id)->delete();
            
            // Delete course
            $courseModel->delete();
            
            return response()->json(['message' => 'Course deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('AdminController@destroy: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete course'], 500);
        }
    }
    
    /**
     * GET: List all users (for admin management)
     * Endpoint: GET /api/admin/users
     */
    public function getUsers(): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $users = User::select('id', 'name', 'email', 'role', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json($users);
            
        } catch (\Exception $e) {
            Log::error('AdminController@getUsers: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load users'], 500);
        }
    }
    
    /**
     * DELETE: Delete user
     * Endpoint: DELETE /api/admin/users/{user}
     */
    public function deleteUser($user): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $userModel = User::findOrFail($user);
            
            // Prevent deleting self
            if ($userModel->id === Auth::id()) {
                return response()->json(['message' => 'Cannot delete your own account'], 400);
            }
            
            // Prevent deleting other admins (optional safety)
            if ($userModel->role === 'admin' && Auth::user()->id !== $userModel->id) {
                return response()->json(['message' => 'Cannot delete other admin accounts'], 403);
            }
            
            // Delete related enrollments first
            Enrollment::where('user_id', $userModel->id)->delete();
            
            // Delete user
            $userModel->delete();
            
            return response()->json(['message' => 'User deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('AdminController@deleteUser: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }

        /**
     * GET: List all quiz attempts with filters
     * Endpoint: GET /api/admin/quiz-attempts
     */
    public function getQuizAttempts(Request $request): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $query = \App\Models\QuizAttempt::with([
                'user' => fn($q) => $q->select('id', 'name', 'email'),
                'quiz.meeting.course' => fn($q) => $q->select('id', 'title')
            ]);
            
            // Filter by course
            if ($request->filled('course_id')) {
                $query->whereHas('quiz.meeting', fn($q) => 
                    $q->where('course_id', $request->course_id)
                );
            }
            
            // Filter by status (passed/failed)
            if ($request->filled('status')) {
                $query->where('passed', $request->status === 'passed');
            }
            
            // Filter by date
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }
            
            $attempts = $query->orderBy('created_at', 'desc')->limit(100)->get();
            
            // Format response for frontend
            $formatted = $attempts->map(fn($a) => [
                'id' => $a->id,
                'user_name' => $a->user->name ?? null,
                'user_email' => $a->user->email ?? null,
                'course_title' => $a->quiz->meeting->course->title ?? null,
                'quiz_title' => $a->quiz->title ?? null,
                'score' => $a->score,
                'passed' => (bool) $a->passed,
                'attempt_number' => $a->attempt_number,
                'created_at' => $a->created_at,
            ]);
            
            return response()->json($formatted);
            
        } catch (\Exception $e) {
            Log::error('AdminController@getQuizAttempts: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load quiz attempts'], 500);
        }
    }
    
    /**
     * Helper: Ensure user is admin
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Admin privileges required');
        }
    }
}