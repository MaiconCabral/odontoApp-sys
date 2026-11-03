<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Closes a clinic the platform suspended: no login, no panel and no public booking.
// The patient links stay open, so patients can still cancel what is already booked.
class EnsureClinicIsActive
{
    /** Answers 403 for a suspended clinic. */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant()?->isSuspended()) {
            return response()->json(['message' => 'Clínica suspensa. Fale com o suporte.', 'suspended' => true], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
