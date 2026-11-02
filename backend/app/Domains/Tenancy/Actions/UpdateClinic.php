<?php

namespace App\Domains\Tenancy\Actions;

use App\Domains\Tenancy\DTOs\ClinicData;
use App\Domains\Tenancy\Models\Tenant;

class UpdateClinic
{
    /** Saves the clinic registration data. */
    public function handle(Tenant $tenant, ClinicData $data): Tenant
    {
        $tenant->update([
            'name' => $data->name,
            'unit' => $data->unit,
            'cnpj' => $data->cnpj,
            'phone' => $data->phone,
            'email' => $data->email,
            'address' => $data->address,
        ]);

        return $tenant;
    }
}
