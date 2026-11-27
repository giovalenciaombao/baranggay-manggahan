<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentPhoto extends Model
{
    protected $primaryKey = 'incident_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['mime_type', 'contents'];
    protected $hidden = ['contents'];
    protected $casts = ['contents' => 'encrypted'];
}
