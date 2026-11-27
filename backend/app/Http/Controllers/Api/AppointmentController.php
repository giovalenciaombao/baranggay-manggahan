<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Notification;
use App\Services\AssistService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            'notes'   => 'nullable|string',
            'document' => 'nullable|file|max:2048|extensions:rtf,docx|mimes:rtf,docx',
        ]);

        $file = $request->file('document');
        $document = $file ? [
            'extension' => strtolower($file->getClientOriginalExtension()),
            'contents' => base64_encode($file->get()),
        ] : null;

        return $this->withScheduleLock(function () use ($request, $document) {
            $now = now('Asia/Manila');
            $date = $now->copy()->startOfDay();
            $end = $date->copy()->addDays(90);

            $bookings = Appointment::whereBetween('date', [$date->toDateString(), $end->toDateString()])
                ->whereIn('status', $this->occupyingStatuses())
                ->get(['date', 'time'])
                ->groupBy(fn (Appointment $appointment) => $appointment->date->toDateString());

            while ($date <= $end) {
                if ($date->isWeekday()) {
                    $day = $date->toDateString();
                    $taken = $bookings->get($day, collect())->pluck('time')->all();

                    foreach (Appointment::TIME_SLOTS as $time) {
                        $slot = Carbon::parse("$day $time", $now->timezone);
                        if ($slot <= $now || in_array($time, $taken, true)) {
                            continue;
                        }

                        $appointment = DB::transaction(function () use ($request, $day, $time, $document) {
                            $appointment = Appointment::create([
                                'user_id' => $request->user()->id,
                                'purpose' => $request->purpose,
                                'date'    => $day,
                                'time'    => $time,
                                'notes'   => $request->notes,
                                'status'  => Appointment::STATUS_PENDING,
                            ]);
                            if ($document) {
                                $appointment->document()->create($document);
                            }

                            return $appointment;
                        });

                        return response()->json([
                            'message'     => 'Appointment date and time assigned.',
                            'appointment' => $appointment,
                            'document_attached' => $document !== null,
                        ], 201);
                    }
                }

                $date->addDay();
            }

            return response()->json(['message' => 'No appointment slots are available in the next 90 days.'], 422);
        });
    }

    private function withScheduleLock(callable $callback)
    {
        try {
            return Cache::lock('appointments:schedule', 30)->block(5, $callback);
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'The appointment schedule is busy. Please try again.'], 503);
        }
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

        return $this->withScheduleLock(fn () => $this->updateAppointment($request, $appointment->refresh()));
    }

    private function updateAppointment(Request $request, Appointment $appointment)
    {
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
            ->withAggregate('document as document_extension', 'extension')
            ->latest()
            ->get();

        return response()->json(['appointments' => $appointments]);
    }

    public function downloadDocument(Appointment $appointment)
    {
        $document = $appointment->document()->firstOrFail();
        $filename = "appointment-{$appointment->id}.{$document->extension}";

        return response(base64_decode($document->contents), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
