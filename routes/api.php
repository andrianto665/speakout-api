<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes - SpeakOut E-Course
|--------------------------------------------------------------------------
*/

// ========== 🌐 PUBLIC ROUTES ==========
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// ========== 🔐 PROTECTED ROUTES (Semua user login) ==========
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Courses (User biasa bisa lihat)
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    
    // Progress (User bisa update progress sendiri)
    Route::post('/courses/{id}/progress', [ProgressController::class, 'updateProgress']);
});

// ========== 👑 ADMIN ROUTES (Hanya admin) ==========
// Gunakan middleware 'is_admin' yang sudah kita daftar di Step 2
Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {
    
    // 📊 Stats Dashboard
    Route::get('/stats', [AdminController::class, 'getStats']);
    
    // 📚 Manage Courses (CRUD lengkap)
    Route::get('/courses', [AdminController::class, 'index']);           // List semua course
    Route::post('/courses', [AdminController::class, 'store']);          // Tambah course
    Route::get('/courses/{course}', [AdminController::class, 'show']);   // Detail course
    Route::put('/courses/{course}', [AdminController::class, 'update']); // Edit course
    Route::delete('/courses/{course}', [AdminController::class, 'destroy']); // Hapus course
    
    // 👥 Manage Users
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
});

// ========== 🔧 FALLBACK ==========
Route::fallback(function () {
    return response()->json(['message' => 'Endpoint not found'], 404);
});