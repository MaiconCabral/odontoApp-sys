<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Actions\UpdateBusinessHours;
use App\Domains\Agenda\Models\BusinessHour;
use App\Domains\Agenda\Models\ScheduleBlock;
use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessHoursRequest;
use App\Http\Requests\ScheduleBlockRequest;
use App\Http\Resources\BusinessHourResource;
use App\Http\Resources\ScheduleBlockResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ScheduleController extends Controller
{
    /** Returns the opening hours of the seven weekdays. */
    public function hours(): AnonymousResourceCollection
    {
        return BusinessHourResource::collection(BusinessHour::query()->orderBy('weekday')->get());
    }

    /** Replaces the opening hours. */
    public function updateHours(BusinessHoursRequest $request, UpdateBusinessHours $update): AnonymousResourceCollection
    {
        $update->handle($request->input('days'));

        return $this->hours();
    }

    /** Lists holidays and days off from today on. */
    public function blocks(): AnonymousResourceCollection
    {
        Gate::authorize('manageBlocks');

        $blocks = ScheduleBlock::query()
            ->with('professional:id,name')
            ->where('ends_at', '>=', today())
            ->orderBy('starts_at')
            ->get();

        return ScheduleBlockResource::collection($blocks);
    }

    /** Adds a holiday or a day off. Booked appointments in the period are kept. */
    public function storeBlock(ScheduleBlockRequest $request): ScheduleBlockResource
    {
        $block = ScheduleBlock::create(['professional_id' => null, ...$request->validated()]);

        return new ScheduleBlockResource($block->load('professional:id,name'));
    }

    /** Updates a holiday or a day off. */
    public function updateBlock(ScheduleBlockRequest $request, ScheduleBlock $block): ScheduleBlockResource
    {
        // Without a professional the block applies to the whole clinic.
        $block->update(['professional_id' => null, ...$request->validated()]);

        return new ScheduleBlockResource($block->load('professional:id,name'));
    }

    /** Deletes a holiday or a day off. */
    public function destroyBlock(ScheduleBlock $block): Response
    {
        Gate::authorize('manageBlocks');

        $block->delete();

        return response()->noContent();
    }
}
