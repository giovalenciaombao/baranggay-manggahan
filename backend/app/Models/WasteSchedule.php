<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasteSchedule extends Model
{
    protected $fillable = [
        'created_by', 'waste_type', 'collection_date',
        'collection_time', 'area', 'notes',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}