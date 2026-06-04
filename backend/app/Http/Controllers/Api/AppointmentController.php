<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'purpose' => 'required|string|max:255',
            'date'    => 'required|date|after_or_equal:today',
            'time'    => 'required|string',
            'notes'   => 'nullable|string',
        ]);

        $appointment = Appointment::create([
            'user_id' => $request->user()->id,
            'purpose' => $request->purpose,
            'date'    => $request->date,
            'time'    => $request->time,
            'notes'   => $request->notes,
            'status'  => 'pending',
        ]);

        return response()->json([
            'message'     => 'Appointment booked successfully.',
            'appointment' => $appointment,
        ], 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($request->user()->role !== 'admin' && $appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['appointment' => $appointment->load('user')]);
    }

    // Admin only
    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,cancelled,completed',
        ]);

        $appointment->update(['status' => $request->status]);

        $labels = [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ];

        \App\Models\Notification::create([
            'user_id'      => $appointment->user_id,
            'type'         => 'appointment',
            'title'        => 'Appointment Updated',
            'message'      => "Your appointment for \"{$appointment->purpose}\" is now {$labels[$request->status]}.",
            'reference_id' => $appointment->id,
        ]);

        return response()->json([
            'message'     => 'Appointment status updated.',
            'appointment' => $appointment,
        ]);
    }

    public function adminIndex()
    {
        $appointments = Appointment::with('user:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json(['appointments' => $appointments]);
    }
}