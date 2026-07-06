<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPaymentController extends Controller
{
    /**
     * Get semua pembayaran yang pending
     * GET /api/admin/payments/pending
     */
    public function getPendingPayments()
    {
        // Cek apakah user adalah admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $payments = Enrollment::where('payment_status', 'pending')
            ->with(['user', 'course'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'user' => [
                        'id' => $p->user->id,
                        'name' => $p->user->name,
                        'email' => $p->user->email,
                    ],
                    'course' => [
                        'id' => $p->course->id,
                        'title' => $p->course->title,
                    ],
                    'amount_paid' => $p->amount_paid,
                    'payment_method' => $p->payment_method,
                    'payment_proof' => $p->payment_proof ? 
                        asset('storage/' . $p->payment_proof) : null,
                    'payment_proof_filename' => $p->payment_proof ? basename($p->payment_proof) : null,
                    'enrolled_at' => $p->enrolled_at,
                    'created_at' => $p->updated_at,
                    ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
            'total' => $payments->count()
        ]);
    }

    /**
     * Approve payment
     * POST /api/admin/payments/{id}/approve
     */
    public function approvePayment($id)
    {
        // Cek apakah user adalah admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $enrollment = Enrollment::findOrFail($id);
        
        if ($enrollment->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran sudah disetujui sebelumnya'
            ], 400);
        }

        // Approve payment
        $enrollment->approvePayment();

        // Optional: Send notification to user (email/WhatsApp)
        // This can be implemented later

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran disetujui. Siswa sekarang bisa akses kursus.',
            'enrollment' => [
                'id' => $enrollment->id,
                'payment_status' => $enrollment->payment_status,
                'paid_at' => $enrollment->paid_at,
                'can_access' => $enrollment->canAccessCourse(),
            ]
        ]);
    }

    /**
     * Reject payment
     * POST /api/admin/payments/{id}/reject
     */
    public function rejectPayment($id, Request $request)
    {
        // Cek apakah user adalah admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $enrollment = Enrollment::findOrFail($id);
        
        if ($enrollment->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran sudah ditolak sebelumnya'
            ], 400);
        }

        // Reject payment
        $enrollment->rejectPayment($request->reason ?? '');

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran ditolak',
            'enrollment' => [
                'id' => $enrollment->id,
                'payment_status' => $enrollment->payment_status,
            ]
        ]);
    }

    /**
     * Get all payments (paid, pending, rejected)
     * GET /api/admin/payments
     */
    public function getAllPayments(Request $request)
    {
        // Cek apakah user adalah admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $query = Enrollment::with(['user', 'course']);

        // Filter by status if provided
        if ($request->has('status') && in_array($request->status, ['pending', 'paid', 'rejected'])) {
            $query->where('payment_status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'user' => [
                        'id' => $p->user->id,
                        'name' => $p->user->name,
                        'email' => $p->user->email,
                    ],
                    'course' => [
                        'id' => $p->course->id,
                        'title' => $p->course->title,
                    ],
                    'amount_paid' => $p->amount_paid, // ✅ diganti dari 'amount' → 'amount_paid'
                    'payment_status' => $p->payment_status,
                    'payment_status_label' => $p->payment_status_label,
                    'payment_method' => $p->payment_method,
                    'payment_proof' => $p->payment_proof ? 
                        asset('storage/' . $p->payment_proof) : null,
                    'paid_at' => $p->paid_at,
                    'enrolled_at' => $p->enrolled_at,
                    'created_at' => $p->updated_at, // ✅ dari fix sebelumnya (tanggal upload)
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payments,
            'summary' => [
                'total' => $payments->count(),
                'pending' => $payments->where('payment_status', 'pending')->count(),
                'paid' => $payments->where('payment_status', 'paid')->count(),
                'rejected' => $payments->where('payment_status', 'rejected')->count(),
                'total_revenue' => $payments->where('payment_status', 'paid')->sum('amount_paid'), // ✅ baru
            ]
        ]);
    }

        /**
     * Get detail 1 payment untuk kwitansi
     * GET /api/admin/payments/{id}
     */
    public function getPaymentDetail($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $p = Enrollment::with(['user', 'course'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $p->id,
                'user' => [
                    'name' => $p->user->name,
                    'email' => $p->user->email,
                ],
                'course' => [
                    'title' => $p->course->title,
                ],
                'amount_paid' => $p->amount_paid,
                'payment_status' => $p->payment_status,
                'payment_method' => $p->payment_method,
                'payment_proof_filename' => $p->payment_proof ? basename($p->payment_proof) : null,
                'paid_at' => $p->paid_at,
                'enrolled_at' => $p->enrolled_at,
                'created_at' => $p->updated_at,
            ]
        ]);
    }
}