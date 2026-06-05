<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminCertificateController extends Controller
{
    /**
     * GET: List semua certificates (dengan filter)
     * 
     * Endpoint: GET /api/admin/certificates
     * Query params: 
     *   - status: pending|approved|rejected (optional)
     *   - user_id: filter by user (optional)
     *   - course_id: filter by course (optional)
     *   - per_page: items per page (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Certificate::with(['user', 'course', 'approvedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by course
            if ($request->has('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $certificates = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Transform data
            $certificates->getCollection()->transform(function ($cert) {
                return [
                    'id' => $cert->id,
                    'certificate_number' => $cert->certificate_number,
                    'status' => $cert->status,
                    'user' => [
                        'id' => $cert->user->id,
                        'name' => $cert->user->name,
                        'email' => $cert->user->email,
                    ],
                    'course' => [
                        'id' => $cert->course->id,
                        'title' => $cert->course->title,
                    ],
                    'issued_at' => $cert->issued_at?->format('Y-m-d H:i:s'),
                    'approved_at' => $cert->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $cert->approvedBy?->name,
                    'rejection_reason' => $cert->rejection_reason,
                    'created_at' => $cert->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $certificates->items(),
                'pagination' => [
                    'total' => $certificates->total(),
                    'per_page' => $certificates->perPage(),
                    'current_page' => $certificates->currentPage(),
                    'last_page' => $certificates->lastPage(),
                ],
                'summary' => [
                    'total' => Certificate::count(),
                    'pending' => Certificate::pending()->count(),
                    'approved' => Certificate::approved()->count(),
                    'rejected' => Certificate::rejected()->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AdminCertificateController@index error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch certificates',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST: Approve certificate
     * 
     * Endpoint: POST /api/admin/certificates/{id}/approve
     */
    public function approve($id): JsonResponse
    {
        try {
            $admin = Auth::user();

            // Cek apakah user adalah admin
            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $certificate = Certificate::findOrFail($id);

            // Cek status
            if ($certificate->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate sudah di-approve sebelumnya.'
                ], 400);
            }

            // Approve certificate
            $certificate->approve($admin->id);

            Log::info('✅ Certificate approved by admin', [
                'certificate_id' => $certificate->id,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Certificate berhasil di-approve.',
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'status' => $certificate->status,
                    'approved_at' => $certificate->approved_at->format('Y-m-d H:i:s'),
                    'approved_by' => $admin->name,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('AdminCertificateController@approve error', [
                'certificate_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve certificate',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST: Reject certificate
     * 
     * Endpoint: POST /api/admin/certificates/{id}/reject
     * Body: { "reason": "Alasan penolakan" }
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $admin = Auth::user();

            // Cek apakah user adalah admin
            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            // Validasi reason
            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            $certificate = Certificate::findOrFail($id);

            // Cek status
            if ($certificate->isRejected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate sudah di-reject sebelumnya.'
                ], 400);
            }

            // Reject certificate
            $certificate->reject($admin->id, $request->reason);

            Log::info('❌ Certificate rejected by admin', [
                'certificate_id' => $certificate->id,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Certificate berhasil di-reject.',
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'status' => $certificate->status,
                    'approved_at' => $certificate->approved_at->format('Y-m-d H:i:s'),
                    'approved_by' => $admin->name,
                    'rejection_reason' => $certificate->rejection_reason,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('AdminCertificateController@reject error', [
                'certificate_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject certificate',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}