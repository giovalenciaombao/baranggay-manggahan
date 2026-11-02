<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:users',
            'contact_number'  => 'required|string',
            'address'         => 'required|string',
            'password'        => 'required|min:8|confirmed',
            'proof_type'      => 'required|in:barangay_id,billing_statement',
            'proof'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'privacy_consent' => 'accepted',
        ], [
            'proof.required'            => 'Please upload your proof of residency.',
            'proof.mimes'               => 'Proof must be a JPG, PNG or PDF file.',
            'proof.max'                 => 'Proof must not be larger than 5 MB.',
            'privacy_consent.accepted'  => 'Kailangan mong pumayag sa data privacy notice para magpatuloy.',
        ]);

        // Private disk (storage/app/private) - NOT publicly accessible
        $path = $request->file('proof')->store('proofs', 'local');

        $user = User::create([
            'first_name'         => $request->first_name,
            'last_name'          => $request->last_name,
            'email'              => $request->email,
            'contact_number'     => $request->contact_number,
            'address'            => $request->address,
            'password'           => $request->password,
            'role'               => 'resident',
            'status'             => 'pending',
            'proof_type'         => $request->proof_type,
            'proof_path'         => $path,
            'proof_submitted_at' => now(),
            'privacy_consent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Registration successful. Please wait for admin approval.',
            'user'    => $user,
        ], 201);
    }

    // For pending users who signed up via Google (no proof yet)
    public function uploadProof(Request $request)
    {
        $user = $request->user();

        if ($user->status !== 'pending') {
            return response()->json([
                'message' => 'Proof can only be submitted while your account is pending.',
            ], 403);
        }

        $request->validate([
            'proof_type'      => 'required|in:barangay_id,billing_statement',
            'proof'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'privacy_consent' => 'accepted',
        ], [
            'proof.required'            => 'Please upload your proof of residency.',
            'proof.mimes'               => 'Proof must be a JPG, PNG or PDF file.',
            'proof.max'                 => 'Proof must not be larger than 5 MB.',
            'privacy_consent.accepted'  => 'Kailangan mong pumayag sa data privacy notice para magpatuloy.',
        ]);

        if ($user->proof_path) {
            Storage::disk('local')->delete($user->proof_path);
        }

        $path = $request->file('proof')->store('proofs', 'local');

        $user->update([
            'proof_type'         => $request->proof_type,
            'proof_path'         => $path,
            'proof_submitted_at' => now(),
            'privacy_consent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Proof submitted. Please wait for admin review.',
            'user'    => $user->fresh(),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status === 'pending') {
            return response()->json(['message' => 'Your account is pending approval.'], 403);
        }

        if ($user->status === 'suspended' || $user->status === 'rejected') {
            return response()->json(['message' => 'Your account has been suspended or rejected.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'contact_number' => 'required|string',
            'address'        => 'required|string',
        ]);

        $user->update($request->only([
            'first_name', 'last_name', 'email', 'contact_number', 'address'
        ]));

        return response()->json(['user' => $user]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}