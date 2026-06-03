<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminContentController extends Controller
{
    /**
     * GET: List all meetings/lessons for a course
     * Endpoint: GET /api/admin/courses/{courseId}/content
     */
    public function index($courseId): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $course = Course::findOrFail($courseId);
            $meetings = $course->meetings()->orderBy('order_number')->get();
            
            return response()->json($meetings);
            
        } catch (\Exception $e) {
            Log::error('AdminContentController@index: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load content'], 500);
        }
    }

    /**
     * POST: Create new meeting/lesson
     * Endpoint: POST /api/admin/courses/{courseId}/content
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:normal,test,final',
                'content' => 'nullable|url',
                'order_number' => 'required|integer|min:1',
            ]);
            
            $meeting = Meeting::create([
                'course_id' => $courseId,
                'title' => $validated['title'],
                'type' => $validated['type'],
                'content' => $validated['content'],
                'order_number' => $validated['order_number'],
            ]);
            
            return response()->json([
                'message' => 'Content created',
                'meeting' => $meeting
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('AdminContentController@store: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create content'], 500);
        }
    }

         /**
     * PUT: Update meeting/lesson
     * Endpoint: PUT /api/admin/content/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $meeting = Meeting::findOrFail($id);
            
            // ✅ PERBAIKAN: Terima semua tipe yang valid termasuk 'lesson'
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:normal,lesson,test,final',
                'content' => 'nullable|string',
                'order_number' => 'sometimes|required|integer|min:1',
            ]);
            
            // ✅ NORMALISASI: Ubah 'lesson' jadi 'normal' untuk database
            if (isset($validated['type'])) {
                if ($validated['type'] === 'lesson') {
                    $validated['type'] = 'normal';
                }
                // 'meeting' juga jadi 'normal'
                if ($validated['type'] === 'meeting') {
                    $validated['type'] = 'normal';
                }
            }
            
            $meeting->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Content berhasil diupdate!',
                'meeting' => $meeting
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal', 
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminContentController@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE: Delete meeting/lesson
     * Endpoint: DELETE /api/admin/content/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->ensureAdmin();
            
            $meeting = Meeting::findOrFail($id);
            $meeting->delete();
            
            return response()->json(['message' => 'Content deleted']);
            
        } catch (\Exception $e) {
            Log::error('AdminContentController@destroy: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete content'], 500);
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
            
            // Filter by user
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            $attempts = $query->orderBy('created_at', 'desc')->limit(100)->get();
            
            // Format response for frontend
            $formatted = $attempts->map(fn($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'user_name' => $a->user->name ?? 'Unknown',
                'user_email' => $a->user->email ?? null,
                'course_id' => $a->quiz->meeting->course->id ?? null,
                'course_title' => $a->quiz->meeting->course->title ?? 'Unknown Course',
                'quiz_id' => $a->quiz_id,
                'quiz_title' => $a->quiz->title ?? 'Quiz',
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
     */
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Admin privileges required');
        }
    }
}