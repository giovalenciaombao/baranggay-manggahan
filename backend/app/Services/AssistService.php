<?php

namespace App\Services;

use App\Models\AssistLog;
use App\Models\Notification;
use App\Models\User;

class AssistService
{
    private const LABELS = [
        'appointment' => 'appointment',
        'incident'    => 'incident report',
        'waste'       => 'waste pickup request',
    ];

    /**
     * Log that $staff acted on a resident's record, and notify the
     * resident when the assisting staff is new or has changed.
     */
    public static function record(
        User $staff,
        int $residentId,
        string $recordType,
        int $recordId,
        string $action
    ): void {
        $last = AssistLog::where('resident_id', $residentId)->latest('id')->first();

        AssistLog::create([
            'resident_id' => $residentId,
            'staff_id'    => $staff->id,
            'record_type' => $recordType,
            'record_id'   => $recordId,
            'action'      => $action,
        ]);

        if ($last && $last->staff_id === $staff->id) {
            return;
        }

        $label = self::LABELS[$recordType] ?? 'request';

        if ($last) {
            $previous = User::find($last->staff_id);
            $message  = "{$staff->full_name} is now assisting you with your {$label}"
                . ($previous ? ", taking over from {$previous->full_name}." : '.');
            $title = 'Your Assisting Staff Changed';
        } else {
            $message = "{$staff->full_name} is assisting you with your {$label}.";
            $title   = 'Your Assisting Staff';
        }

        Notification::create([
            'user_id'      => $residentId,
            'type'         => 'user',
            'title'        => $title,
            'message'      => $message,
            'reference_id' => $recordId,
        ]);
    }
}