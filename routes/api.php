<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ✅ Import semua controller
use App\Http\Controllers\Api\AdminCertificateController;
use App\Http\Controllers\Api\AdminContentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminQuizController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseProgressController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentGatewayController;

/*
|--------------------------------------------------------------------------
| 🌐 PUBLIC ROUTES (No Auth Required)
|--------------------------------------------------------------------------
*/

// ================= AUTH =================
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ================= GOOGLE OAUTH =================
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// ================= TEACHERS =================
Route::get('/teachers', [TeacherController::class, 'index']);
Route::get('/teachers/{name}', [TeacherController::class, 'show']);

// ================= COURSES =================
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

// ================= PAYMENT GATEWAY NOTIFICATION (Public - dipanggil Midtrans) =================
Route::post('/payment/notification', [PaymentGatewayController::class, 'midtransNotification']);


/*
|--------------------------------------------------------------------------
| 🔐 PROTECTED ROUTES (Auth Required - Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ================= AUTH =================
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    // ================= USER PROFILE =================
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);
    Route::delete('/user/account', [UserController::class, 'deleteAccount']);

    // ================= USER DASHBOARD & ENROLLMENTS =================
    Route::get('/user/enrolled-courses', [UserController::class, 'getEnrolledCourses']);
    Route::post('/user/enroll/{courseId}', [UserController::class, 'enroll']);
    Route::get('/user/dashboard', [UserController::class, 'getDashboardSummary']);

    // ================= GRADEBOOK =================
    Route::get('/user/gradebook', [UserController::class, 'getGradebook']);

    // ================= USER CERTIFICATE =================
    Route::get('/user/certificates/{courseId}/status', [CertificateController::class, 'getStatus']);
    Route::get('/user/certificates/{courseId}/download', [CertificateController::class, 'download']);

    // ================= COURSE PROGRESS & COMPLETION =================
    Route::get('/courses/{courseId}/progress', [CourseProgressController::class, 'getProgress']);
    Route::post('/courses/{courseId}/progress', [CourseProgressController::class, 'updateProgress']);
    Route::post('/courses/{courseId}/check-completion', [CourseProgressController::class, 'checkCourseCompletion']);

    // ================= QUIZ SYSTEM (User) =================
    Route::get('/quizzes/{quizId}', [QuizController::class, 'show']);
    Route::post('/quizzes/{quizId}/submit', [QuizController::class, 'submit']);

    // ================= PAYMENT (User) =================
    Route::get('/user/enrollments', [PaymentController::class, 'getMyEnrollments']);
    Route::get('/user/enrollments/{id}/payment-info', [PaymentController::class, 'getPaymentInfo']);
    Route::post('/user/enrollments/{id}/upload-payment', [PaymentController::class, 'uploadProof']);

    // ================= PAYMENT GATEWAY (Midtrans) =================
    Route::post('/payment/create', [PaymentGatewayController::class, 'createPayment']);
    Route::get('/payment/status/{enrollment_id}', [PaymentGatewayController::class, 'checkPaymentStatus']);
    Route::post('/payment/sync/{enrollment_id}', [PaymentGatewayController::class, 'syncStatus']);

}); // ← ✅ TUTUP GROUP auth:sanctum


/*
|--------------------------------------------------------------------------
| 👑 ADMIN ROUTES (Auth + Admin Role Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {

    // ================= DASHBOARD =================
    Route::get('/stats', [AdminController::class, 'getStats']);

    // ================= COURSE MANAGEMENT =================
    Route::get('/courses', [AdminController::class, 'index']);
    Route::post('/courses', [AdminController::class, 'store']);
    Route::get('/courses/{course}', [AdminController::class, 'show']);
    Route::put('/courses/{course}', [AdminController::class, 'update']);
    Route::delete('/courses/{course}', [AdminController::class, 'destroy']);

    // ================= USER MANAGEMENT =================
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::post('/users', [AdminController::class, 'storeUser']);  // ⬅️ baris baru
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

    // ================= CONTENT MANAGEMENT =================
    Route::get('/courses/{courseId}/content', [AdminContentController::class, 'index']);
    Route::post('/courses/{courseId}/content', [AdminContentController::class, 'store']);
    Route::put('/content/{id}', [AdminContentController::class, 'update']);
    Route::delete('/content/{id}', [AdminContentController::class, 'destroy']);

    // ================= QUIZ MANAGEMENT (Admin) =================
    Route::post('/quizzes/{quizId}/questions', [AdminQuizController::class, 'addQuestion']);
    Route::put('/quizzes/questions/{questionId}', [AdminQuizController::class, 'editQuestion']);
    Route::delete('/quizzes/questions/{questionId}', [AdminQuizController::class, 'deleteQuestion']);

    // ================= PAYMENT MANAGEMENT (Admin) =================
    Route::get('/payments', [AdminPaymentController::class, 'getAllPayments']);
    Route::get('/payments/{id}', [AdminPaymentController::class, 'getPaymentDetail']);
    Route::get('/payments/pending', [AdminPaymentController::class, 'getPendingPayments']);
    Route::post('/payments/{id}/approve', [AdminPaymentController::class, 'approvePayment']);
    Route::post('/payments/{id}/reject', [AdminPaymentController::class, 'rejectPayment']);

    // List all quizzes untuk dropdown
    Route::get('/quizzes', function () {
        $quizzes = \App\Models\Quiz::with(['meeting.course' => fn($q) => $q->select('id', 'title')])
            ->select('id', 'title', 'meeting_id')
            ->get()
            ->map(fn($quiz) => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'course_title' => $quiz->meeting->course->title ?? 'Unknown',
                'meeting_id' => $quiz->meeting_id,
            ]);
        return response()->json($quizzes);
    });

    // ================= QUIZ ATTEMPTS MONITORING =================
    Route::get('/quiz-attempts', [AdminController::class, 'getQuizAttempts']);

    // ================= CERTIFICATE MANAGEMENT (Admin) =================
    Route::get('/certificates', [AdminCertificateController::class, 'index']);
    Route::post('/certificates', [AdminCertificateController::class, 'store']); // Create manual
    Route::post('/certificates/{id}/approve', [AdminCertificateController::class, 'approve']);
    Route::post('/certificates/{id}/reject', [AdminCertificateController::class, 'reject']);
    Route::get('/courses/{courseId}/enrolled-students', [AdminCertificateController::class, 'enrolledStudents']);

}); // ← ✅ TUTUP GROUP admin


/*
|--------------------------------------------------------------------------
| 🔧 FALLBACK ROUTE
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API Endpoint not found'
    ], 404);
});