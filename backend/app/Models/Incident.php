<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'user_id', 'title', 'category',
        'description', 'location', 'photo', 'status',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}