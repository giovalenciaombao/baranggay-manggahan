<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::where('role', 'admin')
            ->orderByDesc('is_super_admin')
            ->orderBy('first_name')
            ->get();

        return response()->json(['staff' => $staff]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email',
            'contact_number' => 'required|string|max:50',
            'password'       => 'required|string|min:8|max:100',
        ]);

        $staff = User::create([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'email'          => $data['email'],
            'contact_number' => $data['contact_number'],
            'address'        => 'Barangay Hall',
            'password'       => $data['password'],
            'role'           => 'admin',
            'status'         => 'active',
        ]);

        return response()->json([
            'message' => 'Staff account created.',
            'staff'   => $staff,
        ], 201);
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:active,suspended']);

        if ($error = $this->guard($request, $user)) {
            return $error;
        }

        $user->update(['status' => $request->status]);

        if ($request->status === 'suspended') {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $request->status === 'active' ? 'Staff reactivated.' : 'Staff deactivated.',
            'staff'   => $user->fresh(),
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:8|max:100']);

        if ($error = $this->guard($request, $user)) {
            return $error;
        }

        $user->update(['password' => $request->password]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset. The staff must sign in again.']);
    }

    // Only normal staff can be modified, never yourself or a super admin
    private function guard(Request $request, User $user)
    {
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Not a staff account.'], 404);
        }
        if ($user->is_super_admin) {
            return response()->json(['message' => 'The super admin account cannot be changed here.'], 403);
        }
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot change your own account here.'], 403);
        }
        return null;
    }
}