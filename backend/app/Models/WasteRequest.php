<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasteRequest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'date', 'notes', 'status',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}