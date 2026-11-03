<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Room
 */
class RoomResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Room with its status.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'active' => $this->active,
        ];
    }
}
