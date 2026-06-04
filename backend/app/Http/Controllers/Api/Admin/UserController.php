<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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
            'status' => 'required|in:active,suspended,rejected',
        ]);

        $user->update(['status' => $request->status]);

        $messages = [
            'active'    => 'Your account has been approved! You can now log in.',
            'suspended' => 'Your account has been suspended. Please contact the barangay office.',
            'rejected'  => 'Your account registration has been rejected.',
        ];

        $titles = [
            'active'    => 'Account Approved',
            'suspended' => 'Account Suspended',
            'rejected'  => 'Account Rejected',
        ];

        \App\Models\Notification::create([
            'user_id' => $user->id,
            'type'    => 'user',
            'title'   => $titles[$request->status],
            'message' => $messages[$request->status],
        ]);

        return response()->json([
            'message' => 'User status updated.',
            'user'    => $user,
        ]);
    }
}