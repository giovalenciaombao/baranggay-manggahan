<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    public function __construct(private readonly string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->apiKey === '') {
            throw new TransportException('BREVO_API_KEY is missing.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = [
            'sender' => $this->address($email->getFrom()[0]),
            'to' => array_map($this->address(...), $email->getTo()),
            'subject' => $email->getSubject(),
        ];

        if ($email->getHtmlBody() !== null) {
            $payload['htmlContent'] = $email->getHtmlBody();
        } else {
            $payload['textContent'] = $email->getTextBody();
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['api-key' => $this->apiKey])
                ->withoutRedirecting()
                ->connectTimeout(5)
                ->timeout(10)
                ->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (ConnectionException) {
            throw new TransportException('Could not connect to the Brevo email API.');
        }

        $messageId = $response->json('messageId');
        if (!$response->successful() || !is_string($messageId) || $messageId === '') {
            throw new TransportException('Brevo email API returned HTTP ' . $response->status() . '.');
        }

        $message->setMessageId($messageId);
    }

    private function address(Address $address): array
    {
        $value = ['email' => $address->getAddress()];
        if ($address->getName() !== '') {
            $value['name'] = $address->getName();
        }

        return $value;
    }

    public function __toString(): string
    {
        return 'brevo+https';
    }
}
