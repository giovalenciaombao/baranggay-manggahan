<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidencyProofTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_resident_can_replace_a_missing_file_and_admin_can_read_it(): void
    {
        Storage::fake('local');
        $resident = User::create([
            'first_name' => 'Test',
            'last_name' => 'Resident',
            'email' => 'resident@example.test',
            'password' => 'test-resident-password',
            'role' => 'resident',
            'status' => 'pending',
            'proof_path' => 'proofs/missing.pdf',
            'proof_submitted_at' => now(),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $resident->email,
            'password' => 'test-resident-password',
        ])->assertOk()->assertJsonPath('user.has_proof', false);

        $pdf = "%PDF-1.4\nSample proof\n";
        $this->withToken($login->json('token'))->post('/api/auth/proof', [
            'proof_type' => 'billing_statement',
            'proof' => UploadedFile::fake()->createWithContent('proof.pdf', $pdf),
            'privacy_consent' => '1',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('user.has_proof', true);

        $stored = DB::table('residency_proofs')->where('user_id', $resident->id)->value('contents');
        $this->assertNotEmpty($stored);
        $this->assertStringNotContainsString('Sample proof', $stored);

        $url = "/api/admin/users/{$resident->id}/proof";
        $this->getJson($url)->assertForbidden();
        Sanctum::actingAs(User::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'test-admin-password',
            'role' => 'admin',
            'status' => 'active',
        ]));
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertContent($pdf);
    }
}
