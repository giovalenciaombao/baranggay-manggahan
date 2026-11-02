<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        $platform = request('platform', 'web');

        $url = Socialite::driver('google')
            ->stateless()
            ->with(['state' => $platform])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function callback()
    {
        $platform = request('state', 'web');

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return redirect($this->frontendRedirect($platform, '/auth/callback?error=google_failed'));
        }

        $raw = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        if (!filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return redirect($this->frontendRedirect($platform, '/auth/callback?error=google_unverified'));
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if ($user->role === 'admin') {
                return redirect($this->frontendRedirect($platform, '/auth/callback?error=admin_use_password'));
            }

            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            if (in_array($user->status, ['suspended', 'rejected'])) {
                return redirect($this->frontendRedirect($platform, '/auth/callback?error=account_suspended'));
            }

        } else {
            $user = User::create([
                'first_name' => $raw['given_name']  ?? $googleUser->getName(),
                'last_name'  => $raw['family_name'] ?? '',
                'email'      => $googleUser->getEmail(),
                'google_id'  => $googleUser->getId(),
                'avatar'     => $googleUser->getAvatar(),
                'password'   => bcrypt(Str::random(24)),
                'role'       => 'resident',
                'status'     => 'pending',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
            return redirect($this->frontendRedirect(
                $platform,
                '/pending-approval?token=' . $token
            ));
        }

        if ($user->status === 'pending') {
            $token = $user->createToken('auth_token')->plainTextToken;
            return redirect($this->frontendRedirect(
                $platform,
                '/pending-approval?token=' . $token
            ));
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect($this->frontendRedirect(
            $platform,
            '/auth/callback?token=' . $token . '&redirect=/portal'
        ));
    }

    private function frontendRedirect(string $platform, string $path): string
    {
        if ($platform === 'native') {
            return 'barangaymanggahan://auth' . $path;
        }

        return env('FRONTEND_URL', 'http://localhost:5173') . $path;
    }
}