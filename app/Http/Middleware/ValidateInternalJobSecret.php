<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalJobSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.internal_job.secret');

        if (empty($secret)) {
            abort(500, 'Internal job secret not configured');
        }

        $providedSecret = $request->bearerToken();

        if (! $providedSecret || ! hash_equals($secret, $providedSecret)) {
            abort(401, 'Invalid or missing internal job secret');
        }

        return $next($request);
    }
}
