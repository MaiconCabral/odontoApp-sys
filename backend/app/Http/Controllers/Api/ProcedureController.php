<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Procedure;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcedureRequest;
use App\Http\Resources\ProcedureResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProcedureController extends Controller
{
    /** Lists every procedure, active ones first. */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Procedure::class);

        return ProcedureResource::collection(Procedure::query()->orderByDesc('active')->orderBy('name')->get());
    }

    /** Adds a procedure. */
    public function store(ProcedureRequest $request): ProcedureResource
    {
        return new ProcedureResource(Procedure::create($request->toAttributes()));
    }

    /** Returns a procedure. */
    public function show(Procedure $procedure): ProcedureResource
    {
        Gate::authorize('viewAny', Procedure::class);

        return new ProcedureResource($procedure);
    }

    /** Updates a procedure. Booked appointments keep their length and price. */
    public function update(ProcedureRequest $request, Procedure $procedure): ProcedureResource
    {
        $procedure->update($request->toAttributes());

        return new ProcedureResource($procedure);
    }

    /** Deletes a procedure that was never booked. */
    public function destroy(Procedure $procedure): Response
    {
        Gate::authorize('delete', $procedure);

        if ($procedure->appointments()->exists()) {
            throw ValidationException::withMessages([
                'procedure' => 'Este procedimento já foi usado em consultas. Desative-o em vez de excluí-lo.',
            ]);
        }

        $procedure->delete();

        return response()->noContent();
    }
}
