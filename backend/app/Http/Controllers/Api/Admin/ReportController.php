<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Appointment;
use App\Models\Alert;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, string $type)
    {
        $from   = $request->from;
        $to     = $request->to;
        $status = $request->status;

        $data = match($type) {
            'incidents'    => $this->incidents($from, $to, $status),
            'appointments' => $this->appointments($from, $to, $status),
            'alerts'       => $this->alerts($from, $to),
            default        => collect(),
        };

        return response()->json(['data' => $data]);
    }

    private function incidents($from, $to, $status)
    {
        return Incident::with('user:id,first_name,last_name')
            ->when($from,   fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to,     fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->get()
            ->map(fn($i) => [
                'id'        => $i->id,
                'resident'  => $i->user->full_name ?? '—',
                'title'     => $i->title,
                'category'  => $i->category,
                'location'  => $i->location,
                'date'      => $i->created_at->format('M d, Y'),
                'status'    => ucfirst($i->status),
            ]);
    }

    private function appointments($from, $to, $status)
    {
        return Appointment::with('user:id,first_name,last_name')
            ->when($from,   fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to,     fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id'       => $a->id,
                'resident' => $a->user->full_name ?? '—',
                'purpose'  => $a->purpose,
                'date'     => $a->date,
                'time'     => $a->time,
                'status'   => ucfirst($a->status),
            ]);
    }

    private function alerts($from, $to)
    {
        return Alert::with('user:id,first_name,last_name')
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id'      => $a->id,
                'type'    => ucfirst($a->type),
                'title'   => $a->title,
                'message' => $a->message,
                'date'    => $a->created_at->format('M d, Y'),
            ]);
    }
}