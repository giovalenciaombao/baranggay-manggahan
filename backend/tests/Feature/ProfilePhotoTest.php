<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_upload_and_replace_only_their_own_photo(): void
    {
        $url = '/api/auth/profile/photo';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lT8AAAAASUVORK5CYII=');

        $this->getJson($url)->assertUnauthorized();

        Sanctum::actingAs($this->resident('first@example.test'));
        $this->getJson($url)->assertNoContent();
        $this->postJson($url, ['photo' => 'not an upload'])->assertUnprocessable();

        foreach ([$png, $png . 'replacement'] as $image) {
            $this->post($url, [
                'photo' => UploadedFile::fake()->createWithContent('avatar.png', $image),
            ], ['Accept' => 'application/json'])->assertNoContent();
            $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent($image);
        }

        Sanctum::actingAs($this->resident('second@example.test'));
        $this->getJson($url)->assertNoContent();
    }

    private function resident(string $email): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'Resident',
            'email' => $email,
            'password' => 'test-resident-password',
            'role' => 'resident',
            'status' => 'active',
        ]);
    }
}
