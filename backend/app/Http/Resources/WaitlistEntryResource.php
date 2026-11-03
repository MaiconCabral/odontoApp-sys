<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WaitlistEntry
 */
class WaitlistEntryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Entry of the waiting list with what the patient waits for.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'patient' => [
                'id' => (string) $this->patient->id,
                'name' => $this->patient->name,
                'phone' => $this->patient->phone,
                'hasEmail' => $this->patient->email !== null,
            ],
            'procedure' => (string) $this->procedure_id,
            'procedureLabel' => $this->procedure->label(),
            'professional' => $this->professional ? [
                'id' => (string) $this->professional->id,
                'name' => $this->professional->name,
            ] : null,
            'fromDate' => $this->from_date->toDateString(),
            'toDate' => $this->to_date->toDateString(),
            'period' => $this->period->value,
            'periodLabel' => $this->period->label(),
            'notes' => $this->notes,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'notifiedAt' => $this->notified_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
