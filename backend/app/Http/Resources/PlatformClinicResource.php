<?php

namespace App\Http\Resources;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class PlatformClinicResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Keeps the clinic and its usage, read in the clinic database by the controller.
     *
     * @param  array<string, int>|null  $usage
     */
    public function __construct(Tenant $resource, private readonly ?array $usage = null)
    {
        parent::__construct($resource);
    }

    /**
     * A clinic as the platform admin sees it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug(),
            'email' => $this->email,
            'plan' => ['id' => (string) $this->plan_id, 'code' => $this->plan?->code, 'name' => $this->plan?->name],
            'status' => $this->isSuspended() ? 'suspended' : $this->subscriptionStatus(),
            'trialEndsAt' => $this->trial_ends_at?->toDateString(),
            'trialDaysLeft' => $this->trialDaysLeft(),
            'suspendedAt' => $this->getAttribute('suspended_at'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'usage' => $this->usage,
        ];
    }
}
