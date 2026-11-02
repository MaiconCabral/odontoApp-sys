<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Queries\AgendaQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgendaRequest;
use Illuminate\Http\JsonResponse;

class AgendaController extends Controller
{
    /** Returns the agenda of the signed-in user. */
    public function __invoke(AgendaRequest $request, AgendaQuery $agenda): JsonResponse
    {
        return response()->json($agenda->for($request->user(), $request->professionalId()));
    }
}
