<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Platform\DTOs\ClinicAccountData;
use App\Domains\Tenancy\Models\Tenant;

class UpdateClinicAccount
{
    /**
     * Changes the plan, the end of the trial and the suspension of a clinic.
     * A null trial end means the clinic pays; suspending keeps the date it started.
     */
    public function handle(Tenant $tenant, ClinicAccountData $data): Tenant
    {
        $tenant->update([
            'plan_id' => $data->planId,
            'trial_ends_at' => $data->trialEndsAt?->endOfDay(),
            'suspended_at' => $data->suspended ? ($tenant->getAttribute('suspended_at') ?? now()->toIso8601String()) : null,
        ]);

        return $tenant;
    }
}
