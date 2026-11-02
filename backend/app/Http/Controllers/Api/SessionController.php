<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    /** Returns the signed-in user, clinic and plan. */
    public function show(Request $request): SessionResource
    {
        return new SessionResource($request->user());
    }

    /** Signs in with e-mail and password. */
    public function store(LoginRequest $request): SessionResource|JsonResponse
    {
        abort_unless($request->hasSession(), Response::HTTP_BAD_REQUEST);

        // Inactive accounts get the same message, so a login attempt reveals nothing about them.
        if (! Auth::guard('web')->attempt([...$request->credentials(), 'active' => true])) {
            return response()->json(['message' => 'E-mail ou senha incorretos.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->session()->regenerate();

        return new SessionResource(Auth::guard('web')->user());
    }

    /** Signs out and ends the session. */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
