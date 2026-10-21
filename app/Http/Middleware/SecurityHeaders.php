<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        // Nothing served by the API should ever be able to run scripts (e.g. an uploaded HTML/SVG file).
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; sandbox");

        if ($response instanceof JsonResponse) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
