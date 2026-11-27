<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncidentPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_photo_remains_available_to_owner_and_admin_without_local_storage(): void
    {
        Storage::fake('public');
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lT8AAAAASUVORK5CYII=');
        Sanctum::actingAs($this->createUser('owner@example.test', 'resident'));
        $report = $this->post('/api/incidents', [
            'title' => 'Road obstruction',
            'category' => 'Safety',
            'description' => 'Debris blocks the road.',
            'location' => 'Main Street',
            'photo' => UploadedFile::fake()->createWithContent('photo.png', $image),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('incident.has_photo', true);

        Storage::disk('public')->assertDirectoryEmpty('incidents');
        $id = $report->json('incident.id');
        $this->getJson('/api/incidents/' . $id)->assertOk()->assertJsonPath('incident.has_photo', true);
        $this->get('/api/incidents/' . $id . '/photo')->assertOk()->assertContent($image);

        Sanctum::actingAs($this->createUser('other@example.test', 'resident'));
        $this->getJson('/api/incidents/' . $id . '/photo')->assertForbidden();

        Sanctum::actingAs($this->createUser('admin@example.test', 'admin'));
        $this->getJson('/api/admin/incidents')->assertOk()->assertJsonPath('incidents.0.has_photo', true);
        $this->get('/api/admin/incidents/' . $id . '/photo')
            ->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent($image);
    }

    private function createUser(string $email, string $role): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'test-incident-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
