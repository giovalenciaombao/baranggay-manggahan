<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WasteRequest;
use Illuminate\Http\Request;

class WasteRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = WasteRequest::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['waste_requests' => $requests]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'  => 'required|string',
            'date'  => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        $wasteRequest = WasteRequest::create([
            'user_id' => $request->user()->id,
            'type'    => $request->type,
            'date'    => $request->date,
            'notes'   => $request->notes,
            'status'  => 'pending',
        ]);

        return response()->json([
            'message'       => 'Waste pickup request submitted successfully.',
            'waste_request' => $wasteRequest,
        ], 201);
    }

    public function show(Request $request, WasteRequest $wasteRequest)
    {
        if ($request->user()->role !== 'admin' && $wasteRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['waste_request' => $wasteRequest->load('user')]);
    }

    // Admin only
    public function update(Request $request, WasteRequest $wasteRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,scheduled,collected,missed',
        ]);

        $wasteRequest->update(['status' => $request->status]);

        return response()->json([
            'message'       => 'Waste request status updated.',
            'waste_request' => $wasteRequest,
        ]);
    }

    public function destroy(WasteRequest $wasteRequest)
    {
        $wasteRequest->delete();
        return response()->json(['message' => 'Record deleted successfully.']);
    }

    public function adminIndex()
    {
        $wasteRequests = WasteRequest::with('user:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json(['waste_requests' => $wasteRequests]);
    }
}