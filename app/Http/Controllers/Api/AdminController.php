<?php
/**
 * Admin Controller - Handle admin dashboard & management
 * 
 * @package App\Http\Controllers\Api
 * @author SpeakOut Team
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

class AdminController extends Controller
{
    /**
     * GET: Admin dashboard stats
     * Endpoint: GET /api/admin/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            // Pastikan yang akses adalah admin
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied. Admin privileges required.'], 403);
            }
            
            // ✅ FIX: Query yang benar (tanpa kolom 'progress' yang tidak ada)
            return response()->json([
                'total_users' => User::count(),
                'total_courses' => Course::count(),
                'total_enrollments' => Enrollment::count(),
                // ✅ Gunakan completed_at, BUKAN progress
                'completed_courses' => Enrollment::whereNotNull('completed_at')->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('AdminController@getStats: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load stats',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * GET: List all users (for admin management)
     */
    public function getUsers(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied. Admin privileges required.'], 403);
            }
            
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
     * DELETE: Delete a user (admin only)
     */
    public function deleteUser($userId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['message' => 'Access denied. Admin privileges required.'], 403);
            }
            
            // Prevent deleting self
            if ($user->id == $userId) {
                return response()->json(['message' => 'Cannot delete your own account'], 400);
            }
            
            $target = User::findOrFail($userId);
            $target->delete();
            
            return response()->json(['message' => 'User deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('AdminController@deleteUser: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }
}