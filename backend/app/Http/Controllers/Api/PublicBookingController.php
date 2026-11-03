<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Actions\RequestAppointment;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Queries\AvailableSlots;
use App\Domains\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\PublicAvailabilityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

// Open to visitors of the clinic subdomain. Nothing here needs a session.
class PublicBookingController extends Controller
{
    /** Returns what a patient chooses from: clinic, procedures and professionals. */
    public function show(): JsonResponse
    {
        return response()->json([
            'clinic' => ['name' => tenant('name'), 'unit' => tenant('unit'), 'phone' => tenant('phone')],
            'procedures' => Procedure::options(),
            'professionals' => User::query()
                ->where('attends_patients', true)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'specialty'])
                ->map(fn (User $professional) => [
                    'id' => (string) $professional->id,
                    'name' => $professional->name,
                    'specialty' => $professional->specialty,
                ]),
            'maxDaysAhead' => PublicAvailabilityRequest::MAX_DAYS_AHEAD,
        ]);
    }

    /** Returns the free start times of a day. */
    public function availability(PublicAvailabilityRequest $request, AvailableSlots $slots): JsonResponse
    {
        $professionalId = $request->filled('professional_id') ? $request->integer('professional_id') : null;

        return response()->json([
            'date' => $request->input('date'),
            'slots' => array_map(
                fn (array $slot) => $slot['time'],
                $slots->for($request->day(), $request->procedure()->minutes(), $professionalId),
            ),
        ]);
    }

    /** Saves a booking request. A bot gets the same answer, but nothing is saved. */
    public function store(BookingRequest $request, RequestAppointment $requestAppointment): JsonResponse
    {
        if (! $request->isBot()) {
            $requestAppointment->handle($request->toData());
        }

        return response()->json([
            'message' => 'Recebemos seu pedido. A clínica vai entrar em contato para confirmar o horário.',
        ], Response::HTTP_CREATED);
    }
}
