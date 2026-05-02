<?php

namespace Domain\Idempotency\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST, PUT, PATCH methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH']))
        {
            return $next($request);
        }

        // Get idempotency key from header
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) 
        {
            return response()->json([
                'ok' => false,
                'error' => 'Idempotency-Key header is required'
            ], 400);
        }

        // Cache key format
        $cacheKey = 'idempotency:' . $request->path() . ':' . $idempotencyKey;

        // Check if already processed
        if (Cache::has($cacheKey))
        {
            $data = json_decode(Cache::get($cacheKey), true);
            $data['data']['idempotent'] = true; // Set idempotency flag as true.

            return response()->json($data, 200);
        }

        // It is a new idempotency key
        // Process request
        $response = $next($request);
        // Cache successful responses (2xx)
        if (
            $response->getStatusCode() >= 200 &&
            $response->getStatusCode() < 300
        )
        {
            Cache::put(
                $cacheKey, 
                $response->getContent(), 
                now()->addHours(24)
            );
        }

        return $response;
    }
}
