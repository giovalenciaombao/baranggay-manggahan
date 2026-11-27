<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AccountMail
{
    public static function send(User $user, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn (Message $message) => $message->to($user->email)->subject($subject));
        } catch (TransportExceptionInterface $exception) {
            report($exception);
        }
    }
}
