<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * 
     * For API requests: return null → Laravel will return 401 JSON response
     * For web requests: return route('login') → redirect to login page
     */
    protected function redirectTo(Request $request): ?string
    {
        // ✅ Jika request expects JSON (API), return null → auto 401 JSON response
        // ✅ Jika request browser (web), return route login
        return $request->expectsJson() ? null : route('login');
    }
}