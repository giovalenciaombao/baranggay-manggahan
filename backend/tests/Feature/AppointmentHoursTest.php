<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_reschedule_outside_future_weekday_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 16:30', 'Asia/Manila'));
        Sanctum::actingAs($this->activeUser('admin'));
        $appointment = Appointment::create([
            'user_id' => $this->activeUser('resident')->id,
            'purpose' => 'Barangay Clearance',
            'date' => '2026-11-30',
            'time' => '9:00 AM',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $payload = ['status' => 'rescheduled', 'admin_note' => 'Staff schedule changed.'];
        $url = "/api/appointments/{$appointment->id}";
        foreach (['2026-11-28', '2026-11-29'] as $date) {
            $this->getJson("/api/appointments/availability?date={$date}")
                ->assertOk()->assertJsonPath('slots', []);
            $this->putJson($url, $payload + ['date' => $date, 'time' => '8:00 AM'])
                ->assertUnprocessable()->assertJsonValidationErrors('date');
        }
        $this->putJson($url, $payload + ['date' => '2026-11-27', 'time' => '4:00 PM'])
            ->assertUnprocessable()->assertJsonValidationErrors('time');
        $this->putJson($url, $payload + ['date' => '2026-11-30', 'time' => '5:00 PM'])
            ->assertUnprocessable()->assertJsonValidationErrors('time');
        $this->putJson($url, $payload + ['date' => '2026-11-30', 'time' => '4:00 PM'])
            ->assertOk()->assertJsonPath('appointment.time', '4:00 PM');
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
