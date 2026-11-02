<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /** Signs out a deactivated user. */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->active) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();

        throw new AuthenticationException;
    }
}
