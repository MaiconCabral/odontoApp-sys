<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Enums\Procedure;
use App\Domains\Agenda\Queries\AvailableSlots;
use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityRequest;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    /** Returns the free start times of a day for a professional and room. */
    public function __invoke(AvailabilityRequest $request, AvailableSlots $slots): JsonResponse
    {
        return response()->json([
            'date' => $request->input('date'),
            'slots' => $slots->for(
                $request->day(),
                Procedure::from($request->input('procedure')),
                $request->integer('professional_id'),
                $request->filled('room_id') ? $request->integer('room_id') : null,
                $request->filled('ignore') ? $request->integer('ignore') : null,
            ),
        ]);
    }
}
