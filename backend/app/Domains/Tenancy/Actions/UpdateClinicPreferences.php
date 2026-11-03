<?php

namespace App\Domains\Tenancy\Actions;

use App\Domains\Tenancy\DTOs\ClinicPreferencesData;
use App\Domains\Tenancy\Models\Tenant;

class UpdateClinicPreferences
{
    /** Saves the service preferences of the clinic. */
    public function handle(Tenant $tenant, ClinicPreferencesData $data): Tenant
    {
        $tenant->update([
            'auto_confirmation' => $data->autoConfirmation,
            'waiting_list' => $data->waitingList,
            'reminder_24h' => $data->reminder24h,
        ]);

        return $tenant;
    }
}
