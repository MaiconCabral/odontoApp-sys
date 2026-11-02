<?php

namespace App\Http\Controllers\Api;

use App\Domains\Tenancy\Actions\UpdateClinic;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use Illuminate\Support\Facades\Gate;

class ClinicController extends Controller
{
    /** Returns the clinic data. */
    public function show(): ClinicResource
    {
        Gate::authorize('view', tenant());

        return new ClinicResource(tenant());
    }

    /** Updates the clinic data. */
    public function update(UpdateClinicRequest $request, UpdateClinic $updateClinic): ClinicResource
    {
        return new ClinicResource($updateClinic->handle(tenant(), $request->toData()));
    }
}
