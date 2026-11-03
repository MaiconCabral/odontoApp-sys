<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Procedure
 */
class ProcedureResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Procedure with its length, price and status.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'durationMinutes' => $this->duration_minutes,
            'priceCents' => $this->price_cents,
            'active' => $this->active,
        ];
    }
}
