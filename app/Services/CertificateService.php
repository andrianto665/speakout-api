<?php
/**
 * Certificate Service
 * 
 * Handles generation and management of course completion certificates.
 * 
 * @package App\Services
 * @author SpeakOut Team
 */

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    /**
     * Generate and save certificate PDF for a completed course
     * 
     * @param  \App\Models\User  $user
     * @param  \App\Models\Course  $course
     * @return \App\Models\Certificate
     */
    public function generateCertificate(User $user, Course $course): Certificate
    {
        // Generate unique identifiers
        $certificateNumber = Certificate::generateCertificateNumber();
        $verificationCode = Certificate::generateVerificationCode();
        
        // ✅ EXTERNAL QR API: Generate QR code URL (no GD/imagick needed!)
        $verifyUrl = url("/verify/{$verificationCode}");
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verifyUrl);
        
        // Render certificate blade view to HTML
        // We pass the QR code URL directly (not base64)
        $html = view('certificates.course', [
            'userName' => $user->name,
            'courseTitle' => $course->title,
            'completedDate' => now(),
            'qrCode' => $qrCodeUrl,  // ← URL string, bukan base64
            'qrCodeType' => 'url',    // ← Flag untuk view: 'url' atau 'base64'
            'verificationCode' => $verificationCode,
            'certificateNumber' => $certificateNumber,
        ])->render();
        
        // Generate PDF with A4 landscape orientation
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('margin_top', 0)
            ->setOption('margin_bottom', 0);
        
        // Create and return certificate record (PDF generated on-the-fly)
        return Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_number' => $certificateNumber,
            'verification_code' => $verificationCode,
            'issued_at' => now(),
        ]);
    }
    
    /**
     * Get public download URL for a certificate
     * 
     * @param  \App\Models\Certificate  $certificate
     * @return string
     */
    public function getDownloadUrl(Certificate $certificate): string
    {
        return url("/api/user/certificates/{$certificate->course_id}/download");
    }
    
    /**
     * Verify certificate by verification code
     * 
     * @param  string  $code
     * @return array|null Certificate data if valid, null if invalid
     */
    public function verifyCertificate(string $code): ?array
    {
        $certificate = Certificate::where('verification_code', $code)
            ->with(['user', 'course'])
            ->first();
        
        if (!$certificate) {
            return null;
        }
        
        return [
            'valid' => true,
            'user_name' => $certificate->user->name,
            'course_title' => $certificate->course->title,
            'issued_at' => $certificate->issued_at->format('F j, Y'),
            'certificate_number' => $certificate->certificate_number,
        ];
    }
}