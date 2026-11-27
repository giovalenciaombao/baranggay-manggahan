<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BulkApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_approval_handles_five_hundred_residents_and_skips_ineligible_records(): void
    {
        $data = [
            'first_name' => 'Test', 'last_name' => 'Resident',
            'password' => 'unused-test-hash', 'role' => 'resident', 'status' => 'pending',
        ];
        foreach (array_chunk(range(1, 501), 100) as $batch) {
            DB::table('users')->insert(array_map(fn ($number) => [
                ...$data, 'email' => "resident{$number}@example.test",
            ], $batch));
        }
        $residents = User::orderBy('id')->pluck('id')->all();
        $eligible = array_slice($residents, 0, 500);
        $withoutProof = $residents[500];
        foreach (array_chunk($eligible, 100) as $batch) {
            DB::table('residency_proofs')->insert(array_map(fn ($id) => [
                'user_id' => $id, 'mime_type' => 'application/pdf', 'contents' => encrypt('test document', false),
            ], $batch));
        }
        $admin = User::create([...$data, 'email' => 'admin@example.test', 'role' => 'admin', 'status' => 'active']);
        $url = '/api/admin/users/bulk-approve';
        $payload = ['user_ids' => [...$residents, $admin->id]];

        Sanctum::actingAs(User::findOrFail($withoutProof));
        $this->postJson($url, $payload)->assertForbidden();
        Sanctum::actingAs($admin);
        $this->postJson($url, $payload)->assertOk()->assertJsonCount(500, 'approved_ids')
            ->assertJsonPath('skipped_count', 2);
        $this->assertSame(500, User::where('verified_by', $admin->id)->where('status', 'active')->count());
        $this->assertDatabaseHas('users', ['id' => $withoutProof, 'status' => 'pending']);
        $this->assertSame(500, Notification::where('title', 'Account Approved')->count());

        $this->postJson($url, $payload)->assertOk()->assertJsonPath('approved_ids', []);
        $this->assertSame(500, Notification::where('title', 'Account Approved')->count());
    }
}
