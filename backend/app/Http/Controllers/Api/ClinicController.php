<?php

namespace App\Http\Controllers\Api;

use App\Domains\Tenancy\Actions\UpdateClinic;
use App\Domains\Tenancy\Actions\UpdateClinicPreferences;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClinicPreferencesRequest;
use App\Http\Requests\UpdateClinicRequest;
use App\Http\Resources\ClinicPreferencesResource;
use App\Http\Resources\ClinicResource;
use App\Http\Resources\SubscriptionResource;
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

    /** Returns the service preferences. */
    public function preferences(): ClinicPreferencesResource
    {
        Gate::authorize('view', tenant());

        return new ClinicPreferencesResource(tenant());
    }

    /** Updates the service preferences. */
    public function updatePreferences(UpdateClinicPreferencesRequest $request, UpdateClinicPreferences $update): ClinicPreferencesResource
    {
        return new ClinicPreferencesResource($update->handle(tenant(), $request->toData()));
    }

    /** Returns the current plan and the plans the clinic may move to. */
    public function subscription(): SubscriptionResource
    {
        Gate::authorize('view', tenant());

        return new SubscriptionResource(tenant()->loadMissing('plan'));
    }
}
