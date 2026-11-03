<?php

namespace App\Http\Resources;

use App\Domains\Clinical\Models\ClinicalNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalNote
 */
class ClinicalNoteResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Note of the medical record with who wrote it and when.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'body' => $this->body,
            'author' => $this->author->name,
            'appointment' => $this->appointment ? [
                'id' => (string) $this->appointment->id,
                'date' => $this->appointment->starts_at->toDateString(),
                'procedureLabel' => $this->appointment->procedure->label(),
            ] : null,
            'amendsId' => $this->amends_id ? (string) $this->amends_id : null,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
