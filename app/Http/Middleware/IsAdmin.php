<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // ✅ Cek apakah user login DAN punya role 'admin'
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Access denied. Admin privileges required.'], 403);
        }
        
        return $next($request);
    }
}