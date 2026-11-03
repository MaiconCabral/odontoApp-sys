<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
class StaffNotificationResource extends JsonResource
{
    /**
     * Alert shown under the bell.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->data['title'] ?? '',
            'body' => $this->data['body'] ?? '',
            'appointmentId' => $this->data['appointmentId'] ?? null,
            'date' => $this->data['date'] ?? null,
            'read' => $this->read_at !== null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
