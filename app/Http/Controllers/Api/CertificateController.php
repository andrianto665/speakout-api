<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf; // ✅ Import dompdf facade

class CertificateController extends Controller
{
    /**
     * Download certificate PDF (Generate on-the-fly with dompdf)
     * 
     * GET /api/user/certificates/{courseId}/download
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
                    'message' => 'Certificate not found. Please complete all lessons first.'
                ], 404);
            }

            // 2. Prepare data untuk Blade template
            $verificationUrl = config('app.url') . '/verify/' . $certificate->verification_code;
            
            $data = [
                'userName' => $certificate->user->name ?? 'Student',
                'courseTitle' => $certificate->course->title ?? 'Course',
                'completedDate' => $certificate->issued_at ?? now(),
                'certificateNumber' => $certificate->certificate_number,
                'verificationCode' => $certificate->verification_code,
                'qrCode' => 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verificationUrl),
                'qrCodeType' => 'url',
                'instructorName' => $certificate->course->instructor ?? 'Krisna Islami',
                'directorName' => 'Dr. SpeakOut',
            ];

            Log::info("Generating PDF", [
                'user' => $data['userName'],
                'course' => $data['courseTitle'],
                'cert_number' => $data['certificateNumber']
            ]);

            // 3. Generate PDF menggunakan dompdf
            $pdf = Pdf::loadView('certificates.course', $data)
                ->setPaper('a4', 'landscape')
                ->setOption('margin_top', 0)
                ->setOption('margin_bottom', 0)
                ->setOption('margin_left', 0)
                ->setOption('margin_right', 0);

            // 4. Return PDF untuk di-download
            return $pdf->download('Certificate-' . $certificate->certificate_number . '.pdf');

        } catch (\Exception $e) {
            Log::error('CertificateController@download error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to generate certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}