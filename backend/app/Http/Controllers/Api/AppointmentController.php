<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Notification;
use App\Services\AssistService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    private const BOOKED_MESSAGE = 'That time slot is already booked. Please choose another time or date.';

    private function occupyingStatuses(): array
    {
        return [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_APPROVED,
            Appointment::STATUS_RESCHEDULED,
        ];
    }

    private function slotTaken(string $date, string $time, ?int $ignoreId = null): bool
    {
        return Appointment::whereDate('date', $date)
            ->where('time', $time)
            ->whereIn('status', $this->occupyingStatuses())
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function index(Request $request)
    {
        $appointments = Appointment::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    public function availability(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $taken = Appointment::whereDate('date', $request->date)
            ->whereIn('status', $this->occupyingStatuses())
            ->pluck('time')
            ->unique()
            ->values();

        return response()->json([
            'date'  => $request->date,
            'slots' => Appointment::TIME_SLOTS,
            'taken' => $taken,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'purpose' => 'required|string|max:255',
            'date'    => 'required|date|after_or_equal:today',
            'time'    => ['required', 'string', Rule::in(Appointment::TIME_SLOTS)],
            'notes'   => 'nullable|string',
        ]);

        if ($this->slotTaken($request->date, $request->time)) {
            return response()->json([
                'message' => self::BOOKED_MESSAGE,
                'errors'  => ['time' => [self::BOOKED_MESSAGE]],
            ], 422);
        }

        $appointment = Appointment::create([
            'user_id' => $request->user()->id,
            'purpose' => $request->purpose,
            'date'    => $request->date,
            'time'    => $request->time,
            'notes'   => $request->notes,
            'status'  => Appointment::STATUS_PENDING,
        ]);

        return response()->json([
            'message'     => 'Appointment booked successfully.',
            'appointment' => $appointment,
        ], 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($request->user()->role !== 'admin' && $appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json(['appointment' => $appointment->load('user')]);
    }

    // Admin only: approve / reschedule / cancel / complete
    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status'     => ['required', Rule::in(Appointment::STATUSES)],
            'date'       => 'required_if:status,rescheduled|nullable|date|after_or_equal:today',
            'time'       => ['required_if:status,rescheduled', 'nullable', 'string', Rule::in(Appointment::TIME_SLOTS)],
            'admin_note' => 'required_if:status,rescheduled,cancelled|nullable|string|max:1000',
        ], [
            'date.required_if'       => 'Please pick a new date for the reschedule.',
            'time.required_if'       => 'Please pick a new time for the reschedule.',
            'admin_note.required_if' => 'Please provide a reason/note for the resident.',
        ]);

        if (in_array($appointment->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED])) {
            return response()->json([
                'message' => "This appointment is already {$appointment->status} and can no longer be changed.",
            ], 422);
        }

        $status = $request->status;

        // Rescheduling into a slot that is already taken by someone else
        if ($status === Appointment::STATUS_RESCHEDULED
            && $this->slotTaken($request->date, $request->time, $appointment->id)) {
            return response()->json([
                'message' => self::BOOKED_MESSAGE,
                'errors'  => ['time' => [self::BOOKED_MESSAGE]],
            ], 422);
        }

        if ($status === Appointment::STATUS_RESCHEDULED) {
            if (!$appointment->original_date) {
                $appointment->original_date = $appointment->date;
                $appointment->original_time = $appointment->time;
            }

            $appointment->date           = $request->date;
            $appointment->time           = $request->time;
            $appointment->rescheduled_at = now();
        }

        $appointment->status = $status;

        if ($request->filled('admin_note')) {
            $appointment->admin_note = $request->admin_note;
        }

        $appointment->save();

        AssistService::record($request->user(), $appointment->user_id, 'appointment', $appointment->id, $status);

        $this->notifyResident($appointment, $status);

        return response()->json([
            'message'     => 'Appointment updated.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    public function adminIndex()
    {
        $appointments = Appointment::with('user:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    private function notifyResident(Appointment $appointment, string $status): void
    {
        $purpose = $appointment->purpose;
        $note    = $appointment->admin_note;

        $message = match ($status) {
            Appointment::STATUS_APPROVED => "Your appointment for \"{$purpose}\" on "
                . $appointment->date->format('M d, Y') . " at {$appointment->time} has been approved.",

            Appointment::STATUS_RESCHEDULED => "Your appointment for \"{$purpose}\" was rescheduled to "
                . $appointment->date->format('M d, Y') . " at {$appointment->time}."
                . ($note ? " Reason: {$note}" : ''),

            Appointment::STATUS_CANCELLED => "Your appointment for \"{$purpose}\" has been cancelled."
                . ($note ? " Reason: {$note}" : ''),

            Appointment::STATUS_COMPLETED => "Your appointment for \"{$purpose}\" is now completed.",

            default => "Your appointment for \"{$purpose}\" is now " . ucfirst($status) . '.',
        };

        Notification::create([
            'user_id'      => $appointment->user_id,
            'type'         => 'appointment',
            'title'        => $status === Appointment::STATUS_RESCHEDULED
                ? 'Appointment Rescheduled'
                : 'Appointment Updated',
            'message'      => $message,
            'reference_id' => $appointment->id,
        ]);
    }
}