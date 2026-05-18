<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StandardizeApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);

        if (! is_array($payload) || ! array_key_exists('success', $payload)) {
            return $response;
        }

        if (isset($payload['data']['pagination']) && is_array($payload['data']['pagination'])) {
            $payload['meta'] = $payload['data']['pagination'];
            unset($payload['data']['pagination']);

            if (count($payload['data']) === 1) {
                $payload['data'] = reset($payload['data']);
            }
        }

        if (($payload['success'] ?? true) === false && isset($payload['data']['errors'])) {
            $payload['errors'] = $payload['data']['errors'];
            unset($payload['data']);
        }

        $response->setData($payload);

        return $response;
    }
}
