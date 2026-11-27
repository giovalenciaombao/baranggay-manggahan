<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrationRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_survives_mail_failure_and_can_be_retried_safely(): void
    {
        config(['mail.default' => 'missing-test-mailer']);
        $email = 'retry@example.test';
        $password = 'test-registration-password';

        $this->register($email, $password)->assertAccepted()->assertJsonPath('code_sent', false);
        $retry = $this->register($email, $password)->assertAccepted()->assertJsonPath('code_sent', null);
        $this->register($email, 'different-test-password')->assertUnprocessable()->assertJsonValidationErrors('email');
        $token = $retry->json('verification_token');
        $this->postJson('/api/auth/register/resend', ['token' => $token])->assertTooManyRequests();

        $this->travel(61)->seconds();
        config([
            'mail.default' => 'brevo',
            'mail.mailers.brevo.key' => 'test-api-key',
            'mail.from.address' => 'sender@example.test',
        ]);
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'test-id'], 201)]);
        $this->postJson('/api/auth/register/resend', ['token' => $token])->assertOk();
        Http::assertSent(fn (Request $request) => $request->hasHeader('api-key', 'test-api-key')
            && $request['to'][0]['email'] === $email
            && $request['sender']['email'] === 'sender@example.test'
            && $request['subject'] === 'Barangay Manggahan Email Verification');
        $body = Http::recorded()->first()[0]['textContent'];
        config(['mail.default' => 'array']);
        preg_match('/\b[0-9]{6}\b/', $body, $matches);
        $wrong = $matches[0] === '111111' ? '222222' : '111111';
        $this->postJson('/api/auth/register/verify', ['token' => $token, 'code' => $wrong])->assertUnprocessable();
        $this->postJson('/api/auth/login', compact('email', 'password'))->assertForbidden();

        $resident = User::where('email', $email)->firstOrFail();
        Sanctum::actingAs(User::create([
            'first_name' => 'Test', 'last_name' => 'Admin', 'email' => 'admin@example.test',
            'password' => 'test-admin-password', 'role' => 'admin', 'status' => 'active',
        ]));
        $this->getJson('/api/admin/users')->assertJsonCount(0, 'users');
        $this->putJson("/api/admin/users/{$resident->id}/status", ['status' => 'active'])->assertUnprocessable();
        $this->postJson('/api/auth/register/verify', ['token' => $token, 'code' => $matches[0]])->assertOk();
        $this->getJson('/api/admin/users')->assertJsonPath('users.0.id', $resident->id);
        $this->postJson('/api/auth/register/verify', ['token' => $token, 'code' => $matches[0]])->assertUnprocessable();

        config(['mail.default' => 'missing-test-mailer']);
        $this->putJson("/api/admin/users/{$resident->id}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('user.status', 'active');
        $this->assertDatabaseHas('notifications', ['user_id' => $resident->id, 'title' => 'Account Approved']);

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
