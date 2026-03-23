<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token || ! str_contains($token, '.')) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid API credentials.',
            ], 401);
        }

        [$clientKey, $rawSecret] = explode('.', $token, 2);

        $client = ApiClient::where('client_key', $clientKey)->first();

        if (! $client || ! $client->verifySecret($rawSecret)) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid API credentials.',
            ], 401);
        }

        if (! $client->isActive()) {
            return response()->json([
                'status' => 0,
                'message' => 'API client is inactive.',
            ], 403);
        }

        if (! $client->allowsIp($request->ip())) {
            return response()->json([
                'status' => 0,
                'message' => 'IP address is not allowed for this API client.',
            ], 403);
        }

        $client->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
