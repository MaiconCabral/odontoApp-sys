<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Stancl\Tenancy\Middleware\ScopeSessions;
use Symfony\Component\HttpFoundation\Response;

// Sanctum loads the session user right here, ahead of any route middleware. User ids repeat
// across clinic databases, so the session must be proven to belong to this clinic first.
class AuthenticateTenantSession extends AuthenticateSession
{
    /** Checks the session clinic before Sanctum loads the user. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenancy()->initialized || ! $request->hasSession()) {
            return parent::handle($request, $next);
        }

        return (new ScopeSessions)->handle(
            $request,
            fn (Request $request) => parent::handle($request, $next),
        );
    }
}
