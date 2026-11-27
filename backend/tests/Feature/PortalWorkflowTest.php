<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_assigns_the_next_unoccupied_weekday_slot(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 15:30', 'Asia/Manila'));
        Sanctum::actingAs($this->createActiveUser('resident'));

        $this->postJson('/api/appointments', ['purpose' => 'Barangay Clearance'])
            ->assertCreated()
            ->assertJsonPath('appointment.date', '2026-11-27')
            ->assertJsonPath('appointment.time', '4:00 PM')
            ->assertJsonPath('appointment.status', 'pending');

        $this->postJson('/api/appointments', ['purpose' => 'Barangay Clearance'])
            ->assertCreated()
            ->assertJsonPath('appointment.date', '2026-11-30')
            ->assertJsonPath('appointment.time', '8:00 AM');
    }

    public function test_admin_can_view_a_stored_incident_photo(): void
    {
        Storage::fake('public');
        $resident = $this->createActiveUser('resident');
        Sanctum::actingAs($resident);

        $incident = Incident::create([
            'user_id' => $resident->id,
            'title' => 'Road obstruction',
            'category' => 'Safety',
            'description' => 'Debris blocks the road.',
            'location' => 'Main Street',
            'photo' => 'incidents/photo.png',
            'status' => 'pending',
        ]);
        Storage::disk('public')->put($incident->photo, 'stored photo bytes');
        $url = "/api/admin/incidents/{$incident->id}/photo";

        $this->getJson($url)->assertForbidden();

        Sanctum::actingAs($this->createActiveUser('admin'));
        $response = $this->get($url)->assertOk();
        $this->assertSame('stored photo bytes', $response->streamedContent());
    }

    public function test_approval_sends_email_after_registration(): void
    {
        Storage::fake('local');
        $this->post('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'Resident',
            'email' => 'resident@example.test',
            'password' => 'sample-password',
            'password_confirmation' => 'sample-password',
            'address' => 'Main Street',
            'contact_number' => '09123456789',
            'proof_type' => 'billing_statement',
            'proof' => UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n"),
            'privacy_consent' => '1',
        ], ['Accept' => 'application/json'])->assertCreated();

        $resident = User::where('email', 'resident@example.test')->firstOrFail();
        Sanctum::actingAs($this->createActiveUser('admin'));
        $this->putJson("/api/admin/users/{$resident->id}/status", ['status' => 'active'])->assertOk();

        $subjects = Mail::getSymfonyTransport()->messages()
            ->map(fn ($mail) => $mail->getOriginalMessage()->getSubject())
            ->all();

        $this->assertSame(['Account Approved'], $subjects);
    }

    private function createActiveUser(string $role): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => ucfirst($role),
            'email' => $role . '@example.test',
            'password' => 'sample-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
