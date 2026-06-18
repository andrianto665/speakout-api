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
     * GET: List semua certificates (dengan filter & search)
     * 
     * Endpoint: GET /api/admin/certificates
     * Query params:
     *   - status: pending|approved|rejected (optional)
     *   - user_id: filter by user (optional)
     *   - course_id: filter by course (optional)
     *   - search: cari nama user, email, course title, atau certificate number (optional)
     *   - per_page: items per page (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Certificate::with(['user', 'course', 'approvedBy']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by course
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            // ✅ IMPROVED: Search by user name, email, course title, or certificate number (case-insensitive)
            if ($request->has('search') && !empty($request->search)) {
                $search = strtolower($request->search);
                $query->where(function($q) use ($search) {
                    $q->whereHas('user', function($uq) use ($search) {
                        $uq->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                           ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('course', function($cq) use ($search) {
                        $cq->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereRaw('LOWER(certificate_number) LIKE ?', ["%{$search}%"]);
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $certificates = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Transform data
            $certificates->setCollection($certificates->getCollection()->map(function ($cert) {
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
                    'issued_at' => $cert->issued_at ? $cert->issued_at->format('Y-m-d H:i:s') : null,
                    'approved_at' => $cert->approved_at ? $cert->approved_at->format('Y-m-d H:i:s') : null,
                    'approved_by' => $cert->approvedBy?->name,
                    'rejection_reason' => $cert->rejection_reason,
                    'created_at' => $cert->created_at->format('Y-m-d H:i:s'),
                ];
            }));

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
     * POST: Create certificate manual (admin buat langsung)
     * 
     * Endpoint: POST /api/admin/certificates
     */
    public function store(Request $request): JsonResponse
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

            // Validasi input
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id',
                'certificate_number' => 'nullable|string|unique:certificates,certificate_number',
            ]);

            // Cek apakah user sudah enroll course ini
            $enrollment = \App\Models\Enrollment::where('user_id', $request->user_id)
                ->where('course_id', $request->course_id)
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User belum enroll course ini'
                ], 422);
            }

            // Cek apakah sudah ada certificate
            if (Certificate::existsForUserAndCourse($request->user_id, $request->course_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate sudah ada untuk user dan course ini'
                ], 400);
            }

            // Generate certificate number jika tidak ada
            $certNumber = $request->certificate_number;
            if (!$certNumber) {
                $certNumber = Certificate::generateCertificateNumber();
            }

            // Create certificate (langsung approved)
            $certificate = Certificate::create([
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
                'certificate_number' => $certNumber,
                'verification_code' => Certificate::generateVerificationCode(),
                'status' => Certificate::STATUS_APPROVED,
                'issued_at' => now(),
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            // Load relationships untuk response
            $certificate->load(['user', 'course', 'approvedBy']);

            Log::info('✅ Certificate created manually by admin', [
                'certificate_id' => $certificate->id,
                'admin_id' => $admin->id,
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Certificate berhasil dibuat dan di-approve',
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'status' => $certificate->status,
                    'user' => [
                        'id' => $certificate->user->id,
                        'name' => $certificate->user->name,
                        'email' => $certificate->user->email,
                    ],
                    'course' => [
                        'id' => $certificate->course->id,
                        'title' => $certificate->course->title,
                    ],
                    'issued_at' => $certificate->issued_at->format('Y-m-d H:i:s'),
                    'approved_at' => $certificate->approved_at->format('Y-m-d H:i:s'),
                    'approved_by' => $certificate->approvedBy->name,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('AdminCertificateController@store error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create certificate',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    
    public function enrolledStudents($courseId): JsonResponse
    {
        $studentIds = \App\Models\Enrollment::where('course_id', $courseId)->pluck('user_id');
        $students = \App\Models\User::whereIn('id', $studentIds)
            ->whereDoesntHave('certificates', fn($q) => $q->where('course_id', $courseId))
            ->select('id', 'name', 'email')->get();
        return response()->json(['success' => true, 'data' => $students]);
    }
    public function approve(Request $request, $id): JsonResponse
{
    try {
        $certificate = Certificate::findOrFail($id);
        if ($certificate->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Certificate sudah di-approve sebelumnya'
            ], 400);
        }

        $certificate->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $certificate->load(['user', 'course', 'approvedBy']);

        Log::info('Certificate approved by admin', [
            'certificate_id' => $certificate->id,
            'admin_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Certificate berhasil di-approve',
            'certificate' => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'status' => $certificate->status,
                'user' => [
                    'id' => $certificate->user->id,
                    'name' => $certificate->user->name,
                ],
                'course' => [
                    'id' => $certificate->course->id,
                    'title' => $certificate->course->title,
                ],
                'approved_at' => $certificate->approved_at->format('Y-m-d H:i:s'),
                'approved_by' => $certificate->approvedBy?->name,
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('AdminCertificateController@approve error', [
            'error' => $e->getMessage(),
            'certificate_id' => $certificate->id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to approve certificate',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

public function reject(Request $request, $id): JsonResponse
{
    try {
        $certificate = Certificate::findOrFail($id);
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($certificate->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Certificate sudah di-reject sebelumnya'
            ], 400);
        }

        $certificate->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => null,
            'rejection_reason' => $request->reason ?? 'Ditolak oleh admin',
        ]);

        Log::info('Certificate rejected by admin', [
            'certificate_id' => $certificate->id,
            'admin_id' => Auth::id(),
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Certificate berhasil di-reject',
            'certificate' => [
                'id' => $certificate->id,
                'status' => $certificate->status,
                'rejection_reason' => $certificate->rejection_reason,
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('AdminCertificateController@reject error', [
            'error' => $e->getMessage(),
            'certificate_id' => $certificate->id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to reject certificate',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
}