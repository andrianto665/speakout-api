<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Get payment info untuk user
     * GET /api/user/enrollments/{id}/payment-info
     */
    public function getPaymentInfo($enrollmentId)
    {
        try {
            // ✅ Gunakan first() bukan findOrFail() untuk handle error lebih baik
            $enrollment = Enrollment::where('id', $enrollmentId)
                ->where('user_id', Auth::id()) // ✅ Validasi ownership
                ->with('course')
                ->first();

            // ✅ Handle jika enrollment tidak ditemukan
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enrollment tidak ditemukan atau Anda tidak memiliki akses.',
                    'enrollment_id' => $enrollmentId,
                    'user_id' => Auth::id()
                ], 404);
            }

            return response()->json([
                'success' => true,
                'enrollment' => [
                    'id' => $enrollment->id,
                    'course_title' => $enrollment->course->title ?? 'Course tidak ditemukan',
                    'course_instructor' => $enrollment->course->instructor ?? '-',
                    'amount' => $enrollment->amount_paid,
                    'status' => $enrollment->payment_status,
                    'status_label' => $enrollment->payment_status_label,
                    'payment_method' => $enrollment->payment_method,
                    'payment_proof' => $enrollment->payment_proof ? 
                        asset('storage/' . $enrollment->payment_proof) : null,
                    'paid_at' => $enrollment->paid_at,
                ],
                'payment_methods' => [
                    'dana' => [
                        'name' => 'DANA',
                        'number' => '0812-3456-7890',
                        'name_holder' => 'LKP PalComTech',
                        'icon' => '💙',
                    ],
                    'gopay' => [
                        'name' => 'GoPay',
                        'number' => '0812-3456-7890',
                        'name_holder' => 'LKP PalComTech',
                        'icon' => '💚',
                    ],
                    'qris' => [
                        'name' => 'QRIS',
                        'description' => 'Scan QR Code dari semua e-wallet & m-banking',
                        'icon' => '📱',
                    ],
                    'bank_transfer' => [
                        'name' => 'Transfer Bank',
                        'bank' => 'Bank Mandiri',
                        'number' => '123-000-1234-567',
                        'name_holder' => 'LKP PalComTech',
                        'icon' => '🏦',
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            // ✅ Log error untuk debugging
            \Log::error('Payment info error: ' . $e->getMessage(), [
                'enrollment_id' => $enrollmentId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload bukti pembayaran
     * POST /api/user/enrollments/{id}/upload-payment
     */
    public function uploadProof(Request $request, $enrollmentId)
    {
        $request->validate([
            'payment_method' => 'required|in:dana,gopay,bank_transfer,qris',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            // ✅ Validasi ownership
            $enrollment = Enrollment::where('id', $enrollmentId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enrollment tidak ditemukan atau Anda tidak memiliki akses.'
                ], 404);
            }

            // Cek jika sudah paid
            if ($enrollment->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran sudah dikonfirmasi sebelumnya'
                ], 400);
            }

            // Hapus file bukti lama jika ada (re-upload)
            if ($enrollment->payment_proof) {
                Storage::disk('public')->delete($enrollment->payment_proof);
            }

            // Upload file baru
            $file = $request->file('payment_proof');
            $path = $file->store('payment_proofs', 'public');

            // Update enrollment
            $enrollment->update([
                'payment_method' => $request->payment_method,
                'payment_proof' => $path,
                'payment_status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diupload. Menunggu konfirmasi admin (1x24 jam).',
                'enrollment' => [
                    'id' => $enrollment->id,
                    'payment_status' => $enrollment->payment_status,
                    'payment_method' => $enrollment->payment_method,
                    'payment_proof_url' => asset('storage/' . $path),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Upload payment proof error: ' . $e->getMessage(), [
                'enrollment_id' => $enrollmentId,
                'user_id' => Auth::id(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload bukti pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list enrollment user dengan status payment
     * GET /api/user/enrollments
     */
    public function getMyEnrollments()
    {
        try {
            $enrollments = Enrollment::where('user_id', Auth::id())
                ->with('course')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($e) {
                    return [
                        'id' => $e->id,
                        'course' => [
                            'id' => $e->course->id ?? null,
                            'title' => $e->course->title ?? 'Course tidak ditemukan',
                            'instructor' => $e->course->instructor ?? '-',
                        ],
                        'amount' => $e->amount_paid,
                        'payment_status' => $e->payment_status,
                        'payment_status_label' => $e->payment_status_label,
                        'payment_method' => $e->payment_method,
                        'can_access' => $e->canAccessCourse(),
                        'enrolled_at' => $e->enrolled_at,
                        'paid_at' => $e->paid_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $enrollments
            ]);

        } catch (\Exception $e) {
            \Log::error('Get enrollments error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data enrollment: ' . $e->getMessage()
            ], 500);
        }
    }
}