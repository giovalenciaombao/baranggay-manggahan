<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

        $this->register($email, $password)->assertCreated();
        $this->register($email, $password)->assertOk()
            ->assertJsonPath('message', 'Your registration is already submitted and awaiting admin approval.');
        $this->register($email, 'different-test-password')->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->register('another@example.test', $password)->assertCreated();

        $this->assertSame(2, User::count());
        $this->assertSame(1, User::where('email', $email)->count());
    }

    public function test_application_with_a_previous_otp_is_now_available_for_admin_approval(): void
    {
        config(['mail.default' => 'missing-test-mailer']);
        $response = $this->register('pending@example.test', 'test-registration-password')
            ->assertCreated()->assertJsonPath('user.status', 'pending');
        $residentId = $response->json('user.id');
        DB::table('registration_verifications')->insert([
            'user_id' => $residentId,
            'token_hash' => str_repeat('a', 64),
            'code_hash' => 'old-unused-challenge',
            'expires_at' => now(),
        ]);
        Sanctum::actingAs(User::create([
            'first_name' => 'Test', 'last_name' => 'Admin', 'email' => 'admin@example.test',
            'password' => 'test-admin-password', 'role' => 'admin', 'status' => 'active',
        ]));

        $this->getJson('/api/admin/users')->assertJsonPath('users.0.id', $residentId);
        $this->putJson("/api/admin/users/{$residentId}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('user.status', 'active');
        $this->assertDatabaseHas('notifications', ['user_id' => $residentId, 'title' => 'Account Approved']);
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
