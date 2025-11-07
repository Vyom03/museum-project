<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminTourRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = TourRegistration::query()
            ->orderBy('preferred_date')
            ->orderBy('preferred_slot')
            ->orderBy('created_at');

        if ($request->filled('date')) {
            $query->whereDate('preferred_date', $request->date);
        }

        if ($request->filled('from')) {
            $query->whereDate('preferred_date', '>=', Carbon::parse($request->from));
        }

        if ($request->filled('to')) {
            $query->whereDate('preferred_date', '<=', Carbon::parse($request->to));
        }

        $registrations = $query->get();

        $grouped = $registrations
            ->groupBy(fn (TourRegistration $registration) => $registration->preferred_date->toDateString())
            ->map(fn ($collection, $date) => [
                'date' => $date,
                'readable_date' => Carbon::parse($date)->translatedFormat('j M Y (D)'),
                'total_visitors' => $this->totalVisitors($collection),
                'slots' => $this->formatSlots($collection),
            ])
            ->values();

        return response()->json([
            'data' => $grouped,
            'meta' => [
                'total_days' => $grouped->count(),
                'total_registrations' => $registrations->count(),
                'total_visitors' => $this->totalVisitors($registrations),
            ],
        ]);
    }

    protected function formatSlots($collection)
    {
        return $collection
            ->groupBy('preferred_slot')
            ->map(function ($slotCollection, $slot) {
                $booked = $this->totalVisitors($slotCollection);
                $capacity = $this->capacityForSlot($slot);
                $remaining = $capacity !== null ? max($capacity - $booked, 0) : null;

                return [
                    'slot' => $slot,
                    'label' => ucfirst($slot),
                    'capacity' => $capacity,
                    'booked_visitors' => $booked,
                    'remaining_capacity' => $remaining,
                    'registrations' => $slotCollection->map(function (TourRegistration $registration) {
                        return [
                            'id' => $registration->id,
                            'contact_name' => $registration->contact_name,
                            'organisation' => $registration->organisation,
                            'email' => $registration->email,
                            'phone' => $registration->phone,
                            'country_code' => $registration->country_code,
                            'visitors_count' => $this->visitorCount($registration),
                            'adults_count' => $registration->adults_count,
                            'students_count' => $registration->students_count,
                            'needs_guided_tour' => (bool) $registration->needs_guided_tour,
                            'notes' => $registration->notes,
                            'created_at' => $registration->created_at,
                            'submitted_at' => $registration->created_at?->toDayDateTimeString(),
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->sortBy(fn ($slot) => $slot['slot'])
            ->values();
    }

    protected function capacityForSlot(string $slot): ?int
    {
        return match ($slot) {
            'Morning (10:30 AM - 12:00 PM)' => 20,
            'Afternoon (02:30 PM - 04:00 PM)' => 15,
            default => null,
        };
    }

    protected function visitorCount(TourRegistration $registration): int
    {
        $visitorsCount = $registration->visitors_count ?? null;

        if ($visitorsCount !== null) {
            return (int) $visitorsCount;
        }

        $adults = (int) ($registration->adults_count ?? 0);
        $students = (int) ($registration->students_count ?? 0);

        return $adults + $students;
    }

    protected function totalVisitors($collection): int
    {
        return $collection->sum(function (TourRegistration $registration) {
            return $this->visitorCount($registration);
        });
    }
}
