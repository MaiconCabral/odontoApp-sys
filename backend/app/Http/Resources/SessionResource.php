<?php

namespace App\Http\Resources;

use App\Domains\Identity\Models\User;
use App\Support\Initials;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class SessionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Session contract used by the frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tenant = tenant()->loadMissing('plan', 'domains');

        return [
            'user' => [
                'id' => (string) $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'initials' => $this->initials(),
                'role' => $this->role->value,
                'roleLabel' => $this->role->label(),
            ],
            'tenant' => [
                'slug' => $tenant->slug(),
                'name' => $tenant->name,
                'unit' => $tenant->unit,
                'initials' => Initials::of($tenant->name),
            ],
            'plan' => [
                'name' => $tenant->plan->name,
                'trialDaysLeft' => $tenant->trialDaysLeft(),
                'maxProfessionals' => $tenant->plan->max_professionals,
            ],
        ];
    }
}
