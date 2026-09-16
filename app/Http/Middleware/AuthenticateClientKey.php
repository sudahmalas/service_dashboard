<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateClientKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extract API Key from multiple potential headers and parameters
        $apiKey = $request->header('X-Client-Key');

        if (!$apiKey && $request->bearerToken()) {
            $apiKey = $request->bearerToken();
        }

        if (!$apiKey) {
            $apiKey = $request->input('api_key');
        }

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key wajib disertakan melalui header X-Client-Key, Bearer token, atau parameter api_key.',
            ], 401);
        }

        // 2. Locate active client
        $client = Client::where('api_key', $apiKey)->first();

        if (!$client || $client->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'API Key tidak valid atau Client berstatus non-aktif.',
            ], 401);
        }

        // 3. Update client activity timestamp
        $client->touchLastSeen();

        // 4. Attach client instance to request and user resolver
        $request->attributes->set('client', $client);
        $request->setUserResolver(fn () => $client);

        return $next($request);
    }
}
