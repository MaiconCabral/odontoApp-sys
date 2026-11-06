<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Queries\AgendaQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgendaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /** Returns the agenda of the period for the signed-in user. */
    public function __invoke(AgendaRequest $request, AgendaQuery $agenda): JsonResponse
    {
        return response()->json($agenda->for($request->user(), $request->from(), $request->to(), $request->professionalId()));
    }

    /** Returns the procedures, professionals and rooms the booking forms list, without any appointment. */
    public function options(Request $request, AgendaQuery $agenda): JsonResponse
    {
        return response()->json($agenda->options($request->user()));
    }
}
