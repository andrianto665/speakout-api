<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Course;
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
            'course_id' => 'nullable|exists:courses,id',
        ]);

        \Log::info('DEBUG REGISTER - course_id diterima:', ['course_id' => $request->course_id, 'type' => gettype($request->course_id)]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'student',
            'is_admin' => 0,
        ]);

        \Log::info('DEBUG REGISTER - user dibuat dengan id:', ['user_id' => $user->id]);

        $enrollment = null;
        $paymentInfo = null;

        if ($request->course_id) {
            \Log::info('DEBUG REGISTER - masuk ke blok enroll, course_id:', ['course_id' => $request->course_id]);
            try {
                // Ambil info course untuk mendapatkan harga
                $course = Course::find($request->course_id);
                $amount = $course->price ?? 0;

                \Log::info('DEBUG REGISTER - course price:', ['price' => $amount]);

                // Create enrollment dengan status payment pending
                $enrollment = Enrollment::create([
                    'user_id'         => $user->id,
                    'course_id'       => $request->course_id,
                    'enrolled_at'     => now(),
                    'payment_status'  => 'pending',  // ✅ Status pending payment
                    'amount_paid'     => $amount,     // ✅ Simpan harga course
                ]);

                \Log::info('DEBUG REGISTER - enrollment berhasil dibuat dengan payment pending:', [
                    'enrollment_id' => $enrollment->id,
                    'payment_status' => $enrollment->payment_status,
                    'amount' => $enrollment->amount_paid
                ]);

                // Siapkan info payment untuk response
                $paymentInfo = [
                    'enrollment_id' => $enrollment->id,
                    'course_title' => $course->title,
                    'amount' => $amount,
                    'status' => 'pending',
                    'message' => 'Silakan lakukan pembayaran untuk mengaktifkan kursus',
                ];

            } catch (\Exception $e) {
                \Log::error('DEBUG REGISTER - GAGAL bikin enrollment:', ['error' => $e->getMessage()]);
            }
        } else {
            \Log::info('DEBUG REGISTER - TIDAK masuk blok enroll karena course_id kosong/falsy');
        }

        // Response dengan info payment
        $response = [
            'message' => $enrollment 
                ? 'Registrasi berhasil. Silakan lakukan pembayaran untuk mengaktifkan kursus.'
                : 'Registrasi berhasil',
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role,
                'is_admin' => $user->is_admin,
            ],
            'token' => $user->createToken('speakout-api')->plainTextToken,
        ];

        // Tambahkan payment info jika ada enrollment
        if ($paymentInfo) {
            $response['payment'] = $paymentInfo;
        }

        return response()->json($response, 201);
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