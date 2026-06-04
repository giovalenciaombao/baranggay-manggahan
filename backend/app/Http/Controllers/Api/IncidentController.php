<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $incidents = Incident::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['incidents' => $incidents]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|string',
            'description' => 'required|string',
            'location'    => 'required|string',
            'photo'       => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('incidents', 'public');
        }

        $incident = Incident::create([
            'user_id'     => $request->user()->id,
            'title'       => $request->title,
            'category'    => $request->category,
            'description' => $request->description,
            'location'    => $request->location,
            'photo'       => $photoPath,
            'status'      => 'pending',
        ]);

        return response()->json([
            'message'  => 'Incident report submitted successfully.',
            'incident' => $incident,
        ], 201);
    }

    public function show(Request $request, Incident $incident)
    {
        if ($request->user()->role !== 'admin' && $incident->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['incident' => $incident->load('user')]);
    }

    // Admin only
    public function update(Request $request, Incident $incident)
    {
        $request->validate([
            'status' => 'required|in:pending,review,resolved,dismissed',
        ]);

        $incident->update(['status' => $request->status]);

        return response()->json([
            'message'  => 'Incident status updated.',
            'incident' => $incident,
        ]);
    }
    // Admin — view all incidents
    public function adminIndex()
    {
        $incidents = Incident::with('user:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json(['incidents' => $incidents]);
    }
}