<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Actions\SaveWaitlistEntry;
use App\Domains\Agenda\Enums\WaitlistStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\WaitlistEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\WaitlistRequest;
use App\Http\Resources\WaitlistEntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WaitlistController extends Controller
{
    /** What every entry of the list shows. */
    private const RELATIONS = ['patient:id,name,phone,email', 'procedure', 'professional:id,name'];

    /** Lists the entries, oldest first. Without a status, the open ones (waiting and notified). */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('manage', Appointment::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(WaitlistStatus::class)],
            'patient_id' => ['nullable', 'integer'],
        ]);

        $entries = WaitlistEntry::query()
            ->with(self::RELATIONS)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
                fn ($query) => $query->whereIn('status', [WaitlistStatus::Waiting, WaitlistStatus::Notified]),
            )
            ->when($request->filled('patient_id'), fn ($query) => $query->where('patient_id', $request->integer('patient_id')))
            ->oldest('id')
            ->get();

        return WaitlistEntryResource::collection($entries);
    }

    /** Adds a patient to the waiting list. */
    public function store(WaitlistRequest $request, SaveWaitlistEntry $save): JsonResponse
    {
        $entry = $save->handle($request->toData());

        return (new WaitlistEntryResource($entry->load(self::RELATIONS)))->response()->setStatusCode(201);
    }

    /** Edits an entry or changes its status. */
    public function update(WaitlistRequest $request, WaitlistEntry $waitlist, SaveWaitlistEntry $save): WaitlistEntryResource
    {
        return new WaitlistEntryResource($save->handle($request->toData(), $waitlist)->load(self::RELATIONS));
    }

    /** Removes an entry. */
    public function destroy(WaitlistEntry $waitlist): Response
    {
        Gate::authorize('manage', Appointment::class);

        $waitlist->delete();

        return response()->noContent();
    }
}
