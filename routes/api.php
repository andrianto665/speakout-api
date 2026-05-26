<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ================= CONTROLLERS =================
use App\Http\Controllers\Api\AdminContentController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseProgressController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - SpeakOut E-Course
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| 🌐 PUBLIC ROUTES (No Auth Required)
|--------------------------------------------------------------------------
*/

// ================= AUTH =================
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ================= TEACHERS =================
Route::get('/teachers', [TeacherController::class, 'index']);
Route::get('/teachers/{name}', [TeacherController::class, 'show']);

// ================= COURSES =================
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);


/*
|--------------------------------------------------------------------------
| 🔐 PROTECTED ROUTES (Auth Required - Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ================= AUTH =================
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());

    // ================= USER DASHBOARD & ENROLLMENTS =================
    Route::get('/user/enrolled-courses', [UserController::class, 'getEnrolledCourses']);
    Route::post('/user/enroll/{courseId}', [UserController::class, 'enroll']);
    Route::get('/user/dashboard', [UserController::class, 'getDashboardSummary']);

    // ================= CERTIFICATE DOWNLOAD =================
    Route::get('/user/certificates/{courseId}/download', [CertificateController::class, 'download']);

    // ================= COURSE PROGRESS & COMPLETION =================
    Route::get('/courses/{courseId}/progress', [CourseProgressController::class, 'getProgress']);
    Route::post('/courses/{courseId}/progress', [CourseProgressController::class, 'updateProgress']);
    Route::post('/courses/{courseId}/check-completion', [CourseProgressController::class, 'checkCourseCompletion']);

    // ================= DEBUG (Remove in Production) =================
    Route::get('/debug/whoami', fn(Request $request) => response()->json([
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'email' => $request->user()->email,
        'role' => $request->user()->role,
        'enrolled_count' => $request->user()->enrolledCourses()->count()
    ]));
});


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
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

    // ================= CONTENT MANAGEMENT (FLAT Structure) =================
    Route::get('/courses/{courseId}/content', [AdminContentController::class, 'index']);
    Route::post('/courses/{courseId}/content', [AdminContentController::class, 'store']);
    Route::put('/content/{id}', [AdminContentController::class, 'update']);
    Route::delete('/content/{id}', [AdminContentController::class, 'destroy']);
});


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