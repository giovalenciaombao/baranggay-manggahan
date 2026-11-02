<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->is_super_admin) {
            return response()->json(['message' => 'Unauthorized. Super admin access only.'], 403);
        }

        return $next($request);
    }
}