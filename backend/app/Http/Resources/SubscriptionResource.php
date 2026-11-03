<?php

namespace App\Http\Resources;

use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Support\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class SubscriptionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Subscription card and plan list of the settings.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->subscriptionStatus(),
            'trialDaysLeft' => $this->trialDaysLeft(),
            'trialEndsAt' => $this->trial_ends_at?->toIso8601String(),
            'professionalsUsed' => app(PlanLimits::class)->professionalsUsed(),
            'plan' => self::plan($this->plan),
            'plans' => Plan::query()->orderBy('price_cents')->get()->map(fn (Plan $plan) => self::plan($plan))->all(),
        ];
    }

    /**
     * Public fields of a plan.
     *
     * @return array<string, mixed>
     */
    private static function plan(Plan $plan): array
    {
        return [
            'code' => $plan->code,
            'name' => $plan->name,
            'priceCents' => $plan->price_cents,
            'maxProfessionals' => $plan->max_professionals,
        ];
    }
}
