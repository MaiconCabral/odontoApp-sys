<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Models\ScheduleBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScheduleBlock
 */
class ScheduleBlockResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Holiday or day off, with the professional when it is a day off.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'professionalId' => $this->professional_id ? (string) $this->professional_id : null,
            'professionalName' => $this->whenLoaded('professional', fn () => $this->professional?->name),
            'startsAt' => $this->starts_at->format('Y-m-d H:i'),
            'endsAt' => $this->ends_at->format('Y-m-d H:i'),
            'reason' => $this->reason,
        ];
    }
}
