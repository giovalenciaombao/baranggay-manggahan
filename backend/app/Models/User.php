<?php

namespace App\Models;
use App\Models\Alert;
use App\Models\Incident;
use App\Models\Appointment;
use App\Models\WasteRequest;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // is_super_admin is intentionally NOT fillable
    protected $fillable = [
        'first_name', 'last_name', 'email',
        'contact_number', 'address', 'password',
        'role', 'status', 'google_id', 'avatar',
        'proof_type', 'proof_path', 'proof_submitted_at',
        'rejection_reason', 'verified_by', 'verified_at',
        'privacy_consent_at',
    ];

    // proof_path stays server-side only; the file is served through an admin-only route
    protected $hidden = ['password', 'remember_token', 'proof_path', 'residency_proof_exists'];

    protected $appends = ['has_proof'];

    protected $casts = [
        'password'           => 'hashed',
        'proof_submitted_at' => 'datetime',
        'verified_at'        => 'datetime',
        'privacy_consent_at' => 'datetime',
        'is_super_admin'     => 'boolean',
    ];

    // Relations
    public function incidents()     { return $this->hasMany(Incident::class); }
    public function appointments()  { return $this->hasMany(Appointment::class); }
    public function wasteRequests() { return $this->hasMany(WasteRequest::class); }
    public function alerts()        { return $this->hasMany(Alert::class); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function verifier()      { return $this->belongsTo(User::class, 'verified_by'); }
    public function residencyProof() { return $this->hasOne(ResidencyProof::class); }

    // Helpers
    public function getFullNameAttribute() {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getHasProofAttribute(): bool {
        if ($this->proof_path) {
            return Storage::disk('local')->exists($this->proof_path);
        }

        return $this->residency_proof_exists
            ?? ($this->proof_submitted_at !== null && $this->residencyProof()->exists());
    }
}
