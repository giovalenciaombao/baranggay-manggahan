<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentDocument extends Model
{
    protected $primaryKey = 'appointment_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['extension', 'contents'];
    protected $hidden = ['contents'];
}
