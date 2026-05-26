<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    /**
     * Download certificate PDF
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

            // 1. Cari certificate di database
            $certificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $courseId)
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

            // 2. Cek apakah file PDF ada
            $filePath = $certificate->file_path;
            Log::info("Checking certificate file", ['path' => $filePath]);
            
            if (!Storage::disk('public')->exists($filePath)) {
                Log::error("Certificate file not found on disk", ['path' => $filePath]);
                
                return response()->json([
                    'message' => 'Certificate file not found on server.'
                ], 404);
            }

            // 3. Download file
            $fullPath = Storage::disk('public')->path($filePath);
            Log::info("Downloading certificate", ['full_path' => $fullPath]);
            
            return response()->download($fullPath, "{$certificate->certificate_number}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            Log::error('CertificateController@download error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to download certificate.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}