<?php

namespace App\Http\Controllers\Api\Platform;

use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PlatformPlanController extends Controller
{
    /** Plans by price, with how many clinics use each one. */
    public function index(): JsonResponse
    {
        $counts = Tenant::query()->selectRaw('plan_id, COUNT(*) as total')->groupBy('plan_id')->pluck('total', 'plan_id');

        return response()->json([
            'data' => Plan::query()->orderBy('price_cents')->get()->map(fn (Plan $plan) => $this->plan($plan, (int) ($counts[$plan->id] ?? 0)))->all(),
        ]);
    }

    /** Adds a plan. */
    public function store(PlanRequest $request): JsonResponse
    {
        return response()->json($this->plan(Plan::create($request->toAttributes()), 0), 201);
    }

    /** Edits a plan. The new limit applies to new professionals only, like a downgrade. */
    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        if ($plan->code === Plan::TRIAL_CODE && $request->input('code') !== Plan::TRIAL_CODE) {
            throw ValidationException::withMessages(['code' => 'O código do plano de trial não pode mudar.']);
        }

        $plan->update($request->toAttributes());

        return response()->json($this->plan($plan, Tenant::query()->where('plan_id', $plan->id)->count()));
    }

    /** Deletes a plan no clinic uses, except the trial plan. */
    public function destroy(Plan $plan): Response
    {
        if ($plan->code === Plan::TRIAL_CODE) {
            throw ValidationException::withMessages(['plan' => 'O plano de trial não pode ser excluído.']);
        }

        if (Tenant::query()->where('plan_id', $plan->id)->exists()) {
            throw ValidationException::withMessages(['plan' => 'Há clínicas neste plano. Mude as clínicas de plano antes de excluir.']);
        }

        $plan->delete();

        return response()->noContent();
    }

    /**
     * Public fields of a plan.
     *
     * @return array<string, mixed>
     */
    private function plan(Plan $plan, int $clinics): array
    {
        return [
            'id' => (string) $plan->id,
            'code' => $plan->code,
            'name' => $plan->name,
            'priceCents' => $plan->price_cents,
            'maxProfessionals' => $plan->max_professionals,
            'clinics' => $clinics,
        ];
    }
}
