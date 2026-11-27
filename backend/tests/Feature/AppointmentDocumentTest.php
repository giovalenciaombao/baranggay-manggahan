<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_download_the_submitted_file_unchanged(): void
    {
        Sanctum::actingAs($this->activeUser('resident'));
        $contents = '{\rtf1\ansi Completed applicant information}';
        $booking = $this->post('/api/appointments', [
            'purpose' => 'Certificate of Residency',
            'document' => UploadedFile::fake()->createWithContent('completed.rtf', $contents),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('document_attached', true);

        $id = $booking->json('appointment.id');
        $url = "/api/admin/appointments/{$id}/document";
        $this->getJson($url)->assertForbidden();

        Sanctum::actingAs($this->activeUser('admin'));
        $this->getJson('/api/admin/appointments')
            ->assertOk()
            ->assertJsonPath('appointments.0.document_extension', 'rtf')
            ->assertJsonMissingPath('appointments.0.document');
        $this->get($url)->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=\"appointment-{$id}.rtf\"")
            ->assertContent($contents);
    }

    private function activeUser(string $role): User
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
