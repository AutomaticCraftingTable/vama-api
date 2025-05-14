<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanAccessContent
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }


        if (!$request->user()->email_verified_at) {
            return response()->json(['error' => 'Email not verified.'], 403);
        }

        if ($request->user()->banned_at) {
            return response()->json(['error' => 'User is banned.'], 403);
        }


        return $next($request);
    }
}
