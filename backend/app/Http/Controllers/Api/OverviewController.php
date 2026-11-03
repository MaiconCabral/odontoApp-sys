<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Queries\OverviewQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OverviewController extends Controller
{
    /** Returns the numbers of the panel home page, for admins and reception. */
    public function __invoke(Request $request, OverviewQuery $overview): JsonResponse
    {
        Gate::authorize('viewAll', Appointment::class);

        return response()->json($overview->for($request->user()));
    }
}
