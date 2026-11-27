<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationVerification extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['token_hash', 'code_hash', 'expires_at', 'attempts'];
    protected $hidden = ['token_hash', 'code_hash'];
    protected $casts = ['expires_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
