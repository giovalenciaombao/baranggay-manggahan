<?php

namespace App\Services;

use App\Models\RegistrationVerification;
use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class RegistrationEmail
{
    public const COOLDOWN_SECONDS = 60;
    private const EXPIRY_MINUTES = 10;

    public function open(User $user): array
    {
        $token = Str::random(64);
        $verification = $user->registrationVerification()->firstOrNew();
        $verification->token_hash = hash('sha256', $token);
        $verification->user_id = $user->id;
        $code = $this->canResend($verification) ? $this->issueCode($verification) : null;
        $verification->save();

        return [$token, $code];
    }

    public function canResend(RegistrationVerification $verification): bool
    {
        return !$verification->exists
            || $verification->expires_at->copy()->subMinutes(self::EXPIRY_MINUTES)
                ->addSeconds(self::COOLDOWN_SECONDS)->lte(now());
    }

    public function issueCode(RegistrationVerification $verification): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $verification->fill([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'attempts' => 0,
        ]);

        return $code;
    }

    public function sendCode(User $user, string $code): bool
    {
        $mailer = config('mail.default');
        if (app()->isProduction() && !in_array($mailer, ['smtp', 'brevo'], true)) {
            $this->logDeliveryFailure('A delivery mailer is not configured. Set MAIL_MAILER to brevo or smtp.');
            return false;
        }

        try {
            $body = "Your Barangay Manggahan verification code is {$code}. It expires in 10 minutes.";
            return Mail::raw($body, fn (Message $message) => $message
                ->to($user->email)->subject('Barangay Manggahan Email Verification')) !== null;
        } catch (Throwable $exception) {
            $reason = $mailer === 'brevo' && $exception instanceof TransportException
                ? $exception->getMessage()
                : $exception::class;
            $this->logDeliveryFailure($reason);
            return false;
        }
    }

    private function logDeliveryFailure(string $reason): void
    {
        $message = 'Registration code delivery failed [' . config('mail.default') . ']: ' . $reason;

        if (app()->isProduction()) {
            error_log($message);
            return;
        }

        logger()->warning($message);
    }
}
