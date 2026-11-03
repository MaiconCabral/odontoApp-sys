<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PlatformSessionController extends Controller
{
    /** The signed-in platform admin. */
    public function show(Request $request): JsonResponse
    {
        return $this->admin(Auth::guard('platform')->user());
    }

    /** Signs a platform admin in on the central domain. */
    public function store(LoginRequest $request): JsonResponse
    {
        abort_unless($request->hasSession(), Response::HTTP_BAD_REQUEST);

        if (! Auth::guard('platform')->attempt($request->credentials())) {
            return response()->json(['message' => 'E-mail ou senha incorretos.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->session()->regenerate();

        return $this->admin(Auth::guard('platform')->user());
    }

    /** Signs out and ends the session. */
    public function destroy(Request $request): Response
    {
        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /** Public fields of the admin. */
    private function admin(mixed $admin): JsonResponse
    {
        return response()->json(['admin' => ['id' => (string) $admin->id, 'name' => $admin->name, 'email' => $admin->email]]);
    }
}
