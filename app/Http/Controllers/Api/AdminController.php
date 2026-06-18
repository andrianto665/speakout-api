<?php
/**
 * Admin Controller
 * 
 * Handles admin dashboard, course management, and user management.
 * 
 * @package App\Http\Controllers\Api
 */

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
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
            $courseIds = $this->myInstructorCourseIds();

            if (count($courseIds) > 0) {
                return response()->json([
                    'is_scoped' => true,
                    'total_users' => Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id'),
                    'total_courses' => count($courseIds),
                    'total_enrollments' => Enrollment::whereIn('course_id', $courseIds)->count(),
                    'completed_courses' => Enrollment::whereIn('course_id', $courseIds)->whereNotNull('completed_at')->count(),
                ]);
            }

            return response()->json([
                'is_scoped' => false,
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
        $courseIds = $this->myInstructorCourseIds();

        $query = Course::withCount(['meetings as total_lessons' => fn($q) => $q->whereNotNull('content')])
            ->select('id', 'title', 'description', 'instructor', 'instructor_id', 'thumbnail', 'category', 'level', 'price', 'duration', 'created_at', 'updated_at');

        if (count($courseIds) > 0) {
            $query->whereIn('id', $courseIds);
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(20);
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

            $courseIds = $this->myInstructorCourseIds();
            if (count($courseIds) > 0 && !in_array((int) $course, $courseIds)) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke course ini'], 403);
            }

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

            if ($blocked = $this->blockIfScoped()) return $blocked;
            
            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'instructor' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'level' => 'nullable|string|max:100',
                'price' => 'nullable|integer|min:0',
                'duration' => 'nullable|string|max:100',
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

            $courseIds = $this->myInstructorCourseIds();
            if (count($courseIds) > 0 && !in_array((int) $course, $courseIds)) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke course ini'], 403);
            }

            $courseModel = Course::findOrFail($course);
            
            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'instructor' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'level' => 'nullable|string|max:100',
                'price' => 'nullable|integer|min:0',
                'duration' => 'nullable|string|max:100',
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

            if ($blocked = $this->blockIfScoped()) return $blocked;
            
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

            if ($blocked = $this->blockIfScoped()) return $blocked;
            
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
 * POST: Create new admin/instructor user
 * Endpoint: POST /api/admin/users
 */
public function storeUser(Request $request): JsonResponse
{
    try {
        $this->ensureAdmin();

        if ($blocked = $this->blockIfScoped()) return $blocked;

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'password'  => 'required|string|min:6',
            'phone'     => 'nullable|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $user = User::forceCreate([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'admin',
            'is_admin' => 1,
        ]);

        // Link instruktur ke course jika dipilih
        if (!empty($validated['course_id'])) {
            Course::where('id', $validated['course_id'])
                ->whereNull('instructor_id')
                ->update(['instructor_id' => $user->id]);
        }

        return response()->json([
            'message' => 'User berhasil dibuat',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('AdminController@storeUser: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to create user'], 500);
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

            if ($blocked = $this->blockIfScoped()) return $blocked;
            
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
            $myCourseIds = $this->myInstructorCourseIds();

            $query = \App\Models\QuizAttempt::with([
                'user' => fn($q) => $q->select('id', 'name', 'email'),
                'quiz.meeting.course' => fn($q) => $q->select('id', 'title')
            ]);

            if (count($myCourseIds) > 0) {
                $allowedIds = $myCourseIds;
                if ($request->filled('course_id') && in_array((int) $request->course_id, $myCourseIds)) {
                    $allowedIds = [(int) $request->course_id];
                }
                $query->whereHas('quiz.meeting', fn($q) => $q->whereIn('course_id', $allowedIds));
            } elseif ($request->filled('course_id')) {
                $query->whereHas('quiz.meeting', fn($q) => $q->where('course_id', $request->course_id));
            }

            if ($request->filled('status')) {
                $query->where('passed', $request->status === 'passed');
            }
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }

            $attempts = $query->orderBy('created_at', 'desc')->limit(100)->get();

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
    * Course IDs di mana user yang login adalah instructor_id-nya.
    * Kosong = bukan instruktur scoped, berarti admin penuh.
    */
    private function myInstructorCourseIds(): array
    {
        return Course::where('instructor_id', Auth::id())->pluck('id')->toArray();
    }

    /**
    * Blokir endpoint ini kalau yang akses adalah akun instruktur scoped.
    */
    private function blockIfScoped(): ?JsonResponse
    {
        if (count($this->myInstructorCourseIds()) > 0) {
            return response()->json(['message' => 'Akun instruktur tidak memiliki akses ke fitur ini'], 403);
        }
        return null;
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