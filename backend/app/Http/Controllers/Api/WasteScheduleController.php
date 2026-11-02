<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\WasteSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WasteScheduleController extends Controller
{
    // Residents + admin: upcoming truck schedules only
    public function index()
    {
        $schedules = WasteSchedule::whereDate('collection_date', '>=', now()->toDateString())
            ->orderBy('collection_date')
            ->orderBy('collection_time')
            ->get();

        return response()->json(['schedules' => $schedules]);
    }

    // Admin: everything (upcoming + past)
    public function adminIndex()
    {
        $schedules = WasteSchedule::orderByDesc('collection_date')
            ->orderByDesc('collection_time')
            ->get();

        return response()->json(['schedules' => $schedules]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'waste_type'      => 'required|string|max:100',
            'collection_date' => 'required|date|after_or_equal:today',
            'collection_time' => 'nullable|date_format:H:i',
            'area'            => 'nullable|string|max:255',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $schedule = WasteSchedule::create($data + ['created_by' => $request->user()->id]);

        $this->notifyResidents(
            'Garbage Truck Schedule',
            $this->describe($schedule, 'New schedule'),
            $schedule->id
        );

        return response()->json([
            'message'  => 'Schedule posted. Residents have been notified.',
            'schedule' => $schedule,
        ], 201);
    }

    public function update(Request $request, WasteSchedule $wasteSchedule)
    {
        $data = $request->validate([
            'waste_type'      => 'required|string|max:100',
            'collection_date' => 'required|date',
            'collection_time' => 'nullable|date_format:H:i',
            'area'            => 'nullable|string|max:255',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $wasteSchedule->update($data);

        $this->notifyResidents(
            'Garbage Truck Schedule Updated',
            $this->describe($wasteSchedule->fresh(), 'Updated schedule'),
            $wasteSchedule->id
        );

        return response()->json([
            'message'  => 'Schedule updated. Residents have been notified.',
            'schedule' => $wasteSchedule->fresh(),
        ]);
    }

    public function destroy(WasteSchedule $wasteSchedule)
    {
        $this->notifyResidents(
            'Garbage Truck Schedule Cancelled',
            $this->describe($wasteSchedule, 'Cancelled'),
            $wasteSchedule->id
        );

        $wasteSchedule->delete();

        return response()->json(['message' => 'Schedule cancelled. Residents have been notified.']);
    }

    // ─── Helpers ───────────────────────────────────────────

    private function describe(WasteSchedule $s, string $prefix): string
    {
        $date = Carbon::parse($s->collection_date)->format('M d, Y');
        $time = $s->collection_time
            ? ' at ' . Carbon::createFromFormat('H:i', substr($s->collection_time, 0, 5))->format('g:i A')
            : '';
        $area = $s->area ? " in {$s->area}" : ' in the whole barangay';

        $message = "{$prefix}: Garbage truck for {$s->waste_type} waste on {$date}{$time}{$area}.";

        if ($s->notes) {
            $message .= " Note: {$s->notes}";
        }

        return $message;
    }

    private function notifyResidents(string $title, string $message, int $scheduleId): void
    {
        $now = now();

        User::where('role', 'resident')
            ->where('status', 'active')
            ->pluck('id')
            ->chunk(500)
            ->each(function ($ids) use ($title, $message, $scheduleId, $now) {
                Notification::insert(
                    $ids->map(fn ($id) => [
                        'user_id'      => $id,
                        'type'         => 'waste',
                        'title'        => $title,
                        'message'      => $message,
                        'reference_id' => $scheduleId,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ])->all()
                );
            });
    }
}