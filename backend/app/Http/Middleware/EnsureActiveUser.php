<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (in_array($user->status, ['suspended', 'rejected'], true)) {
            return response()->json(['message' => 'Your account is no longer active.'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is pending approval.'], 403);
        }

        return $next($request);
    }
}