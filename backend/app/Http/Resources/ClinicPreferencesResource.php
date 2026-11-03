<?php

namespace App\Http\Resources;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class ClinicPreferencesResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Service preferences shown in the settings.
     *
     * @return array<string, bool>
     */
    public function toArray(Request $request): array
    {
        $preferences = $this->preferences();

        return [
            'autoConfirmation' => $preferences['auto_confirmation'],
            'waitingList' => $preferences['waiting_list'],
            'reminder24h' => $preferences['reminder_24h'],
        ];
    }
}
