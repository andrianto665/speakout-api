<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    /**
     * Get certificate status for a course
     */
    public function getStatus($courseId)
    {
        try {
            $user = Auth::user();
            
            $certificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->with(['user', 'course', 'approvedBy'])
                ->first();
            
            if (!$certificate) {
                return response()->json([
                    'has_certificate' => false,
                    'certificate' => null
                ]);
            }
            
            return response()->json([
                'has_certificate' => true,
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'verification_code' => $certificate->verification_code,
                    'status' => $certificate->status,
                    'issued_at' => $certificate->issued_at ? $certificate->issued_at->toDateTimeString() : null,
                    'approved_at' => $certificate->approved_at?->toDateTimeString(),
                    'approved_by' => $certificate->approvedBy?->name,
                    'rejection_reason' => $certificate->rejection_reason,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil status sertifikat'
            ], 500);
        }
    }
    
    /**
     * Download certificate as PDF
     */
    public function download($courseId)
    {
        try {
            $user = Auth::user();
            
            $certificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->with(['user', 'course', 'approvedBy'])
                ->first();
            
            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sertifikat tidak ditemukan'
                ], 404);
            }
            
            if (!$certificate->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sertifikat belum di-approve'
                ], 403);
            }
            
            \Log::info('Generating PDF', [
                'user' => $certificate->user->name,
                'course' => $certificate->course->title,
                'cert_number' => $certificate->certificate_number
            ]);
            
            // Load view
            $pdf = Pdf::loadView('certificates.certificate', [
                'certificate' => $certificate,
                'user' => $certificate->user,
                'course' => $certificate->course,
                'admin' => $certificate->approvedBy,
            ]);
            
            $pdf->setPaper('A4', 'landscape');
            
            $filename = "Certificate-{$certificate->certificate_number}.pdf";
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('Certificate download error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Admin: Approve certificate
     */
    public function approve($id)
    {
        try {
            $admin = Auth::user();
            
            $certificate = Certificate::findOrFail($id);
            
            if ($certificate->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Sertifikat sudah di-approve'
                ], 400);
            }
            
            // Auto-generate nomor jika belum ada
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = Certificate::generateCertificateNumber();
            }
            
            $certificate->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'issued_at' => now(),
                'rejection_reason' => null,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sertifikat berhasil di-approve'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve sertifikat'
            ], 500);
        }
    }
    
    /**
     * Admin: Reject certificate
     */
    public function reject($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|min:10'
            ]);
            
            $certificate = Certificate::findOrFail($id);
            
            $certificate->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['reason'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sertifikat ditolak'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal reject sertifikat'
            ], 500);
        }
    }
}