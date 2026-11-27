<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('DEMO_ADMIN_EMAIL');
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->role !== 'admin' || !$existingUser->is_super_admin) {
                throw new RuntimeException('DEMO_ADMIN_EMAIL belongs to an existing account.');
            }

            if (!filter_var(env('DEMO_ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOLEAN)) {
                return;
            }
        }

        $credentials = Validator::validate([
            'email' => $email,
            'password' => env('DEMO_ADMIN_PASSWORD'),
        ], [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:16', 'max:72'],
        ]);

        if ($existingUser) {
            if (!Hash::check($credentials['password'], $existingUser->password)) {
                DB::transaction(function () use ($existingUser, $credentials): void {
                    $existingUser->update(['password' => $credentials['password']]);
                    $existingUser->tokens()->delete();
                });
                $this->command?->info('Demo super admin password reset.');
            }

            return;
        }

        $admin = new User([
            ...$credentials,
            'first_name' => 'Demo',
            'last_name' => 'Administrator',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->is_super_admin = true;
        $admin->save();
    }
}
