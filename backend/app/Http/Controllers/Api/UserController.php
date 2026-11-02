<?php

namespace App\Http\Controllers\Api;

use App\Domains\Identity\Actions\CreateUser;
use App\Domains\Identity\Actions\DeleteUser;
use App\Domains\Identity\Actions\UpdateUser;
use App\Domains\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /** Lists the team. */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection(User::query()->orderBy('name')->get());
    }

    /** Adds a team member. */
    public function store(StoreUserRequest $request, CreateUser $createUser): UserResource
    {
        return new UserResource($createUser->handle($request->toData()));
    }

    /** Returns a team member. */
    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    /** Updates a team member. */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): UserResource
    {
        return new UserResource($updateUser->handle($user, $request->toData(), $request->user()));
    }

    /** Deletes a team member. */
    public function destroy(Request $request, User $user, DeleteUser $deleteUser): Response
    {
        Gate::authorize('delete', $user);

        $deleteUser->handle($user, $request->user());

        return response()->noContent();
    }
}
