<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'user_id', 'title', 'category',
        'description', 'location', 'photo', 'status',
    ];

    protected $appends = ['has_photo'];
    protected $hidden = ['attachment_exists'];

    public function attachment()
    {
        return $this->hasOne(IncidentPhoto::class);
    }

    public function getHasPhotoAttribute(): bool
    {
        return (bool) ($this->photo || $this->attachment_exists);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
