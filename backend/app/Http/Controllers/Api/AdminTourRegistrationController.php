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
                'total_visitors' => $collection->sum('visitors_count'),
                'slots' => $this->formatSlots($collection),
            ])
            ->values();

        return response()->json([
            'data' => $grouped,
            'meta' => [
                'total_days' => $grouped->count(),
                'total_registrations' => $registrations->count(),
                'total_visitors' => $registrations->sum('visitors_count'),
            ],
        ]);
    }

    protected function formatSlots($collection)
    {
        return $collection
            ->groupBy('preferred_slot')
            ->map(function ($slotCollection, $slot) {
                $booked = $slotCollection->sum('visitors_count');
                $capacity = $this->capacityForSlot($slot);

                return [
                    'slot' => $slot,
                    'label' => ucfirst($slot),
                    'capacity' => $capacity,
                    'booked_visitors' => $booked,
                    'remaining_capacity' => max($capacity - $booked, 0),
                    'registrations' => $slotCollection->map(function (TourRegistration $registration) {
                        return [
                            'id' => $registration->id,
                            'contact_name' => $registration->contact_name,
                            'organisation' => $registration->organisation,
                            'email' => $registration->email,
                            'phone' => $registration->phone,
                            'country_code' => $registration->country_code,
                            'visitors_count' => $registration->visitors_count,
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

    protected function capacityForSlot(string $slot): int
    {
        return match ($slot) {
            'morning' => 20,
            'afternoon' => 15,
            default => 0,
        };
    }
}
