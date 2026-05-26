<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminContentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseProgressController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - SpeakOut E-Course
|--------------------------------------------------------------------------
| Public Routes:
| - Login & Register
| - Teachers Directory
| - Courses List
|
| Protected Routes:
| - Logout
| - User Profile
| - Update Learning Progress
|
| Admin Routes:
| - Manage Courses
| - Manage Users
| - Dashboard Statistics
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| 🌐 PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// ================= AUTH =================

// Register User
Route::post('/auth/register', [AuthController::class, 'register']);

// Login User
Route::post('/auth/login', [AuthController::class, 'login']);


// ================= TEACHERS =================

// Get All Teachers
Route::get('/teachers', [TeacherController::class, 'index']);

// Get Single Teacher
Route::get('/teachers/{name}', [TeacherController::class, 'show']);


// ================= COURSES =================

// Get All Courses
Route::get('/courses', [CourseController::class, 'index']);

// Get Detail Course
Route::get('/courses/{id}', [CourseController::class, 'show']);




/*
|--------------------------------------------------------------------------
| 🔐 PROTECTED ROUTES
|--------------------------------------------------------------------------
| Semua route di bawah ini wajib login menggunakan Sanctum
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ================= AUTH =================

    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Current User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    // ================= LEARNING PROGRESS =================

   
});


/*
|--------------------------------------------------------------------------
| 👑 ADMIN ROUTES
|--------------------------------------------------------------------------
| Khusus admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'is_admin'])
    ->prefix('admin')
    ->group(function () {

        // ================= DASHBOARD =================

        // Dashboard Statistics
        Route::get('/stats', [AdminController::class, 'getStats']);


        // ================= COURSE MANAGEMENT =================

        // Get All Courses
        Route::get('/courses', [AdminController::class, 'index']);

        // Create Course
        Route::post('/courses', [AdminController::class, 'store']);

        // Get Single Course
        Route::get('/courses/{course}', [AdminController::class, 'show']);

        // Update Course
        Route::put('/courses/{course}', [AdminController::class, 'update']);

        // Delete Course
        Route::delete('/courses/{course}', [AdminController::class, 'destroy']);


        // ================= USER MANAGEMENT =================

        // Get All Users
        Route::get('/users', [AdminController::class, 'getUsers']);

        // Delete User
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    });

    // Admin Content Management
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Get all content for a course (FLAT structure)
    Route::get('/courses/{courseId}/content', [AdminContentController::class, 'index']);
    
    // Store new content (meeting/lesson)
    Route::post('/courses/{courseId}/content', [AdminContentController::class, 'store']);
    
    // Update content
    Route::put('/content/{id}', [AdminContentController::class, 'update']);
    
    // Delete content
    Route::delete('/content/{id}', [AdminContentController::class, 'destroy']);
    });
    
    // ✅ TAMBAHKAN INI: Progress Tracking Routes
    Route::middleware('auth:sanctum')->group(function () {
    
    // Get progress percentage for a course
    Route::get('/courses/{courseId}/progress', [CourseProgressController::class, 'getProgress']);
    
    // Update progress for a specific meeting/lesson
    Route::post('/courses/{courseId}/progress', [CourseProgressController::class, 'updateProgress']);
    });

    // User Dashboard & Enrollments
    Route::middleware('auth:sanctum')->group(function () {
    
    // ✅ Get courses that user is enrolled in (dengan progress)
    Route::get('/user/enrolled-courses', [UserController::class, 'getEnrolledCourses']);
    
    // ✅ Enroll in a course
    Route::post('/user/enroll/{courseId}', [UserController::class, 'enroll']);
    
    // ✅ Get dashboard summary
    Route::get('/user/dashboard', [UserController::class, 'getDashboardSummary']);
    
    });

    // 🔧 DEBUG: Cek user yang login (hapus setelah selesai testing)
Route::middleware('auth:sanctum')->get('/debug/whoami', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'enrolled_count' => $user->enrolledCourses()->count()
    ]);
});

// Course Progress & Completion
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/courses/{courseId}/progress', [CourseProgressController::class, 'getProgress']);
    Route::post('/courses/{courseId}/progress', [CourseProgressController::class, 'updateProgress']);
    
    // ✅ NEW: Auto-check course completion
    Route::post('/courses/{courseId}/check-completion', [CourseProgressController::class, 'checkCourseCompletion']);
});
    

/*
|--------------------------------------------------------------------------
| 🔧 FALLBACK ROUTE
|--------------------------------------------------------------------------
| Jika endpoint tidak ditemukan
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API Endpoint not found'
    ], 404);
});