<?php

namespace App\Http\Middleware;

use App\Models\ExternalApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateExternalApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (blank($bearerToken)) {
            return $this->unauthorized('Token API tidak ditemukan.');
        }

        $token = ExternalApiToken::query()
            ->where('token_hash', ExternalApiToken::hashToken($bearerToken))
            ->where('is_active', true)
            ->first();

        if (! $token) {
            return $this->unauthorized('Token API tidak valid.');
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->attributes->set('external_api_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
