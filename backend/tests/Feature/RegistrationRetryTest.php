<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RegistrationRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_survives_mail_failure_and_can_be_retried_safely(): void
    {
        config(['mail.default' => 'missing-test-mailer']);
        $email = 'retry@example.test';
        $password = 'test-registration-password';

        $this->register($email, $password)->assertCreated();
        $this->register($email, $password)->assertOk()
            ->assertJsonPath('message', 'Your registration is already submitted and awaiting admin approval.');
        $this->register($email, 'different-test-password')->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->register('another@example.test', $password)->assertCreated();

        $this->assertSame(2, User::count());
        $this->assertSame(1, User::where('email', $email)->count());
    }

    private function register(string $email, string $password)
    {
        return $this->post('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'Resident',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'address' => 'Main Street',
            'contact_number' => '09123456789',
            'proof_type' => 'billing_statement',
            'proof' => UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n"),
            'privacy_consent' => '1',
        ], ['Accept' => 'application/json']);
    }
}
