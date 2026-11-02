<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistLog;
use App\Models\Appointment;
use App\Models\Incident;
use App\Models\WasteRequest;

class AssistLogController extends Controller
{
    public function index()
    {
        $logs = AssistLog::with([
                'resident:id,first_name,last_name',
                'staff:id,first_name,last_name',
            ])
            ->latest('id')
            ->limit(300)
            ->get();

        $idsOf = fn (string $type) => $logs->where('record_type', $type)->pluck('record_id')->unique()->values();

        $appointments = Appointment::whereIn('id', $idsOf('appointment'))->pluck('purpose', 'id');
        $incidents    = Incident::whereIn('id', $idsOf('incident'))->pluck('title', 'id');
        $waste        = WasteRequest::whereIn('id', $idsOf('waste'))->pluck('type', 'id');

        $data = $logs->map(function ($log) use ($appointments, $incidents, $waste) {
            $label = match ($log->record_type) {
                'appointment' => $appointments[$log->record_id] ?? null,
                'incident'    => $incidents[$log->record_id] ?? null,
                'waste'       => $waste[$log->record_id] ?? null,
                default       => null,
            };

            return [
                'id'            => $log->id,
                'created_at'    => $log->created_at,
                'action'        => $log->action,
                'record_type'   => $log->record_type,
                'record_label'  => $label ?? '(deleted record)',
                'staff_id'      => $log->staff_id,
                'staff_name'    => $log->staff?->full_name ?? 'Unknown',
                'resident_name' => $log->resident?->full_name ?? 'Unknown',
            ];
        })->values();

        return response()->json(['logs' => $data]);
    }
}