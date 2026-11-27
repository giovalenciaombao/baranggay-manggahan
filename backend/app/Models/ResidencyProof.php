<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidencyProof extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['mime_type', 'contents'];

    protected $hidden = ['contents'];

    protected $casts = ['contents' => 'encrypted'];
}
