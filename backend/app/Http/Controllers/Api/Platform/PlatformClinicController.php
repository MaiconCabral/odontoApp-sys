<?php

namespace App\Http\Controllers\Api\Platform;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Models\User;
use App\Domains\Platform\Actions\UpdateClinicAccount;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClinicAccountRequest;
use App\Http\Resources\PlatformClinicResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformClinicController extends Controller
{
    /** Clinics per page; the usage is read in each clinic database, so pages stay small. */
    private const PER_PAGE = 20;

    /** Clinics with search and filters, newest first, with the usage of the ones on the page. */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'plan_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['trial', 'active', 'expired', 'suspended'])],
        ]);

        $clinics = Tenant::query()
            ->with(['plan', 'domains'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhereHas('domains', fn (Builder $domains) => $domains->where('domain', 'like', $search)));
            })
            ->when($request->filled('plan_id'), fn (Builder $query) => $query->where('plan_id', $request->integer('plan_id')))
            ->when($request->filled('status'), fn (Builder $query) => $this->filterStatus($query, $request->string('status')->toString()))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return response()->json([
            'data' => $clinics->getCollection()->map(fn (Tenant $clinic) => (new PlatformClinicResource($clinic, $this->usage($clinic)))->toArray($request))->all(),
            'meta' => ['total' => $clinics->total(), 'current_page' => $clinics->currentPage(), 'last_page' => $clinics->lastPage()],
        ]);
    }

    /** One clinic with its usage. */
    public function show(Tenant $clinic): PlatformClinicResource
    {
        return new PlatformClinicResource($clinic->load(['plan', 'domains']), $this->usage($clinic));
    }

    /** Changes plan, trial and suspension. */
    public function update(UpdateClinicAccountRequest $request, Tenant $clinic, UpdateClinicAccount $update): PlatformClinicResource
    {
        $update->handle($clinic, $request->toData());

        return $this->show($clinic->fresh());
    }

    /** Status filter: suspension first, then the trial dates. */
    private function filterStatus(Builder $query, string $status): void
    {
        if ($status === 'suspended') {
            $query->whereNotNull('data->suspended_at');

            return;
        }

        $query->whereNull('data->suspended_at');

        match ($status) {
            'trial' => $query->where('trial_ends_at', '>', now()),
            'expired' => $query->where('trial_ends_at', '<=', now()),
            'active' => $query->whereNull('trial_ends_at'),
        };
    }

    /**
     * Team and appointments of the last 30 days, read in the clinic database.
     *
     * @return array<string, int>
     */
    private function usage(Tenant $clinic): array
    {
        return $clinic->run(fn () => [
            'activeUsers' => User::query()->where('active', true)->count(),
            'professionals' => User::query()->where('active', true)->where('attends_patients', true)->count(),
            'appointmentsLast30Days' => Appointment::query()->where('starts_at', '>=', now()->subDays(30))->where('starts_at', '<=', now())->count(),
        ]);
    }
}
