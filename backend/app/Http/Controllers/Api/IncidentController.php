<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\AssistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $incidents = Incident::withExists('attachment')->where('user_id', $request->user()->id)
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

        $incident = DB::transaction(function () use ($request) {
            $incident = Incident::create([
                ...$request->only('title', 'category', 'description', 'location'),
                'user_id' => $request->user()->id,
                'status'  => 'pending',
            ]);

            if ($file = $request->file('photo')) {
                $incident->attachment()->create([
                    'mime_type' => $file->getMimeType(),
                    'contents' => $file->get(),
                ]);
            }

            return $incident->loadExists('attachment');
        });

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

        return response()->json(['incident' => $incident->load('user')->loadExists('attachment')]);
    }

    public function photo(Request $request, Incident $incident)
    {
        abort_if($request->user()->role !== 'admin' && $incident->user_id !== $request->user()->id, 403);

        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

        if ($attachment = $incident->attachment()->first()) {
            return response($attachment->contents, 200, [
                ...$headers,
                'Content-Type' => $attachment->mime_type,
            ]);
        }

        $disk = Storage::disk('public');
        abort_unless($incident->photo && $disk->exists($incident->photo), 404);

        return $disk->response($incident->photo, null, $headers);
    }

    // Admin only
    public function update(Request $request, Incident $incident)
    {
        $request->validate([
            'status' => 'required|in:pending,review,resolved,dismissed',
        ]);

        $incident->update(['status' => $request->status]);

        // Tell the resident who is assisting them (only if new / changed)
        AssistService::record($request->user(), $incident->user_id, 'incident', $incident->id, $request->status);

        // Status labels
        $labels = [
            'pending'   => 'Pending',
            'review'    => 'Under Review',
            'resolved'  => 'Resolved',
            'dismissed' => 'Dismissed',
        ];

        // Create notification
        \App\Models\Notification::create([
            'user_id'      => $incident->user_id,
            'type'         => 'incident',
            'title'        => 'Incident Report Updated',
            'message'      => "Your incident \"{$incident->title}\" is now {$labels[$request->status]}.",
            'reference_id' => $incident->id,
        ]);

        return response()->json([
            'message'  => 'Incident status updated.',
            'incident' => $incident->loadExists('attachment'),
        ]);
    }

    // Admin - view all incidents
    public function adminIndex()
    {
        $incidents = Incident::with('user:id,first_name,last_name')->withExists('attachment')
            ->latest()
            ->get();

        return response()->json(['incidents' => $incidents]);
    }
}
