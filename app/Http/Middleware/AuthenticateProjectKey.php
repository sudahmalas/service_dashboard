<?php

namespace App\Http\Middleware;

use App\Models\ProjectClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateProjectKey
{
    /**
     * Handle an incoming request from Prima or external project application.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $projectKey = $request->header('X-Project-Key');

        if (!$projectKey && $request->bearerToken()) {
            $projectKey = $request->bearerToken();
        }

        if (!$projectKey) {
            $projectKey = $request->input('project_key');
        }

        if (!$projectKey) {
            return response()->json([
                'success' => false,
                'message' => 'Project API Key wajib disertakan via header X-Project-Key, Bearer token, atau parameter project_key.',
            ], 401);
        }

        $project = ProjectClient::where('api_key', $projectKey)->first();

        if (!$project || $project->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Project API Key tidak valid atau project sedang berstatus non-aktif.',
            ], 401);
        }

        $request->attributes->set('project', $project);
        $request->setUserResolver(fn () => $project);

        return $next($request);
    }
}
