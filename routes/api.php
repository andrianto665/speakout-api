<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TeacherController;

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

    // Update Course Progress
    Route::post('/courses/{id}/progress', [ProgressController::class, 'updateProgress']);
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