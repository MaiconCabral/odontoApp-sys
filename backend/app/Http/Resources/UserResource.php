<?php

namespace App\Http\Resources;

use App\Domains\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Team member without sensitive fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'initials' => $this->initials(),
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'specialty' => $this->specialty,
            'attendsPatients' => $this->attends_patients,
            'active' => $this->active,
        ];
    }
}
