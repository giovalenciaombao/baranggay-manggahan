<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_can_sign_in_and_load_the_dashboard(): void
    {
        $email = 'demo-admin@example.test';
        $password = 'test-only-password-123';
        $environment = \Illuminate\Support\Env::getRepository();
        $previousEmail = $environment->get('DEMO_ADMIN_EMAIL');
        $previousPassword = $environment->get('DEMO_ADMIN_PASSWORD');
        $environment->set('DEMO_ADMIN_EMAIL', $email);
        $environment->set('DEMO_ADMIN_PASSWORD', $password);

        try {
            $this->seed(\Database\Seeders\DemoAdminSeeder::class);
        } finally {
            foreach (['DEMO_ADMIN_EMAIL' => $previousEmail, 'DEMO_ADMIN_PASSWORD' => $previousPassword] as $key => $value) {
                if ($value === null) {
                    $environment->clear($key);
                } else {
                    $environment->set($key, $value);
                }
            }
        }

        $login = $this->postJson('/api/auth/login', compact('email', 'password'))
            ->assertOk()
            ->assertJsonPath('user.role', 'admin');

        $this->withToken($login->json('token'))->getJson('/api/admin/dashboard')->assertOk();
    }
}
