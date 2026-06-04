<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    // Public — all residents can view
    public function index()
    {
        $alerts = Alert::with('user:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json(['alerts' => $alerts]);
    }

    public function show(Alert $alert)
    {
        return response()->json(['alert' => $alert->load('user:id,first_name,last_name')]);
    }

    // Admin only
    public function store(Request $request)
    {
        $request->validate([
            'type'    => 'required|in:emergency,announcement,maintenance',
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $alert = Alert::create([
            'user_id' => $request->user()->id,
            'type'    => $request->type,
            'title'   => $request->title,
            'message' => $request->message,
        ]);

        // Notify all active residents
        $residents = \App\Models\User::where('role', 'resident')
            ->where('status', 'active')
            ->get();

        $typeLabels = [
            'emergency'    => 'Emergency Alert',
            'announcement' => 'New Announcement',
            'maintenance'  => 'Maintenance Notice',
        ];

        foreach ($residents as $resident) {
            \App\Models\Notification::create([
                'user_id'      => $resident->id,
                'type'         => 'alert',
                'title'        => $typeLabels[$request->type] ?? 'New Alert',
                'message'      => $alert->title . ' — ' . $alert->message,
                'reference_id' => $alert->id,
            ]);
        }

        return response()->json([
            'message' => 'Alert posted successfully.',
            'alert'   => $alert,
        ], 201);
    }

    public function update(Request $request, Alert $alert)
    {
        $request->validate([
            'type'    => 'required|in:emergency,announcement,maintenance',
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $alert->update($request->only(['type', 'title', 'message']));

        return response()->json([
            'message' => 'Alert updated successfully.',
            'alert'   => $alert,
        ]);
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();
        return response()->json(['message' => 'Alert deleted successfully.']);
    }
}