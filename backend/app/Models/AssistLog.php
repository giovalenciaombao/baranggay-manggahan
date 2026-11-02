<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistLog extends Model
{
    protected $fillable = [
        'resident_id', 'staff_id', 'record_type', 'record_id', 'action',
    ];

    public function resident() { return $this->belongsTo(User::class, 'resident_id'); }
    public function staff()    { return $this->belongsTo(User::class, 'staff_id'); }
}