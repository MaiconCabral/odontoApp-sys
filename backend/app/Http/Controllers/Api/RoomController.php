<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Room;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    /** Lists every room, active ones first. */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Room::class);

        return RoomResource::collection(Room::query()->orderByDesc('active')->orderBy('name')->get());
    }

    /** Adds a room. */
    public function store(RoomRequest $request): RoomResource
    {
        return new RoomResource(Room::create($request->toAttributes()));
    }

    /** Returns a room. */
    public function show(Room $room): RoomResource
    {
        Gate::authorize('viewAny', Room::class);

        return new RoomResource($room);
    }

    /** Updates a room. */
    public function update(RoomRequest $request, Room $room): RoomResource
    {
        $room->update($request->toAttributes());

        return new RoomResource($room);
    }

    /** Deletes a room that was never used. */
    public function destroy(Room $room): Response
    {
        Gate::authorize('delete', $room);

        if ($room->appointments()->exists()) {
            throw ValidationException::withMessages([
                'room' => 'Esta sala já foi usada em consultas. Desative-a em vez de excluí-la.',
            ]);
        }

        $room->delete();

        return response()->noContent();
    }
}
