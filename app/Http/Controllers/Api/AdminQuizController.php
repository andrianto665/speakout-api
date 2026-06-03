<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminQuizController extends Controller
{
    /**
     * Helper: Cek apakah user adalah admin.
     * Mengembalikan JsonResponse jika gagal, sehingga TIDAK PERNAH redirect ke halaman login HTML.
     */
    private function checkAdmin(): ?JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin privileges required.'
            ], 403);
        }
        return null; // Null berarti sukses (user adalah admin)
    }

    public function addQuestion(Request $request, $quizId): JsonResponse
    {
        // 1. Cek admin (akan return JSON 403 jika bukan admin, menghentikan eksekusi lebih lanjut)
        $adminCheck = $this->checkAdmin();
        if ($adminCheck) return $adminCheck;

        try {
            $validated = $request->validate([
                'question'       => 'required|string|max:1000',
                'type'           => 'required|string|in:multiple_choice,true_false,short_answer',
                'options'        => 'required|array|min:2',
                'options.*'      => 'required|string|max:255',
                'correct_answer' => 'required|string',
                'points'         => 'nullable|integer|min:1',
                'order'          => 'nullable|integer|min:0',
            ]);

            $questionData = [
                'quiz_id'        => $quizId,
                'question'       => $validated['question'],
                'type'           => $validated['type'],
                'options'        => json_encode($validated['options']),
                'correct_answer' => $validated['correct_answer'],
                'points'         => $validated['points'] ?? 10,
                'order'          => $validated['order'] ?? 0,
            ];

            $newQuestion = QuizQuestion::create($questionData);

            return response()->json([
                'success' => true,
                'message' => 'Soal berhasil ditambahkan!',
                'question' => $newQuestion
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('AdminQuizController@addQuestion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan soal. ' . $e->getMessage()
            ], 500);
        }
    }

    public function editQuestion(Request $request, int $questionId): JsonResponse
    {
        $adminCheck = $this->checkAdmin();
        if ($adminCheck) return $adminCheck;

        try {
            $question = QuizQuestion::find($questionId);

            if (!$question) {
                return response()->json(['success' => false, 'message' => 'Soal tidak ditemukan.'], 404);
            }

            $validated = $request->validate([
                'question'       => 'required|string|max:1000',
                'type'           => 'required|string|in:multiple_choice,true_false,short_answer',
                'options'        => 'required|array|min:2',
                'options.*'      => 'required|string|max:255',
                'correct_answer' => 'required|string',
                'points'         => 'nullable|integer|min:1',
                'order'          => 'nullable|integer|min:0',
            ]);

            $question->update([
                'question'       => $validated['question'],
                'type'           => $validated['type'],
                'options'        => json_encode($validated['options']),
                'correct_answer' => $validated['correct_answer'],
                'points'         => $validated['points'] ?? 10,
                'order'          => $validated['order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Soal berhasil diperbarui!',
                'question' => $question
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('AdminQuizController@editQuestion error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengedit soal. ' . $e->getMessage()], 500);
        }
    }

    public function deleteQuestion(int $questionId): JsonResponse
    {
        $adminCheck = $this->checkAdmin();
        if ($adminCheck) return $adminCheck;

        try {
            $question = QuizQuestion::find($questionId);

            if (!$question) {
                return response()->json(['success' => false, 'message' => 'Soal tidak ditemukan.'], 404);
            }

            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Soal berhasil dihapus dari database.'
            ]);

        } catch (\Throwable $e) {
            Log::error('AdminQuizController@deleteQuestion error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus soal. ' . $e->getMessage()], 500);
        }
    }
}