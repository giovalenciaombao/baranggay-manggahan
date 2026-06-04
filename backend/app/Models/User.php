<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'first_name', 'last_name', 'email',
        'contact_number', 'address', 'password',
        'role', 'status', 'google_id', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Relations
    public function incidents()    { return $this->hasMany(Incident::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function wasteRequests(){ return $this->hasMany(WasteRequest::class); }
    public function alerts()       { return $this->hasMany(Alert::class); }

    // Helper
    public function getFullNameAttribute() {
        return "{$this->first_name} {$this->last_name}";
    }
}