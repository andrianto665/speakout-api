<?php
/**
 * Admin Quiz Controller
 * 
 * Handles management of quiz questions for admin/instructor users.
 * 
 * @package App\Http\Controllers\Api
 */

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
     * Helper: Pastikan user adalah admin
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Admin privileges required');
        }
    }

    /**
     * POST: Tambah soal baru ke quiz tertentu
     * 
     * Endpoint: POST /api/admin/quizzes/{quizId}/questions
     * 
     * @param  Request  $request
     * @param  int  $quizId
     * @return JsonResponse
     */
    public function addQuestion(Request $request, $quizId): JsonResponse
    {
        try {
            // 1. Pastikan user adalah admin
            $this->ensureAdmin();

            // 2. Validasi input dari admin
            $validated = $request->validate([
                'question'       => 'required|string|max:1000',
                'type'           => 'required|string|in:multiple_choice,true_false,short_answer',
                'options'        => 'required|array|min:2',
                'options.*'      => 'required|string|max:255',
                'correct_answer' => 'required|string',
                'points'         => 'nullable|integer|min:1',
                'order'          => 'nullable|integer|min:0',
            ]);

            // 3. Siapkan data untuk disimpan
            $questionData = [
                'quiz_id'        => $quizId,
                'question'       => $validated['question'],
                'type'           => $validated['type'],
                'options'        => json_encode($validated['options']), // Simpan sebagai JSON string
                'correct_answer' => $validated['correct_answer'],
                'points'         => $validated['points'] ?? 1,
                'order'          => $validated['order'] ?? 0,
            ];

            // 4. Simpan ke database
            $newQuestion = QuizQuestion::create($questionData);

            // 5. Return response sukses
            return response()->json([
                'success' => true,
                'message' => 'Soal berhasil ditambahkan!',
                'question' => $newQuestion
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error validasi (data tidak lengkap/salah format)
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Error sistem/database
            Log::error('AdminQuizController@addQuestion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan soal. Silakan coba lagi.'
            ], 500);
        }
    }
}