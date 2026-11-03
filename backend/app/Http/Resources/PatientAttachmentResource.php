<?php

namespace App\Http\Resources;

use App\Domains\Clinical\Models\PatientAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin PatientAttachment
 */
class PatientAttachmentResource extends JsonResource
{
    public static $wrap = null;

    /**
     * File of the record. The path on disk never leaves the API; the download goes through a route.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->original_name,
            'mime' => $this->mime,
            'sizeBytes' => $this->size_bytes,
            'uploader' => $this->uploader->name,
            'createdAt' => $this->created_at->toIso8601String(),
            'canDelete' => Gate::forUser($request->user())->allows('deleteAttachment', $this->patient),
        ];
    }
}
