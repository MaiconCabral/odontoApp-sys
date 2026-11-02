<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Patient;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRequest;
use App\Http\Resources\PatientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    private const PER_PAGE = 20;

    /** Lists patients by name, with search and pages. */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Patient::class);

        $patients = Patient::query()
            ->search($request->string('search')->toString())
            ->withMax(['appointments as last_appointment_at' => fn ($query) => $query->where('starts_at', '<=', now())], 'starts_at')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return PatientResource::collection($patients);
    }

    /** Adds a patient. */
    public function store(PatientRequest $request): PatientResource
    {
        return new PatientResource(Patient::create($request->toData()->toAttributes()));
    }

    /** Returns a patient record. */
    public function show(Patient $patient): PatientResource
    {
        Gate::authorize('view', $patient);

        return new PatientResource($patient);
    }

    /** Updates a patient record. */
    public function update(PatientRequest $request, Patient $patient): PatientResource
    {
        $patient->update($request->toData()->toAttributes());

        return new PatientResource($patient);
    }

    /** Deletes a patient without appointments. */
    public function destroy(Patient $patient): Response
    {
        Gate::authorize('delete', $patient);

        if ($patient->appointments()->exists()) {
            throw ValidationException::withMessages([
                'patient' => 'Este paciente tem consultas registradas e não pode ser excluído.',
            ]);
        }

        $patient->delete();

        return response()->noContent();
    }
}
