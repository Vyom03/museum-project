<?php

namespace App\Http\Controllers;

use App\Models\TourRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TourRegistrationController extends Controller
{
    private const SLOT_CAPACITY = [
        'Morning (10:30 AM - 12:00 PM)' => 20,
        'Afternoon (02:30 PM - 04:00 PM)' => 15,
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = TourRegistration::query()
            ->latest()
            ->paginate(15);

        return response()->json($items);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'organisation' => ['nullable', 'string', 'max:150'],
            'group_type' => ['required', 'string', 'max:50'],
            'preferred_date' => ['required', 'date'],
            'preferred_slot' => ['required', 'string', 'max:50'],
            'adults_count' => ['nullable', 'integer', 'min:0'],
            'needs_guided_tour' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['needs_guided_tour'] = $request->boolean('needs_guided_tour');
        $data['adults_count'] = $data['adults_count'] ?? 0;
        $data['students_count'] = $data['students_count'] ?? 0;

        if (! empty($data['country_code'])) {
            $data['country_code'] = ltrim($data['country_code'], '+');
        }

        $totalPeople = (int) $data['adults_count'] + (int) $data['students_count'];

        if ($totalPeople < 1) {
            throw ValidationException::withMessages([
                'adults_count' => ['Please provide at least one attendee for the visit.'],
            ]);
        }

        $remainingCapacity = $this->remainingCapacity($data['preferred_date'], $data['preferred_slot']);

        if ($remainingCapacity !== null) {
            if ($remainingCapacity === 0) {
                throw ValidationException::withMessages([
                    'preferred_date' => [
                        'The selected slot is fully booked for this date. Please choose another day or time.',
                    ],
                ]);
            }

            if ($totalPeople > $remainingCapacity) {
                throw ValidationException::withMessages([
                    'preferred_date' => [
                        "Only {$remainingCapacity} spots remain for the selected slot. Please adjust your group size or choose another date.",
                    ],
                ]);
            }
        }

        $registration = TourRegistration::create($data);

        return response()->json([
            'message' => 'Your tour request has been received. Our team will confirm availability shortly.',
            'data' => $registration,
        ], 201);
    }

    public function availability(Request $request)
    {
        $validated = $request->validate([
            'preferred_date' => ['required', 'date'],
            'preferred_slot' => ['required', 'string', 'max:50'],
        ]);

        $capacity = $this->slotCapacity($validated['preferred_slot']);
        $booked = $this->bookedCount($validated['preferred_date'], $validated['preferred_slot']);
        $remaining = $capacity !== null ? max(0, $capacity - $booked) : null;

        return response()->json([
            'capacity' => $capacity,
            'booked' => $booked,
            'remaining' => $remaining,
        ]);
    }

    private function slotCapacity(string $slot): ?int
    {
        return self::SLOT_CAPACITY[$slot] ?? null;
    }

    private function bookedCount(string $date, string $slot): int
    {
        return (int) TourRegistration::query()
            ->where('preferred_date', $date)
            ->where('preferred_slot', $slot)
            ->sum(DB::raw('adults_count + students_count'));
    }

    private function remainingCapacity(string $date, string $slot): ?int
    {
        $capacity = $this->slotCapacity($slot);

        if ($capacity === null) {
            return null;
        }

        $booked = $this->bookedCount($date, $slot);

        return max(0, $capacity - $booked);
    }

    /**
     * Display the specified resource.
     */
    public function show(TourRegistration $tourRegistration)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TourRegistration $tourRegistration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TourRegistration $tourRegistration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TourRegistration $tourRegistration)
    {
        //
    }
}
