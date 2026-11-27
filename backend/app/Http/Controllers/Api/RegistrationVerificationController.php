<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationVerification;
use App\Models\User;
use App\Services\AccountMail;
use App\Services\RegistrationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Illuminate\Support\defer;

class RegistrationVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $input = $request->validate([
            'token' => 'required|string|size:64',
            'code' => 'required|string|regex:/^[0-9]{6}$/',
        ]);

        $result = DB::transaction(function () use ($input) {
            $verification = $this->lookup($input['token']);
            if (!$verification) return 'This verification session has expired. Submit the registration form again.';
            if ($verification->expires_at->lte(now())) return 'This code has expired. Request a new code.';
            if ($verification->attempts >= 5) return 'Too many incorrect attempts. Request a new code.';

            if (!Hash::check($input['code'], $verification->code_hash)) {
                $verification->increment('attempts');
                return 'That code is incorrect.';
            }

            $user = $verification->user;
            $verification->delete();
            return $user;
        });

        if (!$result instanceof User) {
            return response()->json(['message' => $result], 422);
        }

        defer(fn () => rescue(fn () => AccountMail::send(
            $result,
            'Barangay Manggahan Registration Received',
            'Your email is verified and your registration has been submitted for barangay review.',
        )));

        return response()->json(['message' => 'Email verified. Your application is awaiting admin approval.']);
    }

    public function resend(Request $request, RegistrationEmail $registrationEmail)
    {
        $input = $request->validate(['token' => 'required|string|size:64']);

        [$verification, $code] = DB::transaction(function () use ($input, $registrationEmail) {
            $verification = $this->lookup($input['token']);
            if (!$verification || !$registrationEmail->canResend($verification)) {
                return [$verification, null];
            }

            $code = $registrationEmail->issueCode($verification);
            $verification->save();
            return [$verification, $code];
        });

        if (!$verification) {
            return response()->json(['message' => 'This verification session has expired. Submit the registration form again.'], 422);
        }

        if ($code === null) {
            return response()->json(['message' => 'Wait one minute before requesting another code.'], 429);
        }

        if (!$registrationEmail->sendCode($verification->user, $code)) {
            return response()->json(['message' => 'The email service could not send the code. Try again in one minute.'], 503);
        }

        return response()->json(['message' => 'A new verification code was sent.']);
    }

    private function lookup(string $token): ?RegistrationVerification
    {
        return RegistrationVerification::where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
    }
}
