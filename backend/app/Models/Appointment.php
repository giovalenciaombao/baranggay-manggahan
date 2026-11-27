<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_APPROVED    = 'approved';
    public const STATUS_RESCHEDULED = 'rescheduled';
    public const STATUS_CANCELLED   = 'cancelled';
    public const STATUS_COMPLETED   = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_RESCHEDULED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    public const TIME_SLOTS = [
        '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
        '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM',
    ];

    protected $fillable = [
        'user_id', 'purpose', 'date', 'time', 'notes', 'status',
        'admin_note', 'original_date', 'original_time', 'rescheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'date'           => 'date:Y-m-d',
            'original_date'  => 'date:Y-m-d',
            'rescheduled_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->hasOne(AppointmentDocument::class);
    }
}
