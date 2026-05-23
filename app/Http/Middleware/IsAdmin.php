<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user sudah login DAN punya role 'admin'
        // Pastikan kolom 'role' sudah ada di tabel users
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request); // Lanjutkan request
        }
        
        // Jika bukan admin, tolak dengan error 403
        return response()->json([
            'message' => 'Access denied. Admin privileges required.'
        ], 403);
    }
}