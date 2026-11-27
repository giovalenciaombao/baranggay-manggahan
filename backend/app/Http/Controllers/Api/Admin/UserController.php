<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\AccountMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', 'resident')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->get();

        return response()->json(['users' => $users]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status'           => 'required|in:active,suspended,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500',
        ], [
            'rejection_reason.required_if' => 'Please give a reason for rejecting this registration.',
        ]);

        if ($user->role !== 'resident') {
            return response()->json(['message' => 'Only resident accounts can be managed here.'], 403);
        }

        if ($user->status === 'pending'
            && $request->status === 'active'
            && !$user->proof_path) {
            return response()->json([
                'message' => 'Cannot approve: this resident has not submitted proof of residency.',
            ], 422);
        }

        $data = [
            'status'           => $request->status,
            'rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
        ];

        if (in_array($request->status, ['active', 'rejected'])) {
            $data['verified_by'] = $request->user()->id;
            $data['verified_at'] = now();
        }

        $statusChanged = $user->status !== $request->status;
        $user->update($data);

        if (in_array($request->status, ['suspended', 'rejected'])) {
            $user->tokens()->delete();
        }

        $messages = [
            'active'    => 'Your account has been approved! You can now log in.',
            'suspended' => 'Your account has been suspended. Please contact the barangay office.',
            'rejected'  => 'Your account registration has been rejected.'
                . ($request->rejection_reason ? " Reason: {$request->rejection_reason}" : ''),
        ];

        $titles = [
            'active'    => 'Account Approved',
            'suspended' => 'Account Suspended',
            'rejected'  => 'Account Rejected',
        ];

        Notification::create([
            'user_id' => $user->id,
            'type'    => 'user',
            'title'   => $titles[$request->status],
            'message' => $messages[$request->status],
        ]);

        if ($statusChanged) {
            AccountMail::send($user, $titles[$request->status], $messages[$request->status]);
        }

        return response()->json([
            'message' => 'User status updated.',
            'user'    => $user->fresh(),
        ]);
    }

    // Admin-only: stream the proof file (private disk)
    public function proof(User $user)
    {
        if (!$user->proof_path || !Storage::disk('local')->exists($user->proof_path)) {
            return response()->json(['message' => 'No proof uploaded.'], 404);
        }

        return Storage::disk('local')->response($user->proof_path);
    }
}