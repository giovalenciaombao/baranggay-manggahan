<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    // Step 1 — Redirect to Google
    public function redirect()
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    // Step 2 — Handle callback from Google
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/login?error=google_failed');
        }

        // Find or create user
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Update google_id if not yet set
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            // Check if suspended or rejected
            if (in_array($user->status, ['suspended', 'rejected'])) {
                return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/login?error=account_suspended');
            }

        } else {
            // Register new user via Google
            $user = User::create([
                'first_name' => $googleUser->user['given_name']  ?? $googleUser->getName(),
                'last_name'  => $googleUser->user['family_name'] ?? '',
                'email'      => $googleUser->getEmail(),
                'google_id'  => $googleUser->getId(),
                'avatar'     => $googleUser->getAvatar(),
                'password'   => bcrypt(Str::random(24)),
                'role'       => 'resident',
                'status'     => 'pending',
            ]);

            // Redirect to pending approval if new user
            $token = $user->createToken('auth_token')->plainTextToken;
            return redirect(
                env('FRONTEND_URL', 'http://localhost:5173') .
                '/pending-approval?token=' . $token
            );
        }

        // If existing user — check if pending
        if ($user->status === 'pending') {
            $token = $user->createToken('auth_token')->plainTextToken;
            return redirect(
                env('FRONTEND_URL', 'http://localhost:5173') .
                '/pending-approval?token=' . $token
            );
        }

        // Login — generate token, redirect to frontend
        $token = $user->createToken('auth_token')->plainTextToken;

        $redirectPath = $user->role === 'admin' ? '/admin' : '/portal';

        return redirect(
            env('FRONTEND_URL', 'http://localhost:5173') .
            '/auth/callback?token=' . $token .
            '&redirect=' . $redirectPath
        );
    }
}