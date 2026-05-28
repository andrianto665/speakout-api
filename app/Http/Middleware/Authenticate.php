<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * 
     * ✅ Untuk API request (Accept: application/json): return null → auto 401 JSON
     * ✅ Untuk web request (browser): return route('login') → redirect ke halaman login
     */
    protected function redirectTo(Request $request): ?string
    {
        // Jika request mengharapkan JSON (API), jangan redirect → return null
        if ($request->expectsJson()) {
            return null;
        }
        
        // Untuk web request, redirect ke route login (jika ada)
        // Gunakan route()->has() untuk cek apakah route login terdefinisi
        return $request->route()->hasParameter('login') || 
               app('router')->has('login') 
            ? route('login') 
            : null;
    }
}