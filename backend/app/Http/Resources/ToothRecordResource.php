<?php

namespace App\Http\Resources;

use App\Domains\Clinical\Models\ToothRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ToothRecord
 */
class ToothRecordResource extends JsonResource
{
    public static $wrap = null;

    /**
     * One change of the odontogram.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'tooth' => $this->tooth,
            'surface' => $this->surface?->value,
            'condition' => $this->condition->value,
            'conditionLabel' => $this->condition->label(),
            'notes' => $this->notes,
            'author' => $this->author->name,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
