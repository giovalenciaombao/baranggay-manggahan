<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Incident;
use App\Models\Appointment;
use App\Models\Alert;
use App\Models\Notification;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats
        $stats = [
            'total_residents'  => User::where('role', 'resident')->where('status', 'active')->count(),
            'total_incidents'  => Incident::count(),
            'total_appointments' => Appointment::count(),
            'active_alerts'    => Alert::count(),
            'pending_users'    => User::where('status', 'pending')->whereDoesntHave('registrationVerification')->count(),
            'pending_incidents'=> Incident::where('status', 'pending')->count(),
        ];

        $monthExpression = match ((new Incident)->getConnection()->getDriverName()) {
            'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            default => 'MONTH(created_at)',
        };

        $incidentsPerMonth = Incident::selectRaw("$monthExpression as month, COUNT(*) as count")
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Appointments by status
        $appointmentsByStatus = Appointment::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Pending user approvals
        $pendingUsers = User::where('status', 'pending')
            ->whereDoesntHave('registrationVerification')
            ->latest()
            ->take(5)
            ->get();

        // Recent incidents
        $recentIncidents = Incident::with('user:id,first_name,last_name')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'stats'                => $stats,
            'incidents_per_month'  => $incidentsPerMonth,
            'appointments_by_status' => $appointmentsByStatus,
            'pending_users'        => $pendingUsers,
            'recent_incidents'     => $recentIncidents,
        ]);
    }

    public function residentDashboard(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_incidents'    => Incident::where('user_id', $user->id)->count(),
            'total_appointments' => Appointment::where('user_id', $user->id)->count(),
            // Alerts this resident was notified about and has not opened yet
            'unread_alerts'      => Notification::where('user_id', $user->id)
                ->where('type', 'alert')
                ->whereNull('read_at')
                ->count(),
        ];

        // Sort by the real timestamp, then format the date for display
        $recentActivity = collect()
            ->merge(
                Incident::where('user_id', $user->id)->latest()->take(3)->get()
                    ->map(fn($i) => [
                        'id'       => $i->id,
                        'type'     => 'incident',
                        'title'    => $i->title,
                        'subtitle' => 'Incident Report',
                        'created'  => $i->created_at,
                        'status'   => $i->status,
                    ])
            )
            ->merge(
                Appointment::where('user_id', $user->id)->latest()->take(3)->get()
                    ->map(fn($a) => [
                        'id'       => $a->id,
                        'type'     => 'appointment',
                        'title'    => $a->purpose,
                        'subtitle' => 'Appointment',
                        'created'  => $a->created_at,
                        'status'   => $a->status,
                    ])
            )
            ->sortByDesc(fn($row) => $row['created']->timestamp)
            ->take(5)
            ->map(fn($row) => [
                'id'       => $row['id'],
                'type'     => $row['type'],
                'title'    => $row['title'],
                'subtitle' => $row['subtitle'],
                'date'     => $row['created']->format('M d'),
                'status'   => $row['status'],
            ])
            ->values();

        $activeAlerts = Alert::latest()->take(3)->get();

        return response()->json([
            'stats'           => $stats,
            'recent_activity' => $recentActivity,
            'active_alerts'   => $activeAlerts,
        ]);
    }
}
