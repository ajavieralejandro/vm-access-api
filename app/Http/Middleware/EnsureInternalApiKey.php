<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-Internal-Key');
        $configuredKey = config('integrations.internal_api_key');

        $isAuthorized = is_string($providedKey)
            && is_string($configuredKey)
            && $configuredKey !== ''
            && hash_equals($configuredKey, $providedKey);

        if (app()->environment(['local', 'staging'])) {
            Log::info('Internal API authorization attempt', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'authorized' => $isAuthorized,
                'header_present' => $providedKey !== null,
            ]);
        }

        if (! $isAuthorized) {
            return ApiResponse::error('Unauthorized internal request.', 401);
        }

        return $next($request);
    }
}
