<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Models\BusinessHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessHour
 */
class BusinessHourResource extends JsonResource
{
    /**
     * Opening hours of one weekday.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'weekday' => $this->weekday,
            'label' => BusinessHour::WEEKDAY_LABELS[$this->weekday],
            'closed' => $this->closed,
            'opensAt' => BusinessHour::hhmm($this->opens_at),
            'closesAt' => BusinessHour::hhmm($this->closes_at),
            'breakStartsAt' => BusinessHour::hhmm($this->break_starts_at),
            'breakEndsAt' => BusinessHour::hhmm($this->break_ends_at),
        ];
    }
}
