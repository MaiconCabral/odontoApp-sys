<?php

namespace App\Http\Resources;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class ClinicResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Clinic data shown in the settings.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'unit' => $this->unit,
            'cnpj' => $this->cnpj,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];
    }
}
