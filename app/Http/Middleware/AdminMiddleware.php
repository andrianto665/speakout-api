<?php
// File: app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek apakah user sudah login (punya token Sanctum)
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // 2. Cek apakah user punya role 'admin'
        // Sesuai hasil cek: kolom 'role' dengan enum('user','admin')
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }
        
        // 3. Jika lolos, lanjutkan request
        return $next($request);
    }
}