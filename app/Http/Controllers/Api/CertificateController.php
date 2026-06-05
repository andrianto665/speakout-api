<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    /**
     * Download certificate PDF
     * 
     * GET /api/user/certificates/{courseId}/download
     * 
     * ✅ CHANGED: Cek status certificate sebelum download
     * - approved → Generate PDF & download
     * - pending → Return error "Sedang dalam proses approval"
     * - rejected → Return error dengan alasan penolakan
     */
    public function download($courseId)
    {
        try {
            $user = Auth::user();
            
            Log::info("Certificate download request", [
                'user_id' => $user->id,
                'course_id' => $courseId
            ]);

            // 1. Cari certificate di database + load relasi
            $certificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->with(['user', 'course'])
                ->first();
            
            if (!$certificate) {
                Log::warning("Certificate not found", [
                    'user_id' => $user->id,
                    'course_id' => $courseId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate not found. Please complete all lessons first.'
                ], 404);
            }

            // 2. ✅ CEK STATUS CERTIFICATE
            if ($certificate->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate Anda sedang dalam proses approval oleh admin. Silakan tunggu hingga di-approve.',
                    'status' => $certificate->status,
                    'certificate_number' => $certificate->certificate_number
                ], 403);
            }

            if ($certificate->isRejected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate Anda ditolak oleh admin.',
                    'reason' => $certificate->rejection_reason ?? 'Tidak ada alasan diberikan.',
                    'status' => $certificate->status
                ], 403);
            }

            // 3. ✅ Hanya jika approved, lanjut generate PDF
            if (!$certificate->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate status tidak valid.'
                ], 400);
            }

            // 4. Prepare data untuk Blade template
            $verificationUrl = config('app.url') . '/verify/' . $certificate->verification_code;
            
            $data = [
                'userName' => $certificate->user->name ?? 'Student',
                'courseTitle' => $certificate->course->title ?? 'Course',
                'completedDate' => $certificate->issued_at ?? now(),
                'certificateNumber' => $certificate->certificate_number,
                'verificationCode' => $certificate->verification_code,
                'qrCode' => 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verificationUrl),
                'qrCodeType' => 'url',
                'instructorName' => $certificate->course->instructor ?? 'Instructor',
                'directorName' => 'Dr. SpeakOut',
            ];

            Log::info("Generating PDF", [
                'user' => $data['userName'],
                'course' => $data['courseTitle'],
                'cert_number' => $data['certificateNumber']
            ]);

            // 5. Generate PDF menggunakan dompdf
            $pdf = Pdf::loadView('certificates.course', $data)
                ->setPaper('a4', 'landscape')
                ->setOption('margin_top', 0)
                ->setOption('margin_bottom', 0)
                ->setOption('margin_left', 0)
                ->setOption('margin_right', 0);

            // 6. Return PDF untuk di-download
            return $pdf->download('Certificate-' . $certificate->certificate_number . '.pdf');

        } catch (\Exception $e) {
            Log::error('CertificateController@download error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get certificate status
     * 
     * GET /api/user/certificates/{courseId}/status
     * 
     * Digunakan oleh frontend untuk menampilkan status certificate
     * (apakah sudah approved, pending, atau rejected)
     */
    public function getStatus($courseId)
    {
        try {
            $user = Auth::user();
            
            $certificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->with(['approvedBy' => function($q) {
                    $q->select('id', 'name');
                }])
                ->first();
            
            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate not found',
                    'has_certificate' => false
                ], 404);
            }

            return response()->json([
                'success' => true,
                'has_certificate' => true,
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'status' => $certificate->status,
                    'issued_at' => $certificate->issued_at?->format('Y-m-d H:i:s'),
                    'approved_at' => $certificate->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $certificate->approvedBy?->name,
                    'rejection_reason' => $certificate->rejection_reason,
                    'can_download' => $certificate->isApproved(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('CertificateController@getStatus error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get certificate status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}