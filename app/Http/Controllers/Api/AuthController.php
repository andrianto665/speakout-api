<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller 
{
    /**
     * Register new user
     * POST /api/auth/register
     */
    public function register(Request $request) 
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'course_id' => 'nullable|exists:courses,id', // opsional, validasi course ada
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'student',
            'is_admin' => 0,
        ]);

        // Auto-enroll ke course yang dipilih jika ada
        if ($request->course_id) {
            Enrollment::create([
                'user_id'     => $user->id,
                'course_id'   => $request->course_id,
                'enrolled_at' => now(),
            ]);
        }
        // Auto-enroll jika ada course_id
        if ($request->course_id) {
            \App\Models\Enrollment::create([
                'user_id'     => $user->id,
                'course_id'   => $request->course_id,
                'enrolled_at' => now(),
            ]);
        }
        return response()->json([
            'message' => 'Registered successfully',
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role,
                'is_admin' => $user->is_admin,
            ]
        ], 201);
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request) 
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials']
            ]);
        }

        // ✅ Return user dengan field role & is_admin secara eksplisit
        return response()->json([
            'message' => 'Login successful',
            'token' => $user->createToken('speakout-api')->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,        // ✅ Penting untuk admin check
                'is_admin' => $user->is_admin ?? 0,  // ✅ Fallback ke 0 jika null
            ]
        ]);
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    public function logout(Request $request) 
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}